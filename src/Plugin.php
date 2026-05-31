<?php
/**
 * Main plugin orchestrator.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking;

use ErediExperienceBooking\Admin\OrderAdmin;
use ErediExperienceBooking\Admin\ProductDataPanels;
use ErediExperienceBooking\Admin\ProductDataSave;
use ErediExperienceBooking\Booking\RestController;
use ErediExperienceBooking\Emails\Emails;
use ErediExperienceBooking\Frontend\Assets;
use ErediExperienceBooking\Frontend\ElementorRegistrar;
use ErediExperienceBooking\Frontend\Modal;
use ErediExperienceBooking\Frontend\Shortcode;
use ErediExperienceBooking\ProductType\ProductTypeRegistrar;
use ErediExperienceBooking\Status\CapacityGuard;
use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every sub-system into WordPress / WooCommerce.
 */
final class Plugin {

	/**
	 * Product type slug used across the plugin.
	 */
	const PRODUCT_TYPE = 'experience';

	/**
	 * Text domain.
	 */
	const TEXT_DOMAIN = 'eredi-experience-booking';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Register every sub-system. Called on plugins_loaded after WooCommerce is confirmed.
	 */
	public function boot() {
		// Product type + custom statuses.
		( new ProductTypeRegistrar() )->register();
		( new OrderStatuses() )->register();
		( new CapacityGuard() )->register();

		// Transactional emails.
		( new Emails() )->register();

		// REST API (front-end booking flow).
		( new RestController() )->register();

		// Front-end (Elementor widget, modal, shortcode, assets).
		( new ElementorRegistrar() )->register();
		( new Shortcode() )->register();
		( new Assets() )->register();
		( new Modal() )->register();

		// Admin only.
		if ( is_admin() ) {
			( new ProductDataPanels() )->register();
			( new ProductDataSave() )->register();
			( new OrderAdmin() )->register();
		}
	}
}
