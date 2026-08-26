<?php
/**
 * Main class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 1.3.2
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN' ) ) {
	/**
	 * YITH WooCommerce Ajax Product Filter
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN {
		/**
		 * Plugin version
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $version;

		/**
		 * Frontend object
		 *
		 * @var YITH_WCAN_Frontend
		 * @since 1.0.0
		 */
		public $frontend = null;

		/**
		 * Admin object
		 *
		 * @var YITH_WCAN_Admin
		 * @since 1.0.0
		 */
		public $admin = null;

		/**
		 * List of supported plugins and integrations
		 *
		 * @var array
		 * @since 4.0
		 */
		protected $supported_plugins = array();

		/**
		 * Main instance
		 *
		 * @var YITH_WCAN
		 * @since 4.0.0
		 */
		protected static $instance = null;

		/**
		 * Parameter of the term object used to filter shop.
		 *
		 * Before WooCommerce 2.6 product attribute use term_id for filter; from WooCommerce 2.6 use slug instead.
		 *
		 * @var string filtered term field.
		 * @since 3.0.0
		 */
		public $filter_term_field = 'slug';

		/**
		 * Constructor
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->version = YITH_WCAN_VERSION;

			// install plugin.
			$this->install();

			// register assets needed both on backend and frontend.
			add_action( 'init', array( $this, 'register_assets' ) );

			// declare HPOS compatibility.
			add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );
		}

		/**
		 * Start plugin process, by requiring files and creating instances of main objects
		 *
		 * @return void
		 * @since  4.0
		 */
		public function install() {
			/**
			 * DO_ACTION: yith_wcan_before_init
			 *
			 * Triggered before plugin starts its initialization function.
			 */
			do_action( 'yith_wcan_before_init' );

			$this->require_files();
			$this->load_compatibilities();

			$this->init();

			/**
			 * DO_ACTION: yith_wcan_standby
			 *
			 * Triggered after plugin completed its initialization function.
			 */
			do_action( 'yith_wcan_standby' );
		}

		/**
		 * Load required files
		 *
		 * @return void
		 * @since  1.4
		 */
		public function require_files() {
			// include autoloader.
			require_once YITH_WCAN_INC . 'class-yith-wcan-autoloader.php';

			// include plugin-specific functions.
			require_once YITH_WCAN_INC . 'functions-yith-wcan.php';

			// basic cli support.
			if ( defined( 'WP_CLI' ) && WP_CLI && file_exists( YITH_WCAN_DIR . 'tools/wp-cli/class-yith-wcan-cli-test-commands.php' ) ) {
				require_once YITH_WCAN_DIR . 'tools/wp-cli/class-yith-wcan-cli-test-commands.php';
			}
		}

		/**
		 * Init plugin, by creating main objects
		 *
		 * @return void
		 * @since  1.4
		 */
		public function init() {
			// do startup operations.
			YITH_WCAN_Install::init();

			// init general classes.
			YITH_WCAN_Presets::instance();
			YITH_WCAN_Cron::instance();

			// init ajax handling.
			YITH_WCAN_Ajax::init();

			// init shortcodes.
			YITH_WCAN_Shortcodes::init();

			// init widgets.
			YITH_WCAN_Widgets::init();

			// init specific classes.
			if ( is_admin() ) {
				$this->admin = new YITH_WCAN_Admin();
			} else {
				$this->frontend = new YITH_WCAN_Frontend();
			}
		}

		/**
		 * Load classes for compatible themes/plugins
		 *
		 * @return void
		 * @since  4.0
		 */
		public function load_compatibilities() {
			// include theme compatibility, if any.
			add_action( 'after_setup_theme', array( $this, 'load_theme_compatibility' ) );

			// include compatibilities for installed plugins.
			add_action( 'init', array( $this, 'load_plugin_compatibilities' ) );
		}

		/**
		 * Load classes for compatible themes
		 *
		 * @return void
		 * @since  4.0
		 */
		public function load_theme_compatibility() {
			$theme = strtolower( wp_get_theme()->Name );
			$theme = str_replace( array( ' ', '-child' ), array( '-', '' ), $theme );

			$compatibility_path = YITH_WCAN_INC . "compatibility/themes/{$theme}/{$theme}.php";

			if ( file_exists( $compatibility_path ) ) {
				include_once $compatibility_path;
			}
		}

		/**
		 * Load classes for compatible plugins
		 *
		 * @return void
		 * @since  4.0
		 */
		public function load_plugin_compatibilities() {
			$supported_plugins = $this->get_compatible_plugins();

			if ( ! empty( $supported_plugins ) ) {
				foreach ( $supported_plugins as $plugin => $args ) {
					if ( isset( $args['check'] ) ) {
						list( $check_callable, $check_args ) = $args['check'];

						if ( ! call_user_func_array( $check_callable, $check_args ) ) {
							continue;
						}
					}

					$compatibility_path = YITH_WCAN_INC . "compatibility/plugins/{$plugin}/{$plugin}.php";

					if ( ! file_exists( $compatibility_path ) ) {
						continue;
					}

					include_once $compatibility_path;

					if ( isset( $args['callback'] ) ) {
						list( $callback_callable, $callback_args ) = $args['callback'];

						call_user_func( $callback_callable, $callback_args );
					}
				}
			}
		}

		/**
		 * Register assets used both on frontend and backend
		 * Specific assets may be found on Frontend and Admin classes; here we only register assets that we need to load both
		 * on frontend and backend, maybe because they are needed for Gutenberg editor.
		 *
		 * @return void
		 * @since  4.0
		 */
		public function register_assets() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_style( 'yith-wcan-shortcodes', YITH_WCAN_URL . 'assets/css/shortcodes.css', array(), YITH_WCAN_VERSION );
			wp_register_script( 'yith-wcan-shortcodes', YITH_WCAN_URL . 'assets/js/yith-wcan-shortcodes' . $suffix . '.js', array( 'jquery', \YIT_Assets::wc_script_handle( 'wc-accounting' ), 'selectWoo' ), YITH_WCAN_VERSION, true );

			if ( is_admin() ) {
				wp_localize_script( 'yith-wcan-shortcodes', 'yith_wcan_shortcodes', array() );
			}
		}

		/**
		 * Declare support for WooCommerce features.
		 */
		public function declare_wc_features_support() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_WCAN_INIT, true );
			}
		}

		/**
		 * Get choosen attribute args
		 *
		 * @return array
		 * @since  2.9.3
		 */
		public function get_layered_nav_chosen_attributes() {
			return WC_Query::get_layered_nav_chosen_attributes();
		}

		/**
		 * Return list of compatible plugins
		 *
		 * @return array Array of compatible plugins
		 *
		 * @since 4.0
		 */
		protected function get_compatible_plugins() {
			if ( empty( $this->supported_plugins ) ) {
				$this->supported_plugins = array(
					'ultimate-member'          => array(
						'check' => array( 'class_exists', array( 'UM_API' ) ),
					),
					'qtranslate-x'             => array(
						'check' => array( 'class_exists', array( 'QTX_Translator' ) ),
					),
					'wpml'                     => array(
						'check' => array( 'class_exists', array( 'Sitepress' ) ),
					),
					'yith-woocommerce-booking' => array(
						'check' => array( 'class_exists', array( 'YITH_WCBK' ) ),
					),
					'yith-woocommerce-colors-label-variations' => array(
						'check' => array( 'class_exists', array( 'YITH_WCCL' ) ),
					),
				);
			}

			/**
			 * APPLY_FILTERS: yith_wcan_compatible_plugins
			 *
			 * List of compatible plugins
			 *
			 * @param array $compatibilities List of compatible plugins.
			 *
			 * @return array
			 */
			return apply_filters( 'yith_wcan_compatible_plugins', $this->supported_plugins );
		}

		/**
		 * Main plugin Instance
		 *
		 * @return YITH_WCAN|YITH_WCAN_Premium Main instance
		 */
		public static function instance() {
			$class = static::class;

			if ( class_exists( "{$class}_Premium" ) ) {
				return YITH_WCAN_Premium::instance();
			} elseif ( class_exists( "{$class}_Extended" ) ) {
				return YITH_WCAN_Extended::instance();
			}

			if ( is_null( static::$instance ) ) {
				static::$instance = new static();
			}

			return static::$instance;
		}
	}
}

if ( ! function_exists( 'YITH_WCAN' ) ) {
	/**
	 * Return single instance for YITH_WCAN_Premium class
	 *
	 * @return YITH_WCAN
	 * @since 4.0.0
	 */
	function YITH_WCAN() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid, Universal.Files.SeparateFunctionsFromOO
		return YITH_WCAN::instance();
	}
}
