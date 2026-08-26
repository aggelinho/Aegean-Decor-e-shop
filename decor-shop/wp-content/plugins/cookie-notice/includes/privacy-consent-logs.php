<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Privacy_Consent_logs class.
 *
 * @class Cookie_Notice_Privacy_Consent_logs
 */
class Cookie_Notice_Privacy_Consent_logs {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'wp_ajax_cn_get_privacy_consent_logs', [ $this, 'get_privacy_consent_logs' ] );
	}

	/**
	 * Get privacy consent logs via AJAX request.
	 *
	 * @return void
	 */
	public function get_privacy_consent_logs() {
		// check data
		if ( ! isset( $_POST['action'], $_POST['nonce'] ) )
			wp_send_json_error();

		// valid nonce?
		if ( check_ajax_referer( 'cn-get-privacy-consent-logs', 'nonce' ) === false )
			wp_send_json_error();

		// check capability
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			wp_send_json_error();

		$data = Cookie_Notice()->welcome_api->get_privacy_consent_logs();

		if ( is_array( $data ) )
			wp_send_json_success( $this->get_privacy_consent_logs_table( $data ) );
		else
			wp_send_json_error( $data );
	}

	/**
	 * Get consent logs from specific date.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function get_privacy_consent_logs_table( $data ) {
		// include wp list table class if needed
		if ( ! class_exists( 'WP_List_Table' ) )
			include_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

		// include consent logs list table
		include_once( COOKIE_NOTICE_PATH . '/includes/privacy-consent-logs-list-table.php' );

		// initialize list table
		$list_table = new Cookie_Notice_Privacy_Consent_Logs_List_Table( [
			'plural'	=> 'cn-privacy-consent-logs',
			'singular'	=> 'cn-privacy-consent-log',
			'ajax'		=> false
		] );

		// prepare data
		$list_table->cn_set_data( $data );

		// prepare items
		$list_table->prepare_items();

		ob_start();
		$list_table->display();
		$html = ob_get_clean();

		return $html;
	}
}
