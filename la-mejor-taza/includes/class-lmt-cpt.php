<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMT_CPT {
    const POST_TYPE = 'lmt_stand';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'meta_boxes' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save_meta' ], 10, 2 );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'               => __( 'Stands', 'la-mejor-taza' ),
                'singular_name'      => __( 'Stand', 'la-mejor-taza' ),
                'add_new'            => __( 'Registrar stand', 'la-mejor-taza' ),
                'add_new_item'       => __( 'Nuevo stand', 'la-mejor-taza' ),
                'edit_item'          => __( 'Editar stand', 'la-mejor-taza' ),
                'new_item'           => __( 'Nuevo stand', 'la-mejor-taza' ),
                'view_item'          => __( 'Ver stand', 'la-mejor-taza' ),
                'search_items'       => __( 'Buscar stands', 'la-mejor-taza' ),
                'menu_name'          => __( 'La Mejor Taza', 'la-mejor-taza' ),
            ],
            'public'              => true,
            'show_in_rest'        => true,
            'has_archive'         => false,
            'menu_icon'           => 'dashicons-coffee',
            'menu_position'       => 26,
            'supports'            => [ 'title', 'editor', 'thumbnail' ],
            'rewrite'             => [ 'slug' => 'stand', 'with_front' => false ],
            'capability_type'     => 'post',
        ] );
    }

    public static function meta_boxes() {
        add_meta_box(
            'lmt_stand_meta',
            __( 'Datos del stand', 'la-mejor-taza' ),
            [ __CLASS__, 'render_meta_box' ],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'lmt_save_stand', 'lmt_stand_nonce' );
        $municipio  = get_post_meta( $post->ID, '_lmt_municipio', true );
        $region     = get_post_meta( $post->ID, '_lmt_region', true );
        $direccion  = get_post_meta( $post->ID, '_lmt_direccion', true );
        $correo     = get_post_meta( $post->ID, '_lmt_correo', true );
        $color      = get_post_meta( $post->ID, '_lmt_color', true ) ?: 'oklch(0.45 0.1 40)';
        $coords_x   = get_post_meta( $post->ID, '_lmt_coord_x', true );
        $coords_y   = get_post_meta( $post->ID, '_lmt_coord_y', true );
        $colors = [
            'oklch(0.42 0.09 50)', 'oklch(0.55 0.13 30)', 'oklch(0.5 0.08 145)',
            'oklch(0.48 0.1 60)', 'oklch(0.5 0.1 200)', 'oklch(0.4 0.08 120)',
            'oklch(0.55 0.12 20)', 'oklch(0.45 0.11 300)',
        ];
        ?>
        <style>
            .lmt-meta-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 18px 24px; }
            .lmt-meta-grid label { display:block; font-weight:600; margin-bottom:4px; }
            .lmt-meta-grid input[type=text], .lmt-meta-grid input[type=email], .lmt-meta-grid input[type=number] { width:100%; }
            .lmt-color-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:4px; }
            .lmt-color-row label { width:30px; height:30px; border-radius:50%; border:2px solid transparent; cursor:pointer; outline:1px solid #ccc; outline-offset:2px; display:inline-block; }
            .lmt-color-row input { display:none; }
            .lmt-color-row input:checked + span { box-shadow: inset 0 0 0 3px #fff, 0 0 0 2px #1d2327; }
            .lmt-color-row span { width:100%; height:100%; display:block; border-radius:50%; }
        </style>
        <div class="lmt-meta-grid">
            <div>
                <label for="lmt_municipio"><?php esc_html_e( 'Municipio', 'la-mejor-taza' ); ?></label>
                <input type="text" id="lmt_municipio" name="lmt_municipio" value="<?php echo esc_attr( $municipio ); ?>" placeholder="La Unión"/>
            </div>
            <div>
                <label for="lmt_region"><?php esc_html_e( 'Región', 'la-mejor-taza' ); ?></label>
                <input type="text" id="lmt_region" name="lmt_region" value="<?php echo esc_attr( $region ); ?>" placeholder="Norte de Nariño"/>
            </div>
            <div style="grid-column:1 / -1;">
                <label for="lmt_direccion"><?php esc_html_e( 'Dirección', 'la-mejor-taza' ); ?></label>
                <input type="text" id="lmt_direccion" name="lmt_direccion" value="<?php echo esc_attr( $direccion ); ?>"/>
            </div>
            <div>
                <label for="lmt_correo"><?php esc_html_e( 'Correo de contacto', 'la-mejor-taza' ); ?></label>
                <input type="email" id="lmt_correo" name="lmt_correo" value="<?php echo esc_attr( $correo ); ?>"/>
            </div>
            <div>
                <label><?php esc_html_e( 'Color del sello', 'la-mejor-taza' ); ?></label>
                <div class="lmt-color-row">
                    <?php foreach ( $colors as $c ) : ?>
                        <label>
                            <input type="radio" name="lmt_color" value="<?php echo esc_attr( $c ); ?>" <?php checked( $color, $c ); ?>/>
                            <span style="background:<?php echo esc_attr( $c ); ?>;"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label for="lmt_coord_x"><?php esc_html_e( 'Mapa · X (0-1)', 'la-mejor-taza' ); ?></label>
                <input type="number" step="0.01" min="0" max="1" id="lmt_coord_x" name="lmt_coord_x" value="<?php echo esc_attr( $coords_x ); ?>"/>
            </div>
            <div>
                <label for="lmt_coord_y"><?php esc_html_e( 'Mapa · Y (0-1)', 'la-mejor-taza' ); ?></label>
                <input type="number" step="0.01" min="0" max="1" id="lmt_coord_y" name="lmt_coord_y" value="<?php echo esc_attr( $coords_y ); ?>"/>
            </div>
        </div>
        <?php
    }

    public static function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['lmt_stand_nonce'] ) || ! wp_verify_nonce( $_POST['lmt_stand_nonce'], 'lmt_save_stand' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = [
            '_lmt_municipio' => 'sanitize_text_field',
            '_lmt_region'    => 'sanitize_text_field',
            '_lmt_direccion' => 'sanitize_text_field',
            '_lmt_correo'    => 'sanitize_email',
            '_lmt_color'     => 'sanitize_text_field',
            '_lmt_coord_x'   => 'floatval',
            '_lmt_coord_y'   => 'floatval',
        ];
        foreach ( $fields as $meta => $sanitizer ) {
            $key = ltrim( $meta, '_' );
            if ( isset( $_POST[ $key ] ) ) {
                $val = call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) );
                update_post_meta( $post_id, $meta, $val );
            }
        }
        if ( ! get_post_meta( $post_id, '_lmt_votos', true ) ) {
            update_post_meta( $post_id, '_lmt_votos', [ 'bueno' => 0, 'regular' => 0, 'malo' => 0 ] );
            update_post_meta( $post_id, '_lmt_score', 0 );
            update_post_meta( $post_id, '_lmt_total_votos', 0 );
        }
    }

    public static function columns( $cols ) {
        $new = [];
        foreach ( $cols as $k => $v ) {
            $new[ $k ] = $v;
            if ( $k === 'title' ) {
                $new['lmt_municipio'] = __( 'Municipio', 'la-mejor-taza' );
                $new['lmt_score']     = __( 'Calificación', 'la-mejor-taza' );
                $new['lmt_total']     = __( 'Votos', 'la-mejor-taza' );
                $new['lmt_qr']        = __( 'QR', 'la-mejor-taza' );
            }
        }
        return $new;
    }

    public static function column_content( $col, $post_id ) {
        switch ( $col ) {
            case 'lmt_municipio':
                echo esc_html( get_post_meta( $post_id, '_lmt_municipio', true ) );
                break;
            case 'lmt_score':
                $score = (float) get_post_meta( $post_id, '_lmt_score', true );
                echo esc_html( number_format_i18n( $score, 0 ) ) . ' / 100';
                break;
            case 'lmt_total':
                echo esc_html( (int) get_post_meta( $post_id, '_lmt_total_votos', true ) );
                break;
            case 'lmt_qr':
                $url = admin_url( 'admin.php?page=lmt-qr&stand=' . $post_id );
                printf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Imprimir', 'la-mejor-taza' ) );
                break;
        }
    }

    public static function get_stands( $args = [] ) {
        $defaults = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_lmt_score',
            'order'          => 'DESC',
        ];
        return get_posts( wp_parse_args( $args, $defaults ) );
    }

    public static function stand_to_array( $post ) {
        $votos = LMT_DB::get_stand_votes( $post->ID );
        return [
            'id'          => $post->ID,
            'slug'        => $post->post_name,
            'nombre'      => get_the_title( $post ),
            'descripcion' => wp_strip_all_tags( $post->post_content ),
            'municipio'   => get_post_meta( $post->ID, '_lmt_municipio', true ),
            'region'      => get_post_meta( $post->ID, '_lmt_region', true ),
            'direccion'   => get_post_meta( $post->ID, '_lmt_direccion', true ),
            'correo'      => get_post_meta( $post->ID, '_lmt_correo', true ),
            'color'       => get_post_meta( $post->ID, '_lmt_color', true ) ?: 'oklch(0.45 0.1 40)',
            'logo'        => get_the_post_thumbnail_url( $post->ID, 'medium' ),
            'coords'      => [
                'x' => (float) get_post_meta( $post->ID, '_lmt_coord_x', true ),
                'y' => (float) get_post_meta( $post->ID, '_lmt_coord_y', true ),
            ],
            'votos'       => $votos,
            'total'       => array_sum( $votos ),
            'score'       => (float) get_post_meta( $post->ID, '_lmt_score', true ),
            'vote_url'    => self::vote_url( $post->ID ),
        ];
    }

    public static function vote_url( $stand_id ) {
        $settings = get_option( 'lmt_settings', [] );
        $page_id  = ! empty( $settings['vote_page'] ) ? (int) $settings['vote_page'] : 0;
        if ( $page_id ) {
            return add_query_arg( 'stand', $stand_id, get_permalink( $page_id ) );
        }
        return add_query_arg( 'lmt_stand', $stand_id, home_url( '/' ) );
    }
}
