<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$settings = get_option( 'lmt_settings', [] );
$palette  = isset( $settings['palette'] ) ? $settings['palette'] : 'mercado';
usort( $stands, function( $a, $b ) { return $b['score'] <=> $a['score']; } );
$rank = 1;
foreach ( $stands as $i => $s ) { if ( $s['id'] === $stand['id'] ) { $rank = $i + 1; break; } }
$total = max( 1, $stand['total'] );
$rows = [
    [ 'k' => __( 'Excelente', 'la-mejor-taza' ), 'v' => $stand['votos']['bueno'],   'color' => 'var(--good)', 'emoji' => '😍' ],
    [ 'k' => __( 'Regular', 'la-mejor-taza' ),   'v' => $stand['votos']['regular'], 'color' => 'var(--meh)',  'emoji' => '😐' ],
    [ 'k' => __( 'Malo', 'la-mejor-taza' ),      'v' => $stand['votos']['malo'],    'color' => 'var(--bad)',  'emoji' => '😞' ],
];
$dashboard_url = ! empty( $settings['dashboard_page'] ) ? get_permalink( (int) $settings['dashboard_page'] ) : home_url( '/' );
$vote_url      = LMT_CPT::vote_url( $stand['id'] );
?>
<div class="lmt lmt-detail" data-lmt-palette="<?php echo esc_attr( $palette ); ?>">
    <header>
        <a class="btn btn-ghost" href="<?php echo esc_url( $dashboard_url ); ?>">← <?php esc_html_e( 'Ranking', 'la-mejor-taza' ); ?></a>
        <div class="lmt-wordmark">
            <?php include __DIR__ . '/_logo.php'; ?>
            <div>
                <div class="wm-name">La Mejor Taza</div>
                <div class="wm-sub"><?php echo esc_html( $settings['festival_name'] ?? 'Festival · Nariño 2026' ); ?></div>
            </div>
        </div>
    </header>
    <section class="hero">
        <div>
            <span class="mono"><?php printf( esc_html__( 'Posición #%1$d · %2$s', 'la-mejor-taza' ), $rank, esc_html( $stand['municipio'] ) ); ?></span>
            <h1><?php echo esc_html( $stand['nombre'] ); ?></h1>
            <p style="font-size:16px; color:var(--ink-2); margin-top:20px; max-width:540px; line-height:1.6;"><?php echo esc_html( $stand['descripcion'] ); ?></p>

            <div style="margin-top:32px; display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
                <div class="stat">
                    <span class="mono"><?php esc_html_e( 'Calificación', 'la-mejor-taza' ); ?></span>
                    <div class="v"><?php echo esc_html( number_format_i18n( $stand['score'], 0 ) ); ?></div>
                    <div style="font-size:11px; color:var(--ink-3)"><?php esc_html_e( '/ 100', 'la-mejor-taza' ); ?></div>
                </div>
                <div class="stat">
                    <span class="mono"><?php esc_html_e( 'Votos', 'la-mejor-taza' ); ?></span>
                    <div class="v"><?php echo esc_html( $stand['total'] ); ?></div>
                    <div style="font-size:11px; color:var(--ink-3)"><?php esc_html_e( 'totales', 'la-mejor-taza' ); ?></div>
                </div>
                <div class="stat">
                    <span class="mono"><?php esc_html_e( 'Aprobación', 'la-mejor-taza' ); ?></span>
                    <div class="v"><?php echo esc_html( round( $stand['votos']['bueno'] / $total * 100 ) ); ?>%</div>
                    <div style="font-size:11px; color:var(--ink-3)"><?php esc_html_e( 'excelente', 'la-mejor-taza' ); ?></div>
                </div>
            </div>

            <div class="dist">
                <span class="mono"><?php esc_html_e( 'Distribución', 'la-mejor-taza' ); ?></span>
                <?php foreach ( $rows as $r ) :
                    $pct = $total ? $r['v'] / $total * 100 : 0; ?>
                    <div class="row">
                        <span style="font-size:20px"><?php echo esc_html( $r['emoji'] ); ?></span>
                        <span style="width:80px; font-size:13px;"><?php echo esc_html( $r['k'] ); ?></span>
                        <div class="bar"><i style="width:<?php echo esc_attr( $pct ); ?>%; background:<?php echo esc_attr( $r['color'] ); ?>"></i></div>
                        <span class="mono" style="width:60px; text-align:right;"><?php echo esc_html( round( $pct ) ); ?>% · <?php echo esc_html( $r['v'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <aside>
            <div class="card" style="background:<?php echo esc_attr( $stand['color'] ); ?>">
                <div class="mono" style="color:oklch(0.95 0.01 75)">#<?php echo esc_html( $stand['id'] ); ?></div>
                <div>
                    <div class="name"><?php echo esc_html( $stand['nombre'] ); ?></div>
                    <div style="font-size:13px; margin-top:12px; opacity:.85"><?php echo esc_html( $stand['direccion'] ); ?></div>
                    <div style="font-size:13px; margin-top:4px; opacity:.85"><?php echo esc_html( $stand['correo'] ); ?></div>
                </div>
                <a class="btn" style="background:var(--paper); color:var(--ink); justify-content:center; padding:14px;" href="<?php echo esc_url( $vote_url ); ?>"><?php esc_html_e( 'Escanear y votar →', 'la-mejor-taza' ); ?></a>
            </div>
            <div style="margin-top:20px">
                <span class="mono"><?php esc_html_e( 'Comentarios recientes', 'la-mejor-taza' ); ?></span>
                <?php if ( ! $comments ) : ?>
                    <div style="font-size:13px; color:var(--ink-3); font-style:italic; font-family:var(--font-display); margin-top:10px;">
                        <?php esc_html_e( 'Aún no hay comentarios. Sé el primero.', 'la-mejor-taza' ); ?>
                    </div>
                <?php else :
                    foreach ( $comments as $i => $c ) : ?>
                        <div style="padding:12px 0; border-bottom:1px solid var(--line);">
                            <div style="font-size:13px; font-family:var(--font-display); font-style:italic; line-height:1.4;">"<?php echo esc_html( $c->comentario ); ?>"</div>
                            <div class="mono" style="margin-top:6px"><?php echo esc_html( LMT_DB::masked_email( $c->email ) ); ?> · <?php echo esc_html( human_time_diff( strtotime( $c->created_at ), current_time( 'timestamp' ) ) ); ?></div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </aside>
    </section>
</div>
