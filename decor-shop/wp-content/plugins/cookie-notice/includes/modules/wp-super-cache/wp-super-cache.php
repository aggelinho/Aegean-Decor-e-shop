<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules WP Super Cache class.
 *
 * Compatibility since: 1.6.3
 *
 * @class Cookie_Notice_Modules_WPSuperCache
 */
class Cookie_Notice_Modules_WPSuperCache {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'init', [ $this, 'add_cookie' ] );
		add_action( 'admin_init', [ $this, 'load_module' ] );
		add_action( 'deactivated_cookie-notice/cookie-notice.php', [ $this, 'delete_cookie' ] );
	}

	/**
	 * Add compatibility to WP Super Cache plugin.
	 *
	 * @return void
	 */
	public function load_module() {
		// bail if function is not available
		if ( ! function_exists( 'wp_cache_is_enabled' ) )
			return;

		// is caching enabled?
		if ( wp_cache_is_enabled() ) {
			// delete cache files after updating settings or status
			add_action( 'cn_configuration_updated', [ $this, 'delete_cache' ] );
		}
	}

	/**
	 * Add hu-consent cookie.
	 *
	 * @return void
	 */
	public function add_cookie() {
		do_action( 'wpsc_add_cookie', 'hu-consent' );
	}

	/**
	 * Delete hu-consent cookie.
	 *
	 * @return void
	 */
	public function delete_cookie() {
		do_action( 'wpsc_delete_cookie', 'hu-consent' );
	}

	/**
	 * Delete all cache files.
	 *
	 * @return void
	 */
	public function delete_cache() {
		if ( function_exists( 'wp_cache_clean_cache' ) ) {
			global $file_prefix;

			wp_cache_clean_cache( $file_prefix, true );
		}
	}
}

new Cookie_Notice_Modules_WPSuperCache();