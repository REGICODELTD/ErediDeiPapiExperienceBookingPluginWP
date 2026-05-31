<?php
/**
 * Customer email (plain): booking rejected.
 *
 * @package ErediExperienceBooking
 * @var WC_Order $order
 * @var \ErediExperienceBooking\Emails\AbstractBookingEmail $email
 */

defined( 'ABSPATH' ) || exit;

echo '= ' . wp_strip_all_tags( $email_heading ) . " =\n\n";
echo sprintf( esc_html__( 'Ciao %s,', 'eredi-experience-booking' ), $order->get_billing_first_name() ) . "\n\n";
echo esc_html__( 'Purtroppo non possiamo confermare la prenotazione per la data e l’orario indicati. Contattaci per un’alternativa.', 'eredi-experience-booking' ) . "\n\n";
echo $email->render_booking_summary( $order, true ) . "\n";

if ( $additional_content ) {
	echo wp_strip_all_tags( wptexturize( $additional_content ) ) . "\n\n";
}

echo wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
