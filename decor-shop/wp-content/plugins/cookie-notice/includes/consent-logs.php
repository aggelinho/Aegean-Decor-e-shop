<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Consent_Logs class.
 *
 * @class Cookie_Notice_Consent_Logs
 */
class Cookie_Notice_Consent_Logs {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'wp_ajax_cn_get_cookie_consent_logs', [ $this, 'get_cookie_consent_logs' ] );
	}

	/**
	 * Get cookie consent logs via AJAX request.
	 *
	 * @return void
	 */
	public function get_cookie_consent_logs() {
		// check data
		if ( ! isset( $_POST['action'], $_POST['date'], $_POST['nonce'] ) )
			wp_send_json_error();

		// valid nonce?
		if ( ! check_ajax_referer( 'cn-get-cookie-consent-logs', 'nonce' ) )
			wp_send_json_error();

		// check capability
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			wp_send_json_error();

		// sanitize date
		$date = preg_replace( '[^\d-]', '', $_POST['date'] );

		// get datetime
		$dt = DateTime::createFromFormat( 'Y-m-d', $date );

		// valid date?
		if ( $dt && $dt->format( 'Y-m-d' ) === $date ) {
			$data = Cookie_Notice()->welcome_api->get_cookie_consent_logs( $date );

			if ( is_array( $data ) )
				wp_send_json_success( $this->get_cookie_consent_logs_table( $data ) );
			else
				wp_send_json_error( $data );
		}

		wp_send_json_error();
	}

	/**
	 * Get consent logs from specific date.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function get_cookie_consent_logs_table( $data ) {
		// include wp list table class if needed
		if ( ! class_exists( 'WP_List_Table' ) )
			include_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

		// include consent logs list table
		include_once( COOKIE_NOTICE_PATH . '/includes/consent-logs-list-table.php' );

		// initialize list table
		$list_table = new Cookie_Notice_Consent_Logs_List_Table( [
			'plural'	=> 'cn-cookie-consent-day-logs',
			'singular'	=> 'cn-cookie-consent-day-log',
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

	/**
	 * Get single row template.
	 *
	 * @return string
	 */
	public function get_single_row_template() {
		return '
		<tr id="" class="cn-consent-log-details">
			<th></th>
			<td colspan="5">
				<div class="cn-consent-logs-data loading">
					<span class="spinner is-active"></span>
				</div>
			</td>
		</tr>';
	}

	/**
	 * Get error template.
	 *
	 * @return string
	 */
	public function get_error_template() {
		return '<p class="description">' . esc_html__( 'We were unable to download consent logs due to an error. Please try again later.', 'cookie-notice' ) . '</p>';
	}
}
