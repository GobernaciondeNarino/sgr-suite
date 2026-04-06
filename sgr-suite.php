<?php
/**
 * Plugin Name: SGR Suite
 * Plugin URI:  https://github.com/GobernaciondeNarino/sgr-suite
 * Description: Importa, almacena, visualiza y filtra datos de proyectos del Sistema General de Regalías (SGR) de Nariño.
 * Version:     1.0.0
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

// Constantes del plugin
define( 'SGR_SUITE_VERSION', '1.0.0' );
define( 'SGR_SUITE_FILE', __FILE__ );
define( 'SGR_SUITE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SGR_SUITE_URL', plugin_dir_url( __FILE__ ) );
define( 'SGR_SUITE_BASENAME', plugin_basename( __FILE__ ) );
define( 'SGR_SUITE_API_URL', 'https://gobiernoabierto.narino.gov.co/wp-api/api_proyectos.php' );
define( 'SGR_SUITE_DEFAULT_IMAGE', 'https://gobiernoabierto.narino.gov.co/wp-content/uploads/2025/11/SGR.jpeg' );

/**
 * Clase principal del plugin SGR Suite.
 */
final class SGR_Suite {

    /** @var self|null */
    private static ?self $instance = null;

    /** @var SGR_Suite_Database */
    public SGR_Suite_Database $database;

    /** @var SGR_Suite_Importer */
    public SGR_Suite_Importer $importer;

    /** @var SGR_Suite_Rest_API */
    public SGR_Suite_Rest_API $rest_api;

    /** @var SGR_Suite_Logger */
    public SGR_Suite_Logger $logger;

    /** @var SGR_Suite_Updater */
    public SGR_Suite_Updater $updater;

    /**
     * Obtener instancia singleton.
     */
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

    /**
     * Cargar archivos de dependencia.
     */
    private function load_dependencies(): void {
        require_once SGR_SUITE_PATH . 'includes/class-logger.php';
        require_once SGR_SUITE_PATH . 'includes/class-database.php';
        require_once SGR_SUITE_PATH . 'includes/class-importer.php';
        require_once SGR_SUITE_PATH . 'includes/class-rest-api.php';
        require_once SGR_SUITE_PATH . 'includes/class-updater.php';
    }

    /**
     * Inicializar componentes.
     */
    private function init_components(): void {
        $this->logger   = new SGR_Suite_Logger();
        $this->database = new SGR_Suite_Database( $this->logger );
        $this->importer = new SGR_Suite_Importer( $this->database, $this->logger );
        $this->rest_api = new SGR_Suite_Rest_API( $this->database );
        $this->updater  = new SGR_Suite_Updater( $this->database, $this->logger );
    }

    /**
     * Registrar hooks de WordPress.
     */
    private function register_hooks(): void {
        // Activación y desactivación
        register_activation_hook( SGR_SUITE_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( SGR_SUITE_FILE, [ $this, 'deactivate' ] );

        // Admin
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Frontend
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

        // Shortcodes
        add_shortcode( 'sgr_proyectos', [ $this, 'render_shortcode_proyectos' ] );
        add_shortcode( 'regalias_grid_visualizador', [ $this, 'render_shortcode_proyectos' ] );

        // AJAX (admin)
        add_action( 'wp_ajax_sgr_suite_start_import', [ $this->importer, 'ajax_start_import' ] );
        add_action( 'wp_ajax_sgr_suite_check_progress', [ $this->importer, 'ajax_check_progress' ] );
        add_action( 'wp_ajax_sgr_suite_cancel_import', [ $this->importer, 'ajax_cancel_import' ] );
        add_action( 'wp_ajax_sgr_suite_truncate_data', [ $this->database, 'ajax_truncate_data' ] );

        // REST API
        add_action( 'rest_api_init', [ $this->rest_api, 'register_routes' ] );

        // Cron
        add_action( 'sgr_suite_scheduled_import', [ $this->importer, 'run_scheduled_import' ] );
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );

        // Internacionalización
        add_action( 'init', [ $this, 'load_textdomain' ] );
    }

    /**
     * Activación del plugin.
     */
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

