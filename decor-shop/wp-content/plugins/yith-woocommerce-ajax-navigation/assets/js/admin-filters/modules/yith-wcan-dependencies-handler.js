'use strict';

/* global yith_wcan_admin, ajaxurl */

import { $ } from '../../shortcodes/globals';

export default class YITH_WCAN_Dependencies_Handler {
	// container
	$container;

	// fields;
	$fields;

	// dependencies tree.
	dependencies = {};

	// context object.
	context = null;

	constructor( $container, dependenciesTree, context ) {
		this.$container = $container;
		this.dependencies = dependenciesTree;
		this.context = context || {};

		if ( ! this.$container?.length ) {
			return;
		}

		this.initFields();

		if ( ! this.$fields?.length ) {
			return;
		}

		this.initDependencies();
	}

	initFields() {
		this.$fields = this.$container.find( ':input' );
	}

	findField( field, returnContainer ) {
		let $field;

		if ( 'function' === typeof this.context.findField ) {
			return this.context.findField( field, returnContainer );
		}

		$field = this.$container.find( `:input[name*="${ field }"]` );

		if ( ! $field.length ) {
			return null;
		}

		if ( returnContainer ) {
			return $field.closest( '.yith-toggle-content-row' );
		}

		return $field;
	}

	initDependencies() {
		if ( ! Object.keys( this.dependencies ).length ) {
			return;
		}

		this.handleDependencies();
	}

	handleDependencies() {
		this.$fields.on( 'change', () => this.applyDependencies() );

		this.applyDependencies();
	}

	applyDependencies() {
		for ( const [ field, conditions ] of Object.entries(
			this.dependencies
		) ) {
			const $field = this.findField( field, true ),
				show = this.checkFieldConditions( conditions );

			if ( show ) {
				$field?.css( { display: 'table' } );

				if ( 'function' === typeof conditions?.__show ) {
					conditions?.__show( this.context );
				}
			} else {
				$field?.hide();

				if ( 'function' === typeof conditions?.__hide ) {
					conditions?.__hide( this.context );
				}
			}
		}
	}

	checkFieldConditions = function ( conditions ) {
		let result = true;

		for ( const [ field, condition ] of Object.entries( conditions ) ) {
			let $field, fieldValue;

			if ( ! result || [ '__show', '__hide' ].includes( field ) ) {
				continue;
			}

			$field = this.findField( field, false );

			if ( ! $field?.length ) {
				continue;
			}

			if ( $field.first().is( 'input[type="radio"]' ) ) {
				fieldValue = $field.filter( ':checked' ).val().toString();
			} else {
				fieldValue = $field?.val()?.toString();
			}

			if ( Array.isArray( condition ) ) {
				result = condition.includes( fieldValue );
			} else if ( typeof condition === 'function' ) {
				result = condition( fieldValue, $field, this.$container );
			} else if ( 0 === condition.indexOf( ':' ) ) {
				result = $field.is( condition );
			} else if ( 0 === condition.indexOf( '!:' ) ) {
				result = ! $field.is( condition.toString().substring( 1 ) );
			} else if ( 0 === condition.indexOf( '!' ) ) {
				result = condition.toString().substring( 1 ) !== fieldValue;
			} else {
				result = condition.toString() === fieldValue;
			}

			if ( typeof this.dependencies[ field ] !== 'undefined' ) {
				result =
					result &&
					this.checkFieldConditions( this.dependencies[ field ] );
			}
		}

		return result;
	};
}
