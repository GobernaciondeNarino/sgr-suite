<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMT_Assets {
    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'public_assets' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
    }

    public static function public_assets() {
        wp_register_style( 'lmt-fonts', 'https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap', [], null );
        wp_register_style( 'lmt-tokens', LMT_URL . 'assets/css/tokens.css', [ 'lmt-fonts' ], LMT_VERSION );
        wp_register_style( 'lmt-public', LMT_URL . 'assets/css/public.css', [ 'lmt-tokens' ], LMT_VERSION );

        wp_register_script( 'lmt-qrcode', LMT_URL . 'assets/vendor/qrcode.min.js', [], '1.0.0', true );
        wp_register_script( 'lmt-public', LMT_URL . 'assets/js/public.js', [ 'lmt-qrcode' ], LMT_VERSION, true );

        $settings = get_option( 'lmt_settings', [] );
        wp_localize_script( 'lmt-public', 'LMT_DATA', [
            'rest'    => esc_url_raw( rest_url( LMT_REST::NS ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'home'    => home_url( '/' ),
            'palette' => isset( $settings['palette'] ) ? $settings['palette'] : 'mercado',
            'i18n'    => [
                'sending'       => __( 'Sellando…', 'la-mejor-taza' ),
                'thanks'        => __( '✓ Voto registrado', 'la-mejor-taza' ),
                'duplicate'     => __( 'Ya votaste por este stand.', 'la-mejor-taza' ),
                'invalid_email' => __( 'Correo inválido.', 'la-mejor-taza' ),
                'live'          => __( 'En vivo', 'la-mejor-taza' ),
            ],
        ] );
    }

    public static function admin_assets( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $is_lmt = $screen && ( $screen->post_type === LMT_CPT::POST_TYPE || strpos( (string) $hook, 'lmt-' ) !== false );
        if ( ! $is_lmt ) return;

        wp_enqueue_style( 'lmt-fonts', 'https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap', [], null );
        wp_enqueue_style( 'lmt-tokens', LMT_URL . 'assets/css/tokens.css', [ 'lmt-fonts' ], LMT_VERSION );
        wp_enqueue_style( 'lmt-admin', LMT_URL . 'assets/css/admin.css', [ 'lmt-tokens' ], LMT_VERSION );
        wp_enqueue_script( 'lmt-qrcode', LMT_URL . 'assets/vendor/qrcode.min.js', [], '1.0.0', true );
        wp_enqueue_script( 'lmt-admin', LMT_URL . 'assets/js/admin.js', [ 'lmt-qrcode' ], LMT_VERSION, true );
        wp_localize_script( 'lmt-admin', 'LMT_ADMIN', [
            'rest'  => esc_url_raw( rest_url( LMT_REST::NS ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }
}
