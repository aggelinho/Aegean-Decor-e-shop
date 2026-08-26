<?php
/**
 * Abstract Cache Provider
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 5.1
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Abstract_CRUD_Cache_Provider' ) ) {
	/**
	 * Cache class.
	 * This class manage all the cache functionalities.
	 *
	 * @since 4.1.2
	 */
	abstract class YITH_WCAN_Abstract_CRUD_Cache_Provider implements YITH_WCAN_Cache_Provider_Interface {

		/**
		 * Temporary storage for cache values;
		 * will be synced to the actual storage on shutdown.
		 *
		 * @var array
		 */
		protected static $store;

		/**
		 * Array of cache groups that need to be saved on shutdown.
		 *
		 * @var array
		 */
		protected static $need_update = array();

		/**
		 * Bootstrap the cache
		 */
		public static function init() {
			// register transients for update.
			add_action( 'shutdown', array( static::class, 'sync' ), 10 );
		}

		/**
		 * Offers quick way to check if cache is enabled or not.
		 */
		public static function is_cache_enabled() {
			/**
			 * APPLY_FILTERS: yith_wcan_suppress_cache
			 *
			 * Allow third party code to suppress cache handling.
			 *
			 * @param bool $suppress_cache Default value: false.
			 *
			 * @return bool
			 */
			return ! apply_filters( 'yith_wcan_suppress_cache', false );
		}

		/**
		 * Checks if a cache group is supported
		 *
		 * @return bool Whether group is supported or not.
		 */
		public static function is_supported_group( $group ) {
			return in_array( $group, static::get_supported_groups(), true );
		}

		/**
		 * Returns an array of supported groups
		 *
		 * @return array Supported groups
		 */
		protected static function get_supported_groups() {
			/**
			 * APPLY_FILTERS: yith_wcan_supported_cache_groups
			 *
			 * Allow third party code to filter supported cache groups.
			 *
			 * @param bool $suppress_cache Default value: false.
			 *
			 * @return bool
			 */
			return apply_filters(
				'yith_wcan_supported_cache_groups',
				array(
					'single_matching_variation',
					'products_instock',

					'products_in_term_count',
					'products_in_stock_count',
					'products_on_sale_count',
					'products_featured_count',
					'products_rated_count',

					'min_price',
					'max_price',
				)
			);
		}

		/* === BASIC CACHE HANDLING === */

		/**
		 * Returns a specific value in a cache group
		 *
		 * @param string $group Cache group name.
		 * @param string $index Optional group index to return.
		 *
		 * @return mixed Value stored in cache for the group and index provided.
		 */
		public static function get( $group, $index = null ) {
			if ( ! static::is_cache_enabled() || ! static::is_supported_group( $group ) ) {
				return false;
			}

			if ( ! isset( static::$store[ $group ] ) ) {
				static::$store[ $group ] = static::read( $group );
			}

			if ( is_null( $index ) ) {
				return static::$store[ $group ];
			}

			if ( ! isset( static::$store[ $group ][ $index ] ) ) {
				return false;
			}

			/**
			 * APPLY_FILTERS: yith_wcan_get_cache_value
			 *
			 * Allow third party code to filter values stored in plugin's cache.
			 *
			 * @param mixed  $value Cached value.
			 * @param string $group Name of the cache group to access.
			 * @param string $index Optional name of the index to access inside group.
			 *
			 * @return mixed
			 */
			return apply_filters( 'yith_wcan_get_cache_value', static::$store[ $group ][ $index ], $group, $index );
		}

		/**
		 * Sets a specific value in a cache group
		 *
		 * @param string $group Cache group name.
		 * @param mixed  $value Value to store in cache.
		 * @param string $index Optional group index.
		 *
		 * @return bool|mixed False on failure, new value otherwise.
		 */
		public static function set( $group, $value, $index = null ) {
			if ( ! static::is_cache_enabled() || ! static::is_supported_group( $group ) ) {
				return false;
			}

			if ( is_null( $index ) ) {
				static::$store[ $group ] = $value;
			} else {
				if ( empty( static::$store[ $group ] ) ) {
					static::$store[ $group ] = array();
				}
				static::$store[ $group ][ $index ] = $value;
			}

			static::mark_for_update( $group );

			return $value;
		}

		/**
		 * Completely deletes a cache group.
		 *
		 * @param string $group Cache group name.
		 * @param string $index Optional group index.
		 * @return bool False on failure; true otherwise.
		 */
		public static function unset( $group, $index = null ) {
			return is_null( static::set( $group, null, $index ) );
		}

		/**
		 * Update modified cache groups at shutdown
		 *
		 * @return void
		 */
		public static function sync() {
			if ( ! static::is_cache_enabled() || empty( static::$need_update ) ) {
				return;
			}

			foreach ( static::$need_update as $group ) {
				if ( ! isset( static::$store[ $group ] ) ) {
					continue;
				}

				$value = static::$store[ $group ];

				if ( is_null( $value ) ) {
					static::delete( $group );
				} else {
					static::update( $group, $value );
				}
			}

			static::$need_update = array();
		}

		/**
		 * Deletes all cache groups
		 *
		 * @return void
		 */
		public static function empty() {
			// delete current version of the groups.
			foreach ( self::get_supported_groups() as $group ) {
				static::delete( $group );
			}

			// delete past versions of the groups.
			static::clean();
		}

		/**
		 * Set a cache group as requiring update
		 *
		 * @param string $group Cache group name.
		 *
		 * @eturn void
		 */
		protected static function mark_for_update( $group ) {
			if ( ! static::is_cache_enabled() || ! static::is_supported_group( $group ) || in_array( $group, static::$need_update, true ) ) {
				return;
			}

			static::$need_update[] = $group;
		}

		/* === CRUD METHODS === */

		/**
		 * Method that reads a cache group from database.
		 *
		 * @param string $group Cache group name.
		 * @return mixed Cache value.
		 */
		protected abstract static function read( $group );

		/**
		 * Update value in database for a specific cache group.
		 *
		 * @param string $group Cache group name.
		 * @param mixed  $value Value to store in cache.
		 * @return bool Status of the operation.
		 */
		protected abstract static function update( $group, $value );

		/**
		 * Deletes a cache value from database for a specific cache group.
		 *
		 * @param string $group Cache group name.
		 * @return bool Status of the operation.
		 */
		protected abstract static function delete( $group );
	}
}
