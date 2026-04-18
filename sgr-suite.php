<?php
/**
 * Plugin Name: SGR Suite
 * Plugin URI:  https://github.com/GobernaciondeNarino/sgr-suite
 * Description: Importa, almacena, visualiza y filtra datos de proyectos del Sistema General de Regalías (SGR) de Nariño. Incluye gráficos D3Plus y personalización de cards.
 * Version:     2.5.8
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author:      Gobernación de Nariño
 * Author URI:  https://www.narino.gov.co
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sgr-suite
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SGR_SUITE_VERSION', '2.5.8' );
define( 'SGR_SUITE_FILE', __FILE__ );
define( 'SGR_SUITE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SGR_SUITE_URL', plugin_dir_url( __FILE__ ) );
define( 'SGR_SUITE_BASENAME', plugin_basename( __FILE__ ) );
define( 'SGR_SUITE_API_URL', 'https://gobiernoabierto.narino.gov.co/wp-api/sgr.php' );
define( 'SGR_SUITE_DEFAULT_IMAGE', 'https://gobiernoabierto.narino.gov.co/wp-content/uploads/2025/11/SGR.jpeg' );

/**
 * Clase principal del plugin SGR Suite.
 */
final class SGR_Suite {

    private static ?self $instance = null;

    public SGR_Suite_Database $database;
    public SGR_Suite_Importer $importer;
    public SGR_Suite_Rest_API $rest_api;
    public SGR_Suite_Logger $logger;
    public SGR_Suite_Updater $updater;
    public SGR_Suite_Visualizer $visualizer;
    public SGR_Suite_Card_Customizer $card_customizer;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->register_hooks();
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \LogicException( 'No se permite deserializar SGR_Suite.' );
    }

    private function load_dependencies(): void {
        require_once SGR_SUITE_PATH . 'includes/class-logger.php';
        require_once SGR_SUITE_PATH . 'includes/class-municipios-normalizer.php';
        require_once SGR_SUITE_PATH . 'includes/class-database.php';
        require_once SGR_SUITE_PATH . 'includes/class-importer.php';
        require_once SGR_SUITE_PATH . 'includes/class-rest-api.php';
        require_once SGR_SUITE_PATH . 'includes/class-updater.php';
        require_once SGR_SUITE_PATH . 'includes/class-visualizer.php';
        require_once SGR_SUITE_PATH . 'includes/class-card-customizer.php';
    }

    private function init_components(): void {
        $this->logger          = new SGR_Suite_Logger();
        $this->database        = new SGR_Suite_Database( $this->logger );
        $this->importer        = new SGR_Suite_Importer( $this->database, $this->logger );
        $this->rest_api        = new SGR_Suite_Rest_API( $this->database );
        $this->updater         = new SGR_Suite_Updater( $this->database, $this->logger );
        $this->visualizer      = new SGR_Suite_Visualizer( $this->database, $this->logger );
        $this->card_customizer = new SGR_Suite_Card_Customizer();
    }

    private function register_hooks(): void {
        register_activation_hook( SGR_SUITE_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( SGR_SUITE_FILE, [ $this, 'deactivate' ] );

        // Admin
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Frontend
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'wp_head', [ $this, 'inject_card_custom_css' ] );

        // Shortcodes
        add_shortcode( 'sgr_proyectos', [ $this, 'render_shortcode_proyectos' ] );
        add_shortcode( 'regalias_grid_visualizador', [ $this, 'render_shortcode_proyectos' ] );

        // AJAX
        add_action( 'wp_ajax_sgr_suite_start_import', [ $this->importer, 'ajax_start_import' ] );
        add_action( 'wp_ajax_sgr_suite_check_progress', [ $this->importer, 'ajax_check_progress' ] );
        add_action( 'wp_ajax_sgr_suite_cancel_import', [ $this->importer, 'ajax_cancel_import' ] );
        add_action( 'wp_ajax_sgr_suite_truncate_data', [ $this->database, 'ajax_truncate_data' ] );

        // REST API
        add_action( 'rest_api_init', [ $this->rest_api, 'register_routes' ] );

        // Cron
        add_action( 'sgr_suite_scheduled_import', [ $this->importer, 'run_scheduled_import' ] );
        add_action( 'sgr_suite_run_import_now', [ $this->importer, 'run_import' ] );
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );

        // Visualizador de gráficos
        $this->visualizer->register_hooks();
        add_action( 'admin_enqueue_scripts', [ $this->visualizer, 'enqueue_admin_chart_assets' ] );

        // Card Customizer
        $this->card_customizer->register_hooks();

        // Internacionalización
        add_action( 'init', [ $this, 'load_textdomain' ] );
    }

    public function activate(): void {
        $this->database->create_tables();
        $this->logger->create_log_directory();

        if ( ! wp_next_scheduled( 'sgr_suite_scheduled_import' ) ) {
            wp_schedule_event( time(), 'sgr_twice_daily', 'sgr_suite_scheduled_import' );
        }

        update_option( 'sgr_suite_version', SGR_SUITE_VERSION );
        update_option( 'sgr_suite_activated', current_time( 'mysql' ) );
        flush_rewrite_rules();

        $this->logger->info( 'Plugin SGR Suite v' . SGR_SUITE_VERSION . ' activado.' );
    }

    public function deactivate(): void {
        $timestamp = wp_next_scheduled( 'sgr_suite_scheduled_import' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'sgr_suite_scheduled_import' );
        }
        flush_rewrite_rules();
        $this->logger->info( 'Plugin SGR Suite desactivado.' );
    }

    public function add_cron_schedules( array $schedules ): array {
        $schedules['sgr_twice_daily'] = [
            'interval' => 43200,
            'display'  => esc_html__( 'Dos veces al día', 'sgr-suite' ),
        ];
        return $schedules;
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'sgr-suite', false, dirname( SGR_SUITE_BASENAME ) . '/languages/' );
    }

    public function register_admin_menu(): void {
        add_menu_page(
            esc_html__( 'SGR Suite', 'sgr-suite' ),
            esc_html__( 'SGR Suite', 'sgr-suite' ),
            'manage_options',
            'sgr-suite',
            [ $this, 'render_admin_dashboard' ],
            'dashicons-chart-area',
            30
        );

        $subpages = [
            [ 'sgr-suite', 'Dashboard', 'Dashboard', 'render_admin_dashboard' ],
            [ 'sgr-suite-import', 'Importar Datos', 'Importar Datos', 'render_admin_import' ],
            [ 'sgr-suite-records', 'Proyectos', 'Proyectos', 'render_admin_records' ],
            [ 'sgr-suite-logs', 'Registros', 'Registros', 'render_admin_logs' ],
        ];

        foreach ( $subpages as $sp ) {
            add_submenu_page( 'sgr-suite', esc_html__( $sp[1], 'sgr-suite' ), esc_html__( $sp[2], 'sgr-suite' ), 'manage_options', $sp[0], [ $this, $sp[3] ] );
        }
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( ! str_contains( $hook, 'sgr-suite' ) ) {
            return;
        }

        wp_enqueue_style( 'sgr-suite-admin', SGR_SUITE_URL . 'assets/css/admin.css', [], SGR_SUITE_VERSION );
        wp_enqueue_script( 'sgr-suite-admin-import', SGR_SUITE_URL . 'assets/js/admin-import.js', [ 'jquery' ], SGR_SUITE_VERSION, true );

        wp_localize_script( 'sgr-suite-admin-import', 'sgrSuiteAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sgr_suite_admin_nonce' ),
            'i18n'    => [
                'importando'       => esc_html__( 'Importando...', 'sgr-suite' ),
                'completado'       => esc_html__( 'Importación completada', 'sgr-suite' ),
                'error'            => esc_html__( 'Error en la importación', 'sgr-suite' ),
                'cancelado'        => esc_html__( 'Importación cancelada', 'sgr-suite' ),
                'confirmarLimpiar' => esc_html__( '¿Está seguro de eliminar TODOS los datos? Esta acción no se puede deshacer.', 'sgr-suite' ),
            ],
        ] );
    }

    public function enqueue_frontend_assets(): void {
        if ( ! is_singular() ) {
            return;
        }

        global $post;
        if ( ! $post || ( ! has_shortcode( $post->post_content, 'sgr_proyectos' ) &&
                          ! has_shortcode( $post->post_content, 'regalias_grid_visualizador' ) ) ) {
            return;
        }

        wp_enqueue_style( 'sgr-suite-frontend', SGR_SUITE_URL . 'assets/css/frontend.css', [], SGR_SUITE_VERSION );
        wp_enqueue_script( 'sgr-suite-frontend', SGR_SUITE_URL . 'assets/js/frontend.js', [], SGR_SUITE_VERSION, true );
    }

    /**
     * Inyectar CSS custom properties del Card Customizer.
     */
    public function inject_card_custom_css(): void {
        $css = $this->card_customizer->get_custom_css();
        echo '<style id="sgr-suite-custom-vars">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS custom properties
    }

    public function render_shortcode_proyectos( $atts ): string {
        $atts = shortcode_atts( [ 'limite' => 0 ], $atts, 'sgr_proyectos' );
        ob_start();
        include SGR_SUITE_PATH . 'templates/frontend/grid.php';
        return ob_get_clean();
    }

    public function render_admin_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sin permisos.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/dashboard-page.php';
    }

    public function render_admin_import(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sin permisos.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/import-page.php';
    }

    public function render_admin_records(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sin permisos.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/records-page.php';
    }

    public function render_admin_logs(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sin permisos.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/logs-page.php';
    }
}

function sgr_suite(): SGR_Suite {
    return SGR_Suite::instance();
}

sgr_suite();
