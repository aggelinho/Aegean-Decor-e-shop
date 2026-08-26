<?php
/**
 * Cache Provider - Transients
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 5.1
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Cache_Provider_Transient' ) ) {
	/**
	 * Cache class.
	 * This class manage all the cache functionalities.
	 *
	 * @since 4.1.2
	 */
	class YITH_WCAN_Cache_Provider_Transient extends YITH_WCAN_Abstract_CRUD_Cache_Provider {

		/**
		 * Deletes old version of the transients still in memory
		 *
		 * @return void
		 */
		public static function clean() {
			global $wpdb;

			$cache_version = WC_Cache_Helper::get_transient_version( 'product' );
			$to_delete     = array();

			foreach ( self::get_supported_groups() as $group ) {
				$to_delete[] = str_replace( $cache_version, '%', self::get_transient_name( $group ) );
			}

			$query = "DELETE FROM {$wpdb->options} WHERE 1=1";
			$args  = array();

			$query .= ' AND ( ';
			$first  = true;
			foreach ( $to_delete as $transient_name ) {
				if ( ! $first ) {
					$query .= ' OR ';
				}

				$args[] = "%{$transient_name}%";

				$query .= 'option_name LIKE %s';
				$first  = false;
			}
			$query .= ')';

			$query .= ' AND option_name NOT LIKE %s';
			$args[] = "%{$cache_version}%";

			$wpdb->query( $wpdb->prepare( $query, $args ) ); // phpcs:ignore WordPress.DB
		}

		/**
		 * Returns transient name for a given group
		 *
		 * @param string $group Cache group name.
		 * @return string Transient name.
		 */
		protected static function get_transient_name( $group ) {
			$cache_version    = WC_Cache_Helper::get_transient_version( 'product' );
			$language_postfix = self::get_language_postfix();
			$default_name     = "yith_wcan_{$group}_{$cache_version}{$language_postfix}";

			/**
			 * APPLY_FILTERS: yith_wcan_$group_name
			 *
			 * Allow third party code to filter option name for builtin transients.
			 * <code>$group</code> will be replaced with the id of the cache group.
			 *
			 * @param string $default_name Default transient name.
			 *
			 * @return string
			 */
			return apply_filters( "yith_wcan_{$group}_name", $default_name );
		}

		/**
		 * Returns transient duration for a given group
		 *
		 * @param string $group Cache group name.
		 * @return int Transient duration.
		 */
		protected static function get_transient_duration( $group ) {
			/**
			 * APPLY_FILTERS: yith_wcan_$group_duration
			 *
			 * Allow third party code to filter duration for builtin transients.
			 * <code>$group</code> will be replaced with the id of the cache group.
			 *
			 * @param int $transient_duration Default transient duration.
			 *
			 * @return int
			 */
			return apply_filters( "yith_wcan_{$group}_duration", DAY_IN_SECONDS );
		}

		/**
		 * Return postfix to be used in transient names, if a multi-language plugin is installed
		 *
		 * TODO: remove this method, and filter transient names in WPML compatibility class
		 *
		 * @return string
		 */
		protected static function get_language_postfix() {
			$postfix      = '';
			$current_lang = apply_filters( 'wpml_current_language', null );

			if ( ! empty( $current_lang ) ) {
				$postfix = "_{$current_lang}";
			}

			return $postfix;
		}

		/* === CRUD METHODS === */

		/**
		 * Method that reads a cache group from database.
		 *
		 * @param string $group Cache group name.
		 * @return mixed Cache value.
		 */
		protected static function read( $group ) {
			return get_transient( self::get_transient_name( $group ) );
		}

		/**
		 * Update value in database for a specific cache group.
		 *
		 * @param string $group Cache group name.
		 * @param mixed  $value Value to store in cache.
		 * @return bool Status of the operation.
		 */
		protected static function update( $group, $value ) {
			return set_transient( self::get_transient_name( $group ), $value, self::get_transient_duration( $group ) );
		}

		/**
		 * Deletes a cache value from database for a specific cache group.
		 *
		 * @param string $group Cache group name.
		 * @return bool Status of the operation.
		 */
		protected static function delete( $group ) {
			return delete_transient( self::get_transient_name( $group ) );
		}
	}
}
