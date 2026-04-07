<?php
/**
 * SGR Suite - Clase Visualizador de Gráficos
 *
 * Gestiona el Custom Post Type para gráficos, AJAX para datos,
 * shortcode de renderizado y configuración de visualizaciones D3Plus.
 *
 * @package SGR_Suite
 * @since   1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Visualizer {

    private const CPT_CHART       = 'sgr_chart';
    private const META_CONFIG     = '_sgr_chart_config';
    private const CACHE_TTL       = 900; // 15 minutos
    private const RATE_LIMIT      = 60;  // requests/minuto

    /** @var SGR_Suite_Database */
    private SGR_Suite_Database $database;

    /** @var SGR_Suite_Logger */
    private SGR_Suite_Logger $logger;

    public function __construct( SGR_Suite_Database $database, SGR_Suite_Logger $logger ) {
        $this->database = $database;
        $this->logger   = $logger;
    }

    /**
     * Registrar hooks.
     */
    public function register_hooks(): void {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::CPT_CHART, [ $this, 'save_chart_config' ] );

        // Shortcode
        add_shortcode( 'sgr_chart', [ $this, 'render_shortcode' ] );

        // AJAX para datos del gráfico (público y autenticado)
        add_action( 'wp_ajax_sgr_suite_get_chart_data', [ $this, 'ajax_get_chart_data' ] );
        add_action( 'wp_ajax_nopriv_sgr_suite_get_chart_data', [ $this, 'ajax_get_chart_data' ] );

        // AJAX admin: columnas disponibles y preview
        add_action( 'wp_ajax_sgr_suite_get_table_columns', [ $this, 'ajax_get_table_columns' ] );
        add_action( 'wp_ajax_sgr_suite_preview_chart_data', [ $this, 'ajax_preview_chart_data' ] );
    }

    /**
     * Registrar Custom Post Type para gráficos.
     */
    public function register_post_type(): void {
        $labels = [
            'name'               => esc_html__( 'Gráficos SGR', 'sgr-suite' ),
            'singular_name'      => esc_html__( 'Gráfico SGR', 'sgr-suite' ),
            'add_new'            => esc_html__( 'Nuevo Gráfico', 'sgr-suite' ),
            'add_new_item'       => esc_html__( 'Crear Nuevo Gráfico', 'sgr-suite' ),
            'edit_item'          => esc_html__( 'Editar Gráfico', 'sgr-suite' ),
            'view_item'          => esc_html__( 'Ver Gráfico', 'sgr-suite' ),
            'all_items'          => esc_html__( 'Todos los Gráficos', 'sgr-suite' ),
            'search_items'       => esc_html__( 'Buscar Gráficos', 'sgr-suite' ),
            'not_found'          => esc_html__( 'No se encontraron gráficos.', 'sgr-suite' ),
            'not_found_in_trash' => esc_html__( 'No hay gráficos en la papelera.', 'sgr-suite' ),
        ];

        register_post_type( self::CPT_CHART, [
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'sgr-suite',
            'supports'     => [ 'title' ],
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ] );
    }

    /**
     * Agregar meta box de configuración.
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'sgr_chart_config',
            esc_html__( 'Configuración del Gráfico', 'sgr-suite' ),
            [ $this, 'render_meta_box' ],
            self::CPT_CHART,
            'normal',
            'high'
        );

        add_meta_box(
            'sgr_chart_shortcode',
            esc_html__( 'Shortcode', 'sgr-suite' ),
            [ $this, 'render_shortcode_box' ],
            self::CPT_CHART,
            'side',
            'high'
        );
    }

    /**
     * Renderizar meta box de configuración.
     */
    public function render_meta_box( \WP_Post $post ): void {
        $config = $this->get_chart_config( $post->ID );
        include SGR_SUITE_PATH . 'templates/admin/chart-config.php';
    }

    /**
     * Renderizar meta box de shortcode.
     */
    public function render_shortcode_box( \WP_Post $post ): void {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<p class="description">' . esc_html__( 'Guarde el gráfico para obtener el shortcode.', 'sgr-suite' ) . '</p>';
            return;
        }
        echo '<p><code>[sgr_chart id="' . esc_html( $post->ID ) . '"]</code></p>';
        echo '<p class="description">' . esc_html__( 'Copie y pegue este shortcode en cualquier página o entrada.', 'sgr-suite' ) . '</p>';
    }

    /**
     * Guardar configuración del gráfico.
     */
    public function save_chart_config( int $post_id ): void {
        if ( ! isset( $_POST['sgr_chart_config_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sgr_chart_config_nonce'] ) ), 'sgr_chart_config_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $config = $this->sanitize_chart_config( $_POST['sgr_chart'] ?? [] );
        update_post_meta( $post_id, self::META_CONFIG, $config );

        // Limpiar cache
        delete_transient( 'sgr_chart_data_' . $post_id );

        $this->logger->info( "Gráfico #{$post_id} configuración guardada." );
    }

    /**
     * Sanitizar configuración del gráfico.
     */
    private function sanitize_chart_config( array $raw ): array {
        $allowed_types = $this->get_chart_types();
        $allowed_agg   = [ 'SUM', 'COUNT', 'AVG', 'MAX', 'MIN' ];
        $allowed_tables = $this->get_available_tables();

        $config = [
            'chart_type'      => in_array( $raw['chart_type'] ?? '', array_keys( $allowed_types ), true )
                                 ? $raw['chart_type'] : 'bar',
            'data_source'     => in_array( $raw['data_source'] ?? '', array_keys( $allowed_tables ), true )
                                 ? $raw['data_source'] : 'proyectos',
            'x_field'         => sanitize_text_field( $raw['x_field'] ?? '' ),
            'y_field'         => sanitize_text_field( $raw['y_field'] ?? '' ),
            'aggregate'       => in_array( strtoupper( $raw['aggregate'] ?? 'COUNT' ), $allowed_agg, true )
                                 ? strtoupper( $raw['aggregate'] ) : 'COUNT',
            'group_by'        => sanitize_text_field( $raw['group_by'] ?? '' ),
            'order_by'        => sanitize_text_field( $raw['order_by'] ?? '' ),
            'order_dir'       => in_array( strtoupper( $raw['order_dir'] ?? 'DESC' ), [ 'ASC', 'DESC' ], true )
                                 ? strtoupper( $raw['order_dir'] ) : 'DESC',
            'limit'           => min( absint( $raw['limit'] ?? 20 ), 500 ),
            'chart_height'    => max( 200, min( absint( $raw['chart_height'] ?? 400 ), 1200 ) ),
            'show_legend'     => ! empty( $raw['show_legend'] ),
            'show_toolbar'    => ! empty( $raw['show_toolbar'] ),
            'number_format'   => in_array( $raw['number_format'] ?? 'colombiano', [ 'colombiano', 'millones', 'internacional', 'sin_formato' ], true )
                                 ? $raw['number_format'] : 'colombiano',
            'colors'          => $this->sanitize_colors( $raw['colors'] ?? '' ),
            'x_axis_title'    => sanitize_text_field( $raw['x_axis_title'] ?? '' ),
            'y_axis_title'    => sanitize_text_field( $raw['y_axis_title'] ?? '' ),
        ];

        // Filtros
        $config['filters'] = [];
        if ( ! empty( $raw['filters'] ) && is_array( $raw['filters'] ) ) {
            $allowed_operators = [ '=', '!=', '>', '<', '>=', '<=', 'LIKE' ];
            foreach ( $raw['filters'] as $filter ) {
                $f_field = sanitize_text_field( $filter['field'] ?? '' );
                $f_op    = in_array( $filter['operator'] ?? '', $allowed_operators, true ) ? $filter['operator'] : '=';
                $f_val   = sanitize_text_field( $filter['value'] ?? '' );
                if ( $f_field && $f_val ) {
                    $config['filters'][] = [
                        'field'    => $f_field,
                        'operator' => $f_op,
                        'value'    => $f_val,
                    ];
                }
            }
        }

        return $config;
    }

    /**
     * Sanitizar colores hex.
     */
    private function sanitize_colors( string $colors_str ): array {
        if ( empty( $colors_str ) ) {
            return $this->get_default_colors();
        }
        $colors = array_map( 'trim', explode( ',', $colors_str ) );
        $valid  = [];
        foreach ( $colors as $c ) {
            if ( preg_match( '/^#[0-9A-Fa-f]{6}$/', $c ) ) {
                $valid[] = $c;
            }
        }
        return ! empty( $valid ) ? $valid : $this->get_default_colors();
    }

    /**
     * Colores por defecto del tema SGR.
     */
    public function get_default_colors(): array {
        return [
            '#348afb', '#1e40af', '#059669', '#d97706',
            '#dc2626', '#7c3aed', '#0891b2', '#be185d',
            '#65a30d', '#ea580c', '#4f46e5', '#0d9488',
        ];
    }

    /**
     * Tipos de gráficos soportados.
     */
    public function get_chart_types(): array {
        return [
            'bar'         => esc_html__( 'Barras', 'sgr-suite' ),
            'stacked_bar' => esc_html__( 'Barras Apiladas', 'sgr-suite' ),
            'grouped_bar' => esc_html__( 'Barras Agrupadas', 'sgr-suite' ),
            'line'        => esc_html__( 'Líneas', 'sgr-suite' ),
            'area'        => esc_html__( 'Área', 'sgr-suite' ),
            'pie'         => esc_html__( 'Pastel (Pie)', 'sgr-suite' ),
            'donut'       => esc_html__( 'Dona (Donut)', 'sgr-suite' ),
            'treemap'     => esc_html__( 'Mapa de Árbol (Treemap)', 'sgr-suite' ),
            'pack'        => esc_html__( 'Burbujas (Pack)', 'sgr-suite' ),
        ];
    }

    /**
     * Tablas disponibles para gráficos (whitelist).
     */
    public function get_available_tables(): array {
        return [
            'proyectos' => esc_html__( 'Proyectos', 'sgr-suite' ),
            'contratos' => esc_html__( 'Contratos', 'sgr-suite' ),
            'municipios' => esc_html__( 'Municipios', 'sgr-suite' ),
            'metas'     => esc_html__( 'Metas', 'sgr-suite' ),
        ];
    }

    /**
     * Obtener columnas de una tabla (con cache).
     */
    public function get_table_columns( string $table_key ): array {
        global $wpdb;

        $available = $this->get_available_tables();
        if ( ! isset( $available[ $table_key ] ) ) {
            return [];
        }

        $cache_key = 'sgr_cols_' . $table_key;
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $table_name = $this->database->table( $table_key );
        $results    = $wpdb->get_results( "DESCRIBE {$table_name}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $columns = [];
        foreach ( $results as $row ) {
            $columns[] = [
                'name'     => $row['Field'],
                'type'     => $row['Type'],
                'nullable' => $row['Null'] === 'YES',
            ];
        }

        set_transient( $cache_key, $columns, 300 );

        return $columns;
    }

    /**
     * Obtener configuración de un gráfico.
     */
    public function get_chart_config( int $chart_id ): array {
        $config = get_post_meta( $chart_id, self::META_CONFIG, true );
        if ( ! is_array( $config ) ) {
            return [
                'chart_type'    => 'bar',
                'data_source'   => 'proyectos',
                'x_field'       => 'dependencia_proyecto',
                'y_field'       => 'valor_proyecto',
                'aggregate'     => 'SUM',
                'group_by'      => '',
                'order_by'      => '',
                'order_dir'     => 'DESC',
                'limit'         => 20,
                'chart_height'  => 400,
                'show_legend'   => true,
                'show_toolbar'  => true,
                'number_format' => 'colombiano',
                'colors'        => $this->get_default_colors(),
                'filters'       => [],
                'x_axis_title'  => '',
                'y_axis_title'  => '',
            ];
        }
        return $config;
    }

    /**
     * Construir y ejecutar consulta de datos para gráficos.
     */
    public function get_chart_data( int $chart_id ): array {
        $config = $this->get_chart_config( $chart_id );

        $available = $this->get_available_tables();
        $source    = $config['data_source'] ?? 'proyectos';
        if ( ! isset( $available[ $source ] ) ) {
            return [];
        }

        // Verificar que los campos existen en la tabla
        $columns     = $this->get_table_columns( $source );
        $column_names = array_column( $columns, 'name' );

        $x_field = $config['x_field'] ?? '';
        $y_field = $config['y_field'] ?? '';

        if ( ! in_array( $x_field, $column_names, true ) ) {
            return [];
        }

        global $wpdb;
        $table_name = $this->database->table( $source );
        $aggregate  = $config['aggregate'] ?? 'COUNT';

        // Construir SELECT
        if ( ! empty( $y_field ) && in_array( $y_field, $column_names, true ) ) {
            $select = "{$x_field} AS label, {$aggregate}({$y_field}) AS value";
        } else {
            $select = "{$x_field} AS label, COUNT(*) AS value";
        }

        // Group By
        $group_by_field = $config['group_by'] ?? '';
        if ( ! empty( $group_by_field ) && in_array( $group_by_field, $column_names, true ) ) {
            $select .= ", {$group_by_field} AS series";
        }

        // WHERE con filtros
        $where   = [ '1=1' ];
        $prepare = [];
        if ( ! empty( $config['filters'] ) ) {
            $allowed_ops = [ '=', '!=', '>', '<', '>=', '<=', 'LIKE' ];
            foreach ( $config['filters'] as $filter ) {
                $f_field = $filter['field'] ?? '';
                $f_op    = $filter['operator'] ?? '=';
                $f_val   = $filter['value'] ?? '';

                if ( ! in_array( $f_field, $column_names, true ) || ! in_array( $f_op, $allowed_ops, true ) ) {
                    continue;
                }

                if ( 'LIKE' === $f_op ) {
                    $where[]   = "{$f_field} LIKE %s";
                    $prepare[] = '%' . $wpdb->esc_like( $f_val ) . '%';
                } else {
                    $where[]   = "{$f_field} {$f_op} %s";
                    $prepare[] = $f_val;
                }
            }
        }

        $where_sql = implode( ' AND ', $where );

        // GROUP BY
        $group_sql = "GROUP BY {$x_field}";
        if ( ! empty( $group_by_field ) && in_array( $group_by_field, $column_names, true ) ) {
            $group_sql .= ", {$group_by_field}";
        }

        // ORDER BY
        $order_field = $config['order_by'] ?? '';
        $order_dir   = strtoupper( $config['order_dir'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
        if ( ! empty( $order_field ) && in_array( $order_field, array_merge( $column_names, [ 'value' ] ), true ) ) {
            $order_sql = "ORDER BY {$order_field} {$order_dir}";
        } else {
            $order_sql = "ORDER BY value {$order_dir}";
        }

        // LIMIT
        $limit = min( absint( $config['limit'] ?? 20 ), 500 );
        $limit_sql = $wpdb->prepare( 'LIMIT %d', $limit );

        $query = "SELECT {$select} FROM {$table_name} WHERE {$where_sql} {$group_sql} {$order_sql} {$limit_sql}";

        if ( ! empty( $prepare ) ) {
            $query = $wpdb->prepare( $query, ...$prepare ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        $results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return is_array( $results ) ? $results : [];
    }

    /**
     * AJAX: Obtener datos del gráfico (público).
     */
    public function ajax_get_chart_data(): void {
        $chart_id = absint( $_POST['chart_id'] ?? 0 );
        if ( ! $chart_id ) {
            wp_send_json_error( [ 'message' => 'ID de gráfico requerido.' ] );
        }

        // Rate limiting
        if ( $this->is_rate_limited() ) {
            wp_send_json_error( [ 'message' => 'Demasiadas solicitudes. Intente de nuevo en un minuto.' ], 429 );
        }

        // Verificar nonce (uno por gráfico)
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'sgr_chart_' . $chart_id ) ) {
            wp_send_json_error( [ 'message' => 'Token de seguridad inválido.' ], 403 );
        }

        // Verificar que el post existe
        $post = get_post( $chart_id );
        if ( ! $post || self::CPT_CHART !== $post->post_type || 'publish' !== $post->post_status ) {
            wp_send_json_error( [ 'message' => 'Gráfico no encontrado.' ], 404 );
        }

        // Cache
        $cache_key = 'sgr_chart_data_' . $chart_id;
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            wp_send_json_success( $cached );
        }

        $config = $this->get_chart_config( $chart_id );
        $data   = $this->get_chart_data( $chart_id );

        $response = [
            'data'   => $data,
            'config' => [
                'chart_type'    => $config['chart_type'],
                'chart_height'  => $config['chart_height'],
                'show_legend'   => $config['show_legend'],
                'show_toolbar'  => $config['show_toolbar'],
                'number_format' => $config['number_format'],
                'colors'        => $config['colors'],
                'x_axis_title'  => $config['x_axis_title'],
                'y_axis_title'  => $config['y_axis_title'],
            ],
        ];

        set_transient( $cache_key, $response, self::CACHE_TTL );

        wp_send_json_success( $response );
    }

    /**
     * AJAX: Obtener columnas de tabla (admin).
     */
    public function ajax_get_table_columns(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $table = sanitize_text_field( wp_unslash( $_POST['table'] ?? '' ) );
        $cols  = $this->get_table_columns( $table );

        wp_send_json_success( [ 'columns' => $cols ] );
    }

    /**
     * AJAX: Preview de datos (admin).
     */
    public function ajax_preview_chart_data(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $chart_id = absint( $_POST['chart_id'] ?? 0 );
        if ( ! $chart_id ) {
            wp_send_json_error( [ 'message' => 'ID de gráfico requerido.' ] );
        }

        $data = $this->get_chart_data( $chart_id );

        wp_send_json_success( [
            'data'  => $data,
            'count' => count( $data ),
        ] );
    }

    /**
     * Shortcode [sgr_chart id="X"].
     */
    public function render_shortcode( $atts ): string {
        $atts = shortcode_atts( [
            'id'     => 0,
            'height' => 0,
            'class'  => '',
        ], $atts, 'sgr_chart' );

        $chart_id = absint( $atts['id'] );
        if ( ! $chart_id ) {
            return '<!-- SGR Chart: ID requerido -->';
        }

        $post = get_post( $chart_id );
        if ( ! $post || self::CPT_CHART !== $post->post_type || 'publish' !== $post->post_status ) {
            return '<!-- SGR Chart: Gráfico no encontrado -->';
        }

        $config = $this->get_chart_config( $chart_id );

        // Override de altura
        if ( ! empty( $atts['height'] ) ) {
            $config['chart_height'] = max( 200, min( absint( $atts['height'] ), 1200 ) );
        }

        // Encolar assets
        $this->enqueue_chart_assets();

        // Generar nonce para el chart
        $nonce = wp_create_nonce( 'sgr_chart_' . $chart_id );

        $uid        = wp_unique_id( 'sgr-chart-' );
        $extra_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

        ob_start();
        include SGR_SUITE_PATH . 'templates/frontend/chart.php';
        return ob_get_clean();
    }

    /**
     * Encolar assets necesarios para los gráficos.
     */
    private function enqueue_chart_assets(): void {
        static $enqueued = false;
        if ( $enqueued ) {
            return;
        }
        $enqueued = true;

        // D3Plus desde CDN con fallback
        wp_enqueue_script(
            'sgr-d3plus',
            'https://cdn.jsdelivr.net/npm/d3plus@2/es/d3plus.full.min.js',
            [],
            '2.1.3',
            true
        );

        wp_enqueue_style(
            'sgr-suite-frontend',
            SGR_SUITE_URL . 'assets/css/frontend.css',
            [],
            SGR_SUITE_VERSION
        );

        wp_enqueue_script(
            'sgr-suite-charts',
            SGR_SUITE_URL . 'assets/js/frontend-charts.js',
            [ 'sgr-d3plus' ],
            SGR_SUITE_VERSION,
            true
        );

        wp_localize_script( 'sgr-suite-charts', 'sgrCharts', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        ] );
    }

    /**
     * Encolar assets admin para gráficos.
     */
    public function enqueue_admin_chart_assets( string $hook ): void {
        global $post_type;
        if ( self::CPT_CHART !== $post_type ) {
            return;
        }

        wp_enqueue_style(
            'sgr-suite-admin',
            SGR_SUITE_URL . 'assets/css/admin.css',
            [],
            SGR_SUITE_VERSION
        );

        wp_enqueue_script(
            'sgr-suite-admin-charts',
            SGR_SUITE_URL . 'assets/js/admin-charts.js',
            [ 'jquery' ],
            SGR_SUITE_VERSION,
            true
        );

        wp_localize_script( 'sgr-suite-admin-charts', 'sgrChartsAdmin', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'sgr_suite_admin_nonce' ),
            'tables'   => $this->get_available_tables(),
        ] );
    }

    /**
     * Rate limiting basado en IP.
     */
    private function is_rate_limited(): bool {
        $ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
        $key = 'sgr_rate_' . md5( $ip );

        $count = (int) get_transient( $key );
        if ( $count >= self::RATE_LIMIT ) {
            return true;
        }

        set_transient( $key, $count + 1, 60 );
        return false;
    }

    /**
     * Contar gráficos publicados.
     */
    public function count_charts(): int {
        $counts = wp_count_posts( self::CPT_CHART );
        return (int) ( $counts->publish ?? 0 );
    }
}
