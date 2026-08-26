'use strict';

/* global jQuery */

import YITH_WCAN_Preset from './modules/yith-wcan-preset';

jQuery( ( $ ) => {
	const $wrapper = $( '#yith_wcan_panel_filter-preset-edit' );

	if ( ! $wrapper.length ) {
		return;
	}

	// Init filters handling
	$( document )
		.on( 'yith_wcan_filters_init', function () {
			new YITH_WCAN_Preset( $wrapper );
		} )
		.trigger( 'yith_wcan_filters_init' );
} );
