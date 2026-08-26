'use strict';

/* global ajaxurl */

import { $, block, unblock } from '../../shortcodes/globals.js';

const request = function ( method, action, params, args ) {
		// retrieve wrapper as current context.
		const $wrapper = $( this );

		if ( params instanceof FormData ) {
			params.append( 'action', `yith_wcan_${ action }` );
		} else {
			params = {
				action: `yith_wcan_${ action }`,
				...params,
			};
		}

		const ajaxArgs = {
			url: ajaxurl,
			data: params,
			dataType: 'json',
			method,
			beforeSend: () => $wrapper.length && block( $wrapper ),
			complete: () => $wrapper.length && unblock( $wrapper ),
			...args,
		};

		return $.ajax( ajaxArgs );
	},
	get = function ( ...params ) {
		return request.call( this, 'get', ...params );
	},
	post = function ( ...params ) {
		return request.call( this, 'post', ...params );
	};

export default { request, get, post };
