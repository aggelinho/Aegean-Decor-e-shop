'use strict';

/* global yith_wcan_admin, ajaxurl */

import { $, block, serialize, unblock } from '../../shortcodes/globals';

export default class YITH_WCAN_Filter_Term {
	/**
	 * Term id
	 */
	id;

	/**
	 * Dom object for containing term box
	 */
	$term;

	/**
	 * Filter object
	 */
	filter;

	constructor( $term, filter ) {
		if ( ! $term.length ) {
			return;
		}

		this.$term = $term;
		this.filter = filter;

		if ( $term.hasClass( 'initialized' ) ) {
			return;
		}

		this.init();
	}

	// init object
	init() {
		this.initTabs();
		this.initImageSelector();
		this.initAdditionalColor();
		this.initFields();

		this.$term.addClass( 'initialized' );
	}

	initTabs() {
		const headers = this.$term.find( '.term-tab-header' );

		headers.on( 'click', ( ev ) => {
			const t = $( ev.target ),
				tab = t.data( 'tab' );

			ev.preventDefault();

			this.showTab( tab );
		} );

		this.showTab( this.$term.find( '.term-mode' ).val() );
	}

	initImageSelector() {
		let $imageSelector = this.$term.find( '.image-selector' ),
			$placeholder = $imageSelector.find( '.placeholder-image' ),
			$selected = $imageSelector.find( '.selected-image' ),
			$selectedImg = $selected.find( 'img' ),
			$input = $imageSelector.find( '.term-image' ),
			$clear = $selected.find( '.clear-image' ),
			media;

		$placeholder.off( 'click' ).on( 'click', () => {
			block( $placeholder );

			if ( media ) {
				media.open();
				return;
			}

			// Create a new media frame
			media = wp.media( {
				title: yith_wcan_admin.labels.upload_media,
				button: {
					text: yith_wcan_admin.labels.confirm_media,
				},
				multiple: false,
			} );

			// When an image is selected in the media frame...
			media.on( 'select', function () {
				// Get media attachment details from the frame state
				const attachment = media
					.state()
					.get( 'selection' )
					.first()
					.toJSON();

				$selectedImg.remove();
				$selectedImg = $( '<img/>', {
					src: attachment.url,
				} );

				$selected.prepend( $selectedImg );

				$input.val( attachment.id ).change();

				unblock( $placeholder );

				$placeholder.hide();
				$selected.show();
			} );

			media.on( 'close', function () {
				unblock( $placeholder );
			} );

			// Finally, open the modal on click
			media.open();
		} );
		$clear.off( 'click' ).on( 'click', () => {
			$input.val( '' ).change();

			$selected.hide();
			$placeholder.show();

			return false;
		} );

		$input.val() || $clear.click();
	}

	initAdditionalColor() {
		const $addColor = this.$term.find( '.term-add-second-color' ),
			$hideColor = this.$term.find( '.term-hide-second-color' ),
			$color = this.$term.find( '.additional-color' ).find( 'input' ),
			color = $color.val();

		$addColor.on( 'click', () => ( this.showAdditionalColor(), false ) );
		$hideColor.on( 'click', () => ( this.hideAdditionalColor(), false ) );

		if ( color && color !== $color.data( 'default-color' ) ) {
			this.showAdditionalColor();
		} else {
			this.hideAdditionalColor();
		}
	}

	initFields() {
		this.$term.trigger( 'yith_fields_init' );

		this.$term
			.find( '.yith-plugin-fw-colorpicker--initialized' )
			.wpColorPicker( 'option', 'change', () =>
				this.filter.afterTermChanged( this )
			);

		this.$term.on( 'change', ':input', () =>
			this.filter.afterTermChanged( this )
		);
	}

	// actions

	showTab( tab, force ) {
		const headers = this.$term.find( '.term-tab-header' ),
			tabs = this.$term.find( '.tab' ),
			selectedTab = tabs.filter( '.tab-' + tab );

		if (
			! selectedTab.length ||
			( ! headers.is( ':visible' ) && ! force )
		) {
			return;
		}

		const $activeMode = this.$term.find( '.term-mode' ),
			prevMode = $activeMode.val();

		headers
			.removeClass( 'active' )
			.filter( '[data-tab="' + tab + '"]' )
			.addClass( 'active' );

		tabs.hide();
		selectedTab.show();
		$activeMode.val( tab );

		prevMode !== tab && $activeMode.change();
	}

	showAdditionalColor() {
		const trigger = this.$term.find( '.term-add-second-color' );

		trigger
			.parent()
			.hide()
			.next( '.additional-color' )
			.show()
			.find( '.wp-color-picker' )
			.prop( 'disabled', false )
			.change();
	}

	hideAdditionalColor() {
		const trigger = this.$term.find( '.term-hide-second-color' );

		trigger
			.parent()
			.find( '.wp-color-picker' )
			.prop( 'disabled', true )
			.val( '' )
			.change()
			.end()
			.hide()
			.prev( 'p' )
			.show();
	}

	updateFields( type ) {
		let tabToShow = false;

		switch ( type ) {
			case 'complete':
				this.$term
					.find( '.term-tab-headers' )
					.show()
					.find( 'a[data-tab="color"], span' )
					.show();
				this.$term.find( '.tab.tab-color' ).show();
				this.$term.find( '.tab.tab-image' ).show();

				tabToShow = this.$term.find( '.term-mode' ).val();
				break;
			case 'colors_only':
				this.$term
					.find( '.term-tab-headers' )
					.show()
					.find( 'a[data-tab="image"], span' )
					.hide();
				this.$term.find( '.tab.tab-color' ).show();
				this.$term.find( '.tab.tab-image' ).hide();

				tabToShow = 'color';
				break;
			case 'image_only':
				this.$term
					.find( '.term-tab-headers' )
					.show()
					.find( 'a[data-tab="color"], span' )
					.hide();
				this.$term.find( '.tab.tab-color' ).hide();
				this.$term.find( '.tab.tab-image' ).show();

				tabToShow = 'image';
				break;
			case 'labels_only':
			default:
				this.$term.find( '.term-tab-headers' ).hide();
				this.$term.find( '.tab.tab-color' ).hide();
				this.$term.find( '.tab.tab-image' ).hide();
		}

		tabToShow && this.showTab( tabToShow, true );
	}

	// data handling

	getId() {
		return this.id || ( this.id = this.$term.data( 'term_id' ) );
	}

	getData() {
		const termData = serialize( this.$term, {
			formatName: ( v ) =>
				v.replace(
					/filters\[[0-9]+]\[terms]\[[0-9]+]\[([a-z0-9_-]+)]/,
					'$1'
				),
		} );
		termData.term_id = this.getId();

		return termData;
	}
}
