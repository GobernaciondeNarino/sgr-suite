<?php
/**
 * SGR Suite - Página de Proyectos
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin = sgr_suite();

// Parámetros de consulta
$page       = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page   = 20;
$buscar     = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
$dep_filter = sanitize_text_field( wp_unslash( $_GET['dependencia'] ?? '' ) );
$orderby    = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'nombre_proyecto' ) );
$order      = sanitize_text_field( wp_unslash( $_GET['order'] ?? 'ASC' ) );

$args = [
    'limite'      => $per_page,
    'offset'      => ( $page - 1 ) * $per_page,
    'buscar'      => $buscar,
    'dependencia' => $dep_filter,
    'orderby'     => $orderby,
    'order'       => $order,
];

$proyectos    = $plugin->database->get_proyectos( $args );
$total        = $plugin->database->count_proyectos( $args );
$total_pages  = (int) ceil( $total / $per_page );
$stats        = $plugin->database->get_stats();
$dependencias = $stats['dependencias'];
?>
<div class="wrap sgr-suite-admin">
    <h1><?php esc_html_e( 'Proyectos SGR', 'sgr-suite' ); ?></h1>

    <form method="get" class="sgr-admin-filters-form">
        <input type="hidden" name="page" value="sgr-suite-records">
        <div class="sgr-admin-filters-row">
            <div>
                <input type="search" name="s" value="<?php echo esc_attr( $buscar ); ?>"
                       placeholder="<?php esc_attr_e( 'Buscar por nombre o BPIN...', 'sgr-suite' ); ?>"
                       class="regular-text">
            </div>
            <div>
                <select name="dependencia">
                    <option value=""><?php esc_html_e( 'Todas las dependencias', 'sgr-suite' ); ?></option>
                    <?php foreach ( $dependencias as $dep ) : ?>
                        <option value="<?php echo esc_attr( $dep ); ?>" <?php selected( $dep_filter, $dep ); ?>>
                            <?php echo esc_html( $dep ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <?php submit_button( esc_html__( 'Filtrar', 'sgr-suite' ), 'secondary', '', false ); ?>
            </div>
        </div>
    </form>

    <p class="description">
        <?php
        printf(
            /* translators: %d: total results count */
            esc_html__( 'Mostrando %1$d de %2$d proyectos.', 'sgr-suite' ),
            count( $proyectos ),
            $total
        );
        ?>
    </p>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th style="width:120px;"><?php esc_html_e( 'BPIN', 'sgr-suite' ); ?></th>
                <th><?php esc_html_e( 'Nombre del Proyecto', 'sgr-suite' ); ?></th>
                <th style="width:150px;"><?php esc_html_e( 'Valor', 'sgr-suite' ); ?></th>
                <th style="width:200px;"><?php esc_html_e( 'Dependencia', 'sgr-suite' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Contratos', 'sgr-suite' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Metas', 'sgr-suite' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $proyectos ) ) : ?>
                <tr>
                    <td colspan="6" style="text-align:center;">
                        <?php esc_html_e( 'No se encontraron proyectos.', 'sgr-suite' ); ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ( $proyectos as $p ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $p['numero_proyecto'] ); ?></strong></td>
                        <td><?php echo esc_html( mb_substr( $p['nombre_proyecto'], 0, 150 ) ); ?></td>
                        <td>$ <?php echo esc_html( number_format( (float) $p['valor_proyecto'], 2, ',', '.' ) ); ?></td>
                        <td><?php echo esc_html( $p['dependencia_proyecto'] ); ?></td>
                        <td style="text-align:center;"><?php echo esc_html( $p['total_contratos'] ); ?></td>
                        <td style="text-align:center;"><?php echo esc_html( count( $p['metas'] ?? [] ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $pagination_args = [
                    'base'    => add_query_arg( 'paged', '%#%' ),
                    'format'  => '',
                    'current' => $page,
                    'total'   => $total_pages,
                    'type'    => 'plain',
                ];
                echo wp_kses_post( paginate_links( $pagination_args ) );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
