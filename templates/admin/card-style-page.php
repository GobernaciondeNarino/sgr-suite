<?php
/**
 * SGR Suite - Card Customizer Admin Page
 *
 * @package SGR_Suite
 * @since   2.0.0
 * @var array $settings Current card style settings (from render_page)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap sgr-suite-admin">
    <h1><?php esc_html_e( 'Personalizar Cards', 'sgr-suite' ); ?></h1>
    <p class="sgr-admin-subtitle"><?php esc_html_e( 'Configura la apariencia visual de las tarjetas de proyectos en el grid.', 'sgr-suite' ); ?></p>

    <form method="post" action="options.php">
        <?php settings_fields( 'sgr_card_style_group' ); ?>
        <?php do_settings_sections( 'sgr_card_style_group' ); ?>

        <div class="sgr-customizer-wrap">

            <!-- ============ FORM COLUMN ============ -->
            <div class="sgr-customizer-form">

                <!-- Tab Navigation -->
                <div class="sgr-customizer-tabs">
                    <button type="button" class="sgr-customizer-tab active" data-tab="general"><?php esc_html_e( 'General', 'sgr-suite' ); ?></button>
                    <button type="button" class="sgr-customizer-tab" data-tab="cards"><?php esc_html_e( 'Cards', 'sgr-suite' ); ?></button>
                    <button type="button" class="sgr-customizer-tab" data-tab="busqueda"><?php esc_html_e( 'B&uacute;squeda', 'sgr-suite' ); ?></button>
                    <button type="button" class="sgr-customizer-tab" data-tab="modal"><?php esc_html_e( 'Modal', 'sgr-suite' ); ?></button>
                </div>

                <!-- ====== TAB: General ====== -->
                <div class="sgr-customizer-section active" data-section="general">

                    <!-- Container -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Contenedor', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-container-bg"><?php esc_html_e( 'Color de fondo', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-container-bg"
                                               value="<?php echo esc_attr( $settings['container_bg'] ); ?>"
                                               data-sync="sgr-container-bg-text">
                                        <input type="text" id="sgr-container-bg-text"
                                               name="sgr_suite_card_style[container_bg]"
                                               value="<?php echo esc_attr( $settings['container_bg'] ); ?>"
                                               data-sync="sgr-container-bg">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-container-padding"><?php esc_html_e( 'Padding (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-container-padding"
                                           name="sgr_suite_card_style[container_padding]"
                                           value="<?php echo esc_attr( $settings['container_padding'] ); ?>"
                                           min="0" max="100" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-grid-gap"><?php esc_html_e( 'Grid gap (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-grid-gap"
                                           name="sgr_suite_card_style[grid_gap]"
                                           value="<?php echo esc_attr( $settings['grid_gap'] ); ?>"
                                           min="0" max="100" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-grid-min-width"><?php esc_html_e( 'Ancho m&iacute;nimo card (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-grid-min-width"
                                           name="sgr_suite_card_style[grid_min_width]"
                                           value="<?php echo esc_attr( $settings['grid_min_width'] ); ?>"
                                           min="200" max="600" class="small-text">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Stats Bar -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Barra de Estad&iacute;sticas', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-stat-number-color"><?php esc_html_e( 'Color n&uacute;mero', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-stat-number-color"
                                               value="<?php echo esc_attr( $settings['stat_number_color'] ); ?>"
                                               data-sync="sgr-stat-number-color-text">
                                        <input type="text" id="sgr-stat-number-color-text"
                                               name="sgr_suite_card_style[stat_number_color]"
                                               value="<?php echo esc_attr( $settings['stat_number_color'] ); ?>"
                                               data-sync="sgr-stat-number-color">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-stat-number-size"><?php esc_html_e( 'Tama&ntilde;o n&uacute;mero (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-stat-number-size"
                                           name="sgr_suite_card_style[stat_number_size]"
                                           value="<?php echo esc_attr( $settings['stat_number_size'] ); ?>"
                                           min="12" max="72" class="small-text">
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
                <!-- /TAB: General -->

                <!-- ====== TAB: Cards ====== -->
                <div class="sgr-customizer-section" data-section="cards">

                    <!-- Cards -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Tarjeta', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-card-bg"><?php esc_html_e( 'Color de fondo', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-card-bg"
                                               value="<?php echo esc_attr( $settings['card_bg'] ); ?>"
                                               data-sync="sgr-card-bg-text">
                                        <input type="text" id="sgr-card-bg-text"
                                               name="sgr_suite_card_style[card_bg]"
                                               value="<?php echo esc_attr( $settings['card_bg'] ); ?>"
                                               data-sync="sgr-card-bg">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-card-border-color"><?php esc_html_e( 'Color de borde', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-card-border-color"
                                               value="<?php echo esc_attr( $settings['card_border_color'] ); ?>"
                                               data-sync="sgr-card-border-color-text">
                                        <input type="text" id="sgr-card-border-color-text"
                                               name="sgr_suite_card_style[card_border_color]"
                                               value="<?php echo esc_attr( $settings['card_border_color'] ); ?>"
                                               data-sync="sgr-card-border-color">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-card-border-radius"><?php esc_html_e( 'Border radius (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="text" id="sgr-card-border-radius"
                                           name="sgr_suite_card_style[card_border_radius]"
                                           value="<?php echo esc_attr( $settings['card_border_radius'] ); ?>"
                                           class="small-text"
                                           placeholder="0">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-card-shadow"><?php esc_html_e( 'Sombra', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="text" id="sgr-card-shadow"
                                           name="sgr_suite_card_style[card_shadow]"
                                           value="<?php echo esc_attr( $settings['card_shadow'] ); ?>"
                                           class="regular-text"
                                           placeholder="none">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-card-hover-shadow"><?php esc_html_e( 'Sombra hover', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="text" id="sgr-card-hover-shadow"
                                           name="sgr_suite_card_style[card_hover_shadow]"
                                           value="<?php echo esc_attr( $settings['card_hover_shadow'] ); ?>"
                                           class="regular-text"
                                           placeholder="0 10px 25px rgba(0,0,0,0.08)">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-card-hover-border"><?php esc_html_e( 'Borde hover', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-card-hover-border"
                                               value="<?php echo esc_attr( $settings['card_hover_border'] ); ?>"
                                               data-sync="sgr-card-hover-border-text">
                                        <input type="text" id="sgr-card-hover-border-text"
                                               name="sgr_suite_card_style[card_hover_border]"
                                               value="<?php echo esc_attr( $settings['card_hover_border'] ); ?>"
                                               data-sync="sgr-card-hover-border">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-card-hover-translate"><?php esc_html_e( 'Hover translate Y (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-card-hover-translate"
                                           name="sgr_suite_card_style[card_hover_translate]"
                                           value="<?php echo esc_attr( $settings['card_hover_translate'] ); ?>"
                                           min="-20" max="20" class="small-text">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Imagen -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Imagen', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-image-height"><?php esc_html_e( 'Altura (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-image-height"
                                           name="sgr_suite_card_style[image_height]"
                                           value="<?php echo esc_attr( $settings['image_height'] ); ?>"
                                           min="80" max="500" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-image-border-bottom"><?php esc_html_e( 'Borde inferior', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-image-border-bottom"
                                               value="<?php echo esc_attr( $settings['image_border_bottom'] ); ?>"
                                               data-sync="sgr-image-border-bottom-text">
                                        <input type="text" id="sgr-image-border-bottom-text"
                                               name="sgr_suite_card_style[image_border_bottom]"
                                               value="<?php echo esc_attr( $settings['image_border_bottom'] ); ?>"
                                               data-sync="sgr-image-border-bottom">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-image-default-url"><?php esc_html_e( 'Imagen por defecto', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="text" id="sgr-image-default-url"
                                           name="sgr_suite_card_style[image_default_url]"
                                           value="<?php echo esc_attr( $settings['image_default_url'] ); ?>"
                                           class="large-text"
                                           placeholder="https://...">
                                    <p class="description"><?php esc_html_e( 'URL de la imagen que se muestra cuando el proyecto no tiene imagen.', 'sgr-suite' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Badge -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Badge', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-badge-bg"><?php esc_html_e( 'Color de fondo', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-badge-bg"
                                               value="<?php echo esc_attr( $settings['badge_bg'] ); ?>"
                                               data-sync="sgr-badge-bg-text">
                                        <input type="text" id="sgr-badge-bg-text"
                                               name="sgr_suite_card_style[badge_bg]"
                                               value="<?php echo esc_attr( $settings['badge_bg'] ); ?>"
                                               data-sync="sgr-badge-bg">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-badge-text-color"><?php esc_html_e( 'Color de texto', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-badge-text-color"
                                               value="<?php echo esc_attr( $settings['badge_text_color'] ); ?>"
                                               data-sync="sgr-badge-text-color-text">
                                        <input type="text" id="sgr-badge-text-color-text"
                                               name="sgr_suite_card_style[badge_text_color]"
                                               value="<?php echo esc_attr( $settings['badge_text_color'] ); ?>"
                                               data-sync="sgr-badge-text-color">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-badge-border-radius"><?php esc_html_e( 'Border radius (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="text" id="sgr-badge-border-radius"
                                           name="sgr_suite_card_style[badge_border_radius]"
                                           value="<?php echo esc_attr( $settings['badge_border_radius'] ); ?>"
                                           class="small-text"
                                           placeholder="0">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- T&iacute;tulo -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'T&iacute;tulo', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-title-color"><?php esc_html_e( 'Color', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-title-color"
                                               value="<?php echo esc_attr( $settings['title_color'] ); ?>"
                                               data-sync="sgr-title-color-text">
                                        <input type="text" id="sgr-title-color-text"
                                               name="sgr_suite_card_style[title_color]"
                                               value="<?php echo esc_attr( $settings['title_color'] ); ?>"
                                               data-sync="sgr-title-color">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-title-size"><?php esc_html_e( 'Tama&ntilde;o (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-title-size"
                                           name="sgr_suite_card_style[title_size]"
                                           value="<?php echo esc_attr( $settings['title_size'] ); ?>"
                                           min="10" max="36" class="small-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-title-max-chars"><?php esc_html_e( 'M&aacute;x. caracteres', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-title-max-chars"
                                           name="sgr_suite_card_style[title_max_chars]"
                                           value="<?php echo esc_attr( $settings['title_max_chars'] ); ?>"
                                           min="30" max="500" class="small-text">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- BPIN -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'BPIN', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-bpin-color"><?php esc_html_e( 'Color', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-bpin-color"
                                               value="<?php echo esc_attr( $settings['bpin_color'] ); ?>"
                                               data-sync="sgr-bpin-color-text">
                                        <input type="text" id="sgr-bpin-color-text"
                                               name="sgr_suite_card_style[bpin_color]"
                                               value="<?php echo esc_attr( $settings['bpin_color'] ); ?>"
                                               data-sync="sgr-bpin-color">
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Footer -->
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Footer', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-footer-text-color"><?php esc_html_e( 'Color de texto', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-footer-text-color"
                                               value="<?php echo esc_attr( $settings['footer_text_color'] ); ?>"
                                               data-sync="sgr-footer-text-color-text">
                                        <input type="text" id="sgr-footer-text-color-text"
                                               name="sgr_suite_card_style[footer_text_color]"
                                               value="<?php echo esc_attr( $settings['footer_text_color'] ); ?>"
                                               data-sync="sgr-footer-text-color">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-link-color"><?php esc_html_e( 'Color de enlace', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-link-color"
                                               value="<?php echo esc_attr( $settings['link_color'] ); ?>"
                                               data-sync="sgr-link-color-text">
                                        <input type="text" id="sgr-link-color-text"
                                               name="sgr_suite_card_style[link_color]"
                                               value="<?php echo esc_attr( $settings['link_color'] ); ?>"
                                               data-sync="sgr-link-color">
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
                <!-- /TAB: Cards -->

                <!-- ====== TAB: B&uacute;squeda ====== -->
                <div class="sgr-customizer-section" data-section="busqueda">
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Campo de B&uacute;squeda', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-search-border-color"><?php esc_html_e( 'Color de borde', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-search-border-color"
                                               value="<?php echo esc_attr( $settings['search_border_color'] ); ?>"
                                               data-sync="sgr-search-border-color-text">
                                        <input type="text" id="sgr-search-border-color-text"
                                               name="sgr_suite_card_style[search_border_color]"
                                               value="<?php echo esc_attr( $settings['search_border_color'] ); ?>"
                                               data-sync="sgr-search-border-color">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-search-font-size"><?php esc_html_e( 'Tama&ntilde;o de fuente (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-search-font-size"
                                           name="sgr_suite_card_style[search_font_size]"
                                           value="<?php echo esc_attr( $settings['search_font_size'] ); ?>"
                                           min="12" max="48" class="small-text">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- /TAB: B&uacute;squeda -->

                <!-- ====== TAB: Modal ====== -->
                <div class="sgr-customizer-section" data-section="modal">
                    <div class="sgr-fieldset-group">
                        <h3><?php esc_html_e( 'Ventana Modal', 'sgr-suite' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="sgr-modal-bg"><?php esc_html_e( 'Color de fondo', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-modal-bg"
                                               value="<?php echo esc_attr( $settings['modal_bg'] ); ?>"
                                               data-sync="sgr-modal-bg-text">
                                        <input type="text" id="sgr-modal-bg-text"
                                               name="sgr_suite_card_style[modal_bg]"
                                               value="<?php echo esc_attr( $settings['modal_bg'] ); ?>"
                                               data-sync="sgr-modal-bg">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-modal-border-color"><?php esc_html_e( 'Color de borde', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <span class="sgr-color-field">
                                        <input type="color" id="sgr-modal-border-color"
                                               value="<?php echo esc_attr( $settings['modal_border_color'] ); ?>"
                                               data-sync="sgr-modal-border-color-text">
                                        <input type="text" id="sgr-modal-border-color-text"
                                               name="sgr_suite_card_style[modal_border_color]"
                                               value="<?php echo esc_attr( $settings['modal_border_color'] ); ?>"
                                               data-sync="sgr-modal-border-color">
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sgr-modal-max-width"><?php esc_html_e( 'Ancho m&aacute;ximo (px)', 'sgr-suite' ); ?></label></th>
                                <td>
                                    <input type="number" id="sgr-modal-max-width"
                                           name="sgr_suite_card_style[modal_max_width]"
                                           value="<?php echo esc_attr( $settings['modal_max_width'] ); ?>"
                                           min="400" max="1600" class="small-text">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- /TAB: Modal -->

                <?php submit_button( esc_html__( 'Guardar Cambios', 'sgr-suite' ) ); ?>

            </div>
            <!-- /FORM COLUMN -->

            <!-- ============ PREVIEW COLUMN ============ -->
            <div class="sgr-customizer-preview">
                <div class="sgr-preview-panel">
                    <h3><?php esc_html_e( 'Vista Previa', 'sgr-suite' ); ?></h3>

                    <div class="sgr-preview-card" id="sgr-live-preview">
                        <div class="sgr-preview-card-image">
                            <span class="dashicons dashicons-location-alt"></span>
                            <span class="sgr-preview-card-badge"><?php esc_html_e( 'En ejecuci&oacute;n', 'sgr-suite' ); ?></span>
                        </div>
                        <div class="sgr-preview-card-body">
                            <div class="sgr-preview-card-title"><?php esc_html_e( 'Mejoramiento v&iacute;as terciarias del municipio', 'sgr-suite' ); ?></div>
                            <div class="sgr-preview-card-bpin">BPIN 2024000123456</div>
                        </div>
                        <div class="sgr-preview-card-stats">
                            <div class="sgr-preview-card-stat">
                                <div class="sgr-preview-card-stat-number">$1.2M</div>
                                <div class="sgr-preview-card-stat-label"><?php esc_html_e( 'Valor', 'sgr-suite' ); ?></div>
                            </div>
                            <div class="sgr-preview-card-stat">
                                <div class="sgr-preview-card-stat-number">3</div>
                                <div class="sgr-preview-card-stat-label"><?php esc_html_e( 'Contratos', 'sgr-suite' ); ?></div>
                            </div>
                            <div class="sgr-preview-card-stat">
                                <div class="sgr-preview-card-stat-number">65%</div>
                                <div class="sgr-preview-card-stat-label"><?php esc_html_e( 'Avance', 'sgr-suite' ); ?></div>
                            </div>
                        </div>
                        <div class="sgr-preview-card-footer">
                            <span><?php esc_html_e( 'Depto. Infraestructura', 'sgr-suite' ); ?></span>
                            <a href="#"><?php esc_html_e( 'Ver m&aacute;s', 'sgr-suite' ); ?></a>
                        </div>
                    </div>

                </div>
            </div>
            <!-- /PREVIEW COLUMN -->

        </div>
    </form>
</div>

<script>
(function() {
    'use strict';

    /* Tab switching */
    document.querySelectorAll('.sgr-customizer-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.sgr-customizer-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.sgr-customizer-section').forEach(function(s) { s.classList.remove('active'); });
            tab.classList.add('active');
            var section = document.querySelector('.sgr-customizer-section[data-section="' + tab.getAttribute('data-tab') + '"]');
            if (section) { section.classList.add('active'); }
        });
    });

    /* Sync color pickers with text inputs */
    document.querySelectorAll('.sgr-color-field input[type="color"]').forEach(function(picker) {
        var syncId = picker.getAttribute('data-sync');
        if (!syncId) return;
        var textInput = document.getElementById(syncId);
        if (!textInput) return;

        picker.addEventListener('input', function() {
            textInput.value = picker.value;
        });
        textInput.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
                picker.value = textInput.value;
            }
        });
    });
})();
</script>
