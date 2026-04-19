<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$settings = get_option( 'lmt_settings', [] );
$palette  = isset( $settings['palette'] ) ? $settings['palette'] : 'mercado';
$email    = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
$passport = $email ? LMT_DB::passport_by_email( $email ) : null;
$total_stands = count( LMT_CPT::get_stands() );
?>
<div class="lmt lmt-passport" data-lmt-palette="<?php echo esc_attr( $palette ); ?>">
    <div class="topbar">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:var(--paper-3); font-size:13px;">← <?php esc_html_e( 'Volver', 'la-mejor-taza' ); ?></a>
        <span class="mono" style="color:var(--paper-3)"><?php esc_html_e( 'Pasaporte', 'la-mejor-taza' ); ?> · <?php echo esc_html( $passport ? ( $passport->nombre ?: LMT_DB::masked_email( $passport->email ) ) : __( 'invitado', 'la-mejor-taza' ) ); ?></span>
        <span style="width:40px"></span>
    </div>

    <?php if ( ! $passport ) : ?>
        <form class="lmt-pp-form" method="get">
            <input type="email" name="email" required placeholder="<?php esc_attr_e( 'Tu correo del festival', 'la-mejor-taza' ); ?>" value="<?php echo esc_attr( $email ); ?>"/>
            <button class="btn btn-primary" type="submit"><?php esc_html_e( 'Abrir', 'la-mejor-taza' ); ?></button>
        </form>
        <p class="hint"><?php esc_html_e( 'Ingresa el correo con el que votaste para abrir tu pasaporte.', 'la-mejor-taza' ); ?></p>
    <?php else :
        $visited = [];
        foreach ( $passport->visits as $v ) {
            $post = get_post( $v->stand_id );
            if ( $post ) {
                $visited[] = LMT_CPT::stand_to_array( $post ) + [ 'visited_at' => $v->visited_at ];
            }
        }
        ?>
        <div class="book" id="lmt-book"
             data-pages="<?php echo esc_attr( count( $visited ) + 3 ); ?>"
             data-total="<?php echo esc_attr( $total_stands ); ?>">
            <div class="page" data-page="0">
                <div class="lmt-pp-cover">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div style="filter:invert(1) hue-rotate(180deg)"><?php include __DIR__ . '/_logo.php'; ?></div>
                        <div class="mono" style="color:var(--paper-3); text-align:right">NARIÑO<br/>COLOMBIA</div>
                    </div>
                    <div>
                        <div class="mono" style="color:var(--paper-3)"><?php esc_html_e( 'Pasaporte del Café', 'la-mejor-taza' ); ?></div>
                        <h1>La Mejor<br/>Taza.</h1>
                    </div>
                    <div>
                        <div style="height:1px; background:var(--paper-3); opacity:.3; margin-bottom:16px"></div>
                        <div class="mono" style="color:var(--paper-3)"><?php esc_html_e( 'Portador', 'la-mejor-taza' ); ?></div>
                        <div style="font-family:var(--font-display); font-style:italic; font-size:28px;">
                            <?php echo esc_html( $passport->nombre ?: LMT_DB::masked_email( $passport->email ) ); ?>
                        </div>
                        <div style="font-size:12px; color:var(--paper-3); margin-top:6px;"><?php echo esc_html( LMT_DB::masked_email( $passport->email ) ); ?></div>
                        <div class="mono" style="color:var(--paper-3); margin-top:16px; display:flex; justify-content:space-between;">
                            <span><?php esc_html_e( 'Inicio', 'la-mejor-taza' ); ?>: <?php echo esc_html( mysql2date( get_option( 'date_format' ), $passport->created_at ) ); ?></span>
                            <span>#P-<?php echo esc_html( str_pad( (string) $passport->id, 4, '0', STR_PAD_LEFT ) ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page" data-page="1" hidden>
                <div class="lmt-pp-page">
                    <span class="mono"><?php esc_html_e( 'Índice', 'la-mejor-taza' ); ?></span>
                    <h2 style="margin-top:8px"><?php esc_html_e( 'Tu travesía.', 'la-mejor-taza' ); ?></h2>
                    <p style="font-size:12px; color:var(--ink-2); line-height:1.5; margin:12px 0 20px;">
                        <?php printf( esc_html__( 'Cada stand que visites sella una página. Colecciona los %d del festival.', 'la-mejor-taza' ), $total_stands ); ?>
                    </p>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php for ( $i = 0; $i < $total_stands; $i++ ) :
                            $vis = $i < count( $visited ); ?>
                            <div style="display:flex; align-items:center; gap:10px; font-size:13px;">
                                <span class="mono" style="width:20px"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
                                <span style="flex:1; border-bottom:1px dotted var(--line-2); height:14px"></span>
                                <span style="font-size:16px"><?php echo $vis ? '●' : '○'; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="lmt-pp-foot mono" style="display:flex; justify-content:space-between;">
                        <span><?php echo esc_html( count( $visited ) ); ?> <?php esc_html_e( 'sellados', 'la-mejor-taza' ); ?></span>
                        <span><?php echo esc_html( max( 0, $total_stands - count( $visited ) ) ); ?> <?php esc_html_e( 'faltantes', 'la-mejor-taza' ); ?></span>
                    </div>
                </div>
            </div>
            <?php foreach ( $visited as $idx => $stand ) : ?>
                <div class="page" data-page="<?php echo esc_attr( 2 + $idx ); ?>" hidden>
                    <div class="lmt-pp-page">
                        <span class="mono"><?php esc_html_e( 'Sello', 'la-mejor-taza' ); ?> · <?php echo esc_html( $stand['municipio'] ); ?></span>
                        <h2 style="margin-top:6px"><?php echo esc_html( $stand['nombre'] ); ?></h2>
                        <div style="font-size:11px; color:var(--ink-3)"><?php echo esc_html( $stand['region'] ); ?></div>
                        <div class="lmt-pp-stamp">
                            <?php $sello_size = 160; $sello_rot = ( crc32( $stand['id'] ) % 20 ) - 10; include __DIR__ . '/_sello.php'; ?>
                        </div>
                        <div class="lmt-pp-foot">
                            <div style="font-size:11px; color:var(--ink-2); line-height:1.5; font-style:italic; font-family:var(--font-display);">"<?php echo esc_html( $stand['descripcion'] ); ?>"</div>
                            <div class="mono" style="margin-top:12px; display:flex; justify-content:space-between;">
                                <span><?php echo esc_html( mysql2date( 'd·M·Y', $stand['visited_at'] ) ); ?></span>
                                <span><?php echo esc_html( mysql2date( 'H:i', $stand['visited_at'] ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="page" data-page="<?php echo esc_attr( 2 + count( $visited ) ); ?>" hidden>
                <div class="lmt-pp-empty">
                    <span class="mono"><?php esc_html_e( 'Fin del pasaporte', 'la-mejor-taza' ); ?></span>
                    <h2 style="margin-top:12px"><?php esc_html_e( 'Gracias por', 'la-mejor-taza' ); ?><br/><?php esc_html_e( 'caminar el café', 'la-mejor-taza' ); ?><br/><?php esc_html_e( 'con nosotros.', 'la-mejor-taza' ); ?></h2>
                    <div style="margin-top:24px; display:inline-block; padding:14px 20px; border:1px solid var(--line-2); border-radius:999px; font-size:12px;">
                        <?php esc_html_e( 'Vuelve el próximo festival', 'la-mejor-taza' ); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="ctrls">
            <button type="button" data-pp-prev disabled>←</button>
            <span class="mono" data-pp-counter style="color:var(--paper-3)">01 / <?php echo esc_html( str_pad( count( $visited ) + 3, 2, '0', STR_PAD_LEFT ) ); ?></span>
            <button type="button" data-pp-next>→</button>
        </div>
        <div class="hint">
            <?php printf( esc_html__( '%1$d / %2$d stands sellados', 'la-mejor-taza' ), count( $visited ), $total_stands ); ?><br/>
            <?php esc_html_e( 'Desliza o usa las flechas', 'la-mejor-taza' ); ?>
        </div>
    <?php endif; ?>
</div>
