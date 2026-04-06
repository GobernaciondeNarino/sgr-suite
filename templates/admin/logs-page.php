<?php
/**
 * SGR Suite - Página de Logs
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logger = sgr_suite()->logger;
$lines  = $logger->read_log( 300 );
?>
<div class="wrap sgr-suite-admin">
    <h1><?php esc_html_e( 'Registros del Sistema', 'sgr-suite' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Últimas 300 entradas del registro de actividad del plugin.', 'sgr-suite' ); ?>
    </p>

    <div class="sgr-admin-section">
        <div class="sgr-log-viewer">
            <?php if ( empty( $lines ) ) : ?>
                <p style="text-align:center; color: #666; padding: 40px;">
                    <?php esc_html_e( 'No hay registros disponibles.', 'sgr-suite' ); ?>
                </p>
            <?php else : ?>
                <pre class="sgr-log-content"><?php
                    foreach ( $lines as $line ) {
                        $escaped = esc_html( $line );
                        // Resaltar niveles
                        $escaped = str_replace( '[ERROR]', '<span class="sgr-log-error">[ERROR]</span>', $escaped );
                        $escaped = str_replace( '[WARNING]', '<span class="sgr-log-warning">[WARNING]</span>', $escaped );
                        $escaped = str_replace( '[INFO]', '<span class="sgr-log-info">[INFO]</span>', $escaped );
                        $escaped = str_replace( '[DEBUG]', '<span class="sgr-log-debug">[DEBUG]</span>', $escaped );
                        echo $escaped . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above
                    }
                ?></pre>
            <?php endif; ?>
        </div>
    </div>
</div>
