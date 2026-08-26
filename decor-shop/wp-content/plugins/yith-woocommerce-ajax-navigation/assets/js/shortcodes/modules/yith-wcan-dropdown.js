'use strict';

/* global globalThis, jQuery, yith_wcan_shortcodes, accounting */

import { $, keys } from '../globals.js';

export default class YITH_WCAN_Dropdown {
	// current button
	$originalSelect = null;

	// list of current items.
	_items = [];

	// main element
	$_main = null;

	// label element
	$_label = null;

	// dropdown
	$_dropdown = null;

	// search input
	$_search = null;

	// show more link
	$_showMore = null;

	// items list
	$_items = null;

	// whether select should paginate.
	paginate = false;

	// whether select has more items than those shown.
	hasMore = false;

	// whether items list needs update.
	needsRefresh = true;

	// whether select is multiple
	multiple = false;

	// current page
	currentPage = 1;

	// options
	options = {};

	// init object
	constructor( el, opts ) {
		this.$originalSelect = el;

		if ( ! this.$originalSelect.is( 'select' ) ) {
			return;
		}

		const defaultPerPage = this.$originalSelect.data( 'per_page' ),
			defaultOrder = this.$originalSelect.data( 'order' ),
			defaultAll = this.$originalSelect.data( 'all-label' ),
			defaults = {
				showSearch: this.$originalSelect.data( 'show_search' ),
				paginate: this.$originalSelect.data( 'paginate' ),
				perPage: defaultPerPage ? defaultPerPage : 10,
				hasMore: false,
				order: defaultOrder ? defaultOrder : 'ASC',
				getElements: null,
				labels: {
					emptyLabel: defaultAll
						? defaultAll
						: yith_wcan_shortcodes.labels?.empty_option,
					searchPlaceholder:
						yith_wcan_shortcodes.labels?.search_placeholder,
					noItemsFound: yith_wcan_shortcodes.labels?.no_items,
					showMore: yith_wcan_shortcodes.labels?.show_more,
				},
			};

		this.multiple = this.$originalSelect.prop( 'multiple' );
		this.options = $.extend( defaults, opts );
		this.paginate = this.options.paginate || false;
		this.hasMore = this.options.hasMore || false;

		this._hideSelect();
		this._initTemplate();
		this._initActions();

		this.$originalSelect.data( 'dropdown', this ).addClass( 'enhanced' );
	}

	// hide select
	_hideSelect() {
		this.$originalSelect.hide();
	}

	// create dropdown
	_initTemplate() {
		const $mainSpan = $( '<div/>', {
				class: 'yith-wcan-dropdown closed',
				'aria-label': this.$originalSelect.attr( 'aria-label' ),
				tabindex: 0,
			} ),
			$labelSpan = $( '<div/>', {
				class: 'dropdown-label',
				html: this.getLabel(),
			} ),
			$dropdownSpan = $( '<div>', {
				class: 'dropdown-wrapper',
			} ),
			$matchingItemsList = $( '<ul/>', {
				class: 'matching-items filter-items',
			} );

		$dropdownSpan.append( $matchingItemsList );
		$mainSpan.append( $labelSpan ).append( $dropdownSpan );

		if ( this.options.showSearch ) {
			this._initSearchTemplate( $dropdownSpan );
		}

		if ( this.options.paginate ) {
			this._initShowMoreTemplate( $dropdownSpan );
		}

		this.$originalSelect.after( $mainSpan );
		this.$_main = $mainSpan;
		this.$_label = $labelSpan;
		this.$_dropdown = $dropdownSpan;
		this.$_items = $matchingItemsList;
	}

	// create search field
	_initSearchTemplate( $dropdwonSpan ) {
		const $container = $( '<div/>', {
				class: 'search-field-container',
			} ),
			$search = $( '<input/>', {
				name: 's',
				class: 'search-field',
				type: 'search',
				placeholder: this.options.labels.searchPlaceholder,
			} ).attr( 'autocomplete', 'off' );

		$container.append( $search ).prependTo( $dropdwonSpan );
		this.$_search = $search;
	}

