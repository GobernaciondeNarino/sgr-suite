<?php
/**
 * SGR Suite - Personalizador de Cards
 *
 * Permite configurar la apariencia de las cards del grid de proyectos:
 * colores, esquinas, sombras, imagen por defecto, tipografía, badges.
 *
 * @package SGR_Suite
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Card_Customizer {

    private const OPTION_KEY = 'sgr_suite_card_style';

    public function register_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_submenu(): void {
        add_submenu_page(
            'sgr-suite',
            esc_html__( 'Personalizar Cards', 'sgr-suite' ),
            esc_html__( 'Personalizar Cards', 'sgr-suite' ),
            'manage_options',
            'sgr-suite-card-style',
            [ $this, 'render_page' ]
        );
    }

    public function register_settings(): void {
        register_setting( 'sgr_card_style_group', self::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_settings' ],
            'default'           => $this->get_defaults(),
        ] );
    }

    /**
     * Valores por defecto.
     */
    public function get_defaults(): array {
        return [
            // Card
            'card_bg'              => '#ffffff',
            'card_border_color'    => '#e5e7eb',
            'card_border_radius'   => '0',
            'card_shadow'          => 'none',
            'card_hover_shadow'    => '0 10px 25px rgba(0,0,0,0.08)',
            'card_hover_border'    => '#348afb',
            'card_hover_translate' => '-5',
            // Image
            'image_height'         => '200',
            'image_border_bottom'  => '#348afb',
            'image_default_url'    => SGR_SUITE_DEFAULT_IMAGE,
            // Badge
            'badge_bg'             => '#348afb',
            'badge_text_color'     => '#ffffff',
            'badge_border_radius'  => '0',
            // Title
            'title_color'          => '#334155',
            'title_size'           => '18',
            'title_max_chars'      => '120',
            // BPIN
            'bpin_color'           => '#348afb',
            // Footer
            'footer_text_color'    => '#666666',
            'link_color'           => '#348afb',
            // Stats Bar
            'stat_number_color'    => '#334155',
            'stat_number_size'     => '36',
            // Container
            'container_bg'         => '#fffcf3',
            'container_padding'    => '20',
            'grid_gap'             => '25',
            'grid_min_width'       => '320',
            // Search
            'search_border_color'  => '#348afb',
            'search_font_size'     => '25',
            // Modal
            'modal_bg'             => '#fffcf3',
            'modal_border_color'   => '#348afb',
            'modal_max_width'      => '1000',
        ];
    }

    /**
     * Obtener configuración actual mezclada con defaults.
     */
    public function get_settings(): array {
        $saved = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        return wp_parse_args( $saved, $this->get_defaults() );
    }

    /**
     * Sanitizar settings.
     *
     * Los valores CSS (sombras y radios) se limitan a un conjunto restringido
     * de caracteres para impedir la inyección de reglas CSS adicionales
     * (por ejemplo `}body{display:none;`).
     */
    public function sanitize_settings( $input ): array {
        if ( ! is_array( $input ) ) {
            return $this->get_defaults();
        }

        $defaults  = $this->get_defaults();
        $sanitized = [];

        foreach ( $defaults as $key => $default ) {
            $val = $input[ $key ] ?? $default;

            if ( str_ends_with( $key, '_color' ) || str_ends_with( $key, '_bg' ) || $key === 'image_border_bottom' || $key === 'card_hover_border' ) {
                $sanitized[ $key ] = sanitize_hex_color( $val ) ?: $default;
            } elseif ( str_ends_with( $key, '_url' ) ) {
                $sanitized[ $key ] = esc_url_raw( $val ) ?: $default;
            } elseif ( in_array( $key, [ 'card_shadow', 'card_hover_shadow' ], true ) ) {
                $sanitized[ $key ] = $this->sanitize_css_shadow( $val, $default );
            } elseif ( $key === 'card_border_radius' || $key === 'badge_border_radius' ) {
                $sanitized[ $key ] = $this->sanitize_css_length( $val, $default );
            } else {
                $sanitized[ $key ] = is_numeric( $val ) ? $val : $default;
            }
        }

        return $sanitized;
    }

    /**
     * Sanear un valor de box-shadow limitando a caracteres seguros.
     *
     * Se permite: dígitos, signos, unidades (px/em/rem/%), espacios, puntos,
     * coma, paréntesis (rgba()), # (hex) y la palabra clave 'none'/'inset'.
     * Bloquea `{`, `}`, `;`, `:` y comillas que permitirían romper la regla.
     */
    private function sanitize_css_shadow( $val, string $default ): string {
        $val = (string) $val;
        if ( '' === trim( $val ) ) {
            return $default;
        }
        // Permite letras para 'none', 'inset', 'rgba', etc.
        if ( ! preg_match( '/^[\w\s\.\,\-\+\#\(\)\%]+$/u', $val ) ) {
            return $default;
        }
        // Blindaje adicional contra separadores de reglas CSS.
        if ( preg_match( '/[{};:@\\\\]/', $val ) ) {
            return $default;
        }
        return sanitize_text_field( $val );
    }

    /**
     * Sanear un valor de longitud CSS (radio, etc).
     *
     * Acepta sólo números opcionalmente seguidos de una unidad válida.
     */
    private function sanitize_css_length( $val, string $default ): string {
        $val = trim( (string) $val );
        if ( '' === $val ) {
            return $default;
        }
        if ( preg_match( '/^-?\d+(?:\.\d+)?(px|em|rem|%)?$/', $val ) ) {
            return $val;
        }
        return $default;
    }

    /**
     * Generar CSS custom properties basadas en la configuración.
     */
    public function get_custom_css(): string {
        $s = $this->get_settings();

        return ":root {
            --sgr-card-bg: {$s['card_bg']};
            --sgr-card-border: {$s['card_border_color']};
            --sgr-card-radius: {$s['card_border_radius']}px;
            --sgr-card-shadow: {$s['card_shadow']};
            --sgr-card-hover-shadow: {$s['card_hover_shadow']};
            --sgr-card-hover-border: {$s['card_hover_border']};
            --sgr-card-hover-translate: {$s['card_hover_translate']}px;
            --sgr-image-height: {$s['image_height']}px;
            --sgr-image-border-bottom: {$s['image_border_bottom']};
            --sgr-badge-bg: {$s['badge_bg']};
            --sgr-badge-text: {$s['badge_text_color']};
            --sgr-badge-radius: {$s['badge_border_radius']}px;
            --sgr-title-color: {$s['title_color']};
            --sgr-title-size: {$s['title_size']}px;
            --sgr-bpin-color: {$s['bpin_color']};
            --sgr-footer-color: {$s['footer_text_color']};
            --sgr-link-color: {$s['link_color']};
            --sgr-stat-number-color: {$s['stat_number_color']};
            --sgr-stat-number-size: {$s['stat_number_size']}px;
            --sgr-container-bg: {$s['container_bg']};
            --sgr-container-padding: {$s['container_padding']}px;
            --sgr-grid-gap: {$s['grid_gap']}px;
            --sgr-grid-min-width: {$s['grid_min_width']}px;
            --sgr-search-border: {$s['search_border_color']};
            --sgr-search-font-size: {$s['search_font_size']}px;
            --sgr-modal-bg: {$s['modal_bg']};
            --sgr-modal-border: {$s['modal_border_color']};
            --sgr-modal-max-width: {$s['modal_max_width']}px;
        }";
    }

    /**
     * Renderizar página de personalización.
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sin permisos.', 'sgr-suite' ) );
        }
        $settings = $this->get_settings();
        include SGR_SUITE_PATH . 'templates/admin/card-style-page.php';
    }
}
