<?php
/*
Plugin Name: Compliance by Hu-manity.co
Description: Compliance by Hu-manity.co (formerly Cookie Notice) — cookie consent banner and full Consent Management Platform for GDPR, CCPA, and global data privacy laws.
Version: 3.0.2
Author: Hu-manity.co
Author URI: https://hu-manity.co/
Plugin URI: https://cookie-compliance.co/
License: MIT License
License URI: https://opensource.org/licenses/MIT
Text Domain: cookie-notice
Domain Path: /languages

Compliance by Hu-manity.co
Copyright (C) 2026, Hu-manity.co - info@hu-manity.co

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice class.
 *
 * @class Cookie_Notice
 * @version	2.5.14
 */
class Cookie_Notice {

	private $status_data = [
		'status'				=> '',
		'subscription'			=> 'basic',
		'threshold_exceeded'	=> false,
		'activation_datetime'	=> 0
	];
	private $x_api_key = 'hudft60djisdusdjwek';
	private $app_host_url = 'https://app.hu-manity.co';
	private $app_login_url = 'https://app.hu-manity.co/#/login';
	private $app_dashboard_url = 'https://app.hu-manity.co/#/';
	private $account_api_url = 'https://account-api.hu-manity.co';
	private $designer_api_url = 'https://designer-api.hu-manity.co';
	private $transactional_api_url = 'https://transactional-api.hu-manity.co';
	private $app_widget_url = '//cdn.hu-manity.co/hu-banner.min.js';
	private $deactivaion_url = '';
	private $network_admin = false;
	private $plugin_network_active = false;
	private static $_instance;
	private $notices = [];
	public $options = [];
	public $network_options = [];
	public $bot_detect;
	public $dashboard;
	public $frontend;
	public $settings;
	public $consent_logs;
	public $privacy_consent;
	public $privacy_consent_logs;
	public $welcome;
	public $welcome_api;
	public $welcome_frontend;
	public $db_version;

	/**
	 * @var $defaults
	 */
	public $defaults = [
		'general'	=> [
			'global_override'		=> false,
			'global_cookie'			=> false,
			'app_id'				=> '',
			'app_key'				=> '',
			'app_blocking'			=> true,
			'conditional_active'	=> false,
			'conditional_display'	=> 'hide',
			'conditional_rules'		=> [],
			'amp_support'			=> false,
			'bot_detection'			=> true,
			'caching_compatibility'	=> true,
			'debug_mode'			=> false,
			'excluded_handles'		=> [],
			'position'				=> 'bottom',
			'message_text'			=> '',
			'css_class'				=> '',
			'accept_text'			=> '',
			'refuse_text'			=> '',
			'refuse_opt'			=> false,
			'refuse_code'			=> '',
			'refuse_code_head'		=> '',
			'revoke_cookies'		=> false,
			'revoke_cookies_opt'	=> 'automatic',
			'revoke_message_text'	=> '',
			'revoke_text'			=> '',
			'redirection'			=> false,
			'see_more'				=> false,
			'link_target'			=> '_blank',
			'link_position'			=> 'banner',
			'time'					=> 'month',
			'time_rejected'			=> 'month',
			'hide_effect'			=> 'fade',
			'on_scroll'				=> false,
			'on_scroll_offset'		=> 100,
			'on_click'				=> false,
			'colors' => [
				'text'			=> '#fff',
				'button'		=> '#00a99d',
				'bar'			=> '#32323a',
				'bar_opacity'	=> 100
			],
			'see_more_opt' => [
				'text'		=> '',
				'link_type'	=> 'page',
				'id'		=> 0,
				'link'		=> '',
				'sync'		=> false
			],
			'script_placement'		=> 'header',
			'translate'				=> true,
			'deactivation_delete'	=> false,
			'review_notice'			=> true,
			'review_notice_delay'	=> 0,
			'update_version'		=> 8,
			'update_notice'			=> true,
			'update_notice_diss'	=> false,
			'update_delay_date'		=> 0,
			'update_threshold_date'	=> 0,
			'csp_notice'			=> false,
			'ui_mode'				=> 'legacy',
			// applied_template removed — now computed on the fly in React via matchTemplate().
			'displayType'			=> 'floating',
		],
		'privacy_consent' => [
			'wordpress_active'					=> true,
			'wordpress_active_type'				=> 'all',
			'contactform7_active'				=> false,
			'contactform7_active_type'			=> 'all',
			'mailchimp_active'					=> false,
			'mailchimp_active_type'				=> 'all',
			'wpforms_active'					=> false,
			'wpforms_active_type'				=> 'all',
			'woocommerce_active'				=> false,
			'woocommerce_active_type'			=> 'all',
			'formidableforms_active'			=> false,
			'formidableforms_active_type'		=> 'all',
			'easydigitaldownloads_active'		=> false,
			'easydigitaldownloads_active_type'	=> 'all'
		],
		'data'	=> [
			'status'				=> '',
			'subscription'			=> 'basic',
			'threshold_exceeded'	=> false,
			'activation_datetime'	=> 0
		],
		'version'	=> '3.0.2'
	];

	/**
	 * Authoritative field-ownership partition (#2264).
	 *
	 * Only these keys may be written to cookie_notice_options by plugin code paths
	 * (save_options in react-admin-ajax.php, validate_options in settings.php).
	 *
	 * API-owned fields (position, displayType, bannerColor, primaryColor) are
	 * NEVER written here — cookie_notice_app_design is their exclusive store.
	 * The Designer API config-pull populates that option via get_app_config().
	 *
	 * @var string[]
	 */
	public static $plugin_owned_fields = [
		'message_text',
		'accept_text',
		'refuse_text',
		'revoke_text',
		'revoke_message_text',
		'css_class',
		'refuse_opt',
		'revoke_cookies',
		'revoke_cookies_opt',
		'on_scroll',
		'on_scroll_offset',
		'on_click',
		'redirection',
		'see_more',
		'see_more_opt',
		'link_target',
		'link_position',
		'time',
		'time_rejected',
		'hide_effect',
		'script_placement',
		'bot_detection',
		'amp_support',
		'caching_compatibility',
		'debug_mode',
		'conditional_active',
		'conditional_display',
		'conditional_rules',
		'deactivation_delete',
		'app_blocking',
		'excluded_handles',
		'refuse_code',
		'refuse_code_head',
		'app_id',
		'app_key',
		'ui_mode',
		'global_override',
		'global_cookie',
		'colors',
		'redirect_delay',
		'review_notice',
		'review_notice_delay',
		'update_version',
		'update_notice',
		'update_notice_diss',
		'update_delay_date',
		'update_threshold_date',
		'csp_notice',
		'translate',
	];

	/**
	 * Disable object cloning.
	 *
	 * @return void
	 */
	public function __clone() {}

