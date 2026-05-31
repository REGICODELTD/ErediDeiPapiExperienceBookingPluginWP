<?php
/**
 * Customer email: booking request received.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Emails;

use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Sent to the customer when a booking is first submitted (status: pending).
 */
class CustomerBookingReceived extends AbstractBookingEmail {

	/**
	 * Configure the email.
	 */
	protected function setup() {
		$this->id              = 'edp_customer_received';
		$this->title           = __( 'Prenotazione ricevuta (cliente)', 'eredi-experience-booking' );
		$this->description     = __( 'Inviata al cliente quando invia una richiesta di prenotazione esperienza.', 'eredi-experience-booking' );
		$this->default_heading = __( 'Abbiamo ricevuto la tua richiesta', 'eredi-experience-booking' );
		$this->default_subject = __( 'La tua richiesta di prenotazione #{order_number}', 'eredi-experience-booking' );
		$this->template_html   = 'emails/customer-booking-received.php';
		$this->template_plain  = 'emails/plain/customer-booking-received.php';
		$this->trigger_status  = OrderStatuses::PENDING;
		$this->is_customer     = true;
	}
}
