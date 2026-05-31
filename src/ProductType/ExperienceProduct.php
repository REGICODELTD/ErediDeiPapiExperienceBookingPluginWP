<?php
/**
 * "Experience" product type class.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\ProductType;

defined( 'ABSPATH' ) || exit;

/**
 * A bookable experience priced per person.
 *
 * Extends WC_Product directly (not WC_Product_Simple) so it is not treated as a
 * standard add-to-cart product: bookings are created as orders via the CRUD API.
 */
class ExperienceProduct extends \WC_Product {

	/**
	 * Product type identifier.
	 *
	 * @var string
	 */
	protected $product_type = 'experience';

	/**
	 * Return the internal product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'experience';
	}

	/**
	 * Per-person price tiers, ordered by ascending `min`.
	 * Falls back to a single tier synthesised from legacy meta.
	 *
	 * @return array[] Each: ['min'=>int,'max'=>int(0=unlimited),'price'=>float].
	 */
	public function get_price_tiers() {
		$tiers = $this->get_meta( '_edp_price_tiers' );
		if ( is_array( $tiers ) && ! empty( $tiers ) ) {
			return $this->normalize_tiers( $tiers );
		}

		// Backward-compatibility: synthesise one tier from the legacy fields.
		$legacy_price = (float) $this->get_meta( '_edp_price_per_person' );
		if ( $legacy_price > 0 ) {
			$legacy_min = (int) $this->get_meta( '_edp_min_persons' );
			return array(
				array(
					'min'   => $legacy_min > 0 ? $legacy_min : 1,
					'max'   => max( 0, (int) $this->get_meta( '_edp_max_persons' ) ),
					'price' => $legacy_price,
				),
			);
		}

		return array();
	}

	/**
	 * Resolve the per-person price for a given party size.
	 * Picks the tier containing `$persons`; on a gap, the highest tier whose
	 * `min` is ≤ `$persons`; otherwise the first tier.
	 *
	 * @param int $persons Party size.
	 * @return float
	 */
	public function resolve_price_per_person( $persons ) {
		$persons = max( 1, (int) $persons );
		$tiers   = $this->get_price_tiers();
		if ( empty( $tiers ) ) {
			return 0.0;
		}

		$chosen = $tiers[0];
		foreach ( $tiers as $tier ) {
			if ( $persons >= $tier['min'] ) {
				$chosen = $tier;
				if ( 0 === (int) $tier['max'] || $persons <= (int) $tier['max'] ) {
					return (float) $tier['price'];
				}
			}
		}
		return (float) $chosen['price'];
	}

	/**
	 * Lowest tier price (the "from" price), used for display and WC regular price.
	 *
	 * @return float
	 */
	public function get_from_price() {
		$tiers = $this->get_price_tiers();
		if ( empty( $tiers ) ) {
			return 0.0;
		}
		$min = null;
		foreach ( $tiers as $tier ) {
			$price = (float) $tier['price'];
			if ( null === $min || $price < $min ) {
				$min = $price;
			}
		}
		return (float) $min;
	}

	/**
	 * "From" price (kept for backward compatibility with earlier single-price code).
	 *
	 * @return float
	 */
	public function get_price_per_person() {
		return $this->get_from_price();
	}

	/**
	 * Free-text duration (e.g. "2 ore").
	 *
	 * @return string
	 */
	public function get_duration() {
		return (string) $this->get_meta( '_edp_duration' );
	}

	/**
	 * Minimum bookable people, derived from the first price tier.
	 *
	 * @return int
	 */
	public function get_min_persons() {
		$tiers = $this->get_price_tiers();
		if ( ! empty( $tiers ) ) {
			return max( 1, (int) $tiers[0]['min'] );
		}
		$min = (int) $this->get_meta( '_edp_min_persons' );
		return $min > 0 ? $min : 1;
	}

	/**
	 * Maximum bookable people derived from the tiers (0 = unlimited when the
	 * last tier is open-ended).
	 *
	 * @return int
	 */
	public function get_max_persons() {
		$tiers = $this->get_price_tiers();
		if ( ! empty( $tiers ) ) {
			$max = 0;
			foreach ( $tiers as $tier ) {
				if ( 0 === (int) $tier['max'] ) {
					return 0; // Open-ended tier -> unlimited.
				}
				$max = max( $max, (int) $tier['max'] );
			}
			return $max;
		}
		return max( 0, (int) $this->get_meta( '_edp_max_persons' ) );
	}

	/**
	 * Normalize + sort tier rows by ascending `min`.
	 *
	 * @param array $tiers Raw tiers.
	 * @return array[]
	 */
	private function normalize_tiers( $tiers ) {
		$out = array();
		foreach ( $tiers as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}
			$out[] = array(
				'min'   => isset( $tier['min'] ) ? max( 1, (int) $tier['min'] ) : 1,
				'max'   => isset( $tier['max'] ) ? max( 0, (int) $tier['max'] ) : 0,
				'price' => isset( $tier['price'] ) ? (float) $tier['price'] : 0.0,
			);
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return $a['min'] <=> $b['min'];
			}
		);
		return $out;
	}

	/**
	 * Availability configuration array.
	 *
	 * @return array
	 */
	public function get_availability_config() {
		$config = $this->get_meta( '_edp_availability' );
		return is_array( $config ) ? $config : array();
	}

	/**
	 * Upsells configuration array.
	 *
	 * @return array
	 */
	public function get_upsells_config() {
		$upsells = $this->get_meta( '_edp_upsells' );
		return is_array( $upsells ) ? $upsells : array();
	}
}
