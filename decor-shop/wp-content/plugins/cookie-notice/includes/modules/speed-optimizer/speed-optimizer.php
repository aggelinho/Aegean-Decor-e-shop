<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

use SiteGround_Optimizer\Options\Options;

/**
 * Cookie Notice Modules Speed Optimizer class.
 *
 * Compatibility since: 5.5.0
 *
 * @class Cookie_Notice_Modules_SpeedOptimizer
 */
class Cookie_Notice_Modules_SpeedOptimizer {

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
	 * Add compatibility to Speed Optimizer plugin.
	 *
	 * @return void
	 */
	public function load_module() {
		// bail if options class is not available
		if ( ! class_exists( 'SiteGround_Optimizer\Options\Options' ) )
			return;

		// check caching status
		$cache_active = Options::is_enabled( 'siteground_optimizer_enable_cache' ) || Options::is_enabled( 'siteground_optimizer_file_caching' );

		// update 2.4.17
		if ( version_compare( Cookie_Notice()->db_version, '2.4.16', '<=' ) ) {
			if ( $cache_active ) {
				// clear cache
				$this->delete_cache();
			}
		}

		if ( $cache_active ) {
			// actions
			add_action( 'cn_configuration_updated', [ $this, 'delete_cache' ] );

			// filters
			add_filter( 'sgo_js_minify_exclude', [ $this, 'exclude_script' ] );
			add_filter( 'sgo_javascript_combine_exclude', [ $this, 'exclude_script' ] );
			add_filter( 'sgo_javascript_combine_excluded_external_paths', [ $this, 'exclude_script' ] );
			add_filter( 'sgo_javascript_combine_excluded_inline_content', [ $this, 'exclude_code' ] );
		}
	}

	/**
	 * Exclude JavaScript file.
	 *
	 * @param array $excludes
	 * @return array
	 */
	function exclude_script( $excludes ) {
		// add widget url
		$excludes[] = basename( Cookie_Notice()->get_url( 'widget' ) );

		return $excludes;
	}

	/**
	 * Exclude JavaScript inline code.
	 *
	 * @param array $excludes
	 * @return array
	 */
	function exclude_code( $excludes ) {
		// add widget inline code
		$excludes[] = 'huOptions';

		return $excludes;
	}

	/**
	 * Delete all cache files.
	 *
	 * @return void
	 */
	public function delete_cache() {
		if ( function_exists( 'sg_cachepress_purge_cache' ) )
			sg_cachepress_purge_cache();
	}
}

new Cookie_Notice_Modules_SpeedOptimizer();