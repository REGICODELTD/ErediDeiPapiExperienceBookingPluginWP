/**
 * Front-end booking modal: open per experience, fetch slots, live price, submit.
 *
 * Reads configuration from window.EDP (base) and window.EDP_EXPERIENCES (per-page
 * experience configs). No jQuery dependency.
 */
( function () {
	'use strict';

	if ( typeof window.EDP === 'undefined' ) {
		return;
	}

	var EXP = window.EDP_EXPERIENCES || {};
	var modal;
	var current = null; // current experience config.

	/* ------------------------------------------------------------------ */
	/* Helpers                                                            */
	/* ------------------------------------------------------------------ */

	function qs( sel, ctx ) {
		return ( ctx || document ).querySelector( sel );
	}
	function qsa( sel, ctx ) {
		return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) );
	}

	function formatPrice( amount ) {
		var c = EDP.currency;
		var n = Number( amount ) || 0;
		var fixed = n.toFixed( c.decimals );
		var parts = fixed.split( '.' );
		var intPart = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, c.thousandSep );
		var num = intPart + ( parts[ 1 ] ? c.decimalSep + parts[ 1 ] : '' );
		switch ( c.position ) {
			case 'left_space':
				return c.symbol + ' ' + num;
			case 'right':
				return num + c.symbol;
			case 'right_space':
				return num + ' ' + c.symbol;
			default:
				return c.symbol + num;
		}
	}

	// Weekday index 0=Mon..6=Sun from a Y-m-d string.
	function weekdayIndex( ymd ) {
		var d = new Date( ymd + 'T00:00:00' );
		return ( d.getDay() + 6 ) % 7;
	}

	function isDateAllowed( cfg, ymd ) {
		if ( ! ymd ) {
			return false;
		}
		var a = cfg.availability || {};
		if ( a.allowed_dates && a.allowed_dates.length ) {
			return a.allowed_dates.indexOf( ymd ) !== -1;
		}
		if ( a.blackout_dates && a.blackout_dates.indexOf( ymd ) !== -1 ) {
			return false;
		}
		if ( a.min_date && ymd < a.min_date ) {
			return false;
		}
		if ( a.max_date && ymd > a.max_date ) {
			return false;
		}
		if ( a.enabled_weekdays && a.enabled_weekdays.length ) {
			return a.enabled_weekdays.indexOf( weekdayIndex( ymd ) ) !== -1;
		}
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Open / close                                                       */
	/* ------------------------------------------------------------------ */

	function openModal( id ) {
		current = EXP[ id ];
		if ( ! current ) {
			return;
		}

		resetForm();
		qs( '.edp-modal__title', modal ).textContent = current.name;
		qs( 'input[name="experience_id"]', modal ).value = current.id;

		setupPersons();
		setupDate();
		buildUpsells();
		recalcTotal();

		modal.classList.add( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'false' );
		document.body.classList.add( 'edp-modal-open' );
	}

	function closeModal() {
		modal.classList.remove( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'true' );
		document.body.classList.remove( 'edp-modal-open' );
	}

	function resetForm() {
		var form = qs( '.edp-form', modal );
		form.reset();
		form.style.display = '';
		qs( '[data-edp-success]', modal ).hidden = true;
		setMessage( '', '' );
		var slot = qs( 'select[name="slot"]', modal );
		slot.innerHTML = '<option value="">' + EDP.i18n.selectDate + '</option>';
	}

	/* ------------------------------------------------------------------ */
	/* Field setup                                                        */
	/* ------------------------------------------------------------------ */

	function setupPersons() {
		var input = qs( 'input[name="persons"]', modal );
		var min = current.min_persons || 1;
		var max = current.max_persons || 0;
		input.min = min;
		input.value = min;
		if ( max > 0 ) {
			input.max = max;
		} else {
			input.removeAttribute( 'max' );
		}
		var hint = qs( '[data-edp-persons-hint]', modal );
		hint.textContent = max > 0 ? min + ' – ' + max : '≥ ' + min;
	}

	function setupDate() {
		var input = qs( 'input[name="date"]', modal );
		var a = current.availability || {};
		input.value = '';
		input.readOnly = false;
		if ( a.min_date ) {
			input.min = a.min_date;
		} else {
			input.removeAttribute( 'min' );
		}
		if ( a.max_date ) {
			input.max = a.max_date;
		} else {
			input.removeAttribute( 'max' );
		}
		// Event with a single date: prefill and lock.
		if ( a.allowed_dates && a.allowed_dates.length === 1 ) {
			input.value = a.allowed_dates[ 0 ];
			input.readOnly = true;
			fetchSlots( a.allowed_dates[ 0 ] );
		}
	}

	function buildUpsells() {
		var wrap = qs( '[data-edp-upsells]', modal );
		wrap.innerHTML = '';
		if ( ! current.upsells || ! current.upsells.length ) {
			return;
		}

		var title = document.createElement( 'div' );
		title.className = 'edp-upsells-title';
		title.textContent = 'Upsell & allestimenti';
		wrap.appendChild( title );

		current.upsells.forEach( function ( upsell, u ) {
			var block = document.createElement( 'div' );
			block.className = 'edp-upsell';
			block.setAttribute( 'data-uindex', u );

			var priceLabel = upsell.price_per_person > 0 ? ' (+' + formatPrice( upsell.price_per_person ) + ' ' + EDP.i18n.perPerson + ')' : '';
			var head = document.createElement( 'label' );
			head.className = 'edp-upsell-head';
			head.innerHTML = '<input type="checkbox" data-edp-upsell="' + u + '"> <span>' + escapeHtml( upsell.name ) + escapeHtml( priceLabel ) + '</span>';
			block.appendChild( head );

			var opts = document.createElement( 'div' );
			opts.className = 'edp-upsell-options';
			opts.hidden = true;

			( upsell.options || [] ).forEach( function ( option, o ) {
				if ( option.mode === 'addon' ) {
					var ol = document.createElement( 'label' );
					ol.className = 'edp-opt';
					var op = option.extra_per_person > 0 ? ' (+' + formatPrice( option.extra_per_person ) + ' ' + EDP.i18n.perPerson + ')' : '';
					ol.innerHTML = '<input type="checkbox" data-edp-option="' + u + ':' + o + '"> <span>' + escapeHtml( option.name ) + escapeHtml( op ) + '</span>';
					opts.appendChild( ol );
				} else {
					var inc = document.createElement( 'div' );
					inc.className = 'edp-opt edp-opt--included';
					inc.innerHTML = '<span>' + escapeHtml( option.name ) + ' <em>(incluso)</em></span>';
					opts.appendChild( inc );
				}
			} );

			block.appendChild( opts );
			wrap.appendChild( block );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Slots                                                              */
	/* ------------------------------------------------------------------ */

	function onDateChange() {
		var date = qs( 'input[name="date"]', modal ).value;
		if ( ! date ) {
			return;
		}
		if ( ! isDateAllowed( current, date ) ) {
			setMessage( EDP.i18n.dateNotBookable, 'error' );
			clearSlots( EDP.i18n.selectDate );
			return;
		}
		setMessage( '', '' );
		fetchSlots( date );
	}

	function clearSlots( placeholder ) {
		var slot = qs( 'select[name="slot"]', modal );
		slot.innerHTML = '<option value="">' + placeholder + '</option>';
	}

	function fetchSlots( date ) {
		clearSlots( EDP.i18n.loadingSlots );
		var url = EDP.restUrl + 'available-slots?experience_id=' + encodeURIComponent( current.id ) + '&date=' + encodeURIComponent( date );

		fetch( url, { headers: { 'X-WP-Nonce': EDP.nonce } } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( data ) {
				var slot = qs( 'select[name="slot"]', modal );
				var slots = ( data && data.slots ) || [];
				var available = slots.filter( function ( s ) {
					return s.available;
				} );
				if ( ! available.length ) {
					clearSlots( EDP.i18n.noSlots );
					return;
				}
				var html = '<option value="">' + EDP.i18n.chooseSlot + '</option>';
				slots.forEach( function ( s ) {
					if ( s.available ) {
						html += '<option value="' + s.time + '">' + s.label + '</option>';
					} else {
						html += '<option value="" disabled>' + s.label + ' — ' + EDP.i18n.slotFull + '</option>';
					}
				} );
				slot.innerHTML = html;
			} )
			.catch( function () {
				clearSlots( EDP.i18n.noSlots );
			} );
	}

	/* ------------------------------------------------------------------ */
	/* Selection + price                                                  */
	/* ------------------------------------------------------------------ */

	function collectSelection() {
		var selection = [];
		qsa( 'input[data-edp-upsell]', modal ).forEach( function ( cb ) {
			if ( ! cb.checked ) {
				return;
			}
			var u = parseInt( cb.getAttribute( 'data-edp-upsell' ), 10 );
			var options = [];
			qsa( 'input[data-edp-option^="' + u + ':"]', modal ).forEach( function ( oc ) {
				if ( oc.checked ) {
					options.push( parseInt( oc.getAttribute( 'data-edp-option' ).split( ':' )[ 1 ], 10 ) );
				}
			} );
			selection.push( { index: u, options: options } );
		} );
		return selection;
	}

	function recalcTotal() {
		var persons = parseInt( qs( 'input[name="persons"]', modal ).value, 10 ) || 0;
		var perPerson = current.price_per_person || 0;

		collectSelection().forEach( function ( sel ) {
			var upsell = current.upsells[ sel.index ];
			if ( ! upsell ) {
				return;
			}
			perPerson += Number( upsell.price_per_person ) || 0;
			sel.options.forEach( function ( oi ) {
				var opt = upsell.options[ oi ];
				if ( opt && opt.mode === 'addon' ) {
					perPerson += Number( opt.extra_per_person ) || 0;
				}
			} );
		} );

		var total = perPerson * persons;
		qs( '[data-edp-total]', modal ).textContent = persons > 0 ? formatPrice( total ) : '—';
	}

	/* ------------------------------------------------------------------ */
	/* Submit                                                             */
	/* ------------------------------------------------------------------ */

	function onSubmit( e ) {
		e.preventDefault();
		var form = qs( '.edp-form', modal );

		if ( ! form.checkValidity() ) {
			form.reportValidity();
			setMessage( EDP.i18n.required, 'error' );
			return;
		}

		var payload = {
			experience_id: current.id,
			persons: parseInt( qs( 'input[name="persons"]', modal ).value, 10 ),
			date: qs( 'input[name="date"]', modal ).value,
			slot: qs( 'select[name="slot"]', modal ).value,
			first_name: qs( 'input[name="first_name"]', modal ).value,
			last_name: qs( 'input[name="last_name"]', modal ).value,
			email: qs( 'input[name="email"]', modal ).value,
			phone: qs( 'input[name="phone"]', modal ).value,
			privacy: qs( 'input[name="privacy"]', modal ).checked,
			edp_hp: qs( 'input[name="edp_hp"]', modal ).value,
			upsells: collectSelection()
		};

		var submitBtn = qs( '.edp-submit', form );
		submitBtn.disabled = true;
		setMessage( EDP.i18n.sending, 'info' );

		fetch( EDP.restUrl + 'booking', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': EDP.nonce
			},
			body: JSON.stringify( payload )
		} )
			.then( function ( r ) {
				return r.json().then( function ( body ) {
					return { ok: r.ok, body: body };
				} );
			} )
			.then( function ( res ) {
				submitBtn.disabled = false;
				if ( res.ok && res.body && res.body.success ) {
					showSuccess( res.body.message );
				} else {
					var msg = res.body && res.body.message ? res.body.message : EDP.i18n.genericError;
					setMessage( msg, 'error' );
				}
			} )
			.catch( function () {
				submitBtn.disabled = false;
				setMessage( EDP.i18n.genericError, 'error' );
			} );
	}

	function showSuccess( message ) {
		qs( '.edp-form', modal ).style.display = 'none';
		var box = qs( '[data-edp-success]', modal );
		qs( '[data-edp-success-text]', modal ).textContent = message;
		box.hidden = false;
	}

	function setMessage( text, type ) {
		var box = qs( '[data-edp-message]', modal );
		box.textContent = text;
		box.className = 'edp-message' + ( type ? ' edp-message--' + type : '' );
	}

	function escapeHtml( str ) {
		return String( str ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Wiring                                                             */
	/* ------------------------------------------------------------------ */

	function init() {
		modal = qs( '#edp-modal' );
		if ( ! modal ) {
			return;
		}

		// Open buttons (delegated so dynamically-rendered widgets work too).
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.edp-book-btn' ) : null;
			if ( btn ) {
				e.preventDefault();
				openModal( parseInt( btn.getAttribute( 'data-experience-id' ), 10 ) );
			}
		} );

		// Close.
		qsa( '[data-edp-close]', modal ).forEach( function ( el ) {
			el.addEventListener( 'click', closeModal );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && modal.classList.contains( 'is-open' ) ) {
				closeModal();
			}
		} );

		// Field interactions.
		modal.addEventListener( 'change', function ( e ) {
			if ( e.target.name === 'date' ) {
				onDateChange();
			}
			if ( e.target.matches( 'input[data-edp-upsell]' ) ) {
				var opts = e.target.closest( '.edp-upsell' ).querySelector( '.edp-upsell-options' );
				if ( opts ) {
					opts.hidden = ! e.target.checked;
				}
			}
			if ( e.target.name === 'persons' || e.target.hasAttribute( 'data-edp-upsell' ) || e.target.hasAttribute( 'data-edp-option' ) ) {
				recalcTotal();
			}
		} );
		modal.addEventListener( 'input', function ( e ) {
			if ( e.target.name === 'persons' ) {
				recalcTotal();
			}
		} );

		// Persons stepper.
		qsa( '[data-edp-step]', modal ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var input = qs( 'input[name="persons"]', modal );
				var step = parseInt( btn.getAttribute( 'data-edp-step' ), 10 );
				var min = parseInt( input.min, 10 ) || 1;
				var max = input.max ? parseInt( input.max, 10 ) : 0;
				var val = ( parseInt( input.value, 10 ) || min ) + step;
				if ( val < min ) {
					val = min;
				}
				if ( max > 0 && val > max ) {
					val = max;
				}
				input.value = val;
				recalcTotal();
			} );
		} );

		qs( '.edp-form', modal ).addEventListener( 'submit', onSubmit );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
