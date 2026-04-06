<?php
/**
 * SGR Suite - Clase de Logging
 *
 * Sistema de registro de eventos con rotación de archivos,
 * niveles de severidad y protección de directorio.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Logger {

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    private const MAX_ARCHIVES  = 3;

    /** @var string */
    private string $log_dir;

    /** @var string */
    private string $log_file;

    public function __construct() {
        $this->log_dir  = SGR_SUITE_PATH . 'logs/';
        $this->log_file = $this->log_dir . 'sgr-suite.log';
    }

    /**
     * Crear directorio de logs con protección.
     */
    public function create_log_directory(): void {
        if ( ! is_dir( $this->log_dir ) ) {
            wp_mkdir_p( $this->log_dir );
        }

        $htaccess = $this->log_dir . '.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }

        $index = $this->log_dir . 'index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }
    }

    /**
     * Escribir entrada de log.
     */
    private function log( string $level, string $message ): void {
        $this->create_log_directory();
        $this->rotate_if_needed();

        $timestamp = current_time( 'Y-m-d H:i:s' );
        $entry     = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        file_put_contents( $this->log_file, $entry, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( "[SGR Suite] [{$level}] {$message}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    public function debug( string $message ): void {
        $this->log( 'DEBUG', $message );
    }

    public function info( string $message ): void {
        $this->log( 'INFO', $message );
    }

    public function warning( string $message ): void {
        $this->log( 'WARNING', $message );
    }

    public function error( string $message ): void {
        $this->log( 'ERROR', $message );
    }

    /**
     * Rotación de archivos de log.
     */
    private function rotate_if_needed(): void {
        if ( ! file_exists( $this->log_file ) ) {
            return;
        }

        if ( filesize( $this->log_file ) < self::MAX_FILE_SIZE ) {
            return;
        }

        // Rotar archivos
        for ( $i = self::MAX_ARCHIVES; $i >= 1; $i-- ) {
            $old = $this->log_file . '.' . $i;
            $new = $this->log_file . '.' . ( $i + 1 );
            if ( file_exists( $old ) ) {
                if ( $i === self::MAX_ARCHIVES ) {
                    wp_delete_file( $old );
                } else {
                    rename( $old, $new );
                }
            }
        }

        rename( $this->log_file, $this->log_file . '.1' );
    }

    /**
     * Leer contenido del log actual.
     *
     * @param int $lines Número de líneas a leer desde el final.
     * @return array
     */
    public function read_log( int $lines = 200 ): array {
        if ( ! file_exists( $this->log_file ) ) {
            return [];
        }

        $content = file_get_contents( $this->log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( empty( $content ) ) {
            return [];
        }

        $all_lines = explode( PHP_EOL, trim( $content ) );

        return array_slice( $all_lines, -$lines );
    }

    /**
     * Limpiar archivo de log.
     */
    public function clear_log(): void {
        if ( file_exists( $this->log_file ) ) {
            file_put_contents( $this->log_file, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }
    }

    /**
     * Obtener ruta del archivo de log.
     */
    public function get_log_path(): string {
        return $this->log_file;
    }
}
