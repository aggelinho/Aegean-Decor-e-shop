<?php
/**
 * Cache provider interface
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Interfaces
 * @version 2.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! interface_exists( 'YITH_WCAN_Cache_Provider_Interface' ) ) {
	interface YITH_WCAN_Cache_Provider_Interface {
		/**
		 * Init method that provides required initialization for this type of cache
		 */
		public static function init();

		/**
		 * Returns a specific value in cache
		 *
		 * @param string $group Cache group name.
		 * @param string $index Optional group index to return.
		 *
		 * @return mixed Value stored in cache for the group and index provided.
		 */
		public static function get( $group, $index = null );

		/**
		 * Sets a specific value in cache
		 *
		 * @param string $group Cache group name.
		 * @param mixed  $value Value to store in cache.
		 * @param string $index Optional group index.
		 *
		 * @return bool|mixed False on failure, new value otherwise.
		 */
		public static function set( $group, $value, $index = null );

		/**
		 * Deletes a value from cache
		 *
		 * @param string $group Cache group name.
		 * @param string $index Optional group index.
		 */
		public static function unset( $group, $index = null );

		/**
		 * Clean expired cache entries
		 */
		public static function clean();

		/**
		 * Empty cache of all entries
		 */
		public static function empty();
	}
}
