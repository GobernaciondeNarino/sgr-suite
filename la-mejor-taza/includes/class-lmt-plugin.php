<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class LMT_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->bootstrap();
        }
        return self::$instance;
    }

    private function bootstrap() {
        load_plugin_textdomain( 'la-mejor-taza', false, dirname( LMT_BASENAME ) . '/languages' );

        LMT_CPT::init();
        LMT_REST::init();
        LMT_Shortcodes::init();
        LMT_Assets::init();

        if ( is_admin() ) {
            LMT_Admin::init();
        }
    }

    public static function activate() {
        require_once LMT_PATH . 'includes/class-lmt-db.php';
        require_once LMT_PATH . 'includes/class-lmt-cpt.php';
        LMT_DB::install();
        LMT_CPT::register();
        flush_rewrite_rules();

        if ( ! get_option( 'lmt_settings' ) ) {
            add_option( 'lmt_settings', [
                'festival_name'  => __( 'Festival del Café de Nariño 2026', 'la-mejor-taza' ),
                'festival_dates' => '14–20 abr',
                'festival_city'  => 'Pasto — San Juan de Pasto',
                'palette'        => 'mercado',
                'organizer'      => __( 'Comité del Café · Nariño', 'la-mejor-taza' ),
                'organizer_mail' => 'admin@lamejortaza.co',
                'vote_page'      => 0,
                'passport_page'  => 0,
                'dashboard_page' => 0,
            ] );
        }
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
