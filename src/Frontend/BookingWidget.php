<?php
/**
 * Elementor widget: Experience booking.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Frontend;

use ErediExperienceBooking\ProductType\ExperienceProduct;
use ErediExperienceBooking\Support\Css;
use ErediExperienceBooking\Support\Experiences;
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
		return Experiences::options();
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

		/* ---------- Content: modal copy ---------- */
		$this->start_controls_section(
			'modal_text_section',
			array(
				'label' => __( 'Modale: testi', 'eredi-experience-booking' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'modal_submit_text',
			array(
				'label'   => __( 'Pulsante invio', 'eredi-experience-booking' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Invia richiesta di prenotazione', 'eredi-experience-booking' ),
			)
		);

		$this->add_control(
			'modal_success_close_text',
			array(
				'label'   => __( 'Pulsante chiudi (conferma)', 'eredi-experience-booking' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Chiudi', 'eredi-experience-booking' ),
			)
		);

		$this->add_control(
			'modal_close_aria',
			array(
				'label'       => __( 'Etichetta chiusura (accessibilità)', 'eredi-experience-booking' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Chiudi', 'eredi-experience-booking' ),
				'description' => __( 'Testo per lettori di schermo sulla “×”.', 'eredi-experience-booking' ),
			)
		);

		$this->add_control(
			'modal_upsells_heading',
			array(
				'label'   => __( 'Titolo sezione upsell', 'eredi-experience-booking' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Upsell & allestimenti', 'eredi-experience-booking' ),
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

		/* ---------- Style: modal ---------- */
		$this->start_controls_section(
			'modal_style_section',
			array(
				'label' => __( 'Modale: stile', 'eredi-experience-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// Overlay is applied on the live modal via JS; the inline editor preview has no overlay.
		$this->add_control(
			'modal_overlay_color',
			array(
				'label'   => __( 'Sfondo overlay', 'eredi-experience-booking' ),
				'type'    => Controls_Manager::COLOR,
				'default' => 'rgba(0, 36, 53, 0.55)',
			)
		);

		$this->add_control(
			'modal_dialog_bg',
			array(
				'label'     => __( 'Sfondo finestra', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f9f7f3',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-cream: {{VALUE}};' ),
			)
		);

		// Drives the modal's primary accent (title, price amounts, success text, focus ring).
		$this->add_control(
			'modal_title_color',
			array(
				'label'     => __( 'Colore primario (titolo, accenti)', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#002435',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-teal: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'modal_body_color',
			array(
				'label'     => __( 'Colore testo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1e1e1e',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-ink: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'modal_border_color',
			array(
				'label'     => __( 'Colore bordi campi', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e6e1d8',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'modal_btn_heading',
			array(
				'label'     => __( 'Pulsante invio', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs( 'modal_btn_tabs' );

		$this->start_controls_tab( 'modal_btn_normal', array( 'label' => __( 'Normale', 'eredi-experience-booking' ) ) );
		$this->add_control(
			'modal_btn_bg',
			array(
				'label'     => __( 'Sfondo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#002435',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-btn-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'modal_btn_text',
			array(
				'label'     => __( 'Colore testo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-btn-text: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'modal_btn_hover_tab', array( 'label' => __( 'Hover', 'eredi-experience-booking' ) ) );
		$this->add_control(
			'modal_btn_bg_hover',
			array(
				'label'     => __( 'Sfondo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#feca91',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-btn-bg-hover: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'modal_btn_text_hover',
			array(
				'label'     => __( 'Colore testo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#002435',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-btn-text-hover: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'modal_dialog_radius',
			array(
				'label'      => __( 'Raggio finestra', 'eredi-experience-booking' ),
				'type'       => Controls_Manager::SLIDER,
				'separator'  => 'before',
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 6,
				),
				'selectors'  => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-dialog-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'modal_field_radius',
			array(
				'label'      => __( 'Raggio campi e pulsante', 'eredi-experience-booking' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array(
					'unit' => 'px',
					'size' => 4,
				),
				'selectors'  => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'modal_font_head',
			array(
				'label'     => __( 'Font titoli', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::FONT,
				'separator' => 'before',
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-font-head: "{{VALUE}}";' ),
			)
		);

		$this->add_control(
			'modal_font_body',
			array(
				'label'     => __( 'Font testo', 'eredi-experience-booking' ),
				'type'      => Controls_Manager::FONT,
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .edp-modal-preview' => '--edp-font-body: "{{VALUE}}";' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Assemble the sanitized modal copy + style config from widget settings.
	 *
	 * Text is applied per-experience by the modal JS (textContent/setAttribute);
	 * style values become inline CSS custom properties set on the shared modal
	 * when it opens. Empty style values are omitted so the CSS :root defaults
	 * keep applying.
	 *
	 * @param array $settings Widget settings.
	 * @return array{text:array<string,string>,style:array<string,string>}
	 */
	private function modal_config( array $settings ) {
		$text = array(
			'submit'         => sanitize_text_field( $settings['modal_submit_text'] ?? '' ),
			'successClose'   => sanitize_text_field( $settings['modal_success_close_text'] ?? '' ),
			'closeAria'      => sanitize_text_field( $settings['modal_close_aria'] ?? '' ),
			'upsellsHeading' => sanitize_text_field( $settings['modal_upsells_heading'] ?? '' ),
		);

		$colors = array(
			'--edp-overlay'        => 'modal_overlay_color',
			'--edp-cream'          => 'modal_dialog_bg',
			'--edp-teal'           => 'modal_title_color',
			'--edp-ink'            => 'modal_body_color',
			'--edp-border'         => 'modal_border_color',
			'--edp-btn-bg'         => 'modal_btn_bg',
			'--edp-btn-text'       => 'modal_btn_text',
			'--edp-btn-bg-hover'   => 'modal_btn_bg_hover',
			'--edp-btn-text-hover' => 'modal_btn_text_hover',
		);

		$style = array();
		foreach ( $colors as $var => $key ) {
			$value = Css::color( $settings[ $key ] ?? '' );
			if ( '' !== $value ) {
				$style[ $var ] = $value;
			}
		}

		$dialog_radius = Css::px( $settings['modal_dialog_radius'] ?? '', 40 );
		if ( '' !== $dialog_radius ) {
			$style['--edp-dialog-radius'] = $dialog_radius;
		}

		$field_radius = Css::px( $settings['modal_field_radius'] ?? '', 40 );
		if ( '' !== $field_radius ) {
			$style['--edp-radius'] = $field_radius;
		}

		$font_head = Css::font( $settings['modal_font_head'] ?? '' );
		if ( '' !== $font_head ) {
			$style['--edp-font-head'] = '"' . $font_head . '"';
		}

		$font_body = Css::font( $settings['modal_font_body'] ?? '' );
		if ( '' !== $font_body ) {
			$style['--edp-font-body'] = '"' . $font_body . '"';
		}

		return array(
			'text'  => $text,
			'style' => $style,
		);
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
				'modal'        => $this->modal_config( $settings ),
			)
		);
	}
}
