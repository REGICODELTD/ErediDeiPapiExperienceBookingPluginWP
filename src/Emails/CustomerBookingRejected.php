<?php
/**
 * Customer email: booking rejected.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Emails;

use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Sent to the customer when the admin rejects the booking.
 */
class CustomerBookingRejected extends AbstractBookingEmail {

	/**
	 * Configure the email.
	 */
	protected function setup() {
		$this->id              = 'edp_customer_rejected';
		$this->title           = __( 'Prenotazione rifiutata (cliente)', 'eredi-experience-booking' );
		$this->description     = __( 'Inviata al cliente quando l’amministrazione rifiuta la prenotazione.', 'eredi-experience-booking' );
		$this->default_heading = __( 'Aggiornamento sulla tua prenotazione', 'eredi-experience-booking' );
		$this->default_subject = __( 'Prenotazione non confermata #{order_number}', 'eredi-experience-booking' );
		$this->template_html   = 'emails/customer-booking-rejected.php';
		$this->template_plain  = 'emails/plain/customer-booking-rejected.php';
		$this->trigger_status  = OrderStatuses::REJECTED;
		$this->is_customer     = true;
	}
}
