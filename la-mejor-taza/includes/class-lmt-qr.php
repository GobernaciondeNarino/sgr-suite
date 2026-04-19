<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helper for QR generation. Renders a placeholder SVG identical to the design
 * mock; for a real, scannable QR the front-end uses the qrcode-svg library
 * (loaded as a vendor asset).
 */
class LMT_QR {
    public static function placeholder_svg( $data, $size = 220, $fg = 'currentColor', $bg = 'transparent' ) {
        $hash = 0;
        $len  = strlen( $data );
        for ( $i = 0; $i < $len; $i++ ) {
            $hash = ( ( $hash << 5 ) - $hash + ord( $data[ $i ] ) ) & 0xFFFFFFFF;
        }
        $rand = function ( $i ) use ( $hash ) {
            $x = sin( $hash + $i * 13.37 ) * 10000;
            return $x - floor( $x );
        };
        $grid = 21;
        $cell = $size / $grid;
        $svg  = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %1$d %1$d" style="background:%2$s;display:block;">',
            $size,
            esc_attr( $bg )
        );
        for ( $y = 0; $y < $grid; $y++ ) {
            for ( $x = 0; $x < $grid; $x++ ) {
                if ( $rand( $y * $grid + $x ) > 0.5 ) {
                    $svg .= sprintf(
                        '<rect x="%s" y="%s" width="%s" height="%s" fill="%s"/>',
                        round( $x * $cell, 2 ),
                        round( $y * $cell, 2 ),
                        round( $cell, 2 ),
                        round( $cell, 2 ),
                        esc_attr( $fg )
                    );
                }
            }
        }
        // Finder patterns
        $finders = [ [ 0, 0 ], [ $grid - 7, 0 ], [ 0, $grid - 7 ] ];
        foreach ( $finders as $f ) {
            list( $fx, $fy ) = $f;
            $svg .= sprintf(
                '<rect x="%1$s" y="%2$s" width="%3$s" height="%3$s" fill="%4$s"/>' .
                '<rect x="%1$s" y="%2$s" width="%3$s" height="%3$s" fill="none" stroke="%5$s" stroke-width="%6$s"/>' .
                '<rect x="%7$s" y="%8$s" width="%9$s" height="%9$s" fill="%5$s"/>',
                round( $fx * $cell, 2 ),
                round( $fy * $cell, 2 ),
                round( $cell * 7, 2 ),
                esc_attr( $bg ),
                esc_attr( $fg ),
                round( $cell, 2 ),
                round( ( $fx + 2 ) * $cell, 2 ),
                round( ( $fy + 2 ) * $cell, 2 ),
                round( $cell * 3, 2 )
            );
        }
        $svg .= '</svg>';
        return $svg;
    }
}
