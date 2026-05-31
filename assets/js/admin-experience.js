/**
 * Admin product editor logic for the "experience" product type.
 *
 * Handles: product-type visibility, availability-mode sections, per-weekday
 * toggles, and the repeatable rows for time-windows, upsells and options.
 */
( function ( $ ) {
	'use strict';

	// Monotonic id generator for new rows (high start to avoid colliding with
	// the 0..n indices rendered server-side for existing rows).
	var uid = Date.now();
	function nextId() {
		return ++uid;
	}

	function tpl( id ) {
		return $( '#' + id ).html() || '';
	}

	function isExperience() {
		return 'experience' === $( '#product-type' ).val();
	}

	// Show/hide our tabs based on product type, and make sure a visible tab is active.
	function toggleType() {
		var on = isExperience();
		$( 'li.edp-show-if-experience' ).toggle( on );

		if ( on ) {
			toggleMode();
			var $active = $( 'ul.wc-tabs li.active' );
			if ( ! $active.length || ! $active.is( ':visible' ) ) {
				$( 'ul.wc-tabs li:visible' ).first().find( 'a' ).trigger( 'click' );
			}
		}
	}

	// Show/hide availability sub-sections by selected mode.
	function toggleMode() {
		var mode = $( '[data-edp-mode]' ).val() || 'always';
		$( '.edp-mode-block' ).each( function () {
			var modes = String( $( this ).attr( 'data-edp-mode-block' ) ).split( ' ' );
			var show =
				modes.indexOf( mode ) !== -1 ||
				( modes.indexOf( 'weekly' ) !== -1 && ( 'always' === mode || 'range' === mode ) );
			$( this ).toggle( show );
		} );
	}

	$( document )
		.on( 'change', '#product-type', toggleType )
		.on( 'change', '[data-edp-mode]', toggleMode )

		// Per-weekday open toggle.
		.on( 'change', '[data-edp-day-toggle]', function () {
			$( this ).closest( '[data-edp-weekday]' ).find( '.edp-weekday-windows' ).toggle( this.checked );
		} )

		// Add a time-window row (event or weekday).
		.on( 'click', '[data-edp-add-window]', function () {
			var prefix = $( this ).attr( 'data-name-prefix' );
			var html = tpl( 'edp-tpl-window' ).split( '__PREFIX__' ).join( prefix ).split( '__WI__' ).join( nextId() );
			$( this ).closest( '.edp-windows-block, .edp-weekday-windows' ).find( '[data-edp-rows]' ).first().append( html );
		} )

		// Add an upsell.
		.on( 'click', '[data-edp-add-upsell]', function () {
			var html = tpl( 'edp-tpl-upsell' ).split( '__UI__' ).join( nextId() );
			$( '[data-edp-upsell-rows]' ).append( html );
		} )

		// Add an allestimento option to an upsell.
		.on( 'click', '[data-edp-add-option]', function () {
			var ui = $( this ).attr( 'data-ui' );
			var html = tpl( 'edp-tpl-option' ).split( '__UI__' ).join( ui ).split( '__OI__' ).join( nextId() );
			$( this ).closest( '.edp-upsell-options' ).find( '[data-edp-rows]' ).first().append( html );
		} )

		// Remove any row (window/option) or a whole upsell.
		.on( 'click', '[data-edp-remove]', function () {
			$( this ).closest( '[data-edp-row], [data-edp-upsell]' ).remove();
		} )

		// Toggle the per-person surcharge field when an option becomes an addon.
		.on( 'change', '[data-edp-option-mode]', function () {
			var addon = 'addon' === $( this ).val();
			$( this ).closest( '.edp-option-row' ).find( '.edp-extra-field' ).toggle( addon );
		} );

	$( function () {
		toggleType();
	} );
} )( jQuery );