	/**
	 * Disable unserializing of the class.
	 *
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Main plugin instance.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( self::$_instance === null ) {
			self::$_instance = new self();

			add_action( 'init', [ self::$_instance, 'load_textdomain' ] );

			self::$_instance->includes();

			self::$_instance->bot_detect = new Cookie_Notice_Bot_Detect();
			self::$_instance->dashboard = new Cookie_Notice_Dashboard();
			self::$_instance->frontend = new Cookie_Notice_Frontend();
			self::$_instance->settings = new Cookie_Notice_Settings();
			new Cookie_Notice_React_Admin_Ajax();
			self::$_instance->consent_logs = new Cookie_Notice_Consent_Logs();
			self::$_instance->privacy_consent = new Cookie_Notice_Privacy_Consent();
			self::$_instance->privacy_consent_logs = new Cookie_Notice_Privacy_Consent_Logs();
			self::$_instance->welcome = new Cookie_Notice_Welcome();
			self::$_instance->welcome_api = new Cookie_Notice_Welcome_API();
			self::$_instance->welcome_frontend = new Cookie_Notice_Welcome_Frontend();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// Allow wp-config.php overrides for staging/prod switching.
		// Usage: define( 'CN_ACCOUNT_API_URL', 'https://stage-api.hu-manity.co' );
		if ( defined( 'CN_ACCOUNT_API_URL' ) )       $this->account_api_url       = CN_ACCOUNT_API_URL;
		if ( defined( 'CN_DESIGNER_API_URL' ) )      $this->designer_api_url      = CN_DESIGNER_API_URL;
		if ( defined( 'CN_TRANSACTIONAL_API_URL' ) ) $this->transactional_api_url = CN_TRANSACTIONAL_API_URL;
		if ( defined( 'CN_X_API_KEY' ) )             $this->x_api_key             = CN_X_API_KEY;
		if ( defined( 'CN_APP_WIDGET_URL' ) )        $this->app_widget_url        = CN_APP_WIDGET_URL;
		if ( defined( 'CN_APP_HOST_URL' ) )          $this->app_host_url          = CN_APP_HOST_URL;

		// define plugin constants
		$this->define_constants();

		// activation hooks
		register_activation_hook( __FILE__, [ $this, 'activation' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivation' ] );

		// set network data
		$this->set_network_data();

		$this->check_legacy_options();

		// get options
		if ( is_multisite() ) {
			// get network options
			$this->network_options['general'] = get_site_option( 'cookie_notice_options', $this->defaults['general'] );
			$this->network_options['privacy_consent'] = get_site_option( 'cookie_notice_privacy_consent', $this->defaults['privacy_consent'] );

			if ( $this->is_network_admin() ) {
				$general_options = $this->network_options['general'];
				$privacy_consent_options = $this->network_options['privacy_consent'];
			} else {
				$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

				// settings page?
				if ( is_admin() && $page === 'cookie-notice' ) {
					// get current url path
					$url_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

					if ( is_string( $url_path ) && basename( $url_path ) === 'admin.php' ) {
						// get site options
						$general_options = get_option( 'cookie_notice_options', $this->defaults['general'] );
						$privacy_consent_options = get_option( 'cookie_notice_privacy_consent', $this->defaults['privacy_consent'] );
					}
				} else {
					if ( $this->is_plugin_network_active() && $this->network_options['general']['global_override'] ) {
						$general_options = $this->network_options['general'];
						$privacy_consent_options = $this->network_options['privacy_consent'];
					} else {
						$general_options = get_option( 'cookie_notice_options', $this->defaults['general'] );
						$privacy_consent_options = get_option( 'cookie_notice_privacy_consent', $this->defaults['privacy_consent'] );
					}
				}
			}
		} else {
			$general_options = get_option( 'cookie_notice_options', $this->defaults['general'] );
			$privacy_consent_options = get_option( 'cookie_notice_privacy_consent', $this->defaults['privacy_consent'] );
		}

		// merge old options with new ones
		$this->options['general'] = $this->multi_array_merge( $this->defaults['general'], $general_options );
		$this->options['privacy_consent'] = $this->multi_array_merge( $this->defaults['privacy_consent'], $privacy_consent_options );

		if ( ! isset( $this->options['general']['see_more_opt']['sync'] ) )
			$this->options['general']['see_more_opt']['sync'] = $this->defaults['general']['see_more_opt']['sync'];

		// actions
		add_action( 'plugins_loaded', [ $this, 'set_database_version' ], 0 );
		add_action( 'plugins_loaded', [ $this, 'set_status_data' ], 0 );
		add_action( 'init', [ $this, 'register_shortcodes' ] );
		add_action( 'init', [ $this, 'wpsc_add_cookie' ] );
		add_action( 'init', [ $this, 'maybe_apply_dev_tier_override' ] );
		add_action( 'init', [ $this, 'set_plugin_links' ] );
		add_action( 'admin_init', [ $this, 'update_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_redirect_after_activation' ] );
		add_action( 'admin_init', [ $this, 'maybe_show_license_assigned_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_switch_ui_mode' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_footer', [ $this, 'deactivate_plugin_template' ] );
		add_action( 'wp_ajax_cn_dismiss_notice', [ $this, 'ajax_dismiss_admin_notice' ] );
		add_action( 'wp_ajax_cn_review_notice', [ $this, 'ajax_review_notice' ] );
		add_action( 'wp_ajax_cn-deactivate-plugin', [ $this, 'deactivate_plugin' ] );
	}

	/**
	 * Set current plugin version from database.
	 *
	 * @return void
	 */
	public function set_database_version() {
		// get current version
		if ( $this->is_network_admin() )
			$this->db_version = get_site_option( 'cookie_notice_version', '1.0.0' );
		else
			$this->db_version = get_option( 'cookie_notice_version', '1.0.0' );
	}

	/**
	 * Check legacy options.
	 *
	 * @return void
	 */
	public function check_legacy_options() {
		// multisite?
		if ( is_multisite() ) {
			// get network options
			$site_options = get_site_option( 'cookie_notice_options', $this->defaults['general'] );

			// update legacy options
			$site_options = $this->update_legacy_options( $site_options );

			// any changes?
			if ( $site_options !== false )
				update_site_option( 'cookie_notice_options', $site_options );
		}

		// get options
		$options = get_option( 'cookie_notice_options', $this->defaults['general'] );

		// update legacy options
		$options = $this->update_legacy_options( $options );

		// any changes?
		if ( $options !== false )
			update_option( 'cookie_notice_options', $options );
	}

	/**
	 * Maybe change legacy options.
	 *
	 * @param array $options
	 * @return false|array
	 */
	public function update_legacy_options( $options ) {
		// bail out if options are missing or invalid to avoid PHP 8 fatal on non-array values
		if ( ! is_array( $options ) )
			return $this->defaults['general'];

		$options_changed = false;

		// check legacy parameters that were yes/no strings
		foreach ( [ 'refuse_opt', 'on_scroll', 'on_click', 'deactivation_delete', 'see_more' ] as $param ) {
			if ( array_key_exists( $param, $options ) && ! is_bool( $options[$param] ) ) {
				$options[$param] = $options[$param] === 'yes';

				$options_changed = true;
			}
		}

		// migrate banner_size → displayType (#2269)
		if ( array_key_exists( 'banner_size', $options ) ) {
			$options['displayType'] = $options['banner_size'];
			unset( $options['banner_size'] );

			$options_changed = true;
		} elseif ( ! array_key_exists( 'displayType', $options ) ) {
			$options['displayType'] = 'floating';

			$options_changed = true;
		}

		// check hide banner
		if ( isset( $options['hide_banner'] ) ) {
			if ( $options['hide_banner'] && ! isset( $options['conditional_active'] ) ) {
				$options['conditional_active'] = true;
				$options['conditional_display'] = 'hide';
				$options['conditional_rules'] = [
					1 => [
						1 => [
							'param'		=> 'user_type',
							'operator'	=> 'equal',
							'value'		=> 'logged_in'
						]
					]
				];
			}

			unset( $options['hide_banner'] );

			$options_changed = true;
		}

		if ( $options_changed )
			return $options;
		else
			return false;
	}

	/**
	 * Setup plugin constants.
	 *
	 * @return void
	 */
	private function define_constants() {
		define( 'COOKIE_NOTICE_URL', plugins_url( '', __FILE__ ) );
		define( 'COOKIE_NOTICE_PATH', plugin_dir_path( __FILE__ ) );
		define( 'COOKIE_NOTICE_BASENAME', plugin_basename( __FILE__ ) );
		define( 'COOKIE_NOTICE_REL_PATH', dirname( COOKIE_NOTICE_BASENAME ) );
	}

