<?php
/**
 * Availability + slot generation for experiences.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Availability;

use ErediExperienceBooking\ProductType\ExperienceProduct;
use ErediExperienceBooking\Status\OrderStatuses;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves which dates/time-slots are bookable for an experience and how much
 * capacity remains (counting ONLY confirmed bookings, per product decision).
 *
 * Weekday indexing used throughout: 0 = Monday … 6 = Sunday.
 */
class AvailabilityService {

	/**
	 * How many months ahead an "always available" experience can be booked.
	 */
	const DEFAULT_HORIZON_MONTHS = 12;

	/**
	 * Today's date in the site timezone (Y-m-d).
	 *
	 * @return string
	 */
	private function today() {
		return wp_date( 'Y-m-d' );
	}

	/**
	 * Current timestamp in the site timezone.
	 *
	 * @return int
	 */
	private function now_ts() {
		return (int) ( current_datetime()->getTimestamp() );
	}

	/**
	 * Weekday index (0=Mon..6=Sun) for a Y-m-d date (timezone-independent).
	 *
	 * @param string $date Date string.
	 * @return int
	 */
	private function weekday_index( $date ) {
		$dt = \DateTime::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		if ( ! $dt ) {
			return 0;
		}
		return (int) $dt->format( 'N' ) - 1; // N: 1=Mon..7=Sun.
	}

