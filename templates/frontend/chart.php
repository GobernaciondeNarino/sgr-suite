<?php
/**
 * SGR Suite - Template Frontend de Gráficos
 *
 * Renderiza el contenedor del gráfico con su configuración
 * embebida en JSON para carga AJAX con D3Plus.
 *
 * @package SGR_Suite
 * @since   1.0.1
 * @var int    $chart_id   ID del gráfico
 * @var string $uid        ID único del contenedor
 * @var string $nonce      Nonce de seguridad
 * @var array  $config     Configuración del gráfico
 * @var string $extra_class Clases CSS adicionales
 * @var WP_Post $post      Post del gráfico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$height = absint( $config['chart_height'] ?? 400 );
?>

<div class="sgr-chart-wrapper<?php echo esc_attr( $extra_class ); ?>" id="<?php echo esc_attr( $uid ); ?>">

    <?php if ( ! empty( $config['show_toolbar'] ) ) : ?>
    <div class="sgr-chart-toolbar">
        <span class="sgr-chart-title"><?php echo esc_html( $post->post_title ); ?></span>
        <div class="sgr-chart-toolbar-actions">
            <button type="button" class="sgr-chart-toolbar-btn" data-action="fullscreen" title="<?php esc_attr_e( 'Pantalla completa', 'sgr-suite' ); ?>">&#x26F6;</button>
            <button type="button" class="sgr-chart-toolbar-btn" data-action="data" title="<?php esc_attr_e( 'Ver datos', 'sgr-suite' ); ?>">&#x1F4CB;</button>
            <button type="button" class="sgr-chart-toolbar-btn" data-action="download" title="<?php esc_attr_e( 'Descargar CSV', 'sgr-suite' ); ?>">&#x2B07;</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="sgr-chart-container" id="<?php echo esc_attr( $uid ); ?>-container"
         style="height: <?php echo esc_attr( $height ); ?>px; position: relative;">
        <div class="sgr-chart-loading">
            <div class="sgr-chart-spinner"></div>
            <p><?php esc_html_e( 'Cargando gráfico...', 'sgr-suite' ); ?></p>
        </div>
    </div>

    <script type="application/json" id="<?php echo esc_attr( $uid ); ?>-config">
    <?php
    echo wp_json_encode( [
        'chartId'  => $chart_id,
        'nonce'    => $nonce,
        'config'   => $config,
    ], JSON_HEX_TAG | JSON_HEX_AMP );
    ?>
    </script>

    <!-- Modal de Datos -->
    <div class="sgr-chart-data-modal" id="<?php echo esc_attr( $uid ); ?>-data-modal" style="display:none;">
        <div class="sgr-chart-data-modal-content">
            <div class="sgr-chart-data-modal-header">
                <h3><?php echo esc_html( $post->post_title ); ?> - <?php esc_html_e( 'Datos', 'sgr-suite' ); ?></h3>
                <button type="button" class="sgr-chart-data-modal-close">&times;</button>
            </div>
            <div class="sgr-chart-data-modal-body">
                <table class="sgr-chart-data-table">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
