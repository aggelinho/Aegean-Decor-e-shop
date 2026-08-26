<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Privacy_Consent class.
 *
 * @class Cookie_Notice_Privacy_Consent
 */
class Cookie_Notice_Privacy_Consent {

	private $sources = [];
	private $instances = [];
	public $form_active_types = [];

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'plugins_loaded', [ $this, 'add_settings' ] );
		add_action( 'init', [ $this, 'init_privacy_consent' ], 5 );
		add_action( 'init', [ $this, 'load_defaults' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Get all sources data.
	 *
	 * @return array
	 */
	public function get_sources() {
		return $this->sources;
	}

	/**
	 * Get single source data.
	 *
	 * @param array $source_id
	 *
	 * @return array
	 */
	public function get_source( $source_id ) {
		return array_key_exists( $source_id, $this->sources ) ? $this->sources[$source_id] : [];
	}

	/**
	 * Get active sources.
	 *
	 * @return array
	 */
	public function get_active_sources() {
		$sources = [];

		foreach ( $this->sources as $source_id => $source ) {
			if ( $source['availability'] && $source['status'] )
				$sources[] = $source_id;
		}

		return $sources;
	}

	/**
	 * Get available sources.
	 *
	 * @return array
	 */
	public function get_available_sources() {
		$sources = [];

		foreach ( $this->sources as $source_id => $source ) {
			if ( $source['availability'] )
				$sources[] = $source_id;
		}

		return $sources;
	}

	/**
	 * Check whether source is available.
	 *
	 * @param string $source_id
	 *
	 * @return bool
	 */
	public function is_source_available( $source_id ) {
		
	}

	/**
	 * Add source.
	 *
	 * @param array $source
	 *
	 * @return array
	 */
	public function add_source( $source ) {
		$this->sources[$source['id']] = $source;
	}

	/**
	 * Strip string to specified length removing multibyte character from the end if needed.
	 *
	 * @param string $str
	 * @param int $length
	 *
	 * @return string
	 */
	public function strcut( $str = '', $length = 100 ) {
		if ( function_exists( 'mb_strcut' ) )
			return mb_strcut( $str, 0, $length );

		// get length
		$str_length = strlen( $str );

		// smaller string?
		if ( $str_length <= $length )
			return $str;

		// check any multibyte characters
		preg_match_all( '/./u', $str, $chars );

		if ( ! empty( $chars[0] ) ) {
			// no multibyte characters
			if ( count( $chars[0] ) === $str_length )
				return $str;

			$mb_str_length = 0;

			// check every character
			foreach ( $chars[0] as $char ) {
				// get character length
				$mb_char_length = strlen( $char );

				// length with new character
				$new_str_length = $mb_str_length + $mb_char_length;

				// longer then expected? cut just before new character
				if ( $new_str_length > $length )
					return substr( $str, 0, $mb_str_length );
				// perfect length? cut without stripping
				elseif ( $new_str_length === $length )
					return substr( $str, 0, $new_str_length );

				$mb_str_length += $mb_char_length;
			}
		} else
			return substr( $str, 0, 100 );
	}

	/**
	 * Add instance.
	 *
	 * @param object $instance
	 * @param array $source
	 *
	 * @return array
	 */
	public function add_instance( $instance, $source ) {
		$this->instances[$source] = $instance;
	}

	/**
	 * Initialize privacy consent.
	 *
	 * @return void
	 */
	public function init_privacy_consent() {
		// get main instance
		$cn = Cookie_Notice();

		if ( is_admin() ) {
			// handle ajax requests
			add_action( 'wp_ajax_cn_privacy_consent_form_status', [ $this, 'set_form_status' ] );
			add_action( 'wp_ajax_cn_privacy_consent_get_forms', [ $this, 'query_forms' ] );
			add_action( 'wp_ajax_cn_privacy_consent_display_table', [ $this, 'display_table' ] );

			// include wp list table class if needed
			if ( ! class_exists( 'WP_List_Table' ) )
				include_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

			// include privacy consent list table
			include_once( COOKIE_NOTICE_PATH . '/includes/privacy-consent-list-table.php' );
		}

		// include modules
		include_once( COOKIE_NOTICE_PATH . '/includes/modules/wordpress/privacy-consent.php' );
		include_once( COOKIE_NOTICE_PATH . '/includes/modules/contact-form-7/privacy-consent.php' );
		include_once( COOKIE_NOTICE_PATH . '/includes/modules/mailchimp/privacy-consent.php' );
		include_once( COOKIE_NOTICE_PATH . '/includes/modules/woocommerce/privacy-consent.php' );
		include_once( COOKIE_NOTICE_PATH . '/includes/modules/wpforms/privacy-consent.php' );
		include_once( COOKIE_NOTICE_PATH . '/includes/modules/formidable-forms/privacy-consent.php' );
		include_once( COOKIE_NOTICE_PATH . '/includes/modules/easy-digital-downloads/privacy-consent.php' );

		// update 2.5.0
		if ( version_compare( $cn->db_version, '2.4.18', '<=' ) ) {
			// $sources = get_available_sources()
			$sources = $cn->defaults['privacy_consent'];

			// check all available sources
			foreach ( $this->sources as $source_id => $source ) {
				if ( $source['availability'] )
					$sources[$source_id . '_active'] = true;
			}

			if ( is_multisite() )
				update_site_option( 'cookie_notice_privacy_consent', $sources );
			else
				update_option( 'cookie_notice_privacy_consent', $sources, null, false );
		}
	}

	/**
	 * Load default data.
	 *
	 * @return void
	 */
	public function load_defaults() {
		$this->form_active_types = [
			'all'		=> __( 'Apply to all forms', 'cookie-notice' ),
			'selected'	=> __( 'Apply to selected forms', 'cookie-notice' )
		];
	}

	/**
	 * Add settings.
	 *
	 * @return void
	 */
	public function add_settings() {
		if ( ! is_admin() )
			return;

		// get main instance
		$cn = Cookie_Notice();

		// update 2.5.0
		if ( version_compare( $cn->db_version, '2.4.18', '<=' ) ) {
			if ( is_multisite() )
				add_site_option( 'cookie_notice_privacy_consent', $cn->defaults['privacy_consent'] );
			else
				add_option( 'cookie_notice_privacy_consent', $cn->defaults['privacy_consent'], null, false );
		}
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		// register privacy consent settings
		register_setting( 'cookie_notice_privacy_consent', 'cookie_notice_privacy_consent', [ $this, 'validate_options' ] );

		add_settings_section( 'cookie_notice_privacy_consent_status', esc_html__( 'Compliance Integration', 'cookie-notice' ), '', 'cookie_notice_privacy_consent', [ 'before_section' => '<div class="%s">', 'after_section' => '</div>', 'section_class' => 'cn-section-container compliance-section' ] );

		add_settings_field( 'cn_privacy_consent_status', esc_html__( 'Compliance Status', 'cookie-notice' ), [ $this, 'cn_privacy_consent_status' ], 'cookie_notice_privacy_consent', 'cookie_notice_privacy_consent_status' );

		// add section
		add_settings_section( 'cookie_notice_privacy_consent_settings', esc_html__( 'Privacy Consent Settings', 'cookie-notice' ), [ $this, 'display_section' ], 'cookie_notice_privacy_consent', [ 'before_section' => '<div class="%s">', 'after_section' => '</div>', 'section_class' => 'cn-section-container privacy-section' ] );

		foreach ( $this->sources as $source ) {
			add_settings_field( 'cn_privacy_consent_' . esc_attr( $source['id'] ), esc_html( $source['name'] ), [ $this, 'option' ], 'cookie_notice_privacy_consent', 'cookie_notice_privacy_consent_settings', $source );
		}
	}

	/**
	 * Display section.
	 *
	 * @return void
	 */
	public function display_section() {
		wp_nonce_field( 'cn-privacy-consent-list-table-nonce', 'cn_privacy_consent_nonce' );
	}

	/**
	 * Compliance status.
	 *
	 * @return void
	 */
	public function cn_privacy_consent_status() {
		// get main instance
		$cn = Cookie_Notice();

		// get cookie compliance status
		$app_status = $cn->get_status();

		if ( $cn->is_network_admin() )
			$url = network_admin_url( 'admin.php?page=cookie-notice' );
		else
			$url = admin_url( 'admin.php?page=cookie-notice' );

		switch ( $app_status ) {
			case 'active':
				echo '
				<div id="cn_app_status">
					<div class="cn_compliance_status"><span class="cn-status-label">' . esc_html__( 'Privacy Consent', 'cookie-notice' ) . '</span>: <span class="cn-status cn-active"><span class="cn-icon"></span> ' . esc_html__( 'Active', 'cookie-notice' ) . '</span></div>
					<div class="cn_compliance_status"><span class="cn-status-label">' . esc_html__( 'Privacy Consent Storage', 'cookie-notice' ) . '</span>: <span class="cn-status cn-active"><span class="cn-icon"></span> ' . esc_html__( 'Active', 'cookie-notice' ) . '</span></div>
					<div class="cn_compliance_status"><span class="cn-status-label">' . esc_html__( 'Proof-of-Consent', 'cookie-notice' ) . '</span>: <span class="cn-status cn-active"><span class="cn-icon"></span> ' . esc_html__( 'Active', 'cookie-notice' ) . '</span></div>
				</div>
				<div id="cn_app_actions">
					<a href="' . esc_url( $cn->get_url( 'host', '?utm_campaign=configure&utm_source=wordpress&utm_medium=button#/dashboard' ) ) . '" class="button button-primary button-hero cn-button" target="_blank">' . esc_html__( 'Log in & Configure', 'cookie-notice' ) . '</a>
					<p class="description">' . esc_html__( 'Log in to the Compliance by Hu-manity.co dashboard to explore, configure and manage its functionalities.', 'cookie-notice' ) . '</p>
				</div>';
				break;

			case 'pending':
				echo '
				<div id="cn_app_status">
					<div class="cn_compliance_status"><span class="cn-status-label">' . esc_html__( 'Privacy Consent', 'cookie-notice' ) . '</span>: <span class="cn-status cn-pending"><span class="cn-icon"></span> ' . esc_html__( 'Pending', 'cookie-notice' ) . '</span></div>
					<div class="cn_compliance_status"><span class="cn-status-label">' . esc_html__( 'Privacy Consent Storage', 'cookie-notice' ) . '</span>: <span class="cn-status cn-pending"><span class="cn-icon"></span> ' . esc_html__( 'Pending', 'cookie-notice' ) . '</span></div>
					<div class="cn_compliance_status"><span class="cn-status-label">' . esc_html__( 'Proof-of-Consent', 'cookie-notice' ) . '</span>: <span class="cn-status cn-pending"><span class="cn-icon"></span> ' . esc_html__( 'Pending', 'cookie-notice' ) . '</span></div>
				</div>
				<div id="cn_app_actions">
					<a href="' . esc_url( $cn->get_url( 'host', '?utm_campaign=configure&utm_source=wordpress&utm_medium=button#/dashboard' ) ) . '" class="button button-primary button-hero cn-button" target="_blank">' . esc_html__( 'Log in & Configure', 'cookie-notice' ) . '</a>
					<p class="description">' . esc_html__( 'Log in to the Compliance by Hu-manity.co web application and complete the setup process.', 'cookie-notice' ) . '</p>
				</div>';
				break;

			default:
				echo '
				<div id="cn_app_status">
					<div class="cn_compliance_status"><span class="cn-status-label">' . '<span class="cn-status-label">' . esc_html__( 'Privacy Consent', 'cookie-notice' ) . '</span>: <span class="cn-status cn-inactive"><span class="cn-icon"></span> ' . esc_html__( 'Inactive', 'cookie-notice' ) . '</span></div>
					<div class="cn_compliance_status"><span class="cn-status-label">' . '<span class="cn-status-label">' . esc_html__( 'Privacy Consent Storage', 'cookie-notice' ) . '</span>: <span class="cn-status cn-inactive"><span class="cn-icon"></span> ' . esc_html__( 'Inactive', 'cookie-notice' ) . '</span></div>
					<div class="cn_compliance_status"><span class="cn-status-label">' . esc_html__( 'Proof-of-Consent', 'cookie-notice' ) . '</span>: <span class="cn-status cn-inactive"><span class="cn-icon"></span> ' . esc_html__( 'Inactive', 'cookie-notice' ) . '</span></div>
				</div>
				<div id="cn_app_actions">
					<a href="' . esc_url( $url ) . '" class="button button-primary button-hero cn-button cn-run-welcome">' . esc_html__( 'Try Compliance by Hu-manity.co free', 'cookie-notice' ) . '</a>
					<p class="description">' . sprintf( esc_html__( 'Sign up to %s and enable Privacy Consent support.', 'cookie-notice' ), '<a href="https://cookie-compliance.co/?utm_campaign=sign-up&utm_source=wordpress&utm_medium=textlink" target="_blank">Compliance by Hu-manity.co</a>' ) . '</p>
				</div>';
		}
	}

	/**
	 * Display source options.
	 *
	 * @return void
	 */
	public function option( $source ) {
		// get main instance
		$cn = Cookie_Notice();

		// get cookie compliance status
		$status = $cn->get_status();

		// disable source for network area
		if ( is_multisite() && $cn->is_network_admin() && $cn->is_plugin_network_active() )
			$source['status'] = false;

		echo '
		<fieldset id="cn_privacy_consent_' . esc_attr( $source['id'] ) . '" class="cn-' . ( $source['availability'] ? '' : 'un' ) . 'available cn-' . ( $status === 'active' ? 'active' : 'inactive' ) . '">
			<div>
				<label><input class="cn-privacy-consent-status" type="checkbox" name="' . esc_attr( 'cookie_notice_privacy_consent[' . $source['id'] . '_active]' ) . '" value="1" data-source="' . esc_attr( $source['id'] ) . '" ' . checked( true, $source['status'] && $source['availability'], false ) . ' ' . disabled( $status === 'active' && $source['availability'], false, false ) . ' />' . sprintf( esc_html__( 'Enable to apply privacy consent support for %s forms.', 'cookie-notice' ), '<strong>' . $source['name'] . '</strong>' ) . '</label>
			</div>
			<div class="cn-privacy-consent-options-container"' . ( $source['status'] && $source['availability'] ? '' : ' style="display: none"' ) . '>
				<div>';

		foreach ( $this->form_active_types as $active_type => $label ) {
			echo '
					<label><input class="cn-privacy-consent-active-type" type="radio" name="' . esc_attr( 'cookie_notice_privacy_consent[' . $source['id'] . '_active_type]' ) . '" value="' . esc_attr( $active_type ) . '" ' . checked( $active_type, $source['status_type'], false ) . ' />' . esc_html( $label ) . '</label>';
		}

		echo '
				</div>
				<div class="cn-privacy-consent-list-table-container apply-' . esc_attr( $source['status_type'] ) . '">';

		if ( $source['availability'] ) {
			// initialize list table
			$list_table = new Cookie_Notice_Privacy_Consent_List_Table( [
				'plural'	=> 'cn-source-' . esc_attr( $source['name'] ) . '-forms',
				'singular'	=> 'cn-source-' . esc_attr( $source['name'] ) . '-form',
				'ajax'		=> false
			] );

			// set source
			$list_table->cn_set_source( $source );

			// set source forms
			$list_table->cn_set_forms( [ 'forms' => $source['forms'] ] );

			// set empty init for dynamic sources
			if ( $source['type'] === 'dynamic' )
				$list_table->cn_empty_init();

			// prepare items
			$list_table->prepare_items();

			// display table
			$list_table->display();
		}

		echo '
				</div>
			</div>
		</fieldset>';
	}

	/**
	 * Source forms query to get data.
	 *
	 * @return void
	 */
	function query_forms() {
		// valid nonce?
		if ( check_ajax_referer( 'cn-privacy-consent-list-table-nonce', 'nonce' ) === false )
			wp_send_json_error();

		// check data
		if ( ! isset( $_REQUEST['action'], $_REQUEST['nonce'], $_REQUEST['source'], $_REQUEST['paged'], $_REQUEST['order'], $_REQUEST['orderby'], $_REQUEST['search'] ) )
			wp_send_json_error();

		// check capability
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			wp_send_json_error();

		// sanitize data
		$source = sanitize_key( $_REQUEST['source'] );
		$order = sanitize_key( $_REQUEST['order'] );
		$orderby = sanitize_key( $_REQUEST['orderby'] );
		$search = trim( sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) );
		$page = (int) $_REQUEST['paged'];

		// validate order
		if ( ! in_array( $order, [ 'asc', 'desc' ], true ) )
			$order = 'asc';

		// validate orderby
		if ( ! in_array( $orderby, [ 'title', 'date' ], true ) )
			$orderby = 'title';

		if ( ! array_key_exists( $source, $this->sources ) || ! $this->sources[$source]['availability'] )
			wp_send_json_error();

		// initialize list table
		$list_table = new Cookie_Notice_Privacy_Consent_List_Table( [
			'plural'	=> 'cn-source-' . esc_attr( $this->sources[$source]['name'] ) . '-forms',
			'singular'	=> 'cn-source-' . esc_attr( $this->sources[$source]['name'] ) . '-form',
			'ajax'		=> true
		] );

		// set source
		$list_table->cn_set_source( $this->sources[$source] );

		$args = [
			'source'	=> $source,
			'order'		=> $order,
			'orderby'	=> $orderby,
			'page'		=> max( $page, $list_table->get_pagenum() ),
			'search'	=> $search
		];

		// set source forms
		$list_table->cn_set_forms( $this->instances[$source]->get_forms( $args ) );

		// handle ajax request
		$list_table->ajax_response();
	}

	/**
	 * Display initial (first page) source table.
	 *
	 * @return void
	 */
	function display_table() {
		// valid nonce?
		if ( check_ajax_referer( 'cn-privacy-consent-list-table-nonce', 'nonce' ) === false )
			wp_send_json_error();

		// check data
		if ( ! isset( $_REQUEST['action'], $_REQUEST['nonce'], $_REQUEST['source'] ) )
			wp_send_json_error();

		// check capability
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			wp_send_json_error();

		// sanitize source
		$source = sanitize_key( $_REQUEST['source'] );

		if ( ! array_key_exists( $source, $this->sources ) || ! $this->sources[$source]['availability'] )
			wp_send_json_error();

		// make title column sorted
		if ( empty( $_GET['orderby'] ) )
			$_GET['orderby'] = 'title';

		if ( empty( $_GET['order'] ) )
			$_GET['order'] = 'asc';

		// initialize list table
		$list_table = new Cookie_Notice_Privacy_Consent_List_Table( [
			'plural'	=> 'cn-source-' . esc_attr( $this->sources[$source]['name'] ) . '-forms',
			'singular'	=> 'cn-source-' . esc_attr( $this->sources[$source]['name'] ) . '-form',
			'ajax'		=> true
		] );

		// set source
		$list_table->cn_set_source( $this->sources[$source] );

		$args = [
			'source'	=> $source,
			'order'		=> 'asc',
			'orderby'	=> 'title',
			'page'		=> 1,
			'search'	=> ''
		];

		// set source forms
		$list_table->cn_set_forms( $this->instances[$source]->get_forms( $args ) );

		// prepare items
		$list_table->prepare_items();

		ob_start();
		// $list_table->search_box( __( 'Search', 'cookie-notice' ), $source );
		$list_table->display();
		$display = ob_get_clean();

		wp_send_json_success( $display );
	}

	/**
	 * Set form status.
	 *
	 * @return void
	 */
	public function set_form_status() {
		if ( ! isset( $_POST['source'], $_POST['form_id'], $_POST['status'] ) || wp_verify_nonce( $_POST['nonce'], 'cn-privacy-consent-set-form-status' ) === false )
			wp_send_json_error();

		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			wp_send_json_error();

		// sanitize source
		$source = sanitize_key( $_POST['source'] );

		// active source?
		if ( array_key_exists( $source, $this->sources ) && $this->sources[$source]['availability'] ) {
			// sanitize form id
			if ( $this->sources[$source]['id_type'] === 'integer' )
				$form_id = (int) $_POST['form_id'];
			elseif ( $this->sources[$source]['id_type'] === 'string' )
				$form_id = (string) sanitize_key( $_POST['form_id'] );

			// valid form?
			if ( $this->instances[$source]->form_exists( $form_id ) ) {
				// inactive source?
				if ( ! $this->sources[$source]['status'] ) {
					// get privacy consent data
					$data = get_option( 'cookie_notice_privacy_consent' );

					// activate source
					$data[$source . '_active'] = true;

					// update privacy consent
					update_option( 'cookie_notice_privacy_consent', $data );
				}

				// get source data
				$data = get_option( 'cookie_notice_privacy_consent_' . $source );

				// update status of specified form
				$data[$form_id]['status'] = (bool) (int) $_POST['status'];

				// update source
				update_option( 'cookie_notice_privacy_consent_' . $source, $data );

				wp_send_json_success();
			}
		}

		wp_send_json_error();
	}

	/**
	 * Check whether form is active.
	 *
	 * @param int|string $form_id
	 * @param string $source
	 *
	 * @return bool
	 */
	public function is_form_active( $form_id, $source ) {
		// sanitize source
		$source = sanitize_key( $source );

		// unavailable source?
		if ( ! array_key_exists( $source, $this->sources ) )
			return false;

		// inactive source?
		if ( ! $this->sources[$source]['availability'] )
			return false;

		// disabled source?
		if ( ! $this->sources[$source]['status'] )
			return false;

		// allow all forms?
		if ( $this->sources[$source]['status_type'] === 'all' )
			return true;

		// sanitize form id
		if ( $this->sources[$source]['id_type'] === 'integer' )
			$form_id = (int) $form_id;
		elseif ( $this->sources[$source]['id_type'] === 'string' )
			$form_id = (string) sanitize_key( $form_id );

		// get source data
		$data = get_option( 'cookie_notice_privacy_consent_' . $source, [] );

		// valid form?
		if ( array_key_exists( $form_id, $data ) && array_key_exists( 'status', $data[$form_id] ) )
			return $data[$form_id]['status'];
		else
			return false;
	}

	/**
	 * Set cookie.
	 *
	 * @param string $value
	 *
	 * @return void
	 */
	public function set_cookie( $value ) {
		// set cookie
		setcookie(
			'hu-form',
			$value,
			[
				'expires'	=> current_time( 'timestamp', true ) + 5 * MINUTE_IN_SECONDS,
				'path'		=> COOKIEPATH,
				'domain'	=> COOKIE_DOMAIN,
				'secure'	=> is_ssl(),
				'httponly'	=> false,
				'samesite'	=> 'LAX'
			]
		);
	}

	/**
	 * Validate options.
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public function validate_options( $input ) {
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			return $input;

		if ( isset( $_POST['save_cookie_notice_privacy_consent'] ) ) {
			// validate every source
			foreach ( $this->sources as $source ) {
				$input = $this->instances[$source['id']]->validate( $input );
			}

			add_settings_error( 'cn_cookie_notice_options', 'save_cookie_notice_privacy_consent', esc_html__( 'Settings saved.', 'cookie-notice' ), 'updated' );
		} elseif ( isset( $_POST['reset_cookie_notice_privacy_consent'] ) ) {
			$input = Cookie_Notice()->defaults['privacy_consent'];

			add_settings_error( 'cn_cookie_notice_options', 'reset_cookie_notice_privacy_consent', esc_html__( 'Settings restored to defaults.', 'cookie-notice' ), 'updated' );
		}

		do_action( 'cn_configuration_updated', 'privacy-consent', $input );

		return $input;
	}
}
