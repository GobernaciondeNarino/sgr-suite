<?php
/**
 * SGR Suite - Configuración del Gráfico (Meta Box)
 *
 * @package SGR_Suite
 * @since   1.0.1
 * @var array   $config Configuración actual del gráfico
 * @var WP_Post $post   Post actual
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_nonce_field( 'sgr_chart_config_save', 'sgr_chart_config_nonce' );

$visualizer  = sgr_suite()->visualizer;
$chart_types = $visualizer->get_chart_types();
$tables      = $visualizer->get_available_tables();

$current_source  = $config['data_source'] ?? 'proyectos';
$current_columns = $visualizer->get_table_columns( $current_source );
?>

<div class="sgr-chart-config-wrap">

    <!-- Tipo de Gráfico -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Tipo de Gráfico', 'sgr-suite' ); ?></h3>
        <div class="sgr-chart-type-grid">
            <?php foreach ( $chart_types as $type_key => $type_label ) : ?>
                <label class="sgr-chart-type-option <?php echo ( $config['chart_type'] === $type_key ) ? 'selected' : ''; ?>">
                    <input type="radio" name="sgr_chart[chart_type]"
                           value="<?php echo esc_attr( $type_key ); ?>"
                           <?php checked( $config['chart_type'], $type_key ); ?>>
                    <span class="sgr-chart-type-icon" data-type="<?php echo esc_attr( $type_key ); ?>"></span>
                    <span class="sgr-chart-type-name"><?php echo esc_html( $type_label ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Fuente de Datos -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Fuente de Datos', 'sgr-suite' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="sgr-data-source"><?php esc_html_e( 'Tabla', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[data_source]" id="sgr-data-source" class="regular-text">
                        <?php foreach ( $tables as $t_key => $t_label ) : ?>
                            <option value="<?php echo esc_attr( $t_key ); ?>" <?php selected( $current_source, $t_key ); ?>>
                                <?php echo esc_html( $t_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-x-field"><?php esc_html_e( 'Campo X (Categoría)', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[x_field]" id="sgr-x-field" class="regular-text">
                        <option value=""><?php esc_html_e( '-- Seleccionar --', 'sgr-suite' ); ?></option>
                        <?php foreach ( $current_columns as $col ) : ?>
                            <option value="<?php echo esc_attr( $col['name'] ); ?>"
                                    <?php selected( $config['x_field'], $col['name'] ); ?>>
                                <?php echo esc_html( $col['name'] . ' (' . $col['type'] . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Eje X o etiquetas de categoría.', 'sgr-suite' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-y-field"><?php esc_html_e( 'Campo Y (Valor)', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[y_field]" id="sgr-y-field" class="regular-text">
                        <option value=""><?php esc_html_e( '-- Conteo --', 'sgr-suite' ); ?></option>
                        <?php foreach ( $current_columns as $col ) : ?>
                            <option value="<?php echo esc_attr( $col['name'] ); ?>"
                                    <?php selected( $config['y_field'], $col['name'] ); ?>>
                                <?php echo esc_html( $col['name'] . ' (' . $col['type'] . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Campo numérico para el eje Y. Si se deja vacío, se usa COUNT.', 'sgr-suite' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-aggregate"><?php esc_html_e( 'Función de Agregación', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[aggregate]" id="sgr-aggregate" class="regular-text">
                        <?php
                        $agg_options = [
                            'SUM'   => 'SUM - Suma',
                            'COUNT' => 'COUNT - Conteo',
                            'AVG'   => 'AVG - Promedio',
                            'MAX'   => 'MAX - Máximo',
                            'MIN'   => 'MIN - Mínimo',
                        ];
                        foreach ( $agg_options as $agg_key => $agg_label ) :
                        ?>
                            <option value="<?php echo esc_attr( $agg_key ); ?>"
                                    <?php selected( $config['aggregate'], $agg_key ); ?>>
                                <?php echo esc_html( $agg_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-group-by"><?php esc_html_e( 'Agrupar por (Series)', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[group_by]" id="sgr-group-by" class="regular-text">
                        <option value=""><?php esc_html_e( '-- Sin agrupación --', 'sgr-suite' ); ?></option>
                        <?php foreach ( $current_columns as $col ) : ?>
                            <option value="<?php echo esc_attr( $col['name'] ); ?>"
                                    <?php selected( $config['group_by'] ?? '', $col['name'] ); ?>>
                                <?php echo esc_html( $col['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Para gráficos apilados o agrupados con múltiples series.', 'sgr-suite' ); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Ordenamiento y Límite -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Ordenamiento y Límite', 'sgr-suite' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="sgr-order-by"><?php esc_html_e( 'Ordenar por', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[order_by]" id="sgr-order-by" class="regular-text">
                        <option value="" <?php selected( $config['order_by'] ?? '', '' ); ?>><?php esc_html_e( 'Valor (por defecto)', 'sgr-suite' ); ?></option>
                        <?php foreach ( $current_columns as $col ) : ?>
                            <option value="<?php echo esc_attr( $col['name'] ); ?>"
                                    <?php selected( $config['order_by'] ?? '', $col['name'] ); ?>>
                                <?php echo esc_html( $col['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="sgr_chart[order_dir]">
                        <option value="DESC" <?php selected( $config['order_dir'] ?? 'DESC', 'DESC' ); ?>>Descendente</option>
                        <option value="ASC" <?php selected( $config['order_dir'] ?? 'DESC', 'ASC' ); ?>>Ascendente</option>
                    </select>
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
        </table>
    </div>

    <!-- Filtros -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Filtros', 'sgr-suite' ); ?></h3>
        <div id="sgr-filters-container">
            <?php
            $filters = $config['filters'] ?? [];
            if ( ! empty( $filters ) ) :
                foreach ( $filters as $idx => $filter ) :
            ?>
                <div class="sgr-filter-row">
                    <select name="sgr_chart[filters][<?php echo esc_attr( $idx ); ?>][field]" class="sgr-filter-field">
                        <?php foreach ( $current_columns as $col ) : ?>
                            <option value="<?php echo esc_attr( $col['name'] ); ?>"
                                    <?php selected( $filter['field'], $col['name'] ); ?>>
                                <?php echo esc_html( $col['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="sgr_chart[filters][<?php echo esc_attr( $idx ); ?>][operator]">
                        <?php foreach ( [ '=', '!=', '>', '<', '>=', '<=', 'LIKE' ] as $op ) : ?>
                            <option value="<?php echo esc_attr( $op ); ?>" <?php selected( $filter['operator'], $op ); ?>>
                                <?php echo esc_html( $op ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="sgr_chart[filters][<?php echo esc_attr( $idx ); ?>][value]"
                           value="<?php echo esc_attr( $filter['value'] ); ?>" class="regular-text">
                    <button type="button" class="button sgr-remove-filter">&times;</button>
                </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <button type="button" id="sgr-add-filter" class="button button-secondary">
            + <?php esc_html_e( 'Agregar Filtro', 'sgr-suite' ); ?>
        </button>
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
                <th><label for="sgr-x-axis-title"><?php esc_html_e( 'Título Eje X', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="text" name="sgr_chart[x_axis_title]" id="sgr-x-axis-title"
                           value="<?php echo esc_attr( $config['x_axis_title'] ?? '' ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="sgr-y-axis-title"><?php esc_html_e( 'Título Eje Y', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="text" name="sgr_chart[y_axis_title]" id="sgr-y-axis-title"
                           value="<?php echo esc_attr( $config['y_axis_title'] ?? '' ); ?>" class="regular-text">
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

<style>
.sgr-chart-config-wrap { max-width: 900px; }
.sgr-config-section { background: #fff; border: 1px solid #c3c4c7; padding: 15px 20px; margin-bottom: 15px; }
.sgr-config-section h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; color: #1d2327; }
.sgr-chart-type-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
.sgr-chart-type-option { display: flex; flex-direction: column; align-items: center; padding: 15px 10px; border: 2px solid #ddd; cursor: pointer; text-align: center; transition: all 0.2s; border-radius: 4px; }
.sgr-chart-type-option:hover { border-color: #348afb; background: #f0f9ff; }
.sgr-chart-type-option.selected { border-color: #348afb; background: #eff6ff; }
.sgr-chart-type-option input[type="radio"] { display: none; }
.sgr-chart-type-name { font-size: 12px; font-weight: 600; color: #334155; margin-top: 5px; }
.sgr-chart-type-icon { font-size: 24px; }
.sgr-chart-type-icon[data-type="bar"]::before { content: "📊"; }
.sgr-chart-type-icon[data-type="stacked_bar"]::before { content: "📊"; }
.sgr-chart-type-icon[data-type="grouped_bar"]::before { content: "📊"; }
.sgr-chart-type-icon[data-type="line"]::before { content: "📈"; }
.sgr-chart-type-icon[data-type="area"]::before { content: "📉"; }
.sgr-chart-type-icon[data-type="pie"]::before { content: "🥧"; }
.sgr-chart-type-icon[data-type="donut"]::before { content: "🍩"; }
.sgr-chart-type-icon[data-type="treemap"]::before { content: "🗺️"; }
.sgr-chart-type-icon[data-type="pack"]::before { content: "🫧"; }
.sgr-filter-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
.sgr-filter-row select, .sgr-filter-row input[type="text"] { flex: 1; }
.sgr-remove-filter { color: #d63638 !important; border-color: #d63638 !important; font-weight: bold; }
.sgr-color-swatches { display: flex; gap: 5px; margin-top: 8px; flex-wrap: wrap; }
.sgr-color-swatch { width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ccc; display: inline-block; }
</style>
