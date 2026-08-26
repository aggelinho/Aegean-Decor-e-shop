<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules SpeedyCache class.
 *
 * Compatibility since: 1.0.0
 *
 * @class Cookie_Notice_Modules_SpeedyCache
 */
class Cookie_Notice_Modules_SpeedyCache {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'plugins_loaded', [ $this, 'load_module' ], 11 );
	}

	/**
	 * Add compatibility to SpeedyCache plugin.
	 *
	 * @global object $speedycache
	 *
	 * @return void
	 */
	public function load_module() {
		global $speedycache;

		// check caching status
		$cache_active = ! empty( $speedycache->options['status'] );

		// update 2.4.17
		if ( version_compare( Cookie_Notice()->db_version, '2.4.16', '<=' ) ) {
			if ( $cache_active ) {
				// clear cache
				$this->delete_cache();
			}
		}

		if ( $cache_active ) {
			// delete cache files after updating settings or status
			add_action( 'cn_configuration_updated', [ $this, 'delete_cache' ] );
		}
	}

	/**
	 * Delete all cache files.
	 *
	 * @return void
	 */
	public function delete_cache() {
		// clear cache
		if ( function_exists( 'speedycache_delete_cache' ) )
			speedycache_delete_cache( true );
	}
}

new Cookie_Notice_Modules_SpeedyCache();