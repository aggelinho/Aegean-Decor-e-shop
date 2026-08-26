'use strict';

/* global yith_wcan_admin, ajaxurl */

import Ajax from './ajax';
import {
	$,
	block,
	unblock,
	serialize,
	removeHierarchyFromString,
} from '../../shortcodes/globals';
import { filterFieldsDependencies } from '../conf';

import YITH_WCAN_Dependencies_Handler from './yith-wcan-dependencies-handler';
import YITH_WCAN_Filter_Term from './yith-wcan-filter-term';
import YITH_WCAN_Filter_Range from './yith-wcan-filter-range';

export default class YITH_WCAN_Filter {
	/**
	 * Unique ID of the filter
	 */
	id;

	/**
	 * Dom object for containing filter form
	 */
	$filter;

	/**
	 * Preset object
	 */
	preset;

	/**
	 * Terms map
	 */
	terms = new Map();

	/**
	 * Ranges map
	 */
	ranges = new Map();

	constructor( $filter, preset ) {
		if ( ! $filter.length ) {
			return;
		}

		this.$filter = $filter;
		this.$toggleTitle = $filter.find( '.yith-toggle-title' );
		this.$title = $filter.find( 'h3.title' );
		this.preset = preset;

		if ( $filter.hasClass( 'initialized' ) ) {
			return;
		}

		this.init();
	}

	// init object
	init() {
		this.initTitle();
		this.initToggle();
		this.initSave();
		this.initDelete();
		this.initClone();
		this.initTerms();
		this.initRanges();
		this.initDependencies();
		this.initFields();

		this.$filter.addClass( 'initialized' );
	}

	initTitle() {
		const $field = this.$filter.find( '.heading-field' ).first();

		if ( this.$title.length && $field.length ) {
			$field.on( 'keyup', () => {
				const v = $field.val();
				this.$title.html(
					v ||
						`<span class="no-title">${ yith_wcan_admin.labels.no_title }</span>`
				);
			} );
		}
	}

	initToggle() {
		this.$toggleTitle.on( 'click', ( ev ) => {
			const $target = $( ev.target );

			if ( $target.closest( '.filter-actions' ).length ) {
				return;
			}

			this.isOpen() ? this.close() : this.open();
		} );
	}

	initDependencies() {
		new YITH_WCAN_Dependencies_Handler(
			this.$filter,
			filterFieldsDependencies,
			this
		);
	}

	initFields() {
		this.$filter.trigger( 'yith_fields_init' );

		this.initTermSearch();
		this.initCustomizeTerms();
		this.initTaxonomy();
		this.initType();
		this.initDesign();
		this.initCurrencyFields();
		this.initErrorsHandling();
	}

