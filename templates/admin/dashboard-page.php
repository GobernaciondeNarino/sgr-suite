<?php
/**
 * SGR Suite - Dashboard Admin
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin      = sgr_suite();
$stats       = $plugin->database->get_stats();
$last_imp    = $plugin->database->get_last_import_date();
$chart_count = $plugin->visualizer->count_charts();
?>
<div class="wrap sgr-suite-admin">
    <h1><?php esc_html_e( 'SGR Suite - Dashboard', 'sgr-suite' ); ?></h1>
    <p class="description"><?php esc_html_e( 'Panel de control del Sistema General de Regalías de Nariño.', 'sgr-suite' ); ?></p>

    <div class="sgr-admin-cards">
        <div class="sgr-admin-card">
            <div class="sgr-admin-card-number"><?php echo esc_html( number_format( $stats['totalProyectos'], 0, ',', '.' ) ); ?></div>
            <div class="sgr-admin-card-label"><?php esc_html_e( 'Proyectos', 'sgr-suite' ); ?></div>
        </div>
        <div class="sgr-admin-card">
            <div class="sgr-admin-card-number"><?php echo esc_html( number_format( $stats['totalContratos'], 0, ',', '.' ) ); ?></div>
            <div class="sgr-admin-card-label"><?php esc_html_e( 'Contratos', 'sgr-suite' ); ?></div>
        </div>
        <div class="sgr-admin-card">
            <div class="sgr-admin-card-number">$ <?php echo esc_html( number_format( $stats['totalValor'], 2, ',', '.' ) ); ?></div>
            <div class="sgr-admin-card-label"><?php esc_html_e( 'Valor Total', 'sgr-suite' ); ?></div>
        </div>
        <div class="sgr-admin-card">
            <div class="sgr-admin-card-number"><?php echo esc_html( number_format( $stats['totalMunicipios'], 0, ',', '.' ) ); ?></div>
            <div class="sgr-admin-card-label"><?php esc_html_e( 'Municipios', 'sgr-suite' ); ?></div>
        </div>
        <div class="sgr-admin-card">
            <div class="sgr-admin-card-number"><?php echo esc_html( number_format( $stats['totalMetas'], 0, ',', '.' ) ); ?></div>
            <div class="sgr-admin-card-label"><?php esc_html_e( 'Metas Registradas', 'sgr-suite' ); ?></div>
        </div>
        <div class="sgr-admin-card">
            <div class="sgr-admin-card-number"><?php echo esc_html( $chart_count ); ?></div>
            <div class="sgr-admin-card-label"><?php esc_html_e( 'Gráficos Creados', 'sgr-suite' ); ?></div>
        </div>
    </div>

    <div class="sgr-admin-section">
        <h2><?php esc_html_e( 'Estado del Sistema', 'sgr-suite' ); ?></h2>
        <table class="widefat fixed striped">
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Versión del Plugin', 'sgr-suite' ); ?></strong></td>
                    <td><?php echo esc_html( SGR_SUITE_VERSION ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'PHP', 'sgr-suite' ); ?></strong></td>
                    <td><?php echo esc_html( PHP_VERSION ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'WordPress', 'sgr-suite' ); ?></strong></td>
                    <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Última Importación', 'sgr-suite' ); ?></strong></td>
                    <td>
                        <?php
                        if ( $last_imp ) {
                            echo esc_html( wp_date( 'd/m/Y H:i:s', strtotime( $last_imp ) ) );
                        } else {
                            esc_html_e( 'Sin importaciones', 'sgr-suite' );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'API Endpoint', 'sgr-suite' ); ?></strong></td>
                    <td><code><?php echo esc_html( SGR_SUITE_API_URL ); ?></code></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Cron Programado', 'sgr-suite' ); ?></strong></td>
                    <td>
                        <?php
                        $next = wp_next_scheduled( 'sgr_suite_scheduled_import' );
                        if ( $next ) {
                            echo esc_html( wp_date( 'd/m/Y H:i:s', $next ) );
                        } else {
                            esc_html_e( 'No programado', 'sgr-suite' );
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="sgr-admin-section">
        <h2><?php esc_html_e( 'Acciones Rápidas', 'sgr-suite' ); ?></h2>
        <p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=sgr-suite-import' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Importar Datos', 'sgr-suite' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=sgr-suite-records' ) ); ?>" class="button">
                <?php esc_html_e( 'Ver Proyectos', 'sgr-suite' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=sgr-suite-logs' ) ); ?>" class="button">
                <?php esc_html_e( 'Ver Registros', 'sgr-suite' ); ?>
            </a>
        </p>
    </div>

    <div class="sgr-admin-section">
        <h2><?php esc_html_e( 'Shortcodes Disponibles', 'sgr-suite' ); ?></h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Shortcode', 'sgr-suite' ); ?></th>
                    <th><?php esc_html_e( 'Descripción', 'sgr-suite' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[sgr_proyectos]</code></td>
                    <td><?php esc_html_e( 'Muestra la grilla de proyectos SGR con filtros y modal de detalle.', 'sgr-suite' ); ?></td>
                </tr>
                <tr>
                    <td><code>[regalias_grid_visualizador]</code></td>
                    <td><?php esc_html_e( 'Alias compatible con el visualizador anterior.', 'sgr-suite' ); ?></td>
                </tr>
                <tr>
                    <td><code>[sgr_chart id="X"]</code></td>
                    <td><?php esc_html_e( 'Muestra un gráfico D3Plus configurado desde el admin. Parámetros opcionales: height, class.', 'sgr-suite' ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="sgr-admin-section">
        <h2><?php esc_html_e( 'REST API Endpoints', 'sgr-suite' ); ?></h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Método', 'sgr-suite' ); ?></th>
                    <th><?php esc_html_e( 'Endpoint', 'sgr-suite' ); ?></th>
                    <th><?php esc_html_e( 'Descripción', 'sgr-suite' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/sgr-suite/v1/proyectos</code></td>
                    <td><?php esc_html_e( 'Lista paginada de proyectos con filtros.', 'sgr-suite' ); ?></td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/sgr-suite/v1/proyectos/{bpin}</code></td>
                    <td><?php esc_html_e( 'Detalle de un proyecto por BPIN.', 'sgr-suite' ); ?></td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/sgr-suite/v1/stats</code></td>
                    <td><?php esc_html_e( 'Estadísticas generales.', 'sgr-suite' ); ?></td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/sgr-suite/v1/proyectos/csv</code></td>
                    <td><?php esc_html_e( 'Exportar proyectos en CSV.', 'sgr-suite' ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
