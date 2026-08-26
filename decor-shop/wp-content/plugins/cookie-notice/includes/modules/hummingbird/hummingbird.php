<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

use Hummingbird\Core\Utils;

/**
 * Cookie Notice Modules Hummingbird class.
 *
 * Compatibility since: 2.1.0
 *
 * @class Cookie_Notice_Modules_Hummingbird
 */
class Cookie_Notice_Modules_Hummingbird {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'init', [ $this, 'load_module' ] );
	}

	/**
	 * Add compatibility to Hummingbird plugin.
	 *
	 * @return void
	 */
	public function load_module() {
		// bail if options class is not available
		if ( ! class_exists( 'Hummingbird\Core\Utils' ) )
			return;

		// get caching module
		$mod = Utils::get_module( 'page_cache' );

		// valid object?
		if ( is_a( $mod, 'Hummingbird\Core\Modules\Page_Cache' ) && method_exists( $mod, 'is_active' ) ) {
			// is caching enabled?
			if ( $mod->is_active() ) {
				// delete cache files after updating settings or status
				add_action( 'cn_configuration_updated', [ $this, 'delete_cache' ] );
			}
		}
	}

	/**
	 * Delete all cache files.
	 *
	 * @return void
	 */
	public function delete_cache() {
		do_action( 'wphb_clear_page_cache' );
	}
}

new Cookie_Notice_Modules_Hummingbird();