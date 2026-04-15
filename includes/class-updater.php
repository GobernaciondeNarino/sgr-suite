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

        // v2.0.0: Recrear tablas con FK robustas, limpiar caches
        if ( version_compare( $from_version, '2.0.0', '<' ) ) {
            $this->database->create_tables();
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.0.0: FK robustas, chart views, card customizer.' );
        }

        // v2.1.2: Revisión de módulos y endurecimiento de seguridad.
        //  - Se corrigen rutas REST (colisión /proyectos/csv ↔ /proyectos/{bpin}).
        //  - CSV export en streaming para evitar OOM.
        //  - TRUNCATE -> DELETE para compatibilidad con FKs.
        //  - Importación asíncrona arreglada (hook registrado en el bootstrap).
        //  - Sanitización reforzada del card customizer.
        //  - Corrección XSS en galería de imágenes del modal frontend.
        if ( version_compare( $from_version, '2.1.2', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.1.2: hardening de seguridad y correcciones de módulos.' );
        }

        // v2.2.0: Nuevas vistas de datos derivadas del roadmap sgr_views.md.
        //  - V-04 vigencia_valor, V-05 vigencia_dependencia_x, V-05b por proyectos.
        //  - V-07b avance físico promedio por dependencia.
        //  - V-08 scatter_valor_avance (+ V-08b distribucion_riesgo_contratos).
        //  - V-14 matrix_municipio_dependencia.
        //  - V-15 ranking_dependencias_vigencia.
        //  - V-19 avance_por_entidad (distribución por entidad).
        //  - Nuevo tipo de gráfico "scatter" (D3plus Plot).
        // Las vistas antiguas siguen funcionando; sólo se limpia la cache.
        if ( version_compare( $from_version, '2.2.0', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.2.0: nuevas vistas (V-04, V-05, V-08, V-14, V-15, V-19) y scatter chart.' );
        }

        // v2.3.0: Geomap de Nariño (V-12 y V-13).
        //  - Topojson + lookup de DIVIPOLA copiados desde tic-suite
        //    (data/topo/narino_municipios.topojson, .lookup.json).
        //  - Normalizador PHP que resuelve variantes ("Alban / San José",
        //    "Los Andes Sotomayor", "San Juan de Pasto", listas separadas
        //    por coma, etc.) a los 64 municipios canónicos.
        //  - Vistas geomap_valor_municipio y geomap_contratos_municipio
        //    con post-procesamiento en PHP (agregación por DIVIPOLA).
        //  - Nuevo tipo de gráfico "geomap" (D3plus v2 Geomap).
        //  - Filtro vista ↔ tipo de gráfico en el editor del admin.
        if ( version_compare( $from_version, '2.3.0', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.3.0: geomap de Nariño (topojson + vistas V-12/V-13).' );
        }
    }
}
