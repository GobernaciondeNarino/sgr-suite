<?php
/**
 * Uninstall handler for La Mejor Taza.
 * Removes options, custom tables and CPT data — only when the user uninstalls
 * the plugin from the WordPress admin.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables.
foreach ( [ 'lmt_votes', 'lmt_passport_visits', 'lmt_passports' ] as $t ) {
    $table = $wpdb->prefix . $t;
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Delete CPT posts + meta.
$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'lmt_stand' ) );
foreach ( $ids as $id ) {
    wp_delete_post( (int) $id, true );
}

// Delete options.
delete_option( 'lmt_settings' );
