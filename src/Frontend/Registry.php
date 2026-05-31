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
	 * Add an experience to the registry (idempotent — first write wins).
	 *
	 * When several booking widgets target the same product on one page, the
	 * first one rendered defines the shared modal's look & copy for that
	 * experience (the modal element itself is a single shared node).
	 *
	 * @param ExperienceProduct $product Experience.
	 * @param array             $modal   Per-experience modal copy + style
	 *                                   (already sanitized by the caller).
	 */
	public static function add( ExperienceProduct $product, array $modal = array() ) {
		$id = $product->get_id();
		if ( isset( self::$experiences[ $id ] ) ) {
			return;
		}
		self::$experiences[ $id ] = self::build_config( $product, $modal );
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
	 * @param array             $modal   Per-experience modal copy + style.
	 * @return array
	 */
	private static function build_config( ExperienceProduct $product, array $modal = array() ) {
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

		$tiers = array();
		foreach ( $product->get_price_tiers() as $tier ) {
			$tiers[] = array(
				'min'   => (int) $tier['min'],
				'max'   => (int) $tier['max'],
				'price' => (float) $tier['price'],
			);
		}

		return array(
			'id'               => $product->get_id(),
			'name'             => $product->get_name(),
			'tiers'            => $tiers,
			'from_price'       => (float) $product->get_from_price(),
			'price_per_person' => (float) $product->get_from_price(),
			'duration'         => $product->get_duration(),
			'min_persons'      => $product->get_min_persons(),
			'max_persons'      => $product->get_max_persons(),
			'upsells'          => $upsells,
			'availability'     => $availability,
			'modal'            => $modal,
		);
	}
}
