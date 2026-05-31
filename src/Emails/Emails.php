<?php
/**
 * Registers the booking emails with WooCommerce.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the four booking emails to WooCommerce's email manager.
 */
class Emails {

	/**
	 * Hook into WooCommerce.
	 */
	public function register() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );
	}

	/**
	 * Register the email classes.
	 *
	 * @param array $emails Existing emails.
	 * @return array
	 */
	public function register_emails( $emails ) {
		$emails['EDP_Customer_Booking_Received']  = new CustomerBookingReceived();
		$emails['EDP_Customer_Booking_Confirmed'] = new CustomerBookingConfirmed();
		$emails['EDP_Customer_Booking_Rejected']  = new CustomerBookingRejected();
		$emails['EDP_Admin_Booking_Received']     = new AdminBookingReceived();
		return $emails;
	}
}
