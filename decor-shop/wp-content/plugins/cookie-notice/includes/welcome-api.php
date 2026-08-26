<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Welcome_API class.
 *
 * @class Cookie_Notice_Welcome_API
 */
class Cookie_Notice_Welcome_API {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'init', [ $this, 'check_cron' ] );
		add_action( 'cookie_notice_get_app_analytics', [ $this, 'get_app_analytics' ] );
		add_action( 'cookie_notice_get_app_config', [ $this, 'get_app_config' ] );
		add_action( 'wp_ajax_cn_api_request', [ $this, 'api_request' ] );

		// React write hooks — only register when ui_mode is "react" (#2267).
		$ui_mode = Cookie_Notice()->options['general']['ui_mode'] ?? 'legacy';

		if ( $ui_mode === 'react' ) {
			add_action( 'wp_ajax_cn_react_update_design', [ $this, 'react_update_design' ] );
			add_action( 'wp_ajax_cn_react_apply_template', [ $this, 'react_apply_template' ] );
			add_action( 'wp_ajax_cn_react_apply_languages', [ $this, 'react_apply_languages' ] );
		}
	}

	/**
	 * Ajax API request.
	 *
	 * @return void
	 */
	public function api_request() {
		// check capabilities
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			wp_die( __( 'You do not have permission to access this page.', 'cookie-notice' ) );

		// check main nonce
		if ( ! check_ajax_referer( 'cookie-notice-welcome', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'cookie-notice' ) );

		// get request
		$request = isset( $_POST['request'] ) ? sanitize_key( $_POST['request'] ) : '';

		// no valid request?
		if ( ! in_array( $request, [ 'register', 'login', 'configure', 'select_plan', 'payment', 'get_bt_init_token', 'use_license', 'sync_config' ], true ) )
			wp_die( __( 'You do not have permission to access this page.', 'cookie-notice' ) );

		$special_actions = [ 'register', 'login', 'configure', 'payment' ];

		// payment nonce
		if ( $request === 'payment' )
			$nonce = isset( $_POST['cn_payment_nonce'] ) ? sanitize_key( $_POST['cn_payment_nonce'] ) : '';
		// special nonce
		elseif ( in_array( $request, $special_actions, true ) )
			$nonce = isset( $_POST['cn_nonce'] ) ? sanitize_key( $_POST['cn_nonce'] ) : '';

		// check additional nonce
		if ( in_array( $request, $special_actions, true ) && ! wp_verify_nonce( $nonce, 'cn_api_' . $request ) )
			wp_die( __( 'You do not have permission to access this page.', 'cookie-notice' ) );

		$errors = [];
		$response = false;

		// get main instance
		$cn = Cookie_Notice();

		// get site language
		$locale = get_locale();
		$locale_code = explode( '_', $locale );

		// check network
		$network = $cn->is_network_admin();

		// get app token data
		if ( $network )
			$data_token = get_site_transient( 'cookie_notice_app_token' );
		else
			$data_token = get_transient( 'cookie_notice_app_token' );

		$admin_email = ! empty( $data_token->email ) ? $data_token->email : '';
		$app_id = $cn->options['general']['app_id'];

		$params = [];

		switch ( $request ) {
			case 'use_license':
				$subscriptionID = isset( $_POST['subscriptionID'] ) ? (int) $_POST['subscriptionID'] : 0;

				// security: validate subscriptionID is in the session allowlist set during login
				$allowed_subs = $network
					? get_site_transient( 'cookie_notice_app_subscriptions' )
					: get_transient( 'cookie_notice_app_subscriptions' );

				$allowed_ids = is_array( $allowed_subs ) ? array_column( $allowed_subs, 'subscriptionid' ) : [];

				if ( ! in_array( $subscriptionID, array_map( 'intval', $allowed_ids ), true ) ) {
					$response = [ 'error' => esc_html__( 'Invalid subscription.', 'cookie-notice' ) ];
					break;
				}

				$result = $this->request(
					'assign_subscription',
					[
						'AppID'				=> $app_id,
						'subscriptionID'	=> $subscriptionID
					]
				);

				// errors?
				if ( ! empty( $result->message ) ) {
					$response = [ 'error' => $result->message ];
					break;
				}

				// update WP subscription tier to 'pro' (mirrors the payment case)
				$status_data = $cn->defaults['data'];

				if ( $network ) {
					$status_data = get_site_option( 'cookie_notice_status', $status_data );
					$status_data['subscription'] = 'pro';

					// get activation timestamp
					$timestamp = $cn->get_cc_activation_datetime();

					// update activation timestamp only for new cookie compliance activations
					$status_data['activation_datetime'] = $timestamp === 0 ? time() : $timestamp;

					update_site_option( 'cookie_notice_status', $status_data );
				} else {
					$status_data = get_option( 'cookie_notice_status', $status_data );
					$status_data['subscription'] = 'pro';

					// get activation timestamp
					$timestamp = $cn->get_cc_activation_datetime();

					// update activation timestamp only for new cookie compliance activations
					$status_data['activation_datetime'] = $timestamp === 0 ? time() : $timestamp;

					update_option( 'cookie_notice_status', $status_data );
				}

				// License assignment (use_license): do not CLEAR setup_wizard_complete on
				// existing sites — that would send already-configured domains back to
				// FirstRunSetup and make them appear as Free on reload (#1893).
				//
				// For brand-new domains the option was never written, so the wizard would
				// fire unnecessarily for existing subscribers assigning a new slot.
				// Set the flag only if it hasn't been set before — new domain case.
				if ( $network ) {
					if ( ! get_site_option( 'cookie_notice_setup_wizard_complete', false ) ) {
						update_site_option( 'cookie_notice_setup_wizard_complete', true );
					}
				} else {
					if ( ! get_option( 'cookie_notice_setup_wizard_complete', false ) ) {
						update_option( 'cookie_notice_setup_wizard_complete', true );
					}
				}

				$response = $result;

				break;

			case 'get_bt_init_token':
				$result = $this->request( 'get_token' );

				// is token available?
				if ( ! empty( $result->token ) )
					$response = [ 'token' => $result->token ];
				break;

			case 'payment':
				$error = [ 'error' => esc_html__( 'Unexpected error occurred. Please try again later.', 'cookie-notice' ) ];

				// empty data?
				if ( empty( $_POST['payment_nonce'] ) || empty( $_POST['plan'] ) || empty( $_POST['method'] ) ) {
					$response = $error;
					break;
				}

				// validate plan and payment method
				$available_plans = [
					'compliance_monthly_notrial',
					'compliance_monthly_5',
					'compliance_monthly_10',
					'compliance_monthly_20',
					'compliance_yearly_notrial',
					'compliance_yearly_5',
					'compliance_yearly_10',
					'compliance_yearly_20'
				];

				$available_payment_methods = [
					'credit_card',
					'paypal'
				];

				$plan = sanitize_key( $_POST['plan'] );

				if ( ! in_array( $_POST['plan'], $available_plans, true ) )
					$plan = false;

				$method = sanitize_key( $_POST['method'] );

				if ( ! in_array( $_POST['method'], $available_payment_methods, true ) )
					$method = false;

				// valid plan and payment method?
				if ( empty( $plan ) || empty( $method ) ) {
					$response = [ 'error' => esc_html__( 'Empty plan or payment method data.', 'cookie-notice' ) ];
					break;
				}

				$result = $this->request(
					'get_customer',
					[
						'AppID'		=> $app_id,
						'PlanId'	=> $plan
					]
				);

				// user found?
				if ( ! empty( $result->id ) ) {
					$customer = $result;
				// create user
				} else {
					$result = $this->request(
						'create_customer',
						[
							'AppID'					=> $app_id,
							'AdminID'				=> $admin_email, // remove later - AdminID from API response
							'PlanId'				=> $plan,
							'paymentMethodNonce'	=> sanitize_key( $_POST['payment_nonce'] )
						]
					);

					if ( ! empty( $result->success ) )
						$customer = $result->customer;
					else
						$customer = $result;
				}

				// user created/received?
				if ( empty( $customer->id ) ) {
					$response = [ 'error' => esc_html__( 'Unable to create customer data.', 'cookie-notice' ) ];
					break;
				}

				// selected payment method
				$payment_method = false;

				// get payment identifier (email or 4 digits)
				$identifier = isset( $_POST['cn_payment_identifier'] ) ? sanitize_text_field( $_POST['cn_payment_identifier'] ) : '';

				// customer available payment methods
				$payment_methods = ! empty( $customer->paymentMethods ) ? $customer->paymentMethods : [];

				// try to find payment method
				if ( ! empty( $payment_methods ) && is_array( $payment_methods ) ) {
					foreach ( $payment_methods as $pm ) {
						// paypal
						if ( isset( $pm->email ) && $pm->email === $identifier )
							$payment_method = $pm;
						// credit card
						elseif ( isset( $pm->last4 ) && $pm->last4 === $identifier )
							$payment_method = $pm;
					}
				}

				// if payment method was not identified, create it
				if ( ! $payment_method ) {
					$result = $this->request(
						'create_payment_method',
						[
							'AppID'					=> $app_id,
							'paymentMethodNonce'	=> sanitize_key( $_POST['payment_nonce'] )
						]
					);

					// payment method created successfully?
					if ( ! empty( $result->success ) ) {
						$payment_method = $result->paymentMethod;
					} else {
						$response = [ 'error' => esc_html__( 'Unable to create payment mehotd.', 'cookie-notice' ) ];
						break;
					}
				}

				if ( ! isset( $payment_method->token ) ) {
					$response = [ 'error' => esc_html__( 'No payment method token.', 'cookie-notice' ) ];
					break;
				}

				// @todo: check if subscription exists
				$subscription = $this->request(
					'create_subscription',
					[
						'AppID'					=> $app_id,
						'PlanId'				=> $plan,
						'paymentMethodToken'	=> $payment_method->token
					]
				);

				// subscription assigned?
				if ( ! empty( $subscription->error ) ) {
					$response = $subscription->error;
					break;
				}

				$status_data = $cn->defaults['data'];

				// update app status
				if ( $network ) {
					$status_data = get_site_option( 'cookie_notice_status', $status_data );
					$status_data['subscription'] = 'pro';

					// get activation timestamp
					$timestamp = $cn->get_cc_activation_datetime();

					// update activation timestamp only for new cookie compliance activations
					$status_data['activation_datetime'] = $timestamp === 0 ? time() : $timestamp;

					update_site_option( 'cookie_notice_status', $status_data );
				} else {
					$status_data = get_option( 'cookie_notice_status', $status_data );
					$status_data['subscription'] = 'pro';

					// get activation timestamp
					$timestamp = $cn->get_cc_activation_datetime();

					// update activation timestamp only for new cookie compliance activations
					$status_data['activation_datetime'] = $timestamp === 0 ? time() : $timestamp;

					update_option( 'cookie_notice_status', $status_data );
				}

				// Only show FirstRunSetup if the user has never completed it.
				// Free→Pro upgrades: the wizard was already done — don't clear the flag
				// or they'll see FirstRunSetup and remain appearing as Free on reload.
				// New activations (flag not set): leave it unset so the wizard fires.
				// (no-op: delete_option is intentionally removed for the upgrade path)

				$response = $app_id;
				break;

			case 'register':
				// check terms
				$terms = isset( $_POST['terms'] );

				// no terms?
				if ( ! $terms ) {
					$response = [ 'error' => esc_html__( 'Please accept the Terms of Service to proceed.', 'cookie-notice' ) ];
					break;
				}

				// check email
				$email = isset( $_POST['email'] ) ? is_email( $_POST['email'] ) : false;

				// empty email?
				if ( ! $email ) {
					$response = [ 'error' => esc_html__( 'Email is not allowed to be empty.', 'cookie-notice' ) ];
					break;
				}

				// check passwords
				$pass = ! empty( $_POST['pass'] ) ? stripslashes( $_POST['pass'] ) : '';
				$pass2 = ! empty( $_POST['pass2'] ) ? stripslashes( $_POST['pass2'] ) : '';

				// empty password?
				if ( ! $pass || ! is_string( $pass ) ) {
					$response = [ 'error' => esc_html__( 'Password is not allowed to be empty.', 'cookie-notice' ) ];
					break;
				}

				// invalid password?
				if ( preg_match( '/^(?=.*[A-Z])(?=.*\d)[\w !"#$%&\'()*\+,\-.\/:;<=>?@\[\]^\`\{\|\}\~\\\\]{8,}$/', $pass ) !== 1 ) {
					$response = [ 'error' => esc_html__( 'The password contains illegal characters or does not meet the conditions.', 'cookie-notice' ) ];
					break;
				}

				// no match?
				if ( $pass !== $pass2 ) {
					$response = [ 'error' => esc_html__( 'Passwords do not match.', 'cookie-notice' ) ];
					break;
				}

				$params = [
					'AdminID'	=> $email,
					'Password'	=> $pass,
					'Language'	=> ! empty( $_POST['language'] ) ? sanitize_key( $_POST['language'] ) : 'en'
				];

				$response = $this->request( 'register', $params );

				// errors?
				if ( ! empty( $response->error ) )
					break;

				// errors?
				if ( ! empty( $response->message ) ) {
					// normalize duplicate-email to machine-readable key for React recovery UI
					if ( ! empty( $response->i18n_msg ) && strpos( $response->i18n_msg, 'api_account_status_' ) === 0 )
						$response = [ 'error' => 'email_exists' ];
					else
						$response->error = $response->message;

					break;
				}

				// ok, so log in now
				$params = [
					'AdminID'	=> $email,
					'Password'	=> $pass
				];

				$response = $this->request( 'login', $params );

				// errors?
				if ( ! empty( $response->error ) )
					break;

				// errors?
				if ( ! empty( $response->message ) ) {
					$response->error = $response->message;
					break;
				}

				// token in response?
				if ( empty( $response->data->token ) ) {
					$response = [ 'error' => esc_html__( 'Unexpected error occurred. Please try again later.', 'cookie-notice' ) ];
					break;
				}

				// set token
				if ( $network )
					set_site_transient( 'cookie_notice_app_token', $response->data, DAY_IN_SECONDS );
				else
					set_transient( 'cookie_notice_app_token', $response->data, DAY_IN_SECONDS );

				// multisite?
				if ( is_multisite() ) {
					switch_to_blog( 1 );
					$site_title = get_bloginfo( 'name' );
					$site_url = network_site_url();
					$site_description = get_bloginfo( 'description' );
					restore_current_blog();
				} else {
					$site_title = get_bloginfo( 'name' );
					$site_url = get_home_url();
					$site_description = get_bloginfo( 'description' );
				}

				// create new app, no need to check existing
				$params = [
					'DomainName'	=> $site_title,
					'DomainUrl'		=> $site_url
				];

				if ( ! empty( $site_description ) )
					$params['DomainDescription'] = $site_description;

				$response = $this->request( 'app_create', $params );

				// If domain already registered, fetch existing app via list_apps and reuse it.
				if ( ! empty( $response->i18n_msg ) && $response->i18n_msg === 'domain_url_already_exist' ) {
					$list_response = $this->request( 'list_apps' );

					$existing_app = null;
					$site_normalized = strtolower( preg_replace( '/^www\./', '', trim( str_replace( [ 'http://', 'https://' ], '', $site_url ), '/' ) ) );

					if ( ! empty( $list_response->data ) && is_array( $list_response->data ) ) {
						foreach ( $list_response->data as $app ) {
							$app_normalized = strtolower( preg_replace( '/^www\./', '', trim( str_replace( [ 'http://', 'https://' ], '', $app->DomainUrl ?? '' ), '/' ) ) );
							if ( $app_normalized === $site_normalized ) {
								$existing_app = $app;
								break;
							}
						}
					}

					if ( ! empty( $existing_app->AppID ) && ! empty( $existing_app->SecretKey ) ) {
						$response = (object) [ 'data' => $existing_app ];
					} else {
						$response->error = $response->message;
						break;
					}
				}

				// errors?
				if ( ! empty( $response->error ) || ( ! empty( $response->message ) && empty( $response->data ) ) ) {
					if ( empty( $response->error ) ) $response->error = $response->message;
					break;
				}

				// data in response?
				if ( empty( $response->data->AppID ) || empty( $response->data->SecretKey ) ) {
					$response = [ 'error' => esc_html__( 'Unexpected error occurred. Please try again later.', 'cookie-notice' ) ];
					break;
				} else {
					$app_id = $response->data->AppID;
					$secret_key = $response->data->SecretKey;
				}

				// update options: app id and secret key
				$cn->options['general'] = wp_parse_args( [ 'app_id' => $app_id, 'app_key' => $secret_key ], $cn->options['general'] );

				if ( $network ) {
					$cn->options['general']['global_override'] = true;

					update_site_option( 'cookie_notice_options', $cn->options['general'] );

					// get options
					$app_config = get_site_transient( 'cookie_notice_app_quick_config' );
				} else {
					update_option( 'cookie_notice_options', $cn->options['general'] );

					// get options
					$app_config = get_transient( 'cookie_notice_app_quick_config' );
				}

				// create quick config
				$params = ! empty( $app_config ) && is_array( $app_config ) ? $app_config : [];

				// cast to objects
				if ( $params ) {
					$new_params = [];

					foreach ( $params as $key => $array ) {
						$object = new stdClass();

						foreach ( $array as $subkey => $value ) {
							$new_params[$key] = $object;
							$new_params[$key]->{$subkey} = $value;
						}
					}

					$params = $new_params;
				}

				$params['AppID'] = $app_id;

				// @todo When mutliple default languages are supported
				$params['DefaultLanguage'] = 'en';

				if ( ! array_key_exists( 'text', $params ) )
					$params['text'] = new stdClass();

				// add privacy policy url
				$params['text']->privacyPolicyUrl = get_privacy_policy_url();

				// add translations if needed
				if ( $locale_code[0] !== 'en' )
					$params['Languages'] = [ $locale_code[0] ];

				$response = $this->request( 'quick_config', $params );
				$status_data = $cn->defaults['data'];

				if ( $response->status === 200 ) {
					// notify publish app
					$params = [
						'AppID'	=> $app_id
					];

					$response = $this->request( 'notify_app', $params );

					if ( $response->status === 200 ) {
						$response = true;
						$status_data['status'] = 'active';
						$status_data['activation_datetime'] = time();

						// update app status
						if ( $network )
							update_site_option( 'cookie_notice_status', $status_data );
						else
							update_option( 'cookie_notice_status', $status_data );

						// Auto-populate tracker/blocking config from Designer API (#2130).
						$this->get_app_config( $app_id, true, true );
					} else {
						$status_data['status'] = 'pending';

						// update app status
						if ( $network )
							update_site_option( 'cookie_notice_status', $status_data );
						else
							update_option( 'cookie_notice_status', $status_data );

						// errors?
						if ( ! empty( $response->error ) )
							break;

						// errors?
						if ( ! empty( $response->message ) ) {
							$response->error = $response->message;
							break;
						}
					}
				} else {
					$status_data['status'] = 'pending';

					// update app status
					if ( $network )
						update_site_option( 'cookie_notice_status', $status_data );
					else
						update_option( 'cookie_notice_status', $status_data );

					// errors?
					if ( ! empty( $response->error ) ) {
						$response->error = $response->error;
						break;
					}

					// errors?
					if ( ! empty( $response->message ) ) {
						$response->error = $response->message;
						break;
					}
				}

				break;

			case 'login':
				// check email
				$email = isset( $_POST['email'] ) ? is_email( $_POST['email'] ) : false;

				// invalid email?
				if ( ! $email ) {
					$response = [ 'error' => esc_html__( 'Email is not allowed to be empty.', 'cookie-notice' ) ];
					break;
				}

				// check password
				$pass = ! empty( $_POST['pass'] ) ? preg_replace( '/[^\w !"#$%&\'()*\+,\-.\/:;<=>?@\[\]^\`\{\|\}\~\\\\]/', '', $_POST['pass'] ) : '';

				// empty password?
				if ( ! $pass ) {
					$response = [ 'error' => esc_html__( 'Password is not allowed to be empty.', 'cookie-notice' ) ];
					break;
				}

				$params = [
					'AdminID'	=> $email,
					'Password'	=> $pass
				];

				$response = $this->request( $request, $params );

				// errors?
				if ( ! empty( $response->error ) )
					break;

				// errors?
				if ( ! empty( $response->message ) ) {
					$response->error = $response->message;
					break;
				}

				// token in response?
				if ( empty( $response->data->token ) ) {
					$response = [ 'error' => esc_html__( 'Unexpected error occurred. Please try again later.', 'cookie-notice' ) ];
					break;
				}

				// set token
				if ( $network )
					set_site_transient( 'cookie_notice_app_token', $response->data, DAY_IN_SECONDS );
				else
					set_transient( 'cookie_notice_app_token', $response->data, DAY_IN_SECONDS );

				// get apps and check if one for the current domain already exists
				$response = $this->request( 'list_apps', [] );

				// errors?
				if ( ! empty( $response->message ) ) {
					$response->error = $response->message;
					break;
				}

				$apps_list = [];
				$app_exists = false;

				// multisite?
				if ( is_multisite() ) {
					switch_to_blog( 1 );
					$site_title = get_bloginfo( 'name' );
					$site_url = network_site_url();
					$site_description = get_bloginfo( 'description' );
					restore_current_blog();
				} else {
					$site_title = get_bloginfo( 'name' );
					$site_url = get_home_url();
					$site_description = get_bloginfo( 'description' );
				}

				// apps added, check if current one exists
				if ( ! empty( $response->data ) ) {
					$apps_list = (array) $response->data;

					// normalize site URL once before the loop: lowercase, strip protocol, strip www, strip trailing slash
					$site_normalized = strtolower( preg_replace( '/^www\./', '', trim( str_replace( [ 'http://', 'https://' ], '', $site_url ), '/' ) ) );

					foreach ( $apps_list as $index => $app ) {
						$app_domain = strtolower( preg_replace( '/^www\./', '', trim( str_replace( [ 'http://', 'https://' ], '', $app->DomainUrl ), '/' ) ) );

						if ( $app_domain === $site_normalized ) {
							$app_exists = $app;

							break;
						}
					}
				}

				// track whether this domain already existed before login
				$app_was_preexisting = (bool) $app_exists;

				// if no app, create one
				if ( ! $app_exists ) {
					// create new app
					$params = [
						'DomainName'	=> $site_title,
						'DomainUrl'		=> $site_url,
					];

					if ( ! empty( $site_description ) )
						$params['DomainDescription'] = $site_description;

					$response = $this->request( 'app_create', $params );

					// errors?
					if ( ! empty( $response->message ) ) {
						$response->error = $response->message;
						break;
					}

					$app_exists = $response->data;
				}

				// check if we have the valid app data
				if ( empty( $app_exists->AppID ) || empty( $app_exists->SecretKey ) ) {
					$response = [ 'error' => esc_html__( 'Unexpected error occurred. Please try again later.', 'cookie-notice' ) ];
					break;
				}

				// get subscriptions
				$subscriptions = [];

				$params = [
					'AppID' => $app_exists->AppID
				];

				$response = $this->request( 'get_subscriptions', $params );

				// errors?
				if ( ! empty( $response->error ) ) {
					$response->error = $response->error;
					break;
				} else
					$subscriptions = map_deep( (array) $response->data, [ $this, 'sanitize_preserve_bools' ] );

				// set subscriptions data
				if ( $network )
					set_site_transient( 'cookie_notice_app_subscriptions', $subscriptions, DAY_IN_SECONDS );
				else
					set_transient( 'cookie_notice_app_subscriptions', $subscriptions, DAY_IN_SECONDS );

				// determine subscription tier:
				// - pre-existing domain: preserve its current tier from WP options (Designer API is authoritative)
				//   availablelicense reflects account-level available slots, NOT this domain's plan
				//   If WP options were cleared (e.g. reset), fall back to API-side SubscriptionType
				// - brand-new domain: always starts as 'basic' (free by default, payment upgrades it)
				if ( $app_was_preexisting ) {
					$existing_status = $network
						? get_site_option( 'cookie_notice_status', $cn->defaults['data'] )
						: get_option( 'cookie_notice_status', $cn->defaults['data'] );

					$subscription_tier = ! empty( $existing_status['subscription'] ) && in_array( $existing_status['subscription'], [ 'basic', 'pro' ], true )
						? $existing_status['subscription']
						: 'basic';

					// WP options cleared but API knows the domain has a subscription — derive tier from API
					if ( $subscription_tier === 'basic' && ! empty( $app_exists->SubscriptionID ) ) {
						$subscription_tier = 'pro';
					}
				} else {
					$subscription_tier = 'basic';
				}

				// update options: app ID and secret key
				$cn->options['general'] = wp_parse_args( [ 'app_id' => $app_exists->AppID, 'app_key' => $app_exists->SecretKey ], $cn->options['general'] );

				if ( $network ) {
					$cn->options['general']['global_override'] = true;

					update_site_option( 'cookie_notice_options', $cn->options['general'] );
				} else {
					update_option( 'cookie_notice_options', $cn->options['general'] );
				}

				// Pre-existing domains already have their configuration in the Designer API.
				// Only call quick_config for new domains to avoid overwriting existing
				// regulations and settings with defaults.
				$status_data = $cn->defaults['data'];
				$status_data['subscription'] = $subscription_tier;

				if ( ! $app_was_preexisting ) {
					// Apply pre-configure settings from transient (mirrors register flow).
					// Transient is set by the configure wizard when the user hasn't yet connected.
					$app_config = $network ? get_site_transient( 'cookie_notice_app_quick_config' ) : get_transient( 'cookie_notice_app_quick_config' );

					// create quick config
					$params = ! empty( $app_config ) && is_array( $app_config ) ? $app_config : [];

					// cast arrays to objects
					if ( $params ) {
						$new_params = [];

						foreach ( $params as $key => $array ) {
							$object = new stdClass();

							foreach ( $array as $subkey => $value ) {
								$new_params[$key] = $object;
								$new_params[$key]->{$subkey} = $value;
							}
						}

						$params = $new_params;
					}

					$params['AppID']           = $app_exists->AppID;
					$params['DefaultLanguage'] = 'en';

					if ( ! array_key_exists( 'text', $params ) )
						$params['text'] = new stdClass();

					// add privacy policy url
					$params['text']->privacyPolicyUrl = get_privacy_policy_url();

					// add translations if needed
					if ( $locale_code[0] !== 'en' )
						$params['Languages'] = [ $locale_code[0] ];

					$response = $this->request( 'quick_config', $params );

					if ( $response->status !== 200 ) {
						$status_data['status'] = 'pending';

						// update app status
						if ( $network )
							update_site_option( 'cookie_notice_status', $status_data );
						else
							update_option( 'cookie_notice_status', $status_data );

						// errors?
						if ( ! empty( $response->error ) )
							break;

						// errors?
						if ( ! empty( $response->message ) ) {
							$response->error = $response->message;
							break;
						}
					}
				}

				// Notify / activate the app (both new and pre-existing domains)
				$params = [
					'AppID' => $app_exists->AppID
				];

				$response = $this->request( 'notify_app', $params );

				// Idempotent: "App was already active" means the API app record is already Active
				// (StatusID != Inactive). This happens when WP options were cleared but the API-side
				// app persists from a prior login. Treat it as success — the app IS active.
				$notify_already_active = ! empty( $response->message )
					&& strpos( $response->message, 'already active' ) !== false;

				if ( $response->status === 200 || $notify_already_active ) {
					$response = true;
					$status_data['status'] = 'active';

					// get activation timestamp
					$timestamp = $cn->get_cc_activation_datetime();

					// update activation timestamp only for new cookie compliance activations
					$status_data['activation_datetime'] = $timestamp === 0 ? time() : $timestamp;

					// update app status
					if ( $network )
						update_site_option( 'cookie_notice_status', $status_data );
					else
						update_option( 'cookie_notice_status', $status_data );

					// Sync config from Designer API for all domains (new + pre-existing)
					// so the Protection tab shows current tracker data (#2130, #2186).
					$this->get_app_config( $app_exists->AppID, true, true );
				} else {
					$status_data['status'] = 'pending';

					// update app status
					if ( $network )
						update_site_option( 'cookie_notice_status', $status_data );
					else
						update_option( 'cookie_notice_status', $status_data );

					// errors?
					if ( ! empty( $response->error ) )
						break;

					// errors?
					if ( ! empty( $response->message ) ) {
						$response->error = $response->message;
						break;
					}
				}

				// all ok, return subscriptions + fresh nonce
				// A fresh nonce is generated here (after authentication completes) so React
				// can use it for subsequent AJAX calls (e.g. use_license). The welcomeNonce
				// in cnReactData was generated at page load, before login state changed —
				// WP nonces are seeded by user identity so the original may no longer verify.
				$response = (object) [];
				$response->subscriptions = $subscriptions;
				$response->fresh_nonce   = wp_create_nonce( 'cookie-notice-welcome' );

				// Tell React whether this domain already has a subscription assigned
				// so it can skip the LicenseSelectStep for already-subscribed domains.
				$response->app_has_subscription = $app_was_preexisting && ! empty( $app_exists->SubscriptionID );
				break;

			case 'configure':
				$fields = [
					'cn_position',
					'cn_color_primary',
					'cn_color_background',
					'cn_color_border',
					'cn_color_text',
					'cn_color_heading',
					'cn_color_button_text',
					'cn_laws',
					'cn_naming',
					'cn_on_scroll',
					'cn_on_click',
					'cn_ui_blocking',
					'cn_revoke_consent'
				];

				$options = [];

				// loop through potential config form fields
				foreach ( $fields as $field ) {
					switch ( $field ) {
						case 'cn_position':
							// sanitize position
							$position = isset( $_POST[$field] ) ? sanitize_key( $_POST[$field] ) : '';

							// valid position? Only include if explicitly provided — omitting lets
							// patch_by_app deep-merge preserve the portal's current value (#ISSUE-1).
							if ( in_array( $position, [ 'bottom', 'top', 'left', 'right', 'center' ], true ) )
								$options['design']['position'] = $position;
							break;

						case 'cn_color_primary':
							$color = isset( $_POST[$field] ) ? sanitize_hex_color( $_POST[$field] ) : '';

							if ( ! empty( $color ) )
								$options['design']['primaryColor'] = $color;
							break;

						case 'cn_color_background':
							$color = isset( $_POST[$field] ) ? sanitize_hex_color( $_POST[$field] ) : '';

							if ( ! empty( $color ) )
								$options['design']['bannerColor'] = $color;
							break;

						case 'cn_color_border':
							$color = isset( $_POST[$field] ) ? sanitize_hex_color( $_POST[$field] ) : '';

							if ( ! empty( $color ) )
								$options['design']['borderColor'] = $color;
							break;

						case 'cn_color_text':
							$color = isset( $_POST[$field] ) ? sanitize_hex_color( $_POST[$field] ) : '';

							if ( ! empty( $color ) )
								$options['design']['textColor'] = $color;
							break;

						case 'cn_color_heading':
							$color = isset( $_POST[$field] ) ? sanitize_hex_color( $_POST[$field] ) : '';

							if ( ! empty( $color ) )
								$options['design']['headingColor'] = $color;
							break;

						case 'cn_color_button_text':
							$color = isset( $_POST[$field] ) ? sanitize_hex_color( $_POST[$field] ) : '';

							if ( ! empty( $color ) )
								$options['design']['btnTextColor'] = $color;
							break;

						case 'cn_laws':
							$new_options = [];

							// any data?
							if ( ! empty( $_POST[$field] ) && is_array( $_POST[$field] ) ) {
								$options['regulations'] = array_map( 'sanitize_text_field', $_POST[$field] );

								foreach ( $options['regulations'] as $law ) {
									if ( in_array( $law, [ 'gdpr', 'ccpa', 'otherus', 'ukpecr', 'lgpd', 'pipeda', 'popia' ], true ) )
										$new_options[$law] = true;
								}
							}

							$options['regulations'] = $new_options;

							// Persist selected law keys to a dedicated WP option so
							// get_dashboard() and cnReactData can expose them to the
							// Protection tab LAWS card without a Designer API round-trip.
							// (#1897 — LAWS card always showed "No laws selected")
							$saved_law_keys = array_keys( $new_options );
							if ( $network )
								update_site_option( 'cookie_notice_app_regulations', $saved_law_keys );
							else
								update_option( 'cookie_notice_app_regulations', $saved_law_keys );

							// GDPR & others
							$options['config']['privacyPolicyLink'] = true;

							// CCPA & Other US
							if ( array_key_exists( 'ccpa', $options['regulations'] ) || array_key_exists( 'otherus', $options['regulations'] ) )
								$options['config']['dontSellLink'] = true;
							else
								$options['config']['dontSellLink'] = false;

							// Build geolocationRules based on selected laws
							$geolocation_rules = [];

							foreach ( $options['regulations'] as $law => $enabled ) {
								if ( ! $enabled )
									continue;

								// CCPA/otherus: Do Not Sell pattern
								if ( in_array( $law, [ 'ccpa', 'otherus' ], true ) ) {
									$geolocation_rules[] = [
										'name'      => $law,
										'display'   => true,
										'blocking'  => false,
										'revoke'    => false,
										'privacy'   => false,
										'doNotSell' => true,
									];
								} else {
									// GDPR/LGPD/UKPECR/PIPEDA/POPIA: full blocking
									$geolocation_rules[] = [
										'name'      => $law,
										'display'   => true,
										'blocking'  => true,
										'revoke'    => true,
										'privacy'   => true,
										'doNotSell' => false,
									];
								}
							}

							if ( ! empty( $geolocation_rules ) )
								$options['config']['geolocationRules'] = $geolocation_rules;

							// ── Auto-set compliance settings based on selected laws (#2143) ──────────
							//
							// Opt-in consent laws (GDPR, UKPECR, LGPD, POPIA) require prior explicit
							// consent — implied consent via scroll/click/close is not valid under any
							// of these frameworks. Apply the strictest safe defaults when any are selected.
							$opt_in_laws  = [ 'gdpr', 'ukpecr', 'lgpd', 'popia' ];
							$has_opt_in   = ! empty( array_intersect( array_keys( $options['regulations'] ), $opt_in_laws ) );
							$has_ccpa_us  = array_key_exists( 'ccpa', $options['regulations'] ) || array_key_exists( 'otherus', $options['regulations'] );
							$has_pipeda   = array_key_exists( 'pipeda', $options['regulations'] );

							// Designer API config keys — sent via the existing patch_by_app PATCH call below.
							if ( $has_opt_in ) {
								// Scroll/click/close are not valid consent signals under GDPR, UKPECR, LGPD, POPIA.
								$options['config']['onScroll']      = false;
								$options['config']['onClick']       = false;
								// onClose: net-new key — no cn_on_close handler exists; written directly to config.
								$options['config']['onClose']       = false;
								$options['config']['revokeConsent'] = true;
							}

							// GDPR only: cookie walls (uiBlocking) are non-compliant per EDPB guidance.
							if ( array_key_exists( 'gdpr', $options['regulations'] ) ) {
								$options['config']['uiBlocking'] = false;
							}

							// CCPA/OTHERUS: CPRA mandates honoring GPC browser signals.
							// gpcSupportMode is normally set via the consent_raw handler; this sets it
							// directly in the config object which patch_by_app sends as a top-level key.
							if ( $has_ccpa_us ) {
								$options['config']['gpcSupportMode'] = true;
							}

							// PIPEDA: express consent requires ability to revoke — send revokeConsent to Designer API
							// to match the WP-side revoke_cookies=true set below (#2146).
							if ( $has_pipeda ) {
								$options['config']['revokeConsent'] = true;
							}

							// ── WP-side options (cookie_notice_options) ─────────────────────────────
							// The configure case does not normally touch WP options — this is new.
							// Use $cn->options['general'] (already loaded + merged with defaults)
							// to avoid clobbering multi_array_merge'd sub-keys.
							if ( $has_opt_in || $has_ccpa_us || $has_pipeda ) {
								$wp_options = $cn->options['general'];

								if ( $has_opt_in ) {
									// Disable implied consent toggles; enable refuse + revoke + policy link.
									$wp_options['on_scroll']      = false;
									$wp_options['on_click']       = false;
									$wp_options['refuse_opt']     = true;
									$wp_options['revoke_cookies'] = true;
									$wp_options['see_more']       = true;
									// Cap cookie expiry to max allowed: 12–13 months (GDPR/UKPECR EDPB guidance).
									$wp_options['time']           = 'year';
									$wp_options['time_rejected']  = '6months';
								} elseif ( $has_ccpa_us || $has_pipeda ) {
									// Opt-out / express-consent laws: revoke + privacy link minimum.
									$wp_options['revoke_cookies'] = true;
									$wp_options['see_more']       = true;
									// PIPEDA also requires a refuse option (express consent implies ability to decline).
									if ( $has_pipeda )
										$wp_options['refuse_opt'] = true;
								}

								if ( $network )
									update_site_option( 'cookie_notice_options', $wp_options );
								else
									update_option( 'cookie_notice_options', $wp_options );
							}
							// ── End auto-set compliance settings (#2143) ─────────────────────────

							break;

						case 'cn_naming':
							if ( ! isset( $_POST[$field] ) )
								break;

							$naming = (int) $_POST[$field];
							$naming = in_array( $naming, [ 1, 2, 3 ] ) ? $naming : 1;

							// english only for now
							$level_names = [
								1 => [
									1 => 'Private',
									2 => 'Balanced',
									3 => 'Personalized'
								],
								2 => [
									1 => 'Silver',
									2 => 'Gold',
									3 => 'Platinum'
								],
								3 => [
									1 => 'Reject All',
									2 => 'Accept Some',
									3 => 'Accept All'
								]
							];

							$options['text'] = [
								'levelNameText_1'	=> $level_names[$naming][1],
								'levelNameText_2'	=> $level_names[$naming][2],
								'levelNameText_3'	=> $level_names[$naming][3]
							];
							break;

						case 'cn_on_scroll':
							if ( isset( $_POST[$field] ) )
								$options['config']['onScroll'] = true;
							break;

						case 'cn_on_click':
							if ( isset( $_POST[$field] ) )
								$options['config']['onClick'] = true;
							break;

						case 'cn_ui_blocking':
							if ( isset( $_POST[$field] ) )
								$options['config']['uiBlocking'] = true;
							break;
						
						case 'cn_revoke_consent':
							$options['config']['revokeConsent'] = isset( $_POST[$field] );
							break;
					}
				}

				// Normalise regulations: move into config with explicit false for
				// every deselected law.  Both patch_by_app (mergeWith deep-merge)
				// and quick_config (dto.config?.regulations) read it from config.
				// Top-level regulations is kept in the quick schema for backward
				// compat with legacy callers, but new code only sends via config.
				$all_laws = [ 'gdpr', 'ccpa', 'otherus', 'ukpecr', 'lgpd', 'pipeda', 'popia' ];
				$selected = isset( $options['regulations'] ) ? $options['regulations'] : [];
				$full_regs = [];
				foreach ( $all_laws as $law ) {
					$full_regs[ $law ] = ! empty( $selected[ $law ] );
				}
				$options['config']['regulations'] = $full_regs;
				unset( $options['regulations'] );

				// set options
				if ( $network )
					set_site_transient( 'cookie_notice_app_quick_config', $options, DAY_IN_SECONDS );
				else
					set_transient( 'cookie_notice_app_quick_config', $options, DAY_IN_SECONDS );

				// For connected apps: PATCH the Designer API immediately (#1913 — #1917).
				// The transient is retained for the register/login initial-creation path.
				// DevMode mock IDs are skipped — get_write_request_type() returns 'devmode'.
				if ( ! empty( $app_id ) ) {
					$write_type = $this->get_write_request_type( $app_id );

					if ( $write_type !== 'devmode' ) {
						// Cast transient arrays to stdClass objects for JSON encoding.
						$patch_params = [ 'AppID' => $app_id ];

						foreach ( $options as $key => $value ) {
							if ( is_array( $value ) ) {
								$obj = new stdClass();
								foreach ( $value as $sub_key => $sub_val ) {
									$obj->{$sub_key} = $sub_val;
								}
								$patch_params[ $key ] = $obj;
							} else {
								$patch_params[ $key ] = $value;
							}
						}

						$patch_result = $this->request( 'patch_by_app', $patch_params );

						// Design record not yet created — fall back to quick_config to seed it.
						// The API returns { i18n_msg: 'user_design_update_id_not_found', status: 400 } (HTTP 200)
						// when no record exists, so check i18n_msg — not statusCode/404.
						// Also restore DefaultLanguage which patch_by_app doesn't accept but quick_config requires.
						if ( is_object( $patch_result ) && isset( $patch_result->i18n_msg ) && $patch_result->i18n_msg === 'user_design_update_id_not_found' ) {
							$patch_params['DefaultLanguage'] = 'en';
							$patch_result = $this->request( 'quick_config', $patch_params );
						}

						// #2160: Surface API errors back to the caller
						$api_error = '';
						if ( is_object( $patch_result ) && isset( $patch_result->error ) ) {
							$api_error = $patch_result->error;
						} elseif ( is_array( $patch_result ) && isset( $patch_result['error'] ) ) {
							$api_error = $patch_result['error'];
						}

						if ( ! empty( $api_error ) ) {
							$response = [ 'error' => __( 'Your laws were saved locally but could not be applied to your live site. Please try again or visit the portal.', 'cookie-notice' ), 'apiSync' => false ];
							break;
						}

						// Pull confirmed state from portal — portal is SoT.
						// Updates cookie_notice_app_blocking, cookie_notice_app_regulations,
						// cookie_notice_app_design, cookie_notice_status.
						// Do NOT assign return value to $response — configure success
						// intentionally returns $response = false (initial value).
						// LawSelectorPanel checks only for json.error; false has none.
						$this->get_app_config( $app_id, true, true );
					}
				}

				break;

			case 'select_plan':
				break;

			case 'sync_config':
				// force update configuration from Designer API
				$status_data = $this->get_app_config( $app_id, true, true );

				// use global_override-aware check for data operations (not is_network_admin)
				$network_options = $cn->is_network_options();

				// get the blocking data with timestamp
				if ( $network_options )
					$blocking = get_site_option( 'cookie_notice_app_blocking', [] );
				else
					$blocking = get_option( 'cookie_notice_app_blocking', [] );

				// debug: include blocking data in response when debug mode is enabled
				$debug = $cn->options['general']['debug_mode'] ? [
					'app_id' => $app_id,
					'status_data' => $status_data,
					'blocking' => $blocking,
					'providers_count' => ! empty( $blocking['providers'] ) ? count( $blocking['providers'] ) : 0,
					'patterns_count' => ! empty( $blocking['patterns'] ) ? count( $blocking['patterns'] ) : 0,
				] : null;

				// check if sync was successful
				if ( ! empty( $status_data ) && is_array( $status_data ) && ! empty( $status_data['status'] ) && $status_data['status'] === 'active' ) {
					// set cache purge transient to force widget to refresh
					if ( $network_options )
						set_site_transient( 'cookie_notice_config_update', time(), DAY_IN_SECONDS );
					else
						set_transient( 'cookie_notice_config_update', time(), DAY_IN_SECONDS );

					$response = [
						'success' => true,
						'message' => esc_html__( 'Configuration synced successfully.', 'cookie-notice' ),
						'timestamp' => ! empty( $blocking['lastUpdated'] ) ? $blocking['lastUpdated'] : ''
					];
				} else {
					$response = [
						'error' => esc_html__( 'Failed to sync configuration. Please check your app ID and try again.', 'cookie-notice' )
					];
				}

				if ( $debug )
					$response['debug'] = $debug;
				break;
		}

		echo wp_json_encode( $response );
		exit;
	}

	/**
	 * Callback for map_deep that leaves booleans (and null) untouched.
	 * sanitize_text_field casts non-strings to string first, which turns
	 * true → "1" and false → "", corrupting BannerConfigJSON booleans like
	 * gpcSupportMode on every get_app_config() round-trip. Use this callback
	 * whenever the decoded payload contains real booleans we need to preserve.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize_preserve_bools( $value ) {
		if ( is_bool( $value ) || is_null( $value ) ) {
			return $value;
		}
		return sanitize_text_field( $value );
	}

	/**
	 * API request.
	 *
	 * @param string $request The requested action.
	 * @param array $params Parameters for the API action.
	 * @return string|array
	 */
	private function request( $request = '', $params = [] ) {
		// get main instance
		$cn = Cookie_Notice();

		// request arguments
		$api_args = [
			'timeout'	=> 60,
			'headers'	=> [
				'x-api-key'	=> $cn->get_api_key()
			]
		];

		// request parameters
		$api_params = [];

		// whether data should be send in json
		$json = false;

		// whether application id is required
		$require_app_id = false;

		// is it network admin area
		$network = $cn->is_network_admin();

		// get app token data
		if ( $network )
			$data_token = get_site_transient( 'cookie_notice_app_token' );
		else
			$data_token = get_transient( 'cookie_notice_app_token' );

		// check api token
		$api_token = ! empty( $data_token->token ) ? $data_token->token : '';

		switch ( $request ) {
			case 'register':
				$api_url = $cn->get_url( 'account_api', '/api/account/account/registration' );
				$api_args['method'] = 'POST';
				break;

			case 'login':
				$api_url = $cn->get_url( 'account_api', '/api/account/account/login' );
				$api_args['method'] = 'POST';
				break;

			case 'list_apps':
				$api_url = $cn->get_url( 'account_api', '/api/account/app/list' );
				$api_args['method'] = 'GET';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization' => 'Bearer ' . $api_token
					]
				);
				break;

			case 'app_create':
				$api_url = $cn->get_url( 'account_api', '/api/account/app/add' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization' => 'Bearer ' . $api_token
					]
				);
				break;

			// DEV ONLY: Delete an app record from the Account API by AppID.
			// Used by dev_reset() (CN_DEV_MODE only) so test runs don't orphan app slots.
			// Requires a Bearer token (obtained via login) to pass the auth middleware.
			case 'app_delete':
				$api_url = $cn->get_url( 'account_api', '/api/account/app/delete' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization' => 'Bearer ' . $api_token
					]
				);
				break;

			case 'get_analytics':
				$require_app_id = true;
				$api_url = $cn->get_url( 'transactional_api', '/api/transactional/analytics/analytics-data' );
				$api_args['method'] = 'GET';

				$diff_data = $cn->settings->get_analytics_app_data();

				if ( ! empty( $diff_data ) ) {
					$app_data = [
						'app-id'			=> $diff_data['id'],
						'app-secret-key'	=> $diff_data['key']
					];
				} else {
					$app_data = [
						'app-id'			=> $cn->options['general']['app_id'],
						'app-secret-key'	=> $cn->options['general']['app_key']
					];
				}

				$api_args['headers'] = array_merge( $api_args['headers'], $app_data );
				break;

			case 'get_cookie_consent_logs':
				$require_app_id = true;
				$api_url = $cn->get_url( 'transactional_api', '/api/transactional/analytics/consent-logs' );
				$api_args['method'] = 'POST';
				$api_args['headers']['app-id'] = $cn->options['general']['app_id'];
				$api_args['headers']['app-secret-key'] = $cn->options['general']['app_key'];
				break;

			case 'get_privacy_consent_logs':
				$require_app_id = true;
				$api_url = $cn->get_url( 'transactional_api', '/api/transactional/privacy/consent-logs' );
				$api_args['method'] = 'POST';
				$api_args['headers']['app-id'] = $cn->options['general']['app_id'];
				$api_args['headers']['app-secret-key'] = $cn->options['general']['app_key'];
				break;

			case 'get_config':
				$require_app_id = true;
				$api_url = $cn->get_url( 'designer_api', '/api/designer/user-design-live' );
				$api_args['method'] = 'GET';
				break;

			case 'quick_config':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'designer_api', '/api/designer/user-design/quick' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;

			// PATCH /user-design/by-app/:AppID — partial update for connected apps (#1913).
			// AppID is pulled from params and placed in the URL; remaining params go in the body.
			// Used by react_update_design(), react_apply_template(), react_apply_languages(),
			// and the configure (laws/wizard) flow — replaces quick_config for existing apps.
			case 'patch_by_app':
				$patch_app_id = isset( $params['AppID'] ) ? $params['AppID'] : '';
				unset( $params['AppID'] );  // AppID goes in URL, not body.
				$require_app_id = false;    // We handle the empty-check ourselves below.
				$json = true;
				$api_url = $cn->get_url( 'designer_api', '/api/designer/user-design/by-app/' . rawurlencode( $patch_app_id ) );
				$api_args['method'] = 'PATCH';

				// Designer API /by-app endpoint uses authenticateApp middleware —
				// expects app-id + app-secret-key headers (NOT Bearer token).
				// Both are stored in WP options from the register/login flow.
				$network = $cn->is_network_admin();
				$patch_app_key = $network
					? $cn->network_options['general']['app_key']
					: $cn->options['general']['app_key'];

				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'app-id'         => $patch_app_id,
						'app-secret-key' => $patch_app_key,
						'Content-Type'   => 'application/json; charset=utf-8',
					]
				);
				break;

			case 'notify_app':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'account_api', '/api/account/app/notifyAppPublished' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;

			// braintree init token
			case 'get_token':
				$api_url = $cn->get_url( 'account_api', '/api/account/braintree' );
				$api_args['method'] = 'GET';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization' => 'Bearer ' . $api_token
					]
				);
				break;

			// braintree get customer
			case 'get_customer':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'account_api', '/api/account/braintree/findcustomer' );
				$api_args['method'] = 'POST';
				$api_args['data_format'] = 'body';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;

			// braintree create customer in vault
			case 'create_customer':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'account_api', '/api/account/braintree/createcustomer' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;

			// braintree get subscriptions
			case 'get_subscriptions':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'account_api', '/api/account/braintree/subscriptionlists' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;

			// braintree create subscription
			case 'create_subscription':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'account_api', '/api/account/braintree/createsubscription' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;

			// braintree assign subscription
			case 'assign_subscription':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'account_api', '/api/account/braintree/assignsubscription' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;

			// braintree create payment method
			case 'create_payment_method':
				$require_app_id = true;
				$json = true;
				$api_url = $cn->get_url( 'account_api', '/api/account/braintree/createpaymentmethod' );
				$api_args['method'] = 'POST';
				$api_args['headers'] = array_merge(
					$api_args['headers'],
					[
						'Authorization'	=> 'Bearer ' . $api_token,
						'Content-Type'	=> 'application/json; charset=utf-8'
					]
				);
				break;
		}

		// check if app id is required to avoid unneeded requests
		if ( $require_app_id ) {
			$empty_app_id = false;

			// check app id
			if ( array_key_exists( 'AppID', $params ) && is_string( $params['AppID'] ) ) {
				$app_id = trim( $params['AppID'] );

				// empty app id?
				if ( $app_id === '' )
					$empty_app_id = true;
			} else
				$empty_app_id = true;

			if ( $empty_app_id )
				return [ 'error' => esc_html__( '"AppID" is not allowed to be empty.', 'cookie-notice' ) ];
		}

		if ( ! empty( $params ) && is_array( $params ) ) {
			foreach ( $params as $key => $param ) {
				if ( is_object( $param ) )
					$api_params[$key] = $param;
				elseif ( is_array( $param ) )
					$api_params[$key] = array_map( 'sanitize_text_field', $param );
				elseif ( $key === 'Password' && ( $request === 'register' || $request === 'login' ) )
					$api_params[$key] = preg_replace( '/[^\w !"#$%&\'()*\+,\-.\/:;<=>?@\[\]^\`\{\|\}\~\\\\]/', '', $param );
				else
					$api_params[$key] = sanitize_text_field( $param );
			}

			// for GET requests, append params as query string instead of body
			if ( $api_args['method'] === 'GET' )
				$api_url = add_query_arg( $api_params, $api_url );
			elseif ( $json )
				$api_args['body'] = wp_json_encode( $api_params );
			else
				$api_args['body'] = $api_params;
		}

		$response = wp_remote_request( $api_url, $api_args );

		if ( is_wp_error( $response ) )
			$result = [ 'error' => $response->get_error_message() ];
		else {
			$content_type = wp_remote_retrieve_header( $response, 'Content-Type' );

			// html response, means error
			if ( $content_type == 'text/html' )
				$result = [ 'error' => esc_html__( 'Unexpected error occurred. Please try again later.', 'cookie-notice' ) ];
			else {
				$result = wp_remote_retrieve_body( $response );

				// detect json or array
				$result = is_array( $result ) ? $result : json_decode( $result );
			}
		}

		return $result;
	}

	/**
	 * Check whether WP Cron needs to add new task.
	 *
	 * @return void
	 */
	public function check_cron() {
		// get main instance
		$cn = Cookie_Notice();

		if ( is_multisite() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] ) {
			$app_id = $cn->network_options['general']['app_id'];
			$app_key = $cn->network_options['general']['app_key'];
		} else {
			$app_id = $cn->options['general']['app_id'];
			$app_key = $cn->options['general']['app_key'];
		}

		// compliance active only
		if ( $app_id !== '' && $app_key !== '' ) {
			if ( $cn->get_status() === 'active' )
				$recurrence = 'daily';
			else
				$recurrence = 'hourly';

			// set schedule if needed
			if ( ! wp_next_scheduled( 'cookie_notice_get_app_analytics' ) )
				wp_schedule_event( time(), 'hourly', 'cookie_notice_get_app_analytics' );

			// set schedule if needed
			if ( ! wp_next_scheduled( 'cookie_notice_get_app_config' ) )
				wp_schedule_event( time(), $recurrence, 'cookie_notice_get_app_config' );
		} else {
			// remove schedule if needed
			if ( wp_next_scheduled( 'cookie_notice_get_app_analytics' ) )
				wp_clear_scheduled_hook( 'cookie_notice_get_app_analytics' );

			// remove schedule if needed
			if ( wp_next_scheduled( 'cookie_notice_get_app_config' ) )
				wp_clear_scheduled_hook( 'cookie_notice_get_app_config' );
		}
	}

	/**
	 * Get privacy consent logs.
	 *
	 * @return string|array
	 */
	public function get_privacy_consent_logs() {
		// get main instance
		$cn = Cookie_Notice();

		// threshold-gated: free users cannot access consent logs when over 1K visits (#1851)
		if ( $cn->threshold_exceeded() )
			return [];

		// get consent logs for specific date
		$result = $this->request(
			'get_privacy_consent_logs',
			[
				'AppID'			=> $cn->options['general']['app_id'],
				'AppSecretKey'	=> $cn->options['general']['app_key'],
				'Latest'		=> 100
			]
		);

		// message?
		if ( ! empty( $result->message ) )
			$result = $result->message;
		// error?
		elseif ( ! empty( $result->error ) )
			$result = $result->error;
		// valid data?
		elseif ( ! empty( $result->data ) )
			$result = $result->data;
		else
			$result = [];
		return $result;
	}

	/**
	 * Get cookie consent logs.
	 *
	 * @param string $date     Start date (Y-m-d).
	 * @param string $end_date Optional end date (Y-m-d). Omit for single-day query.
	 *
	 * @return string|array
	 */
	public function get_cookie_consent_logs( $date, $end_date = '' ) {
		// get main instance
		$cn = Cookie_Notice();

		// threshold-gated: free users cannot access consent logs when over 1K visits (#1851)
		if ( $cn->threshold_exceeded() )
			return [];

		$params = [
			'AppID'			=> $cn->options['general']['app_id'],
			'AppSecretKey'	=> $cn->options['general']['app_key'],
			'Date'			=> $date,
		];

		if ( $end_date !== '' && $end_date !== $date ) {
			$params['EndDate'] = $end_date;
		}

		$result = $this->request( 'get_cookie_consent_logs', $params );

		// message?
		if ( ! empty( $result->message ) )
			$result = $result->message;
		// error?
		elseif ( ! empty( $result->error ) )
			$result = $result->error;
		// valid data?
		elseif ( ! empty( $result->data ) )
			$result = $result->data;
		else
			$result = [];

		return $result;
	}

	/**
	 * Get app analytics.
	 *
	 * @param string $app_id
	 * @param bool $force_update
	 * @param bool $force_action
	 *
	 * @return void
	 */
	public function get_app_analytics( $app_id = '', $force_update = false, $force_action = true ) {
		// get main instance
		$cn = Cookie_Notice();

		$allow_one_cron_per_hour = false;

		if ( is_multisite() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] ) {
			if ( empty( $app_id ) )
				$app_id = $cn->network_options['general']['app_id'];

			$network = true;
			$allow_one_cron_per_hour = true;
		} else {
			if ( empty( $app_id ) )
				$app_id = $cn->options['general']['app_id'];

			$network = false;
		}

		// in global override mode allow only one cron per hour
		if ( $allow_one_cron_per_hour && ! $force_update ) {
			$analytics = get_site_option( 'cookie_notice_app_analytics', [] );

			// analytics data?
			if ( ! empty( $analytics ) ) {
				$updated = strtotime( $analytics['lastUpdated'] );

				// last updated less than an hour?
				if ( $updated !== false && current_time( 'timestamp', true ) - $updated < 3600 )
					return;
			}
		}

		$response = $this->request(
			'get_analytics',
			[
				'AppID' => $app_id
			]
		);

		// get analytics
		if ( ! empty( $response->data ) ) {
			$result = map_deep( (array) $response->data, [ $this, 'sanitize_preserve_bools' ] );

			// add time updated
			$result['lastUpdated'] = date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) );

			// get default status data
			$status_data = $cn->defaults['data'];

			// update status
			$status_data['status'] = $cn->get_status();

			// update subscription
			$status_data['subscription'] = $cn->get_subscription();

			// update activation timestamp
			$status_data['activation_datetime'] = $cn->get_cc_activation_datetime();

			if ( $status_data['status'] === 'active' && $status_data['subscription'] === 'basic' ) {
				$threshold = ! empty( $result['cycleUsage']->threshold ) ? (int) $result['cycleUsage']->threshold : 0;
				$visits = ! empty( $result['cycleUsage']->visits ) ? (int) $result['cycleUsage']->visits : 0;

				if ( $visits >= $threshold && $threshold > 0 )
					$status_data['threshold_exceeded'] = true;
			}

			if ( $network ) {
				update_site_option( 'cookie_notice_app_analytics', $result );
				update_site_option( 'cookie_notice_status', $status_data );
			} else {
				update_option( 'cookie_notice_app_analytics', $result, false );
				update_option( 'cookie_notice_status', $status_data, false );
			}

			// get current status data
			$status_data_old = $cn->get_status_data();

			// update status data
			$cn->set_status_data();

			// only when status data changed
			if ( $force_action && $status_data_old !== $status_data ) {
				do_action( 'cn_configuration_updated', 'analytics', [
					'status' => $status_data
				] );
			}
		}
	}

	/**
	 * Get app config.
	 *
	 * @param string $app_id
	 * @param bool $force_update
	 * @param bool $force_action
	 *
	 * @return void|array
	 */
	public function get_app_config( $app_id = '', $force_update = false, $force_action = true ) {
		// get main instance
		$cn = Cookie_Notice();

		$allow_one_cron_per_hour = false;

		if ( is_multisite() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] ) {
			if ( empty( $app_id ) )
				$app_id = $cn->network_options['general']['app_id'];

			$network = true;
			$allow_one_cron_per_hour = true;
		} else {
			if ( empty( $app_id ) )
				$app_id = $cn->options['general']['app_id'];

			$network = false;
		}

		// in global override mode allow only one cron per hour
		if ( $allow_one_cron_per_hour && ! $force_update ) {
			$blocking = get_site_option( 'cookie_notice_app_blocking', [] );

			// analytics data?
			if ( ! empty( $blocking ) ) {
				$updated = strtotime( $blocking['lastUpdated'] );

				// last updated less than an hour?
				if ( $updated !== false && current_time( 'timestamp', true ) - $updated < 3600 )
					return;
			}
		}

		// get config
		$response = $this->request(
			'get_config',
			[
				'AppID' => $app_id
			]
		);

		// debug: log raw Designer API response
		if ( $cn->options['general']['debug_mode'] ) {
			error_log( '[Cookie Notice] get_app_config - AppID: ' . $app_id );
			error_log( '[Cookie Notice] get_app_config - Designer API response: ' . wp_json_encode( $response ) );
		}

		// get status data
		$status_data = $cn->defaults['data'];

		// get config
		if ( ! empty( $response->data ) ) {
			// sanitize data
			foreach ( (array) $response->data as $index => $value ) {
				// custom patterns
				if ( $index === 'DefaultCookieJSON' ) {
					foreach ( $value as $p_index => $pattern ) {
						$pattern->IsCustom = (bool) $pattern->IsCustom;
						$pattern->CookieID = is_int( $pattern->CookieID ) ? $pattern->CookieID : sanitize_text_field( $pattern->CookieID );
						$pattern->CategoryID = (int) $pattern->CategoryID;
						$pattern->ProviderID = is_int( $pattern->ProviderID ) ? $pattern->ProviderID : sanitize_text_field( $pattern->ProviderID );
						$pattern->PatternType = sanitize_text_field( $pattern->PatternType );
						$pattern->PatternFormat = sanitize_text_field( $pattern->PatternFormat );
						$pattern->Pattern = stripslashes( sanitize_text_field( $pattern->Pattern ) );

						// add pattern
						$result_raw[$index][$p_index] = $pattern;
					}
				// custom providers
				} elseif ( $index === 'DefaultProviderJSON' ) {
					foreach ( $value as $p_index => $provider ) {
						$provider->IsCustom = (bool) $provider->IsCustom;
						$provider->CategoryID = (int) $provider->CategoryID;
						$provider->ProviderID = is_int( $provider->ProviderID ) ? $provider->ProviderID : sanitize_text_field( $provider->ProviderID );
						$provider->ProviderURL = stripslashes( sanitize_text_field( $provider->ProviderURL ) );
						$provider->ProviderName = sanitize_text_field( $provider->ProviderName );

						// add provider
						$result_raw[$index][$p_index] = $provider;
					}
				} else
					$result_raw[$index] = map_deep( $value, [ $this, 'sanitize_preserve_bools' ] );
			}

			// set status
			$status_data['status'] = 'active';

			// get activation timestamp
			$timestamp = $cn->get_cc_activation_datetime();

			// update activation timestamp only for new cookie compliance activations
			$status_data['activation_datetime'] = $timestamp === 0 ? time() : $timestamp;

			// check subscription
			if ( ! empty( $result_raw['SubscriptionType'] ) )
				$status_data['subscription'] = $cn->check_subscription( strtolower( $result_raw['SubscriptionType'] ) );

			if ( $status_data['subscription'] === 'basic' ) {
				// get analytics data options
				if ( $network )
					$analytics = get_site_option( 'cookie_notice_app_analytics', [] );
				else
					$analytics = get_option( 'cookie_notice_app_analytics', [] );

				if ( ! empty( $analytics ) ) {
					$threshold = ! empty( $analytics['cycleUsage']->threshold ) ? (int) $analytics['cycleUsage']->threshold : 0;
					$visits = ! empty( $analytics['cycleUsage']->visits ) ? (int) $analytics['cycleUsage']->visits : 0;

					if ( $visits >= $threshold && $threshold > 0 )
						$status_data['threshold_exceeded'] = true;
				}
			}

			// process blocking data
			$result = [
				'categories'				=> ! empty( $result_raw['DefaultCategoryJSON'] ) && is_array( $result_raw['DefaultCategoryJSON'] ) ? $result_raw['DefaultCategoryJSON'] : [],
				'providers'					=> ! empty( $result_raw['DefaultProviderJSON'] ) && is_array( $result_raw['DefaultProviderJSON'] ) ? $result_raw['DefaultProviderJSON'] : [],
				'patterns'					=> ! empty( $result_raw['DefaultCookieJSON'] ) && is_array( $result_raw['DefaultCookieJSON'] ) ? $result_raw['DefaultCookieJSON'] : [],
				'google_consent_default'	=> [],
				'lastUpdated'				=> date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) )
			];

			if ( ! empty( $result_raw['BannerConfigJSON'] ) && is_object( $result_raw['BannerConfigJSON'] ) ) {
				$gcm = isset( $result_raw['BannerConfigJSON']->googleConsentMode ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMode : 0;

				// is google consent mode enabled? (free + threshold-gated: #1851)
				if ( $gcm === 1 && ! $cn->threshold_exceeded() ) {
					$result['google_consent_default']['ad_storage'] = isset( $result_raw['BannerConfigJSON']->googleConsentMapAdStorage ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMapAdStorage : 4;
					$result['google_consent_default']['analytics_storage'] = isset( $result_raw['BannerConfigJSON']->googleConsentMapAnalytics ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMapAnalytics : 3;
					$result['google_consent_default']['functionality_storage'] = isset( $result_raw['BannerConfigJSON']->googleConsentMapFunctionality ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMapFunctionality : 2;
					$result['google_consent_default']['personalization_storage'] = isset( $result_raw['BannerConfigJSON']->googleConsentMapPersonalization ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMapPersonalization : 2;
					$result['google_consent_default']['security_storage'] = isset( $result_raw['BannerConfigJSON']->googleConsentMapSecurity ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMapSecurity : 2;
					$result['google_consent_default']['ad_personalization'] = isset( $result_raw['BannerConfigJSON']->googleConsentMapAdPersonalization ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMapAdPersonalization : 4;
					$result['google_consent_default']['ad_user_data'] = isset( $result_raw['BannerConfigJSON']->googleConsentMapAdUserData ) ? (int) $result_raw['BannerConfigJSON']->googleConsentMapAdUserData : 4;
				}

				$fcm = isset( $result_raw['BannerConfigJSON']->facebookConsentMode ) ? (int) $result_raw['BannerConfigJSON']->facebookConsentMode : 0;

				// is facebook consent mode enabled? (pro-only: #1851)
				if ( $fcm === 1 && $status_data['subscription'] === 'pro' ) {
					$result['facebook_consent_default']['consent'] = isset( $result_raw['BannerConfigJSON']->facebookConsentMapConsent ) ? (int) $result_raw['BannerConfigJSON']->facebookConsentMapConsent : 4;
				}

				$mcm = isset( $result_raw['BannerConfigJSON']->microsoftConsentMode ) ? (int) $result_raw['BannerConfigJSON']->microsoftConsentMode : 0;

				// is microsoft consent mode enabled? (pro-only: #1851)
				if ( $mcm === 1 && $status_data['subscription'] === 'pro' ) {
					$result['microsoft_consent_default']['ad_storage'] = isset( $result_raw['BannerConfigJSON']->microsoftConsentMapAdStorage ) ? (int) $result_raw['BannerConfigJSON']->microsoftConsentMapAdStorage : 4;
					$result['microsoft_consent_default']['analytics_storage'] = isset( $result_raw['BannerConfigJSON']->microsoftConsentMapAnalyticsStorage ) ? (int) $result_raw['BannerConfigJSON']->microsoftConsentMapAnalyticsStorage : 3;
				}

				// Browser signal modes (cherry-picked for backward compat with Protection tab components).
				$result['gpc_support']  = ! empty( $result_raw['BannerConfigJSON']->gpcSupportMode );
				$result['do_not_track'] = ! empty( $result_raw['BannerConfigJSON']->doNotTrackMode );

				// Raw BannerConfigJSON — full behavioral config from Designer API.
				// React admin reads all fields directly from this (camelCase, same keys as API).
				// Eliminates per-field extraction: new Designer API fields are automatically
				// available in the plugin UI after a config pull.
				$result['banner_config'] = json_decode( wp_json_encode( $result_raw['BannerConfigJSON'] ), true );
			}

			if ( $network ) {
				$blocking_data = get_site_option( 'cookie_notice_app_blocking', [] );

				update_site_option( 'cookie_notice_app_blocking', $result );
			} else {
				$blocking_data = get_option( 'cookie_notice_app_blocking', [] );

				update_option( 'cookie_notice_app_blocking', $result, false );
			}

			// Sync regulations from Designer API to local WP option (#2186).
			// Keeps the Protection tab's law card in sync with what the Admin Portal shows.
			if ( ! empty( $result_raw['BannerConfigJSON'] ) && isset( $result_raw['BannerConfigJSON']->regulations ) ) {
				$api_regs = (array) $result_raw['BannerConfigJSON']->regulations;
				$active_reg_keys = array_keys( array_filter( $api_regs ) );

				if ( $network )
					update_site_option( 'cookie_notice_app_regulations', $active_reg_keys );
				else
					update_option( 'cookie_notice_app_regulations', $active_reg_keys );
			}

			// Cache visual design fields from Designer API response.
			// position, bannerColor, primaryColor live in UserDesignJSON (visual design),
			// NOT BannerConfigJSON (behavioral config). Reading from BannerConfigJSON
			// returned empty strings and caused "No template" on cron refresh (#2261).
			if ( ! empty( $result_raw['UserDesignJSON'] ) && is_object( $result_raw['UserDesignJSON'] ) ) {
				$udj = $result_raw['UserDesignJSON'];

				$design = [
					'position'        => isset( $udj->position )        ? (string) $udj->position        : '',
					'displayType'     => isset( $udj->displayType )     ? (string) $udj->displayType     : '',
					'bannerColor'     => isset( $udj->bannerColor )     ? (string) $udj->bannerColor     : '',
					'primaryColor'    => isset( $udj->primaryColor )    ? (string) $udj->primaryColor    : '',
				];

				// Cache consent level labels from DefaultUserTextJSON so the React
				// admin can show the customer-configured names on ConsentStats cards
				// and audit log pills instead of hardcoded Accept/Custom/Reject.
				if ( ! empty( $result_raw['DefaultUserTextJSON'] ) && is_object( $result_raw['DefaultUserTextJSON'] ) ) {
					$utj = $result_raw['DefaultUserTextJSON'];

					$design['levelNameText_1'] = isset( $utj->levelNameText_1 ) ? (string) $utj->levelNameText_1 : '';
					$design['levelNameText_2'] = isset( $utj->levelNameText_2 ) ? (string) $utj->levelNameText_2 : '';
					$design['levelNameText_3'] = isset( $utj->levelNameText_3 ) ? (string) $utj->levelNameText_3 : '';
				}

				if ( $network )
					update_site_option( 'cookie_notice_app_design', $design );
				else
					update_option( 'cookie_notice_app_design', $design, false );
			}

			// debug: log what gets stored
			if ( $cn->options['general']['debug_mode'] ) {
				error_log( '[Cookie Notice] get_app_config - Stored providers count: ' . count( $result['providers'] ) );
				error_log( '[Cookie Notice] get_app_config - Stored patterns count: ' . count( $result['patterns'] ) );
				error_log( '[Cookie Notice] get_app_config - Stored blocking data: ' . wp_json_encode( $result ) );
			}
		} else {
			if ( $cn->options['general']['debug_mode'] ) {
				error_log( '[Cookie Notice] get_app_config - No data in response. Error: ' . ( ! empty( $response->error ) ? $response->error : 'unknown' ) );
			}

			if ( ! empty( $response->error ) ) {
				if ( $response->error == 'App is not published yet' )
					$status_data['status'] = 'pending';
				else
					$status_data['status'] = '';
			}
		}

		if ( $network )
			update_site_option( 'cookie_notice_status', $status_data );
		else
			update_option( 'cookie_notice_status', $status_data, false );

		// get current status data
		$status_data_old = $cn->get_status_data();

		// update status data
		$cn->set_status_data();

		// check blocking data
		if ( isset( $blocking_data, $result ) ) {
			// do not compare dates
			unset( $blocking_data['lastUpdated'] );
			unset( $result['lastUpdated'] );

			// simple comparing, objects inside
			$blocking_data_updated = $blocking_data != $result;
		} else
			$blocking_data_updated = false;

		// only when status data or blocking data changed
		if ( $force_action && ( $status_data_old !== $status_data || $blocking_data_updated ) ) {
			do_action( 'cn_configuration_updated', 'config', [
				'status'	=> $status_data,
				'blocking'	=> empty( $result ) ? [] : $result
			] );
		}

		return $status_data;
	}

	/**
	 * AJAX: Apply a design template preset via quick_config.
	 *
	 * POST fields: template (minimal|standard|bold|popup|panel|compact)
	 * Syncs full design schema to portal + saves position/displayType to WP options.
	 *
	 * @return void
	 */
	public function react_apply_template() {
		$this->verify_react_request();

		$cn = Cookie_Notice();
		$app_id = $cn->options['general']['app_id'];

		if ( empty( $app_id ) ) {
			wp_send_json_error( [ 'error' => 'No app connected.' ] );
		}

		$template = isset( $_POST['template'] ) ? sanitize_key( $_POST['template'] ) : '';

		// Full design presets — position/color/typography synced to portal.
		// Colors match TemplatePresets.jsx PRESETS array.
		$presets = [
			'minimal' => [
				'position'         => 'left',
				'displayType'      => 'floating',
				'bannerColor'      => '#f0f0f0',
				'primaryColor'     => '#20c19e',
				'textColor'        => '#434f58',
				'headingColor'     => '#434f58',
				'btnTextColor'     => '#ffffff',
				'btnBorderRadius'  => '25px',
				'animation'        => 'fade',
				'bannerOpacity'    => 0.97,
				'revokePosition'   => 'bottom-left',
				'showBulletPoints' => true,
			],
			'standard' => [
				'position'         => 'bottom',
				'displayType'      => 'floating',
				'bannerColor'      => '#2d3436',
				'primaryColor'     => '#20c19e',
				'textColor'        => '#ffffff',
				'headingColor'     => '#ffffff',
				'btnTextColor'     => '#ffffff',
				'btnBorderRadius'  => '25px',
				'animation'        => 'fade',
				'bannerOpacity'    => 0.97,
				'revokePosition'   => 'bottom-left',
				'showBulletPoints' => true,
			],
			'bold' => [
				'position'         => 'top',
				'displayType'      => 'fixed',
				'bannerColor'      => '#1a1a2e',
				'primaryColor'     => '#20c19e',
				'textColor'        => '#ffffff',
				'headingColor'     => '#ffffff',
				'btnTextColor'     => '#ffffff',
				'btnBorderRadius'  => '6px',
				'animation'        => 'slide',
				'bannerOpacity'    => 1.0,
				'revokePosition'   => 'bottom-right',
				'showBulletPoints' => false,
			],
			'popup' => [
				'position'         => 'center',
				'displayType'      => 'floating',
				'bannerColor'      => '#2c3e50',
				'primaryColor'     => '#20c19e',
				'textColor'        => '#ffffff',
				'headingColor'     => '#ffffff',
				'btnTextColor'     => '#ffffff',
				'btnBorderRadius'  => '25px',
				'animation'        => 'fade',
				'bannerOpacity'    => 0.97,
				'revokePosition'   => 'bottom-left',
				'showBulletPoints' => true,
			],
			'panel' => [
				'position'         => 'right',
				'displayType'      => 'floating',
				'bannerColor'      => '#34495e',
				'primaryColor'     => '#3498db',
				'textColor'        => '#ffffff',
				'headingColor'     => '#ffffff',
				'btnTextColor'     => '#ffffff',
				'btnBorderRadius'  => '25px',
				'animation'        => 'fade',
				'bannerOpacity'    => 0.97,
				'revokePosition'   => 'bottom-left',
				'showBulletPoints' => true,
			],
			'compact' => [
				'position'         => 'top',
				'displayType'      => 'floating',
				'bannerColor'      => '#1a1a2e',
				'primaryColor'     => '#e67e22',
				'textColor'        => '#ffffff',
				'headingColor'     => '#ffffff',
				'btnTextColor'     => '#ffffff',
				'btnBorderRadius'  => '6px',
				'animation'        => 'slide',
				'bannerOpacity'    => 1.0,
				'revokePosition'   => 'bottom-right',
				'showBulletPoints' => false,
			],
		];

		if ( ! isset( $presets[ $template ] ) ) {
			wp_send_json_error( [ 'error' => 'Invalid template name.' ] );
		}

		$preset = $presets[ $template ];

		// Build design object for quick_config (exclude displayType — WP option, not portal field)
		$design = new stdClass();

		foreach ( $preset as $key => $value ) {
			if ( $key === 'displayType' )
				continue;

			$design->{$key} = $value;
		}

		$params = [
			'AppID'           => $app_id,
			'DefaultLanguage' => 'en',
			'text'            => (object) [ 'privacyPolicyUrl' => get_privacy_policy_url() ],
			'design'          => $design,
		];

		$write_type = $this->get_write_request_type( $app_id );

		// PATCH /by-app endpoint does not accept DefaultLanguage -- strip it.
		if ( $write_type === 'patch_by_app' ) {
			unset( $params['DefaultLanguage'] );
		}
		// DevMode mock ID — return synthetic success so the UI can be tested without a real API.
		if ( $write_type === 'devmode' ) {
			$network = $cn->is_network_admin();

			// Merge visual design fields (position, displayType, colors) from preset.
			// #2265: API-owned fields write to cookie_notice_app_design only — never cookie_notice_options.
			$existing_design = $network
				? get_site_option( 'cookie_notice_app_design', [] )
				: get_option( 'cookie_notice_app_design', [] );

			$updated_design = array_merge( $existing_design, [
				'position'     => $preset['position'],
				'displayType'  => $preset['displayType'],
				'bannerColor'  => $preset['bannerColor'],
				'primaryColor' => $preset['primaryColor'],
			] );

			if ( $network ) {
				update_site_option( 'cookie_notice_app_design', $updated_design );
			} else {
				update_option( 'cookie_notice_app_design', $updated_design, false );
			}

			wp_send_json_success( [ 'status' => 200, 'template' => $template, 'dev_mode' => true ] );
			return;
		}

		$result = $this->request( $write_type, $params );

		// Design record not yet created — fall back to quick_config to seed it.
		// The API returns { i18n_msg: 'user_design_update_id_not_found', status: 400 } (HTTP 200)
		// when no record exists, so check i18n_msg — not statusCode/404.
		// Also restore DefaultLanguage which patch_by_app doesn't accept but quick_config requires.
		if ( is_object( $result ) && isset( $result->i18n_msg ) && $result->i18n_msg === 'user_design_update_id_not_found' ) {
			$params['DefaultLanguage'] = 'en';
			$result = $this->request( 'quick_config', $params );
		}

		if ( is_object( $result ) && isset( $result->status ) && $result->status === 200 ) {
			// #2265: API-owned fields write to cookie_notice_app_design only — never cookie_notice_options.
			$network = $cn->is_network_admin();

			// Merge visual design fields (position, displayType, colors) from preset.
			$existing_design = $network
				? get_site_option( 'cookie_notice_app_design', [] )
				: get_option( 'cookie_notice_app_design', [] );

			$updated_design = array_merge( $existing_design, [
				'position'     => $preset['position'],
				'displayType'  => $preset['displayType'],
				'bannerColor'  => $preset['bannerColor'],
				'primaryColor' => $preset['primaryColor'],
			] );

			if ( $network ) {
				update_site_option( 'cookie_notice_app_design', $updated_design );
			} else {
				update_option( 'cookie_notice_app_design', $updated_design, false );
			}

			// Pull confirmed state from portal — makes portal unambiguous SoT.
			// Updates cookie_notice_app_blocking, cookie_notice_app_regulations,
			// cookie_notice_app_design, cookie_notice_status.
			// Fires cn_configuration_updated → clears page caches (WP Rocket etc).
			// Does NOT set cookie_notice_config_update transient (widget CDN cache).
			$this->get_app_config( $app_id, true, true );

			// Re-assert preset design values after get_app_config() — the portal may return
			// empty position/color fields (BannerConfigJSON cherry-picks) which would
			// overwrite our just-saved preset and cause matchTemplate() to return null
			// on the next page load ("No template" false negative — #2261).
			// This write is authoritative: we know what template was just applied.
			if ( $network )
				update_site_option( 'cookie_notice_app_design', $updated_design );
			else
				update_option( 'cookie_notice_app_design', $updated_design, false );

			wp_send_json_success( [ 'status' => 200, 'template' => $template ] );
		} else {
			$error = 'Template apply failed.';

			if ( is_array( $result ) && ! empty( $result['error'] ) )
				$error = $result['error'];
			elseif ( is_object( $result ) && ! empty( $result->message ) )
				$error = $result->message;
			elseif ( is_object( $result ) && ! empty( $result->error ) )
				$error = $result->error;
			elseif ( is_object( $result ) && ! empty( $result->i18n_msg ) )
				$error = 'API error: ' . $result->i18n_msg;
			elseif ( $result === null )
				$error = 'No response from API — check connection.';

			wp_send_json_error( [ 'error' => $error, 'apiSync' => false ] );
		}
	}

	/**
	 * Verify React admin AJAX request (nonce + capability).
	 *
	 * @return void Dies on failure.
	 */
	private function verify_react_request() {
		check_ajax_referer( 'cn_react_nonce', 'nonce' );

		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			wp_send_json_error( [ 'error' => 'Insufficient permissions.' ] );
	}

	/**
	 * Determine whether a write should use PATCH /by-app/:AppID (connected app, existing design)
	 * or fall back to quick_config (initial creation).
	 *
	 * Returns 'patch_by_app' for connected FREE/PRO users with a real AppID.
	 * Returns 'quick_config' for BASIC/unconnected, or DevMode mock IDs (cn-dev-*).
	 * DevMode mock IDs bypass the API entirely — AJAX returns synthetic success so the UI
	 * can be tested without hitting a real API.
	 *
	 * @param string $app_id The AppID being written.
	 * @return string 'patch_by_app' | 'quick_config' | 'devmode'
	 */
	private function get_write_request_type( $app_id ) {
		// DevMode mock IDs — never hit the real API.
		if ( defined( 'CN_DEV_MODE' ) && CN_DEV_MODE && strpos( $app_id, 'cn-dev-' ) === 0 )
			return 'devmode';

		// Connected app with a real ID — use the PATCH update endpoint.
		return 'patch_by_app';
	}

	/**
	 * AJAX: Push design updates to Designer API via quick_config.
	 *
	 * POST fields: design[position], design[displayType], design[bannerColor], etc.
	 * Requires connected app (app_id in WP options).
	 *
	 * @return void
	 */
	public function react_update_design() {
		$this->verify_react_request();

		$cn = Cookie_Notice();
		$app_id = $cn->options['general']['app_id'];

		if ( empty( $app_id ) ) {
			wp_send_json_error( [ 'error' => 'No app connected.' ] );
		}

		$design_raw  = isset( $_POST['design'] )        && is_array( $_POST['design'] )        ? $_POST['design']        : [];
		$config_raw  = isset( $_POST['config'] )        && is_array( $_POST['config'] )        ? $_POST['config']        : [];
		$consent_raw = isset( $_POST['consentConfig'] ) && is_array( $_POST['consentConfig'] ) ? $_POST['consentConfig'] : [];

		if ( empty( $design_raw ) && empty( $config_raw ) && empty( $consent_raw ) ) {
			wp_send_json_error( [ 'error' => 'No update data provided.' ] );
		}

		// Allowed design fields with sanitization
		$allowed_fields = [
			'position'             => 'sanitize_key',
			'displayType'          => 'sanitize_key',
			'bannerColor'          => 'sanitize_hex_color',
			'primaryColor'         => 'sanitize_hex_color',
			'textColor'            => 'sanitize_hex_color',
			'headingColor'         => 'sanitize_hex_color',
			'btnTextColor'         => 'sanitize_hex_color',
			'btnBorderRadius'      => 'sanitize_text_field',
			'animation'            => 'sanitize_key',
			'bannerOpacity'        => 'sanitize_text_field',
			'revokePosition'       => 'sanitize_key',
			'showBulletPoints'     => null, // boolean
		];
		// Note: googleConsentMode / facebookConsentMode / microsoftConsentMode are NOT design fields.
		// The PATCH /by-app endpoint rejects them in design{}. They belong in config{} as booleans.
		// They are handled below alongside gpcSupportMode / doNotTrackMode.

		$design = new stdClass();

		foreach ( $allowed_fields as $field => $sanitizer ) {
			if ( ! array_key_exists( $field, $design_raw ) )
				continue;

			if ( $field === 'showBulletPoints' ) {
				$design->{$field} = filter_var( $design_raw[ $field ], FILTER_VALIDATE_BOOLEAN );
			} elseif ( $sanitizer ) {
				$design->{$field} = call_user_func( $sanitizer, $design_raw[ $field ] );
			}
		}

		// Validate position — translate 'popup' → 'center' (portal label vs CSS class)
		if ( isset( $design->position ) ) {
			if ( $design->position === 'popup' ) {
				$design->position = 'center';
			} elseif ( ! in_array( $design->position, [ 'bottom', 'top', 'left', 'right', 'center' ], true ) ) {
				$design->position = 'bottom';
			}
		}

		// Validate displayType
		if ( isset( $design->displayType ) && ! in_array( $design->displayType, [ 'floating', 'fixed' ], true ) )
			$design->displayType = 'floating';

		// Validate animation
		if ( isset( $design->animation ) && ! in_array( $design->animation, [ 'fade', 'slide', 'none' ], true ) )
			$design->animation = 'fade';

		// Validate bannerOpacity
		if ( isset( $design->bannerOpacity ) ) {
			$opacity = (float) $design->bannerOpacity;
			$design->bannerOpacity = max( 0.0, min( 1.0, $opacity ) );
		}

		// Build config object from allowed behavior fields
		$config_allowed = [ 'revokeConsent', 'revokeMethod', 'onScroll', 'onScrollOffset', 'onClick', 'reloading' ];
		$config = new stdClass();
		foreach ( $config_allowed as $f ) {
			if ( isset( $config_raw[ $f ] ) )
				$config->$f = sanitize_text_field( $config_raw[ $f ] );
		}

		// Merge consent mode fields into config{} — the Designer API stores all of these
		// under BannerConfigJSON, not a separate consentConfig key. The PATCH /by-app endpoint
		// rejects a top-level consentConfig key entirely.
		//
		// Field name mapping (JS POST key → API config key):
		//   gpcSupport  → gpcSupportMode  (boolean)
		//   doNotTrack  → doNotTrackMode  (boolean)
		//   All GCM/Facebook/Microsoft map fields keep their names, as integers.
		// Map/level fields: must be integers (0–4).
		$consent_int_fields = [
			'googleConsentMapAdStorage', 'googleConsentMapAnalytics', 'googleConsentMapFunctionality',
			'googleConsentMapPersonalization', 'googleConsentMapSecurity', 'googleConsentMapAdPersonalization',
			'googleConsentMapAdUserData', 'facebookConsentMapConsent', 'microsoftConsentMapAdStorage',
			'microsoftConsentMapAnalyticsStorage',
		];
		foreach ( $consent_int_fields as $f ) {
			if ( isset( $consent_raw[ $f ] ) )
				$config->$f = (int) $consent_raw[ $f ];
		}
		// IMPORTANT: Toggle fields MUST use (bool)(int) — NOT bare (int).
		// wp_json_encode((int)1) = JSON 1 (integer) — API silently drops it.
		// wp_json_encode((bool)true) = JSON true (boolean) — API persists it.
		// See commit 8ff1432 for the original fix. Do NOT revert to (int).
		$consent_bool_fields = [ 'microsoftConsentModePixie', 'microsoftConsentModeClarity' ];
		foreach ( $consent_bool_fields as $f ) {
			if ( isset( $consent_raw[ $f ] ) )
				$config->$f = (bool) (int) $consent_raw[ $f ];
		}
		// gpcSupport → gpcSupportMode (bool)
		if ( isset( $consent_raw['gpcSupport'] ) )
			$config->gpcSupportMode = (bool) (int) $consent_raw['gpcSupport'];
		// doNotTrack → doNotTrackMode (bool)
		if ( isset( $consent_raw['doNotTrack'] ) )
			$config->doNotTrackMode = (bool) (int) $consent_raw['doNotTrack'];
		// Consent mode flags (google/facebook/microsoft) — sent in design_raw from the React POST
		// but must be placed in config{} as booleans. The PATCH /by-app endpoint rejects them in design{}.
		foreach ( [ 'googleConsentMode', 'facebookConsentMode', 'microsoftConsentMode' ] as $mode_field ) {
			if ( isset( $design_raw[ $mode_field ] ) )
				$config->$mode_field = (bool) (int) $design_raw[ $mode_field ];
		}

		// Build params — only include non-empty objects
		$params = [
			'AppID'           => $app_id,
			'DefaultLanguage' => 'en',
			'text'            => (object) [ 'privacyPolicyUrl' => get_privacy_policy_url() ],
		];
		if ( ! empty( (array) $design ) )
			$params['design'] = $design;
		if ( ! empty( (array) $config ) )
			$params['config'] = $config;

		$write_type = $this->get_write_request_type( $app_id );

		// PATCH /by-app endpoint does not accept DefaultLanguage -- strip it.
		if ( $write_type === 'patch_by_app' ) {
			unset( $params['DefaultLanguage'] );
		}
		// DevMode mock ID — return synthetic success so the UI can be tested without a real API.
		if ( $write_type === 'devmode' ) {
			wp_send_json_success( [ 'status' => 200, 'dev_mode' => true ] );
			return;
		}

		$result = $this->request( $write_type, $params );

		// Temporary diagnostic — log raw API response for consent mode debugging.
		error_log( 'react_update_design API result: ' . var_export( $result, true ) );

		// Design record not yet created — fall back to quick_config to seed it.
		// The API returns { i18n_msg: 'user_design_update_id_not_found', status: 400 } (HTTP 200)
		// when no record exists, so check i18n_msg — not statusCode/404.
		// Also restore DefaultLanguage which patch_by_app doesn't accept but quick_config requires.
		if ( is_object( $result ) && isset( $result->i18n_msg ) && $result->i18n_msg === 'user_design_update_id_not_found' ) {
			$params['DefaultLanguage'] = 'en';
			$result = $this->request( 'quick_config', $params );
		}

		if ( is_object( $result ) && isset( $result->status ) && $result->status === 200 ) {
			// Pull confirmed state from portal — makes portal unambiguous SoT.
			// Updates cookie_notice_app_blocking (GCM/GPC signal maps),
			// fires cn_configuration_updated → clears page caches.
			// Does NOT set cookie_notice_config_update transient (widget CDN cache).
			$this->get_app_config( $app_id, true, true );

			wp_send_json_success( [ 'status' => 200 ] );
		} else {
			$error = 'Design update failed.';

			if ( is_array( $result ) && ! empty( $result['error'] ) )
				$error = $result['error'];
			elseif ( is_object( $result ) && ! empty( $result->message ) )
				$error = $result->message;
			elseif ( is_object( $result ) && ! empty( $result->error ) )
				$error = $result->error;
			elseif ( is_object( $result ) && ! empty( $result->i18n_msg ) )
				$error = 'API error: ' . $result->i18n_msg;
			elseif ( $result === null )
				$error = 'No response from API — check connection.';

			wp_send_json_error( [ 'error' => $error, 'apiSync' => false ] );
		}
	}

	/**
	 * AJAX: Apply languages via quick_config.
	 *
	 * POST fields: languages[] (array of language codes)
	 * Server-side enforcement of free plan 1-language limit.
	 *
	 * @return void
	 */
	public function react_apply_languages() {
		$this->verify_react_request();

		$cn = Cookie_Notice();
		$app_id = $cn->options['general']['app_id'];

		if ( empty( $app_id ) ) {
			wp_send_json_error( [ 'error' => 'No app connected.' ] );
		}

		$languages_raw = isset( $_POST['languages'] ) && is_array( $_POST['languages'] ) ? $_POST['languages'] : [];

		// Sanitize and validate language codes (2-letter ISO 639-1)
		$allowed_languages = [ 'fr', 'es', 'de', 'it', 'el', 'nl', 'pt', 'pl', 'sv' ];
		$languages = [];

		foreach ( $languages_raw as $lang ) {
			$lang = sanitize_key( $lang );

			if ( in_array( $lang, $allowed_languages, true ) )
				$languages[] = $lang;
		}

		// Free plan: enforce 1-language limit
		$subscription = $cn->get_subscription();
		$status = $cn->get_status();
		$is_free = ( $status === 'active' && $subscription === 'basic' );

		if ( $is_free && count( $languages ) > 1 )
			$languages = array_slice( $languages, 0, 1 );

		$params = [
			'AppID'           => $app_id,
			'DefaultLanguage' => 'en',
			'languages'       => $languages,
		];

		$write_type = $this->get_write_request_type( $app_id );

		// PATCH /by-app endpoint does not accept DefaultLanguage -- strip it.
		if ( $write_type === 'patch_by_app' ) {
			unset( $params['DefaultLanguage'] );
		}
		// DevMode mock ID — return synthetic success so the UI can be tested without a real API.
		if ( $write_type === 'devmode' ) {
			wp_send_json_success( [ 'status' => 200, 'languages' => $languages, 'dev_mode' => true ] );
			return;
		}

		$result = $this->request( $write_type, $params );

		// Design record not yet created — fall back to quick_config to seed it.
		// The API returns { i18n_msg: 'user_design_update_id_not_found', status: 400 } (HTTP 200)
		// when no record exists, so check i18n_msg — not statusCode/404.
		// Also restore DefaultLanguage which patch_by_app doesn't accept but quick_config requires.
		if ( is_object( $result ) && isset( $result->i18n_msg ) && $result->i18n_msg === 'user_design_update_id_not_found' ) {
			$params['DefaultLanguage'] = 'en';
			$result = $this->request( 'quick_config', $params );
		}

		if ( is_object( $result ) && isset( $result->status ) && $result->status === 200 ) {
			// Persist applied languages locally so the dashboard can reflect the real count.
			$network = is_multisite() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'];
			if ( $network )
				update_site_option( 'cookie_notice_app_languages', $languages );
			else
				update_option( 'cookie_notice_app_languages', $languages, false );

			wp_send_json_success( [ 'status' => 200, 'languages' => $languages ] );
		} else {
			$error = 'Language update failed.';

			if ( is_array( $result ) && ! empty( $result['error'] ) )
				$error = $result['error'];
			elseif ( is_object( $result ) && ! empty( $result->message ) )
				$error = $result->message;

			wp_send_json_error( [ 'error' => $error, 'apiSync' => false ] );
		}
	}
}
