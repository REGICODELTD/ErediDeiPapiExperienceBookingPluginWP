<?php
/**
 * Elementor widget: Experience booking.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

use ErediExperienceBooking\ProductType\ExperienceProduct;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

defined( 'ABSPATH' ) || exit;

/**
 * A fully style-controllable widget that prints the booking summary + button.
 * The booking modal itself is rendered once in the footer and shared.
 */
class BookingWidget extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'edp_experience_booking';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Prenotazione Esperienza', 'eredi-experience-booking' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-calendar';
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'edp' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'prenota', 'booking', 'esperienza', 'experience', 'edp' );
	}

	/**
	 * Front-end script dependency.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( Assets::HANDLE );
	}

	/**
	 * Front-end style dependency.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( Assets::HANDLE );
	}

	/**
	 * Build the list of experiences for the picker.
	 *
	 * @return array<int,string>
	 */
	private function experience_options() {
		$options  = array();
		$products = wc_get_products(
			array(
				'type'   => 'experience',
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'objects',
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);
		foreach ( $products as $product ) {
			$options[ $product->get_id() ] = $product->get_name() . ' (#' . $product->get_id() . ')';
		}
		return $options;
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		/* ---------- Content ---------- */
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Esperienza', 'eredi-experience-booking' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => __( 'Esperienza da mostrare', 'eredi-experience-booking' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => __( 'Prodotto corrente (pagina esperienza)', 'eredi-experience-booking' ),
					'select' => __( 'Scegli un’esperienza', 'eredi-experience-booking' ),
					'id'     => __( 'ID prodotto manuale', 'eredi-experience-booking' ),
				),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'     => __( 'Esperienza', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::SELECT2,
				'options'   => $this->experience_options(),
				'condition' => array( 'source' => 'select' ),
			)
		);

		$this->add_control(
			'manual_id',
			array(
				'label'     => __( 'ID prodotto', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::NUMBER,
				'condition' => array( 'source' => 'id' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => __( 'Testo pulsante', 'eredi-experience-booking' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Prenota ora', 'eredi-experience-booking' ),
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Mostra prezzo a persona', 'eredi-experience-booking' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'price_prefix',
			array(
				'label'     => __( 'Prefisso prezzo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'da', 'eredi-experience-booking' ),
				'condition' => array( 'show_price' => 'yes' ),
			)
		);

		$this->add_control(
			'price_suffix',
			array(
				'label'     => __( 'Suffisso prezzo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'a persona', 'eredi-experience-booking' ),
				'condition' => array( 'show_price' => 'yes' ),
			)
		);

		$this->add_control(
			'show_persons',
			array(
				'label'        => __( 'Mostra range persone', 'eredi-experience-booking' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		/* ---------- Style: box ---------- */
		$this->start_controls_section(
			'box_style',
			array(
				'label' => __( 'Contenitore', 'eredi-experience-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'box_bg',
			array(
				'label'     => __( 'Sfondo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .edp-widget' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'box_padding',
			array(
				'label'      => __( 'Padding', 'eredi-experience-booking' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .edp-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'box_border',
				'selector' => '{{WRAPPER}} .edp-widget',
			)
		);

		$this->add_responsive_control(
			'box_radius',
			array(
				'label'      => __( 'Raggio bordo', 'eredi-experience-booking' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .edp-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		/* ---------- Style: price ---------- */
		$this->start_controls_section(
			'price_style',
			array(
				'label' => __( 'Prezzo', 'eredi-experience-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Colore', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .edp-price' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'selector' => '{{WRAPPER}} .edp-price-amount',
			)
		);

		$this->end_controls_section();

		/* ---------- Style: button ---------- */
		$this->start_controls_section(
			'button_style',
			array(
				'label' => __( 'Pulsante', 'eredi-experience-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .edp-book-btn',
			)
		);

		$this->start_controls_tabs( 'button_tabs' );

		$this->start_controls_tab( 'button_normal', array( 'label' => __( 'Normale', 'eredi-experience-booking' ) ) );
		$this->add_control(
			'button_color',
			array(
				'label'     => __( 'Colore testo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .edp-book-btn' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_bg',
			array(
				'label'     => __( 'Sfondo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .edp-book-btn' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'button_hover', array( 'label' => __( 'Hover', 'eredi-experience-booking' ) ) );
		$this->add_control(
			'button_color_hover',
			array(
				'label'     => __( 'Colore testo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .edp-book-btn:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_bg_hover',
			array(
				'label'     => __( 'Sfondo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .edp-book-btn:hover' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'button_border',
				'selector'  => '{{WRAPPER}} .edp-book-btn',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'button_radius',
			array(
				'label'      => __( 'Raggio bordo', 'eredi-experience-booking' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .edp-book-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Padding', 'eredi-experience-booking' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .edp-book-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Resolve the experience product to display.
	 *
	 * @param array $settings Widget settings.
	 * @return ExperienceProduct|null
	 */
	private function resolve_product( array $settings ) {
		$source = isset( $settings['source'] ) ? $settings['source'] : 'auto';
		$id     = 0;

		if ( 'select' === $source ) {
			$id = (int) ( $settings['product_id'] ?? 0 );
		} elseif ( 'id' === $source ) {
			$id = (int) ( $settings['manual_id'] ?? 0 );
		} else {
			global $product;
			if ( $product instanceof \WC_Product ) {
				$id = $product->get_id();
			} else {
				$queried = get_queried_object_id();
				if ( $queried && 'product' === get_post_type( $queried ) ) {
					$id = $queried;
				}
			}
		}

		$resolved = $id ? wc_get_product( $id ) : null;
		return $resolved instanceof ExperienceProduct ? $resolved : null;
	}

	/**
	 * Render the widget.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$product  = $this->resolve_product( $settings );

		if ( ! $product instanceof ExperienceProduct ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo WidgetView::placeholder( __( 'Seleziona un’esperienza valida nelle impostazioni del widget, oppure usa il widget in una pagina prodotto di tipo Esperienza.', 'eredi-experience-booking' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			return;
		}

		echo WidgetView::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes.
			$product,
			array(
				'show_price'   => 'yes' === ( $settings['show_price'] ?? 'yes' ),
				'show_persons' => 'yes' === ( $settings['show_persons'] ?? 'yes' ),
				'button_text'  => $settings['button_text'] ?? __( 'Prenota ora', 'eredi-experience-booking' ),
				'price_prefix' => $settings['price_prefix'] ?? '',
				'price_suffix' => $settings['price_suffix'] ?? '',
			)
		);
	}
}
