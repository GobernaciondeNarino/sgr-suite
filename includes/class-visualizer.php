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
        // Vista previa a ancho completo (debajo de la configuración) para
        // que el chart real tenga espacio suficiente — antes estaba en la
        // columna lateral y quedaba demasiado angosto.
        add_meta_box( 'sgr_chart_preview', esc_html__( 'Vista Previa del Gráfico', 'sgr-suite' ), [ $this, 'render_preview_box' ], self::CPT_CHART, 'normal', 'low' );

        // Columna lateral (debajo de Publicar + Shortcode).
        add_meta_box( 'sgr_chart_shortcode', esc_html__( 'Shortcode', 'sgr-suite' ), [ $this, 'render_shortcode_box' ], self::CPT_CHART, 'side', 'high' );
        add_meta_box( 'sgr_chart_data_widget', esc_html__( 'Datos de la Vista', 'sgr-suite' ), [ $this, 'render_data_widget_box' ], self::CPT_CHART, 'side', 'low' );
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

    /**
     * Widget lateral con la tabla de datos de la vista seleccionada.
     *
     * Se puebla en vivo desde admin-charts.js al refrescar la vista previa
     * del gráfico (mismo AJAX), así el editor puede inspeccionar qué
     * información existe antes de decidir el tipo de gráfico.
     */
    public function render_data_widget_box( \WP_Post $post ): void {
        echo '<p class="description" style="margin:0 0 10px;">';
        echo esc_html__( 'Muestra los primeros registros devueltos por la vista seleccionada. Se actualiza automáticamente al cambiar la vista.', 'sgr-suite' );
        echo '</p>';
        echo '<div id="sgr-chart-data-widget-area" class="sgr-chart-data-widget">';
        echo '<p class="description sgr-data-widget-placeholder" style="text-align:center;padding:24px 6px;">';
        echo esc_html__( 'Selecciona una vista para ver los datos.', 'sgr-suite' );
        echo '</p>';
        echo '</div>';
    }

    public function render_preview_box( \WP_Post $post ): void {
        $is_new = 'auto-draft' === $post->post_status;
        echo '<p class="description" style="margin:0 0 10px;">';
        if ( $is_new ) {
            echo esc_html__( 'Configura el gráfico arriba; al cambiar cualquier parámetro se actualiza la vista previa automáticamente.', 'sgr-suite' );
        } else {
            echo esc_html__( 'La vista previa se actualiza al cambiar la configuración. También puedes forzar la actualización con el botón.', 'sgr-suite' );
        }
        echo '</p>';
        echo '<div id="sgr-chart-preview-area" style="min-height:360px;">';
        echo '<p class="description sgr-preview-loading">' . esc_html__( 'Cargando vista previa…', 'sgr-suite' ) . '</p>';
        echo '</div>';
        echo '<p style="margin-top:10px;text-align:right;">';
        echo '<button type="button" id="sgr-btn-preview" class="button button-secondary">';
        echo esc_html__( 'Actualizar Vista Previa', 'sgr-suite' );
        echo '</button></p>';
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

        $data_view    = in_array( $raw['data_view'] ?? '', $view_keys, true )
            ? $raw['data_view']
            : 'valor_por_dependencia';
        $chart_type   = in_array( $raw['chart_type'] ?? '', $chart_types, true )
            ? $raw['chart_type']
            : 'bar';

        // Validar compatibilidad vista ↔ tipo: si el usuario envía una
        // combinación incompatible, se fuerza al primer tipo compatible
        // declarado para esa vista.
        $compat_charts = $this->get_view_chart_compatibility()[ $data_view ] ?? [];
        if ( ! empty( $compat_charts ) && ! in_array( $chart_type, $compat_charts, true ) ) {
            $this->logger->warning( sprintf(
                'Gráfico #%d: combinación incompatible %s + %s → auto-corregida a %s.',
                $post_id, $chart_type, $data_view, $compat_charts[0]
            ) );
            $chart_type = $compat_charts[0];
        }

        $config = [
            'chart_type'        => $chart_type,
            'data_view'         => $data_view,
            'limit'             => min( absint( $raw['limit'] ?? 20 ), 500 ),
            'order_dir'         => in_array( strtoupper( $raw['order_dir'] ?? 'DESC' ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $raw['order_dir'] ) : 'DESC',
            'chart_height'      => max( 200, min( absint( $raw['chart_height'] ?? 400 ), 1200 ) ),
            'show_legend'       => ! empty( $raw['show_legend'] ),
            'show_toolbar'      => ! empty( $raw['show_toolbar'] ),
            'number_format'     => in_array( $raw['number_format'] ?? 'colombiano', [ 'colombiano', 'millones', 'internacional', 'sin_formato' ], true ) ? $raw['number_format'] : 'colombiano',
            'colors'            => $this->sanitize_colors( $raw['colors'] ?? '' ),
            // Opciones visuales (v2.4.0+).
            'legend_mode'       => in_array( $raw['legend_mode'] ?? 'auto', [ 'auto', 'text', 'icons', 'hidden' ], true ) ? $raw['legend_mode'] : 'auto',
            'x_labels_rotate'   => max( 0, min( absint( $raw['x_labels_rotate'] ?? 0 ), 90 ) ),
            'x_labels_size'     => max( 8, min( absint( $raw['x_labels_size'] ?? 12 ), 24 ) ),
            'x_labels_visible'  => ! empty( $raw['x_labels_visible'] ),
            // Títulos personalizables de los ejes (v2.5.3). Vacío = sin título.
            'x_title'           => sanitize_text_field( wp_unslash( $raw['x_title'] ?? '' ) ),
            'y_title'           => sanitize_text_field( wp_unslash( $raw['y_title'] ?? '' ) ),
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
     * Catálogo de iconos SVG para leyendas personalizadas (v2.4.0).
     *
     * Cada entrada define:
     *  - svg:   marcado SVG inline (viewBox 24x24, stroke/fill coherente).
     *  - color: color base sugerido si la serie no define el suyo.
     *  - match: patrones (lowercase, sin acentos) para matchear contra el
     *           label/series de cada dato. El matcher usa strpos, así que
     *           los patrones deben ser fragmentos lo suficientemente
     *           específicos para no colisionar.
     *
     * @return array<string,array{svg:string,color:string,match:string[]}>
     */
    private function get_icon_catalog(): array {
        return [
            // Salud / IDSN.
            'health' => [
                'color' => '#dc2626',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3h4v5h5v4h-5v5h-4v-5H5V8h5V3z"/></svg>',
                'match' => [ 'idsn', 'salud', 'instituto departamental' ],
            ],
            // Agua / PDA.
            'water' => [
                'color' => '#0ea5e9',
                'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5c-4.5 6.5-7 9.5-7 13a7 7 0 0 0 14 0c0-3.5-2.5-6.5-7-13z"/></svg>',
                'match' => [ 'pda', 'agua', 'plan departamental' ],
            ],
            // Carretera / Infraestructura.
            'road' => [
                'color' => '#f59e0b',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21 L8 3 M20 21 L16 3 M12 3 v3 m0 4 v3 m0 4 v4"/></svg>',
                'match' => [ 'infra', 'obra', 'vias', 'via ' ],
            ],
            // Monedas / Regalías / SGR.
            'coins' => [
                'color' => '#1e40af',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6 v6 c0 1.7 3.6 3 8 3 s8-1.3 8-3 V6"/><path d="M4 12 v6 c0 1.7 3.6 3 8 3 s8-1.3 8-3 v-6"/></svg>',
                'match' => [ 'regalia', 'regalía', 'sgr' ],
            ],
            // Edificio / Municipio.
            'building' => [
                'color' => '#6366f1',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M4 21V7l8-4 8 4v14"/><path d="M9 21V12h6v9"/><path d="M4 21h16"/></svg>',
                'match' => [ 'municipio' ],
            ],
            // Estrella / Departamento.
            'star' => [
                'color' => '#8b5cf6',
                'svg'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 L14.7 8.6 L22 9.3 L16.4 14.1 L18.2 21.3 L12 17.6 L5.8 21.3 L7.6 14.1 L2 9.3 L9.3 8.6 Z"/></svg>',
                'match' => [ 'departamento', 'gobernacion', 'gobernación' ],
            ],
            // Maletín / Otro / Entidad.
            'briefcase' => [
                'color' => '#64748b',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><rect x="2" y="7" width="20" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M2 13h20"/></svg>',
                'match' => [ 'otro', 'especial', 'entidad' ],
            ],
            // Calendario / Vigencia.
            'calendar' => [
                'color' => '#0891b2',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
                'match' => [ '2023', '2024', '2025', '2026', '2027', 'vigencia', 'idsn*', 'infra*' ],
            ],
            // Meta / Objetivo.
            'target' => [
                'color' => '#be185d',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>',
                'match' => [ 'meta', 'objetivo' ],
            ],
            // Documento / Contrato.
            'document' => [
                'color' => '#0d9488',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="14 3 14 9 20 9"/><path d="M8 13h8M8 17h5"/></svg>',
                'match' => [ 'contrato' ],
            ],
            // Alerta / Riesgo alto.
            'alert' => [
                'color' => '#ef4444',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
                'match' => [ 'alto', 'riesgo alto' ],
            ],
            // Advertencia / Riesgo medio.
            'warning' => [
                'color' => '#f59e0b',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>',
                'match' => [ 'medio', 'riesgo medio' ],
            ],
            // Check / Riesgo bajo.
            'check' => [
                'color' => '#059669',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M7 12l4 4 6-8"/></svg>',
                'match' => [ 'bajo', 'riesgo bajo' ],
            ],
            // Ubicación / Geomap / Municipios.
            'map-pin' => [
                'color' => '#be185d',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
                'match' => [ 'mapa', 'geomap', 'mun.', 'mpio' ],
            ],
            // Default / desconocido.
            'default' => [
                'color' => '#94a3b8',
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.8 1c0 2-3 2.5-3 4.5"/><path d="M12 18h.01"/></svg>',
                'match' => [],
            ],
        ];
    }

    /**
     * Resolver un icono para una clave (label / series) arbitraria.
     *
     * @return array{key:string,svg:string,color:string}
     */
    private function resolve_icon_for( string $raw, string $fallback_color = '' ): array {
        if ( class_exists( 'SGR_Suite_Municipios_Normalizer' ) ) {
            $normalized = SGR_Suite_Municipios_Normalizer::normalize_key( $raw );
        } else {
            $normalized = strtoupper( $raw );
        }
        $needle = strtolower( $normalized );

        $catalog = $this->get_icon_catalog();
        foreach ( $catalog as $key => $entry ) {
            if ( 'default' === $key ) {
                continue;
            }
            foreach ( $entry['match'] as $pattern ) {
                if ( '' === $pattern ) {
                    continue;
                }
                if ( false !== strpos( $needle, $pattern ) ) {
                    return [
                        'key'   => $key,
                        'svg'   => $entry['svg'],
                        'color' => $fallback_color ?: $entry['color'],
                    ];
                }
            }
        }

        $def = $catalog['default'];
        return [
            'key'   => 'default',
            'svg'   => $def['svg'],
            'color' => $fallback_color ?: $def['color'],
        ];
    }

    /**
     * Construir el set de iconos/legenda para un conjunto de datos.
     *
     * Recorre las filas para extraer las categorías únicas que
     * aparecen en la columna `series` (vistas con series) o `label`
     * (vistas simples), y asigna un icono + color a cada una.
     *
     * @param array<int,array<string,mixed>> $data
     * @param array<int,string>              $palette
     * @return array<int,array{key:string,label:string,svg:string,color:string}>
     */
    public function build_legend_icons( array $data, array $palette = [] ): array {
        if ( empty( $data ) ) {
            return [];
        }

        $first     = $data[0];
        $has_series = isset( $first['series'] );
        $field      = $has_series ? 'series' : 'label';

        $seen = [];
        $ordered = [];
        foreach ( $data as $row ) {
            $val = isset( $row[ $field ] ) ? (string) $row[ $field ] : '';
            if ( '' === $val || isset( $seen[ $val ] ) ) {
                continue;
            }
            $seen[ $val ]   = true;
            $ordered[]      = $val;
        }

        if ( empty( $palette ) ) {
            $palette = $this->get_default_colors();
        }

        $legend = [];
        foreach ( $ordered as $i => $label ) {
            $color = $palette[ $i % count( $palette ) ];
            $icon  = $this->resolve_icon_for( $label, $color );
            $legend[] = [
                'key'   => $icon['key'],
                'label' => $label,
                'color' => $color,
                'svg'   => $icon['svg'],
            ];
        }

        return $legend;
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
            'geomap'       => [ 'label' => esc_html__( 'Mapa (Geomap Nariño)', 'sgr-suite' ) ],
        ];
    }

    /**
     * Matriz de compatibilidad vista ↔ tipo de gráfico.
     *
     * Se expone al admin en forma de data-attribute para que el editor de
     * gráficos filtre las opciones de "vista" cuando se elige un tipo de
     * gráfico específico (p.ej., geomap sólo admite las vistas geomap_*).
     *
     * @return array<string,string[]> Mapa view_key => lista de chart_type
     *                                 soportados. Si una vista no aparece,
     *                                 es compatible con todos los tipos.
     */
    /**
     * Metadatos completos de las vistas: compatibilidad con tipos de
     * gráfico + categoría funcional. Única fuente de verdad de v2.5.0.
     *
     * Cada entrada declara:
     *  - charts:   lista ordenada de chart_type compatibles. El primer
     *              elemento se considera el tipo "recomendado" para la
     *              vista y se usa como fallback automático.
     *  - category: clave de agrupación para el selector del admin
     *              (totales, rankings, distribucion, avance, series,
     *              temporal, geografico).
     *
     * @return array<string,array{charts:string[],category:string}>
     */
    public function get_view_metadata(): array {
        return [
            // =============================================================
            // TOTALES Y AGREGADOS
            // =============================================================
            'valor_por_dependencia'        => [ 'category' => 'totales',      'charts' => [ 'bar', 'barH', 'pie', 'donut', 'treemap', 'pack' ] ],
            'valor_por_entidad'            => [ 'category' => 'totales',      'charts' => [ 'bar', 'barH', 'pie', 'donut', 'treemap', 'pack' ] ],
            'valor_por_municipio'          => [ 'category' => 'totales',      'charts' => [ 'bar', 'barH', 'treemap', 'pack' ] ],
            'poblacion_por_municipio'      => [ 'category' => 'totales',      'charts' => [ 'bar', 'barH', 'treemap', 'pack' ] ],
            'contratos_por_dependencia'    => [ 'category' => 'totales',      'charts' => [ 'bar', 'barH', 'pie', 'donut', 'treemap', 'pack' ] ],

            // =============================================================
            // DISTRIBUCIÓN / CATEGORÍAS
            // =============================================================
            'distribucion_riesgo_contratos' => [ 'category' => 'distribucion', 'charts' => [ 'pie', 'donut', 'bar', 'barH' ] ],

            // =============================================================
            // AVANCE FÍSICO
            // =============================================================
            'scatter_valor_avance'         => [ 'category' => 'avance',       'charts' => [ 'scatter' ] ],
            'avance_por_entidad'           => [ 'category' => 'avance',       'charts' => [ 'bar', 'barH' ] ],

            // =============================================================
            // CRUCES CON SERIES
            // =============================================================
            'valor_dependencia_x_entidad'     => [ 'category' => 'series', 'charts' => [ 'stacked_bar', 'grouped_bar', 'bar', 'barH', 'treemap' ] ],
            'proyectos_dependencia_x_entidad' => [ 'category' => 'series', 'charts' => [ 'stacked_bar', 'grouped_bar', 'bar', 'barH' ] ],
            'valor_municipio_x_dependencia'   => [ 'category' => 'series', 'charts' => [ 'stacked_bar', 'grouped_bar', 'bar', 'barH' ] ],
            'valor_entidad_x_dependencia'     => [ 'category' => 'series', 'charts' => [ 'stacked_bar', 'grouped_bar', 'bar', 'barH' ] ],
            'matrix_municipio_dependencia'    => [ 'category' => 'series', 'charts' => [ 'stacked_bar', 'grouped_bar', 'treemap' ] ],

            // =============================================================
            // TEMPORAL / VIGENCIAS
            // =============================================================
            'vigencia_valor'                   => [ 'category' => 'temporal', 'charts' => [ 'bar', 'line', 'area', 'barH', 'pie', 'donut', 'treemap', 'pack' ] ],
            'vigencia_dependencia_x'           => [ 'category' => 'temporal', 'charts' => [ 'stacked_bar', 'grouped_bar', 'area', 'line', 'bar' ] ],
            'proyectos_vigencia_x_dependencia' => [ 'category' => 'temporal', 'charts' => [ 'grouped_bar', 'stacked_bar', 'line', 'area', 'bar' ] ],

            // =============================================================
            // GEOGRÁFICO
            // =============================================================
            'geomap_valor_municipio'     => [ 'category' => 'geografico', 'charts' => [ 'geomap' ] ],
            'geomap_contratos_municipio' => [ 'category' => 'geografico', 'charts' => [ 'geomap' ] ],
        ];
    }

    /**
     * Categorías funcionales con su etiqueta visible. El orden importa:
     * así se mostrarán los optgroup en el selector del admin.
     *
     * @return array<string,string>
     */
    public function get_view_categories(): array {
        return [
            'totales'      => esc_html__( 'Totales y Agregados', 'sgr-suite' ),
            'distribucion' => esc_html__( 'Distribución / Categorías', 'sgr-suite' ),
            'avance'       => esc_html__( 'Avance Físico', 'sgr-suite' ),
            'series'       => esc_html__( 'Cruces con Series', 'sgr-suite' ),
            'temporal'     => esc_html__( 'Evolución Temporal', 'sgr-suite' ),
            'geografico'   => esc_html__( 'Geográfico (Geomap)', 'sgr-suite' ),
        ];
    }

    /**
     * Matriz vista → lista de chart_type compatibles (formato plano).
     *
     * Se alimenta de get_view_metadata() para mantener una única fuente
     * de verdad. Expuesta al JS del admin vía wp_localize_script para el
     * filtrado bidireccional.
     *
     * @return array<string,string[]>
     */
    public function get_view_chart_compatibility(): array {
        $out = [];
        foreach ( $this->get_view_metadata() as $view_key => $meta ) {
            $out[ $view_key ] = $meta['charts'] ?? [];
        }
        return $out;
    }

    /**
     * Devolver el primer chart_type compatible con la vista dada, o null
     * si la vista no existe en la matriz.
     */
    public function get_recommended_chart_for_view( string $view_key ): ?string {
        $meta = $this->get_view_metadata();
        if ( ! isset( $meta[ $view_key ] ) ) {
            return null;
        }
        $charts = $meta[ $view_key ]['charts'] ?? [];
        return ! empty( $charts ) ? $charts[0] : null;
    }

    /**
     * Devolver la URL del topojson de Nariño (servido desde el plugin).
     */
    public function get_topojson_url(): string {
        return SGR_SUITE_URL . 'data/topo/narino_municipios.topojson';
    }

    public function get_chart_config( int $chart_id ): array {
        $defaults = [
            'chart_type'       => 'bar',
            'data_view'        => 'valor_por_dependencia',
            'limit'            => 20,
            'order_dir'        => 'DESC',
            'chart_height'     => 400,
            'show_legend'      => true,
            'show_toolbar'     => true,
            'number_format'    => 'colombiano',
            'colors'           => $this->get_default_colors(),
            'legend_mode'      => 'auto',
            'x_labels_rotate'  => 0,
            'x_labels_size'    => 12,
            'x_labels_visible' => true,
            'x_title'          => '',
            'y_title'          => '',
        ];

        $config = get_post_meta( $chart_id, self::META_CONFIG, true );
        if ( ! is_array( $config ) ) {
            return $defaults;
        }
        // Asegurar que los defaults estén presentes para configs antiguas.
        return array_merge( $defaults, $config );
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
                'chart_type'       => $config['chart_type'],
                'chart_height'     => $config['chart_height'],
                'show_legend'      => $config['show_legend'],
                'show_toolbar'     => $config['show_toolbar'],
                'number_format'    => $config['number_format'],
                'colors'           => $config['colors'],
                'data_view'        => $config['data_view'],
                'legend_mode'      => $config['legend_mode'] ?? 'auto',
                'x_labels_rotate'  => (int) ( $config['x_labels_rotate'] ?? 0 ),
                'x_labels_size'    => (int) ( $config['x_labels_size'] ?? 12 ),
                'x_labels_visible' => ! empty( $config['x_labels_visible'] ?? true ),
                'x_title'          => (string) ( $config['x_title'] ?? '' ),
                'y_title'          => (string) ( $config['y_title'] ?? '' ),
                'legend_icons'     => $this->build_legend_icons( $data, $config['colors'] ?? [] ),
            ],
        ];

        set_transient( $cache_key, $response, self::CACHE_TTL );
        wp_send_json_success( $response );
    }

    /**
     * AJAX: Preview de datos y gráfico (admin).
     *
     * Retorna la misma forma de respuesta que ajax_get_chart_data (data +
     * config) para que el JS del admin pueda reutilizar el renderer
     * público window.SGRChart.render() y dibujar un gráfico real.
     */
    public function ajax_preview_chart_data(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $view_key  = sanitize_text_field( wp_unslash( $_POST['data_view'] ?? 'valor_por_dependencia' ) );
        $limit     = min( absint( wp_unslash( $_POST['limit'] ?? 20 ) ), 500 );
        $order_dir = sanitize_text_field( wp_unslash( $_POST['order_dir'] ?? 'DESC' ) );

        $views       = $this->database->get_chart_views();
        $view_keys   = array_keys( $views );
        $chart_types = array_keys( $this->get_chart_types() );

        if ( ! in_array( $view_key, $view_keys, true ) ) {
            wp_send_json_error( [ 'message' => 'Vista inválida.' ], 400 );
        }

        // Sanear la configuración completa enviada desde el formulario.
        $chart_type    = sanitize_text_field( wp_unslash( $_POST['chart_type'] ?? 'bar' ) );
        $chart_type    = in_array( $chart_type, $chart_types, true ) ? $chart_type : 'bar';
        $chart_height  = max( 200, min( absint( wp_unslash( $_POST['chart_height'] ?? 400 ) ), 1200 ) );
        $number_format = sanitize_text_field( wp_unslash( $_POST['number_format'] ?? 'colombiano' ) );
        $number_format = in_array( $number_format, [ 'colombiano', 'millones', 'internacional', 'sin_formato' ], true ) ? $number_format : 'colombiano';
        $colors        = $this->sanitize_colors( sanitize_text_field( wp_unslash( $_POST['colors'] ?? '' ) ) );
        $legend_mode   = sanitize_text_field( wp_unslash( $_POST['legend_mode'] ?? 'auto' ) );
        $legend_mode   = in_array( $legend_mode, [ 'auto', 'text', 'icons', 'hidden' ], true ) ? $legend_mode : 'auto';
        $x_rotate      = max( 0, min( absint( wp_unslash( $_POST['x_labels_rotate'] ?? 0 ) ), 90 ) );
        $x_size        = max( 8, min( absint( wp_unslash( $_POST['x_labels_size'] ?? 12 ) ), 24 ) );
        $x_visible     = ! empty( $_POST['x_labels_visible'] );
        $show_legend   = ! empty( $_POST['show_legend'] );
        $x_title       = sanitize_text_field( wp_unslash( $_POST['x_title'] ?? '' ) );
        $y_title       = sanitize_text_field( wp_unslash( $_POST['y_title'] ?? '' ) );

        $data = $this->database->execute_chart_view( $view_key, $limit, $order_dir );

        $config = [
            'chart_type'       => $chart_type,
            'chart_height'     => $chart_height,
            'show_legend'      => $show_legend,
            'show_toolbar'     => false,
            'number_format'    => $number_format,
            'colors'           => $colors,
            'data_view'        => $view_key,
            'legend_mode'      => $legend_mode,
            'x_labels_rotate'  => $x_rotate,
            'x_labels_size'    => $x_size,
            'x_labels_visible' => $x_visible,
            'x_title'          => $x_title,
            'y_title'          => $y_title,
            'legend_icons'     => $this->build_legend_icons( $data, $colors ),
        ];

        wp_send_json_success( [
            'data'   => $data,
            'config' => $config,
            'count'  => count( $data ),
        ] );
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
        wp_localize_script( 'sgr-suite-charts', 'sgrCharts', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'topojsonUrl' => $this->get_topojson_url(),
        ] );
    }

    public function enqueue_admin_chart_assets( string $hook ): void {
        global $post_type;
        if ( self::CPT_CHART !== $post_type ) {
            return;
        }

        wp_enqueue_style( 'sgr-suite-admin', SGR_SUITE_URL . 'assets/css/admin.css', [], SGR_SUITE_VERSION );
        // También necesitamos los estilos de frontend para los legends
        // y la estructura del wrapper del gráfico en la vista previa.
        wp_enqueue_style( 'sgr-suite-frontend', SGR_SUITE_URL . 'assets/css/frontend.css', [], SGR_SUITE_VERSION );

        // D3plus + renderer frontend para la vista previa en el admin.
        // El renderer expone window.SGRChart.render() que admin-charts.js
        // usará para dibujar el gráfico real sobre el formulario.
        wp_enqueue_script( 'sgr-d3plus', 'https://cdn.jsdelivr.net/npm/d3plus@2.0.2/build/d3plus.full.min.js', [], '2.0.2', true );
        wp_enqueue_script( 'sgr-suite-charts', SGR_SUITE_URL . 'assets/js/frontend-charts.js', [ 'sgr-d3plus' ], SGR_SUITE_VERSION, true );
        wp_localize_script( 'sgr-suite-charts', 'sgrCharts', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'topojsonUrl' => $this->get_topojson_url(),
        ] );

        wp_enqueue_script( 'sgr-suite-admin-charts', SGR_SUITE_URL . 'assets/js/admin-charts.js', [ 'jquery', 'sgr-suite-charts' ], SGR_SUITE_VERSION, true );
        wp_localize_script( 'sgr-suite-admin-charts', 'sgrChartsAdmin', [
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'sgr_suite_admin_nonce' ),
            'compatibility' => $this->get_view_chart_compatibility(),
            'categories'    => $this->get_view_categories(),
            'topojsonUrl'   => $this->get_topojson_url(),
            'i18n'          => [
                'preview_empty'       => esc_html__( 'No hay datos para mostrar.', 'sgr-suite' ),
                'data_preview_rows'   => esc_html__( 'Mostrando %1$d de %2$d registros', 'sgr-suite' ),
                'auto_switch_chart'   => esc_html__( 'Tipo de gráfico ajustado automáticamente a uno compatible con la vista.', 'sgr-suite' ),
                'auto_switch_view'    => esc_html__( 'Vista ajustada automáticamente a una compatible con el tipo de gráfico.', 'sgr-suite' ),
            ],
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
