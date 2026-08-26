'use strict';

/* global yith_wcan_admin, ajaxurl */

import { $ } from '../../shortcodes/globals';

export default class YITH_WCAN_Filter {
	/**
	 * ID of the range
	 */
	id;

	/**
	 * Dom object for containing range box
	 */
	$range;

	/**
	 * Filter object
	 */
	filter;

	constructor( $range, filter ) {
		if ( ! $range.length ) {
			return;
		}

		this.$range = $range;
		this.filter = filter;

		if ( $range.hasClass( 'initialized' ) ) {
			return;
		}

		this.init();
	}

	// init objec

	init() {
		this.initDependencies();
		this.initRemove();

		this.$range.addClass( 'initialized' );
	}

	initRemove() {
		this.$range.find( 'a.range-remove' ).on( 'click', ( ev ) => {
			ev.preventDefault();
			this.$range.remove();

			this.filter.afterRangeDelete();
		} );
	}

	initDependencies() {
		const $unlimitedCheck = this.$range.find( '[name*="unlimited"]' );

		// manage unlimited check
		$unlimitedCheck
			.on( 'change', function () {
				const t = $( this ),
					$max = t.closest( '.range-box' ).find( '.max' );

				if ( t.is( ':checked' ) ) {
					$max.hide();
				} else {
					$max.show();
				}
			} )
			.change();
	}

	// actions

	populate( rangeData ) {
		const { min, max, unlimited } = rangeData;

		this.$range.find( '.min' ).find( ':input' ).val( min );
		this.$range.find( '.max' ).find( ':input' ).val( max );
		this.$range
			.find( '.unlimited' )
			.find( ':input' )
			.prop( 'checked', unlimited );
	}

	toggleUnlimited( show ) {
		const $unlimitedContainer = this.$range.find( '.unlimited' ),
			$unlimitedCheck = $unlimitedContainer.find( ':input' );

		show && $unlimitedContainer.show();
		show ||
			( $unlimitedCheck.prop( 'checked', false ).change(),
			$unlimitedContainer.hide() );
	}

	// data handling

	getId() {
		return this.id || ( this.id = this.$range.data( 'range_id' ) );
	}
}
