<?php
/**
 * Collects the experiences rendered on the current page so the modal JS can
 * read their configuration without an extra request.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

use ErediExperienceBooking\Availability\AvailabilityService;
use ErediExperienceBooking\ProductType\ExperienceProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Per-request store of experience configs keyed by product id.
 */
class Registry {

	/**
	 * Collected experiences.
	 *
	 * @var array<int,array>
	 */
	private static $experiences = array();

	/**
	 * Add an experience to the registry (idempotent).
	 *
	 * @param ExperienceProduct $product Experience.
	 */
	public static function add( ExperienceProduct $product ) {
		$id = $product->get_id();
		if ( isset( self::$experiences[ $id ] ) ) {
			return;
		}
		self::$experiences[ $id ] = self::build_config( $product );
	}

	/**
	 * Whether anything was registered.
	 *
	 * @return bool
	 */
	public static function has() {
		return ! empty( self::$experiences );
	}

	/**
	 * All registered configs.
	 *
	 * @return array<int,array>
	 */
	public static function all() {
		return self::$experiences;
	}

	/**
	 * Build the JS-facing config for an experience.
	 *
	 * @param ExperienceProduct $product Experience.
	 * @return array
	 */
	private static function build_config( ExperienceProduct $product ) {
		$availability = ( new AvailabilityService() )->get_frontend_availability( $product );

		$upsells = array();
		foreach ( $product->get_upsells_config() as $upsell ) {
			$options = array();
			foreach ( ( isset( $upsell['options'] ) && is_array( $upsell['options'] ) ? $upsell['options'] : array() ) as $option ) {
				$options[] = array(
					'name'             => isset( $option['name'] ) ? $option['name'] : '',
					'mode'             => ( isset( $option['mode'] ) && 'addon' === $option['mode'] ) ? 'addon' : 'included',
					'extra_per_person' => isset( $option['extra_per_person'] ) ? (float) $option['extra_per_person'] : 0.0,
				);
			}
			$upsells[] = array(
				'name'             => isset( $upsell['name'] ) ? $upsell['name'] : '',
				'price_per_person' => isset( $upsell['price_per_person'] ) ? (float) $upsell['price_per_person'] : 0.0,
				'options'          => $options,
			);
		}

		return array(
			'id'               => $product->get_id(),
			'name'             => $product->get_name(),
			'price_per_person' => (float) $product->get_price_per_person(),
			'min_persons'      => $product->get_min_persons(),
			'max_persons'      => $product->get_max_persons(),
			'upsells'          => $upsells,
			'availability'     => $availability,
		);
	}
}
