<?php
/**
 * SGR Suite - Clase Importador
 *
 * Gestiona la importación de datos desde la API del SGR,
 * con soporte para importación manual, programada y AJAX.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGR_Suite_Importer {

    private const TRANSIENT_PROGRESS = 'sgr_suite_import_progress';
    private const TRANSIENT_LOCK     = 'sgr_suite_import_lock';
    private const LOCK_TIMEOUT       = 600; // 10 minutos

    /** @var SGR_Suite_Database */
    private SGR_Suite_Database $database;

    /** @var SGR_Suite_Logger */
    private SGR_Suite_Logger $logger;

    public function __construct( SGR_Suite_Database $database, SGR_Suite_Logger $logger ) {
        $this->database = $database;
        $this->logger   = $logger;
    }

    /**
     * Consultar la API del SGR.
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function fetch_api_data(): array {
        if ( ! function_exists( 'wp_remote_get' ) ) {
            return [
                'success' => false,
                'error'   => 'La función wp_remote_get no está disponible.',
            ];
        }

        $response = wp_remote_get( SGR_SUITE_API_URL, [
            'timeout'   => 60,
            'headers'   => [
                'Accept'     => 'application/json',
                'User-Agent' => 'SGR-Suite/' . SGR_SUITE_VERSION . '; WordPress/' . get_bloginfo( 'version' ),
            ],
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'error'   => 'Error de conexión: ' . $response->get_error_message(),
            ];
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $http_code ) {
            return [
                'success' => false,
                'error'   => "Error HTTP {$http_code}: No se pudo obtener los datos del servidor.",
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return [
                'success' => false,
                'error'   => 'La respuesta del servidor está vacía.',
            ];
        }

        $data = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [
                'success' => false,
                'error'   => 'Error al decodificar JSON: ' . json_last_error_msg(),
            ];
        }

        // Validar estructura: soporta tanto {ok, proyectos} como {proyectos}
        if ( ! isset( $data['proyectos'] ) || ! is_array( $data['proyectos'] ) ) {
            return [
                'success' => false,
                'error'   => 'Estructura de datos inválida en la respuesta del API.',
            ];
        }

        // Si la API tiene campo 'ok' y es false, reportar error
        if ( isset( $data['ok'] ) && ! $data['ok'] ) {
            return [
                'success' => false,
                'error'   => 'La API reportó un error (ok=false).',
            ];
        }

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    /**
     * Ejecutar importación completa.
     *
     * @return array{success: bool, imported: int, errors: int, message: string}
     */
    public function run_import(): array {
        // Verificar lock de concurrencia
        if ( get_transient( self::TRANSIENT_LOCK ) ) {
            return [
                'success'  => false,
                'imported' => 0,
                'errors'   => 0,
                'message'  => 'Ya hay una importación en progreso.',
            ];
        }

        // Establecer lock
        set_transient( self::TRANSIENT_LOCK, true, self::LOCK_TIMEOUT );

        $this->logger->info( 'Iniciando importación de datos del SGR...' );

        $this->set_progress( [
            'status'   => 'running',
            'total'    => 0,
            'current'  => 0,
            'imported' => 0,
            'errors'   => 0,
            'message'  => 'Consultando API...',
        ] );

        // Obtener datos de la API
        $result = $this->fetch_api_data();

        if ( ! $result['success'] ) {
            $this->logger->error( 'Error al consultar API: ' . $result['error'] );
            $this->set_progress( [
                'status'  => 'error',
                'message' => $result['error'],
            ] );
            delete_transient( self::TRANSIENT_LOCK );
            return [
                'success'  => false,
                'imported' => 0,
                'errors'   => 0,
                'message'  => $result['error'],
            ];
        }

        $proyectos = $result['data']['proyectos'];
        $total     = count( $proyectos );
        $imported  = 0;
        $errors    = 0;

        $this->logger->info( "Se encontraron {$total} proyectos para importar." );

        $this->set_progress( [
            'status'   => 'running',
            'total'    => $total,
            'current'  => 0,
            'imported' => 0,
            'errors'   => 0,
            'message'  => "Procesando {$total} proyectos...",
        ] );

        foreach ( $proyectos as $index => $proyecto ) {
            // Verificar cancelación
            $progress = $this->get_progress();
            if ( isset( $progress['status'] ) && 'cancelled' === $progress['status'] ) {
                $this->logger->info( 'Importación cancelada por el usuario.' );
                delete_transient( self::TRANSIENT_LOCK );
                return [
                    'success'  => false,
                    'imported' => $imported,
                    'errors'   => $errors,
                    'message'  => 'Importación cancelada.',
                ];
            }

            $pid = $this->database->upsert_proyecto( $proyecto );

            if ( false !== $pid ) {
                $imported++;
            } else {
                $errors++;
                $bpin = $proyecto['numeroProyecto'] ?? 'desconocido';
                $this->logger->warning( "Error al importar proyecto BPIN: {$bpin}" );
            }

            // Actualizar progreso cada 5 proyectos para reducir carga de transients
            if ( ( $index + 1 ) % 5 === 0 || ( $index + 1 ) === $total ) {
                $this->set_progress( [
                    'status'   => 'running',
                    'total'    => $total,
                    'current'  => $index + 1,
                    'imported' => $imported,
                    'errors'   => $errors,
                    'message'  => "Procesado {$index}/{$total} proyectos...",
                ] );
            }

            // Pequeña pausa para no saturar la BD
            if ( ( $index + 1 ) % 50 === 0 ) {
                usleep( 100000 ); // 100ms
            }
        }

        // Completar
        $this->set_progress( [
            'status'   => 'complete',
            'total'    => $total,
            'current'  => $total,
            'imported' => $imported,
            'errors'   => $errors,
            'message'  => "Importación completada: {$imported} proyectos importados, {$errors} errores.",
        ] );

        delete_transient( self::TRANSIENT_LOCK );

        // Limpiar caches de gráficos tras importación
        $this->database->clear_chart_caches();

        $this->logger->info( "Importación completada: {$imported} proyectos importados, {$errors} errores." );

        return [
            'success'  => true,
            'imported' => $imported,
            'errors'   => $errors,
            'message'  => "Importación completada: {$imported} proyectos importados, {$errors} errores.",
        ];
    }

    /**
     * Importación programada por cron.
     */
    public function run_scheduled_import(): void {
        $this->logger->info( 'Ejecutando importación programada.' );
        $this->run_import();
    }

    /**
     * AJAX: Iniciar importación.
     */
    public function ajax_start_import(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        // Ejecutar importación en background usando wp_schedule_single_event.
        // La acción 'sgr_suite_run_import_now' se registra en sgr-suite.php
        // durante register_hooks(), garantizando que exista cuando WP-Cron
        // la dispare en la siguiente solicitud.
        $this->set_progress( [
            'status'  => 'running',
            'total'   => 0,
            'current' => 0,
            'message' => 'Iniciando importación...',
        ] );

        // Para entornos sin WP-Cron real, ejecutar directamente en esta petición.
        if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
            $result = $this->run_import();
            wp_send_json_success( $result );
            return;
        }

        // Encolar el evento asíncrono.
        if ( ! wp_next_scheduled( 'sgr_suite_run_import_now' ) ) {
            wp_schedule_single_event( time(), 'sgr_suite_run_import_now' );
        }

        // Disparar WP-Cron en segundo plano.
        if ( function_exists( 'spawn_cron' ) ) {
            spawn_cron();
        }

        wp_send_json_success( [ 'message' => 'Importación iniciada.' ] );
    }

    /**
     * AJAX: Verificar progreso.
     */
    public function ajax_check_progress(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $progress = $this->get_progress();

        if ( empty( $progress ) ) {
            wp_send_json_success( [
                'status'  => 'idle',
                'message' => 'No hay importación en progreso.',
            ] );
            return;
        }

        wp_send_json_success( $progress );
    }

    /**
     * AJAX: Cancelar importación.
     */
    public function ajax_cancel_import(): void {
        check_ajax_referer( 'sgr_suite_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Sin permisos.' ], 403 );
        }

        $this->set_progress( [
            'status'  => 'cancelled',
            'message' => 'Cancelando importación...',
        ] );

        delete_transient( self::TRANSIENT_LOCK );

        wp_send_json_success( [ 'message' => 'Importación cancelada.' ] );
    }

    /**
     * Establecer progreso de importación.
     */
    private function set_progress( array $data ): void {
        set_transient( self::TRANSIENT_PROGRESS, $data, self::LOCK_TIMEOUT );
    }

    /**
     * Obtener progreso de importación.
     */
    private function get_progress(): array {
        $progress = get_transient( self::TRANSIENT_PROGRESS );
        return is_array( $progress ) ? $progress : [];
    }
}
