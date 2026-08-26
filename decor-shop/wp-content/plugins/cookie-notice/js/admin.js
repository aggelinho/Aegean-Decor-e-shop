( function( $ ) {

    // ready event
	$( function() {
		// initialize color picker
		$( '.cn_color' ).wpColorPicker();

		$( document ).on( 'click', 'input.cn-reset-settings', function() {
			return confirm( cnArgs.resetToDefaults );
		} );

		if ( cnArgs.settingsTab === 'privacy-consent' ) {
			var cnListTable = {
				displayedSources: [],
				sourceContainers: {},
				tableContainers: {},

				/**
				 * Initialize list tables.
				 *
				 * @param object sources
				 *
				 * @return void
				 */
				init: function( sources ) {
					for ( const source in sources ) {
						let mainContainter = $( '#cn_privacy_consent_' + source );

						// update duplicated ids
						mainContainter.find( '#the-list' ).attr( 'id', 'the-list-' + source );
						mainContainter.find( '#table-paging' ).attr( 'id', 'table-paging-' + source );

						if ( sources[source].type === 'dynamic' ) {
							// add containers
							this.sourceContainers[source] = mainContainter;
							this.tableContainers[source] = mainContainter.find( '.cn-privacy-consent-list-table-container' );

							// load list table only for active (checked) sources
							if ( !! sources[source].status && !! sources[source].availability )
								this.display( source );
						}
					}
				},

				/**
				 * Handle clicking functional links.
				 *
				 * @param string sourceId
				 *
				 * @return void
				 */
				start: function( sourceId ) {
					var that = this;

					this.tableContainers[sourceId].find( '.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a' ).on( 'click', function( e ) {
						e.preventDefault();

						// remove question mark
						var query = this.search.substring( 1 );

						// prepare data
						var data = {
							paged: that.query( query, 'paged' ) || 1,
							order: that.query( query, 'order' ) || 'asc',
							orderby: that.query( query, 'orderby' ) || 'title',
							search: that.query( query, 'search' ) || ''
						};

						that.update( sourceId, data );
					} );
				},

				/**
				 * Display data for the first time.
				 *
				 * @param string sourceId
				 *
				 * @return void
				 */
				display: function( sourceId ) {
					// already displayed?
					if ( this.displayedSources.includes( sourceId ) )
						return;

					this.displayedSources.push( sourceId );

					let that = this;
					let spinner = this.sourceContainers[sourceId].find( '.tablenav .spinner' );

					$.ajax( {
						url: ajaxurl,
						type: 'GET',
						dataType: 'json',
						data: {
							nonce: $( '#cn_privacy_consent_nonce' ).val(),
							action: 'cn_privacy_consent_display_table',
							source: sourceId
						}
					} ).done( function( response ) {
						try {
							if ( response.success ) {
								// update list table
								that.tableContainers[sourceId].html( response.data );

								// update duplicated ids
								that.tableContainers[sourceId].find( '#the-list' ).attr( 'id', 'the-list-' + sourceId );
								that.tableContainers[sourceId].find( '#table-paging' ).attr( 'id', 'table-paging-' + sourceId );

								// bind form status handling
								that.tableContainers[sourceId].find( 'input.cn-privacy-consent-form-status' ).on( 'change', handleFormStatus );

								let searchInput = $( '#' + sourceId + '-search-input' );
								let searchButton = $( '#' + sourceId + '-search-submit' );

								// disallow enter key form submission
								searchInput.on( 'keydown', function( e ) {
									if ( e.key === 'Enter' ) {
										e.preventDefault();

										searchButton.click();
										// return false;
									}
								} );

								// handle searching
								searchButton.on( 'click', function( e ) {
									e.preventDefault();

									// remove question mark
									var query = this.search.substring( 1 );

									// prepare data
									var data = {
										paged: that.query( query, 'paged' ) || 1,
										order: that.query( query, 'order' ) || 'asc',
										orderby: that.query( query, 'orderby' ) || 'title',
										search: searchInput.val() || ''
									};

									that.update( sourceId, data );
								} );

								that.start( sourceId );
							} else {
								console.log( 'Loading source "' + sourceId + '" failed.' );
							}
						} catch ( e ) {
							console.log( 'Loading source "' + sourceId + '" failed.' );
						}
					} ).always( function() {
						// hide spinner
						spinner.removeClass( 'is-active' );

						// enable list table
						that.tableContainers[sourceId].find( 'table' ).removeClass( 'loading' );
					} );
				},

				/**
				 * AJAX request to get source data.
				 *
				 * @param string sourceId
				 * @param object data
				 *
				 * @return void
				 */
				update: function( sourceId, data ) {
					let that = this;
					let spinner = this.sourceContainers[sourceId].find( '.tablenav .spinner' );

					// display spinner
					spinner.addClass( 'is-active' );

					// disable list table
					this.tableContainers[sourceId].find( 'table' ).addClass( 'loading' );

					$.ajax( {
						url: ajaxurl,
						type: 'GET',
						data: {
							nonce: $( '#cn_privacy_consent_nonce' ).val(),
							action: 'cn_privacy_consent_get_forms',
							source: sourceId,
							paged: data.paged,
							order: data.order,
							orderby: data.orderby,
							search: data.search
						}
					} ).done( function( response ) {
						try {
							if ( response.success ) {
								if ( response.data.rows.length )
									that.sourceContainers[sourceId].find( 'tbody' ).html( response.data.rows );

								if ( response.data.column_headers.length )
									that.sourceContainers[sourceId].find( 'thead tr, tfoot tr' ).html( response.data.column_headers );

								if ( response.data.pagination.length ) {
									that.sourceContainers[sourceId].find( '.tablenav.bottom .tablenav-pages' ).html( $( response.data.pagination ).html() );
									that.sourceContainers[sourceId].find( '#table-paging' ).attr( 'id', 'table-paging-' + sourceId );
								}

								// bind form status handling
								that.tableContainers[sourceId].find( 'input.cn-privacy-consent-form-status' ).on( 'change', handleFormStatus );

								that.start( sourceId );
							} else {
								console.log( 'FAILED' );
							}
						} catch ( e ) {
							console.log( 'FAILED' );
						}
					} ).always( function() {
						// hide spinner
						spinner.removeClass( 'is-active' );

						// enable list table
						that.tableContainers[sourceId].find( 'table' ).removeClass( 'loading' );
					} );
				},

				/**
				 * Filter the URL Query to extract variables.
				 *
				 * @param string query
				 * @param string variable
				 *
				 * @return string|bool
				 */
				query: function( query, variable ) {
					var vars = query.split( '&' );

					for ( var i = 0; i < vars.length; i++ ) {
						var pair = vars[i].split( '=' );

						if ( pair[0] === variable )
							return pair[1];
					}

					return false;
				}
			}

			// any privacy consent sources?
			if ( cnArgs.privacyConsentSources ) {
				// initialize list tables
				cnListTable.init( cnArgs.privacyConsentSources );

				// handle every single static form status
				for ( const source in cnArgs.privacyConsentSources ) {
					if ( cnArgs.privacyConsentSources[source].type === 'static' ) {
						$( '#cn_privacy_consent_' + cnArgs.privacyConsentSources[source].id ).find( 'input.cn-privacy-consent-form-status' ).on( 'change', handleFormStatus );
					}
				}
				
				// privacy consent source status
				$( 'input.cn-privacy-consent-status' ).on( 'change', function() {
					let checkbox = $( this );

					if ( checkbox.is( ':checked' ) ) {
						let source = checkbox.data( 'source' );

						checkbox.closest( 'fieldset' ).find( '.cn-privacy-consent-options-container' ).slideDown( 'fast' );

						// dynamic source?
						if ( cnArgs.privacyConsentSources[source].type === 'dynamic' )
							cnListTable.display( source );
					} else
						checkbox.closest( 'fieldset' ).find( '.cn-privacy-consent-options-container' ).slideUp( 'fast' );
				} );
				
				// privacy consent active type
				$( 'input.cn-privacy-consent-active-type' ).on( 'change', function( e ) {
					let radio = $( e.target );
					let target = radio.closest( 'fieldset' ).find( '.cn-privacy-consent-list-table-container' );
					let value = $( '[name="' + $( radio ).attr('name') + '"]:checked' ).val();
					
					if ( target.length > 0 ) {
						if ( value === 'all' ) {
							target.addClass( 'apply-all' );
							target.removeClass( 'apply-selected' );
						} else {
							target.addClass( 'apply-selected' );
							target.removeClass( 'apply-all' );
						}
					}
				} );
			}

			function handleFormStatus() {
				let el = $( this );

				// disable list table
				el.closest( 'table' ).addClass( 'loading' );

				$.post( ajaxurl, {
					action: 'cn_privacy_consent_form_status',
					form_id: el.data( 'form_id' ),
					source: el.data( 'source' ),
					status: el.is( ':checked' ) ? 1 : 0,
					nonce: cnArgs.noncePrivacyConsent
				} ).done( function( data ) {
					//
				} ).always( function() {
					// enable list table
					el.closest( 'table' ).removeClass( 'loading' );
				} );
			}
		} else if ( cnArgs.settingsTab === 'consent-logs' ) {
			function handleListTablePagination( container, perPage ) {
				let paginationLinks = container.find( '.pagination-links' );
				let firstPageButton = paginationLinks.find( '.first-page' );
				let lastPageButton = paginationLinks.find( '.last-page' );
				let nextPageButton = paginationLinks.find( '.next-page' );
				let prevPageButton = paginationLinks.find( '.prev-page' );
				let currentPageEl = paginationLinks.find( '.current-page' );
				let totalNumberofPages = parseInt( paginationLinks.data( 'total' ) ) || 1;

				// get table body
				var tableBody = container.find( 'table tbody' );

				// prepare array with table rows
				var dataRows = container.find( 'table' ).find( 'tbody tr' ).toArray();

				// set flag
				var firstTime = true;

				// add pagination
				container.pagination( {
					dataSource: dataRows,
					pageSize: perPage,
					showNavigator: false,
					showPrevious: false,
					showNext: false,
					showPageNumbers: false,
					callback: function( data, pagination ) {
						// skip showing/hiding table rows on init
						if ( firstTime ) {
							firstTime = false;

							return;
						}

						// hide all table rows
						tableBody.find( 'tr' ).hide();

						// display table rows
						for ( const el of data ) {
							$( el ).show();
						}
					}
				} );

				// handle first page
				firstPageButton.on( 'click', function( e ) {
					e.preventDefault();

					firstPageButton.addClass( 'disabled' );
					lastPageButton.removeClass( 'disabled' );
					nextPageButton.removeClass( 'disabled' );
					prevPageButton.addClass( 'disabled' );

					container.pagination( 'go', 1 );

					currentPageEl.html( 1 );
				} );

				// handle last page
				lastPageButton.on( 'click', function( e ) {
					e.preventDefault();

					firstPageButton.removeClass( 'disabled' );
					lastPageButton.addClass( 'disabled' );
					nextPageButton.addClass( 'disabled' );
					prevPageButton.removeClass( 'disabled' );

					container.pagination( 'go', totalNumberofPages );

					currentPageEl.html( totalNumberofPages );
				} );

				// handle next page
				nextPageButton.on( 'click', function( e ) {
					e.preventDefault();

					firstPageButton.removeClass( 'disabled' );
					prevPageButton.removeClass( 'disabled' );

					container.pagination( 'next' );

					let currentPage = container.pagination( 'getCurrentPageNum' );

					currentPageEl.html( currentPage );

					if ( currentPage === totalNumberofPages ) {
						lastPageButton.addClass( 'disabled' );
						nextPageButton.addClass( 'disabled' );
					}
				} );

				// handle previous page
				prevPageButton.on( 'click', function( e ) {
					e.preventDefault();

					lastPageButton.removeClass( 'disabled' );
					nextPageButton.removeClass( 'disabled' );

					container.pagination( 'previous' );

					let currentPage = container.pagination( 'getCurrentPageNum' );

					currentPageEl.html( currentPage );

					if ( currentPage === 1 ) {
						firstPageButton.addClass( 'disabled' );
						prevPageButton.addClass( 'disabled' );
					}
				} );
			}

			if ( cnArgs.settingsSection === 'cookie' ) {
				// consent logs
				$( '.cn-consent-log-item input[type="checkbox"]' ).on( 'change', function() {
					var el = $( this );
					var trEl = el.closest( 'tr' );
					var trDetailsId = trEl.attr( 'id' ) + '_details';
					var trDetailsIdHash = '#' + trDetailsId;
					var trDetailsRow = trEl.attr( 'id' ) + '_row';

					if ( el.is( ':checked' ) ) {
						// remove fake row
						$( '#' + trDetailsRow ).remove();

						// valid data already downloaded?
						if ( $( trDetailsIdHash ).length && $( trDetailsIdHash ).data( 'status' ) === 1 ) {
							$( trDetailsIdHash ).show();
						} else {
							var trDetailsDataEl = null;

							if ( $( trDetailsIdHash ).length ) {
								$( trDetailsIdHash ).show();

								trDetailsDataEl = $( trDetailsIdHash + ' .cn-consent-logs-data' );

								trDetailsDataEl.addClass( 'loading' );
								trDetailsDataEl.html( '<span class="spinner is-active"></span>' );
							} else {
								trEl.after( cnArgs.consentLogsTemplate );
								trEl.next().attr( 'id', trDetailsId );

								trDetailsDataEl = $( trDetailsIdHash + ' .cn-consent-logs-data' );
							}

							$.ajax( {
								url: cnArgs.ajaxURL,
								type: 'POST',
								dataType: 'json',
								data: {
									action: 'cn_get_cookie_consent_logs',
									nonce: cnArgs.nonceCookieConsentLogs,
									date: el.closest( 'tr' ).data( 'date' )
								}
							} ).done( function( result ) {
								if ( result.success ) {
									// set success status
									$( trDetailsIdHash ).data( 'status', 1 );

									// add table rows or display error
									trDetailsDataEl.find( '.spinner' ).replaceWith( result.data );

									// update duplicated ids
									trDetailsDataEl.find( '#the-list' ).attr( 'id', 'the-list-' + trEl.data( 'date' ) );

									// bind pagination script
									handleListTablePagination( trDetailsDataEl, 10 );
								} else {
									// set failed status
									$( trDetailsIdHash ).data( 'status', 0 );

									// display error
									trDetailsDataEl.find( '.spinner' ).replaceWith( cnArgs.consentLogsError );
								}
							} ).fail( function( result ) {
								// set failed status
								$( trDetailsIdHash ).data( 'status', 0 );

								// display error
								trDetailsDataEl.find( '.spinner' ).replaceWith( cnArgs.consentLogsError );
							} ).always( function( result ) {
								// hide spinner
								trDetailsDataEl.removeClass( 'loading' );
							} );
						}
					} else {
						$( trDetailsIdHash ).hide();
						$( trDetailsIdHash ).after( '<tr id="' + trDetailsRow + '" class="cn-consent-logs-row"><td colspan="6"></td></tr>' );
					}
				} );
			} else if ( cnArgs.settingsSection === 'privacy' ) {
				let container = $( '.cn-privacy-consent-logs-data' );

				$.ajax( {
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						nonce: cnArgs.noncePrivacyConsentLogs,
						action: 'cn_get_privacy_consent_logs'
					}
				} ).done( function( result ) {
					if ( result.success ) {
						// update list table
						container.html( result.data );

						// bind pagination script
						handleListTablePagination( container, 20 );
					} else {
						// display error
						container.find( '.spinner' ).replaceWith( cnArgs.consentLogsError );
					}
				} ).fail( function() {
					// display error
					container.find( '.spinner' ).replaceWith( cnArgs.consentLogsError );
				} ).always( function() {
					// hide spinner
					container.find( 'table' ).removeClass( 'loading' );
				} );
			}
		} else if ( cnArgs.settingsTab === 'settings' ) {
			// purge cache
			$( '#cn_app_purge_cache a' ).on( 'click', function( e ) {
				e.preventDefault();

				var el = this;

				$( el ).parent().addClass( 'loading' ).append( '<span class="spinner is-active" style="float: none"></span>' );

				var ajaxArgs = {
					action: 'cn_purge_cache',
					nonce: cnArgs.nonce
				};

				// network area?
				if ( cnArgs.network )
					ajaxArgs.cn_network = 1;

				$.ajax( {
					url: cnArgs.ajaxURL,
					type: 'POST',
					dataType: 'json',
					data: ajaxArgs
				} ).always( function( result ) {
					$( el ).parent().find( '.spinner' ).remove();
				} );
			} );

			// global override
			$( 'input[name="cookie_notice_options[global_override]"]' ).on( 'change', function() {
				$( '.cookie-notice-settings form' ).toggleClass( 'cn-options-disabled' );
			} );

			// refuse option
			$( '#cn_refuse_opt' ).on( 'change', function() {
				if ( $( this ).is( ':checked' ) )
					$( '#cn_refuse_opt_container' ).slideDown( 'fast' );
				else
					$( '#cn_refuse_opt_container' ).slideUp( 'fast' );
			} );

			// revoke option
			$( '#cn_revoke_cookies' ).on( 'change', function() {
				if ( $( this ).is( ':checked' ) )
					$( '#cn_revoke_opt_container' ).slideDown( 'fast' );
				else
					$( '#cn_revoke_opt_container' ).slideUp( 'fast' );
			} );

			// privacy policy option
			$( '#cn_see_more' ).on( 'change', function() {
				if ( $( this ).is( ':checked' ) )
					$( '#cn_see_more_opt' ).slideDown( 'fast' );
				else
					$( '#cn_see_more_opt' ).slideUp( 'fast' );
			} );

			// on scroll option
			$( '#cn_on_scroll' ).on( 'change', function() {
				if ( $( this ).is( ':checked' ) )
					$( '#cn_on_scroll_offset' ).slideDown( 'fast' );
				else
					$( '#cn_on_scroll_offset' ).slideUp( 'fast' );
			} );

			// conditional display option
			$( '#cn_conditional_display_opt' ).on( 'change', function() {
				if ( $( this ).is( ':checked' ) )
					$( '#cn_conditional_display_opt_container' ).slideDown( 'fast' );
				else
					$( '#cn_conditional_display_opt_container' ).slideUp( 'fast' );
			} );

			// privacy policy link
			$( '#cn_see_more_link-custom, #cn_see_more_link-page' ).on( 'change', function() {
				if ( $( '#cn_see_more_link-custom:checked' ).val() === 'custom' ) {
					$( '#cn_see_more_opt_page' ).slideUp( 'fast', function() {
						$( '#cn_see_more_opt_link' ).slideDown( 'fast' );
					} );
				} else if ( $( '#cn_see_more_link-page:checked' ).val() === 'page' ) {
					$( '#cn_see_more_opt_link' ).slideUp( 'fast', function() {
						$( '#cn_see_more_opt_page' ).slideDown( 'fast' );
					} );
				}
			} );

			// script blocking
			$( '#cn_refuse_code_fields' ).find( 'a' ).on( 'click', function( e ) {
				e.preventDefault();

				$( '#cn_refuse_code_fields' ).find( 'a' ).removeClass( 'nav-tab-active' );
				$( '.refuse-code-tab' ).removeClass( 'active' );

				var id = $( this ).attr( 'id' ).replace( '-tab', '' );

				$( '#' + id ).addClass( 'active' );
				$( this ).addClass( 'nav-tab-active' );
			} );

			// add new group of rules
			$( document ).on( 'click', '.add-rule-group', function( e ) {
				e.preventDefault();

				var html = $( '#rules-group-template' ).html();
				var group = $( '#rules-groups' );
				var groups = group.find( '.rules-group' );
				var groupID = ( groups.length > 0 ? parseInt( groups.last().attr( 'id' ).split( '-' )[2] ) + 1 : 1 );

				html = html.replace( /__GROUP_ID__/g, groupID );
				html = html.replace( /__RULE_ID__/g, 1 );

				group.append( '<div class="rules-group" id="rules-group-' + groupID + '">' + html + '</div>' );
				group.find( '.rules-group' ).last().fadeIn( 'fast' );
			} );

			// remove single rule or group
			$( document ).on( 'click', '.remove-rule', function( e ) {
				e.preventDefault();

				var number = $( this ).closest( 'tbody' ).find( 'tr' ).length;

				if ( number === 1 ) {
					$( this ).closest( '.rules-group' ).fadeOut( 'fast', function() {
						$( this ).remove();
					} );
				} else {
					$( this ).closest( 'tr' ).fadeOut( 'fast', function() {
						$( this ).remove();
					} );
				}
			} );

			// handle changing values for specified type of rules
			$( document ).on( 'change', '.rule-type', function() {
				var el = $( this );
				var td = el.closest( 'tr' ).find( 'td.value' );
				var select = td.find( 'select' );
				var spinner = td.find( '.spinner' );

				select.hide();
				spinner.fadeIn( 'fast' ).css( 'visibility', 'visible' );

				$.post( ajaxurl, {
					action: 'cn-get-group-rules-values',
					cn_param: el.val(),
					cn_nonce: cnArgs.nonceConditional
				} ).done( function( data ) {
					spinner.hide().css( 'visibility', 'hidden' );

					try {
						var response = $.parseJSON( data );

						// remove potential optgroups
						select.find( 'optgroup' ).remove();

						// replace old select options with new ones
						select.fadeIn( 'fast' ).find( 'option' ).remove().end().append( response.select );
					} catch( e ) {
						//
					}
				} ).fail( function() {
					//
				} );
			} );
		}

		// sync configuration button
		$( document ).on( 'click', '.cn-sync-config-btn', function( e ) {
			e.preventDefault();

			var button = $( this );
			var container = $( '#cn_sync_config' );
			var spinner = container.find( '.cn-sync-spinner' );
			var statusEl = container.find( '.cn-sync-status' );
			var messageEl = container.find( '.cn-sync-message' );

			// disable button and show spinner
			button.prop( 'disabled', true );
			spinner.addClass( 'is-active' );
			messageEl.hide().removeClass( 'notice notice-success notice-error' );

			// make AJAX request
			$.ajax( {
				url: cnArgs.ajaxURL,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'cn_api_request',
					request: 'sync_config',
					nonce: cnArgs.nonceSyncConfig,
					cn_network: cnArgs.network ? 1 : 0
				}
			} ).done( function( response ) {
				if ( response && response.success ) {
					// update last synced timestamp
					if ( response.timestamp ) {
						var date = new Date( response.timestamp.replace( /-/g, '/' ) );
						var dateOptions = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
						var formattedDate = date.toLocaleString( undefined, dateOptions );
						statusEl.text( 'Last synced: ' + formattedDate );
					}

					// show success message
					messageEl.html( '<p>' + ( response.message || 'Configuration synced successfully.' ) + '</p>' )
						.addClass( 'notice notice-success' )
						.fadeIn( 'fast' );

					// hide message after 5 seconds
					setTimeout( function() {
						messageEl.fadeOut( 'slow' );
					}, 5000 );
				} else {
					// show error message
					var errorMsg = response && response.error ? response.error : 'Failed to sync configuration. Please try again.';
					messageEl.html( '<p>' + errorMsg + '</p>' )
						.addClass( 'notice notice-error' )
						.fadeIn( 'fast' );
				}
			} ).fail( function( jqXHR, textStatus, errorThrown ) {
				// show error message
				messageEl.html( '<p>An error occurred while syncing configuration. Please try again.</p>' )
					.addClass( 'notice notice-error' )
					.fadeIn( 'fast' );
			} ).always( function() {
				// re-enable button and hide spinner
				button.prop( 'disabled', false );
				spinner.removeClass( 'is-active' );
			} );
		} );
    } );

} )( jQuery );