	// create showMore field
	_initShowMoreTemplate( $dropdwonSpan ) {
		const $showMore = $( '<a/>', {
			class: 'show-more',
			text: this.options.labels.showMore?.replace(
				'%d',
				this.options.perPage
			),
		} );

		$showMore.on( 'click', this.loadNextPage.bind( this ) ).hide();

		$dropdwonSpan.append( $showMore );
		this.$_showMore = $showMore;
	}

	// init actions performed over dropdown elements
	_initActions() {
		// main open event
		this.$_main
			?.on( 'click', ( ev ) => {
				ev.stopPropagation();
				this.toggleDropdown();
			} )
			?.on( 'keyup', ( ev ) => {
				if ( ! Object.values( keys ).includes( ev.keyCode ) ) {
					return;
				}

				ev.preventDefault();

				if ( [ keys.enter, keys.space ].includes( ev.keyCode ) ) {
					this.toggleDropdown();
					return false;
				} else if ( keys.esc === ev.keyCode ) {
					this.closeDropdown();
					return false;
				}
			} );
		this.$_dropdown.on( 'click', ( ev ) => {
			ev.stopPropagation();
		} );

		// search event
		this.$_search
			?.on( 'keyup search', ( { keyCode } ) => {
				if ( keyCode && keyCode === keys.esc ) {
					return;
				}

				this.paginate = false;

				this._populateItems().then( () => {
					this.needsRefresh = true;
				} );
				return false;
			} )
			.on( 'change', () => false );

		// select event
		this.$_items.on( 'change', ':input', ( ev ) => {
			let $li = $( ev.target ).closest( 'li' ),
				value = $li.data( 'value' ),
				isActive = false;

			if (
				$li.hasClass( 'disabled' ) &&
				! this.isValueSelected( value )
			) {
				return false;
			}

			$li.toggleClass( 'active' );
			isActive = $li.hasClass( 'active' );

			this._changeItemStatus( value, isActive );
			return false;
		} );
		this.$_items.on( 'click', 'li:not(.checkbox) a', ( ev ) => {
			let $li = $( ev.target ).closest( 'li' ),
				value = $li.data( 'value' ),
				isActive = false;

			ev.preventDefault();

			if (
				$li.hasClass( 'disabled' ) &&
				! this.isValueSelected( value )
			) {
				return false;
			}

			$li.toggleClass( 'active' );
			isActive = $li.hasClass( 'active' );

			if ( isActive ) {
				$li.siblings().removeClass( 'active' );
			}

			this._changeItemStatus( value, isActive );
			return false;
		} );
		this.$_items.on( 'click', 'label > a', ( ev ) => {
			const input = $( ev.target ).parent().find( ':input' );

			ev.preventDefault();

			if (
				input.is( '[type="radio"]' ) ||
				input.is( '[type="checkbox"]' )
			) {
				input.prop( 'checked', ! input.prop( 'checked' ) );
			}

			input.change();
		} );

		// select change
		this.$originalSelect.on( 'change', ( ev, selfOriginated ) => {
			if ( selfOriginated ) {
				return;
			}

			this.updateLabel();
		} );

		// close dropdown on external click; do this handler only once for any dropdown in the page
		if ( ! globalThis?.yith_wcan_dropdown_init ) {
			$( document ).on( 'click', this._closeAllDropdowns );
			globalThis.yith_wcan_dropdown_init = true;
		}
	}

	// open dropdown
	openDropdown() {
		this.$_main?.addClass( 'open' ).removeClass( 'closed' );
		this._afterDropdownOpen();
	}

	// close dropdown
	closeDropdown() {
		this.$_main?.removeClass( 'open' ).addClass( 'closed' );
	}

	// close all dropdowns
	_closeAllDropdowns() {
		const dropdowns = $( document )
			.find( 'select.enhanced' )
			.filter( function ( i, select ) {
				const $el = $( select );

				return !! $el.data( 'dropdown' );
			} );

		dropdowns.each( function () {
			$( this ).data( 'dropdown' ).closeDropdown();
		} );
	}

