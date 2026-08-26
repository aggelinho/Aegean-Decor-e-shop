<?php
/**
 * AJAX Handler
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 1.3.2
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Ajax' ) ) {
	/**
	 * Class that handles AJAX for the plugin
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Ajax {

		/**
		 * List of supported handlers
		 *
		 * @var string[]
		 */
		protected static $handlers = array(
			'render_filter',
			'render_remaining_terms',
			'get_filter_terms',
		);

		/**
		 * Stores currently processed action
		 *
		 * @var string
		 */
		protected static $processing;


		/**
		 * Init defined AJAX handlers
		 */
		public static function init() {
			$handlers = self::$handlers;

			foreach ( $handlers as $handler ) {
				add_action( "wc_ajax_yith_wcan_$handler", self::class . '::process' );
				add_action( "wp_ajax_yith_wcan_$handler", self::class . '::process' );
				add_action( "wp_ajax_nopriv_yith_wcan_$handler", self::class . '::process' );
			}
		}

		/**
		 * Single AJAX handler for the plugin
		 * Performs basic checks over the call, then uses current action to execute proper handler in this class
		 */
		public static function process() {
			$current_action = current_action();
			$handler        = str_replace( array( 'wc_ajax_yith_wcan_', 'wp_ajax_yith_wcan_', 'wp_ajax_nopriv_yith_wcan_' ), '', $current_action );

			// checks for supported handler.
			if ( ! in_array( $handler, self::$handlers, true ) ) {
				wp_die();
			}

			// checks for correct nonce.
			check_admin_referer( $handler, 'security' );

			// checks that method exists.
			if ( ! method_exists( self::class, $handler ) ) {
				wp_die();
			}

			// stores currently processing handler.
			self::$processing = $handler;

			// runs proper handler.
			call_user_func( self::class . '::' . $handler );
		}


		/**
		 * Checks if a specific handler is being processed
		 *
		 * @param string $action Action to check, if any. If none submitted checks whether any defined handler is running.
		 * @return bool Whether specified handler is running.
		 */
		public static function is_processing( $action = false ) {
			return defined( 'DOING_AJAX' ) && DOING_AJAX && ( $action ? $action === self::$processing : self::$processing );
		}

		/**
		 * Render any filter for a given preset
		 */
		public static function render_filter() {
			// phpcs:disable WordPress.Security.NonceVerification
			$preset_id = isset( $_REQUEST['_preset_id'] ) ? (int) $_REQUEST['_preset_id'] : false;
			$filter_id = isset( $_REQUEST['_filter_id'] ) ? (int) $_REQUEST['_filter_id'] : false;
			// phpcs:enable WordPress.Security.NonceVerification

			if ( ! $preset_id ) {
				wp_die();
			}

			$preset = yith_wcan_get_preset( $preset_id );

			if ( ! $preset || ! $preset->is_enabled() ) {
				wp_die();
			}

			$filters = $preset->get_filters();
			$filter  = $filters[ $filter_id ] ?? false;

			if ( ! $filter || ! $filter->is_enabled() ) {
				wp_die();
			}

			wp_send_json_success(
				array(
					'preset_id' => $preset_id,
					'filter_id' => $filter_id,
					'html'      => $filter->render(),
				)
			);
		}

		/**
		 * Render remaining terms for a given Tax filter
		 */
		public static function render_remaining_terms() {
			// phpcs:disable WordPress.Security.NonceVerification
			$preset_id = isset( $_REQUEST['_preset_id'] ) ? (int) $_REQUEST['_preset_id'] : false;
			$filter_id = isset( $_REQUEST['_filter_id'] ) ? (int) $_REQUEST['_filter_id'] : false;
			// phpcs:enable WordPress.Security.NonceVerification

			if ( ! $preset_id ) {
				wp_die();
			}

			$preset = yith_wcan_get_preset( $preset_id );

			if ( ! $preset || ! $preset->is_enabled() ) {
				wp_die();
			}

			$filters = $preset->get_filters();
			$filter  = $filters[ $filter_id ] ?? false;

			if ( ! $filter || ! $filter->is_enabled() || ! $filter->is_type( 'tax' ) ) {
				wp_die();
			}

			$remaining_terms = $filter->get_formatted_terms( 0, YITH_WCAN_Filters_Factory::get_terms_on_first_loading() );
			$markup          = '';

			foreach ( $remaining_terms as $term_id => $term_options ) :
				$markup .= $filter->render_item( $term_id, $term_options );
			endforeach;

			wp_send_json_success(
				array(
					'preset_id' => $preset_id,
					'filter_id' => $filter_id,
					'html'      => $markup,
				)
			);
		}

		/**
		 * Render remaining terms for a given Tax filter
		 */
		public static function get_filter_terms() {
			// phpcs:disable WordPress.Security.NonceVerification
			$preset_id = isset( $_REQUEST['_preset_id'] ) ? (int) $_REQUEST['_preset_id'] : false;
			$filter_id = isset( $_REQUEST['_filter_id'] ) ? (int) $_REQUEST['_filter_id'] : false;
			$search    = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : false;
			// phpcs:enable WordPress.Security.NonceVerification

			if ( ! $preset_id ) {
				wp_die();
			}

			$preset = yith_wcan_get_preset( $preset_id );

			if ( ! $preset || ! $preset->is_enabled() ) {
				wp_die();
			}

			$filters = $preset->get_filters();
			$filter  = $filters[ $filter_id ] ?? false;

			if ( ! $filter || ! $filter->is_enabled() || ! $filter->is_type( 'tax' ) ) {
				wp_die();
			}

			$remaining_terms = $filter->get_formatted_terms( 0 );
			$items           = array();

			foreach ( $remaining_terms as $term_id => $term_options ) {
				$term = get_term( $term_id, $filter->get_taxonomy() );

				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}

				if ( $search && false === stripos( $term->slug, $search ) && false === stripos( $term_options['label'], $search ) ) {
					continue;
				}

				$items[] = array(
					'value' => $term->slug,
					'label' => $term_options['label'] ? $term_options['label'] : $term->name,
				);
			}

			wp_send_json_success(
				array(
					'preset_id' => $preset_id,
					'filter_id' => $filter_id,
					'items'     => $items,
				)
			);
		}
	}
}
