<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$settings = get_option( 'lmt_settings', [] );
$palette  = isset( $settings['palette'] ) ? $settings['palette'] : 'mercado';
$emojis = [
    [ 'id' => 'malo',    'label' => __( 'Malo', 'la-mejor-taza' ),    'emoji' => '😞', 'var' => 'var(--bad)' ],
    [ 'id' => 'regular', 'label' => __( 'Regular', 'la-mejor-taza' ), 'emoji' => '😐', 'var' => 'var(--meh)' ],
    [ 'id' => 'bueno',   'label' => __( 'Excelente', 'la-mejor-taza' ),'emoji' => '😍', 'var' => 'var(--good)' ],
];
?>
<div class="lmt" data-lmt-palette="<?php echo esc_attr( $palette ); ?>">
    <form class="lmt-vote" id="lmt-vote-form" data-stand="<?php echo esc_attr( $stand['id'] ); ?>">
        <div class="head">
            <div class="avatar" style="background:<?php echo esc_attr( $stand['color'] ); ?>"><?php echo esc_html( mb_substr( $stand['nombre'], 0, 1 ) ); ?></div>
            <div style="flex:1">
                <div class="mono">#<?php echo esc_html( $stand['id'] ); ?></div>
                <div style="font-weight:600; font-size:15px;"><?php echo esc_html( $stand['nombre'] ); ?></div>
            </div>
        </div>
        <div class="progress" data-step="0">
            <i class="on"></i><i></i><i></i>
        </div>

        <div class="step is-active" data-step="0">
            <span class="mono"><?php esc_html_e( 'Paso 1 / 3', 'la-mejor-taza' ); ?></span>
            <h2><?php esc_html_e( 'Califica', 'la-mejor-taza' ); ?><br/><?php echo esc_html( $stand['nombre'] ); ?></h2>
            <p style="font-size:13px; color:var(--ink-2); margin:8px 0 0; line-height:1.5;">
                <?php esc_html_e( 'Tu correo se usa solo para crear tu pasaporte del café y evitar votos repetidos.', 'la-mejor-taza' ); ?>
            </p>
            <div class="field" style="margin-top:28px">
                <label><?php esc_html_e( 'Tu correo', 'la-mejor-taza' ); ?></label>
                <input type="email" name="email" required placeholder="nombre@correo.co"/>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-primary" data-next="1" disabled style="flex:1; justify-content:center; padding:14px; opacity:.4">
                    <?php esc_html_e( 'Continuar →', 'la-mejor-taza' ); ?>
                </button>
            </div>
            <p class="mono" style="text-align:center; margin-top:12px; line-height:1.6;">
                <?php esc_html_e( 'Al continuar aceptas el tratamiento de datos del festival.', 'la-mejor-taza' ); ?>
            </p>
        </div>

        <div class="step" data-step="1">
            <span class="mono"><?php esc_html_e( 'Paso 2 / 3', 'la-mejor-taza' ); ?></span>
            <h2><?php esc_html_e( '¿Cómo estuvo', 'la-mejor-taza' ); ?><br/><?php esc_html_e( 'tu experiencia?', 'la-mejor-taza' ); ?></h2>
            <p style="font-size:13px; color:var(--ink-2); margin:8px 0 0;"><?php esc_html_e( 'Elige uno.', 'la-mejor-taza' ); ?></p>
            <div class="emoji-row">
                <?php foreach ( $emojis as $e ) : ?>
                    <button type="button" class="emoji-btn" data-emoji="<?php echo esc_attr( $e['id'] ); ?>" style="color:<?php echo esc_attr( $e['var'] ); ?>" aria-pressed="false">
                        <span class="emoji"><?php echo esc_html( $e['emoji'] ); ?></span>
                        <span style="font-size:16px; font-weight:500; color:var(--ink);"><?php echo esc_html( $e['label'] ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-prev="0">←</button>
                <button type="button" class="btn btn-primary" data-next="2" disabled style="opacity:.4"><?php esc_html_e( 'Continuar →', 'la-mejor-taza' ); ?></button>
            </div>
        </div>

        <div class="step" data-step="2">
            <span class="mono"><?php esc_html_e( 'Paso 3 / 3 · Último', 'la-mejor-taza' ); ?></span>
            <h2><?php esc_html_e( 'Cuéntanos un', 'la-mejor-taza' ); ?><br/><?php esc_html_e( 'poco más.', 'la-mejor-taza' ); ?></h2>
            <div class="mono" style="margin-top:20px; margin-bottom:10px"><?php esc_html_e( '¿Compraste algo?', 'la-mejor-taza' ); ?></div>
            <div class="compra">
                <button type="button" data-compra="1" aria-pressed="false"><?php esc_html_e( 'Sí, compré', 'la-mejor-taza' ); ?></button>
                <button type="button" data-compra="0" aria-pressed="false"><?php esc_html_e( 'No esta vez', 'la-mejor-taza' ); ?></button>
            </div>
            <div class="field" style="margin-top:24px">
                <label><?php esc_html_e( 'Comentario (opcional)', 'la-mejor-taza' ); ?></label>
                <textarea name="comentario" rows="4" placeholder="<?php esc_attr_e( '¿Qué destacarías del stand?', 'la-mejor-taza' ); ?>"></textarea>
            </div>
            <div class="err" hidden></div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-prev="1">←</button>
                <button type="submit" class="btn btn-primary"><?php esc_html_e( 'Sellar pasaporte →', 'la-mejor-taza' ); ?></button>
            </div>
        </div>

        <div class="step lmt-confirm" data-step="3">
            <span class="mono" style="text-align:center"><?php esc_html_e( '✓ Voto registrado', 'la-mejor-taza' ); ?></span>
            <h2><?php esc_html_e( 'Tu pasaporte', 'la-mejor-taza' ); ?><br/><?php esc_html_e( 'ha sido sellado.', 'la-mejor-taza' ); ?></h2>
            <p style="text-align:center; font-size:13px; color:var(--ink-2); margin-top:8px"><?php echo esc_html( $stand['nombre'] ); ?> · <?php echo esc_html( $stand['municipio'] ); ?></p>
            <div class="stamp-zone">
                <div class="stamp-anim">
                    <?php include __DIR__ . '/_sello.php'; ?>
                </div>
            </div>
            <div class="actions">
                <?php
                $passport_url  = ! empty( $settings['passport_page'] ) ? get_permalink( (int) $settings['passport_page'] ) : '#';
                $dashboard_url = ! empty( $settings['dashboard_page'] ) ? get_permalink( (int) $settings['dashboard_page'] ) : home_url( '/' );
                ?>
                <a class="btn btn-primary" href="<?php echo esc_url( add_query_arg( 'email', '', $passport_url ) ); ?>"><?php esc_html_e( 'Ver mi pasaporte →', 'la-mejor-taza' ); ?></a>
                <a class="btn btn-ghost" href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Ver ranking del festival', 'la-mejor-taza' ); ?></a>
            </div>
        </div>
    </form>
</div>
