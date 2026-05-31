<?php
/**
 * Admin email: new booking received.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Emails;

use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Sent to the shop admin when a new booking request arrives (status: pending).
 */
class AdminBookingReceived extends AbstractBookingEmail {

	/**
	 * Configure the email.
	 */
	protected function setup() {
		$this->id              = 'edp_admin_received';
		$this->title           = __( 'Nuova prenotazione (admin)', 'eredi-experience-booking' );
		$this->description     = __( 'Inviata all’amministrazione quando arriva una nuova richiesta di prenotazione da confermare.', 'eredi-experience-booking' );
		$this->default_heading = __( 'Nuova richiesta di prenotazione', 'eredi-experience-booking' );
		$this->default_subject = __( '[{site_title}] Nuova prenotazione #{order_number}', 'eredi-experience-booking' );
		$this->template_html   = 'emails/admin-booking-received.php';
		$this->template_plain  = 'emails/plain/admin-booking-received.php';
		$this->trigger_status  = OrderStatuses::PENDING;
		$this->is_customer     = false;
	}
}