	/**
	 * Set cookie compliance status data.
	 *
	 * @return void
	 */
	public function set_status_data() {
		$default_data = $this->defaults['data'];

		if ( is_multisite() ) {
			if ( $this->is_plugin_network_active() ) {
				// network
				if ( $this->is_network_admin() ) {
					if ( $this->network_options['general']['global_override'] )
						$status_data = get_site_option( 'cookie_notice_status', $default_data );
					else
						$status_data = $default_data;
				// site
				} else {
					if ( $this->network_options['general']['global_override'] )
						$status_data = get_site_option( 'cookie_notice_status', $default_data );
					else
						$status_data = get_option( 'cookie_notice_status', $default_data );
				}
			} else {
				// network
				if ( $this->is_network_admin() )
					$status_data = $default_data;
				// site
				else
					$status_data = get_option( 'cookie_notice_status', $default_data );
			}
		} else
			$status_data = get_option( 'cookie_notice_status', $default_data );

		// old status format?
		if ( ! is_array( $status_data ) ) {
			// update config data
			$status_data = $this->welcome_api->get_app_config( '', true );
		} else {
			// merge database data with default data
			$status_data = array_merge( $default_data, $status_data );
		}

		if ( $status_data['threshold_exceeded'] )
			$this->options['general']['app_blocking'] = false;

		// check status
		$status = $this->check_status( $status_data['status'] );

		// no activation timestamp?
		if ( empty( $status_data['activation_datetime'] ) ) {
			if ( $status === 'active' )
				$activation = time();
			else
				$activation = 0;
		} else
			$activation = (int) $status_data['activation_datetime'];

		// set status data
		$this->status_data = [
			'status'				=> $status,
			'subscription'			=> $this->check_subscription( $status_data['subscription'] ),
			'threshold_exceeded'	=> (bool) $status_data['threshold_exceeded'],
			'activation_datetime'	=> $activation
		];

	}

	/**
	 * Get cookie compliance status data.
	 *
	 * @return string
	 */
	public function get_status_data() {
		return $this->status_data;
	}

	/**
	 * Get cookie compliance status.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status_data['status'];
	}

	/**
	 * Check cookie compliance status.
	 *
	 * @param string $status
	 * @return string
	 */
	public function check_status( $status ) {
		$status = sanitize_key( $status );

		return ! empty( $status ) && in_array( $status, [ 'active', 'pending' ], true ) ? $status : $this->defaults['data']['status'];
	}

	/**
	 * Get cookie compliance subscription.
	 *
	 * @return string
	 */
	/**
	 * CN_DEV_MODE: Apply ?cn_tier override.
	 *
	 * Hooked on 'init' so current_user_can() is available (it is NOT at plugins_loaded:0).
	 * Overrides status_data + app_id in-memory for the current request.
	 */
	public function maybe_apply_dev_tier_override() {
		if ( ! defined( 'CN_DEV_MODE' ) || ! CN_DEV_MODE )
			return;

		if ( ! current_user_can( 'manage_options' ) )
			return;

		$cn_tier = isset( $_GET['cn_tier'] ) ? sanitize_key( $_GET['cn_tier'] ) : '';

		if ( $cn_tier === 'basic' ) {
			$this->status_data['subscription'] = 'basic';
			$this->options['general']['app_id'] = '';
		} elseif ( $cn_tier === 'free' ) {
			$this->status_data['subscription'] = 'basic';
			if ( empty( $this->options['general']['app_id'] ) )
				$this->options['general']['app_id'] = 'cn-dev-free-plan';
		} elseif ( $cn_tier === 'pro' ) {
			$this->status_data['subscription'] = 'pro';
			if ( empty( $this->options['general']['app_id'] ) )
				$this->options['general']['app_id'] = 'cn-dev-pro-plan';
		}
	}

	public function get_subscription() {
		return $this->status_data['subscription'];
	}

	/**
	 * Check cookie compliance subscription.
	 *
	 * @param string $subscription
	 * @return string
	 */
	public function check_subscription( $subscription ) {
		$subscription = sanitize_key( $subscription );

		return ! empty( $subscription ) && in_array( $subscription, [ 'basic', 'pro' ], true ) ? $subscription : $this->defaults['data']['subscription'];
	}

	/**
	 * Check whether the current threshold is exceeded.
	 *
	 * @return bool
	 */
	public function threshold_exceeded() {
		return $this->status_data['threshold_exceeded'];
	}

	/**
	 * Get cookie compliance activation timestamp.
	 *
	 * @return int
	 */
	public function get_cc_activation_datetime() {
		return (int) $this->status_data['activation_datetime'];
	}

	/**
	 * Get endpoint URL.
	 *
	 * @param string $type
	 * @param string $query
	 * @return string
	 */
	public function get_url( $type, $query = '' ) {
		if ( $type === 'login' )
			$url = $this->app_login_url;
		elseif ( $type === 'dashboard' )
			$url = $this->app_dashboard_url;
		elseif ( $type === 'widget' )
			$url = $this->app_widget_url;
		elseif ( $type === 'host' )
			$url = $this->app_host_url;
		elseif ( $type === 'account_api' )
			$url = $this->account_api_url;
		elseif ( $type === 'designer_api' )
			$url = $this->designer_api_url;
		elseif ( $type === 'transactional_api' )
			$url = $this->transactional_api_url;

		return $url . ( $query !== '' ? $query : '' );
	}

	/**
	 * Get API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->x_api_key;
	}

	/**
	 * Check whether the current request is for the network administrative interface.
	 *
	 * @return bool
	 */
	public function is_network_admin() {
		return $this->network_admin;
	}

	/**
	 * Check whether the plugin is active for the entire network.
	 *
	 * @return bool
	 */
	public function is_plugin_network_active() {
		return $this->plugin_network_active;
	}

	/**
	 * Check whether network-wide options should be used.
	 *
	 * Returns true when the plugin is network-active with global_override enabled,
	 * meaning all sites share the network-level configuration.
	 *
	 * @return bool
	 */
	public function is_network_options() {
		return is_multisite() && $this->is_plugin_network_active() && $this->network_options['general']['global_override'];
	}

	/**
	 * Set network data.
	 *
	 * @return void
	 */
	private function set_network_data() {
		// load plugin.php file
		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		$cn_network = isset( $_POST['cn_network'] ) ? (int) $_POST['cn_network'] : false;

		// bypass is_network_admin() to handle AJAX requests properly.
		$this->network_admin = is_multisite() && ( is_network_admin() || ( wp_doing_ajax() && $cn_network === 1 ) );

		// check whether the plugin is active for the entire network.
		$this->plugin_network_active = is_plugin_active_for_network( COOKIE_NOTICE_BASENAME );
	}

	/**
	 * Include required files.
	 *
	 * @return void
	 */
	private function includes() {
		include_once( COOKIE_NOTICE_PATH . 'includes/bot-detect.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/dashboard.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/frontend.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/functions.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/settings.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/react-admin-ajax.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/consent-logs.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/privacy-consent.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/privacy-consent-logs.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/welcome.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/welcome-api.php' );
		include_once( COOKIE_NOTICE_PATH . 'includes/welcome-frontend.php' );
	}

