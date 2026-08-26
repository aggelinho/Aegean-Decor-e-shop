<?php
/**
 * Cache class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 4.1.2
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Cache_Helper' ) ) {
	/**
	 * Cache class.
	 * This class manage all the cache functionalities.
	 *
	 * @since 4.1.2
	 */
	class YITH_WCAN_Cache_Helper {
		/**
		 * Provider
		 *
		 * @param string
		 */
		protected static $provider;

		/**
		 * Bootstrap the cache
		 */
		public static function init() {
			$provider = self::get_provider();

			if ( ! $provider ) {
				return;
			}

			self::$provider = $provider;
			self::$provider::init();
		}

		/**
		 * Returns the provider that will manage the cache
		 *
		 * @return string
		 */
		public static function get_provider() {
			$provider   = apply_filters( 'yith_wcan_cache_provider', 'table' );
			$class_name = "YITH_WCAN_Cache_Provider_{$provider}";

			if ( ! class_exists( $class_name ) ) {
				return false;
			}

			return $class_name;
		}

		/**
		 * Returns a specific value in a cache group
		 *
		 * @param string $group Cache group name.
		 * @param string $index Optional index to return.
		 *
		 * @return mixed Cached value.
		 */
		public static function get( $group, $index = null ) {
			if ( ! self::$provider ) {
				return false;
			}

			return self::$provider::get( $group, $index );
		}

		/**
		 * Sets a specific value in a cache group
		 *
		 * @param string $group Cache grup name.
		 * @param mixed  $value Value to set; could be the entire transient value, or value of a specific index of the transient, assuming it is an array.
		 * @param string $index Optional index to set (assumes transient is an array).
		 *
		 * @return bool|mixed False on failure, new value otherwise.
		 */
		public static function set( $group, $value, $index = null ) {
			if ( ! self::$provider ) {
				return false;
			}

			return self::$provider::set( $group, $value, $index );
		}

		/**
		 * Completely deletes a cache group.
		 *
		 * @param string $group Cache group name.
		 * @return bool False on failure; true otherwise.
		 */
		public static function delete( $group ) {
			if ( ! self::$provider ) {
				return false;
			}

			return self::$provider::unset( $group );
		}

		/**
		 * Deletes all plugin cache groups
		 *
		 * @return void
		 */
		public static function empty() {
			if ( ! self::$provider ) {
				return;
			}

			return self::$provider::empty();
		}

		/**
		 * Deletes old version of the cache groups still in memory
		 *
		 * @return void
		 */
		public static function clean() {
			if ( ! self::$provider ) {
				return;
			}

			return self::$provider::clean();
		}

		/* === QUERY RELATED CACHE === */

		/**
		 * Returns a query-related index to be used in the cache
		 *
		 * @param array $query_vars Array of query vars used to generate cache index,
		 * @return string
		 */
		public static function get_query_index( $query_vars = array() ) {
			$query_vars = $query_vars ? $query_vars : YITH_WCAN_Query()->get_query_vars();
			$query_vars = apply_filters( 'yith_wcan_query_vars_for_cache_index', $query_vars );

			return md5( http_build_query( $query_vars ) );
		}

		/**
		 * Returns a specific value in a cache group, indexed by an hash that is sensible to current query
		 *
		 * @param string $group Cache group name.
		 * @param string $index Optional group index to return.
		 *
		 * @return mixed Cached value.
		 */
		public static function get_for_current_query( $group, $index = null ) {
			$query_index = self::get_query_index();
			$cache_value = self::get( $group, $query_index );

			if ( ! $index ) {
				return $cache_value;
			}

			if ( ! isset( $cache_value[ $index ] ) ) {
				return false;
			}

			return apply_filters( 'yith_wcan_get_cache_value_for_current_query', $cache_value[ $index ], $cache_value, $index, $query_index );
		}

		/**
		 * Sets a specific value in a cache group, indexed by an hash that is sensible to current query
		 *
		 * @param string $group Cache group name.
		 * @param mixed  $value Value to set
		 * @param string $index Optional index to set.
		 *
		 * @return bool|mixed False on failure, new value otherwise.
		 */
		public static function set_for_current_query( $group, $value, $index = null ) {
			$query_index = self::get_query_index();
			$cache_value = self::get( $group, $query_index );

			if ( is_null( $index ) ) {
				$cache_value = $value;
			} else {
				if ( empty( $cache_value ) ) {
					$cache_value = array();
				}
				$cache_value[ $index ] = $value;
			}

			return self::set( $group, $cache_value, $query_index );
		}
	}
}

YITH_WCAN_Cache_Helper::init();
