<?php
/**
 * Shared renderer for the booking summary (used by the Elementor widget and the
 * shortcode) so both produce identical markup.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

use ErediExperienceBooking\ProductType\ExperienceProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the on-page summary + "Prenota" button.
 */
class WidgetView {

	/**
	 * Render the summary HTML for an experience.
	 *
	 * @param ExperienceProduct $product Experience.
	 * @param array             $args    Display options.
	 * @return string
	 */
	public static function render( ExperienceProduct $product, array $args = array() ) {
		Assets::ensure();
		Registry::add( $product );

		$args = wp_parse_args(
			$args,
			array(
				'show_price'    => true,
				'show_persons'  => true,
				'button_text'   => __( 'Prenota ora', 'eredi-experience-booking' ),
				'price_prefix'  => __( 'da', 'eredi-experience-booking' ),
				'price_suffix'  => __( 'a persona', 'eredi-experience-booking' ),
				'wrapper_class' => '',
			)
		);

		ob_start();
		include EDP_PATH . 'templates/widget-summary.php';
		return ob_get_clean();
	}

	/**
	 * Render an editor/admin placeholder when no valid experience is selected.
	 *
	 * @param string $message Message.
	 * @return string
	 */
	public static function placeholder( $message ) {
		return '<div class="edp-widget edp-widget-placeholder" style="padding:16px;border:1px dashed #c3c4c7;color:#555;">' . esc_html( $message ) . '</div>';
	}
}
