jQuery(document).ready(function($) {
	var wcpConditionsTable = {
		operators: [],
		conditions: [],
		triggersInit: false,
		table: null,

		init: function() {
			var table = $( 'table.woo-conditional-payments-conditions' );

			if ( table.length == 0 ) {
				return;
			}

			this.table = table;

			this.operators = table.data( 'operators' );
			this.conditions = table.data( 'conditions' );

			this.initTagSearch();
			this.initBrandSearch();
			this.initCouponSearch();
			this.initMetaSearch();
			this.initDatepicker();
			this.insertExisting();
			this.insertEmpty();
			this.validateInputs();

			if ( ! this.triggersInit ) {
				this.triggerFieldUpdates();
				this.triggerRemoveCondition();
				this.triggerAddCondition();
				this.triggerToggleValueInputs();
				this.triggerToggleMatchByName();

				this.triggersInit = true;
			}
		},

		/**
		 * Meta search
		 */
		initMetaSearch: function() {
			$( document.body ).on( 'wc-enhanced-select-init', function() {
				$( ':input.wcp-product-meta-field-search' ).filter( ':not(.enhanced)' ).each( function() {
					var select2_args = {
						allowClear : $( this ).data( 'allow_clear' ) ? true : false,
						dropdownAutoWidth : true,
						placeholder : $( this ).data( 'placeholder' ),
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : 2,
						escapeMarkup : function( m ) {
							return m;
						},
						ajax: {
							url: wc_enhanced_select_params.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function( params ) {
								return {
									term: params.term,
									action: 'wcp_json_search_meta_keys',
								};
							},
							processResults: function( data ) {
								var terms = [];
								if ( data ) {
									$.each( data, function( id, term ) {
										terms.push({
											id: term.id,
											text: term.name
										});
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					$( this ).selectWoo( select2_args ).addClass( 'enhanced' );
				});
			} );
		},

		/**
		 * Tag search
		 */
		initTagSearch: function() {
			$( document.body ).on( 'wc-enhanced-select-init', function() {
				$( ':input.wcp-tag-search' ).filter( ':not(.enhanced)' ).each( function() {
					var select2_args = {
						allowClear        : $( this ).data( 'allow_clear' ) ? true : false,
						placeholder       : $( this ).data( 'placeholder' ),
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : 2,
						escapeMarkup      : function( m ) {
							return m;
						},
						ajax: {
							url: wc_enhanced_select_params.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function( params ) {
								return {
									term: params.term,
									action: 'wcp_json_search_tags',
								};
							},
							processResults: function( data ) {
								var terms = [];
								if ( data ) {
									$.each( data, function( id, term ) {
										terms.push({
											id: term.term_id,
											text: term.name
										});
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					$( this ).selectWoo( select2_args ).addClass( 'enhanced' );
				});
			} );
		},

		/**
		 * Brand search
		 */
		initBrandSearch: function() {
			$( document.body ).on( 'wc-enhanced-select-init', function() {
				$( ':input.wcp-brand-search' ).filter( ':not(.enhanced)' ).each( function() {
					var select2_args = {
						allowClear        : $( this ).data( 'allow_clear' ) ? true : false,
						placeholder       : $( this ).data( 'placeholder' ),
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : 2,
						escapeMarkup      : function( m ) {
							return m;
						},
						ajax: {
							url: wc_enhanced_select_params.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function( params ) {
								return {
									term: params.term,
									action: 'wcp_json_search_brands',
								};
							},
							processResults: function( data ) {
								var terms = [];
								if ( data ) {
									$.each( data, function( id, term ) {
										terms.push({
											id: term.term_id,
											text: term.name
										});
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					$( this ).selectWoo( select2_args ).addClass( 'enhanced' );
				});
			} );
		},

		/**
		 * Datepicker
		 */
		initDatepicker: function() {
			$( document.body ).on( 'wc-enhanced-select-init', function() {
				$( ':input.wcp-datepicker' ).filter( ':not(.enhanced)' ).each( function() {
					$( this ).datepicker({
						dateFormat: 'yy-mm-dd',
					}).addClass( 'enhanced' );
				} );
			} );
		},

		/**
		 * Coupon search
		 */
		initCouponSearch: function() {
			$( document.body ).on( 'wc-enhanced-select-init', function() {
				$( ':input.wcp-coupon-search' ).filter( ':not(.enhanced)' ).each( function() {
					var select2_args = {
						allowClear        : $( this ).data( 'allow_clear' ) ? true : false,
						placeholder       : $( this ).data( 'placeholder' ),
						minimumInputLength: 1,
						escapeMarkup      : function( m ) {
							return m;
						},
						ajax: {
							url: wc_enhanced_select_params.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function( params ) {
								return {
									coupon: params.term,
									action: 'wcp_json_search_coupons',
								};
							},
							processResults: function( data ) {
								var coupons = [];
								if ( data ) {
									$.each( data, function( id, coupon ) {
										coupons.push({
											id: coupon.id,
											text: coupon.code
										});
									});
								}
								return {
									results: coupons
								};
							},
							cache: true
						}
					};

					$( this ).selectWoo( select2_args ).addClass( 'enhanced' );
				});
			} );
		},

		/**
		 * Show correct fields when changing condition type
		 */
		triggerFieldUpdates: function() {
			var self = this;

			$( document ).on( 'change', 'select.wcp_condition_type_select', function() {
				var row = $( this ).closest( 'tr' );

				self.toggleOperators( row );
				self.toggleValueInputs( row );
			});
		},

		/**
		 * Insert existing conditions into the table
		 */
		insertExisting: function() {
			for ( var i = 0; i < this.conditions.length; i++ ) {
				this.addCondition( this.conditions[i] );
			}
		},

		/**
		 * Insert empty condition
		 */
		insertEmpty: function() {
			if ( $( 'tbody tr', this.table ).length == 0 ) {
				this.addCondition( {} );
			}
		},

		/**
		 * Toggle value inputs for a single row
		 */
		toggleValueInputs: function( row ) {
			this.removeClassStartingWith( row, 'wcp-operator-' );
			this.removeClassStartingWith( row, 'wcp-type-' );

			var type = $( 'select.wcp_condition_type_select', row ).val();
			var operator = $( 'select.wcp_operator_select', row ).val();

			row.addClass( 'wcp-operator-' + operator );
			row.addClass( 'wcp-type-' + type );

			$( '.wcp-values select:data(placeholder)', row ).trigger( 'change' );
		},

		/**
		 * Toggle operators
		 */
		toggleOperators: function( row ) {
			var operators = $( 'select.wcp_condition_type_select option:selected', row) .data( 'operators' );

			// Save current value
			var currentValue = $( 'select.wcp_operator_select', row ).val();

			// First remove all operators
			$( 'select.wcp_operator_select option', row ).remove();

			var self = this;
			$.each( operators, function( index, value ) {
				self.renderOperator( row, value );
			} );

			if ( typeof currentValue != 'undefined' ) {
				if ( $( 'select.wcp_operator_select option[value="' + currentValue + '"]', row ).length > 0 ) {
					$( 'select.wcp_operator_select', row ).val( currentValue ).trigger( 'change' );
				}
			}
		},

		/**
		 * Trigger toggle match by name
		 */
		triggerToggleMatchByName: function() {
			var self = this;

			$( document ).on( 'change', '.wcp_shipping_method_value_input select', function( e ) {
				var row = $( this ).closest( 'tr' );

				self.toggleMatchByName( row );
			} );
		},

		/**
		 * Toggle match by name
		 */
		toggleMatchByName: function( row ) {
			var methods = $( '.wcp_shipping_method_value_input select', row ).val();
			var nameMatch = $.inArray( '_name_match&_name_match', methods ) !== -1;

			$( '.wcp-match-by-name', row ).toggle( nameMatch );
		},

		/**
		 * Render operator
		 */
		renderOperator: function( row, operator ) {
			var operatorTitle = this.operators[operator];

			$( 'select.wcp_operator_select', row ).append( '<option value="' + operator + '">' + operatorTitle + '</option>' );
		},

		/**
		 * Add new condition
		 */
		addCondition: function( data ) {
			// Get index
			var index = this.table.data( 'index' );
			if (typeof index == 'undefined') { index = 0; }
			data['index'] = index;

			// Add one to conditions table index
			this.table.data( 'index', index + 1 );

			// Get template
			var row_template = wp.template( 'wcp_row_template' );

			// Add products
			var products_data = this.table.data( 'selected-products' );
			data.selected_products = [];
			if ( typeof data.product_ids !== 'undefined' && data.product_ids !== null && data.product_ids.length > 0 ) {
				jQuery.each( data.product_ids, function( index, product_id ) {
					if ( typeof products_data[product_id] !== 'undefined' ) {
						data.selected_products.push({
							'id': product_id,
							'title': products_data[product_id]
						});
					}
				});
			}

			// Add coupons
			var coupons_data = this.table.data( 'selected-coupons' );
			data.selected_coupons = [];
			if ( typeof data.coupon_ids !== 'undefined' && data.coupon_ids !== null && data.coupon_ids.length > 0 ) {
				jQuery.each( data.coupon_ids, function( index, coupon_id ) {
					if ( typeof coupons_data[coupon_id] !== 'undefined' ) {
						data.selected_coupons.push({
							'id': coupon_id,
							'title': coupons_data[coupon_id]
						});
					}
				});
			}

			// Add tags
			var tags_data = this.table.data( 'selected-tags' );
			data.selected_tags = [];
			if ( typeof data.product_tags !== 'undefined' && data.product_tags !== null && data.product_tags.length > 0 ) {
				jQuery.each( data.product_tags, function( index, tag_id ) {
					if ( typeof tags_data[tag_id] !== 'undefined' ) {
						data.selected_tags.push({
							'id': tag_id,
							'title': tags_data[tag_id]
						});
					}
				});
			}

			// Add brands
			var brands_data = this.table.data( 'selected-brands' );
			data.selected_brands = [];
			if ( typeof data.product_brands !== 'undefined' && data.product_brands !== null && data.product_brands.length > 0 ) {
				jQuery.each( data.product_brands, function( index, brand_id ) {
					if ( typeof brands_data[brand_id] !== 'undefined' ) {
						data.selected_brands.push({
							'id': brand_id,
							'title': brands_data[brand_id]
						});
					}
				});
			}

			// Render template and add to the table
			$( 'tbody', this.table ).append( row_template( data ) );

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			let addedRow = $( 'tbody tr:last-child', this.table );
			this.toggleOperators( addedRow );
			this.toggleValueInputs( addedRow );
			this.toggleMatchByName( addedRow );
		},

		/**
		 * Remove selected conditions when clicking the button
		 */
		triggerRemoveCondition: function() {
			var self = this;

			$( document ).on( 'click', 'a.wcp-remove-condition', function( e ) {
				e.preventDefault();
				
				$( this ).closest( 'tr' ).remove();
				self.insertEmpty();
			});
		},

		/**
		 * Add new condition when clicking the Add button
		 */
		triggerAddCondition: function() {
			var self = this;

			$( document ).on( 'click', 'button#wcp-add-condition', function() {
				self.addCondition( {} );
			});
		},

		/**
		 * Update value inputs when changing operator type
		 */
		triggerToggleValueInputs: function() {
			var self = this;

			$( document ).on('change', 'select.wcp_operator_select', function() {
				var row = $( this ).closest( 'tr' );
				self.toggleValueInputs( row );
			});
		},

		/**
		 * Validate inputs
		 */
		validateInputs: function() {
			var self = this;

			// Clear previous errors
			$(document).on('input', 'input.wcp_text_value_input', function () {
				self.clearInputValidation($(this));
			});
			$(document).on('change', '.condition_row select', function () {
				let row = $(this).closest('tr');
				self.clearInputValidation($('input.wcp_text_value_input', row));
			});

			$('form#mainform').on('submit', function(e) {
				// Check that all fields with "greater than", "less than" etc.
				// have numerical values EXCEPT date and time
				let rowElements = [
					'tr.wcp-operator-gt',
					'tr.wcp-operator-gte',
					'tr.wcp-operator-lt',
					'tr.wcp-operator-lte',
					'tr.wcp-operator-e',
				].join(', ');
				let exclude = [
					'.wcp-type-date',
					'.wcp-type-time',
				].join(', ');

				$(rowElements).each(function () {
					if ($(this).is(':not(' + exclude + ')')) {
						let inputEl = $('input.wcp_text_value_input', this);

						if (inputEl.is(':visible')) {
							let inputValue = inputEl.val();
							inputValue = inputValue.trim().replace(',', '.');

							if (inputValue === '' || isNaN(inputValue)) {
								self.markNumericInvalid(inputEl, 'Please enter a number.');
								e.preventDefault();
							}
						}
					}
				});
			});
		},

		/**
		 * Mark input as invalid
		 */
		markNumericInvalid: function($input, message) {
			$input.addClass('has-error');
			$input[0].setCustomValidity(message);
			$input[0].reportValidity();
		},

		/**
		 * Clear input validation
		 */
		clearInputValidation: function($input) {
			$input.removeClass('has-error');
			$input[0].setCustomValidity('');
		},

		removeClassStartingWith: function(el, filter) {
			el.removeClass(function (index, className) {
				return (className.match(new RegExp("\\S*" + filter + "\\S*", 'g')) || []).join(' ');
			});
		}
	};

	wcpConditionsTable.init();

	var wcpActionsTable = {
		actions: [],
		triggersInit: false,
		table: null,

		init: function() {
			var table = $( 'table.woo-conditional-payments-actions' );

			if ( table.length == 0 ) {
				return;
			}

			this.table = table;

			this.actions = table.data( 'actions' );

			this.insertExisting();
			this.insertEmpty();

			if ( ! this.triggersInit ) {
				this.triggerFieldUpdates();
				this.triggerAddAction();
				this.triggerRemoveAction();
				this.triggerHelpModal();

				this.triggersInit = true;
			}
		},

		/**
		 * Show correct fields when changing action type
		 */
		triggerFieldUpdates: function() {
			var self = this;

			$( document ).on( 'change', 'select.wcp_action_type_select', function() {
				var row = $( this ).closest( 'tr' );

				self.toggleValueInputs( row );
			});
		},

		/**
		 * Insert existing actions into the table
		 */
		insertExisting: function() {
			for ( var i = 0; i < this.actions.length; i++ ) {
				this.addAction( this.actions[i] );
			}
		},

		/**
		 * Insert empty condition
		 */
		insertEmpty: function() {
			if ( $( 'tbody tr', this.table ).length == 0 ) {
				this.addAction( {} );
			}
		},

		/**
		 * Toggle value inputs for a single row
		 */
		toggleValueInputs: function( row ) {
			this.removeClassStartingWith( row, 'wcp-action-type-' );

			var type = $( 'select.wcp_action_type_select', row ).val();

			row.addClass( 'wcp-action-type-' + type );

			$( '.wcp-methods select:data(placeholder)', row ).trigger( 'change' );
		},

		/**
		 * Add action
		 */
		addAction: function( data ) {
			// Get index
			var index = this.table.data( 'index' );
			if (typeof index == 'undefined') { index = 0; }
			data['index'] = index;

			// Add one to conditions table index
			this.table.data( 'index', index + 1 );

			// Get template
			var row_template = wp.template( 'wcp_action_row_template' );

			// Render template and add to the table
			$( 'tbody', this.table ).append( row_template( data ) );

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			let addedRow = $( 'tbody tr:last-child', this.table );
			this.toggleValueInputs( addedRow );
		},

		/**
		 * Remove selected actions when clicking the button
		 */
		triggerRemoveAction: function() {
			var self = this;

			$( document ).on( 'click', 'a.wcp-remove-action', function( e ) {
				e.preventDefault();
				
				$( this ).closest( 'tr' ).remove();
				self.insertEmpty();
			});
		},

		/**
		 * Add new action when clicking the Add button
		 */
		triggerAddAction: function() {
			var self = this;

			$( document ).on( 'click', 'button#wcp-add-action', function() {
				self.addAction( {} );
			});
		},

		/**
		 * Trigger help modal
		 */
		triggerHelpModal: function() {
			$('#wcp-actions-help-modal').dialog({
				autoOpen: false,
				modal: true,
				width: 600,
				dialogClass: 'wcp-actions-help-modal-container',
				buttons: { Close: function() { $(this).dialog('close'); } }
			});

			$('.wcp-action-help-tip').on('click', function(e) {
				e.preventDefault();
				$('#wcp-actions-help-modal').dialog('open');
			});

			$(document).on('click', '.ui-widget-overlay', function() {
				$('#wcp-actions-help-modal').dialog('close');
			});
		},

		removeClassStartingWith: function(el, filter) {
			el.removeClass(function (index, className) {
				return (className.match(new RegExp("\\S*" + filter + "\\S*", 'g')) || []).join(' ');
			});
		}
	};
	wcpActionsTable.init();

	/**
	 * Toggle Pro features
	 */
	$( 'input[name="wcp_pro_features"]' ).change( function( e ) {
		var displayFeatures = $( this ).is( ':checked' );

		$( '.wcp-table .wcp-condition option:disabled').toggle( displayFeatures );
		$( '.wcp-table .wcp-action option:disabled').toggle( displayFeatures );

		$( '.wcp-table .wcp-condition optgroup' ).each( function() {
			var visibleOptions = $( 'option:not(:disabled)', this ).length;

			$( this ).toggle( ( visibleOptions > 0 || displayFeatures ) );
		} );

		$( '.wcp-table .wcp-action optgroup' ).each( function() {
			var visibleOptions = $( 'option:not(:disabled)', this ).length;

			$( this ).toggle( ( visibleOptions > 0 || displayFeatures ) );
		} );
	} );
	$( 'input[name="wcp_pro_features"]' ).trigger( 'change' );

	/**
	 * Sortable rulesets
	 */
	if ( ! woo_conditional_payments.disable_sortable ) {
		$( 'table.wcp-rulesets tbody' ).sortable( {
			items: 'tr',
			cursor: 'move',
			axis: 'y',
			handle: 'td.wcp-ruleset-sort',
			scrollSensitivity: 40
		} );
	}

	/**
	 * Warn when deleting ruleset
	 */
	$( document ).on( 'click', '.wcp-ruleset-delete', function( e ) {
		return confirm( "Are you sure?" );
	} );

	/**
	 * Open health check issue
	 */
	$( document ).on( 'click', '.woo-conditional-payments-health-check .issue-container .title', function( e ) {
		var container = $( this ).closest( '.issue-container' );

		$( '.details', container ).slideToggle();
		$( '.toggle-indicator' ).toggleClass( 'open' );
	} );

	/**
	 * AJAX toggle
	 */
	$( document ).on( 'click', '.wcp-ruleset-status .woocommerce-input-toggle', function( e ) {
		e.preventDefault();

		var self = this;

		var data = {
			id: $( this ).data( 'id' ),
			security: woo_conditional_payments.nonces.ruleset_toggle
		};

		$.ajax( {
			type: 'post',
			url: woo_conditional_payments.ajax_urls.ruleset_toggle,
			data: data,
			dataType: 'json',
			beforeSend: function() {
				$( self ).removeClass( 'woocommerce-input-toggle--enabled woocommerce-input-toggle--disabled' );
				$( self ).addClass( 'woocommerce-input-toggle--loading' );
			},
			success: function( response ) {
				$( self ).removeClass( 'woocommerce-input-toggle--loading' );

				if ( response.enabled ) {
					var cssClass = 'woocommerce-input-toggle--enabled';
				} else {
					var cssClass = 'woocommerce-input-toggle--disabled';
				}

				$( self ).addClass( cssClass );
			},
			error: function() {
				alert( 'Unknown error' );
			},
			complete: function() {

			}
		} );
	} );

	/**
	 * Welcome form submit
	 */
	var wcpWelcomeForm = $( '#wcp-welcome-form' ).closest( 'form' );
	if ( wcpWelcomeForm.length > 0 ) {
		$( document ).on( 'submit', wcpWelcomeForm, function( e ) {
			e.preventDefault();

			$.ajax( {
				type: 'post',
				url: woo_conditional_payments.ajax_urls.welcome_submit,
				data: {
					license_key: $( 'input#wcp-license-key-input' ).val(),
					security: woo_conditional_payments.nonces.welcome_submit,
				},
				dataType: 'json',
				beforeSend: function() {
					$( '.wcp-spinner' ).css( 'visibility', 'visible' );
					$( '.wcp-success, .wcp-error' ).hide();
				},
				success: function( response ) {
					if (response.status === 'success') {
						$( '.wcp-success' ).show();

						// Remove "Changes you made may not be saved." warning
						window.onbeforeunload = null;

						// Reload the current page
						setTimeout(function() {
							location.reload();
						}, 3000);
					} else {
						$( '.wcp-error' ).show();
						$( '.wcp-error' ).text( response.error );
					}
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					console.log( jqXHR, textStatus, errorThrown );
					alert( jqXHR.status + " " + jqXHR.responseText + " " + textStatus + " " + errorThrown );
				},
				complete: function() {
					$( '.wcp-spinner' ).css( 'visibility', 'hidden' );
				}
			} );
		} );
	}
});
