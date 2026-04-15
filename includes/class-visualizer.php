<?php
/**
 * SGR Suite - Clase Visualizador de Gráficos v2.0.0
 *
 * Custom Post Type para gráficos con soporte de vistas predefinidas
 * con JOINs entre tablas, D3Plus v2 correcto, rate limiting y cache.
 *
 * @package SGR_Suite
 * @since   1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Visualizer {

    private const CPT_CHART   = 'sgr_chart';
    private const META_CONFIG = '_sgr_chart_config';
    private const CACHE_TTL   = 900;
    private const RATE_LIMIT  = 60;

    /** @var SGR_Suite_Database */
    private SGR_Suite_Database $database;

    /** @var SGR_Suite_Logger */
    private SGR_Suite_Logger $logger;

    public function __construct( SGR_Suite_Database $database, SGR_Suite_Logger $logger ) {
        $this->database = $database;
        $this->logger   = $logger;
    }

    public function register_hooks(): void {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::CPT_CHART, [ $this, 'save_chart_config' ] );

        add_shortcode( 'sgr_chart', [ $this, 'render_shortcode' ] );

        add_action( 'wp_ajax_sgr_suite_get_chart_data', [ $this, 'ajax_get_chart_data' ] );
        add_action( 'wp_ajax_nopriv_sgr_suite_get_chart_data', [ $this, 'ajax_get_chart_data' ] );
        add_action( 'wp_ajax_sgr_suite_preview_chart_data', [ $this, 'ajax_preview_chart_data' ] );
    }

    public function register_post_type(): void {
        register_post_type( self::CPT_CHART, [
            'labels' => [
                'name'               => esc_html__( 'Gráficos SGR', 'sgr-suite' ),
                'singular_name'      => esc_html__( 'Gráfico SGR', 'sgr-suite' ),
                'add_new'            => esc_html__( 'Nuevo Gráfico', 'sgr-suite' ),
                'add_new_item'       => esc_html__( 'Crear Nuevo Gráfico', 'sgr-suite' ),
                'edit_item'          => esc_html__( 'Editar Gráfico', 'sgr-suite' ),
                'all_items'          => esc_html__( 'Gráficos', 'sgr-suite' ),
                'search_items'       => esc_html__( 'Buscar Gráficos', 'sgr-suite' ),
                'not_found'          => esc_html__( 'No se encontraron gráficos.', 'sgr-suite' ),
            ],
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'sgr-suite',
            'supports'        => [ 'title' ],
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ] );
    }

    public function add_meta_boxes(): void {
        add_meta_box( 'sgr_chart_config', esc_html__( 'Configuración del Gráfico', 'sgr-suite' ), [ $this, 'render_meta_box' ], self::CPT_CHART, 'normal', 'high' );
        add_meta_box( 'sgr_chart_shortcode', esc_html__( 'Shortcode', 'sgr-suite' ), [ $this, 'render_shortcode_box' ], self::CPT_CHART, 'side', 'high' );
        add_meta_box( 'sgr_chart_preview', esc_html__( 'Vista Previa', 'sgr-suite' ), [ $this, 'render_preview_box' ], self::CPT_CHART, 'side', 'default' );
    }

    public function render_meta_box( \WP_Post $post ): void {
        $config = $this->get_chart_config( $post->ID );
        include SGR_SUITE_PATH . 'templates/admin/chart-config.php';
    }

    public function render_shortcode_box( \WP_Post $post ): void {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<p class="description">' . esc_html__( 'Guarde el gráfico para obtener el shortcode.', 'sgr-suite' ) . '</p>';
            return;
        }
        echo '<div style="background:#f0f9ff;padding:12px;border:1px solid #348afb;margin:-6px -12px;">';
        echo '<code style="font-size:13px;display:block;word-break:break-all;">[sgr_chart id="' . esc_html( $post->ID ) . '"]</code>';
        echo '</div>';
    }

    public function render_preview_box( \WP_Post $post ): void {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<p class="description">' . esc_html__( 'Guarde para ver la vista previa.', 'sgr-suite' ) . '</p>';
            return;
        }
        echo '<div id="sgr-chart-preview-area" style="min-height:200px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px;">';
        echo '<p class="description" style="text-align:center;padding:40px 10px;">' . esc_html__( 'La vista previa se mostrará al guardar.', 'sgr-suite' ) . '</p>';
        echo '</div>';
        echo '<p style="margin-top:8px;"><button type="button" id="sgr-btn-preview" class="button button-secondary" style="width:100%;">' . esc_html__( 'Actualizar Vista Previa', 'sgr-suite' ) . '</button></p>';
    }

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

        $raw = $_POST['sgr_chart'] ?? [];

        $views       = $this->database->get_chart_views();
        $view_keys   = array_keys( $views );
        $chart_types = array_keys( $this->get_chart_types() );

        $config = [
            'chart_type'    => in_array( $raw['chart_type'] ?? '', $chart_types, true ) ? $raw['chart_type'] : 'bar',
            'data_view'     => in_array( $raw['data_view'] ?? '', $view_keys, true ) ? $raw['data_view'] : 'valor_por_dependencia',
            'limit'         => min( absint( $raw['limit'] ?? 20 ), 500 ),
            'order_dir'     => in_array( strtoupper( $raw['order_dir'] ?? 'DESC' ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $raw['order_dir'] ) : 'DESC',
            'chart_height'  => max( 200, min( absint( $raw['chart_height'] ?? 400 ), 1200 ) ),
            'show_legend'   => ! empty( $raw['show_legend'] ),
            'show_toolbar'  => ! empty( $raw['show_toolbar'] ),
            'number_format' => in_array( $raw['number_format'] ?? 'colombiano', [ 'colombiano', 'millones', 'internacional', 'sin_formato' ], true ) ? $raw['number_format'] : 'colombiano',
            'colors'        => $this->sanitize_colors( $raw['colors'] ?? '' ),
        ];

        update_post_meta( $post_id, self::META_CONFIG, $config );
        delete_transient( 'sgr_chart_data_' . $post_id );

        $this->logger->info( "Gráfico #{$post_id} configuración guardada." );
    }

    private function sanitize_colors( string $colors_str ): array {
        if ( empty( $colors_str ) ) {
            return $this->get_default_colors();
        }
        $valid = [];
        foreach ( array_map( 'trim', explode( ',', $colors_str ) ) as $c ) {
            if ( preg_match( '/^#[0-9A-Fa-f]{6}$/', $c ) ) {
                $valid[] = $c;
            }
        }
        return ! empty( $valid ) ? $valid : $this->get_default_colors();
    }

    public function get_default_colors(): array {
        return [ '#348afb', '#1e40af', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#be185d', '#65a30d', '#ea580c', '#4f46e5', '#0d9488' ];
    }

    /**
     * Tipos de gráficos soportados (solo los que existen en D3Plus v2).
     */
    public function get_chart_types(): array {
        return [
            'bar'          => [ 'label' => esc_html__( 'Barras', 'sgr-suite' ) ],
            'line'         => [ 'label' => esc_html__( 'Líneas', 'sgr-suite' ) ],
            'area'         => [ 'label' => esc_html__( 'Área', 'sgr-suite' ) ],
            'pie'          => [ 'label' => esc_html__( 'Pie / Torta', 'sgr-suite' ) ],
            'donut'        => [ 'label' => esc_html__( 'Donut', 'sgr-suite' ) ],
            'treemap'      => [ 'label' => esc_html__( 'Treemap', 'sgr-suite' ) ],
            'barH'         => [ 'label' => esc_html__( 'Barras Horizontales', 'sgr-suite' ) ],
            'pack'         => [ 'label' => esc_html__( 'Burbujas (Pack)', 'sgr-suite' ) ],
            'stacked_bar'  => [ 'label' => esc_html__( 'Barras Apiladas', 'sgr-suite' ) ],
            'grouped_bar'  => [ 'label' => esc_html__( 'Barras Agrupadas', 'sgr-suite' ) ],
            'scatter'      => [ 'label' => esc_html__( 'Dispersión (Scatter)', 'sgr-suite' ) ],
        ];
    }

    public function get_chart_config( int $chart_id ): array {
        $config = get_post_meta( $chart_id, self::META_CONFIG, true );
        if ( ! is_array( $config ) ) {
            return [
                'chart_type'    => 'bar',
                'data_view'     => 'valor_por_dependencia',
                'limit'         => 20,
                'order_dir'     => 'DESC',
                'chart_height'  => 400,
                'show_legend'   => true,
                'show_toolbar'  => true,
                'number_format' => 'colombiano',
                'colors'        => $this->get_default_colors(),
            ];
        }
        return $config;
    }

    /**
     * Obtener datos para un gráfico usando la vista predefinida.
     */
    public function get_chart_data( int $chart_id ): array {
        $config = $this->get_chart_config( $chart_id );
        return $this->database->execute_chart_view(
            $config['data_view'] ?? 'valor_por_dependencia',
            $config['limit'] ?? 20,
            $config['order_dir'] ?? 'DESC'
        );
    }

    /**
     * AJAX: Obtener datos del gráfico (público).
     */
    public function ajax_get_chart_data(): void {
        $chart_id = isset( $_POST['chart_id'] ) ? absint( wp_unslash( $_POST['chart_id'] ) ) : 0;
        if ( ! $chart_id ) {
            wp_send_json_error( [ 'message' => 'ID requerido.' ] );
        }

        if ( $this->is_rate_limited() ) {
            wp_send_json_error( [ 'message' => 'Demasiadas solicitudes.' ], 429 );
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'sgr_chart_' . $chart_id ) ) {
            wp_send_json_error( [ 'message' => 'Token inválido.' ], 403 );
        }

        $post = get_post( $chart_id );
        if ( ! $post || self::CPT_CHART !== $post->post_type || 'publish' !== $post->post_status ) {
            wp_send_json_error( [ 'message' => 'Gráfico no encontrado.' ], 404 );
        }

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
            ],
        ];

        set_transient( $cache_key, $response, self::CACHE_TTL );
        wp_send_json_success( $response );
    }

    /**
     * AJAX: Preview de datos (admin).
     */
    public function ajax_preview_chart_data(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $view_key  = sanitize_text_field( wp_unslash( $_POST['data_view'] ?? 'valor_por_dependencia' ) );
        $limit     = min( absint( wp_unslash( $_POST['limit'] ?? 20 ) ), 500 );
        $order_dir = sanitize_text_field( wp_unslash( $_POST['order_dir'] ?? 'DESC' ) );

        // Validar que la vista exista contra la whitelist de vistas predefinidas.
        $views = $this->database->get_chart_views();
        if ( ! isset( $views[ $view_key ] ) ) {
            wp_send_json_error( [ 'message' => 'Vista inválida.' ], 400 );
        }

        $data = $this->database->execute_chart_view( $view_key, $limit, $order_dir );

        wp_send_json_success( [ 'data' => $data, 'count' => count( $data ) ] );
    }

    /**
     * Shortcode [sgr_chart id="X"].
     */
    public function render_shortcode( $atts ): string {
        $atts     = shortcode_atts( [ 'id' => 0, 'height' => 0, 'class' => '' ], $atts, 'sgr_chart' );
        $chart_id = absint( $atts['id'] );

        if ( ! $chart_id ) {
            return '<!-- SGR Chart: ID requerido -->';
        }

        $post = get_post( $chart_id );
        if ( ! $post || self::CPT_CHART !== $post->post_type || 'publish' !== $post->post_status ) {
            return '<!-- SGR Chart: No encontrado -->';
        }

        $config = $this->get_chart_config( $chart_id );
        if ( ! empty( $atts['height'] ) ) {
            $config['chart_height'] = max( 200, min( absint( $atts['height'] ), 1200 ) );
        }

        $this->enqueue_chart_assets();

        $nonce       = wp_create_nonce( 'sgr_chart_' . $chart_id );
        $uid         = 'sgr-chart-' . $chart_id . '-' . wp_rand( 1000, 9999 );
        // El escape final ocurre en el template; aquí sólo se normaliza.
        $extra_class = ! empty( $atts['class'] ) ? sanitize_html_class( $atts['class'] ) : '';

        ob_start();
        include SGR_SUITE_PATH . 'templates/frontend/chart.php';
        return ob_get_clean();
    }

    private function enqueue_chart_assets(): void {
        static $enqueued = false;
        if ( $enqueued ) {
            return;
        }
        $enqueued = true;

        wp_enqueue_script( 'sgr-d3plus', 'https://cdn.jsdelivr.net/npm/d3plus@2.0.2/build/d3plus.full.min.js', [], '2.0.2', true );
        wp_enqueue_style( 'sgr-suite-frontend', SGR_SUITE_URL . 'assets/css/frontend.css', [], SGR_SUITE_VERSION );
        wp_enqueue_script( 'sgr-suite-charts', SGR_SUITE_URL . 'assets/js/frontend-charts.js', [ 'sgr-d3plus' ], SGR_SUITE_VERSION, true );
        wp_localize_script( 'sgr-suite-charts', 'sgrCharts', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ] );
    }

    public function enqueue_admin_chart_assets( string $hook ): void {
        global $post_type;
        if ( self::CPT_CHART !== $post_type ) {
            return;
        }

        wp_enqueue_style( 'sgr-suite-admin', SGR_SUITE_URL . 'assets/css/admin.css', [], SGR_SUITE_VERSION );
        wp_enqueue_script( 'sgr-suite-admin-charts', SGR_SUITE_URL . 'assets/js/admin-charts.js', [ 'jquery' ], SGR_SUITE_VERSION, true );
        wp_localize_script( 'sgr-suite-admin-charts', 'sgrChartsAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sgr_suite_admin_nonce' ),
        ] );
    }

    private function is_rate_limited(): bool {
        $ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
        $key   = 'sgr_rate_' . md5( $ip );
        $count = (int) get_transient( $key );

        set_transient( $key, $count + 1, 60 );

        return $count >= self::RATE_LIMIT;
    }

    public function count_charts(): int {
        $counts = wp_count_posts( self::CPT_CHART );
        return (int) ( $counts->publish ?? 0 );
    }
}
