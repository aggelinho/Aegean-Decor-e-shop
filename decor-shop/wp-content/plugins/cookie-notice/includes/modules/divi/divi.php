<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules Divi class.
 *
 * Compatibility since: 2.4.19
 *
 * @class Cookie_Notice_Modules_Divi
 */
class Cookie_Notice_Modules_Divi {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'cn_is_preview_mode', [ $this, 'is_preview_mode' ] );
	}

	/**
	 * Whether Divi builder is active.
	 *
	 * @return bool
	 */
	function is_preview_mode() {
		return is_et_pb_preview() || isset( $_GET[ 'et_fb' ] );
	}
}

new Cookie_Notice_Modules_Divi();