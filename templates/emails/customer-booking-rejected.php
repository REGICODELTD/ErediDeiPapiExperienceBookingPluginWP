<?php
/**
 * Customer email (HTML): booking rejected.
 *
 * @package ErediExperienceBooking
 * @var WC_Order $order
 * @var string   $email_heading
 * @var string   $additional_content
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var \ErediExperienceBooking\Emails\AbstractBookingEmail $email
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'Ciao %s,', 'eredi-experience-booking' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<p><?php esc_html_e( 'Ti ringraziamo per la tua richiesta. Purtroppo non possiamo confermare la prenotazione per la data e l’orario indicati.', 'eredi-experience-booking' ); ?></p>

<p><?php esc_html_e( 'Contattaci pure per individuare insieme una nuova disponibilità: saremo felici di trovare un’alternativa.', 'eredi-experience-booking' ); ?></p>

<?php
echo $email->render_booking_summary( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is built with escaping.

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
