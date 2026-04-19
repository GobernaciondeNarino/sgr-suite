<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMT_Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menus' ] );
        add_action( 'admin_init', [ __CLASS__, 'maybe_save_settings' ] );
    }

    public static function menus() {
        add_submenu_page(
            'edit.php?post_type=' . LMT_CPT::POST_TYPE,
            __( 'Códigos QR', 'la-mejor-taza' ),
            __( 'Códigos QR', 'la-mejor-taza' ),
            'edit_posts',
            'lmt-qr',
            [ __CLASS__, 'render_qr' ]
        );
        add_submenu_page(
            'edit.php?post_type=' . LMT_CPT::POST_TYPE,
            __( 'Actividad en vivo', 'la-mejor-taza' ),
            __( 'Actividad en vivo', 'la-mejor-taza' ),
            'edit_posts',
            'lmt-live',
            [ __CLASS__, 'render_live' ]
        );
        add_submenu_page(
            'edit.php?post_type=' . LMT_CPT::POST_TYPE,
            __( 'Configuración', 'la-mejor-taza' ),
            __( 'Configuración', 'la-mejor-taza' ),
            'manage_options',
            'lmt-settings',
            [ __CLASS__, 'render_settings' ]
        );
    }

    public static function maybe_save_settings() {
        if ( ! isset( $_POST['lmt_settings_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['lmt_settings_nonce'], 'lmt_save_settings' ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $current = get_option( 'lmt_settings', [] );
        $fields  = [ 'festival_name', 'festival_dates', 'festival_city', 'palette', 'organizer', 'organizer_mail', 'vote_page', 'passport_page', 'dashboard_page' ];
        foreach ( $fields as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                $val = wp_unslash( $_POST[ $f ] );
                $current[ $f ] = is_numeric( $val ) ? (int) $val : sanitize_text_field( $val );
            }
        }
        update_option( 'lmt_settings', $current );
        add_settings_error( 'lmt_settings', 'lmt_saved', __( 'Configuración guardada.', 'la-mejor-taza' ), 'updated' );
    }

    public static function render_qr() {
        $stands = LMT_CPT::get_stands( [ 'orderby' => 'title', 'order' => 'ASC' ] );
        $selected = isset( $_GET['stand'] ) ? absint( $_GET['stand'] ) : ( $stands ? $stands[0]->ID : 0 );
        $stand = $selected ? get_post( $selected ) : null;
        ?>
        <div class="wrap lmt-admin">
            <h1 class="lmt-h1"><span class="mono"><?php esc_html_e( 'Códigos QR · Imprimir y pegar', 'la-mejor-taza' ); ?></span><br/><em><?php esc_html_e( 'Carteles A5', 'la-mejor-taza' ); ?></em></h1>
            <div class="lmt-qr-grid">
                <aside class="lmt-card">
                    <div class="mono"><?php esc_html_e( 'Seleccionar stand', 'la-mejor-taza' ); ?></div>
                    <div class="lmt-stand-list">
                        <?php foreach ( $stands as $s ) :
                            $color = get_post_meta( $s->ID, '_lmt_color', true ) ?: 'oklch(0.45 0.1 40)'; ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=lmt-qr&stand=' . $s->ID ) ); ?>"
                               class="lmt-stand-item <?php echo $selected === $s->ID ? 'is-active' : ''; ?>">
                                <span class="lmt-dot" style="background:<?php echo esc_attr( $color ); ?>"></span>
                                <span class="lmt-stand-info">
                                    <span class="name"><?php echo esc_html( get_the_title( $s ) ); ?></span>
                                    <span class="mono">#<?php echo esc_html( $s->ID ); ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="lmt-actions">
                        <button class="button button-primary" onclick="window.print()"><?php esc_html_e( 'Imprimir cartel', 'la-mejor-taza' ); ?></button>
                        <p class="lmt-hint"><?php esc_html_e( 'Cartel A5 · 148 × 210 mm · Papel offset mate recomendado', 'la-mejor-taza' ); ?></p>
                    </div>
                </aside>
                <div class="lmt-poster-stage">
                    <?php if ( $stand ) :
                        $stand_arr = LMT_CPT::stand_to_array( $stand );
                        $template = LMT_PATH . 'templates/qr-poster.php';
                        include $template;
                    else : ?>
                        <p><?php esc_html_e( 'No hay stands registrados aún.', 'la-mejor-taza' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_live() {
        $stands = LMT_CPT::get_stands();
        $rows   = LMT_DB::recent_comments( 12 );
        ?>
        <div class="wrap lmt-admin">
            <h1 class="lmt-h1">
                <span class="mono"><?php esc_html_e( 'Actividad', 'la-mejor-taza' ); ?> <span class="lmt-live-pill"><span class="dot"></span> <?php esc_html_e( 'En vivo', 'la-mejor-taza' ); ?></span></span><br/>
                <em><?php esc_html_e( 'Votos en tiempo real', 'la-mejor-taza' ); ?></em>
            </h1>
            <div class="lmt-live-grid">
                <div class="lmt-card">
                    <div class="mono"><?php esc_html_e( 'Últimos votos', 'la-mejor-taza' ); ?></div>
                    <div id="lmt-live-list" class="lmt-live-list" data-endpoint="<?php echo esc_attr( rest_url( 'lmt/v1/live' ) ); ?>">
                        <?php foreach ( $rows as $i => $r ) :
                            $emoji_map = [ 'bueno' => '😍', 'regular' => '😐', 'malo' => '😞' ];
                            $emoji = isset( $emoji_map[ $r->emoji ] ) ? $emoji_map[ $r->emoji ] : '·'; ?>
                            <div class="lmt-live-item">
                                <span class="emoji"><?php echo esc_html( $emoji ); ?></span>
                                <div class="body">
                                    <div class="name"><?php echo esc_html( get_the_title( $r->stand_id ) ); ?></div>
                                    <div class="quote">"<?php echo esc_html( $r->comentario ); ?>"</div>
                                    <div class="mono">
                                        <?php echo esc_html( LMT_DB::masked_email( $r->email ) ); ?> ·
                                        <?php echo esc_html( human_time_diff( strtotime( $r->created_at ), current_time( 'timestamp' ) ) ); ?>
                                        <?php if ( $r->comprado ) : ?> · <span class="bought"><?php esc_html_e( 'Compró', 'la-mejor-taza' ); ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if ( ! $rows ) : ?>
                            <p class="lmt-hint"><?php esc_html_e( 'Aún no hay votos. Apenas alguien escanee un QR aparecerá aquí.', 'la-mejor-taza' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="lmt-card">
                    <div class="mono"><?php esc_html_e( 'Ranking actual', 'la-mejor-taza' ); ?></div>
                    <div class="lmt-rank">
                        <?php foreach ( $stands as $i => $s ) :
                            $score = (float) get_post_meta( $s->ID, '_lmt_score', true );
                            $total = (int) get_post_meta( $s->ID, '_lmt_total_votos', true );
                            ?>
                            <div class="lmt-rank-row">
                                <span class="mono pos"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
                                <div class="info">
                                    <div class="name"><?php echo esc_html( get_the_title( $s ) ); ?></div>
                                    <div class="mono"><?php echo esc_html( get_post_meta( $s->ID, '_lmt_municipio', true ) ); ?> · <?php echo esc_html( $total ); ?> <?php esc_html_e( 'votos', 'la-mejor-taza' ); ?></div>
                                </div>
                                <div class="score"><?php echo esc_html( number_format_i18n( $score, 0 ) ); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_settings() {
        $settings = wp_parse_args( get_option( 'lmt_settings', [] ), [
            'festival_name'  => '',
            'festival_dates' => '',
            'festival_city'  => '',
            'palette'        => 'mercado',
            'organizer'      => '',
            'organizer_mail' => '',
            'vote_page'      => 0,
            'passport_page'  => 0,
            'dashboard_page' => 0,
        ] );
        ?>
        <div class="wrap lmt-admin">
            <h1 class="lmt-h1"><span class="mono"><?php esc_html_e( 'Configuración · Festival 2026', 'la-mejor-taza' ); ?></span><br/><em><?php esc_html_e( 'La Mejor Taza', 'la-mejor-taza' ); ?></em></h1>
            <?php settings_errors( 'lmt_settings' ); ?>
            <form method="post" class="lmt-card lmt-settings-form">
                <?php wp_nonce_field( 'lmt_save_settings', 'lmt_settings_nonce' ); ?>
                <div class="grid">
                    <label><span class="mono"><?php esc_html_e( 'Nombre del festival', 'la-mejor-taza' ); ?></span>
                        <input type="text" name="festival_name" value="<?php echo esc_attr( $settings['festival_name'] ); ?>"/>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Fechas', 'la-mejor-taza' ); ?></span>
                        <input type="text" name="festival_dates" value="<?php echo esc_attr( $settings['festival_dates'] ); ?>"/>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Ciudad', 'la-mejor-taza' ); ?></span>
                        <input type="text" name="festival_city" value="<?php echo esc_attr( $settings['festival_city'] ); ?>"/>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Paleta', 'la-mejor-taza' ); ?></span>
                        <select name="palette">
                            <?php foreach ( [ 'nariño', 'arena', 'niebla', 'mercado' ] as $p ) : ?>
                                <option value="<?php echo esc_attr( $p ); ?>" <?php selected( $settings['palette'], $p ); ?>><?php echo esc_html( ucfirst( $p ) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Organizador', 'la-mejor-taza' ); ?></span>
                        <input type="text" name="organizer" value="<?php echo esc_attr( $settings['organizer'] ); ?>"/>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Correo del organizador', 'la-mejor-taza' ); ?></span>
                        <input type="email" name="organizer_mail" value="<?php echo esc_attr( $settings['organizer_mail'] ); ?>"/>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Página · Formulario de voto', 'la-mejor-taza' ); ?></span>
                        <?php wp_dropdown_pages( [ 'name' => 'vote_page', 'selected' => $settings['vote_page'], 'show_option_none' => '— ' . __( 'ninguna', 'la-mejor-taza' ) . ' —' ] ); ?>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Página · Pasaporte', 'la-mejor-taza' ); ?></span>
                        <?php wp_dropdown_pages( [ 'name' => 'passport_page', 'selected' => $settings['passport_page'], 'show_option_none' => '— ' . __( 'ninguna', 'la-mejor-taza' ) . ' —' ] ); ?>
                    </label>
                    <label><span class="mono"><?php esc_html_e( 'Página · Dashboard', 'la-mejor-taza' ); ?></span>
                        <?php wp_dropdown_pages( [ 'name' => 'dashboard_page', 'selected' => $settings['dashboard_page'], 'show_option_none' => '— ' . __( 'ninguna', 'la-mejor-taza' ) . ' —' ] ); ?>
                    </label>
                </div>
                <div class="lmt-shortcodes">
                    <div class="mono"><?php esc_html_e( 'Shortcodes disponibles', 'la-mejor-taza' ); ?></div>
                    <ul>
                        <li><code>[lmt_dashboard]</code> — <?php esc_html_e( 'ranking público con mapa y feed live', 'la-mejor-taza' ); ?></li>
                        <li><code>[lmt_vote stand="ID"]</code> — <?php esc_html_e( 'formulario de voto (acepta ?stand=ID en la URL)', 'la-mejor-taza' ); ?></li>
                        <li><code>[lmt_passport]</code> — <?php esc_html_e( 'pasaporte con efecto libreta', 'la-mejor-taza' ); ?></li>
                        <li><code>[lmt_stand id="ID"]</code> — <?php esc_html_e( 'detalle público de un stand', 'la-mejor-taza' ); ?></li>
                    </ul>
                </div>
                <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar cambios', 'la-mejor-taza' ); ?></button></p>
            </form>
        </div>
        <?php
    }
}
