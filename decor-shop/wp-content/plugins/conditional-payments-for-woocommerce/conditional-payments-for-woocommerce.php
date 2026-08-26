<?php

/*
Plugin Name: Conditional Payments for WooCommerce
Description: Disable payment methods based on shipping methods, customer address and much more.
Version:     3.5.0
Author:      Lauri Karisola / WP Trio
Author URI:  https://wptrio.com
Text Domain: woo-conditional-payments
Domain Path: /languages
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0.0
WC tested up to: 10.0.0
*/

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version
 */
if ( ! defined( 'CONDITIONAL_PAYMENTS_FOR_WOO_VERSION' ) ) {
	define( 'CONDITIONAL_PAYMENTS_FOR_WOO_VERSION', '3.5.0' );
}

/**
 * Assets version
 */
if ( ! defined( 'WOO_CONDITIONAL_PAYMENTS_ASSETS_VERSION' ) ) {
	define( 'WOO_CONDITIONAL_PAYMENTS_ASSETS_VERSION', '3.5.0' );
}

/** 
 * HPOS compatibility 
 */ 
add_action( 'before_woocommerce_init', function() { 
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) { 
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true ); 
	} 
} ); 

/**
 * Load plugin textdomain
 *
 * @return void
 */
add_action( 'plugins_loaded', 'woo_conditional_payments_load_textdomain' );
function woo_conditional_payments_load_textdomain() {
  load_plugin_textdomain( 'woo-conditional-payments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

class Woo_Conditional_Payments {
	function __construct() {
		// WooCommerce not activated, abort
		if ( ! defined( 'WC_VERSION' ) ) {
			return;
		}

		// Prevent running same actions twice if Pro version is enabled
		if ( class_exists( 'Woo_Conditional_Payments_Pro' ) ) {
			return;
		}

		if ( ! defined( 'WOO_CONDITIONAL_PAYMENTS_BASENAME' ) ) {
			define( 'WOO_CONDITIONAL_PAYMENTS_BASENAME', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WOO_CONDITIONAL_PAYMENTS_URL' ) ) {
			define( 'WOO_CONDITIONAL_PAYMENTS_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'WOO_CONDITIONAL_PAYMENTS_FILE' ) ) {
			define( 'WOO_CONDITIONAL_PAYMENTS_FILE', __FILE__ );
		}

		$this->includes();
	}

	/**
	 * Include required files
	 */
	public function includes() {
		$this->load_class( 'includes/class-woo-conditional-payments-updater.php' );

		$this->load_class( 'includes/class-woo-conditional-payments-debug.php' );
		Woo_Conditional_Payments_Debug::instance();

		$this->load_class( 'includes/class-conditional-payments-filters.php' );

		$this->load_class( 'includes/class-woo-conditional-payments-post-type.php', 'Woo_Conditional_Payments_Post_Type' );

		$this->load_class( 'includes/class-woo-conditional-payments-ruleset.php', 'Woo_Conditional_Payments_Ruleset' );

		$this->load_class( 'includes/woo-conditional-payments-utils.php' );

		if ( is_admin() ) {
			$this->admin_includes();
		}

		$this->load_class( 'includes/frontend/class-woo-conditional-payments-frontend.php', 'Woo_Conditional_Payments_Frontend' );
	}

	/**
	 * Include admin files
	 */
	private function admin_includes() {
		$this->load_class( 'includes/admin/class-woo-conditional-payments-admin.php', 'Woo_Conditional_Payments_Admin' );
	}

	/**
	 * Load class
	 */
	private function load_class( $filepath, $class_name = FALSE ) {
		$dir_path = plugin_dir_path( __FILE__ );

		require_once( $dir_path . $filepath );

		if ( $class_name ) {
			return new $class_name;
		}

		return TRUE;
	}
}

function init_woo_conditional_payments() {
	new Woo_Conditional_Payments();
}
add_action( 'plugins_loaded', 'init_woo_conditional_payments', 10 );