	/**
	 * UTC timestamp for a local date + HH:MM in the site timezone.
	 *
	 * @param string $date Y-m-d.
	 * @param string $time HH:MM.
	 * @return int
	 */
	private function slot_timestamp( $date, $time ) {
		$dt = \DateTime::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, wp_timezone() );
		return $dt ? $dt->getTimestamp() : (int) strtotime( $date . ' ' . $time );
	}

	/**
	 * Return the availability config with safe defaults.
	 *
	 * @param ExperienceProduct $product Experience.
	 * @return array
	 */
	private function config( ExperienceProduct $product ) {
		$config = $product->get_availability_config();

		return wp_parse_args(
			$config,
			array(
				'mode'           => 'always',
				'event_date'     => '',
				'range_start'    => '',
				'range_end'      => '',
				'weekly'         => array(),
				'event_windows'  => array(),
				'blackout_dates' => array(),
				'min_lead_hours' => 0,
			)
		);
	}

	/**
	 * Windows applicable on a given date (empty when the date is not open).
	 *
	 * @param ExperienceProduct $product Experience.
	 * @param string            $date    Y-m-d.
	 * @return array[] List of window arrays.
	 */
	public function get_windows_for_date( ExperienceProduct $product, $date ) {
		$config = $this->config( $product );

		if ( 'event' === $config['mode'] ) {
			if ( $date === $config['event_date'] ) {
				return $this->clean_windows( $config['event_windows'] );
			}
			return array();
		}

		if ( 'range' === $config['mode'] ) {
			if ( $config['range_start'] && $date < $config['range_start'] ) {
				return array();
			}
			if ( $config['range_end'] && $date > $config['range_end'] ) {
				return array();
			}
		}

		// always | range -> weekly schedule.
		$weekday = $this->weekday_index( $date );
		$weekly  = isset( $config['weekly'][ $weekday ] ) ? $config['weekly'][ $weekday ] : null;
		if ( ! $weekly || empty( $weekly['open'] ) ) {
			return array();
		}
		return $this->clean_windows( isset( $weekly['windows'] ) ? $weekly['windows'] : array() );
	}

	/**
	 * Whether a date can be booked at all (has windows, not blackout, not past, within lead time).
	 *
	 * @param ExperienceProduct $product Experience.
	 * @param string            $date    Y-m-d.
	 * @return bool
	 */
	public function is_date_bookable( ExperienceProduct $product, $date ) {
		if ( ! $this->is_valid_date( $date ) ) {
			return false;
		}
		if ( $date < $this->today() ) {
			return false;
		}
		$config = $this->config( $product );
		if ( in_array( $date, (array) $config['blackout_dates'], true ) ) {
			return false;
		}
		$windows = $this->get_windows_for_date( $product, $date );
		if ( empty( $windows ) ) {
			return false;
		}
		// At least one slot must still satisfy the lead time.
		$slots = $this->get_slots_for_date( $product, $date );
		foreach ( $slots as $slot ) {
			if ( $slot['available'] && ! $slot['past_lead'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate all slots for a date with capacity info.
	 *
	 * @param ExperienceProduct $product Experience.
	 * @param string            $date    Y-m-d.
	 * @return array[] Each: time,label,max,confirmed,remaining,available,past_lead.
	 */
	public function get_slots_for_date( ExperienceProduct $product, $date ) {
		$windows = $this->get_windows_for_date( $product, $date );
		if ( empty( $windows ) ) {
			return array();
		}

		$config         = $this->config( $product );
		$lead_seconds   = max( 0, (int) $config['min_lead_hours'] ) * HOUR_IN_SECONDS;
		$confirmed_map  = $this->count_confirmed_by_slot( $product->get_id(), $date );
		$now_ts         = $this->now_ts();
		$slots          = array();
		$seen           = array();

		foreach ( $windows as $window ) {
			foreach ( $this->window_times( $window ) as $time ) {
				if ( isset( $seen[ $time ] ) ) {
					continue; // De-duplicate overlapping windows.
				}
				$seen[ $time ] = true;

				$max       = isset( $window['max'] ) ? max( 0, (int) $window['max'] ) : 0;
				$confirmed = isset( $confirmed_map[ $time ] ) ? (int) $confirmed_map[ $time ] : 0;
				$remaining = $max > 0 ? max( 0, $max - $confirmed ) : null; // null = unlimited.
				$slot_ts   = $this->slot_timestamp( $date, $time );
				$past_lead = ( $slot_ts - $lead_seconds ) <= $now_ts;
				$available = ( null === $remaining || $remaining > 0 ) && ! $past_lead;

				$slots[] = array(
					'time'      => $time,
					'label'     => $time,
					'max'       => $max,
					'confirmed' => $confirmed,
					'remaining' => $remaining,
					'available' => $available,
					'past_lead' => $past_lead,
				);
			}
		}

		usort(
			$slots,
			static function ( $a, $b ) {
				return strcmp( $a['time'], $b['time'] );
			}
		);

		return $slots;
	}

	/**
	 * Constraints used by the front-end date picker.
	 *
	 * @param ExperienceProduct $product Experience.
	 * @return array
	 */
	public function get_frontend_availability( ExperienceProduct $product ) {
		$config = $this->config( $product );
		$today  = $this->today();

		$data = array(
			'mode'             => $config['mode'],
			'min_date'         => $today,
			'max_date'         => null,
			'allowed_dates'    => null,
			'enabled_weekdays' => null,
			'blackout_dates'   => array_values( array_filter( (array) $config['blackout_dates'], array( $this, 'is_valid_date' ) ) ),
			'min_lead_hours'   => max( 0, (int) $config['min_lead_hours'] ),
		);

		if ( 'event' === $config['mode'] ) {
			$event_date            = $this->is_valid_date( $config['event_date'] ) ? $config['event_date'] : '';
			$data['allowed_dates'] = $event_date ? array( $event_date ) : array();
			$data['min_date']      = $event_date ?: $today;
			$data['max_date']      = $event_date ?: $today;
			return $data;
		}

		// always | range: compute enabled weekdays from the weekly schedule.
		$enabled = array();
		for ( $wd = 0; $wd <= 6; $wd++ ) {
			$day = isset( $config['weekly'][ $wd ] ) ? $config['weekly'][ $wd ] : null;
			if ( $day && ! empty( $day['open'] ) && ! empty( $this->clean_windows( isset( $day['windows'] ) ? $day['windows'] : array() ) ) ) {
				$enabled[] = $wd;
			}
		}
		$data['enabled_weekdays'] = $enabled;

		if ( 'range' === $config['mode'] ) {
			$start            = $this->is_valid_date( $config['range_start'] ) ? $config['range_start'] : $today;
			$data['min_date'] = max( $start, $today );
			$data['max_date'] = $this->is_valid_date( $config['range_end'] ) ? $config['range_end'] : null;
		} else {
			$data['max_date'] = wp_date( 'Y-m-d', strtotime( '+' . self::DEFAULT_HORIZON_MONTHS . ' months', strtotime( $today ) ) );
		}

		return $data;
	}

	/**
	 * Count confirmed bookings on a date grouped by slot time.
	 *
	 * @param int    $experience_id Experience product id.
	 * @param string $date          Y-m-d.
	 * @return array<string,int> Map of HH:MM => count.
	 */
	public function count_confirmed_by_slot( $experience_id, $date ) {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'type'       => 'shop_order',
				'status'     => array( OrderStatuses::CONFIRMED ),
				'return'     => 'objects',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => '_edp_experience_id',
						'value' => (int) $experience_id,
					),
					array(
						'key'   => '_edp_booking_date',
						'value' => $date,
					),
				),
			)
		);

		$map = array();
		foreach ( $orders as $order ) {
			$slot = (string) $order->get_meta( '_edp_booking_slot' );
			if ( '' === $slot ) {
				continue;
			}
			$map[ $slot ] = isset( $map[ $slot ] ) ? $map[ $slot ] + 1 : 1;
		}
		return $map;
	}

	/**
	 * Count confirmed bookings for a single slot, optionally excluding one order.
	 *
	 * @param int    $experience_id  Experience id.
	 * @param string $date           Y-m-d.
	 * @param string $slot           HH:MM.
	 * @param int    $exclude_order  Order id to exclude (e.g. the one being confirmed).
	 * @return int
	 */
	public function count_confirmed_for_slot( $experience_id, $date, $slot, $exclude_order = 0 ) {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'type'       => 'shop_order',
				'status'     => array( OrderStatuses::CONFIRMED ),
				'return'     => 'ids',
				'exclude'    => $exclude_order ? array( (int) $exclude_order ) : array(),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => '_edp_experience_id',
						'value' => (int) $experience_id,
					),
					array(
						'key'   => '_edp_booking_date',
						'value' => $date,
					),
					array(
						'key'   => '_edp_booking_slot',
						'value' => $slot,
					),
				),
			)
		);
		return count( $orders );
	}

	/**
	 * Slot max-capacity configured for a given date/time (0 = unlimited).
	 *
	 * @param ExperienceProduct $product Experience.
	 * @param string            $date    Y-m-d.
	 * @param string            $slot    HH:MM.
	 * @return int|null Max capacity, or null if the slot is not configured.
	 */
	public function get_slot_max( ExperienceProduct $product, $date, $slot ) {
		foreach ( $this->get_slots_for_date( $product, $date ) as $generated ) {
			if ( $generated['time'] === $slot ) {
				return (int) $generated['max'];
			}
		}
		return null;
	}

	/**
	 * Generate the start-times for a single window.
	 *
	 * @param array $window Window array (start,end,interval).
	 * @return string[] List of HH:MM.
	 */
	private function window_times( array $window ) {
		$start    = $this->normalize_time( $window['start'] ?? '' );
		$end      = $this->normalize_time( $window['end'] ?? '' );
		$interval = isset( $window['interval'] ) ? (int) $window['interval'] : 0;

		if ( '' === $start || '' === $end || $interval <= 0 ) {
			return array();
		}

		$start_min = $this->minutes( $start );
		$end_min   = $this->minutes( $end );
		if ( $end_min <= $start_min ) {
			return array();
		}

		$times = array();
		for ( $m = $start_min; $m < $end_min; $m += $interval ) {
			$times[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
		}
		return $times;
	}

	/**
	 * Sanitize a list of window arrays.
	 *
	 * @param mixed $windows Raw windows.
	 * @return array[]
	 */
	private function clean_windows( $windows ) {
		if ( ! is_array( $windows ) ) {
			return array();
		}
		$clean = array();
		foreach ( $windows as $window ) {
			if ( ! is_array( $window ) ) {
				continue;
			}
			$start    = $this->normalize_time( $window['start'] ?? '' );
			$end      = $this->normalize_time( $window['end'] ?? '' );
			$interval = isset( $window['interval'] ) ? (int) $window['interval'] : 0;
			if ( '' === $start || '' === $end || $interval <= 0 ) {
				continue;
			}
			$clean[] = array(
				'start'    => $start,
				'end'      => $end,
				'interval' => $interval,
				'max'      => isset( $window['max'] ) ? max( 0, (int) $window['max'] ) : 0,
			);
		}
		return $clean;
	}

	/**
	 * Normalize a HH:MM time string; returns '' if invalid.
	 *
	 * @param string $time Raw time.
	 * @return string
	 */
	private function normalize_time( $time ) {
		$time = trim( (string) $time );
		if ( ! preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $m ) ) {
			return '';
		}
		return sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] );
	}

	/**
	 * Convert HH:MM to minutes-from-midnight.
	 *
	 * @param string $time HH:MM.
	 * @return int
	 */
	private function minutes( $time ) {
		list( $h, $i ) = array_map( 'intval', explode( ':', $time ) );
		return ( $h * 60 ) + $i;
	}

	/**
	 * Validate a Y-m-d date string.
	 *
	 * @param string $date Date.
	 * @return bool
	 */
	public function is_valid_date( $date ) {
		if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = explode( '-', $date );
		return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
	}
}
