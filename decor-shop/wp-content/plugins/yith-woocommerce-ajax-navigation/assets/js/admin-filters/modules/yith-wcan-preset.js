'use strict';

/* global yith_wcan_admin, ajaxurl */

import Ajax from './ajax';
import { $, block, serialize, unblock } from '../../shortcodes/globals';
import YITH_WCAN_Filter from './yith-wcan-filter';

export default class YITH_WCAN_Preset {
	// status
	rowIndex = 0;

	// dom objects
	$wrapper;
	$mainAddNewFilterButton;
	$loadMoreFiltersButtons;
	$filtersContainer;
	$layout;
	$page;

	// filters
	filters = new Map();

	constructor( $wrapper ) {
		if ( ! $wrapper.length ) {
			return;
		}

		this.$wrapper = $wrapper;
		this.$mainAddNewFilterButton = this.$wrapper.find( '#add_new_filter' );
		this.$filtersContainer = this.$wrapper.find( '.preset-filters' );
		this.$layout = this.$wrapper.find( '#preset_layout' );
		this.$page = this.$wrapper.find( '#paged' );
		this.$loadMoreFiltersButtons =
			this.$wrapper.find( '.load-more-filters' );

		this.init();
	}

	// init object
	init() {
		this.initFilters();
		this.initAddFilter();
		this.initLoadMoreFilters();
		this.initLayout();
		this.initSubmit();
	}

	// general init
	initAddFilter() {
		this.$wrapper.find( '.add-new-filter' ).on( 'click', () => {
			this.addFilter();
			return false;
		} );
	}

	initLoadMoreFilters() {
		this.$loadMoreFiltersButtons.on( 'click', () => {
			this.loadMoreFilters();

			return false;
		} );
	}

	initSubmit() {
		this.$wrapper.find( '#submit' ).on( 'click', ( ev ) => {
			if ( ! this.$wrapper.find( 'form' ).get( 0 ).reportValidity() ) {
				return false;
			}

			if ( ! this.validateFilters() ) {
				return false;
			}

			ev.preventDefault();

			block( this.$wrapper );

			return Ajax.post
				.call( null, 'save_preset', this.getData() )
				.then( ( data ) => {
					this.maybeSetId( data?.id );

					const promises = [ ...this.filters.values() ].map(
						( filter ) => filter.save( false )
					);

					return Promise.all( promises );
				} )
				.then( () => ( window.location = this.getUrl() ) );
		} );
	}

	initLayout() {
		this.$layout
			.on( 'change', 'input', () => this.afterLayoutChange() )
			.find( 'input' )
			.first()
			.change();
	}

	initFilters() {
		// init filter drag & drop
		this.initFiltersDragDrop();

		const $filters = this.$filtersContainer.find( '.filter-row' );

		// filter specific init
		for ( const filterNode of $filters.get() ) {
			const $filter = $( filterNode ),
				filter = new YITH_WCAN_Filter( $filter, this );

			this.filters.set( filter.getId(), filter );
		}
	}

	initFiltersDragDrop() {
		this.$filtersContainer.sortable( {
			cursor: 'move',
			handle: '.yith-toggle-title',
			axis: 'y',
			scrollSensitivity: 40,
			forcePlaceholderSize: true,
		} );
	}

	afterLayoutChange() {
		const layout = this.$layout.find( ':checked' ).val();

		for ( const filter of this.filters.values() ) {
			filter.updateLayout( layout );
		}
	}

	// filter actions

	filterExists( filterId ) {
		return this.filters.has( filterId );
	}

	addFilter( data, index ) {
		const newFilterTemplate = wp.template( 'yith-wcan-filter' ),
			newFilter = newFilterTemplate( {
				id: index || this.nextRowIndex(),
				key: this.filters.size,
			} ),
			$newFilter = $( newFilter );

		this.$filtersContainer.append( $newFilter );

		const filter = new YITH_WCAN_Filter( $newFilter, this );
		data && filter.populate( data );

		this.afterFilterAdd( filter );

		return $newFilter;
	}

