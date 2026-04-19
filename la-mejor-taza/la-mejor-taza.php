<?php
/**
 * Plugin Name:       La Mejor Taza
 * Plugin URI:        https://github.com/GobernaciondeNarino/la-mejor-taza
 * Description:       Pasaporte del Café de Nariño — registro de stands, códigos QR, votación pública con emojis y dashboard en vivo.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Gobernación de Nariño
 * Author URI:        https://narino.gov.co
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       la-mejor-taza
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LMT_VERSION', '1.0.0' );
define( 'LMT_FILE', __FILE__ );
define( 'LMT_PATH', plugin_dir_path( __FILE__ ) );
define( 'LMT_URL', plugin_dir_url( __FILE__ ) );
define( 'LMT_BASENAME', plugin_basename( __FILE__ ) );

require_once LMT_PATH . 'includes/class-lmt-db.php';
require_once LMT_PATH . 'includes/class-lmt-cpt.php';
require_once LMT_PATH . 'includes/class-lmt-qr.php';
require_once LMT_PATH . 'includes/class-lmt-rest.php';
require_once LMT_PATH . 'includes/class-lmt-shortcodes.php';
require_once LMT_PATH . 'includes/class-lmt-admin.php';
require_once LMT_PATH . 'includes/class-lmt-assets.php';
require_once LMT_PATH . 'includes/class-lmt-plugin.php';

register_activation_hook( __FILE__, [ 'LMT_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'LMT_Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', [ 'LMT_Plugin', 'instance' ] );
