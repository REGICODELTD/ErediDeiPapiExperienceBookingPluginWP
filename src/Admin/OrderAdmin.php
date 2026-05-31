<?php
/**
 * Order screen integration: booking details metabox + confirm/reject actions.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Admin;

use ErediExperienceBooking\Availability\AvailabilityService;
use ErediExperienceBooking\ProductType\ExperienceProduct;
use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Dettagli prenotazione" metabox with confirm/reject controls.
 * Capacity is enforced by CapacityGuard on every confirmation path.
 */
class OrderAdmin {

	/**
	 * Hook in.
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'admin_post_edp_confirm_booking', array( $this, 'handle_confirm' ) );
		add_action( 'admin_post_edp_reject_booking', array( $this, 'handle_reject' ) );
	}

	/**
	 * Register the metabox on both legacy and HPOS order screens.
	 */
	public function add_metabox() {
		$screens = array( 'shop_order' );
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}

		foreach ( array_unique( array_filter( $screens ) ) as $screen ) {
			add_meta_box(
				'edp_booking_details',
				__( 'Dettagli prenotazione', 'eredi-experience-booking' ),
				array( $this, 'render_metabox' ),
				$screen,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render the metabox.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Screen subject.
	 */
	public function render_metabox( $post_or_order ) {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( is_object( $post_or_order ) ? $post_or_order->ID : 0 );
		if ( ! $order instanceof \WC_Order || ! $order->meta_exists( '_edp_booking' ) ) {
			echo '<p>' . esc_html__( 'Questo ordine non è una prenotazione esperienza.', 'eredi-experience-booking' ) . '</p>';
			return;
		}

		$booking = $order->get_meta( '_edp_booking' );
		$status  = $order->get_status();

		echo '<div class="edp-order-box">';
		$this->render_summary( $order, is_array( $booking ) ? $booking : array() );
		$this->render_capacity( $order );
		$this->render_actions( $order, $status );
		echo '</div>';
	}

	/**
	 * Render the booking summary table.
	 *
	 * @param \WC_Order $order   Order.
	 * @param array     $booking Snapshot.
	 */
	private function render_summary( $order, array $booking ) {
		$rows = array(
			__( 'Esperienza', 'eredi-experience-booking' ) => $booking['experience_name'] ?? '',
			__( 'Data', 'eredi-experience-booking' )       => $booking['date_human'] ?? '',
			__( 'Orario', 'eredi-experience-booking' )     => $booking['slot'] ?? '',
			__( 'Persone', 'eredi-experience-booking' )    => $booking['persons'] ?? '',
		);

		echo '<table class="edp-order-summary">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
		}
		echo '</table>';

		if ( ! empty( $booking['breakdown']['upsells'] ) ) {
			echo '<p class="edp-order-subtitle"><strong>' . esc_html__( 'Upsell & allestimenti', 'eredi-experience-booking' ) . '</strong></p><ul class="edp-order-upsells">';
			foreach ( $booking['breakdown']['upsells'] as $upsell ) {
				echo '<li>' . esc_html( $upsell['name'] ) . ' — ' . wp_kses_post( wc_price( $upsell['line_total'] ) );
				if ( ! empty( $upsell['options'] ) ) {
					$opts = array();
					foreach ( $upsell['options'] as $opt ) {
						$opts[] = $opt['name'] . ( 'addon' === $opt['mode'] ? ' (+' . wp_strip_all_tags( wc_price( $opt['line_total'] ) ) . ')' : ' (' . __( 'incluso', 'eredi-experience-booking' ) . ')' );
					}
					echo '<br><small>' . esc_html( implode( ', ', $opts ) ) . '</small>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}
	}

	/**
	 * Show how full the booked slot is (confirmed-only).
	 *
	 * @param \WC_Order $order Order.
	 */
	private function render_capacity( $order ) {
		$experience_id = (int) $order->get_meta( '_edp_experience_id' );
		$date          = (string) $order->get_meta( '_edp_booking_date' );
		$slot          = (string) $order->get_meta( '_edp_booking_slot' );
		$product       = $experience_id ? wc_get_product( $experience_id ) : null;
		if ( ! $product instanceof ExperienceProduct ) {
			return;
		}

		$service = new AvailabilityService();
		$max     = $service->get_slot_max( $product, $date, $slot );
		if ( null === $max || $max <= 0 ) {
			echo '<p class="edp-capacity">' . esc_html__( 'Capienza slot: illimitata.', 'eredi-experience-booking' ) . '</p>';
			return;
		}

		$confirmed = $service->count_confirmed_for_slot( $experience_id, $date, $slot );
		printf(
			'<p class="edp-capacity"><strong>%s</strong> %d / %d</p>',
			esc_html__( 'Confermate su questo slot:', 'eredi-experience-booking' ),
			(int) $confirmed,
			(int) $max
		);
	}

	/**
	 * Render confirm/reject buttons.
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $status Current status (without wc-).
	 */
	private function render_actions( $order, $status ) {
		$action_url = admin_url( 'admin-post.php' );

		echo '<div class="edp-order-actions">';

		if ( OrderStatuses::CONFIRMED !== $status ) {
			echo '<form method="post" action="' . esc_url( $action_url ) . '" style="display:inline-block;margin-right:6px;">';
			wp_nonce_field( 'edp_confirm_booking_' . $order->get_id() );
			echo '<input type="hidden" name="action" value="edp_confirm_booking" />';
			echo '<input type="hidden" name="order_id" value="' . esc_attr( $order->get_id() ) . '" />';
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'Conferma', 'eredi-experience-booking' ) . '</button>';
			echo '</form>';
		}

		if ( OrderStatuses::REJECTED !== $status ) {
			echo '<form method="post" action="' . esc_url( $action_url ) . '" style="display:inline-block;">';
			wp_nonce_field( 'edp_reject_booking_' . $order->get_id() );
			echo '<input type="hidden" name="action" value="edp_reject_booking" />';
			echo '<input type="hidden" name="order_id" value="' . esc_attr( $order->get_id() ) . '" />';
			echo '<button type="submit" class="button">' . esc_html__( 'Rifiuta', 'eredi-experience-booking' ) . '</button>';
			echo '</form>';
		}

		echo '<p class="edp-status-current">' . esc_html__( 'Stato attuale:', 'eredi-experience-booking' ) . ' <strong>' . esc_html( wc_get_order_status_name( $status ) ) . '</strong></p>';
		echo '</div>';

		echo '<style>
			.edp-order-summary{width:100%;border-collapse:collapse;margin-bottom:8px}
			.edp-order-summary th{text-align:left;width:40%;padding:3px 6px 3px 0;color:#002435}
			.edp-order-summary td{padding:3px 0}
			.edp-order-upsells{margin:0 0 8px;padding-left:16px}
			.edp-capacity{padding:6px 8px;background:#f9f7f3;border-left:3px solid #FECA91;margin:8px 0}
			.edp-order-actions{margin-top:10px}
			.edp-status-current{margin-top:8px}
		</style>';
	}

	/**
	 * Handle the confirm action.
	 */
	public function handle_confirm() {
		$this->handle_transition( OrderStatuses::CONFIRMED, 'edp_confirm_booking', __( 'Prenotazione confermata dall’amministrazione.', 'eredi-experience-booking' ) );
	}

	/**
	 * Handle the reject action.
	 */
	public function handle_reject() {
		$this->handle_transition( OrderStatuses::REJECTED, 'edp_reject_booking', __( 'Prenotazione rifiutata dall’amministrazione.', 'eredi-experience-booking' ) );
	}

	/**
	 * Shared transition handler.
	 *
	 * @param string $target_status New status.
	 * @param string $nonce_action  Nonce action prefix.
	 * @param string $note          Order note.
	 */
	private function handle_transition( $target_status, $nonce_action, $note ) {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'eredi-experience-booking' ) );
		}
		check_admin_referer( $nonce_action . '_' . $order_id );

		$order = $order_id ? wc_get_order( $order_id ) : null;
		if ( $order instanceof \WC_Order && $order->meta_exists( '_edp_booking' ) ) {
			$order->update_status( $target_status, $note );
		}

		$redirect = wp_get_referer();
		if ( ! $redirect && $order instanceof \WC_Order ) {
			$redirect = $order->get_edit_order_url();
		}
		wp_safe_redirect( $redirect ? $redirect : admin_url() );
		exit;
	}
}
