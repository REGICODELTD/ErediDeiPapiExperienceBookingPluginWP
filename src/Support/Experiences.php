<?php
/**
 * Shared queries for experience products.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Small helper to list experience products (reused by the Elementor widget
 * picker and the "copy settings" tool).
 */
class Experiences {

	/**
	 * Build an id => label map of experience products.
	 *
	 * @param int      $exclude  Product id to exclude (e.g. the current one).
	 * @param string[] $statuses Post statuses to include.
	 * @return array<int,string>
	 */
	public static function options( $exclude = 0, $statuses = array( 'publish' ) ) {
		$exclude  = (int) $exclude;
		$options  = array();
		$products = wc_get_products(
			array(
				'type'    => 'experience',
				'status'  => $statuses,
				'limit'   => -1,
				'return'  => 'objects',
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);

		foreach ( $products as $product ) {
			if ( $exclude && (int) $product->get_id() === $exclude ) {
				continue;
			}
			$options[ $product->get_id() ] = $product->get_name() . ' (#' . $product->get_id() . ')';
		}

		return $options;
	}
}