	// close other dropdowns
	_closeOtherDropdowns() {
		const dropdowns = $( document )
			.find( 'select.enhanced' )
			.filter( ( i, select ) => {
				const $el = $( select );

				return (
					!! $el.data( 'dropdown' ) &&
					! $el.is( this.$originalSelect )
				);
			} );

		dropdowns.each( function () {
			$( this ).data( 'dropdown' ).closeDropdown();
		} );
	}

	// toggle dropdown
	toggleDropdown() {
		this.$_main?.toggleClass( 'open' ).toggleClass( 'closed' );

		if ( this.$_main?.hasClass( 'open' ) ) {
			this._afterDropdownOpen();
		}
	}

	// perform operations after dropdown is open
	_afterDropdownOpen() {
		this._closeOtherDropdowns();

		if ( this.$_search?.length ) {
			this.$_search.val( '' );
		}

		this._maybePopulateItems();
	}

	async getItems( search ) {
		if ( ! this._items.length ) {
			const $options = this.getOptions();

			$options.each( ( i, el ) => {
				const t = $( el ),
					value = t.val(),
					label = t.html();

				this._items.push( {
					value,
					label,
				} );
			} );
		}

		let items = await this.getMatchingElements( search );
		const perPage = this.paginate ? this.options.perPage : 0;

		if ( perPage && items.length > perPage ) {
			this.hasMore = true;
			items = items.slice( 0, perPage );
		}

		return items;
	}

	// get elements
	getMatchingElements( search ) {
		let matchingElements = this._items,
			promise;

		promise = new Promise( ( resolve ) => {
			matchingElements = search
				? matchingElements.filter( ( { label, value } ) => {
						const regex = new RegExp( '.*' + search + '.*', 'i' );
						return regex.test( value ) || regex.test( label );
				  } )
				: matchingElements;

			// then retrieve additional items
			if ( this.options.getElements ) {
				// we're expecting key => value pairs
				this.options.getElements
					.call( this, search )
					.then( ( retrievedElements ) => {
						if ( retrievedElements ) {
							// reformat retrieved array
							retrievedElements = Object.keys(
								retrievedElements
							).reduce( ( a, i ) => {
								if ( !! retrievedElements[ i ].label ) {
									a.push( retrievedElements[ i ] );
									return a;
								}

								a.push( {
									label: retrievedElements[ i ],
									value: i,
								} );
								return a;
							}, [] );

							// merge found results with options
							matchingElements = [
								...matchingElements,
								...retrievedElements,
							];
						}

						resolve( this._formatItems( matchingElements ) );
					} );
			} else {
				resolve( this._formatItems( matchingElements ) );
			}
		} );

		return promise;
	}

	// format items as key/value pairs for further processing
	_formatItems( items ) {
		let indexes = [];

		// remove duplicates and sort array of results
		return items.filter( ( { value, label } ) => {
			if ( -1 === indexes.indexOf( value ) ) {
				indexes.push( value );

				// checks if select has a related option.
				if ( ! this.getOptionByValue( value ).length ) {
					this.$originalSelect.append(
						`<option class="filter-item" value="${ value }">${ label }</option>`
					);
				}

				// add item to final array of elements.
				return true;
			}

			// item should not be included in the final set.
			return false;
		} );
	}

	// generate item to append to items list
	_generateItem( value, label ) {
		let active = this.isValueSelected( value ),
			option = this.getOptionByValue( value ),
			$item = $( '<li/>', {
				'data-value': value,
				class: option.length ? option.attr( 'class' ) : '',
				tabindex: 0,
			} ),
			$anchor;

		if ( option.length ) {
			const template = option.data( 'template' ),
				count = option.data( 'count' );

			label = template ? template : label;

			if ( !! count ) {
				label += count;
			}
		}

		$anchor = $( '<a/>', {
			href: option.length ? option.data( 'filter_url' ) : '#',
			html: label,
			rel: 'nofollow',
			'data-title': option.length ? option.data( 'title' ) : '',
			tabindex: -1,
		} );

		$item.on( 'keyup', ( ev ) => {
			if ( ! Object.values( keys ).includes( ev?.keyCode ) ) {
				return;
			}

			ev.preventDefault();

			if ( [ keys.space, keys.enter ].includes( ev?.keyCode ) ) {
				$anchor.click();
			} else if ( keys.next === ev.keyCode ) {
				$item.next().focus();
			} else if ( keys.prev === ev.keyCode ) {
				$item.prev().focus();
			}
		} );

		if ( this.multiple ) {
			const $checkbox = $( '<input/>', {
					type: 'checkbox',
					tabindex: -1,
					value,
				} ),
				$label = $( '<label>' );

			$checkbox.prop( 'checked', active );
			$label.prepend( $checkbox ).append( $anchor );
			$item.append( $label ).addClass( 'checkbox' );
		} else {
			$item.append( $anchor );
		}

		active ? $item.addClass( 'active' ) : $item.removeClass( 'active' );

		return $item;
	}

