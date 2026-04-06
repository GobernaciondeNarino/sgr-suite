<?php
/**
 * SGR Suite - Clase de Actualización
 *
 * Gestiona migraciones de base de datos y actualizaciones
 * de versión del plugin.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Updater {

    /** @var SGR_Suite_Database */
    private SGR_Suite_Database $database;

    /** @var SGR_Suite_Logger */
    private SGR_Suite_Logger $logger;

    public function __construct( SGR_Suite_Database $database, SGR_Suite_Logger $logger ) {
        $this->database = $database;
        $this->logger   = $logger;

        add_action( 'admin_init', [ $this, 'check_version' ] );
    }

    /**
     * Verificar si se necesita actualización.
     */
    public function check_version(): void {
        $stored_version = get_option( 'sgr_suite_version', '0.0.0' );

        if ( version_compare( $stored_version, SGR_SUITE_VERSION, '<' ) ) {
            $this->run_migrations( $stored_version );
            update_option( 'sgr_suite_version', SGR_SUITE_VERSION );

            $this->logger->info( "Plugin actualizado de v{$stored_version} a v" . SGR_SUITE_VERSION );

            do_action( 'sgr_suite_after_upgrade', $stored_version, SGR_SUITE_VERSION );
        }
    }

    /**
     * Ejecutar migraciones necesarias.
     */
    private function run_migrations( string $from_version ): void {
        // v1.0.0: Instalación inicial
        if ( version_compare( $from_version, '1.0.0', '<' ) ) {
            $this->database->create_tables();
            $this->logger->info( 'Migración v1.0.0: Tablas creadas.' );
        }

        // Futuras migraciones se agregan aquí:
        // if ( version_compare( $from_version, '1.1.0', '<' ) ) { ... }
    }
}
