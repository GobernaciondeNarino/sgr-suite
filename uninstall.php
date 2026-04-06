<?php
/**
 * SGR Suite - Desinstalación
 *
 * Se ejecuta cuando el plugin es eliminado desde WordPress.
 * Limpia todas las tablas, opciones y transients creados.
 *
 * @package SGR_Suite
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Eliminar tablas (orden inverso por dependencias FK)
$prefix = $wpdb->prefix . 'sgr_';
$tables = [ 'imagenes', 'municipios', 'metas', 'contratos', 'proyectos' ];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// 2. Eliminar opciones del plugin
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'sgr\_suite\_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// 3. Eliminar transients
delete_transient( 'sgr_suite_import_progress' );
delete_transient( 'sgr_suite_import_lock' );

// 4. Limpiar cron
$timestamp = wp_next_scheduled( 'sgr_suite_scheduled_import' );
if ( $timestamp ) {
    wp_unschedule_event( $timestamp, 'sgr_suite_scheduled_import' );
}

// 5. Eliminar directorio de logs
$log_dir = plugin_dir_path( __FILE__ ) . 'logs/';
if ( is_dir( $log_dir ) ) {
    $files = glob( $log_dir . '*' );
    if ( $files ) {
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                wp_delete_file( $file );
            }
        }
    }
    rmdir( $log_dir );
}
