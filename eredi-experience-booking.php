<?php
/**
 * Plugin Name:       Eredi dei Papi – Experience Booking
 * Plugin URI:        https://eredideipapiexperience.it/
 * Description:        Sistema di prenotazione esperienze per WooCommerce: tipo prodotto "Esperienza" con prezzo a persona, upsell/allestimenti, slot orari configurabili, modale di prenotazione (widget Elementor) e gestione conferma/rifiuto via ordini WooCommerce.
 * Version:           1.0.0
 * Author:            Regicode
 * Author URI:        https://regicode.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eredi-experience-booking
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * WC requires at least: 8.0
 * WC tested up to:   10.7
 *
 * @package ErediExperienceBooking
 */

defined( 'ABSPATH' ) || exit;

define( 'EDP_VERSION', '1.0.0' );
define( 'EDP_FILE', __FILE__ );
define( 'EDP_PATH', plugin_dir_path( __FILE__ ) );
define( 'EDP_URL', plugin_dir_url( __FILE__ ) );
define( 'EDP_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-4 autoloader for the ErediExperienceBooking\ namespace mapped to /src.
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'ErediExperienceBooking\\';
		$len    = strlen( $prefix );
		if ( 0 !== strncmp( $prefix, $class, $len ) ) {
			return;
		}
		$relative = substr( $class, $len );
		$file     = EDP_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

/**
 * Declare High-Performance Order Storage (HPOS) compatibility.
 * Must run before WooCommerce initialises its features.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', EDP_FILE, true );
		}
	}
);

/**
 * Boot the plugin once all plugins are loaded so we can verify WooCommerce.
 */
add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'eredi-experience-booking', false, dirname( EDP_BASENAME ) . '/languages' );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'Eredi dei Papi – Experience Booking richiede WooCommerce attivo per funzionare.', 'eredi-experience-booking' );
					echo '</p></div>';
				}
			);
			return;
		}

		\ErediExperienceBooking\Plugin::instance()->boot();
	}
);
