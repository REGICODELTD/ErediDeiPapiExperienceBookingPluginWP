<?php
/**
 * Custom booking order statuses.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Status;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and labels the booking order statuses.
 */
class OrderStatuses {

	const PENDING   = 'edp-pending';
	const CONFIRMED = 'edp-confirmed';
	const REJECTED  = 'edp-rejected';

	/**
	 * Hook into WordPress / WooCommerce.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_statuses' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_to_order_statuses' ) );
		// Treat confirmed bookings like a "real" sale for reports/exports.
		add_filter( 'woocommerce_reports_order_statuses', array( $this, 'add_to_report_statuses' ) );
	}

	/**
	 * Map of status key (with wc- prefix) to label.
	 *
	 * @return array<string,string>
	 */
	public static function labels() {
		return array(
			'wc-' . self::PENDING   => _x( 'Prenotazione: In attesa', 'Order status', 'eredi-experience-booking' ),
			'wc-' . self::CONFIRMED => _x( 'Prenotazione: Confermata', 'Order status', 'eredi-experience-booking' ),
			'wc-' . self::REJECTED  => _x( 'Prenotazione: Rifiutata', 'Order status', 'eredi-experience-booking' ),
		);
	}

	/**
	 * Register the custom post statuses.
	 */
	public function register_post_statuses() {
		$definitions = array(
			self::PENDING   => _x( 'Prenotazione: In attesa', 'Order status', 'eredi-experience-booking' ),
			self::CONFIRMED => _x( 'Prenotazione: Confermata', 'Order status', 'eredi-experience-booking' ),
			self::REJECTED  => _x( 'Prenotazione: Rifiutata', 'Order status', 'eredi-experience-booking' ),
		);

		foreach ( $definitions as $key => $label ) {
			register_post_status(
				'wc-' . $key,
				array(
					'label'                     => $label,
					'public'                    => false,
					'internal'                  => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders */
					'label_count'               => _n_noop(
						$label . ' <span class="count">(%s)</span>',
						$label . ' <span class="count">(%s)</span>',
						'eredi-experience-booking'
					),
				)
			);
		}
	}

	/**
	 * Add the statuses to the WooCommerce order status dropdown/lists.
	 *
	 * @param array $statuses Existing statuses.
	 * @return array
	 */
	public function add_to_order_statuses( $statuses ) {
		// Insert our statuses right after "pending payment" for a natural ordering.
		$new = array();
		foreach ( $statuses as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'wc-pending' === $key ) {
				foreach ( self::labels() as $edp_key => $edp_label ) {
					$new[ $edp_key ] = $edp_label;
				}
			}
		}
		// In case wc-pending was absent, ensure ours are present.
		foreach ( self::labels() as $edp_key => $edp_label ) {
			if ( ! isset( $new[ $edp_key ] ) ) {
				$new[ $edp_key ] = $edp_label;
			}
		}
		return $new;
	}

	/**
	 * Include confirmed bookings in WooCommerce reports.
	 *
	 * @param array $statuses Report statuses (without wc- prefix).
	 * @return array
	 */
	public function add_to_report_statuses( $statuses ) {
		$statuses[] = self::CONFIRMED;
		return $statuses;
	}
}
