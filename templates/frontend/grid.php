<?php
/**
 * SGR Suite - Template Frontend Grid
 *
 * Muestra la grilla de proyectos del SGR con filtros,
 * estadísticas y modal de detalle.
 *
 * @package SGR_Suite
 * @since   1.0.0
 * @var array $atts Shortcode attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin       = sgr_suite();
$card_settings = $plugin->card_customizer->get_settings();

// Obtener datos desde la BD local
$stats = $plugin->database->get_stats();

$query_args = [];
$limite = absint( $atts['limite'] ?? 0 );
if ( $limite > 0 ) {
    $query_args['limite'] = $limite;
}

$proyectos = $plugin->database->get_proyectos( $query_args );

$has_data       = ! empty( $proyectos );
$municipios     = $stats['municipios'] ?? [];
$dependencias   = $stats['dependencias'] ?? [];
$entidades      = $stats['entidades'] ?? [];
$totalProyectos = $stats['totalProyectos'] ?? 0;
$totalContratos = $stats['totalContratos'] ?? 0;
$totalValor     = $stats['totalValor'] ?? 0;
$totalMetas     = $stats['totalMetas'] ?? 0;
$totalMunicipios = $stats['totalMunicipios'] ?? 0;

// Preparar datos JSON para el modal (solo campos necesarios para el frontend)
$proyectos_json = [];
foreach ( $proyectos as $index => $p ) {
    $contratos_json = [];
    if ( ! empty( $p['contratos'] ) ) {
        foreach ( $p['contratos'] as $c ) {
            $contratos_json[] = [
                'numeroContrato'              => $c['numero_contrato'],
                'valorContrato'               => $c['valor_contrato'],
                'objetoContrato'              => $c['objeto_contrato'],
                'esOpsEjecContractual'        => $c['es_ops_ejec_contractual'],
                'procentajeAvanceFisico'      => $c['porcentaje_avance_fisico'],
                'descripcionEjecContractual'  => $c['descripcion_ejec_contractual'],
                'municipiosEjecContractual'   => array_map( function ( $m ) {
                    return [
                        'nombre'               => $m['nombre'],
                        'poblacion_beneficiada' => $m['poblacion_beneficiada'],
                    ];
                }, $c['municipios'] ?? [] ),
                'imagenesEjecContractual'     => $c['imagenes'] ?? [],
            ];
        }
    }

    $proyectos_json[] = [
        'nombreProyecto'           => $p['nombre_proyecto'],
        'numeroProyecto'           => $p['numero_proyecto'],
        'valorProyecto'            => $p['valor_proyecto'],
        'dependenciaProyecto'      => $p['dependencia_proyecto'],
        'entidadEjecutoraProyecto' => $p['entidad_ejecutora_proyecto'],
        'metasProyecto'            => $p['metas'] ?? [],
        'contratosProyecto'        => $contratos_json,
    ];
}
?>

<div class="regalias-grid-container">
    <?php if ( ! $has_data ) : ?>
        <div class="regalias-grid-error-box">
            <strong><?php esc_html_e( 'Sin datos disponibles', 'sgr-suite' ); ?></strong><br>
            <?php esc_html_e( 'No se encontraron proyectos. Realice una importación desde el panel de administración.', 'sgr-suite' ); ?>
        </div>
    <?php else : ?>
        <div class="regalias-grid-search-container">
            <input type="search" id="regalias-grid-search-general" class="regalias-grid-search-input"
                   placeholder="<?php esc_attr_e( 'Buscar proyectos...', 'sgr-suite' ); ?>">
        </div>

        <div class="regalias-text-intro">
            <p>
                <?php esc_html_e(
                    'Conoce de forma directa, clara y actualizada cómo se implementan los Proyectos de Regalías en Nariño. Reflejando el compromiso de la Gobernación con la transparencia, la rendición de cuentas y la participación ciudadana.',
                    'sgr-suite'
                ); ?>
            </p>
        </div>

        <div class="regalias-grid-stats-bar">
            <div class="regalias-grid-stat-item">
                <div class="regalias-grid-stat-number"><?php echo esc_html( number_format( $totalProyectos, 0, ',', '.' ) ); ?></div>
                <div class="regalias-grid-stat-label"><?php esc_html_e( 'Total Proyectos', 'sgr-suite' ); ?></div>
            </div>
            <div class="regalias-grid-stat-item">
                <div class="regalias-grid-stat-number"><?php echo esc_html( '$ ' . number_format( $totalValor, 2, ',', '.' ) ); ?></div>
                <div class="regalias-grid-stat-label"><?php esc_html_e( 'Valor Total', 'sgr-suite' ); ?></div>
            </div>
            <div class="regalias-grid-stat-item">
                <div class="regalias-grid-stat-number"><?php echo esc_html( number_format( $totalContratos, 0, ',', '.' ) ); ?></div>
                <div class="regalias-grid-stat-label"><?php esc_html_e( 'Total Contratos', 'sgr-suite' ); ?></div>
            </div>
            <div class="regalias-grid-stat-item">
                <div class="regalias-grid-stat-number"><?php echo esc_html( $totalMunicipios ); ?></div>
                <div class="regalias-grid-stat-label"><?php esc_html_e( 'Municipios', 'sgr-suite' ); ?></div>
            </div>
            <div class="regalias-grid-stat-item">
                <div class="regalias-grid-stat-number"><?php echo esc_html( number_format( $totalMetas, 0, ',', '.' ) ); ?></div>
                <div class="regalias-grid-stat-label"><?php esc_html_e( 'Metas Totales', 'sgr-suite' ); ?></div>
            </div>
        </div>

        <div class="regalias-grid-filters-row">
            <div class="regalias-grid-filter-item">
                <label for="regalias-grid-filter-municipio"><?php esc_html_e( 'Municipio:', 'sgr-suite' ); ?></label>
                <select id="regalias-grid-filter-municipio" class="regalias-grid-select">
                    <option value=""><?php esc_html_e( 'Todos', 'sgr-suite' ); ?></option>
                    <?php foreach ( $municipios as $mun ) : ?>
                        <option value="<?php echo esc_attr( $mun ); ?>"><?php echo esc_html( $mun ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="regalias-grid-filter-item">
                <label for="regalias-grid-filter-dependencia"><?php esc_html_e( 'Dependencia:', 'sgr-suite' ); ?></label>
                <select id="regalias-grid-filter-dependencia" class="regalias-grid-select">
                    <option value=""><?php esc_html_e( 'Todas', 'sgr-suite' ); ?></option>
                    <?php foreach ( $dependencias as $dep ) : ?>
                        <option value="<?php echo esc_attr( $dep ); ?>"><?php echo esc_html( $dep ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="regalias-grid-filter-item">
                <label for="regalias-grid-filter-entidad"><?php esc_html_e( 'Entidad Ejecutora:', 'sgr-suite' ); ?></label>
                <select id="regalias-grid-filter-entidad" class="regalias-grid-select">
                    <option value=""><?php esc_html_e( 'Todas', 'sgr-suite' ); ?></option>
                    <?php foreach ( $entidades as $ent ) : ?>
                        <option value="<?php echo esc_attr( $ent ); ?>"><?php echo esc_html( $ent ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="regalias-grid-proyectos" id="regalias-grid-proyectos">
            <?php foreach ( $proyectos as $index => $proyecto ) :
                $num_contratos   = (int) $proyecto['total_contratos'];
                $imagen_card     = $card_settings['image_default_url'] ?? SGR_SUITE_DEFAULT_IMAGE;
                $municipios_text = '';

                // Primera imagen de contratos
                if ( ! empty( $proyecto['contratos'] ) ) {
                    foreach ( $proyecto['contratos'] as $contrato ) {
                        if ( ! empty( $contrato['imagenes'] ) ) {
                            $primera = $contrato['imagenes'][0];
                            if ( filter_var( $primera, FILTER_VALIDATE_URL ) ) {
                                $imagen_card = $primera;
                            }
                            break;
                        }
                    }

                    // Municipios texto
                    $mun_names = [];
                    foreach ( $proyecto['contratos'] as $contrato ) {
                        if ( ! empty( $contrato['municipios'] ) ) {
                            foreach ( $contrato['municipios'] as $m ) {
                                $mun_names[] = $m['nombre'];
                            }
                        }
                    }
                    $municipios_text = implode( ', ', array_unique( $mun_names ) );
                }
            ?>
                <div class="regalias-grid-card"
                     data-index="<?php echo esc_attr( $index ); ?>"
                     data-municipios="<?php echo esc_attr( mb_strtolower( $municipios_text ) ); ?>"
                     data-dependencia="<?php echo esc_attr( mb_strtolower( $proyecto['dependencia_proyecto'] ) ); ?>"
                     data-entidad="<?php echo esc_attr( mb_strtolower( $proyecto['entidad_ejecutora_proyecto'] ) ); ?>"
                     onclick="sgrAbrirModal(<?php echo esc_attr( $index ); ?>)">

                    <div class="regalias-grid-card-image">
                        <img src="<?php echo esc_url( $imagen_card ); ?>"
                             alt="<?php echo esc_attr( $proyecto['nombre_proyecto'] ); ?>"
                             loading="lazy">
                    </div>

                    <div class="regalias-grid-card-content">
                        <div class="regalias-grid-card-badge">
                            <?php echo esc_html( $proyecto['dependencia_proyecto'] ?: __( 'Sin especificar', 'sgr-suite' ) ); ?>
                        </div>

                        <div class="regalias-grid-card-bpin">
                            BPIN: <?php echo esc_html( $proyecto['numero_proyecto'] ); ?>
                        </div>

                        <div class="regalias-grid-card-title">
                            <?php echo esc_html( mb_substr( $proyecto['nombre_proyecto'], 0, 120 ) ); ?>
                            <?php if ( mb_strlen( $proyecto['nombre_proyecto'] ) > 120 ) echo '...'; ?>
                        </div>

                        <div class="regalias-grid-card-footer">
                            <span class="regalias-grid-card-contracts">
                                <?php
                                printf(
                                    /* translators: %d: number of contracts */
                                    esc_html( _n( '%d Contrato', '%d Contratos', $num_contratos, 'sgr-suite' ) ),
                                    $num_contratos
                                );
                                ?>
                            </span>
                            <span class="regalias-grid-card-link"><?php esc_html_e( 'Ver más', 'sgr-suite' ); ?> &rarr;</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="regalias-grid-no-results-message" style="display:none;">
            <?php esc_html_e( 'No se encontraron proyectos con los filtros seleccionados.', 'sgr-suite' ); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="regalias-grid-modal" class="regalias-grid-modal">
    <div class="regalias-grid-modal-content">
        <span class="regalias-grid-modal-close" onclick="sgrCerrarModal()">&times;</span>
        <div id="regalias-grid-modal-body"></div>
    </div>
</div>

<?php if ( $has_data ) : ?>
<script>
var sgrProyectosData = <?php echo wp_json_encode( $proyectos_json, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
</script>
<?php endif; ?>
