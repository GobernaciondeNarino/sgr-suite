<?php
/**
 * SGR Suite - Página de Importación
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$stats = sgr_suite()->database->get_stats();
?>
<div class="wrap sgr-suite-admin">
    <h1><?php esc_html_e( 'Importar Datos SGR', 'sgr-suite' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Importa datos de proyectos desde la API del Sistema General de Regalías.', 'sgr-suite' ); ?>
    </p>

    <div class="sgr-admin-section">
        <h2><?php esc_html_e( 'Estado Actual', 'sgr-suite' ); ?></h2>
        <p>
            <?php
            printf(
                /* translators: %s: number of projects */
                esc_html__( 'Proyectos en base de datos: %s', 'sgr-suite' ),
                '<strong>' . esc_html( number_format( $stats['totalProyectos'], 0, ',', '.' ) ) . '</strong>'
            );
            ?>
        </p>
    </div>

    <div class="sgr-admin-section">
        <h2><?php esc_html_e( 'Importar desde API', 'sgr-suite' ); ?></h2>
        <p><?php esc_html_e( 'Se consultará la API y se actualizarán todos los proyectos y sus relaciones.', 'sgr-suite' ); ?></p>

        <div id="sgr-import-controls">
            <button type="button" id="sgr-btn-import" class="button button-primary button-hero">
                <?php esc_html_e( 'Iniciar Importación', 'sgr-suite' ); ?>
            </button>
            <button type="button" id="sgr-btn-cancel" class="button button-secondary" style="display:none;">
                <?php esc_html_e( 'Cancelar', 'sgr-suite' ); ?>
            </button>
        </div>

        <div id="sgr-import-progress" style="display:none; margin-top: 20px;">
            <div class="sgr-progress-bar-container">
                <div class="sgr-progress-bar" id="sgr-progress-bar" style="width: 0%;"></div>
            </div>
            <p id="sgr-progress-text" class="description"></p>
        </div>

        <div id="sgr-import-result" style="display:none; margin-top: 20px;"></div>
    </div>

    <div class="sgr-admin-section sgr-danger-zone">
        <h2><?php esc_html_e( 'Zona de Peligro', 'sgr-suite' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Eliminar todos los datos importados. Esta acción no se puede deshacer.', 'sgr-suite' ); ?>
        </p>
        <button type="button" id="sgr-btn-truncate" class="button sgr-btn-danger">
            <?php esc_html_e( 'Eliminar Todos los Datos', 'sgr-suite' ); ?>
        </button>
    </div>
</div>
