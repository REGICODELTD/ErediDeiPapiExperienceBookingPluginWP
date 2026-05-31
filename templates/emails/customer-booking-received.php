<?php
/**
 * Customer email (HTML): booking request received.
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

<p><?php esc_html_e( 'Abbiamo ricevuto la tua richiesta di prenotazione. La nostra amministrazione la valuterà e riceverai a breve una email con l’esito.', 'eredi-experience-booking' ); ?></p>

<?php
echo $email->render_booking_summary( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is built with escaping.

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
?>

<p><em><?php esc_html_e( 'La prenotazione si intende confermata solo dopo la nostra email di conferma. Nessun pagamento è richiesto in questa fase.', 'eredi-experience-booking' ); ?></em></p>

<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