	initTermSearch() {
		const $termSearch = this.$filter.find( '.term-search' ).first(),
			$taxonomySelect = this.$filter.find( '.taxonomy' ).first(),
			$container = $termSearch.closest( '.yith-plugin-fw-field-wrapper' ),
			getAjaxParams = function ( params ) {
				return {
					term: params.term,
					all: typeof params.all !== 'undefined' ? params.all : 0,
					taxonomy: $taxonomySelect.val(),
					selected: $termSearch.val(),
					action: 'yith_wcan_search_term',
					security: yith_wcan_admin.nonce.search_term,
					lang: yith_wcan_admin.current_lang,
				};
			},
			select2_args = {
				placeholder: $( this ).data( 'placeholder' ),
				minimumInputLength: '1',
				templateSelection( option ) {
					return removeHierarchyFromString( option.text );
				},
				templateResult( option ) {
					return option.text.replace( '&amp;', '&' );
				},
				ajax: {
					url: ajaxurl,
					dataType: 'json',
					delay: 250,
					data: getAjaxParams,
					processResults( data ) {
						const terms = [];
						if ( data ) {
							$.each( data, function ( id, text ) {
								terms.push( { id, text } );
							} );
						}
						return {
							results: terms,
						};
					},
					cache: true,
				},
				sorter( items ) {
					return items;
				},
			};

		// init terms select
		$termSearch.selectWoo( select2_args );

		// on term changes redraw Customize terms section
		$termSearch.on( 'change', ( ev, ignoreVisibility ) =>
			this.afterTermsSelected( ignoreVisibility )
		);

		// add all button
		$container.find( '.yith-plugin-fw-select-all' ).on( 'click', ( ev ) => {
			ev.preventDefault();

			if ( ! this.confirmAddAllTerms( $taxonomySelect ) ) {
				return false;
			}

			block( $container );

			$.get( ajaxurl, getAjaxParams( { term: '', all: 1 } ) ).then(
				( data ) => {
					let selected = $termSearch.val();

					if ( ! selected ) {
						selected = [];
					}

					$termSearch.find( 'option' ).not( ':selected' ).remove();
					for ( const [ termId, termLabel ] of Object.entries(
						data
					) ) {
						selected.push( termId );

						$termSearch.append(
							$( '<option/>', {
								value: termId,
								text: termLabel,
							} )
						);
					}

					$termSearch.val( selected ).trigger( 'change', [ true ] );

					unblock( $container );
				}
			);

			return false;
		} );

		// remove all button
		$container
			.find( '.yith-plugin-fw-deselect-all' )
			.on( 'click', function ( ev ) {
				ev.preventDefault();

				$termSearch.find( 'option' ).remove().end().val( '' ).change();

				return false;
			} );
	}

	initCustomizeTerms() {
		const $customizeTerms = this.$filter
				.find( '.customize-terms' )
				.find( 'input' ),
			$orderBy = this.$filter.find( '.order-by' );

		$customizeTerms
			.on( 'change', () => {
				$orderBy
					.find( '[value="include"]' )
					.prop( 'disabled', ! $customizeTerms.is( ':checked' ) );
				$orderBy
					.removeClass( 'enhanced' )
					.trigger( 'wc-enhanced-select-init' );
				! $orderBy.val() && $orderBy.val( 'name' );

				this.afterTermsSelected();
			} )
			.change();
	}

	initTaxonomy() {
		const $taxonomySelect = this.$filter.find( '.taxonomy' ).first(),
			$filterDesign = this.$filter.find( '.filter-design' ).first();

		$filterDesign.on( 'change', () => this.customizeTermsNotice() );
		$taxonomySelect.on( 'change', () => {
			const prevValue = $taxonomySelect.data( 'taxonomy' ),
				currentValue = $taxonomySelect.val();

			prevValue !== currentValue && this.afterTaxonomyChange();
		} );
		setTimeout( function () {
			$taxonomySelect.select2();
		}, 1000 );
	}

	afterTaxonomyChange() {
		const $termSearch = this.$filter.find( '.term-search' ).first();

		// clear terms select when taxonomy is changed
		$termSearch.find( 'option' ).remove().end().change();

		// handle changes to Customize Terms description
		this.customizeTermsNotice();
	}

	customizeTermsNotice() {
		const $taxonomySelect = this.$filter.find( '.taxonomy' ).first(),
			$filterDesign = this.$filter.find( '.filter-design' ).first(),
			$customizeTermsWrapper = this.$filter
				.find( '.customize-terms' )
				.parent(),
			$customizeTermsRow = $customizeTermsWrapper.closest(
				'.yith-toggle-content-row'
			),
			$customizeTermsDescription =
				$customizeTermsWrapper.next( '.description' ),
			$customizeTerms = $customizeTermsWrapper.find( 'input' ),
			$wcclNotice = $customizeTermsDescription.find( '.wccl-notice' ),
			$imagesNotice = $customizeTermsDescription.find( '.images-notice' ),
			taxonomies = $taxonomySelect.data( 'taxonomies' ),
			taxonomy = $taxonomySelect.val(),
			filterDesign = $filterDesign.val();

		// show Colors & Labels notice
		if (
			! yith_wcan_admin.yith_wccl_enabled ||
			! taxonomies[ taxonomy ]?.is_attribute
		) {
			$wcclNotice.hide();
		} else {
			$wcclNotice.show();
		}

		// show images notice
		if (
			! taxonomies[ taxonomy ]?.supports_images ||
			'label' !== filterDesign
		) {
			$imagesNotice.hide();
		} else {
			$imagesNotice.show();
		}

		// hide option if not needed
		if (
			'color' === filterDesign &&
			( ! yith_wcan_admin.yith_wccl_enabled ||
				! taxonomies[ taxonomy ]?.is_attribute )
		) {
			$customizeTerms.prop( 'checked', true );
			$customizeTermsRow.addClass( 'disabled' );
		} else {
			$customizeTermsRow.removeClass( 'disabled' );
		}
	}

