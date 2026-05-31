<?php
/**
 * [edp_experience_booking] shortcode (fallback to the Elementor widget).
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

use ErediExperienceBooking\ProductType\ExperienceProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the booking summary anywhere via a shortcode.
 *
 * Usage: [edp_experience_booking id="123" button_text="Prenota" show_price="yes" show_persons="yes"]
 * When "id" is omitted it falls back to the current product (single product page).
 */
class Shortcode {

	/**
	 * Register the shortcode.
	 */
	public function register() {
		add_shortcode( 'edp_experience_booking', array( $this, 'render' ) );
	}

	/**
	 * Render callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'           => 0,
				'button_text'  => __( 'Prenota ora', 'eredi-experience-booking' ),
				'show_price'   => 'yes',
				'show_persons' => 'yes',
				'price_prefix' => __( 'da', 'eredi-experience-booking' ),
				'price_suffix' => __( 'a persona', 'eredi-experience-booking' ),
			),
			$atts,
			'edp_experience_booking'
		);

		$id = absint( $atts['id'] );
		if ( ! $id ) {
			$id = (int) get_the_ID();
		}

		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product instanceof ExperienceProduct ) {
			return current_user_can( 'manage_woocommerce' )
				? WidgetView::placeholder( __( 'Shortcode prenotazione: nessuna esperienza valida per questo ID.', 'eredi-experience-booking' ) )
				: '';
		}

		return WidgetView::render(
			$product,
			array(
				'show_price'   => 'yes' === $atts['show_price'],
				'show_persons' => 'yes' === $atts['show_persons'],
				'button_text'  => $atts['button_text'],
				'price_prefix' => $atts['price_prefix'],
				'price_suffix' => $atts['price_suffix'],
			)
		);
	}
}
