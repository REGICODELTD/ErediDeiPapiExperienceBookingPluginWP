<?php
/**
 * Base class for the booking transactional emails.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Shared plumbing: triggers on a status hook, renders templates and a booking
 * summary block. Subclasses only declare their identity in setup().
 */
abstract class AbstractBookingEmail extends \WC_Email {

	/**
	 * Order status (without wc- prefix) whose transition sends this email.
	 *
	 * @var string
	 */
	protected $trigger_status = '';

	/**
	 * Whether the email goes to the customer (true) or the admin (false).
	 *
	 * @var bool
	 */
	protected $is_customer = true;

	/**
	 * Default subject (before settings override).
	 *
	 * @var string
	 */
	protected $default_subject = '';

	/**
	 * Default heading.
	 *
	 * @var string
	 */
	protected $default_heading = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->template_base = EDP_PATH . 'templates/';
		$this->email_type    = 'html';

		$this->setup();

		$this->customer_email = $this->is_customer;

		parent::__construct();

		if ( ! $this->is_customer ) {
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		add_action( 'woocommerce_order_status_' . $this->trigger_status, array( $this, 'trigger' ), 10, 2 );
	}

	/**
	 * Configure id/title/description/heading/subject/templates/trigger.
	 */
	abstract protected function setup();

	/**
	 * Send the email on the configured status transition.
	 *
	 * @param int            $order_id Order id.
	 * @param \WC_Order|null $order    Order object.
	 */
	public function trigger( $order_id, $order = null ) {
		$this->setup_locale();

		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order instanceof \WC_Order ) {
			$this->object = $order;

			// Only act on bookings created by this plugin.
			if ( ! $order->meta_exists( '_edp_booking' ) ) {
				$this->restore_locale();
				return;
			}

			if ( $this->is_customer ) {
				$this->recipient = $order->get_billing_email();
			}

			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{customer_name}'] = $order->get_billing_first_name();
		}

		$suppressed = $this->object instanceof \WC_Order && \ErediExperienceBooking\Status\CapacityGuard::should_suppress( $this->object->get_id() );

		if ( ! $suppressed && $this->is_enabled() && $this->get_recipient() && $this->object instanceof \WC_Order ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => ! $this->is_customer,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Plain-text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => ! $this->is_customer,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Default subject used until overridden in settings.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return $this->default_subject;
	}

	/**
	 * Default heading used until overridden in settings.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return $this->default_heading;
	}

	/**
	 * Render the booking summary (HTML or plain) from the order snapshot.
	 *
	 * @param \WC_Order $order Order.
	 * @param bool      $plain Plain-text mode.
	 * @return string
	 */
	public function render_booking_summary( $order, $plain = false ) {
		$booking = $order->get_meta( '_edp_booking' );
		if ( ! is_array( $booking ) ) {
			return '';
		}

		$rows = array(
			__( 'Esperienza', 'eredi-experience-booking' ) => isset( $booking['experience_name'] ) ? $booking['experience_name'] : '',
			__( 'Data', 'eredi-experience-booking' )       => isset( $booking['date_human'] ) ? $booking['date_human'] : '',
			__( 'Orario', 'eredi-experience-booking' )     => isset( $booking['slot'] ) ? $booking['slot'] : '',
			__( 'Persone', 'eredi-experience-booking' )    => isset( $booking['persons'] ) ? $booking['persons'] : '',
			__( 'Riferimento', 'eredi-experience-booking' ) => $order->get_order_number(),
		);

		if ( ! $this->is_customer && isset( $booking['customer'] ) ) {
			$c        = $booking['customer'];
			$contact  = trim( ( $c['first_name'] ?? '' ) . ' ' . ( $c['last_name'] ?? '' ) );
			$contact .= "\n" . ( $c['email'] ?? '' ) . ' · ' . ( $c['phone'] ?? '' );
			$rows[ __( 'Cliente', 'eredi-experience-booking' ) ] = $contact;
		}

		if ( $plain ) {
			$out = '';
			foreach ( $rows as $label => $value ) {
				$out .= $label . ': ' . wp_strip_all_tags( str_replace( "\n", ' ', (string) $value ) ) . "\n";
			}
			return $out;
		}

		$html  = '<h2 style="color:#002435;">' . esc_html__( 'Dettagli prenotazione', 'eredi-experience-booking' ) . '</h2>';
		$html .= '<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e6e1d8;border-collapse:collapse;margin-bottom:20px;">';
		foreach ( $rows as $label => $value ) {
			$html .= '<tr>';
			$html .= '<th style="text-align:left;background:#f9f7f3;border:1px solid #e6e1d8;color:#002435;width:35%;">' . esc_html( $label ) . '</th>';
			$html .= '<td style="text-align:left;border:1px solid #e6e1d8;">' . nl2br( esc_html( (string) $value ) ) . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		return $html;
	}
}
