<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMT_DB {

    public static function votes_table() {
        global $wpdb;
        return $wpdb->prefix . 'lmt_votes';
    }

    public static function passports_table() {
        global $wpdb;
        return $wpdb->prefix . 'lmt_passports';
    }

    public static function passport_visits_table() {
        global $wpdb;
        return $wpdb->prefix . 'lmt_passport_visits';
    }

    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $votes = self::votes_table();
        $sql_votes = "CREATE TABLE {$votes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            stand_id bigint(20) unsigned NOT NULL,
            email_hash char(64) NOT NULL,
            email varchar(190) NOT NULL,
            emoji varchar(10) NOT NULL,
            comprado tinyint(1) NOT NULL DEFAULT 0,
            comentario text NULL,
            created_at datetime NOT NULL,
            ip_hash char(64) NULL,
            PRIMARY KEY  (id),
            KEY stand_id (stand_id),
            KEY email_hash (email_hash),
            KEY created_at (created_at)
        ) {$charset};";
        dbDelta( $sql_votes );

        $passports = self::passports_table();
        $sql_pass = "CREATE TABLE {$passports} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email_hash char(64) NOT NULL,
            email varchar(190) NOT NULL,
            nombre varchar(190) NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email_hash (email_hash)
        ) {$charset};";
        dbDelta( $sql_pass );

        $visits = self::passport_visits_table();
        $sql_visits = "CREATE TABLE {$visits} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            passport_id bigint(20) unsigned NOT NULL,
            stand_id bigint(20) unsigned NOT NULL,
            visited_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY passport_stand (passport_id, stand_id),
            KEY stand_id (stand_id)
        ) {$charset};";
        dbDelta( $sql_visits );
    }

    public static function hash_email( $email ) {
        return hash( 'sha256', strtolower( trim( $email ) ) . wp_salt( 'auth' ) );
    }

    public static function ensure_passport( $email, $nombre = '' ) {
        global $wpdb;
        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) return 0;
        $hash = self::hash_email( $email );
        $table = self::passports_table();

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE email_hash = %s LIMIT 1", $hash ) );
        if ( $existing ) {
            if ( $nombre && empty( $existing->nombre ) ) {
                $wpdb->update( $table, [ 'nombre' => sanitize_text_field( $nombre ) ], [ 'id' => $existing->id ] );
            }
            return (int) $existing->id;
        }
        $wpdb->insert( $table, [
            'email_hash' => $hash,
            'email'      => $email,
            'nombre'     => sanitize_text_field( $nombre ),
            'created_at' => current_time( 'mysql' ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function record_vote( $stand_id, $email, $emoji, $comprado, $comentario, $nombre = '' ) {
        global $wpdb;
        $email = sanitize_email( $email );
        if ( ! is_email( $email ) || ! get_post( $stand_id ) ) {
            return new WP_Error( 'lmt_invalid', __( 'Datos inválidos.', 'la-mejor-taza' ) );
        }
        $allowed = [ 'bueno', 'regular', 'malo' ];
        if ( ! in_array( $emoji, $allowed, true ) ) {
            return new WP_Error( 'lmt_invalid_emoji', __( 'Calificación inválida.', 'la-mejor-taza' ) );
        }

        $hash    = self::hash_email( $email );
        $votes   = self::votes_table();
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$votes} WHERE stand_id = %d AND email_hash = %s LIMIT 1",
            $stand_id, $hash
        ) );
        if ( $existing ) {
            return new WP_Error( 'lmt_duplicate', __( 'Ya votaste por este stand.', 'la-mejor-taza' ) );
        }

        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
        $wpdb->insert( $votes, [
            'stand_id'   => $stand_id,
            'email_hash' => $hash,
            'email'      => $email,
            'emoji'      => $emoji,
            'comprado'   => $comprado ? 1 : 0,
            'comentario' => wp_kses_post( $comentario ),
            'created_at' => current_time( 'mysql' ),
            'ip_hash'    => $ip ? hash( 'sha256', $ip . wp_salt( 'auth' ) ) : null,
        ] );
        $vote_id = (int) $wpdb->insert_id;

        $passport_id = self::ensure_passport( $email, $nombre );
        if ( $passport_id ) {
            $wpdb->query( $wpdb->prepare(
                "INSERT IGNORE INTO " . self::passport_visits_table() .
                " (passport_id, stand_id, visited_at) VALUES (%d, %d, %s)",
                $passport_id, $stand_id, current_time( 'mysql' )
            ) );
        }

        self::recompute_stand_counts( $stand_id );
        return $vote_id;
    }

    public static function recompute_stand_counts( $stand_id ) {
        global $wpdb;
        $votes = self::votes_table();
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT emoji, COUNT(*) c FROM {$votes} WHERE stand_id = %d GROUP BY emoji",
            $stand_id
        ) );
        $counts = [ 'bueno' => 0, 'regular' => 0, 'malo' => 0 ];
        foreach ( $rows as $r ) {
            if ( isset( $counts[ $r->emoji ] ) ) {
                $counts[ $r->emoji ] = (int) $r->c;
            }
        }
        update_post_meta( $stand_id, '_lmt_votos', $counts );
        $total = array_sum( $counts );
        $score = $total ? ( $counts['bueno'] * 100 + $counts['regular'] * 50 ) / $total : 0;
        update_post_meta( $stand_id, '_lmt_score', round( $score, 2 ) );
        update_post_meta( $stand_id, '_lmt_total_votos', $total );
    }

    public static function get_stand_votes( $stand_id ) {
        $v = get_post_meta( $stand_id, '_lmt_votos', true );
        if ( ! is_array( $v ) ) {
            $v = [ 'bueno' => 0, 'regular' => 0, 'malo' => 0 ];
        }
        return wp_parse_args( $v, [ 'bueno' => 0, 'regular' => 0, 'malo' => 0 ] );
    }

    public static function recent_comments( $limit = 10, $stand_id = 0 ) {
        global $wpdb;
        $votes = self::votes_table();
        $where = "WHERE comentario <> ''";
        $args  = [];
        if ( $stand_id ) {
            $where .= ' AND stand_id = %d';
            $args[] = $stand_id;
        }
        $args[] = (int) $limit;
        $sql = "SELECT id, stand_id, email, emoji, comprado, comentario, created_at FROM {$votes} {$where} ORDER BY created_at DESC LIMIT %d";
        $prepared = $args ? $wpdb->prepare( $sql, $args ) : $sql;
        return $wpdb->get_results( $prepared );
    }

    public static function passport_by_email( $email ) {
        global $wpdb;
        $hash = self::hash_email( $email );
        $row  = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::passports_table() . " WHERE email_hash = %s",
            $hash
        ) );
        if ( ! $row ) return null;
        $visits = $wpdb->get_results( $wpdb->prepare(
            "SELECT stand_id, visited_at FROM " . self::passport_visits_table() . " WHERE passport_id = %d ORDER BY visited_at ASC",
            $row->id
        ) );
        $row->visits = $visits;
        return $row;
    }

    public static function masked_email( $email ) {
        $email = (string) $email;
        $parts = explode( '@', $email );
        if ( count( $parts ) !== 2 ) return $email;
        $name = $parts[0];
        if ( strlen( $name ) <= 1 ) {
            $masked = $name . '***';
        } else {
            $masked = substr( $name, 0, 1 ) . '***';
        }
        return $masked . '@' . $parts[1];
    }
}
