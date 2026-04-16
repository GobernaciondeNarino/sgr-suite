<?php
/**
 * SGR Suite - Chart Configuration Meta Box v2.1.0
 *
 * Visual chart type selector with SVG icons matching secop-suite style.
 *
 * @package SGR_Suite
 * @since   2.1.0
 * @var array   $config Chart configuration
 * @var WP_Post $post   Current post
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_nonce_field( 'sgr_chart_config_save', 'sgr_chart_config_nonce' );

$visualizer  = sgr_suite()->visualizer;
$chart_types = $visualizer->get_chart_types();
$views       = sgr_suite()->database->get_chart_views();

// SVG icons for each chart type
$chart_icons = [
    'bar'          => '<svg viewBox="0 0 48 48" width="48" height="48"><rect x="6" y="22" width="8" height="20" fill="#4285f4" rx="1"/><rect x="17" y="12" width="8" height="30" fill="#ea4335" rx="1"/><rect x="28" y="6" width="8" height="36" fill="#fbbc04" rx="1"/><rect x="39" y="18" width="3" height="24" fill="#34a853" rx="1"/></svg>',
    'line'         => '<svg viewBox="0 0 48 48" width="48" height="48"><polyline points="6,36 16,20 26,28 36,12 42,16" fill="none" stroke="#4285f4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'area'         => '<svg viewBox="0 0 48 48" width="48" height="48"><polygon points="6,42 6,30 16,18 26,26 36,10 42,14 42,42" fill="#4285f4" opacity="0.3"/><polyline points="6,30 16,18 26,26 36,10 42,14" fill="none" stroke="#4285f4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'pie'          => '<svg viewBox="0 0 48 48" width="48" height="48"><circle cx="24" cy="24" r="16" fill="#fbbc04"/><path d="M24,24 L24,8 A16,16 0 0,1 37.9,16.1 Z" fill="#ea4335"/><path d="M24,24 L37.9,16.1 A16,16 0 0,1 40,24 L24,24 Z" fill="#34a853"/><path d="M24,24 L8,24 A16,16 0 0,1 24,8 Z" fill="#4285f4"/></svg>',
    'donut'        => '<svg viewBox="0 0 48 48" width="48" height="48"><circle cx="24" cy="24" r="16" fill="none" stroke="#fbbc04" stroke-width="8"/><path d="M24,8 A16,16 0 0,1 37.9,16.1" fill="none" stroke="#ea4335" stroke-width="8" stroke-linecap="butt"/><path d="M37.9,16.1 A16,16 0 0,1 40,24" fill="none" stroke="#34a853" stroke-width="8" stroke-linecap="butt"/><path d="M8,24 A16,16 0 0,1 24,8" fill="none" stroke="#4285f4" stroke-width="8" stroke-linecap="butt"/></svg>',
    'treemap'      => '<svg viewBox="0 0 48 48" width="48" height="48"><rect x="4" y="4" width="24" height="20" fill="#4285f4" rx="1"/><rect x="30" y="4" width="14" height="20" fill="#34a853" rx="1"/><rect x="4" y="26" width="14" height="18" fill="#fbbc04" rx="1"/><rect x="20" y="26" width="24" height="18" fill="#ea4335" rx="1"/></svg>',
    'barH'         => '<svg viewBox="0 0 48 48" width="48" height="48"><rect x="4" y="6" width="36" height="7" fill="#4285f4" rx="1"/><rect x="4" y="16" width="24" height="7" fill="#ea4335" rx="1"/><rect x="4" y="26" width="30" height="7" fill="#fbbc04" rx="1"/><rect x="4" y="36" width="18" height="7" fill="#34a853" rx="1"/></svg>',
    'pack'         => '<svg viewBox="0 0 48 48" width="48" height="48"><circle cx="20" cy="22" r="12" fill="#4285f4" opacity="0.7"/><circle cx="34" cy="18" r="8" fill="#ea4335" opacity="0.7"/><circle cx="30" cy="34" r="7" fill="#34a853" opacity="0.7"/><circle cx="14" cy="36" r="5" fill="#fbbc04" opacity="0.7"/></svg>',
    'stacked_bar'  => '<svg viewBox="0 0 48 48" width="48" height="48"><rect x="6" y="28" width="8" height="14" fill="#4285f4" rx="1"/><rect x="6" y="18" width="8" height="10" fill="#fbbc04" rx="1"/><rect x="17" y="20" width="8" height="22" fill="#4285f4" rx="1"/><rect x="17" y="8" width="8" height="12" fill="#fbbc04" rx="1"/><rect x="28" y="24" width="8" height="18" fill="#4285f4" rx="1"/><rect x="28" y="12" width="8" height="12" fill="#fbbc04" rx="1"/><rect x="39" y="30" width="5" height="12" fill="#4285f4" rx="1"/><rect x="39" y="22" width="5" height="8" fill="#fbbc04" rx="1"/></svg>',
    'grouped_bar'  => '<svg viewBox="0 0 48 48" width="48" height="48"><rect x="4" y="20" width="5" height="22" fill="#4285f4" rx="1"/><rect x="10" y="14" width="5" height="28" fill="#fbbc04" rx="1"/><rect x="19" y="10" width="5" height="32" fill="#4285f4" rx="1"/><rect x="25" y="18" width="5" height="24" fill="#fbbc04" rx="1"/><rect x="34" y="24" width="5" height="18" fill="#4285f4" rx="1"/><rect x="40" y="16" width="5" height="26" fill="#fbbc04" rx="1"/></svg>',
    'scatter'      => '<svg viewBox="0 0 48 48" width="48" height="48"><line x1="6" y1="42" x2="44" y2="42" stroke="#94a3b8" stroke-width="1.5"/><line x1="6" y1="6" x2="6" y2="42" stroke="#94a3b8" stroke-width="1.5"/><circle cx="12" cy="34" r="3" fill="#4285f4"/><circle cx="18" cy="26" r="2.5" fill="#ea4335"/><circle cx="23" cy="30" r="3.5" fill="#fbbc04"/><circle cx="28" cy="18" r="2" fill="#34a853"/><circle cx="33" cy="24" r="3" fill="#4285f4"/><circle cx="37" cy="12" r="2.5" fill="#ea4335"/><circle cx="41" cy="20" r="2.5" fill="#fbbc04"/></svg>',
    'geomap'       => '<svg viewBox="0 0 48 48" width="48" height="48"><path d="M6 8 L18 6 L30 10 L42 8 L42 40 L30 42 L18 38 L6 40 Z" fill="#bfdbfe" stroke="#1e40af" stroke-width="1.5" stroke-linejoin="round"/><path d="M18 6 L18 38 M30 10 L30 42" stroke="#1e40af" stroke-width="1" stroke-dasharray="2,2"/><circle cx="14" cy="22" r="2" fill="#dc2626"/><circle cx="24" cy="18" r="2" fill="#ea580c"/><circle cx="36" cy="26" r="2" fill="#fbbc04"/><circle cx="22" cy="32" r="2" fill="#1e40af"/></svg>',
];

$data_views = [];
foreach ( $views as $vk => $vi ) {
    $data_views[ $vk ] = $vi['label'];
}
?>

<div class="sgr-chart-config-wrap">

    <!-- Tipo de Gráfica -->
    <div class="sgr-config-section">
        <h3>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px;"><rect x="3" y="12" width="4" height="9"/><rect x="10" y="6" width="4" height="15"/><rect x="17" y="3" width="4" height="18"/></svg>
            <?php esc_html_e( 'Tipo de Gráfica', 'sgr-suite' ); ?>
        </h3>
        <div class="sgr-chart-type-grid">
            <?php foreach ( $chart_types as $type_key => $type_info ) : ?>
                <label class="sgr-chart-type-option <?php echo ( ( $config['chart_type'] ?? 'bar' ) === $type_key ) ? 'selected' : ''; ?>">
                    <input type="radio" name="sgr_chart[chart_type]"
                           value="<?php echo esc_attr( $type_key ); ?>"
                           <?php checked( $config['chart_type'] ?? 'bar', $type_key ); ?>>
                    <span class="sgr-chart-type-icon">
                        <?php echo $chart_icons[ $type_key ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG literals defined above ?>
                    </span>
                    <span class="sgr-chart-type-name"><?php echo esc_html( $type_info['label'] ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Vista de Datos -->
    <div class="sgr-config-section">
        <h3><?php esc_html_e( 'Vista de Datos', 'sgr-suite' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="sgr-data-view"><?php esc_html_e( 'Vista predefinida', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[data_view]" id="sgr-data-view" class="regular-text" style="min-width:360px;">
                        <?php
                        // v2.5.0: agrupación por categoría funcional declarada en
                        // SGR_Suite_Visualizer::get_view_metadata(). Las vistas sin
                        // metadata caen al grupo "otros" para no perderse.
                        $metadata    = $visualizer->get_view_metadata();
                        $categories  = $visualizer->get_view_categories();
                        $current_view = $config['data_view'] ?? 'valor_por_dependencia';

                        $grouped = [];
                        foreach ( $categories as $cat_key => $_ ) {
                            $grouped[ $cat_key ] = [];
                        }
                        $grouped['otros'] = [];

                        foreach ( $data_views as $vk => $vl ) {
                            $cat = $metadata[ $vk ]['category'] ?? 'otros';
                            if ( ! isset( $grouped[ $cat ] ) ) {
                                $grouped['otros'][ $vk ] = $vl;
                            } else {
                                $grouped[ $cat ][ $vk ] = $vl;
                            }
                        }

                        foreach ( $categories as $cat_key => $cat_label ) :
                            if ( empty( $grouped[ $cat_key ] ) ) {
                                continue;
                            }
                        ?>
                            <optgroup label="<?php echo esc_attr( $cat_label ); ?>">
                                <?php foreach ( $grouped[ $cat_key ] as $vk => $vl ) :
                                    $compat = $metadata[ $vk ]['charts'] ?? [];
                                ?>
                                    <option value="<?php echo esc_attr( $vk ); ?>"
                                            data-charts="<?php echo esc_attr( implode( ',', $compat ) ); ?>"
                                            <?php selected( $current_view, $vk ); ?>>
                                        <?php echo esc_html( $vl ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>

                        <?php if ( ! empty( $grouped['otros'] ) ) : ?>
                            <optgroup label="<?php esc_attr_e( 'Otros', 'sgr-suite' ); ?>">
                                <?php foreach ( $grouped['otros'] as $vk => $vl ) : ?>
                                    <option value="<?php echo esc_attr( $vk ); ?>" <?php selected( $current_view, $vk ); ?>>
                                        <?php echo esc_html( $vl ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Las vistas se agrupan por función: Totales, Rankings, Distribución, Avance, Series (apiladas/agrupadas), Temporal y Geográfico. Al cambiar la vista el tipo de gráfico se ajusta automáticamente si la combinación es incompatible.', 'sgr-suite' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-limit"><?php esc_html_e( 'Límite', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="number" name="sgr_chart[limit]" id="sgr-limit"
                           value="<?php echo esc_attr( $config['limit'] ?? 20 ); ?>"
                           min="1" max="500" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="sgr-order-dir"><?php esc_html_e( 'Orden', 'sgr-suite' ); ?></label></th>
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
                <td><input type="number" name="sgr_chart[chart_height]" id="sgr-chart-height" value="<?php echo esc_attr( $config['chart_height'] ?? 400 ); ?>" min="200" max="1200" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="sgr-number-format"><?php esc_html_e( 'Formato Numérico', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[number_format]" id="sgr-number-format" class="regular-text">
                        <option value="colombiano" <?php selected( $config['number_format'] ?? 'colombiano', 'colombiano' ); ?>>Colombiano (1.234.567,89)</option>
                        <option value="millones" <?php selected( $config['number_format'] ?? 'colombiano', 'millones' ); ?>>Millones (1.5M)</option>
                        <option value="internacional" <?php selected( $config['number_format'] ?? 'colombiano', 'internacional' ); ?>>Internacional (1,234,567.89)</option>
                        <option value="sin_formato" <?php selected( $config['number_format'] ?? 'colombiano', 'sin_formato' ); ?>>Sin Formato</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-colors"><?php esc_html_e( 'Colores', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="text" name="sgr_chart[colors]" id="sgr-colors" value="<?php echo esc_attr( implode( ', ', $config['colors'] ?? [] ) ); ?>" class="large-text" placeholder="#348afb, #1e40af, #059669, #d97706">
                    <div id="sgr-color-preview" class="sgr-color-swatches">
                        <?php foreach ( $config['colors'] ?? [] as $color ) : ?>
                            <span class="sgr-color-swatch" style="background: <?php echo esc_attr( $color ); ?>;"></span>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-legend-mode"><?php esc_html_e( 'Leyenda', 'sgr-suite' ); ?></label></th>
                <td>
                    <select name="sgr_chart[legend_mode]" id="sgr-legend-mode" class="regular-text">
                        <option value="auto"   <?php selected( $config['legend_mode'] ?? 'auto', 'auto' ); ?>><?php esc_html_e( 'Automática (texto D3plus)', 'sgr-suite' ); ?></option>
                        <option value="text"   <?php selected( $config['legend_mode'] ?? 'auto', 'text' ); ?>><?php esc_html_e( 'Solo texto (personalizada)', 'sgr-suite' ); ?></option>
                        <option value="icons"  <?php selected( $config['legend_mode'] ?? 'auto', 'icons' ); ?>><?php esc_html_e( 'Solo iconos (personalizada)', 'sgr-suite' ); ?></option>
                        <option value="hidden" <?php selected( $config['legend_mode'] ?? 'auto', 'hidden' ); ?>><?php esc_html_e( 'Oculta', 'sgr-suite' ); ?></option>
                    </select>
                    <p class="description">
                        <?php esc_html_e( '"Solo iconos" muestra únicamente los cuadros con iconos coherentes a cada categoría (el nombre aparece como tooltip al pasar el cursor). "Solo texto" usa un punto de color + el nombre.', 'sgr-suite' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Eje X (etiquetas)', 'sgr-suite' ); ?></th>
                <td>
                    <label style="display:block;margin-bottom:6px;">
                        <input type="checkbox" name="sgr_chart[x_labels_visible]" value="1" <?php checked( $config['x_labels_visible'] ?? true ); ?>>
                        <?php esc_html_e( 'Mostrar etiquetas del eje X', 'sgr-suite' ); ?>
                    </label>
                    <label style="display:inline-block;margin-right:14px;">
                        <?php esc_html_e( 'Rotación (°)', 'sgr-suite' ); ?>
                        <input type="number" name="sgr_chart[x_labels_rotate]" id="sgr-x-labels-rotate"
                               value="<?php echo esc_attr( $config['x_labels_rotate'] ?? 0 ); ?>"
                               min="0" max="90" step="5" class="small-text">
                    </label>
                    <label style="display:inline-block;">
                        <?php esc_html_e( 'Tamaño (px)', 'sgr-suite' ); ?>
                        <input type="number" name="sgr_chart[x_labels_size]" id="sgr-x-labels-size"
                               value="<?php echo esc_attr( $config['x_labels_size'] ?? 12 ); ?>"
                               min="8" max="24" class="small-text">
                    </label>
                    <p class="description"><?php esc_html_e( 'Aplica a barras, líneas, área y scatter. En pie/donut/treemap/geomap se ignora.', 'sgr-suite' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-x-title"><?php esc_html_e( 'Título del eje X', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="text" name="sgr_chart[x_title]" id="sgr-x-title"
                           value="<?php echo esc_attr( $config['x_title'] ?? '' ); ?>"
                           class="regular-text"
                           placeholder="<?php esc_attr_e( 'Ej: Dependencia, Vigencia, Municipio…', 'sgr-suite' ); ?>">
                    <p class="description"><?php esc_html_e( 'Texto descriptivo del eje horizontal. Déjalo vacío para no mostrar título.', 'sgr-suite' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sgr-y-title"><?php esc_html_e( 'Título del eje Y', 'sgr-suite' ); ?></label></th>
                <td>
                    <input type="text" name="sgr_chart[y_title]" id="sgr-y-title"
                           value="<?php echo esc_attr( $config['y_title'] ?? '' ); ?>"
                           class="regular-text"
                           placeholder="<?php esc_attr_e( 'Ej: Inversión (COP), Proyectos, Avance (%)…', 'sgr-suite' ); ?>">
                    <p class="description"><?php esc_html_e( 'Texto descriptivo del eje vertical. Déjalo vacío para no mostrar título.', 'sgr-suite' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Opciones', 'sgr-suite' ); ?></th>
                <td>
                    <label><input type="checkbox" name="sgr_chart[show_legend]" value="1" <?php checked( $config['show_legend'] ?? true ); ?>> <?php esc_html_e( 'Mostrar leyenda (compatibilidad)', 'sgr-suite' ); ?></label><br>
                    <label><input type="checkbox" name="sgr_chart[show_toolbar]" value="1" <?php checked( $config['show_toolbar'] ?? true ); ?>> <?php esc_html_e( 'Mostrar barra de herramientas (Detalle, Compartir, Datos, Imagen, Descarga)', 'sgr-suite' ); ?></label>
                </td>
            </tr>
        </table>
    </div>
</div>
