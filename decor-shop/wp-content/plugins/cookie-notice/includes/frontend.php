<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Frontend class.
 *
 * @class Cookie_Notice_Frontend
 */
class Cookie_Notice_Frontend {

	private $compliance = false;
	private $matched_handles = [];

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// general actions
		add_action( 'init', [ $this, 'early_init' ], 9 );
		add_action( 'wp', [ $this, 'init' ] );
		add_action( 'wp_head', [ $this, 'wp_print_header_scripts' ] );
		add_action( 'wp_print_footer_scripts', [ $this, 'wp_print_footer_scripts' ] );

		// compliance actions
		add_action( 'wp_head', [ $this, 'add_dns_prefetch' ], -1 );
		add_action( 'wp_head', [ $this, 'add_cookie_compliance' ], 0 );
		add_action( 'login_head', [ $this, 'add_cookie_compliance' ], 0 );

		// notice actions
		add_action( 'wp_footer', [ $this, 'add_cookie_notice' ], 1000 );
		add_action( 'login_footer', [ $this, 'add_cookie_notice' ], 1000 );
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_notice_scripts' ] );
		add_action( 'login_enqueue_scripts', [ $this, 'wp_enqueue_notice_scripts' ] );

		// filters
		add_filter( 'body_class', [ $this, 'change_body_class' ] );
		add_filter( 'cn_is_bot', [ $this, 'wp_cache_check' ] );
	}

	/**
	 * Early initialization.
	 *
	 * @return void
	 */
	public function early_init() {
		// get main instance
		$cn = Cookie_Notice();

		// set compliance status
		$this->compliance = ( $cn->get_status() === 'active' );

		// cookie compliance initialization
		if ( $this->compliance ) {
			// amp compatibility
			if ( $cn->options['general']['amp_support'] && cn_is_plugin_active( 'amp' ) )
				include_once( COOKIE_NOTICE_PATH . 'includes/modules/amp/amp.php' );

			// excluded script handles — stamp data-hu-category="1" so the widget never blocks them
			if ( $cn->options['general']['app_blocking'] && ! empty( $cn->options['general']['excluded_handles'] ) ) {
				add_filter( 'script_loader_tag', [ $this, 'exclude_handles_from_blocking' ], 10, 2 );

				if ( $cn->options['general']['debug_mode'] )
					add_action( 'wp_footer', [ $this, 'debug_excluded_handles' ], 999 );
			}
		}
	}

	/**
	 * Stamp excluded script handles with data-hu-category="1" (Essential).
	 *
	 * @param string $tag    Full <script> tag HTML.
	 * @param string $handle WordPress script handle.
	 * @return string
	 */
	public function exclude_handles_from_blocking( $tag, $handle ) {
		$excluded = Cookie_Notice()->options['general']['excluded_handles'];

		if ( in_array( $handle, $excluded, true ) && strpos( $tag, 'data-hu-category' ) === false ) {
			$tag = str_replace( ' src=', ' data-hu-category="1" src=', $tag );
			$this->matched_handles[] = $handle;
		}

		return $tag;
	}

	/**
	 * Output debug console.warn lines listing matched / unmatched excluded handles.
	 *
	 * @return void
	 */
	public function debug_excluded_handles() {
		$configured = Cookie_Notice()->options['general']['excluded_handles'];
		$matched    = $this->matched_handles;
		$unmatched  = array_values( array_diff( $configured, $matched ) );

		echo '<script>' .
			'console.warn("CC Banner: Excluded script handles — stamped (' . count( $matched ) . '): " + ' . wp_json_encode( $matched ) . ');' .
			( ! empty( $unmatched ) ? 'console.warn("CC Banner: Excluded script handles — not found on this page (' . count( $unmatched ) . '): " + ' . wp_json_encode( $unmatched ) . ');' : '' ) .
		'</script>' . "\n";
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() )
			return;

		// purge cache
		if (
			isset( $_GET['hu_purge_cache'], $_GET['_wpnonce'] )
			&& current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'cn-purge-cache' )
		) {
			$this->purge_cache();
		}

		// get main instance
		$cn = Cookie_Notice();

		// compatibility fixes
		if ( $this->compliance ) {
			// is blocking active?
			if ( $cn->options['general']['app_blocking'] ) {
				// contact form 7 compatibility
				if ( cn_is_plugin_active( 'contactform7', 'captcha' ) )
					include_once( COOKIE_NOTICE_PATH . 'includes/modules/contact-form-7/contact-form-7.php' );
			}
		}
	}

	/**
	 * Whether banner is allowed to display.
	 *
	 * @param array $args
	 * @return bool
	 */
	public function maybe_display_banner( $args = [] ) {
		$defaults = [
			'skip_amp' => false
		];

		if ( is_array( $args ) )
			$args = wp_parse_args( $args, $defaults );
		else
			$args = $defaults;

		// get main instance
		$cn = Cookie_Notice();

		// is cookie compliance active?
		if ( $this->compliance ) {
			// elementor compatibility, needed early for is_preview_mode
			if ( cn_is_plugin_active( 'elementor' ) )
				include_once( COOKIE_NOTICE_PATH . 'includes/modules/elementor/elementor.php' );

			// divi builder compatibility
			if ( cn_is_plugin_active( 'divi', 'theme' ) )
				include_once( COOKIE_NOTICE_PATH . 'includes/modules/divi/divi.php' );
		}

		// is it preview mode?
		if ( $this->is_preview_mode() )
			return false;

		// is bot detection enabled and it's a bot?
		if ( $cn->options['general']['bot_detection'] && apply_filters( 'cn_is_bot', $cn->bot_detect->is_crawler() ) )
			return false;

		// check amp
		if ( ! $args['skip_amp'] ) {
			if ( $cn->options['general']['amp_support'] && cn_is_plugin_active( 'amp' ) && function_exists( 'amp_is_request' ) && amp_is_request() )
				return false;
		}

		// final check for conditional display
		return $this->check_conditions();
	}

	/**
	* Check if WP_CACHE is active.
	 *
	 * @return bool
	 */
	public function wp_cache_check( $result ) {
		if ( defined( 'WP_CACHE' ) && WP_CACHE === true )
			$result = false;

		return $result;
	}

	/**
	 * Whether preview mode is active.
	 *
	 * @return bool
	 */
	public function is_preview_mode() {
		return isset( $_GET['cn_preview_mode'] ) || is_preview() || is_customize_preview() || defined( 'IFRAME_REQUEST' ) || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) || isset( $_GET[ 'fl_builder' ] ) || apply_filters( 'cn_is_preview_mode', false );
	}

	/**
	 * Check whether banner should be displayed based on specified conditions.
	 *
	 * @return bool
	 */
	public function check_conditions() {
		// get main instance
		$cn = Cookie_Notice();

		if ( ! $cn->options['general']['conditional_active'] )
			return true;

		// get conditions
		$rules = $cn->options['general']['conditional_rules'];

		// set access type
		$access_type = $cn->options['general']['conditional_display'] === 'show';

		// get object
		$object = get_queried_object();

		// no rules?
		if ( empty( $rules ) )
			$final_access = true;
		else {
			// check the rules
			foreach( $rules as $index => $group ) {
				$give_group_access = true;

				foreach ( $group as $rule ) {
					$give_rule_access = false;

					switch ( $rule['param'] ) {
						case 'page_type':
							if ( ( $rule['operator'] === 'equal' && $rule['value'] === 'front' && is_front_page() ) || ( $rule['operator'] === 'not_equal' && $rule['value'] === 'front' && ! is_front_page() ) || ( $rule['operator'] === 'equal' && $rule['value'] === 'home' && is_home() ) || ( $rule['operator'] === 'not_equal' && $rule['value'] === 'home' && ! is_home() ) || ( $rule['operator'] === 'equal' && $rule['value'] === 'login' && $this->is_login() ) || ( $rule['operator'] === 'not_equal' && $rule['value'] === 'login' && ! $this->is_login() ) )
								$give_rule_access = true;
							break;

						case 'page':
							if ( ( $rule['operator'] === 'equal' && ! empty( $object ) && is_a( $object, 'WP_Post' ) && property_exists( $object, 'ID' ) && is_page( $object->ID ) && (int) $object->ID === (int) $rule['value'] ) || ( $rule['operator'] === 'not_equal' && ( empty( $object ) || ! is_page() || ( is_page() && ! empty( $object ) && is_a( $object, 'WP_Post' ) && property_exists( $object, 'ID' ) && $object->ID !== (int) $rule['value'] ) ) ) )
								$give_rule_access = true;
							break;

						case 'post_type':
							if ( ( $rule['operator'] === 'equal' && is_singular( $rule['value'] ) ) || ( $rule['operator'] === 'not_equal' && ! is_singular( $rule['value'] ) ) )
								$give_rule_access = true;
							break;

						case 'post_type_archive':
							if ( ( $rule['operator'] === 'equal' && is_post_type_archive( $rule['value'] ) ) || ( $rule['operator'] === 'not_equal' && ! is_post_type_archive( $rule['value'] ) ) )
								$give_rule_access = true;
							break;

						case 'user_type':
							if ( ( $rule['operator'] === 'equal' && $rule['value'] === 'logged_in' && is_user_logged_in() ) || ( $rule['operator'] === 'equal' && $rule['value'] === 'guest' && ! is_user_logged_in() ) || ( $rule['operator'] === 'not_equal' && $rule['value'] === 'logged_in' && ! is_user_logged_in() ) || ( $rule['operator'] === 'not_equal' && $rule['value'] === 'guest' && is_user_logged_in() ) )
								$give_rule_access = true;
							break;

						case 'taxonomy_archive':
							// check value
							if ( strpos( $rule['value'], '|' ) !== false ) {
								// explode it
								$values = explode( '|', $rule['value'] );

								// 2 chunks?
								if ( count( $values ) === 2 ) {
									$term_id = (int) $values[0];

									if ( $values[1] === 'category' && ( ( $rule['operator'] === 'equal' && is_category( $term_id ) ) || ( $rule['operator'] === 'not_equal' && ! is_category( $term_id ) ) ) )
										$give_rule_access = true;
									elseif ( $values[1] === 'post_tag' && ( ( $rule['operator'] === 'equal' && is_tag( $term_id ) ) || ( $rule['operator'] === 'not_equal' && ! is_tag( $term_id ) ) ) )
										$give_rule_access = true;
									elseif ( ( $rule['operator'] === 'equal' && is_tax( $values[1], $term_id ) ) || ( $rule['operator'] === 'not_equal' && ! is_tax( $values[1], $term_id ) ) )
										$give_rule_access = true;
								}
							}
							break;
					}

					// condition failed?
					if ( ! $give_rule_access ) {
						// group failed
						$give_group_access = false;

						// finish group checking
						break;
					}
				}

				// whole group successful?
				if ( $give_group_access ) {
					// set final access
					$final_access = $access_type;

					// finish rules checking
					break;
				} else
					$final_access = ! $access_type;
			}
		}

		return (bool) apply_filters( 'cn_conditional_display', $final_access, $object );
	}

	/**
	 * Determine whether the current request is for the login screen.
	 *
	 * @return bool
	 */
	public function is_login() {
		return ( function_exists( 'is_login' ) ? is_login() : ( stripos( wp_login_url(), $_SERVER['SCRIPT_NAME'] ) !== false ) );
	}

	/**
	 * Get Cookie Compliance options.
	 *
	 * @return array
	 */
	public function get_cc_options() {
		// get main instance
		$cn = Cookie_Notice();

		// get site language
		$locale = get_locale();
		$locale_code = explode( '_', $locale );

		// exceptions, norwegian
		if ( is_array( $locale_code ) && in_array( $locale_code[0], [ 'nb', 'nn' ] ) )
			$locale_code[0] = 'no';

		// get active sources
		$sources = $cn->privacy_consent->get_active_sources();

		// prepare huOptions
		$options = [
			'appID'				=> $cn->options['general']['app_id'],
			'currentLanguage'	=> $locale_code[0],
			'blocking'			=> ! is_user_logged_in() ? $cn->options['general']['app_blocking'] : false,
			'globalCookie'		=> is_multisite() && $cn->options['general']['global_cookie'] && is_subdomain_install(),
			'isAdmin'			=> current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ),
			'privacyConsent'	=> ! empty( $sources )
		];

		// any active source?
		if ( ! empty( $sources ) )
			$options['forms'] = [];

		// filter options
		$options = apply_filters( 'cn_cookie_compliance_args', $options );

		// get config timestamp
		if ( is_multisite() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] )
			$timestamp = (int) get_site_transient( 'cookie_notice_config_update' );
		else
			$timestamp = (int) get_transient( 'cookie_notice_config_update' );

		// update config?
		if ( $timestamp > 0 ) {
			$options['cachePurge'] = true;
			$options['cacheTimestamp'] = $timestamp;
		}

		// debug mode
		if ( $cn->options['general']['debug_mode'] )
			$options['debugMode'] = true;

		// blocking data (custom patterns, providers, consent mode defaults)
		// always include in huOptions so the widget has the full configuration;
		// the huOptions.blocking flag controls whether scripts are actually blocked
		if ( $cn->is_network_options() )
			$blocking = get_site_option( 'cookie_notice_app_blocking' );
		else
			$blocking = get_option( 'cookie_notice_app_blocking' );

		if ( ! empty( $blocking ) && is_array( $blocking ) ) {
			$options['customProviders'] = ! empty( $blocking['providers'] ) && is_array( $blocking['providers'] ) ? $blocking['providers'] : [];
			$options['customPatterns'] = ! empty( $blocking['patterns'] ) && is_array( $blocking['patterns'] ) ? $blocking['patterns'] : [];

			// google consent mode default categories
			if ( ! empty( $blocking['google_consent_default'] ) && is_array( $blocking['google_consent_default'] ) ) {
				$gcd = [];

				foreach ( $blocking['google_consent_default'] as $storage => $category ) {
					if ( in_array( $storage, ['ad_storage', 'analytics_storage', 'functionality_storage', 'personalization_storage', 'security_storage', 'ad_personalization', 'ad_user_data'], true ) )
						$gcd[$storage] = (int) $category;
				}

				if ( ! empty( $gcd ) )
					$options['googleConsentDefault'] = $gcd;
			}

			// facebook consent mode default categories
			if ( ! empty( $blocking['facebook_consent_default'] ) && is_array( $blocking['facebook_consent_default'] ) ) {
				$fcd = [];

				foreach ( $blocking['facebook_consent_default'] as $storage => $category ) {
					if ( in_array( $storage, ['consent'], true ) )
						$fcd[$storage] = (int) $category;
				}

				if ( ! empty( $fcd ) )
					$options['facebookConsentDefault'] = $fcd;
			}

			// microsoft consent mode default categories
			if ( ! empty( $blocking['microsoft_consent_default'] ) && is_array( $blocking['microsoft_consent_default'] ) ) {
				$mcd = [];

				foreach ( $blocking['microsoft_consent_default'] as $storage => $category ) {
					if ( in_array( $storage, ['ad_storage', 'analytics_storage'], true ) )
						$mcd[$storage] = (int) $category;
				}

				if ( ! empty( $mcd ) )
					$options['microsoftConsentDefault'] = $mcd;
			}
		}

		if ( isset( $_GET['cn_preview'] ) && $_GET['cn_preview'] === '1' && current_user_can( 'manage_options' ) ) {
			$options['forceShow'] = true;
		}

		return $options;
	}

	/**
	 * Get Cookie Compliance output.
	 *
	 * @param array $options
	 * @return string
	 */
	public function get_cc_output( $options ) {
		$output = '
		<!-- Cookie Compliance -->
		<script type="text/javascript">var huOptions = ' . wp_json_encode( $options, JSON_UNESCAPED_SLASHES ) . ';</script>
		<script type="text/javascript" src="' . esc_url( ( is_ssl() ? 'https:' : 'http:' ) . Cookie_Notice()->get_url( 'widget' ) ) . '"></script>';

		return apply_filters( 'cn_cookie_compliance_output', $output, $options );
	}

	/**
	 * Add DNS Prefetch.
	 *
	 * @return void
	 */
	public function add_dns_prefetch() {
		if ( ! $this->compliance )
			return;

		// is banner allowed to display?
		if ( ! $this->maybe_display_banner() )
			return;

		// Derive prefetch host from widget URL so CN_APP_WIDGET_URL overrides are honoured.
		$widget_url = Cookie_Notice()->get_url( 'widget' );
		$prefetch_host = '//' . wp_parse_url( 'https:' . $widget_url, PHP_URL_HOST );
		echo '<link rel="dns-prefetch" href="' . esc_attr( $prefetch_host ) . '" />';
	}

	/**
	 * Run Cookie Compliance.
	 *
	 * @return void
	 */
	public function add_cookie_compliance() {
		// skip modal login iframe
		if ( current_filter() === 'login_head' && ! empty( $_REQUEST['interim-login'] ) )
			return;

		// allow only for compliance
		if ( ! $this->compliance )
			return;

		// is banner allowed to display?
		if ( ! $this->maybe_display_banner() )
			return;

		// get options
		$options = $this->get_cc_options();

		// display output
		echo $this->get_cc_output( $options );
	}

	/**
	 * Cookie notice output.
	 *
	 * @return void
	 */
	public function add_cookie_notice() {
		// skip modal login iframe
		if ( current_filter() === 'login_footer' && ! empty( $_REQUEST['interim-login'] ) )
			return;

		if ( $this->compliance )
			return;

		// is banner allowed to display?
		if ( ! $this->maybe_display_banner() )
			return;

		// get main instance
		$cn = Cookie_Notice();

		// WPML >= 3.2
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
			$cn->options['general']['message_text'] = apply_filters( 'wpml_translate_single_string', $cn->options['general']['message_text'], 'Cookie Notice', 'Message in the notice' );
			$cn->options['general']['accept_text'] = apply_filters( 'wpml_translate_single_string', $cn->options['general']['accept_text'], 'Cookie Notice', 'Button text' );
			$cn->options['general']['refuse_text'] = apply_filters( 'wpml_translate_single_string', $cn->options['general']['refuse_text'], 'Cookie Notice', 'Refuse button text' );
			$cn->options['general']['revoke_message_text'] = apply_filters( 'wpml_translate_single_string', $cn->options['general']['revoke_message_text'], 'Cookie Notice', 'Revoke message text' );
			$cn->options['general']['revoke_text'] = apply_filters( 'wpml_translate_single_string', $cn->options['general']['revoke_text'], 'Cookie Notice', 'Revoke button text' );
			$cn->options['general']['see_more_opt']['text'] = apply_filters( 'wpml_translate_single_string', $cn->options['general']['see_more_opt']['text'], 'Cookie Notice', 'Privacy policy text' );
			$cn->options['general']['see_more_opt']['link'] = apply_filters( 'wpml_translate_single_string', $cn->options['general']['see_more_opt']['link'], 'Cookie Notice', 'Custom link' );
		// WPML and Polylang compatibility
		} elseif ( function_exists( 'icl_t' ) ) {
			$cn->options['general']['message_text'] = icl_t( 'Cookie Notice', 'Message in the notice', $cn->options['general']['message_text'] );
			$cn->options['general']['accept_text'] = icl_t( 'Cookie Notice', 'Button text', $cn->options['general']['accept_text'] );
			$cn->options['general']['refuse_text'] = icl_t( 'Cookie Notice', 'Refuse button text', $cn->options['general']['refuse_text'] );
			$cn->options['general']['revoke_message_text'] = icl_t( 'Cookie Notice', 'Revoke message text', $cn->options['general']['revoke_message_text'] );
			$cn->options['general']['revoke_text'] = icl_t( 'Cookie Notice', 'Revoke button text', $cn->options['general']['revoke_text'] );
			$cn->options['general']['see_more_opt']['text'] = icl_t( 'Cookie Notice', 'Privacy policy text', $cn->options['general']['see_more_opt']['text'] );
			$cn->options['general']['see_more_opt']['link'] = icl_t( 'Cookie Notice', 'Custom link', $cn->options['general']['see_more_opt']['link'] );
		}

		if ( $cn->options['general']['see_more_opt']['link_type'] === 'page' ) {
			// multisite with global override?
			if ( is_multisite() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] ) {
				// get main site id
				$main_site_id = get_main_site_id();

				// switch to main site
				switch_to_blog( $main_site_id );

				// update page id for current language if needed
				if ( function_exists( 'icl_object_id' ) )
					$cn->options['general']['see_more_opt']['id'] = icl_object_id( $cn->options['general']['see_more_opt']['id'], 'page', true );

				// get main site privacy policy link
				$permalink = get_permalink( $cn->options['general']['see_more_opt']['id'] );

				// restore current site
				restore_current_blog();
			} else {
				// update page id for current language if needed
				if ( function_exists( 'icl_object_id' ) )
					$cn->options['general']['see_more_opt']['id'] = icl_object_id( $cn->options['general']['see_more_opt']['id'], 'page', true );

				// get privacy policy link
				$permalink = get_permalink( $cn->options['general']['see_more_opt']['id'] );
			}
		}

		// #2266: position is API-owned — read from cookie_notice_app_design for connected sites.
		// Falls back to cookie_notice_options["general"]["position"] for disconnected/legacy-only installs.
		$app_design      = $cn->is_network_options()
			? get_site_option( 'cookie_notice_app_design', [] )
			: get_option( 'cookie_notice_app_design', [] );
		$banner_position = ! empty( $app_design['position'] )
			? sanitize_key( $app_design['position'] )
			: ( $cn->options['general']['position'] ?? 'bottom' );

		// get cookie container args
		$options = apply_filters( 'cn_cookie_notice_args', [
			'position'				=> $banner_position,
			'css_class'				=> $cn->options['general']['css_class'],
			'button_class'			=> 'cn-button',
			'colors'				=> $cn->options['general']['colors'],
			'message_text'			=> $cn->options['general']['message_text'],
			'accept_text'			=> $cn->options['general']['accept_text'],
			'refuse_text'			=> $cn->options['general']['refuse_text'],
			'revoke_message_text'	=> $cn->options['general']['revoke_message_text'],
			'revoke_text'			=> $cn->options['general']['revoke_text'],
			'refuse_opt'			=> $cn->options['general']['refuse_opt'],
			'revoke_cookies'		=> $cn->options['general']['revoke_cookies'],
			'see_more'				=> $cn->options['general']['see_more'],
			'see_more_opt'			=> $cn->options['general']['see_more_opt'],
			'link_target'			=> $cn->options['general']['link_target'],
			'link_position'			=> $cn->options['general']['link_position'],
			'aria_label'			=> 'Compliance by Hu-manity.co'
		] );

		// message output
		$output = '
		<!-- Compliance by Hu-manity.co plugin v' . esc_attr( $cn->defaults['version'] ) . ' https://hu-manity.co/ -->
		<div id="cookie-notice" role="dialog" class="cookie-notice-hidden cookie-revoke-hidden cn-position-' . esc_attr( $options['position'] ) . '" aria-label="' . esc_attr( $options['aria_label'] ) . '" style="background-color: __CN_BG_COLOR__">'
			. '<div class="cookie-notice-container" style="color: ' . esc_attr( $options['colors']['text'] ) . '">'
			. '<span id="cn-notice-text" class="cn-text-container">'. ( $options['see_more'] ? do_shortcode( $options['message_text'] ) : $options['message_text'] ) . '</span>'
			. '<span id="cn-notice-buttons" class="cn-buttons-container"><button id="cn-accept-cookie" data-cookie-set="accept" class="cn-set-cookie ' . esc_attr( $options['button_class'] ) . ( $options['css_class'] !== '' ? ' cn-button-custom ' . esc_attr( $options['css_class'] ) : '' ) . '" aria-label="' . esc_attr( $options['accept_text'] ) . '"' . ( $options['css_class'] == '' ? ' style="background-color: ' . esc_attr( $options['colors']['button'] ) . '"' : '' ) . '>' . esc_html( $options['accept_text'] ) . '</button>'
			. ( $options['refuse_opt'] ? '<button id="cn-refuse-cookie" data-cookie-set="refuse" class="cn-set-cookie ' . esc_attr( $options['button_class'] ) . ( $options['css_class'] !== '' ? ' cn-button-custom ' . esc_attr( $options['css_class'] ) : '' ) . '" aria-label="' . esc_attr( $options['refuse_text'] ) . '"' . ( $options['css_class'] == '' ? ' style="background-color: ' . esc_attr( $options['colors']['button'] ) . '"' : '' ) . '>' . esc_html( $options['refuse_text'] ) . '</button>' : '' )
			. ( $options['see_more'] && $options['link_position'] === 'banner' ? '<button data-link-url="' . esc_url( $options['see_more_opt']['link_type'] === 'custom' ? $options['see_more_opt']['link'] : $permalink ) . '" data-link-target="' . esc_attr( $options['link_target'] ) . '" id="cn-more-info" class="cn-more-info ' . esc_attr( $options['button_class'] ) . ( $options['css_class'] !== '' ? ' cn-button-custom ' . esc_attr( $options['css_class'] ) : '' ) . '" aria-label="' . esc_attr( $options['see_more_opt']['text'] ) . '"' . ( $options['css_class'] == '' ? ' style="background-color: ' . esc_attr( $options['colors']['button'] ) . '"' : '' ) . '>' . esc_html( $options['see_more_opt']['text'] ) . '</button>' : '' )
			. '</span><button type="button" id="cn-close-notice" data-cookie-set="accept" class="cn-close-icon" aria-label="' . esc_attr( $options['refuse_text'] ) . '" tabindex="0"></button>'
			. '</div>
			' . ( $options['refuse_opt'] && $options['revoke_cookies'] ?
			'<div class="cookie-revoke-container" style="color: ' . esc_attr( $options['colors']['text'] ) . '">'
			. ( ! empty( $options['revoke_message_text'] ) ? '<span id="cn-revoke-text" class="cn-text-container">' . $options['revoke_message_text'] . '</span>' : '' )
			. '<span id="cn-revoke-buttons" class="cn-buttons-container"><button id="cn-revoke-cookie" class="cn-revoke-cookie ' . esc_attr( $options['button_class'] ) . ( $options['css_class'] !== '' ? ' cn-button-custom ' . esc_attr( $options['css_class'] ) : '' ) . '" aria-label="' . esc_attr( $options['revoke_text'] ) . '"' . ( $options['css_class'] == '' ? ' style="background-color: ' . esc_attr( $options['colors']['button'] ) . '"' : '' ) . '>' . esc_html( $options['revoke_text'] ) . '</button></span>
			</div>' : '' ) . '
		</div>
		<!-- / Compliance by Hu-manity.co plugin -->';

		add_filter( 'safe_style_css', [ $this, 'allow_style_attributes' ] );

		$output = apply_filters( 'cn_cookie_notice_output', wp_kses_post( $output ), $options );

		remove_filter( 'safe_style_css', [ $this, 'allow_style_attributes' ] );

		// convert rgb color to hex
		$bg_rgb_color = $this->hex2rgb( $options['colors']['bar'] );

		// invalid color? use default
		if ( $bg_rgb_color === false )
			$bg_rgb_color = $this->hex2rgb( $cn->defaults['general']['colors']['bar'] );

		// allow rgba background
		echo str_replace( '__CN_BG_COLOR__', esc_attr( 'rgba(' . implode( ',', $bg_rgb_color ) . ',' . ( (int) $options['colors']['bar_opacity'] ) * 0.01 . ');' ), $output );

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
	 * Convert HEX to RGB color.
	 *
	 * @param string $color
	 * @return bool|array
	 */
	public function hex2rgb( $color ) {
		if ( ! is_string( $color ) )
			return false;

		// with hash?
		if ( $color[0] === '#' )
			$color = substr( $color, 1 );

		if ( sanitize_hex_color_no_hash( $color ) !== $color )
			return false;

		// 6 hex digits?
		if ( strlen( $color ) === 6 )
			list( $r, $g, $b ) = [ $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] ];
		// 3 hex digits?
		elseif ( strlen( $color ) === 3 )
			list( $r, $g, $b ) = [ $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] ];
		else
			return false;

		return [ 'r' => hexdec( $r ), 'g' => hexdec( $g ), 'b' => hexdec( $b ) ];
	}

	/**
	 * Add blocking class to scripts, iframes and links.
	 *
	 * @param string $type
	 * @param string $code
	 * @return string
	 */
	public function add_block_class( $type, $code ) {
		// clear and disable libxml errors and allow user to fetch error information as needed
		libxml_use_internal_errors( true );

		// create new dom object
		$document = new DOMDocument( '1.0', 'UTF-8' );

		// set attributes
		$document->formatOutput = true;
		$document->preserveWhiteSpace = false;

		// load code
		$document->loadHTML( '<div>' . wp_kses( trim( $code ), Cookie_Notice()->get_allowed_html( $type ) ) . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$container = $document->getElementsByTagName( 'div' )->item( 0 );
		$container = $container->parentNode->removeChild( $container );

		while ( $document->firstChild ) {
			$document->removeChild( $document->firstChild );
		}

		while ( $container->firstChild ) {
			$document->appendChild( $container->firstChild );
		}

		// set blocked tags
		if ( $type === 'body' )
			$blocked_tags = [ 'script', 'iframe' ];
		elseif ( $type === 'head' )
			$blocked_tags = [ 'script', 'link' ];

		foreach ( $blocked_tags as $blocked_tag ) {
			$tags = $document->getElementsByTagName( $blocked_tag );

			// any tags?
			if ( ! empty( $tags ) && is_object( $tags ) ) {
				foreach ( $tags as $tag ) {
					$tag->setAttribute( 'class', 'hu-block' );
				}
			}
		}

		// save new HTML
		$output = $document->saveHTML();

		// reenable libxml errors
		libxml_use_internal_errors( false );

		return $output;
	}

	/**
	 * Load notice scripts and styles - frontend.
	 *
	 * @return void
	 */
	public function wp_enqueue_notice_scripts() {
		// skip modal login iframe
		if ( current_filter() === 'login_enqueue_scripts' && ! empty( $_REQUEST['interim-login'] ) )
			return;

		// force script if a reopen shortcode is present on the page
		$force_enqueue = $this->has_reopen_shortcode();

		if ( $this->compliance && ! $force_enqueue )
			return;

		// is banner allowed to display?
		if ( ! $force_enqueue && ! $this->maybe_display_banner() )
			return;

		// get main instance
		$cn = Cookie_Notice();

		wp_enqueue_script( 'cookie-notice-front', COOKIE_NOTICE_URL . '/js/front' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [], $cn->defaults['version'], isset( $cn->options['general']['script_placement'] ) && $cn->options['general']['script_placement'] === 'footer' );

		// not array? changeable by cn_cookie_expiry filter
		if ( is_array( $cn->settings->times ) ) {
			// check cookie expiration time
			if ( array_key_exists( $cn->options['general']['time'], $cn->settings->times ) && array_key_exists( 1, $cn->settings->times[$cn->options['general']['time']] ) )
				$cookie_time = (int) $cn->settings->times[$cn->options['general']['time']][1];
			else {
				// fallback to default length of month
				$cookie_time = MONTH_IN_SECONDS;
			}

			// check cookie rejection expiration time
			if ( array_key_exists( $cn->options['general']['time_rejected'], $cn->settings->times ) && array_key_exists( 1, $cn->settings->times[$cn->options['general']['time_rejected']] ) )
				$cookie_time_rejected = (int) $cn->settings->times[$cn->options['general']['time_rejected']][1];
			else {
				// fallback to default length of month
				$cookie_time_rejected = MONTH_IN_SECONDS;
			}
		} else {
			// fallback to default length of month
			$cookie_time = $cookie_time_rejected = MONTH_IN_SECONDS;
		}

		// #2266: position is API-owned — read from cookie_notice_app_design for connected sites.
		// Falls back to cookie_notice_options["general"]["position"] for disconnected/legacy-only installs.
		// (Same resolution as add_cookie_notice() — duplicated here because this is a separate WP hook.)
		$app_design      = $cn->is_network_options()
			? get_site_option( 'cookie_notice_app_design', [] )
			: get_option( 'cookie_notice_app_design', [] );
		$banner_position = ! empty( $app_design['position'] )
			? sanitize_key( $app_design['position'] )
			: ( $cn->options['general']['position'] ?? 'bottom' );

		// prepare script data
		$script_data = [
			'ajaxUrl'				=> admin_url( 'admin-ajax.php' ),
			'nonce'					=> wp_create_nonce( 'cn_save_cases' ),
			'hideEffect'			=> $cn->options['general']['hide_effect'],
			'position'				=> $banner_position,
			'onScroll'				=> $cn->options['general']['on_scroll'],
			'onScrollOffset'		=> (int) $cn->options['general']['on_scroll_offset'],
			'onClick'				=> $cn->options['general']['on_click'],
			'cookieName'			=> 'cookie_notice_accepted',
			'cookieTime'			=> $cookie_time,
			'cookieTimeRejected'	=> $cookie_time_rejected,
			'globalCookie'			=> is_multisite() && $cn->options['general']['global_cookie'] && is_subdomain_install(),
			'redirection'			=> $cn->options['general']['redirection'],
			'cache'					=> defined( 'WP_CACHE' ) && WP_CACHE,
			'revokeCookies'			=> $cn->options['general']['revoke_cookies'],
			'revokeCookiesOpt'		=> $cn->options['general']['revoke_cookies_opt']
		];

		wp_add_inline_script( 'cookie-notice-front', 'var cnArgs = ' . wp_json_encode( $script_data ) . ";\n", 'before' );

		wp_enqueue_style( 'cookie-notice-front', COOKIE_NOTICE_URL . '/css/front' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', [], $cn->defaults['version'] );
	}

	/**
	 * Print non functional JavaScript in body.
	 *
	 * @return void
	 */
	public function wp_print_footer_scripts() {
		// get main instance
		$cn = Cookie_Notice();

		if ( $cn->cookies_accepted() || $this->compliance ) {
			$scripts = apply_filters( 'cn_refuse_code_scripts_html', $cn->options['general']['refuse_code'], 'body' );

			if ( ! empty( $scripts ) )
				echo html_entity_decode( wp_kses( $scripts, $cn->get_allowed_html( 'body' ) ) );
		}
	}

	/**
	 * Print non functional JavaScript in header.
	 *
	 * @return void
	 */
	public function wp_print_header_scripts() {
		// get main instance
		$cn = Cookie_Notice();

		if ( $cn->cookies_accepted() || $this->compliance ) {
			$scripts = apply_filters( 'cn_refuse_code_scripts_html', $cn->options['general']['refuse_code_head'], 'head' );

			if ( ! empty( $scripts ) )
				echo html_entity_decode( wp_kses( $scripts, $cn->get_allowed_html( 'head' ) ) );
		}
	}

	/**
	 * Add new body classes.
	 *
	 * @param array $classes Body classes
	 * @return array
	 */
	public function change_body_class( $classes ) {
		if ( is_admin() )
			return $classes;

		if ( Cookie_Notice()->cookies_set() ) {
			$classes[] = 'cookies-set';

			if ( Cookie_Notice()->cookies_accepted() )
				$classes[] = 'cookies-accepted';
			else
				$classes[] = 'cookies-refused';
		} else
			$classes[] = 'cookies-not-set';

		return $classes;
	}

	/**
	 * Detect if reopen shortcode is present on the current singular content.
	 *
	 * @return bool
	 */
	private function has_reopen_shortcode() {
		if ( ! is_singular() )
			return false;

		global $post;

		if ( empty( $post ) || ! property_exists( $post, 'post_content' ) )
			return false;

		return has_shortcode( $post->post_content, 'cookies_revoke' );
	}

	/**
	 * Purge config cache.
	 *
	 * @return void
	 */
	public function purge_cache() {
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
			// request for new config data too
			$cn->welcome_api->get_app_config( $app_id, true );
		}
	}
}
