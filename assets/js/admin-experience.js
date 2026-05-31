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

	// Default product-data tabs that make no sense for an experience.
	var HIDE_TABS = '.attribute_tab, .shipping_tab, .linked_product_tab';

	// Hide those tabs for experiences; restore only the ones we hid (no
	// regression on other product types). Run deferred so it executes AFTER
	// WooCommerce's own product-type handler.
	function hideDefaultTabs() {
		var on = isExperience();
		$( HIDE_TABS ).each( function () {
			var $tab = $( this );
			if ( on ) {
				$tab.hide().attr( 'data-edp-hid', '1' );
			} else if ( $tab.attr( 'data-edp-hid' ) ) {
				$tab.show().removeAttr( 'data-edp-hid' );
			}
		} );
	}

	function deferHideTabs() {
		window.setTimeout( hideDefaultTabs, 0 );
	}

	$( document )
		.on( 'change', '#product-type', toggleType )
		.on( 'change', '#product-type', deferHideTabs )
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

		// Add a price-tier row.
		.on( 'click', '[data-edp-add-tier]', function () {
			var html = tpl( 'edp-tpl-tier' ).split( '__TI__' ).join( nextId() );
			$( this ).closest( '.options_group' ).find( '[data-edp-rows]' ).first().append( html );
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
		} )

		// Copy settings from another experience.
		.on( 'click', '[data-edp-copy]', function () {
			if ( 'undefined' === typeof edpAdmin ) {
				return;
			}
			var $btn = $( this );
			var $box = $btn.closest( '.edp-copy-box' );
			var source = $box.find( '[data-edp-copy-source]' ).val();
			var target = $( '#post_ID' ).val();
			var sections = $box
				.find( '[data-edp-copy-section]:checked' )
				.map( function () {
					return this.value;
				} )
				.get();

			if ( ! source ) {
				window.alert( edpAdmin.i18n.selectSource );
				return;
			}
			if ( ! sections.length ) {
				window.alert( edpAdmin.i18n.selectSection );
				return;
			}
			if ( ! window.confirm( edpAdmin.i18n.confirm ) ) {
				return;
			}

			$btn.prop( 'disabled', true );
			$.post( edpAdmin.ajaxUrl, {
				action: 'edp_copy_experience_settings',
				nonce: edpAdmin.nonce,
				source_id: source,
				target_id: target,
				sections: sections
			} )
				.done( function ( res ) {
					if ( res && res.success ) {
						window.location.reload();
					} else {
						$btn.prop( 'disabled', false );
						window.alert( res && res.data && res.data.message ? res.data.message : edpAdmin.i18n.error );
					}
				} )
				.fail( function ( xhr ) {
					$btn.prop( 'disabled', false );
					var msg = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: edpAdmin.i18n.error;
					window.alert( msg );
				} );
		} );

	$( function () {
		toggleType();
		deferHideTabs();
	} );
} )( jQuery );
