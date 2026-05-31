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
	 * Price charged per person.
	 *
	 * @return float
	 */
	public function get_price_per_person() {
		return (float) $this->get_meta( '_edp_price_per_person' );
	}

	/**
	 * Minimum number of people per booking.
	 *
	 * @return int
	 */
	public function get_min_persons() {
		$min = (int) $this->get_meta( '_edp_min_persons' );
		return $min > 0 ? $min : 1;
	}

	/**
	 * Maximum number of people per booking (0 = unlimited).
	 *
	 * @return int
	 */
	public function get_max_persons() {
		return max( 0, (int) $this->get_meta( '_edp_max_persons' ) );
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
