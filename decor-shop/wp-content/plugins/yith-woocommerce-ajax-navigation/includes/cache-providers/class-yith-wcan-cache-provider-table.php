<?php
/**
 * Cache Provider - Table
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 5.1
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Cache_Provider_Table' ) ) {
	/**
	 * Cache class.
	 * This class manage all the cache functionalities.
	 *
	 * @since 4.1.2
	 */
	class YITH_WCAN_Cache_Provider_Table extends YITH_WCAN_Abstract_CRUD_Cache_Provider {

		/**
		 * Table name
		 *
		 * @const string
		 */
		const TABLE = 'yith_wcan_cache';

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

			if ( ! is_null( $index ) ) {
				if ( ! isset( static::$store[ $group ] ) ) {
					static::$store[ $group ] = array();
				}

				if ( ! isset( static::$store[ $group ][ $index ] ) ) {
					static::$store[ $group ][ $index ] = self::read( $group, $index );
				}

				$value = static::$store[ $group ][ $index ];
			} else {
				if ( ! isset( static::$store[ $group ] ) ) {
					static::$store[ $group ] = self::read( $group );
				}

				$value = static::$store[ $group ];
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
			return apply_filters( 'yith_wcan_get_cache_value', $value, $group, $index );
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

				if ( ! self::is_indexed_group( $group ) ) {
					self::update( $group, static::$store[ $group ] );
				} else {
					foreach ( static::$store[ $group ] as $index => $value ) {
						self::update( $group, $value, $index );
					}
				}
			}

			static::$need_update = array();
		}

		/**
		 * Deletes old version of the transients still in memory
		 *
		 * @return void
		 */
		public static function clean() {
			global $wpdb;

			$table_name    = self::TABLE;
			$cache_version = WC_Cache_Helper::get_transient_version( 'product' );
			$query         = <<<EOQ
				DELETE FROM {$wpdb->prefix}{$table_name} WHERE version <> %s OR expiration < NOW()
			EOQ;

			$wpdb->query( $wpdb->prepare( $query, $cache_version ) );
		}

		/**
		 * Deletes all cache groups
		 *
		 * @return void
		 */
		public static function empty() {
			global $wpdb;

			$table_name = self::TABLE;
			$query      = <<<EOQ
				TRUNCATE TABLE {$wpdb->prefix}{$table_name}
			EOQ;

			$wpdb->query( $query );
		}

		/**
		 * Returns group name for a given group id
		 *
		 * @param string $group Cache group id.
		 * @return string Group name.
		 */
		protected static function get_group_name( $group ) {
			$language_postfix = self::get_language_postfix();
			$default_name     = "{$group}{$language_postfix}";

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
		 * Returns group duration for a given group id
		 *
		 * @param string $group Cache group name.
		 * @return int Transient duration.
		 */
		protected static function get_group_duration( $group ) {
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

		/**
		 *
		 */
		protected static function is_indexed_group( $group ) {
			$value = static::$store[ $group ];

			if ( ! is_array( $value ) ) {
				return false;
			}

			$keys       = array_keys( $value );
			$is_indexed = true;

			foreach ( $keys as $key ) {
				if ( ! preg_match( '/[a-z0-9]{32}/', $key ) ) {
					$is_indexed = false;
					break;
				}
			}

			return $is_indexed;
		}

		/* === CRUD METHODS === */

		/**
		 * Method that reads a cache group from database.
		 *
		 * @param string $group Cache group name.
		 * @return mixed Cache value.
		 */
		protected static function read( $group, $index = null ) {
			global $wpdb;

			$table_name    = self::TABLE;
			$cache_version = WC_Cache_Helper::get_transient_version( 'product' );
			$query         = <<<EOQ
				SELECT value 
				FROM {$wpdb->prefix}{$table_name}
				WHERE `group` = %s AND `version` = %s AND `expiration` > NOW() AND `index` = %s
				LIMIT 1
			EOQ;
			$query_args   = array(
				self::get_group_name( $group ),
				$cache_version,
				$index ?: ''
			);

			$result = $wpdb->get_var( $wpdb->prepare( $query, $query_args ) );

			return $result ? maybe_unserialize( $result ) : false;
		}

		/**
		 * Update value in database for a specific cache group.
		 *
		 * @param string $group Cache group name.
		 * @param mixed  $value Value to store in cache.
		 * @return bool Status of the operation.
		 */
		protected static function update( $group, $value, $index = null ) {
			global $wpdb;

			if ( is_null( $value ) ) {
				return self::delete( $group, $index );
			}

			$table_name    = self::TABLE;
			$cache_version = WC_Cache_Helper::get_transient_version( 'product' );

			return ! ! $wpdb->replace(
				"{$wpdb->prefix}{$table_name}",
				array(
					'group'      => self::get_group_name( $group ),
					'version'    => $cache_version,
					'index'      => $index ?: '',
					'value'      => maybe_serialize( $value ),
					'expiration' => time() + self::get_group_duration( $group ),
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'FROM_UNIXTIME( %d )'
				)
			);
		}

		/**
		 * Deletes a cache value from database for a specific cache group.
		 *
		 * @param string $group Cache group name.
		 * @return bool Status of the operation.
		 */
		protected static function delete( $group, $index = null ) {
			global $wpdb;

			$table_name    = self::TABLE;
			$cache_version = WC_Cache_Helper::get_transient_version( 'product' );

			return ! ! $wpdb->delete(
				"{$wpdb->prefix}{$table_name}",
				array(
					'group'   => self::get_group_name( $group ),
					'version' => $cache_version,
					'index'   => $index ?: '',
				)
			);
		}
	}
}