	initType() {
		const $filterType = this.$filter.find( '.filter-type' ),
			$filterDesign = this.$filter.find( '.filter-design' );

		$filterType
			.on( 'change', () => {
				const filterType = $filterType.val(),
					designs = Object.entries(
						yith_wcan_admin.supported_designs
					),
					unsupported = {
						review: [ 'color', 'label' ],
						price_range: [ 'color', 'label' ],
					};

				for ( const [ design, designName ] of designs ) {
					const $opt = $filterDesign.find( `[value="${ design }"]` );
					if ( unsupported?.[ filterType ]?.includes( design ) ) {
						$opt.remove();
						continue;
					}

					if ( $opt.length ) {
						continue;
					}

					$filterDesign.append(
						$( '<option/>', {
							value: design,
							text: designName,
						} )
					);
				}

				$filterDesign.change();
			} )
			.change();
	}

	initDesign() {
		const $filterType = this.$filter.find( '.filter-design' );

		$filterType.on( 'change', () => this.updateTermFields() ).change();
	}

	initCurrencyFields() {
		this.$filter.find( '[data-currency]' ).each( function () {
			const $field = $( this ),
				$currencySpan = $( '<span/>', {
					text: $field.data( 'currency' ),
					class: 'currency',
				} );

			$field.after( $currencySpan );
		} );
	}

	initErrorsHandling() {
		/**
		 * Invalid event does not bubble, so we need to handle it for each filter added
		 * https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/invalid_event
		 */
		this.$filter.find( ':input' ).on( 'invalid', ( ev ) => {
			const target = ev.target,
				$target = $( target );

			ev.preventDefault();

			this.preset.goToFilter( this.$filter, $target ).done( () => {
				this.addInputValidationMessage(
					$target,
					target.validationMessage
				);
			} );
		} );

		this.$filter.on( 'change keydown', ':input', function () {
			const $input = $( this );

			if ( $input.hasClass( 'validation-error' ) ) {
				// remove any validation class
				$input
					.removeClass( 'validation-error' )
					.removeClass( 'required-field-empty' );

				// remove any error message
				$input.next( '.validation-message' ).remove();
			}
		} );
	}

	// init actions

	initSave() {
		this.$filter
			.find( '.save' )
			.on( 'click', () => ( this.maybeSave(), false ) );
	}

	initClone() {
		this.$filter.find( '.clone' ).on( 'click', () => this.clone() );
	}

	initDelete() {
		this.$filter.find( '.delete' ).on( 'click', () => this.maybeDelete() );
	}

	// actions

	maybeSave() {
		if ( ! this.validate() ) {
			return false;
		}

		this.save().done( ( data ) => {
			this.preset.maybeSetId( data?.id );
			this.close();
		} );
	}

	save( maybeBlock = true ) {
		const preset_id = this.preset.getId(),
			filter = this.getData(),
			filter_id = this.getId(),
			$prev_filter = this.$filter.prev( '.filter-row' ),
			termsPool = this.getTermsPool(),
			prev_filter_id = $prev_filter.length
				? $prev_filter.attr( 'id' ).replace( 'filter_', '' )
				: -1;

		// send terms.
		filter.terms = Object.fromEntries(
			termsPool.map( ( term ) => [ term.term_id, term ] )
		);
		filter.terms_order = termsPool.map( ( term ) => term.term_id );

		return Ajax.post.call(
			maybeBlock ? this.$filter : null,
			'save_preset_filter',
			{
				preset: preset_id,
				filter,
				filter_id,
				prev_filter_id,
				_wpnonce: yith_wcan_admin.nonce.save_preset_filter,
			}
		);
	}

