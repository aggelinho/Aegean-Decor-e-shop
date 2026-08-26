( function() {
	'use strict';

	/**
	 * Build a form-encoded payload and send it without relying on jQuery.
	 *
	 * @param {string} action
	 * @param {Object} data
	 * @returns {void}
	 */
	const postNoticeAction = function( action, data ) {
		if ( ! window.cnArgsNotice || ! cnArgsNotice.ajaxURL ) {
			return;
		}

		const bodyParams = {
			action: action,
			notice_action: data.noticeAction,
			nonce: data.nonce,
			cn_network: cnArgsNotice.network ? 1 : 0
		};

		if ( typeof data.param !== 'undefined' ) {
			bodyParams.param = data.param;
		}

		const encodeBody = function( params ) {
			return Object.keys( params )
				.map( function( key ) {
					return encodeURIComponent( key ) + '=' + encodeURIComponent( params[ key ] );
				} )
				.join( '&' );
		};

		const body = encodeBody( bodyParams );

		if ( window.fetch ) {
			fetch( cnArgsNotice.ajaxURL, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: body
			} ).catch( function() {
				// fail silently – notice still closes
			} );
		} else {
			// XHR fallback for older browsers.
			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', cnArgsNotice.ajaxURL, true );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
			xhr.send( body );
		}
	};

	const hideNotice = function( notice ) {
		if ( notice ) {
			notice.style.display = 'none';
		}
	};

	document.addEventListener( 'DOMContentLoaded', function() {
		// No cookie compliance notice.
		document.addEventListener( 'click', function( event ) {
			const target = event.target;

			if ( ! target || typeof target.closest !== 'function' ) {
				return;
			}

			const dismissButton = target.closest( '.cn-notice .cn-no-compliance .cn-notice-dismiss' );

			if ( ! dismissButton ) {
				return;
			}

			const notice = dismissButton.closest( '.cn-notice' );

			if ( ! notice ) {
				return;
			}

			event.preventDefault();

			let noticeAction = 'dismiss';
			let param = '';

			if ( dismissButton.classList.contains( 'cn-approve' ) ) {
				noticeAction = 'approve';
			} else if ( dismissButton.classList.contains( 'cn-delay' ) ) {
				noticeAction = 'delay';
			} else if ( notice.classList.contains( 'cn-threshold' ) ) {
				noticeAction = 'threshold';

				const noticeText = notice.querySelector( '.cn-notice-text' );
				const delay = noticeText && noticeText.dataset ? parseInt( noticeText.dataset.delay, 10 ) : NaN;

				param = ! isNaN( delay ) && isFinite( delay ) ? delay : '';
			}

			postNoticeAction( 'cn_dismiss_notice', {
				noticeAction: noticeAction,
				nonce: cnArgsNotice.nonce,
				param: param
			} );

			hideNotice( notice );
		} );

		// Review notice.
		document.addEventListener( 'click', function( event ) {
			const target = event.target;

			if ( ! target || typeof target.closest !== 'function' ) {
				return;
			}

			const link = target.closest( '.cn-notice .cn-review .button-link' );

			if ( ! link ) {
				return;
			}

			const notice = link.closest( '.cn-notice' );

			if ( ! notice ) {
				return;
			}

			event.preventDefault();

			let noticeAction = 'dismiss';

			if ( link.classList.contains( 'cn-notice-review' ) ) {
				noticeAction = 'review';
			} else if ( link.classList.contains( 'cn-notice-delay' ) ) {
				noticeAction = 'delay';
			}

			postNoticeAction( 'cn_review_notice', {
				noticeAction: noticeAction,
				nonce: cnArgsNotice.reviewNonce
			} );

			hideNotice( notice );
		} );
	} );
} )();