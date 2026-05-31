<?php
/**
 * Persists the experience product fields.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Saves price/min/max, availability and upsells when an experience is saved.
 *
 * Runs inside WooCommerce's product save flow (nonce + capability already
 * verified by core). All values are sanitized before storage.
 */
class ProductDataSave {

	/**
	 * Hook into the product save.
	 */
	public function register() {
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save' ) );
	}

	/**
	 * Save handler.
	 *
	 * @param \WC_Product $product Typed product object.
	 */
	public function save( $product ) {
		if ( 'experience' !== $product->get_type() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- handled by WooCommerce core.
		$price = isset( $_POST['_edp_price_per_person'] ) ? wc_format_decimal( wp_unslash( $_POST['_edp_price_per_person'] ) ) : '';
		$min   = isset( $_POST['_edp_min_persons'] ) ? max( 1, absint( $_POST['_edp_min_persons'] ) ) : 1;
		$max   = isset( $_POST['_edp_max_persons'] ) ? absint( $_POST['_edp_max_persons'] ) : 0;

		$availability = $this->sanitize_availability( isset( $_POST['_edp_availability'] ) ? wp_unslash( $_POST['_edp_availability'] ) : array() );
		$upsells      = $this->sanitize_upsells( isset( $_POST['_edp_upsells'] ) ? wp_unslash( $_POST['_edp_upsells'] ) : array() );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$product->set_regular_price( '' !== $price ? $price : '' );
		$product->set_price( '' !== $price ? $price : '' );

		$product->update_meta_data( '_edp_price_per_person', '' !== $price ? $price : 0 );
		$product->update_meta_data( '_edp_min_persons', $min );
		$product->update_meta_data( '_edp_max_persons', $max );
		$product->update_meta_data( '_edp_availability', $availability );
		$product->update_meta_data( '_edp_upsells', $upsells );
	}

	/**
	 * Sanitize the availability payload.
	 *
	 * @param mixed $raw Raw posted array.
	 * @return array
	 */
	private function sanitize_availability( $raw ) {
		$raw  = is_array( $raw ) ? $raw : array();
		$mode = isset( $raw['mode'] ) && in_array( $raw['mode'], array( 'event', 'always', 'range' ), true ) ? $raw['mode'] : 'always';

		$weekly = array();
		if ( isset( $raw['weekly'] ) && is_array( $raw['weekly'] ) ) {
			for ( $wd = 0; $wd <= 6; $wd++ ) {
				$day            = isset( $raw['weekly'][ $wd ] ) ? $raw['weekly'][ $wd ] : array();
				$weekly[ $wd ]  = array(
					'open'    => ! empty( $day['open'] ),
					'windows' => $this->sanitize_windows( isset( $day['windows'] ) ? $day['windows'] : array() ),
				);
			}
		}

		return array(
			'mode'           => $mode,
			'event_date'     => $this->sanitize_date( isset( $raw['event_date'] ) ? $raw['event_date'] : '' ),
			'range_start'    => $this->sanitize_date( isset( $raw['range_start'] ) ? $raw['range_start'] : '' ),
			'range_end'      => $this->sanitize_date( isset( $raw['range_end'] ) ? $raw['range_end'] : '' ),
			'min_lead_hours' => isset( $raw['min_lead_hours'] ) ? absint( $raw['min_lead_hours'] ) : 0,
			'blackout_dates' => $this->sanitize_blackout( isset( $raw['blackout_dates'] ) ? $raw['blackout_dates'] : '' ),
			'event_windows'  => $this->sanitize_windows( isset( $raw['event_windows'] ) ? $raw['event_windows'] : array() ),
			'weekly'         => $weekly,
		);
	}

	/**
	 * Sanitize a list of window rows.
	 *
	 * @param mixed $rows Raw rows.
	 * @return array[]
	 */
	private function sanitize_windows( $rows ) {
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$clean = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$start    = $this->sanitize_time( isset( $row['start'] ) ? $row['start'] : '' );
			$end      = $this->sanitize_time( isset( $row['end'] ) ? $row['end'] : '' );
			$interval = isset( $row['interval'] ) ? absint( $row['interval'] ) : 0;
			if ( '' === $start || '' === $end || $interval <= 0 ) {
				continue;
			}
			$clean[] = array(
				'start'    => $start,
				'end'      => $end,
				'interval' => $interval,
				'max'      => isset( $row['max'] ) ? absint( $row['max'] ) : 0,
			);
		}
		return array_values( $clean );
	}

	/**
	 * Sanitize the upsells payload.
	 *
	 * @param mixed $raw Raw posted array.
	 * @return array[]
	 */
	private function sanitize_upsells( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$clean = array();
		foreach ( $raw as $upsell ) {
			if ( ! is_array( $upsell ) ) {
				continue;
			}
			$name = isset( $upsell['name'] ) ? sanitize_text_field( $upsell['name'] ) : '';
			if ( '' === $name ) {
				continue; // Skip nameless upsells.
			}
			$options = array();
			if ( isset( $upsell['options'] ) && is_array( $upsell['options'] ) ) {
				foreach ( $upsell['options'] as $option ) {
					if ( ! is_array( $option ) ) {
						continue;
					}
					$opt_name = isset( $option['name'] ) ? sanitize_text_field( $option['name'] ) : '';
					if ( '' === $opt_name ) {
						continue;
					}
					$mode      = ( isset( $option['mode'] ) && 'addon' === $option['mode'] ) ? 'addon' : 'included';
					$extra     = ( 'addon' === $mode && isset( $option['extra_per_person'] ) ) ? (float) wc_format_decimal( $option['extra_per_person'] ) : 0.0;
					$options[] = array(
						'name'             => $opt_name,
						'mode'             => $mode,
						'extra_per_person' => $extra,
					);
				}
			}
			$clean[] = array(
				'name'             => $name,
				'price_per_person' => isset( $upsell['price_per_person'] ) ? (float) wc_format_decimal( $upsell['price_per_person'] ) : 0.0,
				'options'          => array_values( $options ),
			);
		}
		return array_values( $clean );
	}

	/**
	 * Sanitize a textarea of blackout dates (one per line).
	 *
	 * @param string $raw Raw textarea.
	 * @return string[]
	 */
	private function sanitize_blackout( $raw ) {
		$lines = preg_split( '/[\r\n,]+/', (string) $raw );
		$dates = array();
		foreach ( (array) $lines as $line ) {
			$date = $this->sanitize_date( trim( $line ) );
			if ( '' !== $date ) {
				$dates[] = $date;
			}
		}
		return array_values( array_unique( $dates ) );
	}

	/**
	 * Validate a Y-m-d date; returns '' if invalid.
	 *
	 * @param string $date Raw date.
	 * @return string
	 */
	private function sanitize_date( $date ) {
		$date = trim( (string) $date );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return '';
		}
		$parts = explode( '-', $date );
		return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) ? $date : '';
	}

	/**
	 * Validate and normalize a HH:MM time; returns '' if invalid.
	 *
	 * @param string $time Raw time.
	 * @return string
	 */
	private function sanitize_time( $time ) {
		$time = trim( (string) $time );
		if ( ! preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $m ) ) {
			return '';
		}
		return sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] );
	}
}
