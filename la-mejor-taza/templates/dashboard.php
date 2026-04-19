<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$settings  = get_option( 'lmt_settings', [] );
$palette   = isset( $settings['palette'] ) ? $settings['palette'] : 'mercado';
$total     = array_sum( array_map( function( $s ) { return $s['total']; }, $stands ) );
usort( $stands, function( $a, $b ) { return $b['score'] <=> $a['score']; } );
$top3 = array_slice( $stands, 0, 3 );
$emoji_map = [ 'bueno' => '😍', 'regular' => '😐', 'malo' => '😞' ];
?>
<div class="lmt lmt-dashboard" data-lmt-palette="<?php echo esc_attr( $palette ); ?>">
    <header>
        <div class="lmt-wordmark">
            <?php include __DIR__ . '/_logo.php'; ?>
            <div>
                <div class="wm-name">La Mejor Taza</div>
                <div class="wm-sub"><?php echo esc_html( $settings['festival_name'] ?? 'Festival · Nariño 2026' ); ?></div>
            </div>
        </div>
        <div style="display:flex; gap:20px; align-items:center;">
            <span class="pill live"><span class="dot"></span><?php esc_html_e( 'En vivo · actualiza automáticamente', 'la-mejor-taza' ); ?></span>
            <span class="mono" style="color:var(--ink-3)"><?php echo esc_html( $settings['festival_dates'] ?? '14–20 abr' ); ?></span>
        </div>
    </header>

    <section class="hero">
        <div>
            <span class="mono"><?php esc_html_e( 'Ranking público', 'la-mejor-taza' ); ?></span>
            <h1>¿Cuál es la<br/>mejor taza de<br/><em>Nariño</em>?</h1>
            <p><?php esc_html_e( 'El festival lo decide el público. Escanea el QR de cada stand, vota con un emoji y sella tu pasaporte.', 'la-mejor-taza' ); ?></p>
        </div>
        <div class="metrics">
            <?php
            $passport_count = (int) $GLOBALS['wpdb']->get_var( 'SELECT COUNT(*) FROM ' . LMT_DB::passports_table() );
            $aprob = $total ? round( array_sum( array_map( function( $s ) { return $s['votos']['bueno']; }, $stands ) ) / $total * 100 ) : 0;
            $cards = [
                [ 'v' => number_format_i18n( $total ), 'sub' => __( 'votos totales', 'la-mejor-taza' ) ],
                [ 'v' => number_format_i18n( $passport_count ), 'sub' => __( 'pasaportes activos', 'la-mejor-taza' ) ],
                [ 'v' => count( $stands ), 'sub' => __( 'stands participan', 'la-mejor-taza' ) ],
                [ 'v' => $aprob . '%', 'sub' => __( 'aprobación general', 'la-mejor-taza' ) ],
            ];
            foreach ( $cards as $c ) : ?>
                <div class="metric">
                    <div class="v"><?php echo esc_html( $c['v'] ); ?></div>
                    <div class="mono" style="margin-top:8px"><?php echo esc_html( $c['sub'] ); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ( count( $top3 ) === 3 ) : ?>
    <section class="lmt-podium">
        <div class="mono" style="margin-bottom:16px"><?php esc_html_e( 'Top 3 · Podio en vivo', 'la-mejor-taza' ); ?></div>
        <div class="row">
            <?php
            $podium_order = [ $top3[1], $top3[0], $top3[2] ];
            $heights      = [ 200, 260, 170 ];
            $ranks        = [ 2, 1, 3 ];
            foreach ( $podium_order as $i => $s ) :
                $rank = $ranks[ $i ];
                ?>
                <a class="col <?php echo $rank === 1 ? 'top' : ''; ?>"
                   style="height:<?php echo esc_attr( $heights[ $i ] ); ?>px;"
                   href="<?php echo esc_url( get_permalink( $s['id'] ) ); ?>">
                    <div class="mono"><?php printf( '#%d %s', $rank, $rank === 1 ? '· La Mejor Taza' : '' ); ?></div>
                    <div>
                        <div class="name"><?php echo esc_html( $s['nombre'] ); ?></div>
                        <div style="font-size:12px; margin-top:8px; opacity:.7"><?php echo esc_html( $s['municipio'] ); ?></div>
                        <div style="margin-top:14px; display:flex; align-items:baseline; gap:10px;">
                            <span class="score"><?php echo esc_html( number_format_i18n( $s['score'], 0 ) ); ?></span>
                            <span class="mono" style="opacity:.7">/ 100 · <?php echo esc_html( $s['total'] ); ?> votos</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="lmt-grid-2">
        <div class="lmt-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <span class="mono"><?php esc_html_e( 'Mapa · Nariño', 'la-mejor-taza' ); ?></span>
                <span class="mono" style="color:var(--ink-3)"><?php printf( esc_html__( '%d stands ubicados', 'la-mejor-taza' ), count( $stands ) ); ?></span>
            </div>
            <div class="lmt-mapa paper-texture">
                <svg viewBox="0 0 100 76" preserveAspectRatio="xMidYMid meet">
                    <path d="M 15 10 L 25 5 L 45 8 L 58 4 L 70 12 L 82 18 L 88 28 L 90 40 L 85 50 L 78 58 L 68 64 L 55 68 L 42 70 L 28 68 L 18 62 L 10 52 L 8 40 L 10 28 L 15 18 Z"
                          fill="var(--paper)" stroke="var(--line-2)" stroke-width="0.3" stroke-dasharray="0.5 0.5"/>
                    <g transform="translate(52, 50)">
                        <path d="M -3 0 L 0 -5 L 3 0 Z" fill="var(--ink-3)" opacity="0.4"/>
                        <text x="0" y="6" text-anchor="middle" font-size="2.2" fill="var(--ink-3)" font-family="var(--font-mono)">GALERAS</text>
                    </g>
                    <text x="6" y="60" font-size="2.2" fill="var(--ink-3)" font-family="var(--font-mono)" opacity="0.6">OCÉANO PACÍFICO</text>
                    <text x="78" y="10" font-size="2.2" fill="var(--ink-3)" font-family="var(--font-mono)" opacity="0.6">CAUCA →</text>
                </svg>
                <?php foreach ( $stands as $i => $s ) :
                    $rank = $i + 1;
                    $size = $rank === 1 ? 28 : ( $rank <= 3 ? 22 : 16 );
                    $x = max( 0.05, min( 0.95, (float) $s['coords']['x'] ) );
                    $y = max( 0.05, min( 0.95, (float) $s['coords']['y'] ) );
                    if ( $x <= 0.001 && $y <= 0.001 ) continue;
                    ?>
                    <a class="lmt-mapa-pin"
                       href="<?php echo esc_url( get_permalink( $s['id'] ) ); ?>"
                       style="left:<?php echo esc_attr( $x * 100 ); ?>%; top:<?php echo esc_attr( $y * 100 ); ?>%; width:<?php echo esc_attr( $size ); ?>px; height:<?php echo esc_attr( $size ); ?>px; background:<?php echo esc_attr( $s['color'] ); ?>;">
                        <?php echo $rank <= 3 ? esc_html( $rank ) : ''; ?>
                        <span class="lmt-mapa-tip"><?php echo esc_html( $s['nombre'] ); ?> · <?php echo esc_html( $s['municipio'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="mono" style="margin-top:12px; display:flex; gap:16px; color:var(--ink-3)">
                <span>● Top 3</span><span>● Participantes</span>
                <span style="margin-left:auto"><?php esc_html_e( 'Toca un punto para ver detalle', 'la-mejor-taza' ); ?></span>
            </div>
        </div>
        <div class="lmt-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <span class="mono"><?php esc_html_e( 'Últimos votos', 'la-mejor-taza' ); ?></span>
                <span class="pill live"><span class="dot"></span> Live</span>
            </div>
            <div class="lmt-live-list" id="lmt-public-live" data-endpoint="<?php echo esc_attr( rest_url( 'lmt/v1/live' ) ); ?>">
                <?php foreach ( $comentarios as $c ) :
                    $emoji = isset( $emoji_map[ $c->emoji ] ) ? $emoji_map[ $c->emoji ] : '·';
                    $stand_post = get_post( $c->stand_id ); ?>
                    <div class="item" data-stand="<?php echo esc_attr( $c->stand_id ); ?>">
                        <span class="emoji"><?php echo esc_html( $emoji ); ?></span>
                        <div>
                            <div style="font-size:13px; font-weight:500;"><?php echo esc_html( $stand_post ? $stand_post->post_title : '—' ); ?></div>
                            <div class="quote">"<?php echo esc_html( $c->comentario ); ?>"</div>
                            <div class="meta"><?php echo esc_html( LMT_DB::masked_email( $c->email ) ); ?> · <?php echo esc_html( human_time_diff( strtotime( $c->created_at ), current_time( 'timestamp' ) ) ); ?>
                                <?php if ( $c->comprado ) : ?> · <span style="color:var(--cafeto)">compró</span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ( ! $comentarios ) : ?>
                    <p class="mono" style="text-align:center"><?php esc_html_e( 'Aún no hay votos. Sé el primero en sellar tu pasaporte.', 'la-mejor-taza' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="lmt-table">
        <div class="mono" style="margin-bottom:16px"><?php printf( esc_html__( 'Tabla completa · %d stands', 'la-mejor-taza' ), count( $stands ) ); ?></div>
        <div class="wrap">
            <?php foreach ( $stands as $i => $s ) : ?>
                <a class="row" href="<?php echo esc_url( get_permalink( $s['id'] ) ); ?>">
                    <span class="mono"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
                    <div>
                        <div class="name"><?php echo esc_html( $s['nombre'] ); ?></div>
                        <div class="desc"><?php echo esc_html( wp_trim_words( $s['descripcion'], 14 ) ); ?></div>
                    </div>
                    <div style="font-size:13px"><?php echo esc_html( $s['municipio'] ); ?></div>
                    <div class="score"><?php echo esc_html( number_format_i18n( $s['score'], 0 ) ); ?></div>
                    <div style="font-size:13px; color:var(--ink-2)"><?php echo esc_html( $s['total'] ); ?> votos</div>
                    <?php
                    $tot = max( 1, $s['total'] );
                    $pb  = $s['votos']['bueno'] / $tot * 100;
                    $pr  = $s['votos']['regular'] / $tot * 100;
                    $pm  = $s['votos']['malo'] / $tot * 100;
                    ?>
                    <span class="lmt-bar">
                        <i class="b" style="width:<?php echo esc_attr( $pb ); ?>%"></i>
                        <i class="r" style="width:<?php echo esc_attr( $pr ); ?>%"></i>
                        <i class="m" style="width:<?php echo esc_attr( $pm ); ?>%"></i>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <footer>
        <div class="lmt-wordmark">
            <?php include __DIR__ . '/_logo.php'; ?>
            <div>
                <div class="wm-name">La Mejor Taza</div>
                <div class="wm-sub"><?php echo esc_html( $settings['organizer'] ?? 'Comité del Café · Nariño' ); ?></div>
            </div>
        </div>
        <span class="mono" style="color:var(--ink-3)">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> · <?php echo esc_html( $settings['organizer'] ?? 'Gobernación de Nariño' ); ?></span>
    </footer>
</div>