	afterFilterAdd( filter ) {
		this.filters.set( filter.getId(), filter );
		this.closeAllFilters().then( () => this.goToFilter( filter ) );
		this.updateRowIndex();
		this.maybeHideEmptyBox();

		this.$mainAddNewFilterButton.show();
	}

	afterFilterDelete( filter ) {
		this.filters.delete( filter.getId() );
		this.maybeShowEmptyBox();

		if ( ! this.filters.size ) {
			this.$mainAddNewFilterButton.hide();
		}
	}

	closeAllFilters() {
		const promises = [ ...this.filters.values() ].map( ( filter ) =>
			filter.close()
		);

		return Promise.all( promises );
	}

	loadMoreFilters() {
		let page = this.$page.val();

		Ajax.get
			.call( this.$loadMoreFiltersButtons, 'load_more_filters', {
				preset: this.getId(),
				page: ++page,
				_wpnonce: yith_wcan_admin.nonce.load_more_filters,
			} )
			.done( ( data ) => {
				if ( ! data ) {
					return;
				}

				if ( data.filters ) {
					for ( const filterData of data.filters ) {
						const filterId = filterData.id;

						if ( this.filterExists( filterId ) ) {
							continue;
						}

						this.addFilter( filterData, filterId );
					}
				}

				if ( ! data.has_more ) {
					this.$loadMoreFiltersButtons.remove();
					this.$page.remove();
					this.$page = null;
				} else {
					this.$page.val( page );
				}
			} );
	}

	validateFilters() {
		return [ ...this.filters.values() ].reduce(
			( valid, filter ) => valid && filter.validate(),
			true
		);
	}

	goToFilter( filter, $target ) {
		if ( filter.isOpen() ) {
			$target = $target || filter.$filter;

			return $( 'html, body' )
				.stop( true )
				.animate( {
					scrollTop: $target.offset().top - 100,
				} )
				.promise();
		}

		return $( 'html, body' )
			.stop( true )
			.animate( {
				scrollTop: filter.$filter.offset().top - 100,
			} )
			.promise()
			.done( () => {
				filter.open();

				if ( ! $target || ! $target.length ) {
					return;
				}

				$( 'html, body' ).animate( {
					scrollTop: $target.offset().top - 100,
				} );
			} );
	}

	// indexes

	getId() {
		return this.$wrapper.find( '#preset_id' ).val();
	}

	getUrl() {
		const currentUrl = new URL( window.location ),
			id = this.getId();

		if ( id ) {
			currentUrl.searchParams.set( 'action', 'edit' );
			currentUrl.searchParams.set( 'preset', id );
		}

		return currentUrl.toString();
	}

	maybeSetId( newId ) {
		if ( this.getId() || ! newId ) {
			return;
		}

		this.$wrapper.find( '#preset_id' ).val( newId );
	}

	static getRowIndex( $row ) {
		const index = $row.data( 'item_key' );

		return index ? parseInt( index ) : 0;
	}

	updateRowIndex() {
		let maxIndex = this.$filtersContainer.data( 'max-filter-id' ),
			maxKey = Math.max( ...this.filters.keys() );

		this.rowIndex = Math.max( maxIndex, maxKey );
	}

	nextRowIndex() {
		if ( ! this.rowIndex ) {
			this.updateRowIndex();
		}

		return ++this.rowIndex;
	}

	currentRowIndex() {
		if ( ! this.rowIndex ) {
			this.updateRowIndex();
		}

		return this.rowIndex;
	}

	// utils

	getData() {
		return serialize( this.$wrapper, {
			filterItems: ( i, v ) => ! $( v ).is( '[name^="filters"]' ),
		} );
	}

	maybeShowEmptyBox() {
		const emptyBox = this.$filtersContainer.children(
			'.yith-wcan-admin-no-post'
		);

		if (
			emptyBox.length &&
			! emptyBox.is( ':visible' ) &&
			! this.filters.size
		) {
			emptyBox.show();
		}
	}

	maybeHideEmptyBox() {
		const emptyBox = this.$filtersContainer.children(
			'.yith-wcan-admin-no-post'
		);

		if (
			emptyBox.length &&
			emptyBox.is( ':visible' ) &&
			this.filters.size
		) {
			emptyBox.hide();
		}
	}
}
