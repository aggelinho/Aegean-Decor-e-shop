'use strict';

/* global globalThis, jQuery, yith_wcan_shortcodes, accounting */

const $ = jQuery, // we can do this as WebPack will compact all together inside a closure.
	$body = $( 'body' ),
	block = ( $el ) => {
		if ( typeof $.fn.block === 'undefined' ) {
			return;
		}

		let background = '#fff center center no-repeat';

		if (
			'undefined' !== typeof yith_wcan_shortcodes &&
			yith_wcan_shortcodes?.loader
		) {
			background = `url('${ yith_wcan_shortcodes.loader }') ${ background }`;
		}

		$el.block( {
			message: null,
			overlayCSS: {
				background,
				opacity: 0.7,
			},
		} );
	},
	unblock = ( $el ) => {
		if ( typeof $.fn.unblock === 'undefined' ) {
			return;
		}

		$el.unblock();
	},
	serialize = ( $el, { formatName, filterItems } ) => {
		let result = {},
			inputs = $el.find( ':input' ).not( '[disabled]' );

		if ( typeof filterItems === 'function' ) {
			inputs = inputs.filter( filterItems );
		}

		inputs.each( function () {
			let t = $( this ),
				name = t.attr( 'name' ),
				value;

			if ( ! name ) {
				return;
			}

			// removes ending brackets, since are not needed
			name = name.replace( /^(.*)\[]$/, '$1' );

			// offers additional name formatting from invoker
			if ( typeof formatName === 'function' ) {
				name = formatName( name );
			}

			// retrieve value, depending on input type
			if ( t.is( '[type="radio"]' ) && ! t.is( ':checked' ) ) {
				return;
			}
			value = t.val();

			// if name is composite, try to recreate missing structure
			if ( -1 !== name.indexOf( '[' ) ) {
				const components = name
						.split( '[' )
						.map( ( c ) => c.replace( /[\[, \]]/g, '' ) ),
					firstComponent = components.shift(),
					newItem = components
						.reverse()
						.reduce( ( res, key ) => ( { [ key ]: res } ), value );

				if ( typeof result[ firstComponent ] === 'undefined' ) {
					result[ firstComponent ] = newItem;
				} else {
					result[ firstComponent ] = $.extend(
						true,
						result[ firstComponent ],
						newItem
					);
				}
			}
			// else simply append value to result object
			else {
				result[ name ] = value;
			}
		} );

		return result;
	},
	removeHierarchyFromString = ( value ) => {
		return value
			.replace( /^(.*>)([^>]+)$/, '$2' )
			.replace( '&amp;', '&' )
			.trim();
	},
	keys = {
		esc: 27,
		enter: 32,
		space: 13,
		up: 38,
		down: 40,
	};

export { $, $body, block, unblock, serialize, removeHierarchyFromString, keys };
