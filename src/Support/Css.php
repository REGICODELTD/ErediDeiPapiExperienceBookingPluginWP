<?php
/**
 * Whitelisting sanitizers for admin-provided CSS values.
 *
 * @package ErediExperienceBooking
 */

namespace ErediExperienceBooking\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizers for the colour / font / length values that an admin sets on the
 * booking widget and that end up as inline CSS custom properties on the modal.
 *
 * The values only ever reach the DOM through `element.style.setProperty()`,
 * which silently drops anything invalid, but we still whitelist here so junk
 * (or a clever injection attempt) never makes it into the JSON we print or the
 * inline preview markup.
 */
class Css {

	/**
	 * Characters / tokens that could break out of a declaration. Rejected outright.
	 */
	const BREAKOUT = '/[;{}<>]|url\s*\(|expression\s*\(|\/\*/i';

	/**
	 * Sanitize a CSS colour value.
	 *
	 * Accepts hex (#rgb, #rgba, #rrggbb, #rrggbbaa), rgb()/rgba(), hsl()/hsla()
	 * and bare CSS named colours (letters only — the browser validates the name).
	 *
	 * @param mixed $value Raw value.
	 * @return string Safe colour, or '' if invalid.
	 */
	public static function color( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		if ( '' === $value || preg_match( self::BREAKOUT, $value ) ) {
			return '';
		}
		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\)$/i', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^[a-z]+$/i', $value ) ) {
			return strtolower( $value );
		}
		return '';
	}

	/**
	 * Sanitize a font-family value (a single family name from Elementor's FONT
	 * control). Keeps letters, digits, spaces, quotes, commas and hyphens.
	 *
	 * @param mixed $value Raw value.
	 * @return string Safe font-family, or '' if empty/invalid.
	 */
	public static function font( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		if ( '' === $value || preg_match( self::BREAKOUT, $value ) ) {
			return '';
		}
		$value = preg_replace( '/[^a-z0-9 ,"\'\-]/i', '', $value );
		return trim( (string) $value );
	}

	/**
	 * Sanitize a pixel length coming from an Elementor SLIDER control.
	 *
	 * @param mixed $value Raw control value (array with 'size'/'unit', or scalar).
	 * @param int   $max   Maximum allowed pixels.
	 * @return string e.g. "6px", or '' if not set.
	 */
	public static function px( $value, $max = 200 ) {
		$size = null;
		if ( is_array( $value ) && isset( $value['size'] ) && '' !== $value['size'] ) {
			$size = $value['size'];
		} elseif ( is_numeric( $value ) ) {
			$size = $value;
		}
		if ( null === $size ) {
			return '';
		}
		$size = (int) round( (float) $size );
		$size = max( 0, min( (int) $max, $size ) );
		return $size . 'px';
	}
}
