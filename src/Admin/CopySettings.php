<?php
/**
 * "Copy experience settings" AJAX handler.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Admin;

use ErediExperienceBooking\ProductType\ExperienceProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Copies selected experience settings (pricing / availability / upsells) from a
 * source experience to the one currently being edited. Server-side copy of the
 * canonical stored meta, so the nested structures (windows, upsells, options)
 * are replicated 1:1; the editor then reloads to show the result.
 */
class CopySettings {

	/**
	 * Copyable sections and the meta keys each one owns.
	 */
	const SECTIONS = array(
		'pricing'      => array( '_edp_price_tiers', '_edp_duration', '_edp_price_per_person', '_edp_min_persons', '_edp_max_persons' ),
		'availability' => array( '_edp_availability' ),
		'upsells'      => array( '_edp_upsells' ),
	);

	/**
	 * Hook in.
	 */
	public function register() {
		add_action( 'wp_ajax_edp_copy_experience_settings', array( $this, 'handle' ) );
	}

	/**
	 * Handle the AJAX request.
	 */
	public function handle() {
		check_ajax_referer( 'edp_copy_settings', 'nonce' );

		$source_id = isset( $_POST['source_id'] ) ? absint( $_POST['source_id'] ) : 0;
		$target_id = isset( $_POST['target_id'] ) ? absint( $_POST['target_id'] ) : 0;

		if ( ! current_user_can( 'edit_products' ) || ! $target_id || ! current_user_can( 'edit_post', $target_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti.', 'eredi-experience-booking' ) ), 403 );
		}

		if ( ! $source_id || $source_id === $target_id ) {
			wp_send_json_error( array( 'message' => __( 'Seleziona un’esperienza sorgente valida.', 'eredi-experience-booking' ) ), 400 );
		}

		$source = wc_get_product( $source_id );
		$target = wc_get_product( $target_id );
		if ( ! $source instanceof ExperienceProduct || ! $target instanceof ExperienceProduct ) {
			wp_send_json_error( array( 'message' => __( 'Sorgente o destinazione non valide. Salva prima questo prodotto come Esperienza.', 'eredi-experience-booking' ) ), 400 );
		}

		$requested = isset( $_POST['sections'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['sections'] ) ) : array();
		$sections  = array_values( array_intersect( array_keys( self::SECTIONS ), $requested ) );
		if ( empty( $sections ) ) {
			wp_send_json_error( array( 'message' => __( 'Seleziona almeno una sezione da copiare.', 'eredi-experience-booking' ) ), 400 );
		}

		foreach ( $sections as $section ) {
			foreach ( self::SECTIONS[ $section ] as $meta_key ) {
				$target->update_meta_data( $meta_key, $source->get_meta( $meta_key ) );
			}
		}

		// Keep WooCommerce's own price in sync when pricing is copied.
		if ( in_array( 'pricing', $sections, true ) ) {
			$price = $source->get_meta( '_edp_price_per_person' );
			$price = ( '' !== $price && null !== $price ) ? $price : '';
			$target->set_regular_price( $price );
			$target->set_price( $price );
		}

		$target->save();

		wp_send_json_success(
			array(
				'message'  => __( 'Impostazioni copiate correttamente.', 'eredi-experience-booking' ),
				'sections' => $sections,
			)
		);
	}
}
