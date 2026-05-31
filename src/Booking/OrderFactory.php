<?php
/**
 * Builds a WooCommerce order from a validated booking.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Booking;

use ErediExperienceBooking\Pricing\PriceCalculator;
use ErediExperienceBooking\ProductType\ExperienceProduct;
use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the order via the CRUD API (HPOS-safe): one product line for the
 * experience, fees for upsells and addon options, and flat meta used by the
 * availability queries, emails and the admin metabox.
 */
class OrderFactory {

	/**
	 * Create the booking order.
	 *
	 * @param ExperienceProduct $product The experience.
	 * @param array             $args    {
	 *     Validated booking data.
	 *
	 *     @type int    $persons    Number of people.
	 *     @type string $date       Y-m-d.
	 *     @type string $slot       HH:MM.
	 *     @type array  $selection  Selected upsells [ ['index'=>int,'options'=>int[]], ... ].
	 *     @type array  $customer   first_name,last_name,email,phone.
	 *     @type bool   $privacy    Consent flag.
	 * }
	 * @return \WC_Order|\WP_Error
	 */
	public function create( ExperienceProduct $product, array $args ) {
		$persons   = (int) $args['persons'];
		$date      = (string) $args['date'];
		$slot      = (string) $args['slot'];
		$selection = isset( $args['selection'] ) && is_array( $args['selection'] ) ? $args['selection'] : array();
		$customer  = isset( $args['customer'] ) && is_array( $args['customer'] ) ? $args['customer'] : array();

		$calc      = new PriceCalculator();
		$breakdown = $calc->build_breakdown( $product, $persons, $selection );

		// NOTE: we intentionally do NOT pass a status here. wc_create_order() saves
		// immediately; setting the booking status before line items + meta exist
		// would fire the "pending" transition (and its emails) on an empty order.
		$order = wc_create_order( array( 'created_via' => 'edp-booking' ) );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$human_date = date_i18n( get_option( 'date_format' ), strtotime( $date ) );

		// Experience as a product line item priced per person.
		$line = new \WC_Order_Item_Product();
		$line->set_product( $product );
		$line->set_quantity( $persons );
		$line->set_subtotal( $breakdown['base_total'] );
		$line->set_total( $breakdown['base_total'] );
		$line->set_subtotal_tax( 0 );
		$line->set_total_tax( 0 );
		$line->add_meta_data( __( 'Persone', 'eredi-experience-booking' ), $persons );
		$line->add_meta_data( __( 'Data', 'eredi-experience-booking' ), $human_date );
		$line->add_meta_data( __( 'Orario', 'eredi-experience-booking' ), $slot );
		$order->add_item( $line );

		// Upsells + options.
		foreach ( $breakdown['upsells'] as $upsell ) {
			$this->add_upsell_fees( $order, $upsell, $persons );
		}

		// Billing details.
		$order->set_billing_first_name( isset( $customer['first_name'] ) ? $customer['first_name'] : '' );
		$order->set_billing_last_name( isset( $customer['last_name'] ) ? $customer['last_name'] : '' );
		$order->set_billing_email( isset( $customer['email'] ) ? $customer['email'] : '' );
		$order->set_billing_phone( isset( $customer['phone'] ) ? $customer['phone'] : '' );

		// Flat meta used for availability queries.
		$order->update_meta_data( '_edp_experience_id', $product->get_id() );
		$order->update_meta_data( '_edp_booking_date', $date );
		$order->update_meta_data( '_edp_booking_slot', $slot );
		$order->update_meta_data( '_edp_persons', $persons );
		$order->update_meta_data( '_edp_privacy_consent', ! empty( $args['privacy'] ) ? current_time( 'mysql' ) : '' );

		// Structured snapshot for emails + admin metabox.
		$order->update_meta_data(
			'_edp_booking',
			array(
				'experience_id'   => $product->get_id(),
				'experience_name' => $product->get_name(),
				'date'            => $date,
				'date_human'      => $human_date,
				'slot'            => $slot,
				'persons'         => $persons,
				'breakdown'       => $breakdown,
				'customer'        => array(
					'first_name' => isset( $customer['first_name'] ) ? $customer['first_name'] : '',
					'last_name'  => isset( $customer['last_name'] ) ? $customer['last_name'] : '',
					'email'      => isset( $customer['email'] ) ? $customer['email'] : '',
					'phone'      => isset( $customer['phone'] ) ? $customer['phone'] : '',
				),
			)
		);

		$order->calculate_totals( false );

		// Set the booking status now that line items + meta are in place, so the
		// status transition (and its emails) fires with the full order context.
		$order->set_status( OrderStatuses::PENDING );
		$order->save();

		return $order;
	}

	/**
	 * Add an upsell (and its addon options) as order fees.
	 *
	 * @param \WC_Order $order   Order.
	 * @param array     $upsell  Upsell breakdown entry.
	 * @param int       $persons People count.
	 */
	private function add_upsell_fees( $order, array $upsell, $persons ) {
		$fee = new \WC_Order_Item_Fee();
		/* translators: 1: upsell name, 2: number of people */
		$fee->set_name( sprintf( __( '%1$s (× %2$d persone)', 'eredi-experience-booking' ), $upsell['name'], $persons ) );
		$fee->set_amount( (string) $upsell['line_total'] );
		$fee->set_total( $upsell['line_total'] );
		$fee->set_tax_status( 'none' );
		$fee->set_total_tax( 0 );

		// Record included options as meta on the upsell fee.
		foreach ( $upsell['options'] as $option ) {
			if ( 'included' === $option['mode'] ) {
				$fee->add_meta_data( $option['name'], __( 'incluso', 'eredi-experience-booking' ) );
			}
		}
		$order->add_item( $fee );

		// Addon options become their own fee lines for total transparency.
		foreach ( $upsell['options'] as $option ) {
			if ( 'addon' !== $option['mode'] || $option['line_total'] <= 0 ) {
				continue;
			}
			$opt_fee = new \WC_Order_Item_Fee();
			/* translators: 1: upsell name, 2: option name, 3: number of people */
			$opt_fee->set_name( sprintf( __( '%1$s – %2$s (× %3$d persone)', 'eredi-experience-booking' ), $upsell['name'], $option['name'], $persons ) );
			$opt_fee->set_amount( (string) $option['line_total'] );
			$opt_fee->set_total( $option['line_total'] );
			$opt_fee->set_tax_status( 'none' );
			$opt_fee->set_total_tax( 0 );
			$order->add_item( $opt_fee );
		}
	}
}
