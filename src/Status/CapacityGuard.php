<?php
/**
 * Enforces slot capacity at confirmation time (confirmed-only counting).
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Status;

use ErediExperienceBooking\Availability\AvailabilityService;
use ErediExperienceBooking\ProductType\ExperienceProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Because only confirmed bookings occupy a slot, confirming one more than the
 * configured maximum is the only way to overbook. This guard runs on every
 * confirmation path (Confirm button, status dropdown, bulk action): if the slot
 * is already full it cancels the confirmation back to "pending" and notifies the
 * admin. Emails for the cancelled transition are suppressed.
 */
class CapacityGuard {

	/**
	 * Order ids whose plugin emails must be suppressed this request.
	 *
	 * @var array<int,bool>
	 */
	private static $suppress = array();

	/**
	 * Hook in.
	 */
	public function register() {
		// Priority 1: run before the confirmation email (priority 10).
		add_action( 'woocommerce_order_status_' . OrderStatuses::CONFIRMED, array( $this, 'guard' ), 1, 2 );
		add_action( 'admin_notices', array( $this, 'print_notices' ) );
	}

	/**
	 * Whether emails for an order should be suppressed.
	 *
	 * @param int $order_id Order id.
	 * @return bool
	 */
	public static function should_suppress( $order_id ) {
		return ! empty( self::$suppress[ (int) $order_id ] );
	}

	/**
	 * Cancel an over-capacity confirmation.
	 *
	 * @param int       $order_id Order id.
	 * @param \WC_Order $order    Order.
	 */
	public function guard( $order_id, $order ) {
		if ( ! $order instanceof \WC_Order || ! $order->meta_exists( '_edp_booking' ) ) {
			return;
		}

		$experience_id = (int) $order->get_meta( '_edp_experience_id' );
		$date          = (string) $order->get_meta( '_edp_booking_date' );
		$slot          = (string) $order->get_meta( '_edp_booking_slot' );
		if ( ! $experience_id || ! $date || ! $slot ) {
			return;
		}

		$product = wc_get_product( $experience_id );
		if ( ! $product instanceof ExperienceProduct ) {
			return;
		}

		$service = new AvailabilityService();
		$max     = $service->get_slot_max( $product, $date, $slot );
		if ( null === $max || $max <= 0 ) {
			return; // Unlimited or slot no longer configured.
		}

		$already = $service->count_confirmed_for_slot( $experience_id, $date, $slot, $order_id );
		if ( $already < $max ) {
			return; // Still room.
		}

		// Over capacity: cancel this confirmation.
		self::$suppress[ (int) $order_id ] = true;

		$order->update_status(
			OrderStatuses::PENDING,
			__( 'Conferma annullata automaticamente: capienza dello slot esaurita.', 'eredi-experience-booking' )
		);

		$this->add_notice(
			sprintf(
				/* translators: 1: slot time, 2: date, 3: max capacity */
				__( 'Impossibile confermare la prenotazione #%1$s: lo slot delle %2$s del %3$s ha già raggiunto la capienza massima (%4$d). La prenotazione è stata riportata in attesa.', 'eredi-experience-booking' ),
				$order->get_order_number(),
				$slot,
				date_i18n( get_option( 'date_format' ), strtotime( $date ) ),
				$max
			)
		);
	}

	/**
	 * Queue an admin notice (survives the post-save redirect).
	 *
	 * @param string $message Message.
	 * @param string $type    error|warning|success.
	 */
	public function add_notice( $message, $type = 'error' ) {
		$key     = 'edp_admin_notices_' . get_current_user_id();
		$notices = get_transient( $key );
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		$notices[] = array(
			'type'    => in_array( $type, array( 'error', 'warning', 'success' ), true ) ? $type : 'error',
			'message' => $message,
		);
		set_transient( $key, $notices, 120 );
	}

	/**
	 * Print and clear queued admin notices.
	 */
	public function print_notices() {
		$key     = 'edp_admin_notices_' . get_current_user_id();
		$notices = get_transient( $key );
		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return;
		}
		delete_transient( $key );
		foreach ( $notices as $notice ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}
	}
}