	clone() {
		this.preset.addFilter( this.getData() );
	}

	maybeDelete() {
		if ( confirm( yith_wcan_admin.messages.confirm_delete ) ) {
			this.delete().then( () => {
				this.$filter.remove();
				this.preset.afterFilterDelete( this );
			} );
		}
	}

	delete() {
		const preset_id = this.preset.getId();

		if ( ! preset_id ) {
			return Promise.resolve();
		}

		const filter_id = this.getId();

		if ( ! filter_id ) {
			return Promise.resolve();
		}

		return Ajax.post.call( this.$filter, 'delete_preset_filter', {
			preset: preset_id,
			filter_id,
			_wpnonce: yith_wcan_admin.nonce.delete_preset_filter,
		} );
	}

	validate() {
		const layout = this.preset.$layout.find( ':checked' ).val(),
			$title = this.findField( 'title', false ),
			title = $title.val();

		// horizontal layout needs title for each filter
		if ( 'horizontal' === layout && ! title ) {
			this.addInputValidationMessage(
				$title,
				yith_wcan_admin.messages.filter_title_required
			);
			this.preset.goToFilter( this );

			return false;
		}

		// trigger default browser validation.
		return this.$filter
			.find( ':input' )
			.get()
			.reduce( ( valid, node ) => {
				return valid && node.reportValidity();
			}, true );
	}

	updateLayout( layout ) {
		const $showToggle = this.findField( 'show_toggle' ),
			$tooltips = this.findField( 'tooltip', false );

		if ( 'horizontal' === layout ) {
			$showToggle
				?.hide()
				.find( ':input' )
				.prop( 'checked', false )
				.val( 'no' )
				.change();
			$tooltips?.parent().hide();
		} else {
			$showToggle?.show();
			$tooltips?.parent().show();
		}
	}

	// data handling

	getId() {
		return (
			this.id ||
			( this.id = this.$filter.attr( 'id' ).replace( 'filter_', '' ) )
		);
	}

	getRowIndex() {
		return this.$filter.data( 'item_key' );
	}

	getData() {
		return {
			...serialize( this.$filter, {
				formatName: ( v ) =>
					v.replace( /filters\[[0-9]+]\[([a-z_-]+)]/, '$1' ),
				filterItems: ( i, v ) => ! $( v ).is( '[name*="[terms]"]' ),
			} ),
			terms: this.getTermsPool(),
		};
	}

	populate( filterData ) {
		const row_id = this.getId();

		for ( const i in filterData ) {
			const value = filterData[ i ];

			let nameId =
					'terms' === i
						? `filters_${ row_id }_term_ids`
						: `filters_${ row_id }_${ i }`,
				$input = this.$filter.find( `#${ nameId }` );

			if ( ! $input.length && 'price_ranges' !== i ) {
				continue;
			}

			if ( 'terms' === i ) {
				for ( const term of value ) {
					if ( ! term?.label ) {
						continue;
					}

					const newOption = $( '<option/>', {
						value: term.term_id,
						text: term.label,
						selected: true,
					} );

					$input.append( newOption );
				}

				this.$filter.find( '.terms-wrapper' ).data( 'terms', value );
				$input.val( value.map( ( term ) => term.term_id ) ).change();
				this.updateTerms( true );
			} else if ( 'price_ranges' === i ) {
				const ranges = value;

				if ( 'object' !== typeof ranges ) {
					continue;
				}

				for ( const j in ranges ) {
					const range = ranges[ j ];

					this.addRange( range );
				}
			} else if ( $input.is( ':checkbox' ) ) {
				$input
					.prop( 'checked', value === 'yes' )
					.val( value )
					.change();
			} else if ( $input.is( '[data-type="radio"]' ) ) {
				$input
					.find( ':input' )
					.prop( 'checked', false )
					.filter( '[value="' + value + '"]' )
					.prop( 'checked', true )
					.change();
			} else if ( 'title' === i ) {
				$input.val( filterData[ i ] ).keyup();
			} else if ( 'taxonomy' === i ) {
				$input.data( 'taxonomy', value ).val( value );
			} else {
				$input.val( filterData[ i ] ).change();
			}
		}
	}

