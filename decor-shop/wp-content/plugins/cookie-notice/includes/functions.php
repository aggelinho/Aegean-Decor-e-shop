<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Check if cookies are accepted.
 *
 * @return bool Whether cookies are accepted
 */
if ( ! function_exists( 'cn_cookies_accepted' ) ) {
	function cn_cookies_accepted() {
		return (bool) Cookie_Notice::cookies_accepted();
	}
}

/**
 * Check if cookies are set.
 *
 * @return bool Whether cookies are set
 */
if ( ! function_exists( 'cn_cookies_set' ) ) {
	function cn_cookies_set() {
		return (bool) Cookie_Notice::cookies_set();
	}
}

/**
 * Get active caching plugins.
 *
 * @param array $args
 * @return array
 */
function cn_get_active_caching_plugins( $args = [] ) {
	if ( isset( $args['versions'] ) && $args['versions'] === true )
		$version = true;
	else
		$version = false;

	$active_plugins = [];

	// autoptimize
	if ( cn_is_plugin_active( 'autoptimize' ) ) {
		if ( $version )
			$active_plugins['Autoptimize'] = '2.4.0';
		else
			$active_plugins[] = 'Autoptimize';
	}

	// wp-optimize
	if ( cn_is_plugin_active( 'wpoptimize' ) ) {
		if ( $version )
			$active_plugins['WP-Optimize'] = '3.0.12';
		else
			$active_plugins[] = 'WP-Optimize';
	}

	// litespeed
	if ( cn_is_plugin_active( 'litespeed' ) ) {
		if ( $version )
			$active_plugins['LiteSpeed Cache'] = '3.0.0';
		else
			$active_plugins[] = 'LiteSpeed Cache';
	}

	// speed optimizer
	if ( cn_is_plugin_active( 'speedoptimizer' ) ) {
		if ( $version )
			$active_plugins['Speed Optimizer'] = '5.5.0';
		else
			$active_plugins[] = 'Speed Optimizer';
	}

	// wp fastest cache
	if ( cn_is_plugin_active( 'wpfastestcache' ) ) {
		if ( $version )
			$active_plugins['WP Fastest Cache'] = '1.0.0';
		else
			$active_plugins[] = 'WP Fastest Cache';
	}

	// wp rocket
	if ( cn_is_plugin_active( 'wprocket' ) ) {
		if ( $version )
			$active_plugins['WP Rocket'] = '3.8.0';
		else
			$active_plugins[] = 'WP Rocket';
	}

	// hummingbird
	if ( cn_is_plugin_active( 'hummingbird' ) ) {
		if ( $version )
			$active_plugins['Hummingbird'] = '2.1.0';
		else
			$active_plugins[] = 'Hummingbird';
	}

	// wp super cache
	if ( cn_is_plugin_active( 'wpsupercache' ) ) {
		if ( $version )
			$active_plugins['WP Super Cache'] = '1.6.9';
		else
			$active_plugins[] = 'WP Super Cache';
	}

	// breeze
	if ( cn_is_plugin_active( 'breeze' ) ) {
		if ( $version )
			$active_plugins['Breeze'] = '1.1.0';
		else
			$active_plugins[] = 'Breeze';
	}

	// speedycache
	if ( cn_is_plugin_active( 'speedycache' ) ) {
		if ( $version )
			$active_plugins['SpeedyCache'] = '1.0.0';
		else
			$active_plugins[] = 'SpeedyCache';
	}

	return $active_plugins;
}

/**
 * Check whether specified plugin is active.
 *
 * @global object $siteground_optimizer_loader
 * @global int $wpsc_version
 *
 * @param string $plugin
 * @param string $module
 * @return bool
 */
