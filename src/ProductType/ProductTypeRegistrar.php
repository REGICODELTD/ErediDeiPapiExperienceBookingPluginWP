<?php
/**
 * Registers the "experience" product type with WooCommerce.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\ProductType;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the product type to the selector and maps it to its class.
 */
class ProductTypeRegistrar {

	/**
	 * Hook into WooCommerce.
	 */
	public function register() {
		add_filter( 'product_type_selector', array( $this, 'add_type_to_selector' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'map_product_class' ), 10, 2 );
	}

	/**
	 * Add "Esperienza" to the product type dropdown.
	 *
	 * @param array $types Existing product types.
	 * @return array
	 */
	public function add_type_to_selector( $types ) {
		$types['experience'] = __( 'Esperienza', 'eredi-experience-booking' );
		return $types;
	}

	/**
	 * Map the "experience" type to the ExperienceProduct class.
	 *
	 * @param string $classname   Default class name.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public function map_product_class( $classname, $product_type ) {
		if ( 'experience' === $product_type ) {
			return ExperienceProduct::class;
		}
		return $classname;
	}
}
