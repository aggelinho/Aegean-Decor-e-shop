<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Welcome class.
 *
 * @class Cookie_Notice_Welcome
 */
class Cookie_Notice_Welcome {

	private $pricing_monthly = [];
	private $pricing_yearly = [];

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'plugins_loaded', [ $this, 'allow_protocols' ] );
		add_action( 'admin_init', [ $this, 'init' ] );
		add_action( 'admin_init', [ $this, 'welcome' ] );
		add_action( 'wp_ajax_cn_welcome_screen', [ $this, 'welcome_screen' ] );
		add_action( 'wp_ajax_cn_dismiss_welcome', [ $this, 'dismiss_welcome' ] );
	}

	/**
	 * Allow new protocols.
	 *
	 * @return void
	 */
	function allow_protocols() {
		// allow only ajax calls
		if ( ! wp_doing_ajax() )
			return;

		// get action
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';

		// welcome screen?
		if ( $action === 'cn_welcome_screen' )
			add_filter( 'kses_allowed_protocols', [ $this, 'allow_data_protocol' ] );
	}

	/**
	 * Add new properties to style safe list.
	 *
	 * @param array $styles
	 * @return array
	 */
	public function allow_style_attributes( $styles ) {
		$styles[] = 'display';

		return $styles;
	}

	/**
	 * Add data protocol to image source.
	 *
	 * @param array $protocols
	 * @return array
	 */
	public function allow_data_protocol( $protocols ) {
		$protocols[] = 'data';

		return $protocols;
	}

	/**
	 * Load defaults.
	 *
	 * @return void
	 */
	public function init() {
		$this->pricing_monthly = [
			'compliance_monthly_notrial'	=> '14.95',
			'compliance_monthly_5'			=> '29.95',
			'compliance_monthly_10'			=> '49.95',
			'compliance_monthly_20'			=> '69.95'
		];

		$this->pricing_yearly = [
			'compliance_yearly_notrial'	=> '149.50',
			'compliance_yearly_5'		=> '299.50',
			'compliance_yearly_10'		=> '499.50',
			'compliance_yearly_20'		=> '699.50'
		];
	}

	/**
	 * Load scripts and styles - admin.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $page ) {
		// get main instance
		$cn = Cookie_Notice();

		if ( $cn->check_status( $cn->get_status() ) )
			return;

		// Legacy welcome scripts only load when ui_mode is explicitly set to legacy.
		if ( $cn->options['general']['ui_mode'] !== 'legacy' )
			return;

		// styles
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'cookie-notice-modaal', COOKIE_NOTICE_URL . '/assets/modaal/css/modaal.min.css', [], $cn->defaults['version'] );
		wp_enqueue_style( 'cookie-notice-spectrum', COOKIE_NOTICE_URL . '/assets/spectrum/spectrum.min.css', [], $cn->defaults['version'] );
		wp_enqueue_style( 'cookie-notice-microtip', COOKIE_NOTICE_URL . '/assets/microtip/microtip.min.css', [], $cn->defaults['version'] );

		// scripts
		wp_enqueue_script( 'cookie-notice-modaal', COOKIE_NOTICE_URL . '/assets/modaal/js/modaal.min.js', [], $cn->defaults['version'] );
		wp_enqueue_script( 'cookie-notice-spectrum', COOKIE_NOTICE_URL . '/assets/spectrum/spectrum.min.js', [], $cn->defaults['version'] );
		wp_enqueue_script( 'cookie-notice-welcome', COOKIE_NOTICE_URL . '/js/admin-welcome.js', [ 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ], $cn->defaults['version'] );
		wp_enqueue_script( 'cookie-notice-braintree-client', 'https://js.braintreegateway.com/web/3.71.0/js/client.min.js', [], null, false );
		wp_enqueue_script( 'cookie-notice-braintree-hostedfields', 'https://js.braintreegateway.com/web/3.71.0/js/hosted-fields.min.js', [], null, false );
		wp_enqueue_script( 'cookie-notice-braintree-paypal', 'https://js.braintreegateway.com/web/3.71.0/js/paypal-checkout.min.js', [], null, false );

		// check network
		$network = $cn->is_network_admin();

		// prepare script data
		$script_data = [
			'ajaxURL'			=> admin_url( 'admin-ajax.php' ),
			'network'			=> $network,
			'nonce'				=> wp_create_nonce( 'cookie-notice-welcome' ),
			'cnNonce'			=> wp_create_nonce( 'cookie-notice-welcome' ),
			'initModal'			=> $network ? get_site_transient( 'cn_show_welcome' ) : get_transient( 'cn_show_welcome' ), // welcome modal
			'error'				=> esc_html__( 'Unexpected error occurred. Please try again later.', 'cookie-notice' ),
			'statusPassed'		=> esc_html__( 'Passed', 'cookie-notice' ),
			'statusFailed'		=> esc_html__( 'Failed', 'cookie-notice' ),
			'paidMonth'			=> esc_html__( 'monthly', 'cookie-notice' ),
			'paidYear'			=> esc_html__( 'yearly', 'cookie-notice' ),
			'pricingMonthly'	=> $this->pricing_monthly,
			'pricingYearly'		=> $this->pricing_yearly,
			'complianceStatus'	=> $cn->get_status(),
			'complianceFailed'	=> sprintf( esc_html__( '%sCompliance Failed!%sYour website does not achieve minimum viable compliance. %sSign up to Compliance by Hu-manity.co%s to bring your site into compliance with the latest data privacy rules and regulations.', 'cookie-notice' ), '<em>', '</em>', '<b><a href="#" class="cn-sign-up">', '</a></b>' ),
			'compliancePassed'	=> sprintf( esc_html__( '%sCompliance Passed!%sCongratulations. Your website meets minimum viable compliance.', 'cookie-notice' ), '<em>', '</em>' ),
			'licensesAvailable'	=> esc_html__( 'available', 'cookie-notice' ),
			'invalidFields'		=> esc_html__( 'Please fill all the required fields.', 'cookie-notice' ),
			'dismissed'			=> (bool) get_option( 'cookie_notice_welcome_dismissed', false )
		];

		// delete the show modal transient
		if ( $network )
			delete_site_transient( 'cn_show_welcome' );
		else
			delete_transient( 'cn_show_welcome' );

		wp_add_inline_script( 'cookie-notice-welcome', 'var cnWelcomeArgs = ' . wp_json_encode( $script_data ) . ";\n", 'before' );

		wp_enqueue_style( 'cookie-notice-welcome', COOKIE_NOTICE_URL . '/css/admin-welcome.css', [], $cn->defaults['version'] );
	}

	/**
	 * Send user to the welcome page on first activation.
	 *
	 * @global string $pagenow
	 *
	 * @return void
	 */
	public function welcome() {
		global $pagenow;

		if ( $pagenow !== 'admin.php' )
			return;

		// get page
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

		if ( $page !== 'cookie-notice' )
			return;

		// bail if bulk activating or within an iframe
		if ( isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) )
			return;

		// get action
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';

		// get plugin
		$plugin = isset( $_GET['plugin'] ) ? sanitize_file_name( $_GET['plugin'] ) : '';

		if ( $action === 'upgrade-plugin' && strpos( $plugin, 'cookie-notice.php' ) !== false )
			return;

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_footer', [ $this, 'admin_footer' ] );
	}

	/**
	 * Welcome modal container.
	 *
	 * @return void
	 */
	public function admin_footer() {
		echo '<button id="cn-modal-trigger" style="display:none"></button>';
	}

	/**
	 * Dismiss the welcome modal — AJAX handler.
	 * Sets cookie_notice_welcome_dismissed option so the modal won't auto-fire again
	 * when user navigates to ?welcome=1 after closing without completing.
	 *
	 * @return void
	 */
	public function dismiss_welcome() {
		if ( ! check_ajax_referer( 'cookie-notice-welcome', 'nonce', false ) )
			wp_die( -1 );

		if ( ! current_user_can( 'manage_options' ) )
			wp_die( -1 );

		update_option( 'cookie_notice_welcome_dismissed', true, false );

		wp_die( 1 );
	}

	/**
	 * Render welcome screen sidebar step.
	 *
	 * @param int|string $screen
	 * @param bool $echo
	 * @return string|void
	 */
	public function welcome_screen( $screen, $echo = true ) {
		if ( ! current_user_can( 'install_plugins' ) )
			wp_die( __( 'You do not have permission to access this page.', 'cookie-notice' ) );

		$sidebars = [ 'about', 'login', 'register', 'configure', 'success' ];
		$steps = [ 1, 2, 3, 4 ];
		$screens = array_merge( $sidebars, $steps );

		if ( ! empty( $screen ) ) {
			if ( is_numeric( $screen ) )
				$screen = (int) $screen;
			else
				$screen = sanitize_key( $screen );
		} else
			$screen = '';

		if ( empty( $screen ) || ! in_array( $screen, $screens, true ) ) {
			if ( isset( $_REQUEST['screen'] ) ) {
				if ( is_numeric( $_REQUEST['screen'] ) )
					$screen = (int) $_REQUEST['screen'];
				else
					$screen = sanitize_key( $_REQUEST['screen'] );
			} else
				$screen = '';

			if ( ! in_array( $screen, $screens, true ) )
				$screen = '';
		}

		if ( empty( $screen ) )
			wp_die( __( 'You do not have permission to access this page.', 'cookie-notice' ) );

		if ( wp_doing_ajax() && ! check_ajax_referer( 'cookie-notice-welcome', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'cookie-notice' ) );

		// step screens
		if ( in_array( $screen, $steps ) ) {
			$html = '
			<div class="wrap full-width-layout cn-welcome-wrap cn-welcome-step-' . esc_attr( $screen ) . ' has-loader">';

			if ( $screen == 1 ) {
				$html .= $this->welcome_screen( 'about', false );

				$html .= '
				<div class="cn-content cn-sidebar-visible">
					<div class="cn-inner">
						<div class="cn-content-full">
							<h1><b>Compliance by Hu-manity.co</b></h1>
							<h2>' . esc_html__( 'Simple cookie & privacy compliance solution for your business.', 'cookie-notice' ) . '</h2>
							<div class="cn-lead">
								<div class="cn-hero-image">
									<div class="cn-flex-item">
										<img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/screen-compliance.png" alt="Compliance by Hu-manity.co dashboard" />
									</div>
								</div>
								<p>' . sprintf( esc_html__( 'Protect your business and take a proactive approach to data privacy laws with Compliance by Hu-manity.co. Build trust by giving your website visitors a beautiful, multi-level consent experience that complies with the latest cookie regulations in 100+ countries.', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
							</div>';
				$html .= '
							<div class="cn-buttons">
								<button type="button" class="cn-btn cn-btn-lg cn-screen-button" data-screen="2"><span class="cn-spinner"></span>' . esc_html__( 'Sign up to Compliance by Hu-manity.co', 'cookie-notice' ) . '</button><br />
								<button type="button" class="cn-btn cn-btn-lg cn-btn-transparent cn-skip-button">' . esc_html__( 'Skip for now', 'cookie-notice' ) . '</button>
							</div>
							';

				$html .= '
						</div>
					</div>
				</div>';
			} elseif ( $screen == 2 ) {
				$html .= $this->welcome_screen( 'configure', false );

				$html .= '
				<div id="cn_upgrade_iframe" class="cn-content cn-sidebar-visible has-loader cn-loading"><span class="cn-spinner"></span>
					<iframe id="cn_iframe_id" src="' . esc_url( home_url( '/?cn_preview_mode=1' ) ) . '"></iframe>
				</div>';
			} elseif ( $screen == 3 ) {
				$html .= $this->welcome_screen( 'register', false );

				$html .= '
				<div class="cn-content cn-sidebar-visible">
					<div class="cn-inner">
						<div class="cn-content-full">
							<h1><b>Compliance by Hu-manity.co</b></h1>
							<h2>' . esc_html__( 'Consent Management Platform with simple, transparent pricing.', 'cookie-notice' ) . '</h2>
							<div class="cn-lead">
								<p>' . esc_html__( 'Choose monthly or yearly payment and number of domains for the fully featured, Professional plan. Or start with limited, Basic plan for free.', 'cookie-notice' ) . '</p>
							</div>';

				$html .= '
							<h3 class="cn-pricing-select">' . esc_html__( 'Select plan', 'cookie-notice' ) . ':</h3>
							<div class="cn-pricing-type cn-checkmark-wrapper">
								<label for="pricing-type-monthly"><input id="pricing-type-monthly" type="radio" name="cn_pricing_type" value="monthly" checked><span class="cn-pricing-toggle toggle-left"><span class="cn-checkmark-container"><span class="cn-checkmark"></span></span><span class="cn-label">' . esc_html__( 'Monthly', 'cookie-notice' ) . '</span></span></label>
								<label for="pricing-type-yearly"><input id="pricing-type-yearly" type="radio" name="cn_pricing_type" value="yearly"><span class="cn-pricing-toggle toggle-right"><span class="cn-checkmark-container"><span class="cn-checkmark"></span></span><span class="cn-label">' . esc_html__( 'Yearly', 'cookie-notice' ) . '<span class="cn-badge">' . esc_html__( 'Save 12%', 'cookie-notice' ) . '</span></span></span></label>
							</div>
							<div class="cn-pricing-table">
								<label class="cn-pricing-item cn-pricing-plan-free" for="cn-pricing-plan-free">
									<input id="cn-pricing-plan-free" type="radio" name="cn_pricing" value="free">
									<div class="cn-pricing-info">
										<div class="cn-pricing-head">
											<h4>' . esc_html__( 'Basic', 'cookie-notice' ) . '</h4>
											<span class="cn-plan-pricing"><span class="cn-plan-price">' . esc_html__( 'Free', 'cookie-notice' ) . '</span></span>
										</div>
										<div class="cn-pricing-body">
											<p class="cn-included"><span class="cn-icon"></span>' . esc_html__( 'GDPR, CCPA, LGPD, PECR requirements', 'cookie-notice' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . esc_html__( 'Consent Analytics Dashboard', 'cookie-notice' ) . '</p>
											<p class="cn-excluded"><span class="cn-icon"></span>' . sprintf( esc_html__( '%s1,000%s visits / month', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-excluded"><span class="cn-icon"></span>' . sprintf( esc_html__( '%s100%s privacy consents', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-excluded"><span class="cn-icon"></span>' . sprintf( esc_html__( '%s30 days%s consent storage', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-excluded"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sGoogle & Facebook%s consent modes', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-excluded"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sGeolocation%s support', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-excluded"><span class="cn-icon"></span>' . sprintf( esc_html__( '%s1 additional%s language', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-excluded"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sBasic%s Support', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
										</div>
										<div class="cn-pricing-footer">
											<button type="button" class="cn-btn cn-btn-outline">' . esc_html__( 'Start Basic', 'cookie-notice' ) . '</button>
											<span class="cn-trust-badge">' . esc_html__( 'No credit card · Free to start', 'cookie-notice' ) . '</span>
										</div>
									</div>
								</label>
								<label class="cn-pricing-item cn-pricing-plan-pro" for="cn-pricing-plan-pro">
									<input id="cn-pricing-plan-pro" type="radio" name="cn_pricing" value="pro">
									<div class="cn-pricing-info">
										<div class="cn-pricing-head">
											<h4>' . esc_html__( 'Professional', 'cookie-notice' ) . '</h4>
											<span class="cn-plan-pricing"><span class="cn-plan-price"><sup>$ </sup><span class="cn-plan-amount">' . esc_attr( $this->pricing_monthly['compliance_monthly_notrial'] ) . '</span><sub> / <span class="cn-plan-period">' . esc_html__( 'monthly', 'cookie-notice' ) . '</span></sub></span></span>
											<span class="cn-plan-promo">' . esc_html__( 'Recommended', 'cookie-notice' ) . '</span>
											<div class="cn-select-wrapper">
												<select name="cn_pricing_plan" class="form-select" aria-label="' . esc_html__( 'Pricing options', 'cookie-notice' ) . '" id="cn-pricing-plans">
													<option value="compliance_monthly_notrial" data-price="' . esc_attr( $this->pricing_monthly['compliance_monthly_notrial'] ) . '">' . esc_html( sprintf( _n( '%s domain license', '%s domains license', 1, 'cookie-notice' ), 1 ) ) . '</option>
													<option value="compliance_monthly_5" data-price="' . esc_attr( $this->pricing_monthly['compliance_monthly_5'] ) . '">' . esc_html( sprintf( _n( '%s domain license', '%s domains license', 5, 'cookie-notice' ), 5 ) ) . '</option>
													<option value="compliance_monthly_10" data-price="' . esc_attr( $this->pricing_monthly['compliance_monthly_10'] ) . '">' . esc_html( sprintf( _n( '%s domain license', '%s domains license', 10, 'cookie-notice' ), 10 ) ) . '</option>
													<option value="compliance_monthly_20" data-price="' . esc_attr( $this->pricing_monthly['compliance_monthly_20'] ) . '">' . esc_html( sprintf( _n( '%s domain license', '%s domains license', 20, 'cookie-notice' ), 20 ) ) . '</option>
												</select>
											</div>
										</div>
										<div class="cn-pricing-body">
											<p class="cn-included"><span class="cn-icon"></span>' . esc_html__( 'GDPR, CCPA, LGPD, PECR requirements', 'cookie-notice' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . esc_html__( 'Consent Analytics Dashboard', 'cookie-notice' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sUnlimited%s visits', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sUnlimited%s privacy consents', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sLifetime%s consent storage', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sGoogle & Facebook%s consent modes', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sGeolocation%s support', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sUnlimited%s languages', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
											<p class="cn-included"><span class="cn-icon"></span>' . sprintf( esc_html__( '%sPriority%s Support', 'cookie-notice' ), '<b>', '</b>' ) . '</p>
										</div>
										<div class="cn-pricing-footer">
											<button type="button" class="cn-btn cn-btn-secondary">' . esc_html__( 'Start Professional', 'cookie-notice' ) . '</button>
										</div>
									</div>
								</label>
							</div>
							<div class="cn-buttons">
								<button type="button" class="cn-btn cn-btn-lg cn-btn-transparent cn-skip-button">' . esc_html__( "I don’t want to create an account now", 'cookie-notice' ) . '</button>
							</div>';

				$html .= '
						</div>
					</div>
				</div>';
			} elseif ( $screen == 4 ) {
				$html .= $this->welcome_screen( 'success', false );

				// get main instance
				$cn = Cookie_Notice();
				$subscription = $cn->get_subscription();

				$html .= '
				<div class="cn-content cn-sidebar-visible">
					<div class="cn-inner">
						<div class="cn-content-full">
							<h1><b>' . esc_html__( 'Congratulations', 'cookie-notice' ) . '</b></h1>
							<h2>' . esc_html__( 'You have successfully signed up to Compliance by Hu-manity.co.', 'cookie-notice' ) . '</h2>
							<div class="cn-lead">
								<p>' . esc_html__( 'Log in to your account and continue configuring your website.', 'cookie-notice' ) . '</p>
							</div>
							<div class="cn-buttons">
								<a href="' . esc_url( $cn->get_url( 'host', '?utm_campaign=configure&utm_source=wordpress&utm_medium=button#/login' ) ) . '" class="cn-btn cn-btn-lg" target="_blank">' . esc_html__( 'Go to Application', 'cookie-notice' ) . '</a>
							</div>
						</div>
					</div>
				</div>';
			}

			$html .= '
			</div>';
		// sidebar screens
		} elseif ( in_array( $screen, $sidebars ) ) {
			$html = '';

			if ( $screen === 'about' ) {
				$theme = wp_get_theme();

				$html .= '
				<div class="cn-sidebar cn-sidebar-left has-loader">
					<div class="cn-inner">
						<div class="cn-header">
							<div class="cn-top-bar">
								<div class="cn-logo"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/cookie-compliance-logo.png" alt="Compliance by Hu-manity.co" /></div>
							</div>
						</div>
						<div class="cn-body">
							<h2>' . esc_html__( 'Compliance check', 'cookie-notice' ) . '</h2>
							<div class="cn-lead"><p>' . esc_html__( 'This is a Compliance Check to determine your site’s compliance with updated data processing and consent rules under GDPR, CCPA and other international data privacy laws.', 'cookie-notice' ) . '</p></div>
							<div id="cn_preview_about">
								<p>' . esc_html__( 'Site URL', 'cookie-notice' ) . ': <b>' . esc_url( home_url() ) . '</b></p>
								<p>' . esc_html__( 'Site Name', 'cookie-notice' ) . ': <b>' . esc_html( get_bloginfo( 'name' ) ) . '</b></p>
							</div>
							<div class="cn-compliance-check">
								<div class="cn-progressbar"><div class="cn-progress-label">' . esc_html__( 'Checking...', 'cookie-notice' ) . '</div></div>
								<div class="cn-compliance-feedback cn-hidden"></div>
								<div class="cn-compliance-results">
									<div class="cn-compliance-item"><p><span class="cn-compliance-label">' . esc_html__( 'Consent Banner', 'cookie-notice' ) . ' </span><span class="cn-compliance-status"></span></p><p><span class="cn-compliance-desc">' . esc_html__( 'Notify visitors to the site that it uses cookies or similar technologies.', 'cookie-notice' ) . '</span></p></div>
									<div class="cn-compliance-item" style="display: none"><p><span class="cn-compliance-label">' . esc_html__( 'Autoblocking', 'cookie-notice' ) . ' </span><span class="cn-compliance-status"></span></p><p><span class="cn-compliance-desc">' . esc_html__( 'Block non-essential 3rd party services until consent is registered.', 'cookie-notice' ) . '</span></p></div>
									<div class="cn-compliance-item" style="display: none"><p><span class="cn-compliance-label">' . esc_html__( 'Cookie Categories', 'cookie-notice' ) . ' </span><span class="cn-compliance-status"></span></p><p><span class="cn-compliance-desc">' . esc_html__( 'Allow to customize the consent requested per purpose of use.', 'cookie-notice' ) . '</span></p></div>
									<div class="cn-compliance-item" style="display: none"><p><span class="cn-compliance-label">' . esc_html__( 'Cookie Consent Logs', 'cookie-notice' ) . ' </span><span class="cn-compliance-status"></span></p><p><span class="cn-compliance-desc">' . esc_html__( "Save the website visitor's cookie consent preferences.", 'cookie-notice' ) . '</span></p></div>
									<div class="cn-compliance-item" style="display: none"><p><span class="cn-compliance-label">' . esc_html__( 'Privacy Consent Logs', 'cookie-notice' ) . ' </span><span class="cn-compliance-status"></span></p><p><span class="cn-compliance-desc">' . esc_html__( "Record the website user's consent to the processing of personal data.", 'cookie-notice' ) . '</span></p></div>
									<div class="cn-compliance-item" style="display: none"><p><span class="cn-compliance-label">' . esc_html__( 'Proof-of-Consent', 'cookie-notice' ) . ' </span><span class="cn-compliance-status"></span></p><p><span class="cn-compliance-desc">' . esc_html__( 'Store and export a Proof-of-consent in secure audit format.', 'cookie-notice' ) . '</span></p></div>
								</div>
							</div>
							' /* <div id="cn_preview_frame"><img src=" ' . esc_url( $theme->get_screenshot() ) . '" /></div>
							. '<div id="cn_preview_frame"><div id="cn_preview_frame_wrapper"><iframe id="cn_iframe_id" src="' . home_url( '/?cn_preview_mode=0' ) . '" scrolling="no" frameborder="0"></iframe></div></div> */ . '
						</div>';
			} elseif ( $screen === 'configure' ) {
				$html .= '
				<div class="cn-sidebar cn-sidebar-left has-loader cn-theme-light">
					<div class="cn-inner">
						<div class="cn-header">
							<div class="cn-top-bar">
								<div class="cn-logo"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/cookie-compliance-logo.png" alt="Compliance by Hu-manity.co" /></div>
							</div>
						</div>
						<div class="cn-body">
							<h2>' . esc_html__( 'Live Setup', 'cookie-notice' ) . '</h2>
							<div class="cn-lead"><p>' . esc_html__( 'Configure your Compliance by Hu-manity.co design and compliance features through the options below. Click Apply Setup to save the configuration and go to selecting your preferred cookie solution.', 'cookie-notice' ) . '</p></div>
							<form method="post" id="cn-form-configure" class="cn-form" action="" data-action="configure">
								<div class="cn-accordion">
									<div class="cn-accordion-item cn-form-container" tabindex="-1">
										<div class="cn-accordion-header cn-form-header"><button class="cn-accordion-button" type="button">' . esc_html__( 'Banner Compliance', 'cookie-notice' ) . '</button></div>
										<div class="cn-accordion-collapse cn-form">
											<div class="cn-form-feedback cn-hidden"></div>' .
											/*
											<div class="cn-field cn-field-select">
												<label for="cn_location">' . __( 'What is the location of your business/organization?', 'cookie-notice' ) . '​</label>
												<div class="cn-select-wrapper">
													<select id="cn_location" name="cn_location">
														<option value="0">' . __( 'Select location', 'cookie-notice' ) . '</option>';

				foreach ( Cookie_Notice()->settings->countries as $country_code => $country_name ) {
					$html .= '<option value="' . $country_code . '">' . $country_name . '</option>';
				}

				$html .= '
													</select>
												</div>
											</div>
											*/
											'
											<div id="cn_laws" class="cn-field cn-field-checkbox">
												<label>' . esc_html__( 'Select the laws that apply to your business', 'cookie-notice' ) . ':</label>
												<div class="cn-checkbox-wrapper">
													<label for="cn_laws_gdpr"><input id="cn_laws_gdpr" type="checkbox" name="cn_laws" value="gdpr" title="' . esc_attr__( 'GDPR', 'cookie-notice' ) . '" checked><span>' . esc_html__( 'GDPR', 'cookie-notice' ) . '<span class="cn-tooltip" aria-label="' . esc_html__( 'European Union and Switzerland', 'cookie-notice' ) . '" data-microtip-position="right" data-microtip-size="small" role="tooltip"><i class="cn-tooltip-icon"></i></span></span></label>
													<label for="cn_laws_ccpa"><input id="cn_laws_ccpa" type="checkbox" name="cn_laws" value="ccpa" title="' . esc_attr__( 'CCPA', 'cookie-notice' ) . '"><span>' . esc_html__( 'CCPA/CPRA', 'cookie-notice' ) . '<span class="tooltip" aria-label="' . esc_html__( 'California', 'cookie-notice' ) . '" data-microtip-position="right" data-microtip-size="small" role="tooltip"><i class="cn-tooltip-icon"></i></span></span></label>
													<label for="cn_laws_otherus"><input id="cn_laws_otherus" type="checkbox" name="cn_laws" value="otherus" title="' . esc_attr__( 'Other U.S. State Laws', 'cookie-notice' ) . '"><span>' . esc_html__( 'Other U.S. State Laws', 'cookie-notice' ) . '<span class="tooltip" aria-label="' . esc_html__( 'Virginia, Colorado, Connecticut, Utah, etc.', 'cookie-notice' ) . '" data-microtip-position="right" data-microtip-size="small" role="tooltip"><i class="cn-tooltip-icon"></i></span></span></label>
													<label for="cn_laws_ukpecr"><input id="cn_laws_ukpecr" type="checkbox" name="cn_laws" value="ukpecr" title="' . esc_attr__( 'UK PECR', 'cookie-notice' ) . '"><span>' . esc_html__( 'UK PECR', 'cookie-notice' ) . '<span class="tooltip" aria-label="' . esc_html__( 'United Kingdom', 'cookie-notice' ) . '" data-microtip-position="right" data-microtip-size="small" role="tooltip"><i class="cn-tooltip-icon"></i></span></span></label>
													<label for="cn_laws_lgpd"><input id="cn_laws_lgpd" type="checkbox" name="cn_laws" value="lgpd" title="' . esc_attr__( 'LGPD', 'cookie-notice' ) . '"><span>' . esc_html__( 'LGPD', 'cookie-notice' ) . '<span class="tooltip" aria-label="' . esc_html__( 'Brazil', 'cookie-notice' ) . '" data-microtip-position="right" data-microtip-size="small" role="tooltip"><i class="cn-tooltip-icon"></i></span></span></label>
													<label for="cn_laws_pipeda"><input id="cn_laws_pipeda" type="checkbox" name="cn_laws" value="pipeda" title="' . esc_attr__( 'PIPEDA', 'cookie-notice' ) . '"><span>' . esc_html__( 'PIPEDA', 'cookie-notice' ) . '<span class="tooltip" aria-label="' . esc_html__( 'Canada', 'cookie-notice' ) . '" data-microtip-position="right" data-microtip-size="small" role="tooltip"><i class="cn-tooltip-icon"></i></span></span></label>
													<label for="cn_laws_popia"><input id="cn_laws_popia" type="checkbox" name="cn_laws" value="popia" title="' . esc_attr__( 'POPIA', 'cookie-notice' ) . '"><span>' . esc_html__( 'POPIA', 'cookie-notice' ) . '<span class="tooltip" aria-label="' . esc_html__( 'South Africa', 'cookie-notice' ) . '" data-microtip-position="right" data-microtip-size="small" role="tooltip"><i class="cn-tooltip-icon"></i></span></span></label>
													<label for="cn_laws_other"><input id="cn_laws_other" type="checkbox" name="cn_laws" value="other" title="' . esc_attr__( 'Other', 'cookie-notice' ) . '"><span>' . esc_html__( 'Other', 'cookie-notice' ) . '</span></label>
												</div>
											</div>
											<div id="cn_naming" class="cn-field cn-field-radio">
												<label class="cn-asterix">' . esc_html__( 'Select a naming style for the consent choices', 'cookie-notice' ) . ':</label>
												<div class="cn-radio-wrapper">
													<label for="cn_naming_1"><input id="cn_naming_1" type="radio" name="cn_naming" value="1" checked><span>' . esc_html__( 'Private, Balanced, Personalized', 'cookie-notice' ) . '</span></label>
													<label for="cn_naming_2"><input id="cn_naming_2" type="radio" name="cn_naming" value="2"><span>' . esc_html__( 'Silver, Gold, Platinum', 'cookie-notice' ) . '</span></label>
													<label for="cn_naming_3"><input id="cn_naming_3" type="radio" name="cn_naming" value="3"><span>' . esc_html__( 'Reject All, Accept Some, Accept All​', 'cookie-notice' ) . '</span></label>
												</div>
											</div>
											<div class="cn-field cn-field-checkbox">
												<label>' . esc_html__( 'Select basic consent options:', 'cookie-notice' ) . '</label>
												<div class="cn-checkbox-wrapper">
													<label for="cn_on_scroll"><input id="cn_on_scroll" type="checkbox" name="cn_on_scroll" value="1"><span>' . esc_html__( 'Consent on Scroll', 'cookie-notice' ) . '</span></label>
													<label for="cn_on_click"><input id="cn_on_click" type="checkbox" name="cn_on_click" value="1"><span>' . esc_html__( 'Consent on Click', 'cookie-notice' ) . '</span></label>
													<label for="cn_ui_blocking"><input id="cn_ui_blocking" type="checkbox" name="cn_ui_blocking" value="1"><span>' . esc_html__( 'UI Blocking', 'cookie-notice' ) . '</span></label>
													<label for="cn_revoke_consent"><input id="cn_revoke_consent" type="checkbox" name="cn_revoke_consent" value="1" checked><span>' . esc_html__( 'Revoke Consent', 'cookie-notice' ) . '</span></label>
												</div>
											</div>' . 
											// <div class="cn-small">* ' . esc_html__( 'available for Cookie Compliance&trade; Pro plans only', 'cookie-notice' ) . '</div>
										'</div>
									</div>
									<div class="cn-accordion-item cn-form-container cn-collapsed" tabindex="-1">
										<div class="cn-accordion-header cn-form-header"><button class="cn-accordion-button" type="button">' . esc_html__( 'Banner Design', 'cookie-notice' ) . '</button></div>
										<div class="cn-accordion-collapse cn-form">
											<div class="cn-form-feedback cn-hidden"></div>
											<div class="cn-field cn-field-radio-image">
												<label>' . esc_html__( 'Select your preferred display position', 'cookie-notice' ) . '​:</label>
												<div class="cn-radio-image-wrapper">
													<label for="cn_position_bottom"><input id="cn_position_bottom" type="radio" name="cn_position" value="bottom" title="' . esc_attr__( 'Bottom', 'cookie-notice' ) . '" checked><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/layout-bottom.png" width="24" height="24"></label>
													<label for="cn_position_top"><input id="cn_position_top" type="radio" name="cn_position" value="top" title="' . esc_attr__( 'Top', 'cookie-notice' ) . '"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/layout-top.png" width="24" height="24"></label>
													<label for="cn_position_left"><input id="cn_position_left" type="radio" name="cn_position" value="left" title="' . esc_attr__( 'Left', 'cookie-notice' ) . '"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/layout-left.png" width="24" height="24"></label>
													<label for="cn_position_right"><input id="cn_position_right" type="radio" name="cn_position" value="right" title="' . esc_attr__( 'Right', 'cookie-notice' ) . '"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/layout-right.png" width="24" height="24"></label>
													<label for="cn_position_center"><input id="cn_position_center" type="radio" name="cn_position" value="center" title="' . esc_attr__( 'Center', 'cookie-notice' ) . '"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/layout-center.png" width="24" height="24"></label>
												</div>
											</div>
											<div class="cn-field cn-fieldset">
												<label>' . esc_html__( 'Adjust the banner color scheme', 'cookie-notice' ) . '​:</label>
												<div class="cn-checkbox-wrapper cn-color-picker-wrapper">
													<label for="cn_color_primary"><input id="cn_color_primary" class="cn-color-picker" type="checkbox" name="cn_color_primary" value="#20c19e"><span>' . esc_html__( 'Color of the buttons and interactive elements.', 'cookie-notice' ) . '</span></label>
													<label for="cn_color_background"><input id="cn_color_background" class="cn-color-picker" type="checkbox" name="cn_color_background" value="#ffffff"><span>' . esc_html__( 'Color of the banner background.', 'cookie-notice' ) . '</span></label>
													<label for="cn_color_text"><input id="cn_color_text" class="cn-color-picker" type="checkbox" name="cn_color_text" value="#434f58"><span>' . esc_html__( 'Color of the body text.', 'cookie-notice' ) . '</span></label>
													<label for="cn_color_border"><input id="cn_color_border" class="cn-color-picker" type="checkbox" name="cn_color_border" value="#5e6a74"><span>' . esc_html__( 'Color of the borders and inactive elements.', 'cookie-notice' ) . '</span></label>
													<label for="cn_color_heading"><input id="cn_color_heading" class="cn-color-picker" type="checkbox" name="cn_color_heading" value="#434f58"><span>' . esc_html__( 'Color of the heading text.', 'cookie-notice' ) . '</span></label>
													<label for="cn_color_button_text"><input id="cn_color_button_text" class="cn-color-picker" type="checkbox" name="cn_color_button_text" value="#ffffff"><span>' . esc_html__( 'Color of the button text.', 'cookie-notice' ) . '</span></label>
												</div>
											</div>' . 
											// <div class="cn-small">* ' . esc_html__( 'available for Cookie Compliance&trade; Pro plans only', 'cookie-notice' ) . '</div>
										'</div>
									</div>
								</div>
								<div class="cn-field cn-field-submit cn-nav">
									<button type="button" class="cn-btn cn-screen-button" data-screen="3"><span class="cn-spinner"></span>' . esc_html__( 'Apply Setup', 'cookie-notice' ) . '</button>
								</div>';

				$html .= wp_nonce_field( 'cn_api_configure', 'cn_nonce', true, false );

				$html .= '
							</form>
						</div>';
			} elseif ( $screen === 'register' ) {
				$html .= '
				<div class="cn-sidebar cn-sidebar-left has-loader">
					<div class="cn-inner">
						<div class="cn-header">
							<div class="cn-top-bar">
								<div class="cn-logo"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/cookie-compliance-logo.png" alt="Compliance by Hu-manity.co" /></div>
							</div>
						</div>
						<div class="cn-body">
							<h2>' . esc_html__( 'Compliance account', 'cookie-notice' ) . '</h2>
							<div class="cn-lead">
								<p>' . esc_html__( 'Create a Compliance by Hu-manity.co account and select your preferred plan.', 'cookie-notice' ) . '</p>
							</div>
							<div class="cn-accordion">
								<div id="cn-accordion-account" class="cn-accordion-item cn-form-container" tabindex="-1">
									<div class="cn-accordion-header cn-form-header"><button class="cn-accordion-button" type="button">1. ' . esc_html__( 'Create Account', 'cookie-notice' ) . '</button></div>
									<div class="cn-accordion-collapse">
										<form method="post" class="cn-form" action="" data-action="register">
											<div class="cn-form-feedback cn-hidden"></div>
											<div class="cn-field cn-field-text">
												<input type="text" name="email" value="" tabindex="1" placeholder="' . esc_attr__( 'Email address', 'cookie-notice' ) . '">
											</div>
											<div class="cn-field cn-field-text">
												<input type="password" name="pass" value="" tabindex="2" autocomplete="off" placeholder="' . esc_attr__( 'Password', 'cookie-notice' ) . '">
												<span>' . esc_html( 'Minimum eight characters, at least one capital letter and one number are required.', 'cookie-notice' ) . '</span>
											</div>
											<div class="cn-field cn-field-text">
												<input type="password" name="pass2" value="" tabindex="3" autocomplete="off" placeholder="' . esc_attr__( 'Confirm Password', 'cookie-notice' ) . '">
											</div>
											<div class="cn-field cn-field-checkbox">
												<div class="cn-checkbox-wrapper">
													<label for="cn_terms"><input id="cn_terms" type="checkbox" name="terms" value="1"><span>' . sprintf( esc_html__( 'I have read and agree to the %sTerms of Service%s', 'cookie-notice' ), '<a href="https://cookie-compliance.co/terms-of-service/?utm_campaign=accept-terms&utm_source=wordpress&utm_medium=link" target="_blank">', '</a>' ) . '</span></label>
												</div>
											</div>
											<div class="cn-field cn-field-submit cn-nav">
												<button type="submit" class="cn-btn cn-screen-button" tabindex="4" data-screen="4"><span class="cn-spinner"></span>' . esc_html__( 'Sign Up', 'cookie-notice' ) . '</button>
											</div>';

				// get site language
				$locale = get_locale();
				$locale_code = explode( '_', $locale );

				$html .= '
											<input type="hidden" name="language" value="' . esc_attr( $locale_code[0] ) . '" />';

				$html .= wp_nonce_field( 'cn_api_register', 'cn_nonce', true, false );

				$html .= '
										</form>
										<p>' . esc_html__( 'Already have an account?', 'cookie-notice' ) . ' <a href="#" class="cn-screen-button" data-screen="login">' . esc_html__( 'Sign in', 'cookie-notice' ). '</a></p>
									</div>
								</div>';

				$html .= '
								<div id="cn-accordion-billing" class="cn-accordion-item cn-form-container cn-collapsed cn-disabled" tabindex="-1">
									<div class="cn-accordion-header cn-form-header">
										<button class="cn-accordion-button" type="button">2. ' . esc_html__( 'Select Plan', 'cookie-notice' ) . '</button>
									</div>
									<form method="post" class="cn-accordion-collapse cn-form cn-form-disabled" action="" data-action="payment">
										<div class="cn-form-feedback cn-hidden"></div>
										<div class="cn-field cn-field-radio">
											<div class="cn-radio-wrapper cn-plan-wrapper">
												<label for="cn-field-plan-free" class="cn-pricing-plan-free"><input id="cn-field-plan-free" type="radio" name="plan" value="free" checked><span><span class="cn-plan-description">' . esc_html__( 'Basic', 'cookie-notice' ) . '</span><span class="cn-plan-pricing"><span class="cn-plan-price">Free</span></span><span class="cn-plan-overlay"></span></span></label>
												<label for="cn-field-plan-pro" class="cn-pricing-plan-pro"><input id="cn-field-plan-pro" type="radio" name="plan" value="compliance_monthly_notrial"><span><span class="cn-plan-description">' . sprintf( esc_html__( '%sProfessional%s', 'cookie-notice' ), '<b>', '</b>' ) . ' - <span class="cn-plan-period">' . esc_html__( 'monthly', 'cookie-notice' ) . '</span></span><span class="cn-plan-pricing"><span class="cn-plan-price">$<span class="cn-plan-amount">' . esc_attr( $this->pricing_monthly['compliance_monthly_notrial'] ) . '</span></span></span><span class="cn-plan-overlay"></span></span></label>
											</div>
										</div>
										<div class="cn-field cn-fieldset" id="cn_submit_free">
											<button type="submit" class="cn-btn cn-screen-button" tabindex="4" data-screen="4"><span class="cn-spinner"></span>' . esc_html__( 'Confirm', 'cookie-notice' ) . '</button>
										</div>
										<div class="cn-field cn-fieldset cn-hidden" id="cn_submit_pro">
											<input type="hidden" name="cn_payment_identifier" value="" />
											<div class="cn-field cn-field-radio">
												<label>' . esc_html__( 'Payment Method', 'cookie-notice' ) . '</label>
												<div class="cn-radio-wrapper cn-horizontal-wrapper">
													<label for="cn_field_method_credit_card"><input id="cn_field_method_credit_card" type="radio" name="method" value="credit_card" checked><span>' . esc_html__( 'Credit Card', 'cookie-notice' ) . '</span></label>
													<label for="cn_field_method_paypal"><input id="cn_field_method_paypal" type="radio" name="method" value="paypal"><span>' . esc_html__( 'PayPal', 'cookie-notice' ) . '</span></label>
												</div>
											</div>
											<div class="cn-fieldset" id="cn_payment_method_credit_card">
												<input type="hidden" name="payment_nonce" value="" />
												<div class="cn-field cn-field-text">
													<label for="cn_card_number">' . esc_html__( 'Card Number', 'cookie-notice' ) . '</label>
													<div id="cn_card_number"></div>
												</div>
												<div class="cn-field cn-field-text cn-field-half cn-field-first">
													<label for="cn_expiration_date">' . esc_html__( 'Expiration Date', 'cookie-notice' ) . '</label>
													<div id="cn_expiration_date"></div>
												</div>
												<div class="cn-field cn-field-text cn-field-half cn-field-last">
													<label for="cn_cvv">' . esc_html__( 'CVC/CVV', 'cookie-notice' ) . '</label>
													<div id="cn_cvv"></div>
												</div>
												<div class="cn-field cn-field-submit cn-nav">
													<button type="submit" class="cn-btn cn-screen-button" tabindex="4" data-screen="4"><span class="cn-spinner"></span>' . esc_html__( 'Submit', 'cookie-notice' ) . '</button>
												</div>
											</div>
											<div class="cn-fieldset" id="cn_payment_method_paypal" style="display: none">
												<div id="cn_paypal_button"></div>
											</div>
										</div>';

				$html .= wp_nonce_field( 'cn_api_payment', 'cn_payment_nonce', true, false );

				$html .= '
									</form>
								</div>
							</div>
						</div>';
			} elseif ( $screen === 'login' ) {
				$html .= '
				<div class="cn-sidebar cn-sidebar-left has-loader">
					<div class="cn-inner">
						<div class="cn-header">
							<div class="cn-top-bar">
								<div class="cn-logo"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/cookie-compliance-logo.png" alt="Compliance by Hu-manity.co" /></div>
							</div>
						</div>
						<div class="cn-body">
							<h2>' . esc_html__( 'Compliance Sign in', 'cookie-notice' ) . '</h2>
							<div class="cn-lead">
								<p>' . esc_html__( 'Sign in to your existing Compliance by Hu-manity.co account and select your preferred plan.', 'cookie-notice' ) . '</p>
							</div>
							<div class="cn-accordion">
								<div id="cn-accordion-account" class="cn-accordion-item cn-form-container" tabindex="-1">
									<div class="cn-accordion-header cn-form-header"><button class="cn-accordion-button" type="button">1. ' . esc_html__( 'Account Login', 'cookie-notice' ) . '</button></div>
									<div class="cn-accordion-collapse">
										<form method="post" class="cn-form" action="" data-action="login">
											<div class="cn-form-feedback cn-hidden"></div>
											<div class="cn-field cn-field-text">
												<input type="text" name="email" value="" tabindex="1" placeholder="' . esc_attr__( 'Email address', 'cookie-notice' ) . '">
											</div>
											<div class="cn-field cn-field-text">
												<input type="password" name="pass" value="" tabindex="2" autocomplete="off" placeholder="' . esc_attr__( 'Password', 'cookie-notice' ) . '">
											</div>
											<div class="cn-field cn-field-submit cn-nav">
												<button type="submit" class="cn-btn cn-screen-button" tabindex="4" ' . /* data-screen="4" */ '><span class="cn-spinner"></span>' . esc_html__( 'Sign in', 'cookie-notice' ) . '</button>
											</div>';

				// get site language
				$locale = get_locale();
				$locale_code = explode( '_', $locale );

				$html .= '
											<input type="hidden" name="language" value="' . esc_attr( $locale_code[0] ) . '" />';

				$html .= wp_nonce_field( 'cn_api_login', 'cn_nonce', true, false );

				$html .= '
										</form>
										<p>' . esc_html__( 'Don\'t have an account yet?', 'cookie-notice' ) . ' <a href="#" class="cn-screen-button" data-screen="register">' . esc_html__( 'Sign up', 'cookie-notice' ) . '</a></p>
									</div>
								</div>
								<div id="cn-accordion-billing" class="cn-accordion-item cn-form-container cn-collapsed cn-disabled" tabindex="-1">
									<div class="cn-accordion-header cn-form-header">
										<button class="cn-accordion-button" type="button">2. ' . esc_html__( 'Select Plan', 'cookie-notice' ) . '</button>
									</div>
									<form method="post" class="cn-accordion-collapse cn-form cn-form-disabled" action="" data-action="payment">
										<div class="cn-form-feedback cn-hidden"></div>
										<div class="cn-field cn-field-radio">
											<div class="cn-radio-wrapper cn-plan-wrapper">
												<label for="cn-field-plan-free" class="cn-pricing-plan-free"><input id="cn-field-plan-free" type="radio" name="plan" value="free" checked><span><span class="cn-plan-description">' . esc_html__( 'Basic', 'cookie-notice' ) . '</span><span class="cn-plan-pricing"><span class="cn-plan-price">Free</span></span><span class="cn-plan-overlay"></span></span></label>
												<label for="cn-field-plan-pro" class="cn-pricing-plan-pro"><input id="cn-field-plan-pro" type="radio" name="plan" value="compliance_monthly_notrial"><span><span class="cn-plan-description">' . sprintf( esc_html__( '%sProfessional%s', 'cookie-notice' ), '<b>', '</b>' ) . ' - <span class="cn-plan-period">' . esc_html__( 'monthly', 'cookie-notice' ) . '</span></span><span class="cn-plan-pricing"><span class="cn-plan-price">$<span class="cn-plan-amount">' . esc_attr( $this->pricing_monthly['compliance_monthly_notrial'] ) . '</span></span></span><span class="cn-plan-overlay"></span></span></label>
												<label for="cn-field-plan-license" class="cn-pricing-plan-license cn-disabled">
													<input id="cn-field-plan-license" type="radio" name="plan" value="license"><span><span class="cn-plan-description">' . esc_html__( 'Use License', 'cookie-notice' ) . '</span><span class="cn-plan-pricing"><span class="cn-plan-price"><span class="cn-plan-amount">0</span> ' . esc_html__( 'available', 'cookie-notice' ) . '</span></span><span class="cn-plan-overlay"></span></span>
												</label>
											</div>
										</div>
										<div class="cn-field cn-fieldset" id="cn_submit_free">
											<button type="submit" class="cn-btn cn-screen-button" tabindex="4" data-screen="4"><span class="cn-spinner"></span>' . esc_html__( 'Confirm', 'cookie-notice' ) . '</button>
										</div>
										<div class="cn-field cn-fieldset cn-hidden" id="cn_submit_pro">
											<input type="hidden" name="cn_payment_identifier" value="" />
											<div class="cn-field cn-field-radio">
												<label>' . esc_html__( 'Payment Method', 'cookie-notice' ) . '</label>
												<div class="cn-radio-wrapper cn-horizontal-wrapper">
													<label for="cn_field_method_credit_card"><input id="cn_field_method_credit_card" type="radio" name="method" value="credit_card" checked><span>' . esc_html__( 'Credit Card', 'cookie-notice' ) . '</span></label>
													<label for="cn_field_method_paypal"><input id="cn_field_method_paypal" type="radio" name="method" value="paypal"><span>' . esc_html__( 'PayPal', 'cookie-notice' ) . '</span></label>
												</div>
											</div>
											<div class="cn-fieldset" id="cn_payment_method_credit_card">
												<input type="hidden" name="payment_nonce" value="" />
												<div class="cn-field cn-field-text">
													<label for="cn_card_number">' . esc_html__( 'Card Number', 'cookie-notice' ) . '</label>
													<div id="cn_card_number"></div>
												</div>
												<div class="cn-field cn-field-text cn-field-half cn-field-first">
													<label for="cn_expiration_date">' . esc_html__( 'Expiration Date', 'cookie-notice' ) . '</label>
													<div id="cn_expiration_date"></div>
												</div>
												<div class="cn-field cn-field-text cn-field-half cn-field-last">
													<label for="cn_cvv">' . esc_html__( 'CVC/CVV', 'cookie-notice' ) . '</label>
													<div id="cn_cvv"></div>
												</div>
												<div class="cn-field cn-field-submit cn-nav">
													<button type="submit" class="cn-btn cn-screen-button" tabindex="4" data-screen="4"><span class="cn-spinner"></span>' . esc_html__( 'Submit', 'cookie-notice' ) . '</button>
												</div>
											</div>
											<div class="cn-fieldset" id="cn_payment_method_paypal" style="display: none">
												<div id="cn_paypal_button"></div>
											</div>
										</div>
										<div class="cn-field cn-fieldset cn-hidden" id="cn_submit_license">
											<div class="cn-field cn-field-select" id="cn-subscriptions-list">
												<label for="cn-subscription-select">' . esc_html__( 'Select subscription', 'cookie-notice' ) . '​</label>
												<select  name="cn_subscription_id" class="form-select" aria-label="' . esc_attr__( 'Licenses', 'cookie-notice' ) . '" id="cn-subscription-select">
												</select>
											</div><br>
											<button type="submit" class="cn-btn cn-screen-button" tabindex="4" data-screen="4"><span class="cn-spinner"></span>' . esc_html__( 'Confirm', 'cookie-notice' ) . '</button>
										</div>';

				$html .= wp_nonce_field( 'cn_api_payment', 'cn_payment_nonce', true, false );

				$html .= '
									</form>
								</div>
							</div>
						</div>';
			} elseif ( $screen === 'success' ) {
				$html .= '
				<div class="cn-sidebar cn-sidebar-left has-loader">
					<div class="cn-inner">
						<div class="cn-header">
							<div class="cn-top-bar">
								<div class="cn-logo"><img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/cookie-compliance-logo.png" alt="Compliance by Hu-manity.co" /></div>
							</div>
						</div>
						<div class="cn-body">
							<h2>' . esc_html__( 'Success!', 'cookie-notice' ) . '</h2>
							<div class="cn-lead"><p><b>' . esc_html__( 'You have successfully integrated your website with Compliance by Hu-manity.co.', 'cookie-notice' ) . '</b></p><p>' . sprintf( esc_html__( 'Go to Compliance by Hu-manity.co now. Or access it anytime from your %sCompliance settings page%s.', 'cookie-notice' ), '<a href="' . esc_url( Cookie_Notice()->is_network_admin() ? network_admin_url( 'admin.php?page=cookie-notice' ) : admin_url( 'admin.php?page=cookie-notice' ) ) . '">', '</a>' ) . '</p></div>
						</div>';
			}



			$html .= '
					<div class="cn-footer">';
			/*
			switch ( $screen ) {
				case 'about':
					$html .= '<a href="' . esc_url( admin_url( 'admin.php?page=cookie-notice' ) ) . '" class="cn-btn cn-btn-link cn-skip-button">' . __( 'Skip Live Setup', 'cookie-notice' ) . '</a>';
					break;
				case 'success':
					$html .= '<a href="' . esc_url( get_dashboard_url() ) . '" class="cn-btn cn-btn-link cn-skip-button">' . __( 'WordPress Dashboard', 'cookie-notice' ) . '</a>';
					break;
				default:
					$html .= '<a href="' . esc_url( admin_url( 'admin.php?page=cookie-notice' ) ) . '" class="cn-btn cn-btn-link cn-skip-button">' . __( 'Skip for now', 'cookie-notice' ) . '</a>';
					break;
			}
			*/
			$html .= '
					</div>
				</div>
			</div>';
		}

		if ( $echo ) {
			// get allowed html
			$allowed_html = wp_kses_allowed_html( 'post' );
			$allowed_html['div']['tabindex'] = true;
			$allowed_html['button']['tabindex'] = true;
			$allowed_html['iframe'] = [
				'id'	=> true,
				'src'	=> true
			];
			$allowed_html['form'] = [
				'id'			=> true,
				'class'			=> true,
				'action'		=> true,
				'data-action'	=> true
			];
			$allowed_html['select'] = [
				'name'			=> true,
				'class'			=> true,
				'id'			=> true,
				'aria-label'	=> true
			];
			$allowed_html['option'] = [
				'value'			=> true,
				'data-price'	=> true
			];
			$allowed_html['input'] = [
				'id'			=> true,
				'type'			=> true,
				'name'			=> true,
				'class'			=> true,
				'value'			=> true,
				'tabindex'		=> true,
				'autocomplete'	=> true,
				'checked'		=> true,
				'placeholder'	=> true,
				'title'			=> true
			];

			add_filter( 'safe_style_css', [ $this, 'allow_style_attributes' ] );

			// echo wp_kses( $html, $allowed_html );
			echo $html;

			remove_filter( 'safe_style_css', [ $this, 'allow_style_attributes' ] );
		} else
			return $html;

		if ( wp_doing_ajax() )
			exit();
	}
}
