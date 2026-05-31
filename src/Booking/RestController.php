<?php
/**
 * REST endpoints for the front-end booking flow.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Booking;

use ErediExperienceBooking\Availability\AvailabilityService;
use ErediExperienceBooking\ProductType\ExperienceProduct;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes:
 *   GET  edp/v1/available-slots  -> selectable slots for a date.
 *   POST edp/v1/booking          -> create a pending booking order.
 *
 * All pricing and availability are recomputed server-side; the client payload
 * is never trusted for totals or capacity.
 */
class RestController {

	const REST_NS = 'edp/v1';

	/**
	 * Hook into the REST API.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NS,
			'/available-slots',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_slots' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'experience_id' => array( 'required' => true ),
					'date'          => array( 'required' => true ),
				),
			)
		);

		register_rest_route(
			self::REST_NS,
			'/booking',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_booking' ),
				'permission_callback' => array( $this, 'verify_request' ),
			)
		);
	}

	/**
	 * Verify the REST nonce for the booking endpoint.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public function verify_request( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error( 'edp_bad_nonce', __( 'Sessione scaduta, ricarica la pagina e riprova.', 'eredi-experience-booking' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * GET available slots for a date.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_slots( $request ) {
		$product = $this->get_experience( (int) $request->get_param( 'experience_id' ) );
		if ( is_wp_error( $product ) ) {
			return $product;
		}

		$date    = sanitize_text_field( (string) $request->get_param( 'date' ) );
		$service = new AvailabilityService();

		if ( ! $service->is_valid_date( $date ) ) {
			return new \WP_Error( 'edp_bad_date', __( 'Data non valida.', 'eredi-experience-booking' ), array( 'status' => 400 ) );
		}

		$slots = array();
		foreach ( $service->get_slots_for_date( $product, $date ) as $slot ) {
			$slots[] = array(
				'time'      => $slot['time'],
				'label'     => $slot['label'],
				'available' => (bool) $slot['available'],
				'remaining' => $slot['remaining'], // null = unlimited.
			);
		}

		return rest_ensure_response(
			array(
				'date'     => $date,
				'bookable' => $service->is_date_bookable( $product, $date ),
				'slots'    => $slots,
			)
		);
	}

	/**
	 * POST create a booking.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_booking( $request ) {
		// Honeypot: bots fill every field.
		if ( '' !== trim( (string) $request->get_param( 'edp_hp' ) ) ) {
			return new \WP_Error( 'edp_spam', __( 'Invio non valido.', 'eredi-experience-booking' ), array( 'status' => 400 ) );
		}

		$product = $this->get_experience( (int) $request->get_param( 'experience_id' ) );
		if ( is_wp_error( $product ) ) {
			return $product;
		}

		$service = new AvailabilityService();

		// People.
		$persons = absint( $request->get_param( 'persons' ) );
		$min     = $product->get_min_persons();
		$max     = $product->get_max_persons();
		if ( $persons < $min || ( $max > 0 && $persons > $max ) ) {
			return new \WP_Error( 'edp_persons', __( 'Numero di persone non valido per questa esperienza.', 'eredi-experience-booking' ), array( 'status' => 400 ) );
		}

		// Date + slot.
		$date = sanitize_text_field( (string) $request->get_param( 'date' ) );
		$slot = sanitize_text_field( (string) $request->get_param( 'slot' ) );
		if ( ! $service->is_date_bookable( $product, $date ) ) {
			return new \WP_Error( 'edp_date', __( 'La data scelta non è disponibile.', 'eredi-experience-booking' ), array( 'status' => 400 ) );
		}
		if ( ! $this->slot_is_available( $service, $product, $date, $slot ) ) {
			return new \WP_Error( 'edp_slot', __( 'Lo slot orario scelto non è più disponibile.', 'eredi-experience-booking' ), array( 'status' => 409 ) );
		}

		// Customer.
		$first = sanitize_text_field( (string) $request->get_param( 'first_name' ) );
		$last  = sanitize_text_field( (string) $request->get_param( 'last_name' ) );
		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		$phone = sanitize_text_field( (string) $request->get_param( 'phone' ) );

		if ( '' === $first || '' === $last || '' === $phone ) {
			return new \WP_Error( 'edp_fields', __( 'Compila tutti i campi obbligatori.', 'eredi-experience-booking' ), array( 'status' => 400 ) );
		}
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'edp_email', __( 'Indirizzo email non valido.', 'eredi-experience-booking' ), array( 'status' => 400 ) );
		}
		if ( ! rest_sanitize_boolean( $request->get_param( 'privacy' ) ) ) {
			return new \WP_Error( 'edp_privacy', __( 'Devi accettare l’informativa privacy per procedere.', 'eredi-experience-booking' ), array( 'status' => 400 ) );
		}

		$selection = $this->normalize_selection( $request->get_param( 'upsells' ) );

		$factory = new OrderFactory();
		$order   = $factory->create(
			$product,
			array(
				'persons'   => $persons,
				'date'      => $date,
				'slot'      => $slot,
				'selection' => $selection,
				'privacy'   => true,
				'customer'  => array(
					'first_name' => $first,
					'last_name'  => $last,
					'email'      => $email,
					'phone'      => $phone,
				),
			)
		);

		if ( is_wp_error( $order ) ) {
			return new \WP_Error( 'edp_order', __( 'Impossibile registrare la prenotazione. Riprova.', 'eredi-experience-booking' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success'      => true,
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'message'      => __( 'Grazie! La tua richiesta di prenotazione è stata inviata. Riceverai una email di conferma a breve.', 'eredi-experience-booking' ),
			)
		);
	}

	/**
	 * Load and validate an experience product.
	 *
	 * @param int $id Product id.
	 * @return ExperienceProduct|\WP_Error
	 */
	private function get_experience( $id ) {
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product instanceof ExperienceProduct ) {
			return new \WP_Error( 'edp_no_product', __( 'Esperienza non trovata.', 'eredi-experience-booking' ), array( 'status' => 404 ) );
		}
		return $product;
	}

	/**
	 * Whether a slot is currently selectable.
	 *
	 * @param AvailabilityService $service Service.
	 * @param ExperienceProduct   $product Product.
	 * @param string              $date    Y-m-d.
	 * @param string              $slot    HH:MM.
	 * @return bool
	 */
	private function slot_is_available( AvailabilityService $service, ExperienceProduct $product, $date, $slot ) {
		foreach ( $service->get_slots_for_date( $product, $date ) as $generated ) {
			if ( $generated['time'] === $slot ) {
				return (bool) $generated['available'];
			}
		}
		return false;
	}

	/**
	 * Normalize the raw upsell selection from the request.
	 *
	 * @param mixed $raw Raw value.
	 * @return array
	 */
	private function normalize_selection( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$selection = array();
		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['index'] ) ) {
				continue;
			}
			$options = array();
			if ( isset( $entry['options'] ) && is_array( $entry['options'] ) ) {
				$options = array_map( 'intval', $entry['options'] );
			}
			$selection[] = array(
				'index'   => (int) $entry['index'],
				'options' => $options,
			);
		}
		return $selection;
	}
}
