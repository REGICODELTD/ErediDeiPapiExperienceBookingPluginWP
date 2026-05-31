<?php
/**
 * Customer email: booking confirmed.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Emails;

use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Sent to the customer when the admin confirms the booking.
 */
class CustomerBookingConfirmed extends AbstractBookingEmail {

	/**
	 * Configure the email.
	 */
	protected function setup() {
		$this->id              = 'edp_customer_confirmed';
		$this->title           = __( 'Prenotazione confermata (cliente)', 'eredi-experience-booking' );
		$this->description     = __( 'Inviata al cliente quando l’amministrazione conferma la prenotazione.', 'eredi-experience-booking' );
		$this->default_heading = __( 'La tua prenotazione è confermata', 'eredi-experience-booking' );
		$this->default_subject = __( 'Prenotazione confermata #{order_number}', 'eredi-experience-booking' );
		$this->template_html   = 'emails/customer-booking-confirmed.php';
		$this->template_plain  = 'emails/plain/customer-booking-confirmed.php';
		$this->trigger_status  = OrderStatuses::CONFIRMED;
		$this->is_customer     = true;
	}
}