	// toggle accordions.

	isOpen() {
		return this.$filter.hasClass( 'filter-row-opened' );
	}

	open() {
		// fix title
		this.$toggleTitle
			.find( '.title-arrow' )
			.removeClass( 'yith-icon-arrow-right-alt' )
			.addClass( 'yith-icon-arrow-down-alt' );

		// animate content and return promise
		return this.$filter
			.addClass( 'filter-row-opened' )
			.find( '.yith-toggle-content' )
			.slideDown()
			.promise();
	}

	isClosed() {
		return ! this.isOpen();
	}

	close() {
		// fix title
		this.$filter
			.find( '.yith-toggle-title' )
			.find( '.title-arrow' )
			.addClass( 'yith-icon-arrow-right-alt' )
			.removeClass( 'yith-icon-arrow-down-alt' );

		// animate content and return promise
		return this.$filter
			.find( '.yith-toggle-content' )
			.slideUp( 400, () => {
				this.$filter.removeClass( 'filter-row-opened' );
			} )
			.promise();
	}

	// term handling

	getTermsPool() {
		return [
			...this.$filter.find( '.terms-wrapper' ).data( 'terms' ).values(),
		].filter( ( term ) => !! term );
	}

	getTermsToShow() {
		let termsPool = this.getTermsPool(),
			perPage = parseInt( yith_wcan_admin.terms_per_page );

		if (
			termsPool &&
			this.termsPaginated &&
			perPage &&
			Object.keys( termsPool ).length > perPage
		) {
			termsPool = termsPool.slice( 0, perPage );
		} else {
			this.$filter.find( '.show-more-terms' ).hide();
		}

		return termsPool;
	}

	getTermsType() {
		const $filterType = this.$filter.find( '.filter-design' ),
			filterType = $filterType?.val();

		if ( 'label' !== filterType && 'color' !== filterType ) {
			return 'labels_only';
		} else if ( 'color' === filterType ) {
			return 'complete';
		}

		return 'image_only';
	}

	initTerms() {
		const $terms = this.$filter.find( '.term-box' ),
			$orderBy = this.$filter.find( '.order-by' ),
			$showMore = this.$filter.find( '.show-more-terms' );

		this.termsPaginated = !! this.$filter.find( '.show-more-terms' ).length;

		for ( const termNode of $terms.get() ) {
			const $term = $( termNode ),
				term = new YITH_WCAN_Filter_Term( $term, this );

			this.terms.set( term.getId(), term );
		}

		$showMore.on( 'click', () => this.showMoreTerms() );
		$orderBy
			.on( 'change', () => {
				const v = $orderBy.val(),
					methodToRun =
						'include' === v
							? 'initTermsDragDrop'
							: 'destroyTermsDragDrop';

				this[ methodToRun ]();
			} )
			.change();

		this.updateTermFields();
	}

	initTermsDragDrop() {
		try {
			this.$filter.find( '.terms-wrapper' ).sortable( {
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				helper: 'clone',
				stop: () => {
					let termsPool = this.getTermsPool(),
						newPool = [],
						shownOrder = this.$filter
							.find( '.term-box' )
							.get()
							.map( ( termNode ) =>
								parseInt( termNode.dataset.term_id )
							);

					shownOrder.forEach( ( termId ) => {
						const termIndex = termsPool.findIndex(
							( term ) => term.term_id === termId
						);

						newPool.push( termsPool[ termIndex ] );
					} );

					if ( this.termsPaginated ) {
						newPool = newPool.concat(
							termsPool.slice( yith_wcan_admin.terms_per_page )
						);
					}

					this.$filter
						.find( '.terms-wrapper' )
						.data( 'terms', newPool );
				},
			} );
		} catch ( e ) {
			// do nothing.
		}
	}