	_maybePopulateItems() {
		if ( ! this.needsRefresh ) {
			return;
		}

		this._populateItems();
	}

	_populateItems() {
		const search = this.$_search?.length ? this.$_search.val() : false;

		return this.getItems( search ).then( ( items ) => {
			this._emptyItems();
			this._hideLoadMore();

			this.$_items.append(
				items.map( ( { label, value } ) =>
					this._generateItem( value, label )
				)
			);
			this.$originalSelect.trigger( 'yith_wcan_dropdown_updated' );
			this.needsRefresh = false;

			if ( this.paginate && this.hasMore ) {
				this._showLoadMore();
			}
		} );
	}

	// load next page of items
	async loadNextPage() {
		this.paginate = false;

		this._populateItems().then( () => {
			this.hasMore = false;
		} );
	}

	// set an item as active
	_selectItem( value ) {
		return this._changeItemStatus( value, true );
	}

	// disable an item
	_deselectItem( value ) {
		return this._changeItemStatus( value, false );
	}

	// change item status
	_changeItemStatus( value, status ) {
		const $option = this.$originalSelect.find(
			`option[value="${ value }"]`
		);

		if ( $option.length ) {
			$option.prop( 'selected', status );

			( ! yith_wcan_shortcodes.instant_filters && this.multiple ) ||
				this.closeDropdown();
			this.updateLabel();

			this.$originalSelect.trigger( 'change', [ true ] );

			return true;
		}
		return false;
	}

	// empty items list
	_emptyItems() {
		this.$_items.html( '' );
	}

	// show "Load more" link
	_showLoadMore() {
		this.$_showMore.show();
	}

	// hide "Load more" link
	_hideLoadMore() {
		this.$_showMore.hide();
	}

	// returns select label
	getLabel() {
		return this.hasSelectedValues()
			? this.getSelectedLabels().join( ', ' )
			: this.options.labels.emptyLabel;
	}

	// update label to match new selection
	updateLabel() {
		const label = this.getLabel();

		this.$_label?.html( label );
	}

	// returns select options
	getOptions() {
		return this.$originalSelect.find( 'option' );
	}

	// checks whether select has selected values
	hasSelectedValues() {
		return this.getSelectedOptions().length;
	}

	// checks whether a value is selected
	isValueSelected( value ) {
		const found = this.getSelectedValues().indexOf( value.toString() );

		return -1 !== found;
	}

	// retrieve selected options
	getSelectedOptions() {
		return this.$originalSelect.find( 'option' ).filter( ':selected' );
	}

	// retrieves an option node by value
	getOptionByValue( value ) {
		return this.$originalSelect.find( `option[value="${ value }"]` );
	}

	// retrieve labels for selected options
	getSelectedLabels() {
		const labels = [];

		this.getSelectedOptions().each( function () {
			let $option = $( this ),
				template = $option.data( 'template' );

			template = template
				? template
				: $option.html().replace( /\([0-9]*\)/, '' );

			labels.push( template );
		} );

		return labels;
	}

	// retrieve values for selected options
	getSelectedValues() {
		const values = [];

		this.getSelectedOptions().each( function () {
			values.push( $( this ).val() );
		} );

		return values;
	}

	destroy() {
		// TBD
	}
}
