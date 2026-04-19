<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMT_REST {
    const NS = 'lmt/v1';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
    }

    public static function routes() {
        register_rest_route( self::NS, '/vote', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ __CLASS__, 'create_vote' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'stand_id'   => [ 'required' => true, 'type' => 'integer' ],
                'email'      => [ 'required' => true, 'type' => 'string' ],
                'emoji'      => [ 'required' => true, 'type' => 'string' ],
                'comprado'   => [ 'required' => false, 'type' => 'boolean' ],
                'comentario' => [ 'required' => false, 'type' => 'string' ],
                'nombre'     => [ 'required' => false, 'type' => 'string' ],
                '_wpnonce'   => [ 'required' => true, 'type' => 'string' ],
            ],
        ] );

        register_rest_route( self::NS, '/stands', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'list_stands' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NS, '/live', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'live_feed' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NS, '/passport', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'get_passport' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'email' => [ 'required' => true, 'type' => 'string' ],
            ],
        ] );
    }

    public static function create_vote( WP_REST_Request $req ) {
        $nonce = $req->get_param( '_wpnonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'lmt_nonce', __( 'Sesión expirada, recarga la página.', 'la-mejor-taza' ), [ 'status' => 403 ] );
        }
        $stand_id = (int) $req->get_param( 'stand_id' );
        $result = LMT_DB::record_vote(
            $stand_id,
            (string) $req->get_param( 'email' ),
            (string) $req->get_param( 'emoji' ),
            (bool)   $req->get_param( 'comprado' ),
            (string) $req->get_param( 'comentario' ),
            (string) $req->get_param( 'nombre' )
        );
        if ( is_wp_error( $result ) ) {
            $result->add_data( [ 'status' => 400 ] );
            return $result;
        }

        $stand = get_post( $stand_id );
        $payload = [
            'ok'       => true,
            'vote_id'  => (int) $result,
            'stand'    => LMT_CPT::stand_to_array( $stand ),
        ];
        return rest_ensure_response( $payload );
    }

    public static function list_stands() {
        $stands = array_map( [ 'LMT_CPT', 'stand_to_array' ], LMT_CPT::get_stands() );
        return rest_ensure_response( $stands );
    }

    public static function live_feed( WP_REST_Request $req ) {
        $limit = max( 1, min( 50, (int) ( $req->get_param( 'limit' ) ?: 8 ) ) );
        $rows  = LMT_DB::recent_comments( $limit );
        $out   = [];
        foreach ( $rows as $r ) {
            $out[] = [
                'stand_id'   => (int) $r->stand_id,
                'stand'      => get_the_title( $r->stand_id ),
                'emoji'      => $r->emoji,
                'comentario' => $r->comentario,
                'comprado'   => (bool) $r->comprado,
                'autor'      => LMT_DB::masked_email( $r->email ),
                'hora'       => human_time_diff( strtotime( $r->created_at ), current_time( 'timestamp' ) ),
            ];
        }
        return rest_ensure_response( $out );
    }

    public static function get_passport( WP_REST_Request $req ) {
        $email = sanitize_email( (string) $req->get_param( 'email' ) );
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'lmt_email', __( 'Correo inválido.', 'la-mejor-taza' ), [ 'status' => 400 ] );
        }
        $p = LMT_DB::passport_by_email( $email );
        if ( ! $p ) {
            return new WP_Error( 'lmt_not_found', __( 'No hay pasaporte para ese correo.', 'la-mejor-taza' ), [ 'status' => 404 ] );
        }
        $visited = [];
        foreach ( $p->visits as $v ) {
            $post = get_post( $v->stand_id );
            if ( $post ) {
                $visited[] = LMT_CPT::stand_to_array( $post ) + [ 'visited_at' => $v->visited_at ];
            }
        }
        return rest_ensure_response( [
            'nombre'  => $p->nombre,
            'correo'  => LMT_DB::masked_email( $p->email ),
            'inicio'  => mysql2date( get_option( 'date_format' ), $p->created_at ),
            'visitados' => $visited,
            'total_stands' => count( LMT_CPT::get_stands() ),
        ] );
    }
}
