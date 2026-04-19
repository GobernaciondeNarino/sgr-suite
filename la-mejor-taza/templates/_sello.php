<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$sello_id   = 'sello-' . $stand['id'] . '-' . wp_rand( 1000, 9999 );
$sello_size = isset( $sello_size ) ? (int) $sello_size : 110;
$sello_rot  = isset( $sello_rot ) ? (int) $sello_rot : -8;
$letras     = mb_strtoupper( $stand['nombre'] );
$first      = explode( ' ', $stand['nombre'] );
$first      = $first ? $first[0] : '';
$muni       = mb_strtoupper( $stand['municipio'] );
$fecha      = date_i18n( 'd·M·Y' );
?>
<div class="lmt-sello" style="width:<?php echo esc_attr( $sello_size ); ?>px; height:<?php echo esc_attr( $sello_size ); ?>px; transform:rotate(<?php echo esc_attr( $sello_rot ); ?>deg);">
    <svg width="<?php echo esc_attr( $sello_size ); ?>" height="<?php echo esc_attr( $sello_size ); ?>" viewBox="0 0 110 110">
        <defs>
            <path id="<?php echo esc_attr( $sello_id ); ?>" d="M 55,55 m -40,0 a 40,40 0 1,1 80,0 a 40,40 0 1,1 -80,0"/>
        </defs>
        <circle cx="55" cy="55" r="48" stroke="<?php echo esc_attr( $stand['color'] ); ?>" stroke-width="2" fill="none"/>
        <circle cx="55" cy="55" r="42" stroke="<?php echo esc_attr( $stand['color'] ); ?>" stroke-width="1" fill="none"/>
        <text fill="<?php echo esc_attr( $stand['color'] ); ?>" font-size="7" font-family="JetBrains Mono, monospace" letter-spacing="1.5">
            <textPath href="#<?php echo esc_attr( $sello_id ); ?>" startOffset="0"><?php echo esc_html( $letras ); ?> · <?php echo esc_html( $muni ); ?> · </textPath>
        </text>
        <text x="55" y="48" text-anchor="middle" fill="<?php echo esc_attr( $stand['color'] ); ?>" font-size="8" font-family="JetBrains Mono, monospace" letter-spacing="2">VISITADO</text>
        <text x="55" y="62" text-anchor="middle" fill="<?php echo esc_attr( $stand['color'] ); ?>" font-size="16" font-family="Instrument Serif, serif" font-style="italic"><?php echo esc_html( $first ); ?></text>
        <text x="55" y="74" text-anchor="middle" fill="<?php echo esc_attr( $stand['color'] ); ?>" font-size="7" font-family="JetBrains Mono, monospace" letter-spacing="1"><?php echo esc_html( $fecha ); ?></text>
    </svg>
</div>
