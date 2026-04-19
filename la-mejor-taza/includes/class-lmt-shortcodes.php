<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMT_Shortcodes {
    public static function init() {
        add_shortcode( 'lmt_dashboard', [ __CLASS__, 'dashboard' ] );
        add_shortcode( 'lmt_vote', [ __CLASS__, 'vote' ] );
        add_shortcode( 'lmt_passport', [ __CLASS__, 'passport' ] );
        add_shortcode( 'lmt_stand', [ __CLASS__, 'detail' ] );
    }

    private static function enqueue() {
        wp_enqueue_style( 'lmt-public' );
        wp_enqueue_script( 'lmt-public' );
    }

    public static function dashboard( $atts ) {
        self::enqueue();
        $stands = array_map( [ 'LMT_CPT', 'stand_to_array' ], LMT_CPT::get_stands() );
        ob_start();
        $template = LMT_PATH . 'templates/dashboard.php';
        $comentarios = LMT_DB::recent_comments( 8 );
        include $template;
        return ob_get_clean();
    }

    public static function vote( $atts ) {
        self::enqueue();
        $atts = shortcode_atts( [ 'stand' => 0 ], $atts, 'lmt_vote' );
        $stand_id = absint( $atts['stand'] );
        if ( ! $stand_id && isset( $_GET['stand'] ) ) {
            $stand_id = absint( $_GET['stand'] );
        }
        if ( ! $stand_id && isset( $_GET['lmt_stand'] ) ) {
            $stand_id = absint( $_GET['lmt_stand'] );
        }
        $post = $stand_id ? get_post( $stand_id ) : null;
        if ( ! $post || $post->post_type !== LMT_CPT::POST_TYPE ) {
            return '<div class="lmt-empty">' . esc_html__( 'Stand no encontrado. Escanea un código QR válido.', 'la-mejor-taza' ) . '</div>';
        }
        $stand = LMT_CPT::stand_to_array( $post );
        ob_start();
        include LMT_PATH . 'templates/vote.php';
        return ob_get_clean();
    }

    public static function passport( $atts ) {
        self::enqueue();
        ob_start();
        include LMT_PATH . 'templates/passport.php';
        return ob_get_clean();
    }

    public static function detail( $atts ) {
        self::enqueue();
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'lmt_stand' );
        $id = absint( $atts['id'] );
        if ( ! $id && isset( $_GET['stand'] ) ) {
            $id = absint( $_GET['stand'] );
        }
        $post = $id ? get_post( $id ) : null;
        if ( ! $post || $post->post_type !== LMT_CPT::POST_TYPE ) {
            return '<div class="lmt-empty">' . esc_html__( 'Stand no encontrado.', 'la-mejor-taza' ) . '</div>';
        }
        $stand   = LMT_CPT::stand_to_array( $post );
        $stands  = array_map( [ 'LMT_CPT', 'stand_to_array' ], LMT_CPT::get_stands() );
        $comments = LMT_DB::recent_comments( 6, $id );
        ob_start();
        include LMT_PATH . 'templates/detail.php';
        return ob_get_clean();
    }
}
