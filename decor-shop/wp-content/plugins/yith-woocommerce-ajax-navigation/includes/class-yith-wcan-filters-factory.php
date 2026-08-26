<?php
/**
 * Filter Factory
 *
 * Defines a couple of static methods to allow easy access to Filter classes
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 4.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Filters_Factory' ) ) {
	/**
	 * Product factory class.
	 */
	class YITH_WCAN_Filters_Factory {

		/**
		 * Get a product.
		 *
		 * @param array $filter Filter data.
		 *
		 * @return bool|YITH_WCAN_Filter Filter to retrieve, or false on failure
		 */
		public static function get_filter( $filter = array() ) {
			$supported_types = self::get_supported_types();
			$type_slugs      = array_keys( $supported_types );
			$default_type    = current( $type_slugs );
			$type            = isset( $filter['type'] ) && in_array( $filter['type'], $type_slugs, true ) ? $filter['type'] : $default_type;

			$classname = self::get_filter_classname( $type, $filter );

			try {
				return new $classname( $filter );
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Gets a product classname and allows filtering. Returns WC_Product_Simple if the class does not exist.
		 *
		 * @param string $filter_type Filter type.
		 * @param array  $filter      Filter data.
		 *
		 * @return string
		 */
		public static function get_filter_classname( $filter_type, $filter ) {
			$filter_type = str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $filter_type ) ) );

			/**
			 * APPLY_FILTERS: yith_wcan_filter_class_name
			 *
			 * Filters name of the class this factory will use when creating object for current filter.
			 *
			 * @param string $classname   Class name for filter object.
			 * @param string $filter_type Filter type.
			 * @param array  $fliter_data Filter data.
			 *
			 * @return string
			 */
			$classname = apply_filters( 'yith_wcan_filter_class_name', ucwords( "YITH_WCAN_Filter_{$filter_type}" ), $filter_type, $filter );

			if ( ! $classname || ! class_exists( $classname ) ) {
				$classname = 'YITH_WCAN_Filter_Tax';
			}

			return $classname;
		}

		/**
		 * Get all supported filters
		 *
		 * @return array Array of supported filters (id=>name)
		 */
		public static function get_supported_types() {
			/**
			 * APPLY_FILTERS: yith_wcan_yith_wcan_supported_filters
			 *
			 * Filters list of supported filters type.
			 *
			 * @param array $supported_types Filter types.
			 *
			 * @return array
			 */
			return apply_filters(
				'yith_wcan_supported_filters',
				array(
					'tax' => _x( 'Taxonomy', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				)
			);
		}

		/**
		 * Get supported designs
		 *
		 * @return array Array of supported designs (id=>name)
		 */
		public static function get_supported_designs() {
			/**
			 * APPLY_FILTERS: yith_wcan_supported_filter_designs
			 *
			 * Filters list of supported filters designs.
			 *
			 * @param array $supported_design Filter designs.
			 *
			 * @return array
			 */
			return apply_filters(
				'yith_wcan_supported_filter_designs',
				array(
					'checkbox' => _x( 'Checkbox', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'select'   => _x( 'Select', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'text'     => _x( 'Text', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'color'    => _x( 'Color Swatches', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'label'    => _x( 'Label', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				)
			);
		}

		/**
		 * Get all supported orders for products
		 *
		 * @return array Array of supported orders (id=>name)
		 */
		public static function get_supported_orders() {
			return apply_filters(
				'woocommerce_catalog_orderby',
				array(
					'menu_order' => _x( 'Default sorting', '[Admin] Filter edit form, sorting options', 'yith-woocommerce-ajax-navigation' ),
					'popularity' => _x( 'Sort by popularity', '[Admin] Filter edit form, sorting options', 'yith-woocommerce-ajax-navigation' ),
					'rating'     => _x( 'Sort by average rating', '[Admin] Filter edit form, sorting options', 'yith-woocommerce-ajax-navigation' ),
					'date'       => _x( 'Sort by latest', '[Admin] Filter edit form, sorting options', 'yith-woocommerce-ajax-navigation' ),
					'price'      => _x( 'Sort by price: low to high', '[Admin] Filter edit form, sorting options', 'yith-woocommerce-ajax-navigation' ),
					'price-desc' => _x( 'Sort by price: high to low', '[Admin] Filter edit form, sorting options', 'yith-woocommerce-ajax-navigation' ),
				)
			);
		}

		/**
		 * Returns a list of supported orders for terms in the filter
		 *
		 * @return array Array of supported orders (id=>name)
		 */
		public static function get_supported_terms_orders() {
			return apply_filters(
				'yith_wcan_filter_terms_orderby',
				array(
					'name'       => _x( 'Name', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'name_num'   => _x( 'Name (numeric)', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'slug'       => _x( 'Slug', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'count'      => _x( 'Term count', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'term_order' => _x( 'Term order', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'include'    => _x( 'Drag & drop', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				)
			);
		}

		/**
		 * Returns the number of terms that should be shown on first loading
		 *
		 * @return int Number of terms to show on first loading.
		 */
		public static function get_terms_on_first_loading() {
			return apply_filters( 'yith_wcan_terms_on_first_loading', 10 );
		}
	}

	class_alias( 'YITH_WCAN_Filters_Factory', 'YITH_WCAN_Filter_Factory' );
}
