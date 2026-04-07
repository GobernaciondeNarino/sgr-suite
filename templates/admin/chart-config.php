<?php
/**
 * SGR Suite - Chart Configuration Meta Box
 *
 * Uses predefined data views with JOINs instead of raw table/column selection.
 *
 * @package SGR_Suite
 * @since   2.0.0
 * @var array   $config Chart configuration (from render_meta_box)
 * @var WP_Post $post   Current post
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_nonce_field( 'sgr_chart_config_save', 'sgr_chart_config_nonce' );

$visualizer  = sgr_suite()->visualizer;
$chart_types = $visualizer->get_chart_types();
$database    = sgr_suite()->database;
$views       = $database->get_chart_views();

$data_views = [
    'valor_por_dependencia'            => esc_html__( 'Valor por Dependencia', 'sgr-suite' ),
    'valor_por_entidad'                => esc_html__( 'Valor por Entidad Ejecutora', 'sgr-suite' ),
    'valor_por_municipio'              => esc_html__( 'Inversi&oacute;n por Municipio', 'sgr-suite' ),
    'poblacion_por_municipio'          => esc_html__( 'Poblaci&oacute;n Beneficiada por Municipio', 'sgr-suite' ),
    'contratos_por_dependencia'        => esc_html__( 'Contratos por Dependencia', 'sgr-suite' ),
    'avance_promedio_por_dependencia'  => esc_html__( 'Avance F&iacute;sico Promedio por Dependencia', 'sgr-suite' ),
    'metas_por_dependencia'            => esc_html__( 'Metas por Dependencia', 'sgr-suite' ),
    'top_proyectos_valor'              => esc_html__( 'Top Proyectos por Valor', 'sgr-suite' ),
    'municipios_por_proyecto'          => esc_html__( 'Municipios por Proyecto (Top)', 'sgr-suite' ),
];
?>

<div class="sgr-chart-config-wrap">

    <!-- Chart Type -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Tipo de Gr&aacute;fico', 'sgr-suite' ); ?></h3>
        <div class="sgr-chart-type-grid">
            <?php foreach ( $chart_types as $type_key => $type_info ) : ?>
                <label class="sgr-chart-type-option <?php echo ( ( $config['chart_type'] ?? 'bar' ) === $type_key ) ? 'selected' : ''; ?>">
                    <input type="radio" name="sgr_chart[chart_type]"
                           value="<?php echo esc_attr( $type_key ); ?>"
                           <?php checked( $config['chart_type'] ?? 'bar', $type_key ); ?>>
                    <span class="sgr-chart-type-icon" data-type="<?php echo esc_attr( $type_key ); ?>"></span>
                    <span class="sgr-chart-type-name"><?php echo esc_html( $type_info['label'] ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Data View -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Vista de Datos', 'sgr-suite' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="sgr-data-view"><?php esc_html_e( 'Vista predefinida', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[data_view]" id="sgr-data-view" class="regular-text">
                        <?php foreach ( $data_views as $view_key => $view_label ) : ?>
                            <option value="<?php echo esc_attr( $view_key ); ?>"
                                    <?php selected( $config['data_view'] ?? 'valor_por_dependencia', $view_key ); ?>>
                                <?php echo esc_html( $view_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Selecciona la consulta predefinida que alimentar&aacute; el gr&aacute;fico. Cada vista incluye JOINs entre tablas para an&aacute;lisis cruzado.', 'sgr-suite' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-limit"><?php esc_html_e( 'L&iacute;mite de registros', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="number" name="sgr_chart[limit]" id="sgr-limit"
                           value="<?php echo esc_attr( $config['limit'] ?? 20 ); ?>"
                           min="1" max="500" class="small-text">
                    <p class="description"><?php esc_html_e( 'Cantidad m&aacute;xima de resultados a mostrar (1-500).', 'sgr-suite' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-order-dir"><?php esc_html_e( 'Direcci&oacute;n de orden', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[order_dir]" id="sgr-order-dir">
                        <option value="DESC" <?php selected( $config['order_dir'] ?? 'DESC', 'DESC' ); ?>><?php esc_html_e( 'Descendente (mayor a menor)', 'sgr-suite' ); ?></option>
                        <option value="ASC" <?php selected( $config['order_dir'] ?? 'DESC', 'ASC' ); ?>><?php esc_html_e( 'Ascendente (menor a mayor)', 'sgr-suite' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
    </div>

    <!-- Appearance -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Apariencia', 'sgr-suite' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="sgr-chart-height"><?php esc_html_e( 'Altura (px)', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="number" name="sgr_chart[chart_height]" id="sgr-chart-height"
                           value="<?php echo esc_attr( $config['chart_height'] ?? 400 ); ?>"
                           min="200" max="1200" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="sgr-number-format"><?php esc_html_e( 'Formato Num&eacute;rico', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[number_format]" id="sgr-number-format" class="regular-text">
                        <option value="colombiano" <?php selected( $config['number_format'] ?? 'colombiano', 'colombiano' ); ?>><?php esc_html_e( 'Colombiano (1.234.567,89)', 'sgr-suite' ); ?></option>
                        <option value="millones" <?php selected( $config['number_format'] ?? 'colombiano', 'millones' ); ?>><?php esc_html_e( 'Millones (1.5M)', 'sgr-suite' ); ?></option>
                        <option value="internacional" <?php selected( $config['number_format'] ?? 'colombiano', 'internacional' ); ?>><?php esc_html_e( 'Internacional (1,234,567.89)', 'sgr-suite' ); ?></option>
                        <option value="sin_formato" <?php selected( $config['number_format'] ?? 'colombiano', 'sin_formato' ); ?>><?php esc_html_e( 'Sin Formato', 'sgr-suite' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-colors"><?php esc_html_e( 'Colores', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="text" name="sgr_chart[colors]" id="sgr-colors"
                           value="<?php echo esc_attr( implode( ', ', $config['colors'] ?? [] ) ); ?>"
                           class="large-text"
                           placeholder="#348afb, #1e40af, #059669, #d97706">
                    <p class="description"><?php esc_html_e( 'Colores hex separados por coma.', 'sgr-suite' ); ?></p>
                    <div id="sgr-color-preview" class="sgr-color-swatches">
                        <?php foreach ( $config['colors'] ?? [] as $color ) : ?>
                            <span class="sgr-color-swatch" style="background: <?php echo esc_attr( $color ); ?>;"></span>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Opciones', 'sgr-suite' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="sgr_chart[show_legend]" value="1"
                               <?php checked( $config['show_legend'] ?? true ); ?>>
                        <?php esc_html_e( 'Mostrar leyenda', 'sgr-suite' ); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="sgr_chart[show_toolbar]" value="1"
                               <?php checked( $config['show_toolbar'] ?? true ); ?>>
                        <?php esc_html_e( 'Mostrar barra de herramientas', 'sgr-suite' ); ?>
                    </label>
                </td>
            </tr>
        </table>
    </div>

</div>

<script>
(function() {
    'use strict';

    /* Chart type selection */
    document.querySelectorAll('.sgr-chart-type-option').forEach(function(option) {
        option.addEventListener('click', function() {
            document.querySelectorAll('.sgr-chart-type-option').forEach(function(o) { o.classList.remove('selected'); });
            option.classList.add('selected');
            var radio = option.querySelector('input[type="radio"]');
            if (radio) { radio.checked = true; }
        });
    });

    /* Color swatch preview */
    var colorsInput = document.getElementById('sgr-colors');
    var previewContainer = document.getElementById('sgr-color-preview');
    if (colorsInput && previewContainer) {
        colorsInput.addEventListener('input', function() {
            var colors = colorsInput.value.split(',').map(function(c) { return c.trim(); });
            previewContainer.innerHTML = '';
            colors.forEach(function(color) {
                if (/^#[0-9A-Fa-f]{3,6}$/.test(color)) {
                    var swatch = document.createElement('span');
                    swatch.className = 'sgr-color-swatch';
                    swatch.style.background = color;
                    previewContainer.appendChild(swatch);
                }
            });
        });
    }
})();
</script>
