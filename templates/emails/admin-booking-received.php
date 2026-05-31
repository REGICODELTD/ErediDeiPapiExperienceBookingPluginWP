<?php
/**
 * Admin email (HTML): new booking received.
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

<p><?php esc_html_e( 'È arrivata una nuova richiesta di prenotazione da confermare o rifiutare.', 'eredi-experience-booking' ); ?></p>

<?php
echo $email->render_booking_summary( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is built with escaping.

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
?>

<p>
	<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" style="display:inline-block;padding:10px 18px;background:#002435;color:#fff;text-decoration:none;border-radius:2px;">
		<?php esc_html_e( 'Gestisci la prenotazione', 'eredi-experience-booking' ); ?>
	</a>
</p>

<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