	destroyTermsDragDrop() {
		try {
			this.$filter.find( '.terms-wrapper' ).sortable( 'destroy' );
		} catch ( e ) {
			// do nothing.
		}
	}

	// terms actions.

	afterTermsSelected( ignoreVisibility = false ) {
		const $termSearch = this.$filter.find( '.term-search' ),
			selectedTerms = $termSearch.val().map( ( id ) => parseInt( id ) ), //
			termsPool = this.getTermsPool(),
			termType = this.getTermsType(),
			existingTerms = termsPool.map( ( term ) => term.term_id ),
			toAdd = selectedTerms.filter(
				( termId ) => ! existingTerms.includes( termId )
			),
			toRemove = existingTerms.filter(
				( termId ) => ! selectedTerms.includes( termId )
			);

		toRemove.forEach( ( termId ) => {
			delete termsPool[
				termsPool.findIndex( ( term ) => termId === term?.term_id )
			];
		} );
		toAdd.forEach( ( termId ) => {
			const termLabel = $termSearch
				.find( `[value="${ termId }"]` )
				.text();

			termsPool.push( {
				id: this.getId(),
				term_id: termId,
				label: termLabel,
				name: termLabel,
				color_1: '#007694',
				color_2: '',
				mode: 'images_only' === termType ? 'image' : 'color',
			} );
		} );

		if ( toRemove.length || toAdd.length ) {
			toAdd.length && ( this.termsPaginated = false );
			this.$filter.find( '.terms-wrapper' ).data( 'terms', termsPool );
		}

		this.updateTerms( ignoreVisibility );
	}

	afterTermChanged( term ) {
		const termsPool = this.getTermsPool(),
			termId = term.getId(),
			termIndex = termsPool.findIndex(
				( _term ) => termId === _term?.term_id
			);

		termsPool[ termIndex ] = term.getData();
		this.$filter.find( '.terms-wrapper' ).data( 'terms', termsPool );
	}

	updateTerms( ignoreVisibility = false ) {
		const $termsContainer = this.$filter.find( '.terms-wrapper' );

		if ( ! ignoreVisibility && ! $termsContainer.is( ':visible' ) ) {
			return;
		}

		const selectedTerms = this.getTermsToShow(),
			newTerms = new Map(),
			newTermTemplate = wp.template( 'yith-wcan-filter-term' ),
			$existingTerms = $termsContainer.find( '.term-box' );

		$existingTerms.detach();

		if ( selectedTerms ) {
			for ( const selectedTerm of selectedTerms ) {
				let term, $term;

				if ( this.terms.has( selectedTerm.term_id ) ) {
					term = this.terms.get( selectedTerm.term_id );
					$term = term.$term;
				} else {
					$term = $( newTermTemplate( selectedTerm ) );
				}

				$term.length && $termsContainer.append( $term );
				term || ( term = new YITH_WCAN_Filter_Term( $term, this ) );
				term && newTerms.set( selectedTerm.term_id, term );
			}
		}

		this.terms = newTerms;
		this.updateTermFields();
	}

	showMoreTerms() {
		this.termsPaginated = false;
		this.updateTerms();
	}

	updateTermFields() {
		const type = this.getTermsType();

		[ ...this.terms.values() ].map( ( term ) => {
			return term.updateFields( type );
		} );
	}

	confirmAddAllTerms() {
		const $taxonomySelect = this.$filter.find( '.taxonomy' ).first(),
			v = $taxonomySelect.val(),
			details = $taxonomySelect.data( 'taxonomies' ),
			message = yith_wcan_admin.messages.confirm_add_all_terms;

		if ( details[ v ]?.terms_count && details[ v ]?.terms_count > 1 ) {
			return confirm(
				message.replace( '%s', details[ v ]?.terms_count )
			);
		}
		return true;
	}

