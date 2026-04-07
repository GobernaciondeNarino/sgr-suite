<?php
/**
 * SGR Suite - Página de Personalización de Cards
 *
 * @package SGR_Suite
 * @since   2.0.0
 * @var array $settings Configuración actual
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap sgr-suite-admin">
    <h1><?php esc_html_e( 'Personalizar Cards de Proyectos', 'sgr-suite' ); ?></h1>
    <p class="sgr-admin-subtitle"><?php esc_html_e( 'Configure la apariencia de las tarjetas en la grilla de proyectos SGR.', 'sgr-suite' ); ?></p>

    <form method="post" action="options.php">
        <?php settings_fields( 'sgr_card_style_group' ); ?>

        <div class="sgr-card-style-layout">
            <div class="sgr-card-style-fields">

                <!-- Contenedor -->
                <div class="sgr-admin-section">
                    <h2><?php esc_html_e( 'Contenedor', 'sgr-suite' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Fondo', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[container_bg]" value="<?php echo esc_attr( $settings['container_bg'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Padding (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[container_padding]" value="<?php echo esc_attr( $settings['container_padding'] ); ?>" min="0" max="60" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Espacio entre cards (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[grid_gap]" value="<?php echo esc_attr( $settings['grid_gap'] ); ?>" min="5" max="60" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Ancho mínimo card (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[grid_min_width]" value="<?php echo esc_attr( $settings['grid_min_width'] ); ?>" min="200" max="500" class="small-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Cards -->
                <div class="sgr-admin-section">
                    <h2><?php esc_html_e( 'Cards', 'sgr-suite' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Fondo', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[card_bg]" value="<?php echo esc_attr( $settings['card_bg'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Borde', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[card_border_color]" value="<?php echo esc_attr( $settings['card_border_color'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Border radius (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[card_border_radius]" value="<?php echo esc_attr( $settings['card_border_radius'] ); ?>" min="0" max="30" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Sombra', 'sgr-suite' ); ?></th>
                            <td><input type="text" name="sgr_suite_card_style[card_shadow]" value="<?php echo esc_attr( $settings['card_shadow'] ); ?>" class="regular-text" placeholder="none"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Sombra hover', 'sgr-suite' ); ?></th>
                            <td><input type="text" name="sgr_suite_card_style[card_hover_shadow]" value="<?php echo esc_attr( $settings['card_hover_shadow'] ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Borde hover', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[card_hover_border]" value="<?php echo esc_attr( $settings['card_hover_border'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Elevación hover (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[card_hover_translate]" value="<?php echo esc_attr( $settings['card_hover_translate'] ); ?>" min="-20" max="0" class="small-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Imagen -->
                <div class="sgr-admin-section">
                    <h2><?php esc_html_e( 'Imagen', 'sgr-suite' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Altura (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[image_height]" value="<?php echo esc_attr( $settings['image_height'] ); ?>" min="100" max="400" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Borde inferior', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[image_border_bottom]" value="<?php echo esc_attr( $settings['image_border_bottom'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Imagen por defecto', 'sgr-suite' ); ?></th>
                            <td><input type="url" name="sgr_suite_card_style[image_default_url]" value="<?php echo esc_url( $settings['image_default_url'] ); ?>" class="large-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Badge, Título, BPIN -->
                <div class="sgr-admin-section">
                    <h2><?php esc_html_e( 'Badge, Título y BPIN', 'sgr-suite' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Badge fondo', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[badge_bg]" value="<?php echo esc_attr( $settings['badge_bg'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Badge texto', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[badge_text_color]" value="<?php echo esc_attr( $settings['badge_text_color'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Badge border radius (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[badge_border_radius]" value="<?php echo esc_attr( $settings['badge_border_radius'] ); ?>" min="0" max="20" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Color título', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[title_color]" value="<?php echo esc_attr( $settings['title_color'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Tamaño título (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[title_size]" value="<?php echo esc_attr( $settings['title_size'] ); ?>" min="12" max="30" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Caracteres máx. título', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[title_max_chars]" value="<?php echo esc_attr( $settings['title_max_chars'] ); ?>" min="50" max="300" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Color BPIN', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[bpin_color]" value="<?php echo esc_attr( $settings['bpin_color'] ); ?>"></td>
                        </tr>
                    </table>
                </div>

                <!-- Búsqueda y Modal -->
                <div class="sgr-admin-section">
                    <h2><?php esc_html_e( 'Búsqueda y Modal', 'sgr-suite' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Borde búsqueda', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[search_border_color]" value="<?php echo esc_attr( $settings['search_border_color'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Font size búsqueda (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[search_font_size]" value="<?php echo esc_attr( $settings['search_font_size'] ); ?>" min="14" max="36" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Fondo modal', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[modal_bg]" value="<?php echo esc_attr( $settings['modal_bg'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Borde modal', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[modal_border_color]" value="<?php echo esc_attr( $settings['modal_border_color'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Ancho máx. modal (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[modal_max_width]" value="<?php echo esc_attr( $settings['modal_max_width'] ); ?>" min="600" max="1400" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Color link', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[link_color]" value="<?php echo esc_attr( $settings['link_color'] ); ?>"></td>
                        </tr>
                    </table>
                </div>

                <!-- Stats Bar -->
                <div class="sgr-admin-section">
                    <h2><?php esc_html_e( 'Barra de Estadísticas', 'sgr-suite' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Color número', 'sgr-suite' ); ?></th>
                            <td><input type="color" name="sgr_suite_card_style[stat_number_color]" value="<?php echo esc_attr( $settings['stat_number_color'] ); ?>"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Tamaño número (px)', 'sgr-suite' ); ?></th>
                            <td><input type="number" name="sgr_suite_card_style[stat_number_size]" value="<?php echo esc_attr( $settings['stat_number_size'] ); ?>" min="18" max="60" class="small-text"></td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( esc_html__( 'Guardar Personalización', 'sgr-suite' ) ); ?>
            </div>
        </div>
    </form>
</div>
