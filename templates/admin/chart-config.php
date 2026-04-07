<?php
/**
 * SGR Suite - Configuración del Gráfico (Meta Box) v2.0.0
 *
 * Usa vistas predefinidas con JOINs en lugar de selección de tabla/columna.
 *
 * @package SGR_Suite
 * @since   2.0.0
 * @var array   $config Configuración actual del gráfico
 * @var WP_Post $post   Post actual
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_nonce_field( 'sgr_chart_config_save', 'sgr_chart_config_nonce' );

$visualizer  = sgr_suite()->visualizer;
$chart_types = $visualizer->get_chart_types();
$views       = sgr_suite()->database->get_chart_views();
?>

<div class="sgr-chart-config-wrap">

    <!-- Tipo de Gráfico -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Tipo de Gráfico', 'sgr-suite' ); ?></h3>
        <div class="sgr-chart-type-grid">
            <?php foreach ( $chart_types as $type_key => $type_info ) : ?>
                <label class="sgr-chart-type-option <?php echo ( ( $config['chart_type'] ?? '' ) === $type_key ) ? 'selected' : ''; ?>">
                    <input type="radio" name="sgr_chart[chart_type]"
                           value="<?php echo esc_attr( $type_key ); ?>"
                           <?php checked( $config['chart_type'] ?? 'bar', $type_key ); ?>>
                    <span class="sgr-chart-type-icon" data-type="<?php echo esc_attr( $type_key ); ?>"></span>
                    <span class="sgr-chart-type-name"><?php echo esc_html( $type_info['label'] ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Vista de Datos -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Fuente de Datos', 'sgr-suite' ); ?></h3>
        <p class="description" style="margin-bottom:15px;">
            <?php esc_html_e( 'Seleccione una vista predefinida. Cada vista incluye JOINs entre tablas para análisis cruzado.', 'sgr-suite' ); ?>
        </p>
        <table class="form-table">
            <tr>
                <th><label for="sgr-data-view"><?php esc_html_e( 'Vista de Datos', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[data_view]" id="sgr-data-view" class="regular-text" style="min-width:350px;">
                        <?php foreach ( $views as $v_key => $v_info ) : ?>
                            <option value="<?php echo esc_attr( $v_key ); ?>"
                                    <?php selected( $config['data_view'] ?? 'valor_por_dependencia', $v_key ); ?>>
                                <?php echo esc_html( $v_info['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Columnas disponibles: label (categoría), value (valor numérico).', 'sgr-suite' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-limit"><?php esc_html_e( 'Límite de registros', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="number" name="sgr_chart[limit]" id="sgr-limit"
                           value="<?php echo esc_attr( $config['limit'] ?? 20 ); ?>"
                           min="1" max="500" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="sgr-order-dir"><?php esc_html_e( 'Ordenamiento', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[order_dir]" id="sgr-order-dir">
                        <option value="DESC" <?php selected( $config['order_dir'] ?? 'DESC', 'DESC' ); ?>><?php esc_html_e( 'Mayor a menor', 'sgr-suite' ); ?></option>
                        <option value="ASC" <?php selected( $config['order_dir'] ?? 'DESC', 'ASC' ); ?>><?php esc_html_e( 'Menor a mayor', 'sgr-suite' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
    </div>

    <!-- Apariencia -->
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
                <th><label for="sgr-number-format"><?php esc_html_e( 'Formato Numérico', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[number_format]" id="sgr-number-format" class="regular-text">
                        <option value="colombiano" <?php selected( $config['number_format'] ?? '', 'colombiano' ); ?>>Colombiano (1.234.567,89)</option>
                        <option value="millones" <?php selected( $config['number_format'] ?? '', 'millones' ); ?>>Millones (1.5M)</option>
                        <option value="internacional" <?php selected( $config['number_format'] ?? '', 'internacional' ); ?>>Internacional (1,234,567.89)</option>
                        <option value="sin_formato" <?php selected( $config['number_format'] ?? '', 'sin_formato' ); ?>>Sin Formato</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-colors"><?php esc_html_e( 'Colores', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="text" name="sgr_chart[colors]" id="sgr-colors"
                           value="<?php echo esc_attr( implode( ', ', $config['colors'] ?? [] ) ); ?>"
                           class="large-text" placeholder="#348afb, #1e40af, #059669, #d97706">
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
