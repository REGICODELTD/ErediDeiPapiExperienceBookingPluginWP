<?php
/**
 * Server-side price calculation for a booking.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Pricing;

use ErediExperienceBooking\ProductType\ExperienceProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Computes booking totals. Everything is per person:
 *
 *   per_person = price_per_person + Σ selected_upsell.price_per_person
 *                                 + Σ selected_addon_option.extra_per_person
 *   total      = per_person × persons
 *
 * The selection is always validated against the product's stored configuration,
 * so a tampered client payload can never invent upsells, options or prices.
 */
class PriceCalculator {

	/**
	 * Build a fully validated breakdown of the booking cost.
	 *
	 * @param ExperienceProduct $product   The experience.
	 * @param int               $persons   Number of people.
	 * @param array             $selection List of selected upsells: [ ['index'=>int, 'options'=>int[]], ... ].
	 * @return array Structured breakdown (see inline shape).
	 */
	public function build_breakdown( ExperienceProduct $product, $persons, array $selection ) {
		$persons          = max( 1, (int) $persons );
		$price_per_person = round( (float) $product->get_price_per_person(), wc_get_price_decimals() );
		$config_upsells   = $product->get_upsells_config();

		$breakdown = array(
			'persons'          => $persons,
			'price_per_person' => $price_per_person,
			'base_total'       => round( $price_per_person * $persons, wc_get_price_decimals() ),
			'upsells'          => array(),
			'addons_total'     => 0.0,
		);

		$selected_map = $this->normalize_selection( $selection );

		foreach ( $selected_map as $upsell_index => $option_indexes ) {
			if ( ! isset( $config_upsells[ $upsell_index ] ) ) {
				continue; // Unknown upsell - ignore.
			}
			$config_upsell    = $config_upsells[ $upsell_index ];
			$upsell_pp        = round( (float) ( $config_upsell['price_per_person'] ?? 0 ), wc_get_price_decimals() );
			$upsell_line_total = round( $upsell_pp * $persons, wc_get_price_decimals() );

			$options_out      = array();
			$config_options   = isset( $config_upsell['options'] ) && is_array( $config_upsell['options'] ) ? $config_upsell['options'] : array();

			foreach ( $config_options as $opt_index => $config_option ) {
				$mode      = ( isset( $config_option['mode'] ) && 'addon' === $config_option['mode'] ) ? 'addon' : 'included';
				$selected  = in_array( $opt_index, $option_indexes, true );
				$extra_pp  = 'addon' === $mode ? round( (float) ( $config_option['extra_per_person'] ?? 0 ), wc_get_price_decimals() ) : 0.0;
				$line_total = ( $selected && 'addon' === $mode ) ? round( $extra_pp * $persons, wc_get_price_decimals() ) : 0.0;

				// "included" options always come with the upsell; "addon" options only when selected.
				$is_active = ( 'included' === $mode ) ? true : $selected;
				if ( ! $is_active ) {
					continue;
				}

				$options_out[] = array(
					'index'            => (int) $opt_index,
					'name'             => isset( $config_option['name'] ) ? (string) $config_option['name'] : '',
					'mode'             => $mode,
					'extra_per_person' => $extra_pp,
					'line_total'       => $line_total,
				);

				$breakdown['addons_total'] += $line_total;
			}

			$breakdown['upsells'][] = array(
				'index'            => (int) $upsell_index,
				'name'             => isset( $config_upsell['name'] ) ? (string) $config_upsell['name'] : '',
				'price_per_person' => $upsell_pp,
				'persons'          => $persons,
				'line_total'       => $upsell_line_total,
				'options'          => $options_out,
			);

			$breakdown['addons_total'] += $upsell_line_total;
		}

		$breakdown['addons_total']     = round( $breakdown['addons_total'], wc_get_price_decimals() );
		$breakdown['total']            = round( $breakdown['base_total'] + $breakdown['addons_total'], wc_get_price_decimals() );
		$breakdown['per_person_total'] = $persons > 0 ? round( $breakdown['total'] / $persons, wc_get_price_decimals() ) : 0.0;

		return $breakdown;
	}

	/**
	 * Convenience: total amount only.
	 *
	 * @param ExperienceProduct $product   The experience.
	 * @param int               $persons   Number of people.
	 * @param array             $selection Selection list.
	 * @return float
	 */
	public function total( ExperienceProduct $product, $persons, array $selection ) {
		$breakdown = $this->build_breakdown( $product, $persons, $selection );
		return (float) $breakdown['total'];
	}

	/**
	 * Normalize a raw selection list into [ upsell_index => int[] options ].
	 *
	 * @param array $selection Raw selection.
	 * @return array<int,int[]>
	 */
	private function normalize_selection( array $selection ) {
		$map = array();
		foreach ( $selection as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['index'] ) ) {
				continue;
			}
			$index   = (int) $entry['index'];
			$options = array();
			if ( isset( $entry['options'] ) && is_array( $entry['options'] ) ) {
				$options = array_values( array_unique( array_map( 'intval', $entry['options'] ) ) );
			}
			$map[ $index ] = $options;
		}
		return $map;
	}
}
