<?php
/**
 * Admin email (plain): new booking received.
 *
 * @package ErediExperienceBooking
 * @var WC_Order $order
 * @var \ErediExperienceBooking\Emails\AbstractBookingEmail $email
 */

defined( 'ABSPATH' ) || exit;

echo '= ' . wp_strip_all_tags( $email_heading ) . " =\n\n";
echo esc_html__( 'È arrivata una nuova richiesta di prenotazione da confermare o rifiutare.', 'eredi-experience-booking' ) . "\n\n";
echo $email->render_booking_summary( $order, true ) . "\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, true, $email );

echo "\n" . esc_html__( 'Gestisci la prenotazione:', 'eredi-experience-booking' ) . ' ' . esc_url_raw( $order->get_edit_order_url() ) . "\n\n";

if ( $additional_content ) {
	echo wp_strip_all_tags( wptexturize( $additional_content ) ) . "\n\n";
}

echo wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
