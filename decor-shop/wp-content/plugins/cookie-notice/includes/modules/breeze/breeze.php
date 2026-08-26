<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules Breeze class.
 *
 * Compatibility since: 2.0.30
 *
 * @class Cookie_Notice_Modules_Breeze
 */
class Cookie_Notice_Modules_Breeze {

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
	 * Add compatibility to Breeze plugin.
	 *
	 * @return void
	 */
	public function load_module() {
		// get main instance
		$cn = Cookie_Notice();

		// update 2.5.7+
		if ( version_compare( $cn->db_version, '2.5.7', '<=' ) )
			$this->remove_excluded_external_script();

		// is caching active?
		if ( (int) Breeze_Options_Reader::get_option_value( 'breeze-active' ) === 1 ) {
			// update 2.4.16+
			if ( version_compare( $cn->db_version, '2.4.16', '<=' ) ) {
				// clear cache
				$this->delete_cache();
			}

			add_action( 'cn_configuration_updated', [ $this, 'delete_cache' ] );

			// is js minification active?
			if ( (int) Breeze_Options_Reader::get_option_value( 'breeze-minify-js' ) === 1 ) {
				// filters
				add_filter( 'cn_cookie_compliance_output', [ $this, 'update_cc_output' ] );
			}
		}
	}

	/**
	 * Update Cookie Compliance output.
	 *
	 * @param string $output
	 *
	 * @return string
	 */
	public function update_cc_output( $output ) {
		// add special /breeze-extra/ comment
		return preg_replace( '/<script(.*)var huOptions(.*)<\/script>/', "<script$1var huOptions$2\n//breeze-extra/</script>", $output, 1 );
	}

	/**
	 * Remove previously excluded external script from being minified/combined.
	 *
	 * @return void
	 */
	public function remove_excluded_external_script() {
		$pattern = '(.*)/js/hu-options.js(.*)';

		// get breeze file options
		$file_options = breeze_get_option( 'file_settings' );

		// find pattern
		$key = array_search( $pattern, $file_options['breeze-exclude-js'], true );

		// found pattern? remove it
		if ( $key !== false )
			unset( $file_options['breeze-exclude-js'][$key] );

		// update breeze file options
		breeze_update_option( 'file_settings', $file_options, true );
	}

	/**
	 * Delete all cache files.
	 *
	 * @return void
	 */
	public function delete_cache() {
		do_action( 'breeze_clear_all_cache' );
	}
}

new Cookie_Notice_Modules_Breeze();