<?php
/**
 * Renders the shared booking modal in the footer.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Prints the modal markup once (only when a widget/shortcode was rendered) and
 * injects the per-page experiences config for the JS.
 */
class Modal {

	/**
	 * Hook in.
	 */
	public function register() {
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
	}

	/**
	 * Output the modal + experiences data.
	 */
	public function render() {
		if ( ! Registry::has() ) {
			return;
		}

		Assets::ensure();

		wp_add_inline_script(
			Assets::HANDLE,
			'window.EDP_EXPERIENCES = ' . wp_json_encode( Registry::all() ) . ';',
			'before'
		);

		include EDP_PATH . 'templates/booking-modal.php';
	}
}
