<?php
/**
 * Registers the Elementor widget + category (no-op when Elementor is inactive).
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the booking widget into Elementor. The hooks below only fire when
 * Elementor is loaded, so BookingWidget (which extends an Elementor base class)
 * is never referenced without Elementor present.
 */
class ElementorRegistrar {

	/**
	 * Hook in.
	 */
	public function register() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Add a dedicated widget category.
	 *
	 * @param \Elementor\Elements_Manager $manager Elements manager.
	 */
	public function add_category( $manager ) {
		$manager->add_category(
			'edp',
			array(
				'title' => __( 'Eredi dei Papi', 'eredi-experience-booking' ),
				'icon'  => 'eicon-calendar',
			)
		);
	}

	/**
	 * Register the widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function register_widget( $widgets_manager ) {
		$widgets_manager->register( new BookingWidget() );
	}
}