function cn_is_plugin_active( $plugin = '', $module = 'caching' ) {
	// no valid plugin?
	if ( ! in_array( $plugin, [
		'amp',
		'autoptimize',
		'breeze',
		'contactform7',
		'divi',
		'easydigitaldownloads',
		'elementor',
		'formidableforms',
		'hummingbird',
		'litespeed',
		'mailchimp',
		'speedoptimizer',
		'speedycache',
		'woocommerce',
		'wpfastestcache',
		'wpforms',
		'wpoptimize',
		'wprocket',
		'wpsupercache'
	], true ) )
		return false;

	// set default flag
	$is_plugin_active = false;

	switch ( $plugin ) {
		// amp
		case 'amp':
			if ( $module === 'caching' && function_exists( 'amp_is_enabled' ) && defined( 'AMP__VERSION' ) && version_compare( AMP__VERSION, '2.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// autoptimize
		case 'autoptimize':
			if ( $module === 'caching' && function_exists( 'autoptimize' ) && defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) && version_compare( AUTOPTIMIZE_PLUGIN_VERSION, '2.4', '>=' ) )
				$is_plugin_active = true;
			break;

		// breeze
		case 'breeze':
			if ( $module === 'caching' && class_exists( 'Breeze_PurgeCache' ) && class_exists( 'Breeze_Options_Reader' ) && function_exists( 'breeze_get_option' ) && function_exists( 'breeze_update_option' ) && defined( 'BREEZE_VERSION' ) && version_compare( BREEZE_VERSION, '1.1.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// contact form 7
		case 'contactform7':
			if ( $module === 'captcha' && class_exists( 'WPCF7' ) && class_exists( 'WPCF7_RECAPTCHA' ) && defined( 'WPCF7_VERSION' ) && version_compare( WPCF7_VERSION, '5.1', '>=' ) )
				$is_plugin_active = true;
			elseif ( $module === 'privacy-consent' && class_exists( 'WPCF7' ) && defined( 'WPCF7_VERSION' ) && version_compare( WPCF7_VERSION, '5.3', '>=' ) )
				$is_plugin_active = true;
			break;

		// divi
		case 'divi':
			if ( $module === 'theme' && function_exists( 'is_et_pb_preview' ) && defined( 'ET_CORE_VERSION' ) )
				$is_plugin_active = true;
			break;

		// easy digital downloads
		case 'easydigitaldownloads':
			if ( $module === 'privacy-consent' && class_exists( 'Easy_Digital_Downloads' ) && function_exists( 'EDD' ) && defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '3.0.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// elementor
		case 'elementor':
			if ( $module === 'caching' && did_action( 'elementor/loaded' ) && defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '1.3', '>=' ) )
				$is_plugin_active = true;
			break;

		// formidable forms
		case 'formidableforms':
			if ( $module === 'privacy-consent' && class_exists( 'FrmAppHelper' ) && method_exists( 'FrmAppHelper', 'plugin_version' ) && version_compare( FrmAppHelper::plugin_version(), '2.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// hummingbird
		case 'hummingbird':
			if ( $module === 'caching' && class_exists( 'Hummingbird\\WP_Hummingbird' ) && defined( 'WPHB_VERSION' ) && version_compare( WPHB_VERSION, '2.1.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// litespeed
		case 'litespeed':
			if ( $module === 'caching' && class_exists( 'LiteSpeed\Core' ) && defined( 'LSCWP_CUR_V' ) && version_compare( LSCWP_CUR_V, '3.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// mailchimp
		case 'mailchimp':
			if ( $module === 'privacy-consent' && class_exists( 'MC4WP_Form_Manager' ) && defined( 'MC4WP_VERSION' ) && version_compare( MC4WP_VERSION, '4.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// speed optimizer
		case 'speedoptimizer':
			global $siteground_optimizer_loader;

			if ( $module === 'caching' && ! empty( $siteground_optimizer_loader ) && is_object( $siteground_optimizer_loader ) && is_a( $siteground_optimizer_loader, 'SiteGround_Optimizer\Loader\Loader' ) && defined( '\SiteGround_Optimizer\VERSION' ) && version_compare( \SiteGround_Optimizer\VERSION, '5.5', '>=' ) )
				$is_plugin_active = true;
			break;

		// speedycache
		case 'speedycache':
			if ( $module === 'caching' && class_exists( 'SpeedyCache' ) && defined( 'SPEEDYCACHE_VERSION' ) && function_exists( 'speedycache_delete_cache' ) && version_compare( SPEEDYCACHE_VERSION, '1.0.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// woocommerce
		case 'woocommerce':
			if ( $module === 'privacy-consent' && class_exists( 'WooCommerce' ) && defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '4.0.4', '>=' ) )
				$is_plugin_active = true;
			break;

		// wp fastest cache
		case 'wpfastestcache':
			if ( $module === 'caching' && function_exists( 'wpfc_clear_all_cache' ) )
				$is_plugin_active = true;
			break;

		// wpforms
		case 'wpforms':
			if ( $module === 'privacy-consent' && function_exists( 'wpforms' ) && defined( 'WPFORMS_VERSION' ) && version_compare( WPFORMS_VERSION, '1.6.0', '>=' ) )
				$is_plugin_active = true;
			break;

		// wp-optimize
		case 'wpoptimize':
			if ( $module === 'caching' && function_exists( 'WP_Optimize' ) && defined( 'WPO_VERSION' ) && version_compare( WPO_VERSION, '3.0.12', '>=' ) )
				$is_plugin_active = true;
			break;

		// wp rocket
		case 'wprocket':
			if ( $module === 'caching' && function_exists( 'rocket_init' ) && defined( 'WP_ROCKET_VERSION' ) && version_compare( WP_ROCKET_VERSION, '3.8', '>=' ) )
				$is_plugin_active = true;
			break;

		// wp super cache
		case 'wpsupercache':
			if ( $module === 'caching' ) {
				$plugin_name = 'wp-super-cache/wp-cache.php';
				$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_name;

				if ( file_exists( $plugin_path ) && is_plugin_active( $plugin_name ) ) {
					$plugin = get_plugin_data( $plugin_path, false, false );

					if ( version_compare( $plugin['Version'], '1.6.3', '>=' ) && function_exists( 'wp_cache_is_enabled' ) && function_exists( 'wp_cache_clean_cache' ) && function_exists( 'wpsc_add_cookie' ) && function_exists( 'wpsc_delete_cookie' ) )
						$is_plugin_active = true;
				}
			}
			break;
	}

	return $is_plugin_active;
}

/**
 * Detect active analytics/tracking plugins for consent mode suggestions.
 *
 * @return array List of detected plugin categories: 'google', 'facebook', 'microsoft'.
 */
function cn_detect_active_plugins() {
	$checks = [
		'google'    => [
			'google-analytics-for-wordpress/googleanalytics.php',
			'google-site-kit/google-site-kit.php',
			'wp-google-analytics/wp-google-analytics.php',
			'gtm4wp/gtm4wp.php',
		],
		'facebook'  => [
			'official-facebook-pixel/facebook-for-wordpress.php',
			'pixel-caffeine/pixel-caffeine.php',
		],
		'microsoft' => [
			'clarity-analytics/clarity-analytics.php',
		],
	];

	$detected = [];

	foreach ( $checks as $mode => $plugins ) {
		foreach ( $plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				$detected[] = $mode;
				break;
			}
		}
	}

	return array_values( array_unique( $detected ) );
}