	/**
	 * Load textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'cookie-notice', false, dirname( COOKIE_NOTICE_BASENAME ) . '/languages/' );
	}

	/**
	 * Plugin activation.
	 *
	 * @global object $wpdb
	 *
	 * @param bool $network
	 * @return void
	 */
	public function activation( $network ) {
		// New installs start with React UI. We keep the compile-time default as
		// 'legacy' so existing sites (which may lack ui_mode in the DB) continue
		// to see the legacy interface through the multi_array_merge() fallback.
		$activation_defaults = $this->defaults['general'];
		$activation_defaults['ui_mode'] = 'react';

		// network activation?
		if ( is_multisite() && $network ) {
			// add network options
			add_site_option( 'cookie_notice_options', $activation_defaults );
			add_site_option( 'cookie_notice_privacy_consent', $this->defaults['privacy_consent'] );
			add_site_option( 'cookie_notice_status', $this->defaults['data'] );
			add_site_option( 'cookie_notice_version', $this->defaults['version'] );

			// Reactivations: switch network-level option to React UI.
			$net_options = get_site_option( 'cookie_notice_options', [] );

			if ( is_array( $net_options ) && ( ! isset( $net_options['ui_mode'] ) || $net_options['ui_mode'] !== 'react' ) ) {
				$net_options['ui_mode'] = 'react';
				update_site_option( 'cookie_notice_options', $net_options );
			}

			global $wpdb;

			// get all available sites
			$blogs_ids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );

			foreach ( $blogs_ids as $blog_id ) {
				// change to another site
				switch_to_blog( (int) $blog_id );

				// run current site activation process
				$this->activate_site();

				restore_current_blog();
			}
		} else {
			$this->activate_site();
			// Set transient so maybe_redirect_after_activation() fires on the next admin_init.
			// Single-site only — network activation handled above (no per-site redirect).
			set_transient( 'cn_activation_redirect', 1, 30 );
		}
	}

	/**
	 * Single site activation.
	 *
	 * @return void
	 */
	public function activate_site() {
		// New installs start with React UI via activation_defaults.
		$activation_defaults = $this->defaults['general'];
		$activation_defaults['ui_mode'] = 'react';

		add_option( 'cookie_notice_options', $activation_defaults, null, false );
		add_option( 'cookie_notice_privacy_consent', $this->defaults['privacy_consent'], null, false );
		add_option( 'cookie_notice_status', $this->defaults['data'], null, false );
		add_option( 'cookie_notice_version', $this->defaults['version'], null, false );

		// Reactivations: add_option above is a no-op when the key exists,
		// so explicitly switch existing sites to React UI on activation.
		$options = get_option( 'cookie_notice_options', [] );

		if ( is_array( $options ) && ( ! isset( $options['ui_mode'] ) || $options['ui_mode'] !== 'react' ) ) {
			$options['ui_mode'] = 'react';
			update_option( 'cookie_notice_options', $options );
		}
	}

	/**
	 * Redirect to the React admin welcome screen after single-site activation.
	 *
	 * Fires on admin_init. Reads a short-lived transient set by activation().
	 * Guards against: network admin, bulk activation, and insufficient caps.
	 *
	 * ⚠️ Uses cn_react_welcome=1 (NOT welcome=1) — admin-welcome.js intercepts
	 *    the ?welcome=1 param and opens the old PHP modal simultaneously if used.
	 *
	 * @return void
	 */
	public function maybe_redirect_after_activation() {
		if ( ! get_transient( 'cn_activation_redirect' ) ) {
			return;
		}

		// Never redirect inside the network admin screen.
		if ( is_network_admin() ) {
			return;
		}

		// Bulk-activate (wp-admin/plugins.php?activate-multi=true) — skip redirect.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Only admins should be redirected.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		delete_transient( 'cn_activation_redirect' );

		wp_safe_redirect( admin_url( 'admin.php?page=cookie-notice&cn_react_welcome=1' ) );
		exit;
	}

	/**
	 * Show a one-time success notice after a React modal license assignment.
	 *
	 * Triggered by ?license_assigned=1 (set by LicenseSelectStep on success).
	 * Reads optional ?slots_remaining=N for copy personalisation.
	 * The param disappears on the next page load automatically — no transient needed.
	 *
	 * @return void
	 */
	public function maybe_show_license_assigned_notice() {
		if ( ! is_admin() )
			return;

		if ( empty( $_GET['license_assigned'] ) || $_GET['license_assigned'] !== '1' )
			return;

		if ( ! current_user_can( 'manage_options' ) )
			return;

		$slots = isset( $_GET['slots_remaining'] ) ? (int) $_GET['slots_remaining'] : null;

		if ( $slots !== null && $slots > 0 ) {
			/* translators: %d: number of remaining domains on the plan */
			$slots_text = ' ' . sprintf(
				_n( '%d domain remaining on your plan.', '%d domains remaining on your plan.', $slots, 'cookie-notice' ),
				$slots
			);
		} elseif ( $slots === 0 ) {
			$slots_text = ' ' . esc_html__( 'No domains remaining on this plan.', 'cookie-notice' );
		} else {
			$slots_text = '';
		}

		$message = esc_html__( 'Compliance by Hu-manity.co — Pro is now active on this site.', 'cookie-notice' ) . $slots_text;

		$this->add_notice(
			'<p>' . $message . '</p>',
			'notice-success is-dismissible'
		);
	}

	/**
	 * Switch UI mode via ?ui_mode=react|legacy query param.
	 *
	 * Persists the choice to the DB so it sticks across page loads.
	 * Admin-only (manage_options). Works in production — no CN_DEV_MODE required.
	 *
	 * @return void
	 */
	public function maybe_switch_ui_mode() {
		if ( ! isset( $_GET['ui_mode'] ) )
			return;

		// Only process ui_mode switches on the plugin's own admin page.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'cookie-notice' )
			return;

		if ( ! current_user_can( 'manage_options' ) )
			return;

		$requested = sanitize_key( $_GET['ui_mode'] );

		if ( ! in_array( $requested, [ 'react', 'legacy' ], true ) )
			return;

		$current = $this->options['general']['ui_mode'];

		// Update DB only if the value actually changed.
		if ( $current !== $requested ) {
			$this->options['general']['ui_mode'] = $requested;

			if ( $this->is_network_admin() ) {
				$db_options = get_site_option( 'cookie_notice_options', [] );
			} else {
				$db_options = get_option( 'cookie_notice_options', [] );
			}

			$db_options['ui_mode'] = $requested;

			if ( $this->is_network_admin() ) {
				update_site_option( 'cookie_notice_options', $db_options );
			} else {
				update_option( 'cookie_notice_options', $db_options );
			}
		}

		// Always set in-memory so the current request renders the correct view.
		$this->options['general']['ui_mode'] = $requested;
	}

	/**
	 * Plugin deactivation.
	 *
	 * @global object $wpdb
	 *
	 * @param bool $network
	 * @return void
	 */
	public function deactivation( $network ) {
		// network deactivation?
		if ( is_multisite() && $network ) {
			$delete = $this->options['general']['global_override'] && $this->options['general']['deactivation_delete'];

			// delete network options?
			if ( $delete ) {
				delete_site_option( 'cookie_notice_options' );
				delete_site_option( 'cookie_notice_privacy_consent' );
				delete_site_option( 'cookie_notice_status' );
				delete_site_option( 'cookie_notice_app_analytics' );
				delete_site_option( 'cookie_notice_app_blocking' );
				delete_site_option( 'cookie_notice_version' );
			}

			global $wpdb;

			// get all available sites
			$blogs_ids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );

			foreach ( $blogs_ids as $blog_id ) {
				// change to another site
				switch_to_blog( (int) $blog_id );

				// run current site deactivation process
				$this->deactivate_site( $delete );

				restore_current_blog();
			}
		} else
			$this->deactivate_site();
	}

	/**
	 * Single site deactivation.
	 *
	 * @param bool $force_deletion
	 * @return void
	 */
	public function deactivate_site( $force_deletion = false ) {
		// delete settings?
		if ( $force_deletion || $this->options['general']['deactivation_delete'] ) {
			// delete options
			delete_option( 'cookie_notice_options' );
			delete_option( 'cookie_notice_privacy_consent' );
			delete_option( 'cookie_notice_status' );
			delete_option( 'cookie_notice_app_analytics' );
			delete_option( 'cookie_notice_app_blocking' );
			delete_option( 'cookie_notice_version' );

			// delete transients if any
			delete_transient( 'cookie_notice_app_token' );
			delete_transient( 'cookie_notice_app_quick_config' );
			delete_transient( 'cookie_notice_app_subscriptions' );
		}

		// remove wp super cache cookie
		$this->wpsc_delete_cookie();
	}

	/**
	 * Update notice.
	 *
	 * @return void
	 */
	public function update_notice() {
		if ( ! current_user_can( 'install_plugins' ) )
			return;

		// bail an ajax
		if ( wp_doing_ajax() )
			return;

		$network = $this->is_network_admin();

		// get cookie compliance status
		$status = $this->get_status();

		// get subscription
		$subscription = $this->get_subscription();

		// update number
		$current_update = 14;

		// new version?
		if ( version_compare( $this->db_version, $this->defaults['version'], '<' ) ) {
			if ( $this->options['general']['update_version'] < $current_update ) {
				// check version, if update version is lower than plugin version, set update notice to true
				$this->options['general']['update_version'] = $current_update;
				$this->options['general']['update_notice'] = true;

				// update options
				if ( $network ) {
					$this->options['general']['update_notice_diss'] = false;

					update_site_option( 'cookie_notice_options', $this->options['general'] );
				} else
					update_option( 'cookie_notice_options', $this->options['general'] );
			}

			// update 2.4.17+
			if ( version_compare( $this->db_version, '2.4.17', '<' ) ) {
				// get cookie compliance activation timestamp
				$activation_date = $this->get_cc_activation_datetime();

				// get status data
				$data = $this->status_data;

				// no activation timestamp?
				if ( empty( $activation_date ) ) {
					if ( $status === 'active' )
						$activation = time();
					else
						$activation = 0;
				} else
					$activation = (int) $data['activation_datetime'];

				// update activation timestamp
				$data['activation_datetime'] = $activation;

				if ( $network )
					update_site_option( 'cookie_notice_status', $data );
				else
					update_option( 'cookie_notice_status', $data, false );
			}

			// update plugin version
			if ( $network )
				update_site_option( 'cookie_notice_version', $this->defaults['version'] );
			else
				update_option( 'cookie_notice_version', $this->defaults['version'], false );
		}

		// check page
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

		// if visiting settings, mark notice as read
		if ( $page === 'cookie-notice' && ! empty( $_GET['welcome'] ) ) {
			$this->options['general']['update_notice'] = false;

			if ( $network ) {
				$this->options['general']['update_notice_diss'] = true;

				update_site_option( 'cookie_notice_options', $this->options['general'] );
			} else
				update_option( 'cookie_notice_options', $this->options['general'] );
		}

		if ( is_multisite() && ( ( $this->is_plugin_network_active() && ! $network && $this->network_options['general']['global_override'] ) || ( $network && ! $this->is_plugin_network_active() ) ) )
			$this->options['general']['update_notice'] = false;

		// compliance only
		if ( $status === 'active' ) {
			// get analytics data options
			if ( $network )
				$analytics = get_site_option( 'cookie_notice_app_analytics', [] );
			else
				$analytics = get_option( 'cookie_notice_app_analytics', [] );

			if ( is_multisite() && ( ( $network && ! $this->is_plugin_network_active() && ! $this->network_options['general']['global_override'] ) || ( ! $network && $this->is_plugin_network_active() && $this->network_options['general']['global_override'] ) ) )
				$allow_notice = false;
			else
				$allow_notice = true;

			// show threshold limit warning
			if ( ! empty( $analytics ) && $allow_notice ) {
				// cycle usage data
				$cycle_usage = [
					'threshold'		=> ! empty( $analytics['cycleUsage']->threshold ) ? (int) $analytics['cycleUsage']->threshold : 0,
					'visits'		=> ! empty( $analytics['cycleUsage']->visits ) ? (int) $analytics['cycleUsage']->visits : 0,
					'end_date'		=> ! empty( $analytics['cycleUsage']->endDate ) ? date_create_from_format( '!Y-m-d', $analytics['cycleUsage']->endDate ) : date_create_from_format( 'Y-m-d H:i:s', current_time( 'mysql', true ) ),
					'last_updated'	=> ! empty( $analytics['lastUpdated'] ) ? date_create_from_format( 'Y-m-d H:i:s', $analytics['lastUpdated'] ) : date_create_from_format( 'Y-m-d H:i:s', current_time( 'mysql', true ) )
				];

				// if threshold in use
				if ( $cycle_usage['threshold'] ) {
					// if threshold exceeded and there was no notice before
					if ( $cycle_usage['visits'] >= $cycle_usage['threshold'] && $cycle_usage['last_updated']->getTimestamp() < $cycle_usage['end_date']->getTimestamp() && $this->options['general']['update_threshold_date'] < $cycle_usage['end_date']->getTimestamp() ) {
						$date_format = get_option( 'date_format' );

						$upgrade_link = $this->get_url( 'dashboard', '?app-id=' . $this->options['general']['app_id'] . '&open-modal=payment' );
						$threshold = $cycle_usage['threshold'];
						$cycle_date = date_i18n( $date_format, $cycle_usage['end_date']->getTimestamp() );

						$this->add_notice( '<div class="cn-notice-text" data-delay="' . esc_attr( $cycle_usage['end_date']->getTimestamp() ) . '"><h2>' . esc_html__( 'Compliance by Hu-manity.co Warning', 'cookie-notice') . '</h2><p>' . sprintf( __( 'Your website has reached the <b>%1$s visits usage limit for the Compliance by Hu-manity.co Free Plan</b>. Compliance services such as Consent Record Storage, Autoblocking, and Consent Analytics have been deactivated until current usage cycle ends on %2$s.', 'cookie-notice' ), $threshold, $cycle_date ) . '<br>' . sprintf( __( 'To reactivate compliance services now, <a href="%s" target="_blank">upgrade your domain to a Pro plan.</a>', 'cookie-notice' ) . '</p></div>', $upgrade_link ), 'cn-threshold error is-dismissible', 'div' );
					}
				}
			}

			// display review notice, for multisite only for network admin area
			if ( ! empty( $this->options['general']['review_notice'] ) && ( ! is_multisite() || ( $network && $this->is_plugin_network_active() ) ) ) {
				// get current time
				$current_time = time();

				// get cookie compliance activation timestamp
				$activation_date = $this->get_cc_activation_datetime();

				// get delay timestamp
				$delay_timestamp = (int) $this->options['general']['review_notice_delay'];

				// no delay?
				if ( $delay_timestamp === 0 )
					$compare_timestamp = $activation_date + 2 * WEEK_IN_SECONDS;
				else
					$compare_timestamp = $delay_timestamp;

				// display notice?
				if ( $compare_timestamp < $current_time )
					$this->add_notice( '<div class="cn-notice-text cn-review"><h2>' . esc_html__( 'We Value Your Feedback', 'cookie-notice' ) . '</h2><p>' . sprintf( __( "Hi, you've been using <strong>Compliance by Hu-manity.co</strong> for more than %s. We hope it has been a valuable addition to your WordPress site. We would be grateful if you could take a few minutes to share your thoughts by leaving a review.", 'cookie-notice' ), human_time_diff( $activation_date, $current_time ) ) . '<br>' . esc_html__( 'Thank you for helping us improve and grow!', 'cookie-notice' ) . '</p><p class="cn-notice-actions"><a href="https://wordpress.org/support/plugin/cookie-notice/reviews/?filter=5#new-post" class="button-link cn-notice-review" target="_blank" rel="noopener">' . esc_html__( 'Review', 'cookie-notice' ) . '</a><a href="#" class="button-link cn-notice-delay">' . esc_html__( 'Delay', 'cookie-notice' ) . '</a><a href="#" class="button-link cn-notice-dismiss">' . esc_html__( 'Dismiss', 'cookie-notice' ) . '</a></p></div>', 'error', 'div' );
			}
		}
	}

	/**
	 * Add admin notice.
	 *
	 * @param string $html
	 * @param string $status
	 * @param string $container
	 * @return void
	 */
	private function add_notice( $html = '', $status = 'error', $container = '' ) {
		$this->notices[] = [
			'html'		=> $html,
			'status'	=> $status,
			'container'	=> ( ! empty( $container ) && in_array( $container, [ 'p', 'div' ] ) ? $container : '' )
		];

		add_action( 'admin_notices', [ $this, 'display_notice' ], 0 );
		add_action( 'network_admin_notices', [ $this, 'display_notice' ], 0 );
	}

	/**
	 * Print admin notices.
	 *
	 * @return void
	 */
	public function display_notice() {
		foreach( $this->notices as $notice ) {
			echo '
			<div id="cn-admin-notice" class="cn-notice notice notice-info ' . esc_attr( $notice['status'] ) . '">
				' . ( ! empty( $notice['container'] ) ? '<' . esc_attr( $notice['container'] ) . ' class="cn-notice-container">' : '' ) . '
				' . wp_kses_post( $notice['html'] ) . '
				' . ( ! empty( $notice['container'] ) ? '</' . esc_attr( $notice['container'] ) . ' class="cn-notice-container">' : '' ) . '
			</div>';
		}
	}

	/**
	 * Dismiss admin notice.
	 *
	 * @return void
	 */
	public function ajax_dismiss_admin_notice() {
		if ( ! current_user_can( 'install_plugins' ) )
			exit;

		if ( ! isset( $_POST['nonce'], $_POST['notice_action'] ) )
			exit;

		if ( wp_verify_nonce( $_POST['nonce'], 'cn_dismiss_notice' ) ) {
			// get notice action
			$notice_action = ! empty( $_POST['notice_action'] ) ? sanitize_key( $_POST['notice_action'] ) : 'dismiss';

			$cn_network = isset( $_POST['cn_network'] ) ? (int) $_POST['cn_network'] : false;

			// network?
			$network = is_multisite() && $cn_network === 1;

			switch ( $notice_action ) {
				// threshold notice
				case 'threshold':
					// set delay period last cycle day
					$delay = isset( $_POST['param'] ) ? (int) $_POST['param'] : 0;

					$this->options['general']['update_threshold_date'] = $delay + DAY_IN_SECONDS;

					// update options
					if ( $network )
						update_site_option( 'cookie_notice_options', $this->options['general'] );
					else
						update_option( 'cookie_notice_options', $this->options['general'] );
					break;

				// delay notice
				case 'delay':
					// set delay period to 2 weeks from now
					$this->options['general']['update_delay_date'] = time() + 2 * WEEK_IN_SECONDS;

					// update options
					if ( $network )
						update_site_option( 'cookie_notice_options', $this->options['general'] );
					else
						update_option( 'cookie_notice_options', $this->options['general'] );
					break;

				// hide notice
				case 'approve':
				default:
					$this->options['general']['update_notice'] = false;
					$this->options['general']['update_delay_date'] = 0;

					// update options
					if ( $network ) {
						$this->options['general']['update_notice_diss'] = true;

						update_site_option( 'cookie_notice_options', $this->options['general'] );
					} else
						update_option( 'cookie_notice_options', $this->options['general'] );
			}
		}

		exit;
	}

	/**
	 * Dismiss review admin notice.
	 *
	 * @return void
	 */
	public function ajax_review_notice() {
		if ( ! current_user_can( 'install_plugins' ) )
			exit;

		if ( ! isset( $_POST['nonce'], $_POST['notice_action'] ) )
			exit;

		if ( wp_verify_nonce( $_POST['nonce'], 'cn_review_notice' ) ) {
			// get notice action
			$notice_action = ! empty( $_POST['notice_action'] ) ? sanitize_key( $_POST['notice_action'] ) : 'dismiss';

			$cn_network = isset( $_POST['cn_network'] ) ? (int) $_POST['cn_network'] : false;

			// network?
			$network = is_multisite() && $cn_network === 1;

			switch ( $notice_action ) {
				// delay notice
				case 'delay':
					$this->options['general']['review_notice'] = true;
					$this->options['general']['review_notice_delay'] = time() + 2 * WEEK_IN_SECONDS;

					// update options
					if ( $network )
						update_site_option( 'cookie_notice_options', $this->options['general'] );
					else
						update_option( 'cookie_notice_options', $this->options['general'] );
					break;

				// hide notice
				case 'dismiss':
				case 'review':
				default:
					$this->options['general']['review_notice'] = false;
					$this->options['general']['review_notice_delay'] = 0;

					// update options
					if ( $network ) {
						$this->options['general']['update_notice_diss'] = true;

						update_site_option( 'cookie_notice_options', $this->options['general'] );
					} else
						update_option( 'cookie_notice_options', $this->options['general'] );
			}
		}

		exit;
	}

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'cookies_accepted', [ $this, 'cookies_accepted_shortcode' ] );
		add_shortcode( 'cookies_revoke', [ $this, 'cookies_revoke_shortcode' ] );
		add_shortcode( 'cookies_policy_link', [ $this, 'cookies_policy_link_shortcode' ] );
	}

	/**
	 * Register cookies accepted shortcode.
	 *
	 * @param array $args
	 * @param string $content
	 * @return string
	 */
	public function cookies_accepted_shortcode( $args, $content ) {
		if ( $this->cookies_accepted() ) {
			// Only sanitize with wp_kses - do not decode entities from user-generated content
			$scripts = trim( wp_kses( $content, $this->get_allowed_html( 'body' ) ) );

			if ( ! empty( $scripts ) ) {
				if ( preg_match_all( '/' . get_shortcode_regex() . '/', $content ) )
					$scripts = do_shortcode( $scripts );

				return $scripts;
			}
		}

		return '';
	}

	/**
	 * Register cookies revoke shortcode.
	 *
	 * @param array $args
	 * @param string $content
	 * @return string
	 */
	public function cookies_revoke_shortcode( $args, $content ) {
		// get options
		$options = $this->options['general'];

		// WPML >= 3.2
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) )
			$options['revoke_text'] = apply_filters( 'wpml_translate_single_string', $options['revoke_text'], 'Cookie Notice', 'Revoke button text' );
		// WPML and Polylang compatibility
		elseif ( function_exists( 'icl_t' ) )
			$options['revoke_text'] = icl_t( 'Cookie Notice', 'Revoke button text', $options['revoke_text'] );

		// defaults
		$defaults = [
			'title'	=> $options['revoke_text'],
			'class'	=> $options['css_class']
		];

		// combine shortcode arguments
		$args = shortcode_atts( $defaults, $args );

		if ( Cookie_Notice()->get_status() === 'active' )
			$shortcode = '<a href="#" class="cn-revoke-cookie cn-button-inline cn-revoke-inline' . esc_attr( $args['class'] !== '' ? ' ' . $args['class'] : '' ) . '" title="' . esc_attr( $args['title'] ) . '" data-hu-action="cookies-notice-revoke">' . esc_html( $args['title'] ) . '</a>';
		else
			$shortcode = '<a href="#" class="cn-revoke-cookie cn-button-inline cn-revoke-inline' . esc_attr( $args['class'] !== '' ? ' ' . $args['class'] : '' ) . '" title="' . esc_attr( $args['title'] ) . '">' . esc_html( $args['title'] ) . '</a>';

		return $shortcode;
	}

	/**
	 * Register cookies policy link shortcode.
	 *
	 * @param array $args
	 * @param string $content
	 * @return string
	 */
	public function cookies_policy_link_shortcode( $args, $content ) {
		// get options
		$options = $this->options['general'];

		// WPML >= 3.2
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
			$options['see_more_opt']['text'] = apply_filters( 'wpml_translate_single_string', $options['see_more_opt']['text'], 'Cookie Notice', 'Privacy policy text' );
			$options['see_more_opt']['link'] = apply_filters( 'wpml_translate_single_string', $options['see_more_opt']['link'], 'Cookie Notice', 'Custom link' );
		// WPML and Polylang compatibility
		} elseif ( function_exists( 'icl_t' ) ) {
			$options['see_more_opt']['text'] = icl_t( 'Cookie Notice', 'Privacy policy text', $options['see_more_opt']['text'] );
			$options['see_more_opt']['link'] = icl_t( 'Cookie Notice', 'Custom link', $options['see_more_opt']['link'] );
		}

		if ( $options['see_more_opt']['link_type'] === 'page' ) {
			// multisite with global override?
			if ( is_multisite() && $this->is_plugin_network_active() && $this->network_options['general']['global_override'] ) {
				// get main site id
				$main_site_id = get_main_site_id();

				// switch to main site
				switch_to_blog( $main_site_id );

				// update page id for current language if needed
				if ( function_exists( 'icl_object_id' ) )
					$options['see_more_opt']['id'] = icl_object_id( $options['see_more_opt']['id'], 'page', true );

				// get main site privacy policy link
				$permalink = get_permalink( $options['see_more_opt']['id'] );

				// restore current site
				restore_current_blog();
			} else {
				// update page id for current language if needed
				if ( function_exists( 'icl_object_id' ) )
					$options['see_more_opt']['id'] = icl_object_id( $options['see_more_opt']['id'], 'page', true );

				// get privacy policy link
				$permalink = get_permalink( $options['see_more_opt']['id'] );
			}
		}

		// defaults
		$defaults = [
			'title'	=> $options['see_more_opt']['text'] !== '' ? $options['see_more_opt']['text'] : '&#x279c;',
			'link'	=> $options['see_more_opt']['link_type'] === 'custom' ? $options['see_more_opt']['link'] : $permalink,
			'class'	=> $options['css_class']
		];

		// combine shortcode arguments
		$args = shortcode_atts( $defaults, $args );

		$shortcode = '<a href="' . esc_url( $args['link'] ) . '" target="' . esc_attr( $options['link_target'] ) . '" id="cn-more-info" class="cn-privacy-policy-link cn-link' . esc_attr( $args['class'] !== '' ? ' ' . $args['class'] : '' ) . '" data-link-url="' . esc_url( $args['link'] ) . '" data-link-target="' . esc_attr( $options['link_target'] ) . '">' . esc_html( $args['title'] ) . '</a>';

		return $shortcode;
	}

	/**
	 * Check if cookies are accepted.
	 *
	 * @return bool
	 */
	public static function cookies_accepted() {
		if ( Cookie_Notice()->get_status() === 'active' ) {
			// get cookie
			$cookies = isset( $_COOKIE['hu-consent'] ) ? json_decode( stripslashes( $_COOKIE['hu-consent'] ), true ) : [];

			// valid cookie?
			if ( json_last_error() === JSON_ERROR_NONE && ! empty( $cookies ) && is_array( $cookies ) && isset( $cookies['consent'] ) )
				$result = (bool) $cookies['consent'];
			else
				$result = false;
		} else
			$result = isset( $_COOKIE['cookie_notice_accepted'] ) && $_COOKIE['cookie_notice_accepted'] === 'true';

		return (bool) apply_filters( 'cn_is_cookie_accepted', $result );
	}

	/**
	 * Check if cookies are set.
	 *
	 * @return bool
	 */
	public static function cookies_set() {
		if ( Cookie_Notice()->get_status() === 'active' )
			$result = isset( $_COOKIE['hu-consent'] );
		else
			$result = isset( $_COOKIE['cookie_notice_accepted'] );

		return (bool) apply_filters( 'cn_is_cookie_set', $result );
	}

	/**
	 * Add WP Super Cache cookie.
	 *
	 * @return void
	 */
	public function wpsc_add_cookie() {
		if ( $this->get_status() !== 'active' )
			do_action( 'wpsc_add_cookie', 'cookie_notice_accepted' );
	}

	/**
	 * Delete WP Super Cache cookie.
	 *
	 * @return void
	 */
	public function wpsc_delete_cookie() {
		if ( $this->get_status() !== 'active' )
			do_action( 'wpsc_delete_cookie', 'cookie_notice_accepted' );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $page
	 * @return void
	 */
	public function admin_enqueue_scripts( $page ) {
		// plugins page?
		if ( $page === 'plugins.php' ) {
			add_thickbox();

			wp_enqueue_script( 'cookie-notice-admin-plugins', COOKIE_NOTICE_URL . '/js/admin-plugins.js', [ 'jquery' ], $this->defaults['version'] );

			wp_enqueue_style( 'cookie-notice-admin-plugins', COOKIE_NOTICE_URL . '/css/admin-plugins.css', [], $this->defaults['version'] );

			// prepare script data
			$script_data = [
				'deactivate'	=> esc_html__( 'Compliance by Hu-manity.co - Deactivation survey', 'cookie-notice' ),
				'nonce'			=> wp_create_nonce( 'cn-deactivate-plugin' )
			];

			wp_add_inline_script( 'cookie-notice-admin-plugins', 'var cnArgsPlugins = ' . wp_json_encode( $script_data ) . ";\n", 'before' );
		}

		// notice js and css
		wp_enqueue_script( 'cookie-notice-admin-notice', COOKIE_NOTICE_URL . '/js/admin-notice.js', [], $this->defaults['version'] );

		// prepare script data
		$script_data = [
			'ajaxURL'		=> admin_url( 'admin-ajax.php' ),
			'nonce'			=> wp_create_nonce( 'cn_dismiss_notice' ),
			'reviewNonce'	=> wp_create_nonce( 'cn_review_notice' ),
			'network'		=> $this->is_network_admin()
		];

		wp_add_inline_script( 'cookie-notice-admin-notice', 'var cnArgsNotice = ' . wp_json_encode( $script_data ) . ";\n", 'before' );

		wp_enqueue_style( 'cookie-notice-admin-notice', COOKIE_NOTICE_URL . '/css/admin-notice.css', [], $this->defaults['version'] );
	}

	/**
	 * Set plugin links.
	 *
	 * @return void
	 */
	public function set_plugin_links() {
		// filters
		add_filter( 'plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
		add_filter( 'network_admin_plugin_action_links', [ $this, 'plugin_action_links' ], 10, 2 );
	}

	/**
	 * Add links to settings page.
	 *
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function plugin_action_links( $links, $file ) {
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			return $links;

		if ( $file === COOKIE_NOTICE_BASENAME ) {
			if ( ! empty( $links['deactivate'] ) ) {
				// link already contains class attribute?
				if ( preg_match( '/<a.*?class=(\'|")(.*?)(\'|").*?>/is', $links['deactivate'], $result ) === 1 )
					$links['deactivate'] = preg_replace( '/(<a.*?class=(?:\'|").*?)((?:\'|").*?>)/s', '$1 cn-deactivate-plugin-modal$2', $links['deactivate'] );
				else
					$links['deactivate'] = preg_replace( '/(<a.*?)>/s', '$1 class="cn-deactivate-plugin-modal">', $links['deactivate'] );

				// link already contains href attribute?
				if ( preg_match( '/<a.*?href=(\'|")(.*?)(\'|").*?>/is', $links['deactivate'], $result ) === 1 ) {
					if ( ! empty( $result[2] ) )
						$this->deactivaion_url = $result[2];
				}
			}

			// skip settings link if plugin is activated from main site
			if ( ! ( $this->is_network_admin() && ! $this->is_plugin_network_active() ) ) {
				$url = $this->is_network_admin() ? network_admin_url( 'admin.php?page=cookie-notice' ) : admin_url( 'admin.php?page=cookie-notice' );

				// put settings link at start
				array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'cookie-notice' ) ) );
			}

			// get cookie compliance status
			$status = $this->get_status();

			if ( is_multisite() ) {
				$check_status = empty( $status ) && ( ( $this->is_network_admin() && $this->is_plugin_network_active() && $this->network_options['general']['global_override'] ) || ( ! $this->is_network_admin() && ( ( $this->is_plugin_network_active() && ! $this->network_options['general']['global_override'] ) || ! $this->is_plugin_network_active() ) ) );
			} else
				$check_status = empty( $status );

			// add upgrade link
			if ( $check_status ) {
				$url = $this->is_network_admin() ? network_admin_url( 'admin.php?page=cookie-notice&welcome=1' ) : admin_url( 'admin.php?page=cookie-notice&welcome=1' );

				$links[] = sprintf( '<a href="%s" style="color: #20C19E; font-weight: bold">%s</a>', esc_url( $url ), esc_html__( 'Try Compliance by Hu-manity.co free', 'cookie-notice' ) );
			}
		}

		return $links;
	}

	/**
	 * Deactivation modal HTML template.
	 *
	 * @global string $pagenow
	 *
	 * @return void
	 */
	public function deactivate_plugin_template() {
		global $pagenow;

		// display only for plugins page
		if ( $pagenow !== 'plugins.php' )
			return;

		echo '
		<div id="cn-deactivation-modal" style="display: none">
			<div id="cn-deactivation-container">
				<div id="cn-deactivation-body">
					<div class="cn-deactivation-options">
						<p><em>' . esc_html__( "We're sorry to see you go. Could you please tell us what happened?", 'cookie-notice' ) . '</em></p>
						<ul>';

		foreach ( [
				'1'	=> esc_html__( "I couldn't figure out how to make it work.", 'cookie-notice' ),
				'2'	=> esc_html__( 'I found another plugin to use for the same task.', 'cookie-notice' ),
				'3'	=> esc_html__( 'The Compliance by Hu-manity.co banner is too big.', 'cookie-notice' ),
				'4'	=> esc_html__( 'The Compliance by Hu-manity.co consent choices (Silver, Gold, Platinum) are confusing.', 'cookie-notice' ),
				'5'	=> esc_html__( 'The Compliance by Hu-manity.co default settings are too strict.', 'cookie-notice' ),
				'6'	=> esc_html__( 'The web application user interface is not clear to me.', 'cookie-notice' ),
				'7'	=> esc_html__( "Support isn't timely.", 'cookie-notice' ),
				'8'	=> esc_html__( 'Other', 'cookie-notice' )
		] as $option => $text ) {
			echo '
							<li><label><input type="radio" name="cn_deactivation_option" value="' . esc_attr( $option ) . '" ' . checked( '8', $option, false ) . ' />' . esc_html( $text ) . '</label></li>';
			}

		echo '
						</ul>
					</div>
					<div class="cn-deactivation-textarea">
						<textarea name="cn_deactivation_other"></textarea>
					</div>
				</div>
				<div id="cn-deactivation-footer">
					<a href="" class="button cn-deactivate-plugin-cancel">' . esc_html__( 'Cancel', 'cookie-notice' ) . '</a>
					<a href="' . esc_url( $this->deactivaion_url ) . '" class="button button-secondary cn-deactivate-plugin-simple">' . esc_html__( 'Deactivate', 'cookie-notice' ) . '</a>
					<a href="' . esc_url( $this->deactivaion_url ) . '" class="button button-primary right cn-deactivate-plugin-data">' . esc_html__( 'Deactivate & Submit', 'cookie-notice' ) . '</a>
					<span class="spinner"></span>
				</div>
			</div>
		</div>';
	}

	/**
	 * Send data about deactivation of the plugin.
	 *
	 * @return void
	 */
	public function deactivate_plugin() {
		// check permissions
		if ( ! current_user_can( 'install_plugins' ) || wp_verify_nonce( $_POST['nonce'], 'cn-deactivate-plugin' ) === false )
			return;

		if ( isset( $_POST['option_id'] ) ) {
			$option_id = (int) $_POST['option_id'];

			// avoid fake submissions
			if ( $option_id === 8 ) {
				$other = isset( $_POST['other'] ) ? sanitize_textarea_field( $_POST['other'] ) : '';

				// no reason?
				if ( $other === '' )
					wp_send_json_success();
			}

			wp_remote_post(
				'https://hu-manity.co/wp-json/api/v1/forms/',
				[
					'timeout'		=> 15,
					'blocking'		=> true,
					'headers'		=> [],
					'body'			=> [
						'id'		=> 1,
						'option'	=> $option_id,
						'other'		=> $other,
						'referrer'	=> get_site_url()
					]
				]
			);

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Get allowed script blocking HTML.
	 *
	 * @param string $type
	 * @return array
	 */
	public function get_allowed_html( $type = 'head' ) {
		// default allowed html for both types
		$allowed_html = [
			'script'	=> [
				'type'				=> true,
				'src'				=> true,
				'charset'			=> true,
				'async'				=> true,
				'defer'				=> true,
				'crossorigin'		=> true,
				'fetchpriority'		=> true,
				'referrerpolicy'	=> true,
				'nomodule'			=> true,
				'nonce'				=> true,
				'integrity'			=> true,
				'class'				=> true,
				'id'				=> true
			],
			'noscript'	=> [
				'class'	=> true,
				'id'	=> true
			],
			'style'		=> [
				'type'	=> true,
				'media'	=> true,
				'nonce'	=> true,
				'class'	=> true,
				'id'	=> true
			]
		];

		if ( $type === 'head' ) {
			// allow links for head
			$allowed_html['link'] = [
				'as'				=> true,
				'crossorigin'		=> true,
				'fetchpriority'		=> true,
				'imagesizes'		=> true,
				'imagesrcset'		=> true,
				'referrerpolicy'	=> true,
				'sizes'				=> true,
				'integrity'			=> true,
				'href'				=> true,
				'hreflang'			=> true,
				'rel'				=> true,
				'type'				=> true,
				'title'				=> true,
				'media'				=> true,
				'class'				=> true,
				'id'				=> true
			];
		} elseif ( $type === 'body' ) {
			// allow ifarmes for body
			$allowed_html['iframe'] = [
				'src'				=> true,
				'srcdoc'			=> true,
				'height'			=> true,
				'width'				=> true,
				'class'				=> true,
				'id'				=> true,
				'allow'				=> true,
				'loading'			=> true,
				'name'				=> true,
				'title'				=> true,
				'referrerpolicy'	=> true,
				'sandbox'			=> true,
				'allowfullscreen'	=> true
			];
		}

		// combine allowed tags with default post allowed tags
		return apply_filters( 'cn_refuse_code_allowed_html', array_merge( wp_kses_allowed_html( 'post' ), $allowed_html ), $type );
	}

	/**
	 * Merge multidimensional associative arrays.
	 * Works only with strings, integers and arrays as keys. Values can be any type but they have to have same type to be kept in the final array.
	 * Every array should have the same type of elements. Only keys from $defaults array will be kept in the final array unless $siblings are not empty.
	 * $siblings examples: array( '=>', 'only_first_level', 'first_level=>second_level', 'first_key=>next_key=>sibling' ) and so on.
	 * Single '=>' means that all siblings of the highest level will be kept in the final array.
	 *
	 * @param array $defaults Array with defaults values
	 * @param array $array Array to merge
	 * @param bool|array $siblings Whether to allow "string" siblings to copy from $array if they do not exist in $defaults, false otherwise
	 * @return array
	 */
	public function multi_array_merge( $defaults, $array, $siblings = false ) {
		// make a copy for better performance and to prevent $default override in foreach
		$copy = $defaults;

		// prepare siblings for recursive deeper level
		$new_siblings = [];

		// allow siblings?
		if ( ! empty( $siblings ) && is_array( $siblings ) ) {
			foreach ( $siblings as $sibling ) {
				// highest level siblings
				if ( $sibling === '=>' ) {
					// copy all non-existent string siblings
					foreach( $array as $key => $value ) {
						if ( is_string( $key ) && ! array_key_exists( $key, $defaults ) ) {
							$defaults[$key] = null;
						}
					}
				// sublevel siblings
				} else {
					// explode siblings
					$ex = explode( '=>', $sibling );

					// copy all non-existent siblings
					foreach ( array_keys( $array[$ex[0]] ) as $key ) {
						if ( ! array_key_exists( $key, $defaults[$ex[0]] ) )
							$defaults[$ex[0]][$key] = null;
					}

					// more than one sibling child?
					if ( count( $ex ) > 1 )
						$new_siblings[$ex[0]] = [ substr_replace( $sibling, '', 0, strlen( $ex[0] . '=>' ) ) ];
					// no more sibling children
					else
						$new_siblings[$ex[0]] = false;
				}
			}
		}

		// loop through first array
		foreach ( $defaults as $key => $value ) {
			// integer key?
			if ( is_int( $key ) ) {
				$copy = array_unique( array_merge( $defaults, $array ), SORT_REGULAR );

				break;
			// string key?
			} elseif ( is_string( $key ) && isset( $array[$key] ) ) {
				// string, boolean, integer or null values?
				if ( ( is_string( $value ) && is_string( $array[$key] ) ) || ( is_bool( $value ) && is_bool( $array[$key] ) ) || ( is_int( $value ) && is_int( $array[$key] ) ) || is_null( $value ) )
					$copy[$key] = $array[$key];
				// arrays
				elseif ( is_array( $value ) && isset( $array[$key] ) && is_array( $array[$key] ) ) {
					if ( empty( $value ) )
						$copy[$key] = $array[$key];
					else
						$copy[$key] = $this->multi_array_merge( $defaults[$key], $array[$key], ( isset( $new_siblings[$key] ) ? $new_siblings[$key] : false ) );
				}
			}
		}

		return $copy;
	}

	/**
	 * Indicate if current page is the Cookie Policy page.
	 *
	 * @return bool
	 */
	public function is_cookie_policy_page() {
		// get privacy policy options
		$see_more = $this->options['general']['see_more_opt'];

		// custom link?
		if ( $see_more['link_type'] !== 'page' )
			return false;

		// get current object
		$current_page = sanitize_post( $GLOBALS['wp_the_query']->get_queried_object() );

		// check if current page is privacy policy page
		return $current_page->post_name === get_post_field( 'post_name', $see_more['id'] );
	}
}

/**
 * Initialize Cookie Notice.
 *
 * @return object
 */
function Cookie_Notice() {
	static $instance;

	// first call to instance() initializes the plugin
	if ( $instance === null || ! ( $instance instanceof Cookie_Notice ) )
		$instance = Cookie_Notice::instance();

	return $instance;
}

Cookie_Notice();