    /**
     * Desactivación del plugin.
     */
    public function deactivate(): void {
        $timestamp = wp_next_scheduled( 'sgr_suite_scheduled_import' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'sgr_suite_scheduled_import' );
        }
        flush_rewrite_rules();
        $this->logger->info( 'Plugin SGR Suite desactivado.' );
    }

    /**
     * Agregar intervalos de cron personalizados.
     */
    public function add_cron_schedules( array $schedules ): array {
        $schedules['sgr_twice_daily'] = [
            'interval' => 43200,
            'display'  => esc_html__( 'Dos veces al día', 'sgr-suite' ),
        ];
        return $schedules;
    }

    /**
     * Cargar archivos de traducción.
     */
    public function load_textdomain(): void {
        load_plugin_textdomain( 'sgr-suite', false, dirname( SGR_SUITE_BASENAME ) . '/languages/' );
    }

    /**
     * Registrar menú de administración.
     */
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

        add_submenu_page(
            'sgr-suite',
            esc_html__( 'Dashboard', 'sgr-suite' ),
            esc_html__( 'Dashboard', 'sgr-suite' ),
            'manage_options',
            'sgr-suite',
            [ $this, 'render_admin_dashboard' ]
        );

        add_submenu_page(
            'sgr-suite',
            esc_html__( 'Importar Datos', 'sgr-suite' ),
            esc_html__( 'Importar Datos', 'sgr-suite' ),
            'manage_options',
            'sgr-suite-import',
            [ $this, 'render_admin_import' ]
        );

        add_submenu_page(
            'sgr-suite',
            esc_html__( 'Proyectos', 'sgr-suite' ),
            esc_html__( 'Proyectos', 'sgr-suite' ),
            'manage_options',
            'sgr-suite-records',
            [ $this, 'render_admin_records' ]
        );

        add_submenu_page(
            'sgr-suite',
            esc_html__( 'Registros', 'sgr-suite' ),
            esc_html__( 'Registros', 'sgr-suite' ),
            'manage_options',
            'sgr-suite-logs',
            [ $this, 'render_admin_logs' ]
        );
    }

    /**
     * Assets de administración.
     */
    public function enqueue_admin_assets( string $hook ): void {
        if ( ! str_contains( $hook, 'sgr-suite' ) ) {
            return;
        }

        wp_enqueue_style(
            'sgr-suite-admin',
            SGR_SUITE_URL . 'assets/css/admin.css',
            [],
            SGR_SUITE_VERSION
        );

        wp_enqueue_script(
            'sgr-suite-admin-import',
            SGR_SUITE_URL . 'assets/js/admin-import.js',
            [ 'jquery' ],
            SGR_SUITE_VERSION,
            true
        );

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

    /**
     * Assets del frontend.
     */
    public function enqueue_frontend_assets(): void {
        if ( ! is_singular() ) {
            return;
        }

        global $post;
        if ( ! $post || ( ! has_shortcode( $post->post_content, 'sgr_proyectos' ) &&
                          ! has_shortcode( $post->post_content, 'regalias_grid_visualizador' ) ) ) {
            return;
        }

        wp_enqueue_style(
            'sgr-suite-frontend',
            SGR_SUITE_URL . 'assets/css/frontend.css',
            [],
            SGR_SUITE_VERSION
        );

        wp_enqueue_script(
            'sgr-suite-frontend',
            SGR_SUITE_URL . 'assets/js/frontend.js',
            [],
            SGR_SUITE_VERSION,
            true
        );
    }

    /**
     * Renderizar shortcode de proyectos.
     */
    public function render_shortcode_proyectos( $atts ): string {
        $atts = shortcode_atts( [
            'limite' => 0,
        ], $atts, 'sgr_proyectos' );

        ob_start();
        include SGR_SUITE_PATH . 'templates/frontend/grid.php';
        return ob_get_clean();
    }

    /**
     * Templates de administración.
     */
    public function render_admin_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tiene permisos suficientes para acceder a esta página.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/dashboard-page.php';
    }

    public function render_admin_import(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tiene permisos suficientes para acceder a esta página.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/import-page.php';
    }

    public function render_admin_records(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tiene permisos suficientes para acceder a esta página.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/records-page.php';
    }

    public function render_admin_logs(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tiene permisos suficientes para acceder a esta página.', 'sgr-suite' ) );
        }
        include SGR_SUITE_PATH . 'templates/admin/logs-page.php';
    }
}

/**
 * Retorna la instancia principal del plugin.
 */
function sgr_suite(): SGR_Suite {
    return SGR_Suite::instance();
}

// Inicializar
sgr_suite();
