<?php
/**
 * Front-end asset registration + localization.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the front-end CSS/JS and enqueues them only when a booking widget
 * or shortcode is actually rendered on the page.
 */
class Assets {

	const HANDLE = 'edp-frontend';

	/**
	 * Whether the assets have been enqueued this request.
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Hook in.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		// Make sure assets load inside the Elementor editor preview too.
		add_action( 'elementor/preview/enqueue_scripts', array( __CLASS__, 'ensure' ) );
	}

	/**
	 * Register (not enqueue) the handles and localize base config.
	 */
	public function register_assets() {
		wp_register_style( self::HANDLE, EDP_URL . 'assets/css/frontend-booking.css', array(), EDP_VERSION );
		wp_register_script( self::HANDLE, EDP_URL . 'assets/js/frontend-booking.js', array(), EDP_VERSION, true );

		wp_localize_script( self::HANDLE, 'EDP', self::config() );
	}

	/**
	 * Enqueue the assets on demand.
	 */
	public static function ensure() {
		if ( self::$enqueued ) {
			return;
		}
		// Guarantee registration even if wp_enqueue_scripts already ran.
		if ( ! wp_script_is( self::HANDLE, 'registered' ) ) {
			( new self() )->register_assets();
		}
		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );
		self::$enqueued = true;
	}

	/**
	 * Base configuration exposed to JS.
	 *
	 * @return array
	 */
	private static function config() {
		return array(
			'restUrl'  => esc_url_raw( rest_url( 'edp/v1/' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'currency' => array(
				'symbol'       => html_entity_decode( get_woocommerce_currency_symbol() ),
				'decimals'     => wc_get_price_decimals(),
				'decimalSep'   => wc_get_price_decimal_separator(),
				'thousandSep'  => wc_get_price_thousand_separator(),
				'position'     => get_option( 'woocommerce_currency_pos', 'left' ),
			),
			'i18n'     => array(
				'selectDate'      => __( 'Seleziona prima una data', 'eredi-experience-booking' ),
				'noSlots'         => __( 'Nessuno slot disponibile per questa data.', 'eredi-experience-booking' ),
				'loadingSlots'    => __( 'Caricamento orari…', 'eredi-experience-booking' ),
				'slotFull'        => __( 'esaurito', 'eredi-experience-booking' ),
				'chooseSlot'      => __( 'Scegli un orario', 'eredi-experience-booking' ),
				'dateNotBookable' => __( 'Data non disponibile, scegline un’altra.', 'eredi-experience-booking' ),
				'required'        => __( 'Compila tutti i campi obbligatori e accetta la privacy.', 'eredi-experience-booking' ),
				'sending'         => __( 'Invio in corso…', 'eredi-experience-booking' ),
				'genericError'    => __( 'Si è verificato un errore. Riprova.', 'eredi-experience-booking' ),
				'total'           => __( 'Totale', 'eredi-experience-booking' ),
				'perPerson'       => __( 'a persona', 'eredi-experience-booking' ),
			),
		);
	}
}
