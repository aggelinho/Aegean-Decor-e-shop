<?php
/**
 * Autoloader.
 *
 * @package YITH\AjaxProductFilter\Classes
 * @version 2.0.0
 */

defined( 'YITH_WCAN' ) || exit;

if ( ! class_exists( 'YITH_WCAN_Autoloader' ) ) {
	/**
	 * Autoloader class.
	 */
	class YITH_WCAN_Autoloader {

		/**
		 * Path to the includes directory.
		 *
		 * @var string
		 */
		private $include_path = '';

		/**
		 * The Constructor.
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );

			$this->include_path = YITH_WCAN_INC;
		}

		/**
		 * Take a class name and turn it into a file name.
		 *
		 * @param string $class Class name.
		 *
		 * @return string
		 */
		private function get_file_name_from_class( $class ) {
			$filename = '';
			$base     = str_replace( '_', '-', $class );

			if ( false !== strpos( $class, 'interface' ) ) {
				$base     = str_replace( '-interface', '', $base );
				$filename = 'interface-' . $base . '.php';
			} elseif ( false !== strpos( $class, 'trait' ) ) {
				$base     = str_replace( '-trait', '', $base );
				$filename = 'trait-' . $base . '.php';
			}

			if ( empty( $filename ) ) {
				$filename = 'class-' . $base . '.php';
			}

			return $filename;
		}

		/**
		 * Include a class file.
		 *
		 * @param string $path File path.
		 *
		 * @return bool Successful or not.
		 */
		private function load_file( $path ) {
			if ( $path && is_readable( $path ) ) {
				include_once $path;

				return true;
			}

			return false;
		}

		/**
		 * Auto-load plugins' classes on demand to reduce memory consumption.
		 *
		 * @param string $class Class name.
		 */
		public function autoload( $class ) {
			$class = strtolower( $class );

			if ( 0 !== strpos( $class, 'yith_wcan' ) ) {
				return;
			}

			$file = $this->get_file_name_from_class( $class );
			$path = '';

			if ( false !== strpos( $class, 'interface' ) ) {
				$path = $this->include_path . 'interfaces/';
			} elseif ( false !== strpos( $class, 'trait' ) ) {
				$path = $this->include_path . 'traits/';
			} elseif ( false !== strpos( $class, 'abstract' ) ) {
				$path = $this->include_path . 'abstracts/';
			} elseif ( false !== strpos( $class, 'data_store' ) ) {
				$path = $this->include_path . 'data-stores/';
			} elseif ( false !== strpos( $class, 'shortcode' ) ) {
				$path = $this->include_path . 'shortcodes/';
			} elseif ( false !== strpos( $class, 'widget' ) ) {
				$path = $this->include_path . 'widgets/';
			} elseif ( false !== strpos( $class, 'elementor' ) ) {
				$path = $this->include_path . 'elementor/';
			} elseif ( false !== strpos( $class, 'cache_provider' ) ) {
				$path = $this->include_path . 'cache-providers/';
			} elseif ( false !== strpos( $class, 'table' ) ) {
				$path = $this->include_path . 'tables/';
			} elseif ( false !== strpos( $class, 'shortcode' ) && false === strpos( $class, 'shortcodes' ) ) {
				$path = $this->include_path . 'shortcodes/';
			} elseif ( false !== strpos( $class, 'filter' ) && false === strpos( $class, 'filters' ) ) {
				$path = $this->include_path . 'filters/';
			}

			if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
				$this->load_file( $this->include_path . $file );
			}
		}
	}
}

new YITH_WCAN_Autoloader();
