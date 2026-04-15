<?php
/**
 * SGR Suite - Template Frontend de Gráficos v2.1.0
 *
 * Toolbar con estilo secop-suite: Detalle, Compartir, Datos, Imagen, Descarga.
 *
 * @package SGR_Suite
 * @since   2.1.0
 * @var int    $chart_id    ID del gráfico
 * @var string $uid         ID único del contenedor
 * @var string $nonce       Nonce de seguridad
 * @var array  $config      Configuración del gráfico
 * @var string $extra_class Clases CSS adicionales
 * @var WP_Post $post       Post del gráfico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$height = absint( $config['chart_height'] ?? 400 );
?>

<div class="sgr-chart-wrapper<?php echo ! empty( $extra_class ) ? ' ' . esc_attr( trim( (string) $extra_class ) ) : ''; ?>" id="<?php echo esc_attr( $uid ); ?>">

    <?php if ( ! empty( $config['show_toolbar'] ) ) : ?>
    <div class="sgr-chart-toolbar">
        <span class="sgr-chart-title"><?php echo esc_html( $post->post_title ); ?></span>
        <div class="sgr-chart-toolbar-actions">
            <button type="button" class="sgr-chart-toolbar-btn" data-action="detail">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span><?php esc_html_e( 'Detalle', 'sgr-suite' ); ?></span>
            </button>
            <button type="button" class="sgr-chart-toolbar-btn" data-action="share">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                <span><?php esc_html_e( 'Compartir', 'sgr-suite' ); ?></span>
            </button>
            <button type="button" class="sgr-chart-toolbar-btn" data-action="data">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span><?php esc_html_e( 'Datos', 'sgr-suite' ); ?></span>
            </button>
            <button type="button" class="sgr-chart-toolbar-btn" data-action="image">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span><?php esc_html_e( 'Imagen', 'sgr-suite' ); ?></span>
            </button>
            <button type="button" class="sgr-chart-toolbar-btn" data-action="download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span><?php esc_html_e( 'Descarga', 'sgr-suite' ); ?></span>
            </button>
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
        'title'    => $post->post_title,
    ], JSON_HEX_TAG | JSON_HEX_AMP );
    ?>
    </script>

    <!-- Modal de Detalle -->
    <div class="sgr-chart-data-modal" id="<?php echo esc_attr( $uid ); ?>-detail-modal">
        <div class="sgr-chart-data-modal-content" style="max-width:500px;">
            <div class="sgr-chart-data-modal-header">
                <h3><?php esc_html_e( 'Detalle del Gráfico', 'sgr-suite' ); ?></h3>
                <button type="button" class="sgr-chart-data-modal-close">&times;</button>
            </div>
            <div class="sgr-chart-data-modal-body" id="<?php echo esc_attr( $uid ); ?>-detail-body">
                <table class="sgr-chart-data-table" style="font-size:14px;">
                    <tr><td><strong><?php esc_html_e( 'Título', 'sgr-suite' ); ?></strong></td><td><?php echo esc_html( $post->post_title ); ?></td></tr>
                    <tr><td><strong><?php esc_html_e( 'Tipo', 'sgr-suite' ); ?></strong></td><td><?php echo esc_html( $config['chart_type'] ?? 'bar' ); ?></td></tr>
                    <tr><td><strong><?php esc_html_e( 'Vista', 'sgr-suite' ); ?></strong></td><td><?php echo esc_html( $config['data_view'] ?? '' ); ?></td></tr>
                    <tr><td><strong><?php esc_html_e( 'Formato', 'sgr-suite' ); ?></strong></td><td><?php echo esc_html( $config['number_format'] ?? 'colombiano' ); ?></td></tr>
                    <tr><td><strong><?php esc_html_e( 'Fuente', 'sgr-suite' ); ?></strong></td><td>SGR - Gobernación de Nariño</td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Compartir -->
    <div class="sgr-chart-data-modal" id="<?php echo esc_attr( $uid ); ?>-share-modal">
        <div class="sgr-chart-data-modal-content" style="max-width:400px;">
            <div class="sgr-chart-data-modal-header">
                <h3><?php esc_html_e( 'Compartir', 'sgr-suite' ); ?></h3>
                <button type="button" class="sgr-chart-data-modal-close">&times;</button>
            </div>
            <div class="sgr-chart-data-modal-body" style="text-align:center;padding:25px;">
                <div class="sgr-share-buttons">
                    <a href="#" class="sgr-share-btn sgr-share-fb" data-network="facebook" title="Facebook">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="#" class="sgr-share-btn sgr-share-tw" data-network="twitter" title="X/Twitter">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="sgr-share-btn sgr-share-li" data-network="linkedin" title="LinkedIn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <a href="#" class="sgr-share-btn sgr-share-wa" data-network="whatsapp" title="WhatsApp">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.609-1.472A11.94 11.94 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.171 0-4.178-.7-5.813-1.888l-.417-.311-2.733.873.727-2.649-.342-.432A9.71 9.71 0 0 1 2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75z"/></svg>
                    </a>
                </div>
                <div style="margin-top:15px;">
                    <button type="button" class="sgr-chart-toolbar-btn" data-action="copy-link" style="width:100%;justify-content:center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        <span><?php esc_html_e( 'Copiar enlace', 'sgr-suite' ); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Datos -->
    <div class="sgr-chart-data-modal" id="<?php echo esc_attr( $uid ); ?>-data-modal">
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
