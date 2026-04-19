<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$settings = get_option( 'lmt_settings', [] );
$palette  = isset( $settings['palette'] ) ? $settings['palette'] : 'mercado';
$qr_url   = LMT_CPT::vote_url( $stand_arr['id'] );
?>
<div class="lmt" data-lmt-palette="<?php echo esc_attr( $palette ); ?>">
    <div style="width:420px; height:594px; background:var(--paper); border:1px solid var(--line-2); padding:36px; display:flex; flex-direction:column; box-shadow:var(--shadow-2); position:relative; font-family:var(--font-sans);">
        <?php
        $corners = [
            'tl' => 'top:8px; left:8px; border-top:1px solid var(--ink-3); border-left:1px solid var(--ink-3);',
            'tr' => 'top:8px; right:8px; border-top:1px solid var(--ink-3); border-right:1px solid var(--ink-3);',
            'bl' => 'bottom:8px; left:8px; border-bottom:1px solid var(--ink-3); border-left:1px solid var(--ink-3);',
            'br' => 'bottom:8px; right:8px; border-bottom:1px solid var(--ink-3); border-right:1px solid var(--ink-3);',
        ];
        foreach ( $corners as $css ) : ?>
            <div style="position:absolute; width:14px; height:14px; <?php echo esc_attr( $css ); ?>"></div>
        <?php endforeach; ?>

        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div class="lmt-wordmark">
                <?php include __DIR__ . '/_logo.php'; ?>
                <div>
                    <div class="wm-name" style="font-family:var(--font-display); font-style:italic; font-size:16px; line-height:1;">La Mejor Taza</div>
                    <div class="wm-sub" style="font-family:var(--font-mono); font-size:8px; text-transform:uppercase; letter-spacing:0.08em; color:var(--ink-3); margin-top:2px;"><?php echo esc_html( $settings['festival_name'] ?? 'Festival · Nariño 2026' ); ?></div>
                </div>
            </div>
            <div class="mono" style="text-align:right; font-family:var(--font-mono); font-size:11px; text-transform:uppercase; color:var(--ink-3);">
                #<?php echo esc_html( $stand_arr['id'] ); ?><br/>
                <span><?php echo esc_html( $settings['festival_dates'] ?? '14–20 abr' ); ?></span>
            </div>
        </div>

        <div style="margin-top:20px; margin-bottom:16px;">
            <div class="mono"><?php esc_html_e( 'Escanee para calificar', 'la-mejor-taza' ); ?></div>
            <h1 style="font-family:var(--font-display); font-style:italic; font-size:44px; font-weight:400; line-height:1; margin:6px 0 0; letter-spacing:-0.01em;">
                <?php echo esc_html( $stand_arr['nombre'] ); ?>
            </h1>
            <div style="font-size:13px; color:var(--ink-2); margin-top:8px;">
                <?php echo esc_html( $stand_arr['municipio'] ); ?> · <?php echo esc_html( $stand_arr['region'] ); ?>
            </div>
        </div>

        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:16px; border:1px solid var(--line); border-radius:var(--r-md); position:relative;">
            <div id="lmt-qr-code" data-url="<?php echo esc_attr( $qr_url ); ?>" style="width:220px; height:220px;"></div>
            <noscript>
                <?php echo LMT_QR::placeholder_svg( $qr_url, 220, 'currentColor', 'transparent' ); // phpcs:ignore ?>
            </noscript>
            <div class="mono" style="margin-top:16px"><?php echo esc_html( wp_parse_url( $qr_url, PHP_URL_HOST ) ); ?>/?stand=<?php echo esc_html( $stand_arr['id'] ); ?></div>
            <div style="position:absolute; top:-20px; right:-20px;">
                <?php $stand = $stand_arr; $sello_size = 80; $sello_rot = 12; include __DIR__ . '/_sello.php'; ?>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:12px; color:var(--ink-2)"><?php esc_html_e( 'Califica. Opina. Sella tu pasaporte.', 'la-mejor-taza' ); ?></span>
                <div style="display:flex; gap:6px; font-size:20px;"><span>😞</span><span>😐</span><span>😍</span></div>
            </div>
            <div style="height:1px; background:var(--line)"></div>
            <div class="mono" style="margin-top:8px; display:flex; justify-content:space-between;">
                <span><?php esc_html_e( 'Pegue en el frente del stand', 'la-mejor-taza' ); ?></span>
                <span>· <?php echo esc_html( $settings['festival_dates'] ?? '14–20 abr' ); ?> · <?php echo esc_html( $settings['festival_city'] ?? 'Pasto' ); ?></span>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('lmt-qr-code');
    if (!el || typeof QRCode === 'undefined') return;
    new QRCode(el, { text: el.dataset.url, width: 220, height: 220, colorDark: '#222', colorLight: '#ffffff00', correctLevel: QRCode.CorrectLevel.M });
});
</script>
