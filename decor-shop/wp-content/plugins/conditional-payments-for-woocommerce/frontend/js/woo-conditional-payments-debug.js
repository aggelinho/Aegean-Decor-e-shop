jQuery(document).ready(function($) {
	/**
	 * Debug
	 */
	var wcpDebug = {
		init: function() {
			this.toggleDebug();
			this.setInitial();

			var self = this;
			$( document.body ).on( 'updated_checkout wcp_updated_debug', function( data ) {
				self.setInitial();
			} );
		},

		/**
		 * Toggle debug on click
		 */
		toggleDebug: function() {
			var self = this;

			$( document.body ).on( 'click', '#wcp-debug-header', function( e ) {
				if ( $( '#wcp-debug-contents' ).is( ':visible' ) ) {
					$( '#wcp-debug' ).toggleClass( 'closed', true );
				} else {
					$( '#wcp-debug' ).toggleClass( 'closed', false );
				}

				$( '#wcp-debug-contents' ).slideToggle( 200, function() {
					self.saveStatus();
				} );
			} );
		},

		/**
		 * Save debug open / closed status to cookies
		 */
		saveStatus: function() {
			if ( ! this.isLocalStorage() ) {
				return;
			}

			let status = $( '#wcp-debug-contents' ).is( ':visible' ) ? 'true' : 'false';

			localStorage.setItem( 'wcp_debug_status', status );
		},

		/**
		 * Set initial stage for debug
		 */
		setInitial: function() {
			if ( ! this.isLocalStorage() ) {
				return;
			}

            let status = localStorage.getItem( 'wcp_debug_status' );

			$( '#wcp-debug-contents' ).toggle( status === 'true' );
			$( '#wcp-debug' ).toggleClass( 'closed', $( '#wcp-debug-contents' ).is( ':hidden' ) );
		},

        /**
		 * Check if local storage is available
		 */
		isLocalStorage: function() {
			var test = 'test';
			try {
				localStorage.setItem(test, test);
				localStorage.removeItem(test);

				return true;
			} catch(e) {
				return false;
			}
		}
	}

	wcpDebug.init();
});
