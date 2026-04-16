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

        // v2.3.1: Fix del renderizado del geomap.
        //  - Topojson normalizado: feature.id = DIVIPOLA (antes venía
        //    como nombre ASCII "SANDONA"), lo que rompía el join con
        //    los datos.
        //  - Eliminados topojsonFilter/Key/Id-función problemáticos
        //    (el short-circuit `d.id || d.properties.divipola` filtraba
        //    todos los features). Se usa la configuración por defecto de
        //    d3plus-geomap con topojsonId='id'.
        //  - Construcción defensiva con detección de método
        //    (fitFilter/tiles/ocean) para sobrevivir variantes del bundle.
        //  - Cast explícito de data[i].id a string en el renderer.
        if ( version_compare( $from_version, '2.3.1', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.3.1: fix del join topojson↔data en el geomap.' );
        }

        // v2.4.0: Mejoras visuales (ejes, leyenda con iconos, preview admin).
        //  - Nuevos campos en config: legend_mode (auto|icons|hidden),
        //    x_labels_rotate, x_labels_size, x_labels_visible.
        //  - Catálogo de iconos SVG + matcher para series comunes del SGR
        //    (IDSN→salud, PDA→agua, Infraestructura→carretera,
        //     Regalías→monedas, Municipio→edificio, Departamento→estrella,
        //     vigencia→calendario, riesgo alto/medio/bajo, meta, contrato).
        //  - frontend-charts.js expone window.SGRChart.render() para que
        //    el admin pueda dibujar un chart real en la vista previa.
        //  - admin-charts.js refresca la vista previa en tiempo real
        //    (debounced) al cambiar cualquier parámetro.
        //  - La meta-box "Vista Previa" se mueve a la columna principal
        //    para tener ancho suficiente.
        if ( version_compare( $from_version, '2.4.0', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.4.0: ejes configurables, leyenda con iconos, vista previa en admin.' );
        }

        // v2.5.0: Compatibilidad vista↔gráfico exhaustiva + data widget.
        //  - get_view_metadata() declara categoría + lista de chart_type
        //    compatibles para las 31 vistas (única fuente de verdad).
        //  - Filtrado bidireccional en el editor del admin: al cambiar
        //    la vista se ajusta el tipo automáticamente y viceversa.
        //  - save_chart_config() fuerza la combinación al primer tipo
        //    compatible si se intenta guardar algo inconsistente.
        //  - Nueva meta-box lateral "Datos de la Vista" con tabla en
        //    vivo de los primeros 10 registros devueltos por la vista.
        //  - Selector de vistas reorganizado por categoría funcional
        //    (Totales, Rankings, Distribución, Avance, Series, Temporal,
        //     Geográfico) en lugar de "simples vs. con series".
        //  - Configs guardadas con combinaciones legacy incompatibles
        //    se auto-corrigen al cargar (fallback al primer tipo compat).
        if ( version_compare( $from_version, '2.5.0', '<' ) ) {
            $this->database->clear_chart_caches();
            // Normalizar configs antiguas que podrían tener combinaciones
            // vista↔tipo incompatibles según la nueva matriz exhaustiva.
            $this->normalize_legacy_chart_configs();
            $this->logger->info( 'Migración v2.5.0: matriz vista↔gráfico exhaustiva + data widget lateral.' );
        }

        // v2.5.1: Fix del render y separación de modos de leyenda.
        //  - applyXAxisConfig() pasaba `labels: boolean` y `ticks: undefined`
        //    a chart.xConfig(), pero d3plus v2 Axis espera ARRAYS y
        //    disparaba `.slice is not a function` dentro de chart.render().
        //    Esto rompía bar, stacked_bar, grouped_bar, barH, line, area
        //    y scatter para TODAS las vistas desde v2.4.0. Ahora sólo
        //    pasamos shapeConfig.labelConfig (fontSize/rotate) y usamos
        //    tickFormat=()=>'' para ocultar etiquetas cuando toca.
        //  - Nuevo legend_mode 'text' (colored dot + label, sin icono).
        //    El modo 'icons' ya no muestra texto junto al icono — sólo
        //    el cuadro con SVG y el label como tooltip nativo.
        //  - En el renderer se clona la data y se redondean value/x/y
        //    antes de entregarla a d3plus (evita edge-cases numéricos
        //    con valores COP del orden de 10^12). El widget lateral
        //    conserva los valores crudos al no mutar el array original.
        //  - Se fuerzan label/series a string para que d3plus nunca
        //    reciba tipos inesperados en los campos categóricos.
        if ( version_compare( $from_version, '2.5.1', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.5.1: fix crítico del render de charts (d3plus xConfig) + legend modes.' );
        }

        // v2.5.2: Auditoría de datos + tooltips ricos.
        //  - SQL vigencia: el regex anterior `^[0-9]{4}` matchaba los
        //    BPIN SGR "5200100107_0002" como vigencia "5200". Ahora sólo
        //    se consideran años reales `^(20|19)[0-9]{2}`; los BPIN sin
        //    año-prefijo caen al bucket `{dependencia}*`.
        //  - Normalización de entidad ejecutora a nivel SQL: los valores
        //    "Departamento de Nariño", "Municipio de X", "Fundación...",
        //    "Contratista..." se consolidan a Departamento / Municipio /
        //    Otro vía CASE (antes aparecían como categorías separadas).
        //  - valor_por_dependencia, valor_por_entidad, vigencia_valor y
        //    vigencia_dependencia_x añaden `valor_promedio` (AVG). Los
        //    cruces con series añaden `count` para tooltips más ricos.
        //  - Nuevo buildTooltipConfig() en el renderer: título adaptativo
        //    ("label → series"), cuerpo con valor + cantidad + promedio
        //    + valor total + participación %, con etiqueta contextual
        //    según la vista (Cantidad vs Valor, % sufijo para avance).
        //  - Geomap legend: el `label` override devolvía 0 porque le
        //    pasaba un shape-object a formatNumber. Reemplazado por
        //    `axisConfig.tickFormat` que recibe números reales.
        //  - line/area ahora aplican el tooltipCfg enriquecido (antes
        //    usaban el tooltip por defecto de d3plus).
        if ( version_compare( $from_version, '2.5.2', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.5.2: vigencia/entidad normalizadas, tooltips ricos, geomap legend fix.' );
        }

        // v2.5.3: Fix del eje X + títulos de ejes + tooltip geomap universal.
        //  - Rotación y visibilidad de etiquetas del eje X se aplican
        //    ahora vía DOM post-render (applyAxisDomOverrides). D3plus v2
        //    ignoraba silenciosamente shapeConfig.labelConfig.rotate y
        //    tickFormat:()=>"" no colapsaba el layout. Ahora buscamos los
        //    <text> del eje en el SVG renderizado y aplicamos display y
        //    transform directamente (dos pasadas, 120ms + 600ms, para
        //    cubrir la animación de entrada).
        //  - Nuevos campos x_title / y_title para personalizar el título
        //    de cada eje. Se envían como title + titleConfig a d3plus
        //    (que sí los honora correctamente).
        //  - Geomap: pre-relleno de los 64 municipios de Nariño en
        //    geomap_aggregate(). Antes sólo había filas de data para los
        //    ~13 municipios con contratos, así que el tooltip sólo
        //    disparaba en esos; los otros 51 polígonos quedaban mudos
        //    al pasar el cursor. Ahora cada polígono tiene su fila con
        //    no_data=true y el tooltip muestra "Sin contratos registrados
        //    en este municipio" (el render sigue coloreando por value=0
        //    sobre la paleta secuencial).
        if ( version_compare( $from_version, '2.5.3', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.5.3: eje X DOM overrides, títulos de ejes, tooltip geomap universal.' );
        }

        // v2.5.4: Regresión de barras/líneas/scatter del v2.5.3 corregida.
        //  - applyXAxisConfig en v2.5.3 llamaba yConfig() SIEMPRE y con
        //    `titleConfig: {fontWeight: 600}`. Esto disparaba de nuevo
        //    el `.slice is not a function` en d3plus v2 internals para
        //    charts verticales (bar/line/area/scatter). Ahora se vuelve
        //    a la estructura de v2.5.1: yConfig() SÓLO para barH o
        //    cuando hay y_title; sin titleConfig (usar defaults de d3plus).
        //  - applyAxisDomOverrides se bypassa cuando no hay rotación ni
        //    ocultamiento (caso por defecto) para no tocar el DOM de
        //    gratis, y si falla queda atrapado en try/catch sin afectar
        //    el render que ya terminó.
        if ( version_compare( $from_version, '2.5.4', '<' ) ) {
            $this->database->clear_chart_caches();
            $this->logger->info( 'Migración v2.5.4: fix regresión slice error en barras/líneas.' );
        }
    }

    /**
     * Normalizar configs de gráfico guardados con v<2.5.0 que contengan
     * combinaciones vista↔tipo que la nueva matriz considera inválidas.
     *
     * La auto-corrección también ocurre en cada save_chart_config(), así
     * que este paso sólo adelanta la limpieza al momento de migrar.
     */
    private function normalize_legacy_chart_configs(): void {
        if ( ! function_exists( 'get_posts' ) ) {
            return;
        }

        $visualizer = sgr_suite()->visualizer ?? null;
        if ( ! $visualizer ) {
            return;
        }

        $compat     = $visualizer->get_view_chart_compatibility();
        $chart_ids  = get_posts( [
            'post_type'   => 'sgr_chart',
            'numberposts' => -1,
            'post_status' => 'any',
            'fields'      => 'ids',
        ] );

        $fixed = 0;
        foreach ( $chart_ids as $cid ) {
            $cfg = get_post_meta( $cid, '_sgr_chart_config', true );
            if ( ! is_array( $cfg ) ) {
                continue;
            }
            $view = $cfg['data_view'] ?? '';
            $type = $cfg['chart_type'] ?? '';
            if ( ! isset( $compat[ $view ] ) ) {
                continue;
            }
            $allowed = $compat[ $view ];
            if ( empty( $allowed ) || in_array( $type, $allowed, true ) ) {
                continue;
            }
            $cfg['chart_type'] = $allowed[0];
            update_post_meta( $cid, '_sgr_chart_config', $cfg );
            delete_transient( 'sgr_chart_data_' . $cid );
            $fixed++;
        }

        if ( $fixed > 0 ) {
            $this->logger->info( "v2.5.0: {$fixed} gráfico(s) con combinación vista↔tipo incompatible fueron auto-corregidos." );
        }
    }
}