	// ranges handling.

	initRanges() {
		const $ranges = this.$filter.find( '.range-box' );

		this.initAddRange();
		this.initRangesDragDrop();

		for ( const rangeNode of $ranges.get() ) {
			const $range = $( rangeNode ),
				range = new YITH_WCAN_Filter_Range( $range, this );

			this.ranges.set( range.getId(), range );
		}

		this.initRangesPosition();
	}

	initAddRange() {
		const $addRange = this.$filter.find( '.add-price-range' );

		$addRange.on( 'click', ( ev ) => {
			ev.preventDefault();

			this.addRange();
			this.initRangesPosition();
		} );
	}

	initRangesPosition() {
		for ( const range of this.ranges.values() ) {
			range.toggleUnlimited( range.$range.is( ':last-child' ) );
		}
	}

	initRangesDragDrop() {
		const $rangesWrapper = this.$filter.find( '.ranges-wrapper' );

		$rangesWrapper.sortable( {
			cursor: 'move',
			scrollSensitivity: 40,
			forcePlaceholderSize: true,
			helper: 'clone',
			stop: () => {
				const replaceIndex = ( prevIndex, currentIndex ) => {
					return ( i, attr ) =>
						attr.replace( prevIndex, currentIndex );
				};

				for ( const range of this.ranges.values() ) {
					const $range = range.$range,
						currentIndex = $range.index(),
						prevIndex = $range.data( 'range_id' ),
						replacer = replaceIndex( prevIndex, currentIndex );

					$range
						.data( 'range_id', currentIndex )
						.find( ':input' )
						.attr( 'name', replacer )
						.attr( 'id', replacer );
				}

				this.initRangesPosition();
			},
		} );
	}

	// ranges actions.

	addRange( data, index ) {
		const newRangeTemplate = wp.template( 'yith-wcan-filter-range' ),
			newRange = newRangeTemplate( {
				id: this.getRowIndex(),
				range_id: index || this.getNextRangeIndex(),
				min: 0,
				max: 0,
			} ),
			$newRange = $( newRange ),
			range = new YITH_WCAN_Filter_Range( $newRange, this );

		data && range.populate( data );

		this.ranges.set( range.getId(), range );
		this.$filter.find( '.ranges-wrapper' ).append( $newRange );

		return $newRange;
	}

	afterRangeDelete( range ) {
		this.ranges.delete( range.getId() );
		this.initRangesPosition();
	}

	// utils

	findField( field, returnContainer = true ) {
		let $field;

		switch ( field ) {
			case 'terms_options':
				$field = this.$filter.find( '.terms-wrapper' );
				break;
			case 'price_ranges':
				$field = this.$filter.find( '.ranges-wrapper' );
				break;
			default:
				$field = this.$filter.find( ':input[name*="[' + field + ']"]' );
				break;
		}

		if ( ! $field.length ) {
			return null;
		}

		if ( returnContainer ) {
			return $field.closest( '.yith-toggle-content-row' );
		}

		return $field;
	}

	getNextRangeIndex() {
		const $rangeWrapper = this.$filter.find( '.ranges-wrapper' );
		let currentIndex = $rangeWrapper.data( 'index' ),
			nextIndex = 0;

		if ( ! currentIndex ) {
			currentIndex = [ ...this.ranges.values() ].reduce( ( a, range ) => {
				return Math.max( a, range.getId() );
			}, 0 );
		}

		nextIndex = ++currentIndex;
		$rangeWrapper.data( 'index', nextIndex );

		return nextIndex;
	}

	addInputValidationMessage( $input, message ) {
		const $message = $( '<span/>', {
			class: 'validation-message',
			text: message,
		} );

		$input
			.addClass( 'required-field-empty' )
			.addClass( 'validation-error' )
			.next( '.validation-message' )
			.remove()
			.end()
			.after( $message );
	}
}
