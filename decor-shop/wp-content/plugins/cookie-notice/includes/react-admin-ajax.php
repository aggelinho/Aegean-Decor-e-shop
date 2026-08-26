<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_React_Admin_Ajax class.
 *
 * Provides the PHP AJAX backend for the React admin UI.
 * Registers six wp_ajax_ actions consumed by the React admin bundle.
 *
 * @class   Cookie_Notice_React_Admin_Ajax
 * @package Cookie_Notice
 */
class Cookie_Notice_React_Admin_Ajax {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// Read-only hooks — always available regardless of ui_mode.
		add_action( 'wp_ajax_cn_react_dashboard',           [ $this, 'get_dashboard' ] );
		add_action( 'wp_ajax_cn_react_config',              [ $this, 'get_config' ] );
		add_action( 'wp_ajax_cn_react_consent_logs',        [ $this, 'get_consent_logs' ] );
		add_action( 'wp_ajax_cn_react_export_consent_logs', [ $this, 'export_consent_logs' ] );
		add_action( 'wp_ajax_cn_get_api_environment',         [ $this, 'get_api_environment' ] );

		// Write hooks — only register when ui_mode is "react" (#2267).
		// In legacy mode the PHP form path handles writes; registering these
		// would allow stale React JS (cached by a CDN or browser) to race
		// against the legacy form submit.
		$ui_mode = Cookie_Notice()->options['general']['ui_mode'] ?? 'legacy';

		if ( $ui_mode === 'react' ) {
			add_action( 'wp_ajax_cn_react_script_update',       [ $this, 'update_script' ] );
			add_action( 'wp_ajax_cn_react_save_options',        [ $this, 'save_options' ] );
			add_action( 'wp_ajax_cn_react_rescan_scripts',      [ $this, 'rescan_scripts' ] );
			add_action( 'wp_ajax_cn_react_rule_values',         [ $this, 'get_rule_values' ] );
		}

		// Mode-agnostic state hooks — welcome dismissal and setup wizard
		// completion must work regardless of ui_mode.
		add_action( 'wp_ajax_cn_react_dismiss_welcome',        [ $this, 'dismiss_welcome' ] );
		add_action( 'wp_ajax_cn_react_complete_setup_wizard', [ $this, 'complete_setup_wizard' ] );

		// Dev harness only — CN_DEV_MODE is NOT an environment switch (it does not control
		// which API environment the plugin targets). Use CN_APP_HOST_URL, CN_APP_WIDGET_URL,
		// CN_ACCOUNT_API_URL etc. for that. CN_DEV_MODE enables developer-only UI tooling
		// (usage override, dev_reset) and should never be set on production or staging servers.
		if ( defined( 'CN_DEV_MODE' ) && CN_DEV_MODE ) {
			add_action( 'wp_ajax_cn_react_dev_reset',        [ $this, 'dev_reset' ] );
			add_action( 'wp_ajax_cn_react_test_set_option',  [ $this, 'test_set_option' ] );
			add_action( 'wp_ajax_cn_react_test_get_option',  [ $this, 'test_get_option' ] );
		}

	}

	/**
	 * Verify request nonce and capability.
	 *
	 * Sends a JSON error and exits when the check fails, so handlers can call
	 * this at the top without needing to check the return value.
	 *
	 * @return void
	 */
	private function verify_request() {
		check_ajax_referer( 'cn_react_nonce', 'nonce' );

		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) ) {
			wp_send_json_error( [ 'error' => 'Insufficient permissions.' ] );
		}
	}

	/**
	 * Return dashboard data for the Protection tab.
	 *
	 * @return void
	 */
	public function get_dashboard() {
		$this->verify_request();

		$cn = Cookie_Notice();

		// --- Read cached analytics option ---
		// Single source: cookie_notice_app_analytics (refreshed hourly via welcome-api.php cron).
		// ⚠️ Multisite pattern: use site_option ONLY when network-active with global_override.
		// Do NOT simplify to is_multisite() alone — pattern matches welcome-api.php get_app_config().
		$network       = $cn->is_network_options();
		$analytics_raw = $network
			? get_site_option( 'cookie_notice_app_analytics', [] )
			: get_option( 'cookie_notice_app_analytics', [] );

		// --- Cycle usage (visits vs threshold) ---
		// Read from cached analytics option; CN_DEV_MODE overrides for UI testing.
		$visits    = ! empty( $analytics_raw['cycleUsage']->visits ) ? (int) $analytics_raw['cycleUsage']->visits : 0;
		$threshold = ! empty( $analytics_raw['cycleUsage']->threshold ) ? (int) $analytics_raw['cycleUsage']->threshold : 0;

		// CN_DEV_MODE: honour cn_usage=0-100 (forwarded as POST field by fetchDashboard
		// since admin-ajax.php is a POST endpoint and $_GET params from the page URL
		// are not available here).
		if ( defined( 'CN_DEV_MODE' ) && CN_DEV_MODE && isset( $_POST['cn_usage'] ) ) {
			$pct       = max( 0, min( 100, (int) $_POST['cn_usage'] ) );
			$threshold = $threshold > 0 ? $threshold : 1000;
			$visits    = (int) round( $threshold * ( $pct / 100 ) );
		}

		// --- ConsentStats breakdown ---

		$level_totals = [ 1 => 0, 2 => 0, 3 => 0 ];

		if ( ! empty( $analytics_raw['consentActivities'] ) && is_array( $analytics_raw['consentActivities'] ) ) {
			foreach ( $analytics_raw['consentActivities'] as $entry ) {
				$lvl = (int) $entry->consentlevel;
				if ( isset( $level_totals[ $lvl ] ) ) {
					$level_totals[ $lvl ] += (int) $entry->totalrecd;
				}
			}
		}

		$consent_breakdown = $this->compute_consent_breakdown( $level_totals );

		// Regulations saved locally by cn_api_request?configure action.
		// Exposed here so Protection.jsx LAWS card can display them without a
		// Designer API round-trip. (#1897)
		$reg_keys     = $network
			? get_site_option( 'cookie_notice_app_regulations', [] )
			: get_option( 'cookie_notice_app_regulations', [] );
		$regulations  = array_fill_keys( (array) $reg_keys, true );

		// Language codes saved locally by react_apply_languages() on successful API write. (#1966)
		// Always includes 'en' (default) + any additional codes the user configured.
		$saved_languages = $network
			? get_site_option( 'cookie_notice_app_languages', [] )
			: get_option( 'cookie_notice_app_languages', [] );
		$language = array_values( array_unique( array_merge( [ 'en' ], (array) $saved_languages ) ) );

		// Platform account email from login token (#2168).
		// Stored in cookie_notice_app_token transient as ->email after successful login.
		// Used in PortalBridgeModal to tell the user which email to sign in with.
		// Returns empty string when not connected (token not set or expired).
		$data_token    = $network
			? get_site_transient( 'cookie_notice_app_token' )
			: get_transient( 'cookie_notice_app_token' );
		$account_email = ! empty( $data_token->email ) ? sanitize_email( $data_token->email ) : '';

		// Banner design fields cached by get_app_config() — React computes
		// the active template on the fly by matching against PRESETS.
		$design = $network
			? get_site_option( 'cookie_notice_app_design', [] )
			: get_option( 'cookie_notice_app_design', [] );

		wp_send_json_success( [
			'analytics'        => [
				'cycleUsage' => [
					'visits'    => $visits,
					'threshold' => $threshold,
				],
			],
			'consentBreakdown' => $consent_breakdown,
			'domainUrl'        => home_url(),
			'appId'            => $cn->options['general']['app_id'],
			'activatedAt'      => isset( $cn->status_data['activation_datetime'] ) ? $cn->status_data['activation_datetime'] : 0,
			'consentCount'     => $consent_breakdown['total'],
			'accountEmail'     => $account_email,
			'appConfig'        => [
				'regulations' => $regulations,
				'language'    => $language,
				'design'      => $design,
			],
		] );
	}

	/**
	 * Return blocking/consent configuration data.
	 *
	 * Reads the cached Designer API config from the cookie_notice_app_blocking WP option
	 * (populated by welcome-api.php get_app_config() on admin page load, on the
	 * 24h cron, or via "Pull Configuration" button). Falls back to an empty stub
	 * for new installs.
	 *
	 * @return void
	 */
	public function get_config() {
		$this->verify_request();

		$cn = Cookie_Notice();

		// ⚠️ Same multisite pattern as get_dashboard() — see comment there.
		$network  = $cn->is_network_options();
		$blocking = $network
			? get_site_option( 'cookie_notice_app_blocking', [] )
			: get_option( 'cookie_notice_app_blocking', [] );

		wp_send_json_success( $this->build_blocking_response( $blocking ) );
	}

	/**
	 * Return paginated consent log entries.
	 *
	 * Calls the Transactional API via welcome-api.php for the requested date,
	 * maps each record to the shape expected by ConsentLogTable.jsx, then
	 * applies in-PHP pagination (10 records per page).
	 *
	 * POST params accepted:
	 *   page       int     Page number (1-based, default 1)
	 *   start_date string  Date to fetch logs for (Y-m-d, default today)
	 *   sort       string  Sort column key (ignored server-side — API returns ordered data)
	 *   order      string  'asc' | 'desc' (ignored server-side)
	 *
	 * @return void
	 */
	public function get_consent_logs() {
		$this->verify_request();

		$page       = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-d' );
		$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : $start_date;
		$per_page   = 10;

		// Validate date formats (Y-m-d).
		$dt = DateTime::createFromFormat( 'Y-m-d', $start_date );
		if ( ! $dt || $dt->format( 'Y-m-d' ) !== $start_date ) {
			$start_date = date( 'Y-m-d' );
		}

		$dt_end = DateTime::createFromFormat( 'Y-m-d', $end_date );
		if ( ! $dt_end || $dt_end->format( 'Y-m-d' ) !== $end_date || $end_date < $start_date ) {
			$end_date = $start_date;
		}

		$cn = Cookie_Notice();

		// Server-side range cap — free = 7 days, pro = 90 days.
		$max_range = ( $cn->get_subscription() === 'pro' ) ? 90 : 7;
		$range     = (int) ( ( new DateTime( $end_date ) )->diff( new DateTime( $start_date ) )->days );

		if ( $range > $max_range ) {
			$end_date = ( new DateTime( $start_date ) )->modify( "+{$max_range} days" )->format( 'Y-m-d' );
		}

		$empty_breakdown = [ 'total' => 0, 'acceptRate' => 0, 'customRate' => 0, 'rejectRate' => 0, 'levelLabels' => $this->get_level_labels() ];

		// No app_id means not connected — return empty gracefully.
		if ( empty( $cn->options['general']['app_id'] ) ) {
			wp_send_json_success( [
				'logs'             => [],
				'total'            => 0,
				'page'             => $page,
				'totalPages'       => 0,
				'consentBreakdown' => $empty_breakdown,
			] );
			return;
		}

		// Single API call for the full date range (Transactional API handles range via EndDate).
		$raw = $cn->welcome_api->get_cookie_consent_logs( $start_date, $end_date );

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			wp_send_json_success( [
				'logs'             => [],
				'total'            => 0,
				'page'             => $page,
				'totalPages'       => 0,
				'consentBreakdown' => $empty_breakdown,
			] );
			return;
		}

		// Transform raw API records into UI-ready log entries.
		$result = $this->transform_consent_logs( $raw, $cn );
		$logs   = $result['logs'];

		$total      = count( $logs );
		$total_pages = (int) ceil( $total / $per_page );
		$offset     = ( $page - 1 ) * $per_page;
		$paged      = array_slice( $logs, $offset, $per_page );

		wp_send_json_success( [
			'logs'             => $paged,
			'total'            => $total,
			'page'             => $page,
			'totalPages'       => $total_pages,
			'consentBreakdown' => $result['consent_breakdown'],
		] );
	}

	/**
	 * Add, edit, or remove a script provider.
	 *
	 * For 'edit' operations, updates the provider's CategoryID and propagates
	 * the change to all patterns belonging to that provider.
	 *
	 * @return void
	 */
	public function update_script() {
		$this->verify_request();

		$operation = isset( $_POST['operation'] ) ? sanitize_text_field( $_POST['operation'] ) : '';

		if ( ! in_array( $operation, [ 'add', 'edit', 'remove' ], true ) ) {
			wp_send_json_error( [ 'error' => 'Invalid operation.' ] );
		}

		if ( $operation === 'edit' ) {
			$provider_id = isset( $_POST['provider_id'] ) ? sanitize_text_field( $_POST['provider_id'] ) : '';
			$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;

			if ( empty( $provider_id ) ) {
				wp_send_json_error( [ 'error' => 'Missing provider_id.' ] );
			}

			if ( ! in_array( $category_id, [ 1, 2, 3, 4 ], true ) ) {
				wp_send_json_error( [ 'error' => 'Invalid category_id.' ] );
			}

			$cn      = Cookie_Notice();
			$network = $cn->is_network_options();

			$blocking = $network
				? get_site_option( 'cookie_notice_app_blocking', [] )
				: get_option( 'cookie_notice_app_blocking', [] );

			if ( empty( $blocking ) || ! isset( $blocking['providers'] ) ) {
				wp_send_json_error( [ 'error' => 'No blocking configuration found.' ] );
			}

			// Update the provider's CategoryID.
			$found = false;

			foreach ( $blocking['providers'] as &$provider ) {
				$pid = is_object( $provider ) ? $provider->ProviderID : ( isset( $provider['ProviderID'] ) ? $provider['ProviderID'] : '' );

				if ( (string) $pid === (string) $provider_id ) {
					if ( is_object( $provider ) ) {
						$provider->CategoryID = $category_id;
					} else {
						$provider['CategoryID'] = $category_id;
					}
					$found = true;
					break;
				}
			}
			unset( $provider );

			if ( ! $found ) {
				wp_send_json_error( [ 'error' => 'Provider not found.' ] );
			}

			// Propagate CategoryID to all patterns belonging to this provider.
			if ( isset( $blocking['patterns'] ) && is_array( $blocking['patterns'] ) ) {
				foreach ( $blocking['patterns'] as &$pattern ) {
					$pat_pid = is_object( $pattern ) ? $pattern->ProviderID : ( isset( $pattern['ProviderID'] ) ? $pattern['ProviderID'] : '' );

					if ( (string) $pat_pid === (string) $provider_id ) {
						if ( is_object( $pattern ) ) {
							$pattern->CategoryID = $category_id;
						} else {
							$pattern['CategoryID'] = $category_id;
						}
					}
				}
				unset( $pattern );
			}

			// Save back.
			if ( $network ) {
				update_site_option( 'cookie_notice_app_blocking', $blocking );
			} else {
				update_option( 'cookie_notice_app_blocking', $blocking );
			}
		}

		if ( $operation === 'add' ) {
			$provider_name   = isset( $_POST['provider_name'] ) ? sanitize_text_field( $_POST['provider_name'] ) : '';
			$provider_url    = isset( $_POST['provider_url'] )  ? esc_url_raw( $_POST['provider_url'] )           : '';
			$category_id     = isset( $_POST['category_id'] )   ? absint( $_POST['category_id'] )                  : 0;
			$description     = isset( $_POST['description'] )   ? sanitize_text_field( $_POST['description'] )     : '';
			$script_patterns = isset( $_POST['script_patterns'] ) && is_array( $_POST['script_patterns'] ) ? $_POST['script_patterns'] : [];
			$iframe_patterns = isset( $_POST['iframe_patterns'] ) && is_array( $_POST['iframe_patterns'] ) ? $_POST['iframe_patterns'] : [];

			if ( empty( $provider_name ) ) {
				wp_send_json_error( [ 'error' => 'Provider name is required.' ] );
			}

			if ( ! in_array( $category_id, [ 1, 2, 3, 4 ], true ) ) {
				wp_send_json_error( [ 'error' => 'Invalid category_id.' ] );
			}

			$cn      = Cookie_Notice();
			$network = $cn->is_network_options();

			$blocking = $network
				? get_site_option( 'cookie_notice_app_blocking', [] )
				: get_option( 'cookie_notice_app_blocking', [] );

			if ( ! is_array( $blocking ) ) {
				$blocking = [];
			}
			if ( ! isset( $blocking['providers'] ) ) {
				$blocking['providers'] = [];
			}
			if ( ! isset( $blocking['patterns'] ) ) {
				$blocking['patterns'] = [];
			}

			// Generate a unique provider ID from the name + timestamp.
			$provider_id = 'custom-' . sanitize_title( $provider_name ) . '-' . time();

			// Append the new provider.
			$blocking['providers'][] = (object) [
				'ProviderID'   => $provider_id,
				'ProviderName' => $provider_name,
				'ProviderURL'  => $provider_url,
				'CategoryID'   => $category_id,
				'IsCustom'     => true,
			];

			// Find current max CookieID so new patterns get unique IDs.
			$max_cookie_id = 0;
			foreach ( $blocking['patterns'] as $p ) {
				$cid = is_object( $p ) ? (int) $p->CookieID : (int) ( isset( $p['CookieID'] ) ? $p['CookieID'] : 0 );
				if ( $cid > $max_cookie_id ) {
					$max_cookie_id = $cid;
				}
			}

			// Append script patterns.
			foreach ( $script_patterns as $pattern_str ) {
				$pattern_str = sanitize_text_field( stripslashes( $pattern_str ) );
				if ( empty( $pattern_str ) ) {
					continue;
				}
				$max_cookie_id++;
				$blocking['patterns'][] = (object) [
					'CookieID'      => $max_cookie_id,
					'ProviderID'    => $provider_id,
					'CategoryID'    => $category_id,
					'PatternType'   => 'script',
					'PatternFormat' => 'wildcard',
					'Pattern'       => $pattern_str,
				];
			}

			// Append iframe patterns.
			foreach ( $iframe_patterns as $pattern_str ) {
				$pattern_str = sanitize_text_field( stripslashes( $pattern_str ) );
				if ( empty( $pattern_str ) ) {
					continue;
				}
				$max_cookie_id++;
				$blocking['patterns'][] = (object) [
					'CookieID'      => $max_cookie_id,
					'ProviderID'    => $provider_id,
					'CategoryID'    => $category_id,
					'PatternType'   => 'iframe',
					'PatternFormat' => 'wildcard',
					'Pattern'       => $pattern_str,
				];
			}

			if ( $network ) {
				update_site_option( 'cookie_notice_app_blocking', $blocking );
			} else {
				update_option( 'cookie_notice_app_blocking', $blocking );
			}

			wp_send_json_success( [
				'message'     => 'Script provider added.',
				'provider_id' => $provider_id,
			] );
		}

		if ( $operation === 'remove' ) {
			$provider_id = isset( $_POST['provider_id'] ) ? sanitize_text_field( $_POST['provider_id'] ) : '';

			if ( empty( $provider_id ) ) {
				wp_send_json_error( [ 'error' => 'Missing provider_id.' ] );
			}

			$cn      = Cookie_Notice();
			$network = $cn->is_network_options();

			$blocking = $network
				? get_site_option( 'cookie_notice_app_blocking', [] )
				: get_option( 'cookie_notice_app_blocking', [] );

			if ( empty( $blocking ) || ! isset( $blocking['providers'] ) ) {
				wp_send_json_error( [ 'error' => 'No blocking configuration found.' ] );
			}

			// Remove the provider entry.
			$blocking['providers'] = array_values( array_filter( $blocking['providers'], function( $p ) use ( $provider_id ) {
				$pid = is_object( $p ) ? $p->ProviderID : ( isset( $p['ProviderID'] ) ? $p['ProviderID'] : '' );
				return (string) $pid !== (string) $provider_id;
			} ) );

			// Remove all patterns belonging to this provider.
			if ( isset( $blocking['patterns'] ) && is_array( $blocking['patterns'] ) ) {
				$blocking['patterns'] = array_values( array_filter( $blocking['patterns'], function( $p ) use ( $provider_id ) {
					$pid = is_object( $p ) ? $p->ProviderID : ( isset( $p['ProviderID'] ) ? $p['ProviderID'] : '' );
					return (string) $pid !== (string) $provider_id;
				} ) );
			}

			if ( $network ) {
				update_site_option( 'cookie_notice_app_blocking', $blocking );
			} else {
				update_option( 'cookie_notice_app_blocking', $blocking );
			}

			wp_send_json_success( [ 'message' => 'Script provider removed.' ] );
		}

		wp_send_json_success( [ 'message' => 'Script provider updated.' ] );
	}

	/**
	 * Transform raw API consent log records into structured log entries.
	 *
	 * Shared by get_consent_logs() (paginated table) and export_consent_logs() (CSV).
	 * Returns both the transformed log entries and the consent breakdown stats.
	 *
	 * @param array              $raw Raw records from the Transactional API.
	 * @param Cookie_Notice_Main $cn  Plugin instance.
	 * @return array { 'logs' => array, 'consent_breakdown' => array }
	 */
	private function transform_consent_logs( $raw, $cn ) {
		// Compute consent breakdown from real-time data.
		$level_counts = [ 1 => 0, 2 => 0, 3 => 0 ];

		foreach ( $raw as $record ) {
			$lvl = isset( $record->ev_consentlevel ) ? (int) $record->ev_consentlevel : 0;
			if ( isset( $level_counts[ $lvl ] ) ) {
				$level_counts[ $lvl ]++;
			}
		}

		$consent_breakdown = $this->compute_consent_breakdown( $level_counts );

		// Consent level integer → human label (matches ConsentLogTable pill styles).
		$labels = $this->get_level_labels();
		$level_map = [
			1 => $labels['level1'],
			2 => $labels['level2'],
			3 => $labels['level3'],
		];

		$logs = [];

		foreach ( $raw as $record ) {
			$categories = [];

			if ( ! empty( $record->ev_essential ) )
				$categories[] = 'Essential';

			if ( ! empty( $record->ev_analytics ) )
				$categories[] = 'Analytics';

			if ( ! empty( $record->ev_marketing ) )
				$categories[] = 'Marketing';

			if ( ! empty( $record->ev_functional ) )
				$categories[] = 'Functional';

			$level = isset( $record->ev_consentlevel ) ? (int) $record->ev_consentlevel : 0;

			// Format timestamp to readable date/time.
			$date_str = '';
			if ( ! empty( $record->timestamp ) ) {
				try {
					$ts       = new DateTime( $record->timestamp );
					$date_str = $ts->format( 'Y-m-d H:i' ) . ' GMT';
				} catch ( Exception $e ) {
					$date_str = $record->timestamp;
				}
			}

			$logs[] = [
				'id'         => isset( $record->ev_eventdetails_consentid ) ? $record->ev_eventdetails_consentid : '',
				'level'      => isset( $level_map[ $level ] ) ? $level_map[ $level ] : $labels['level2'],
				'levelNum'   => $level,
				'categories' => $categories,
				'date'       => $date_str,
				'ip'         => isset( $record->rj_ip ) ? $record->rj_ip : '',
			];
		}

		return [
			'logs'              => $logs,
			'consent_breakdown' => $consent_breakdown,
		];
	}

	/**
	 * Export consent logs as a downloadable CSV.
	 *
	 * Reuses transform_consent_logs() for data transformation, then formats
	 * the result as CSV and returns it as a string for browser download.
	 * Pro-only: enforced server-side (client-side TierGate is not sufficient).
	 *
	 * POST params accepted:
	 *   start_date string  Range start (Y-m-d, default today)
	 *   end_date   string  Range end   (Y-m-d, default start_date)
	 *
	 * @return void
	 */
	public function export_consent_logs() {
		$this->verify_request();

		$cn = Cookie_Notice();

		// Server-side Pro gate — TierGate in React is client-only.
		if ( $cn->get_subscription() !== 'pro' ) {
			wp_send_json_error( [ 'error' => 'CSV export requires a Pro subscription.' ] );
			return;
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-d' );
		$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : $start_date;

		// Validate date formats (Y-m-d).
		$dt = DateTime::createFromFormat( 'Y-m-d', $start_date );
		if ( ! $dt || $dt->format( 'Y-m-d' ) !== $start_date ) {
			$start_date = date( 'Y-m-d' );
		}

		$dt_end = DateTime::createFromFormat( 'Y-m-d', $end_date );
		if ( ! $dt_end || $dt_end->format( 'Y-m-d' ) !== $end_date || $end_date < $start_date ) {
			$end_date = $start_date;
		}

		// Server-side range cap — Pro = 90 days.
		$range = (int) ( ( new DateTime( $end_date ) )->diff( new DateTime( $start_date ) )->days );

		if ( $range > 90 ) {
			$end_date = ( new DateTime( $start_date ) )->modify( '+90 days' )->format( 'Y-m-d' );
		}

		// No app_id means not connected — return empty.
		if ( empty( $cn->options['general']['app_id'] ) ) {
			wp_send_json_success( [ 'csv' => '', 'count' => 0 ] );
			return;
		}

		$raw = $cn->welcome_api->get_cookie_consent_logs( $start_date, $end_date );

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			wp_send_json_success( [ 'csv' => '', 'count' => 0 ] );
			return;
		}

		$result = $this->transform_consent_logs( $raw, $cn );
		$logs   = $result['logs'];

		// Build CSV string.
		$csv_lines   = [];
		$csv_lines[] = 'Consent ID,Level,Date,IP,Categories';

		foreach ( $logs as $log ) {
			$csv_lines[] = sprintf(
				'"%s","%s","%s","%s","%s"',
				str_replace( '"', '""', $log['id'] ),
				str_replace( '"', '""', $log['level'] ),
				str_replace( '"', '""', $log['date'] ),
				str_replace( '"', '""', $log['ip'] ),
				str_replace( '"', '""', implode( '; ', $log['categories'] ) )
			);
		}

		wp_send_json_success( [
			'csv'   => implode( "\n", $csv_lines ),
			'count' => count( $logs ),
		] );
	}

	/**
	 * Rescan scripts from the Designer API.
	 *
	 * Forces a fresh fetch of the app blocking config from the remote
	 * Designer API, then returns the updated blocking data in the same
	 * shape as get_config().
	 *
	 * @return void
	 */
	public function rescan_scripts() {
		$this->verify_request();

		$cn = Cookie_Notice();

		// Force a fresh sync from the Designer API.
		$cn->welcome_api->get_app_config( '', true );

		// Re-read the now-updated local cache and return it.
		$network  = $cn->is_network_options();
		$blocking = $network
			? get_site_option( 'cookie_notice_app_blocking', [] )
			: get_option( 'cookie_notice_app_blocking', [] );

		// CN_DEV_MODE: inject sample trackers when the real scan returns empty,
		// so the UI can be tested without real third-party scripts on the page.
		if ( defined( 'CN_DEV_MODE' ) && CN_DEV_MODE && empty( $blocking['providers'] ) ) {
			$sample_providers = [
				(object) [ 'ProviderID' => 'google-analytics', 'ProviderName' => 'Google Analytics', 'ProviderURL' => 'analytics.google.com', 'CategoryID' => 0 ],
				(object) [ 'ProviderID' => 'hotjar',           'ProviderName' => 'Hotjar',           'ProviderURL' => 'hotjar.com',            'CategoryID' => 0 ],
				(object) [ 'ProviderID' => 'meta-pixel',       'ProviderName' => 'Meta Pixel',       'ProviderURL' => 'facebook.com',          'CategoryID' => 0 ],
				(object) [ 'ProviderID' => 'hubspot',          'ProviderName' => 'HubSpot',          'ProviderURL' => 'hubspot.com',           'CategoryID' => 1 ],
				(object) [ 'ProviderID' => 'linkedin-insight',  'ProviderName' => 'LinkedIn Insight', 'ProviderURL' => 'linkedin.com',         'CategoryID' => 0 ],
			];

			if ( ! is_array( $blocking ) ) {
				$blocking = [];
			}

			$blocking['providers'] = $sample_providers;
		}

		wp_send_json_success( $this->build_blocking_response( $blocking ) );
	}

	/**
	 * Save welcome modal dismissal timestamp.
	 *
	 * Called when the user closes the modal or clicks "Don't protect my business".
	 * Stores the timestamp so the modal won't re-appear for 30 days.
	 *
	 * @return void
	 */
	public function dismiss_welcome() {
		$this->verify_request();

		$cn = Cookie_Notice();

		if ( $cn->is_network_admin() )
			update_site_option( 'cookie_notice_welcome_dismissed', current_time( 'mysql' ) );
		else
			update_option( 'cookie_notice_welcome_dismissed', current_time( 'mysql' ) );

		wp_send_json_success();
	}

	/**
	 * Mark the setup wizard as complete.
	 *
	 * Called when the user finishes (or skips) the FirstRunSetup wizard on the
	 * Settings tab. Persists a flag so the wizard doesn't re-appear.
	 *
	 * @return void
	 */
	public function complete_setup_wizard() {
		$this->verify_request();

		$cn = Cookie_Notice();

		if ( $cn->is_network_admin() )
			update_site_option( 'cookie_notice_setup_wizard_complete', true );
		else
			update_option( 'cookie_notice_setup_wizard_complete', true );

		wp_send_json_success();
	}

	/**
	 * DEV ONLY: Reset all plugin onboarding state to simulate a fresh activation.
	 * Only registered as an AJAX action when CN_DEV_MODE is true.
	 */
	public function dev_reset() {
		if ( ! defined( 'CN_DEV_MODE' ) || ! CN_DEV_MODE ) {
			wp_send_json_error( [ 'error' => 'Not available outside CN_DEV_MODE.' ] );
		}

		$this->verify_request();

		$cn = Cookie_Notice();

		// --- Step 1: Delete the API-side app record BEFORE clearing WP options. (#1956)
		//
		// After a successful use_license or register+configure flow, the Account API creates
		// an Application row for this domain. Deleting WP options alone does NOT remove it:
		// - The app record consumes a subscription slot (distorts availablelicense counts)
		// - Orphan apps accumulate across test runs
		//
		// We capture the current app_id from WP options, authenticate as the test account
		// (whose credentials are defined via CN_DEV_TEST_EMAIL + CN_DEV_TEST_PASSWORD
		// constants, falling back to env vars), then call POST /api/account/app/delete.
		//
		// This is best-effort: login or delete failures are logged but do NOT block the
		// WP options reset — the reset must always succeed regardless of API availability.
		$current_app_id = ! empty( $cn->options['general']['app_id'] ) ? $cn->options['general']['app_id'] : '';

		if ( ! empty( $current_app_id ) ) {
			$test_email    = defined( 'CN_DEV_TEST_EMAIL' )    ? CN_DEV_TEST_EMAIL    : getenv( 'CN_DEV_TEST_EMAIL' );
			$test_password = defined( 'CN_DEV_TEST_PASSWORD' ) ? CN_DEV_TEST_PASSWORD : getenv( 'CN_DEV_TEST_PASSWORD' );

			if ( ! empty( $test_email ) && ! empty( $test_password ) ) {
				// Login to get a Bearer token, then delete the app.
				$welcome_api = Cookie_Notice()->welcome;
				$login_result = $welcome_api->request( 'login', [
					'AdminID'  => $test_email,
					'Password' => $test_password,
				] );

				if ( ! empty( $login_result->data->token ) ) {
					// Store the full data object (not just the token string) — request() reads
					// $data_token->token so the shape must match what login normally stores.
					set_transient( 'cookie_notice_app_token', $login_result->data, HOUR_IN_SECONDS );

					$delete_result = $welcome_api->request( 'app_delete', [
						'AppID' => $current_app_id,
					] );

					if ( $cn->options['general']['debug_mode'] ) {
						error_log( '[Cookie Notice] dev_reset - app_delete result for ' . $current_app_id . ': ' . wp_json_encode( $delete_result ) );
					}
				} else {
					if ( $cn->options['general']['debug_mode'] ) {
						error_log( '[Cookie Notice] dev_reset - login failed for ' . $test_email . ', skipping app_delete.' );
					}
				}
			}
		}

		// --- Step 2: Clear WP options (always runs regardless of API result above).
		delete_option( 'cookie_notice_welcome_dismissed' );
		delete_option( 'cookie_notice_setup_wizard_complete' );

		$options = $cn->options['general'];
		$options['app_id']  = '';
		$options['app_key'] = '';

		if ( is_multisite() ) {
			update_site_option( 'cookie_notice_options', $options );
		} else {
			update_option( 'cookie_notice_options', $options );
		}

		$default_data = $cn->defaults['data'];

		if ( is_multisite() ) {
			update_site_option( 'cookie_notice_status', $default_data );
		} else {
			update_option( 'cookie_notice_status', $default_data );
		}

		// Clear transient caches
		delete_transient( 'cookie_notice_app_quick_config' );
		delete_site_transient( 'cookie_notice_app_quick_config' );
		delete_transient( 'cookie_notice_app_token' );
		delete_site_transient( 'cookie_notice_app_token' );

		$deleted_app = ! empty( $current_app_id ) ? $current_app_id : null;
		wp_send_json_success( [
			'message'     => 'Plugin reset to fresh-activation state.',
			'deleted_app' => $deleted_app,
		] );
	}

	/**
	 * DEV ONLY: Set a single allowlisted WP option by name.
	 * Used by Playwright tests to set fixture state without Docker/WP-CLI.
	 * Only registered as an AJAX action when CN_DEV_MODE is true.
	 *
	 * POST fields:
	 *   option_name  — one of the allowlisted option names below
	 *   option_value — string value to store
	 */
	public function test_set_option() {
		if ( ! defined( 'CN_DEV_MODE' ) || ! CN_DEV_MODE ) {
			wp_send_json_error( [ 'error' => 'Not available outside CN_DEV_MODE.' ] );
		}

		$this->verify_request();

		// Allowlist — only options the test suite legitimately needs to set.
		$allowed = [
			'cookie_notice_ui_mode',
			'cookie_notice_status',
			'cookie_notice_setup_wizard_complete',
			'cookie_notice_welcome_dismissed',
			'cookie_notice_options',
		];

		$option_name = isset( $_POST['option_name'] ) ? sanitize_key( $_POST['option_name'] ) : '';

		if ( ! in_array( $option_name, $allowed, true ) ) {
			wp_send_json_error( [ 'error' => 'Option not in allowlist: ' . $option_name ] );
		}

		// cookie_notice_options is stored as a PHP array — decode JSON input.
		$raw_value = isset( $_POST['option_value'] ) ? wp_unslash( $_POST['option_value'] ) : '';

		if ( $option_name === 'cookie_notice_options' ) {
			$option_value = json_decode( $raw_value, true );
			if ( ! is_array( $option_value ) ) {
				wp_send_json_error( [ 'error' => 'cookie_notice_options must be valid JSON object.' ] );
			}
		} else {
			$option_value = sanitize_text_field( $raw_value );
		}

		update_option( $option_name, $option_value );

		wp_send_json_success( [ 'option' => $option_name, 'value' => $option_value ] );
	}

	/**
	 * DEV ONLY: Read a single allowlisted WP option by name.
	 * Used by Playwright tests to inspect persisted state without Docker/WP-CLI.
	 * Only registered as an AJAX action when CN_DEV_MODE is true.
	 *
	 * POST fields:
	 *   option_name — one of the allowlisted option names below
	 */
	public function test_get_option() {
		if ( ! defined( 'CN_DEV_MODE' ) || ! CN_DEV_MODE ) {
			wp_send_json_error( [ 'error' => 'Not available outside CN_DEV_MODE.' ] );
		}

		$this->verify_request();

		// Allowlist — only options the test suite legitimately needs to read.
		$allowed = [
			'cookie_notice_options',
			'cookie_notice_status',
			'cookie_notice_ui_mode',
			'cookie_notice_setup_wizard_complete',
			'cookie_notice_welcome_dismissed',
			'cookie_notice_app_blocking',
			'cookie_notice_app_design',
		];

		$option_name = isset( $_POST['option_name'] ) ? sanitize_key( $_POST['option_name'] ) : '';

		if ( ! in_array( $option_name, $allowed, true ) ) {
			wp_send_json_error( [ 'error' => 'Option not in allowlist: ' . $option_name ] );
		}

		$value = get_option( $option_name );

		// Serialize arrays/objects so the test can inspect them as a string.
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		}

		wp_send_json_success( [ 'option' => $option_name, 'value' => (string) $value ] );
	}

	/**
	 * Save plugin options submitted from the React admin UI.
	 *
	 * Reads each recognized POST field, sanitizes it, and merges it into the
	 * existing options array before persisting via update_option() (single-site)
	 * or update_site_option() (network).
	 *
	 * @return void
	 */
	public function save_options() {
		$this->verify_request();

		$cn      = Cookie_Notice();
		$options = $cn->options['general'];

		// Boolean fields.
		$bool_fields = [
			'refuse_opt',
			'revoke_cookies',
			'on_scroll',
			'on_click',
			'redirection',
			'see_more',
			'bot_detection',
			'amp_support',
			'caching_compatibility',
			'debug_mode',
			'conditional_active',
			'deactivation_delete',
			'app_blocking',
		];

		foreach ( $bool_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$options[ $field ] = (bool) $_POST[ $field ];
			}
		}

		// Server-side threshold enforcement: cap app_blocking to false when
		// the free-plan visit limit is exceeded, matching settings.php:1965.
		if ( ! empty( $options['app_blocking'] ) && $cn->threshold_exceeded() ) {
			$options['app_blocking'] = false;
		}

		// Text fields.
		$text_fields = [
			'message_text',
			'accept_text',
			'refuse_text',
			'revoke_text',
			'revoke_message_text',
			'css_class',
		];

		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$options[ $field ] = sanitize_text_field( $_POST[ $field ] );
			}
		}

		// Connection credential fields — sanitize_key strips to lowercase alphanumeric + dashes/underscores.
		if ( isset( $_POST['app_id'] ) ) {
			$options['app_id'] = sanitize_key( $_POST['app_id'] );
		}

		if ( isset( $_POST['app_key'] ) ) {
			$options['app_key'] = sanitize_key( $_POST['app_key'] );
		}

		// Script blocking code fields — these can contain <script> tags,
		// so use wp_unslash only (admin-only, manage_options cap verified).
		if ( isset( $_POST['refuse_code'] ) ) {
			$options['refuse_code'] = wp_unslash( $_POST['refuse_code'] );
		}

		if ( isset( $_POST['refuse_code_head'] ) ) {
			$options['refuse_code_head'] = wp_unslash( $_POST['refuse_code_head'] );
		}

		// Excluded script handles — newline-separated string from React textarea → stored as array.
		if ( isset( $_POST['excluded_handles'] ) ) {
			$options['excluded_handles'] = array_values( array_filter( array_map( 'sanitize_text_field', explode( "\n", $_POST['excluded_handles'] ) ) ) );
		}

		// Conditional rules — JSON string from React → validated nested array.
		if ( isset( $_POST['conditional_rules'] ) ) {
			$raw_rules = json_decode( wp_unslash( $_POST['conditional_rules'] ), true );

			if ( is_array( $raw_rules ) ) {
				$settings  = Cookie_Notice()->settings;
				$group_id  = 1;
				$rules     = [];

				foreach ( $raw_rules as $group ) {
					if ( ! is_array( $group ) || empty( $group ) ) {
						continue;
					}

					$rule_id = 1;

					foreach ( $group as $rule ) {
						if ( ! is_array( $rule ) ) {
							continue;
						}

						$param    = sanitize_key( $rule['param'] ?? '' );
						$operator = sanitize_key( $rule['operator'] ?? '' );
						$value    = $param === 'taxonomy_archive'
							? ( $rule['value'] ?? '' )
							: sanitize_key( $rule['value'] ?? '' );

						if ( $param && $operator && $value !== '' && $settings->check_rule( $param, $operator, $value ) ) {
							$rules[ $group_id ][ $rule_id++ ] = [
								'param'    => $param,
								'operator' => $operator,
								'value'    => $value,
							];
						}
					}

					if ( ! empty( $rules[ $group_id ] ) ) {
						$group_id++;
					}
				}

				$options['conditional_rules'] = $rules;
			} else {
				$options['conditional_rules'] = [];
			}
		}

		// Select fields — value must be one of the allowed options.
		$select_fields = [
			'revoke_cookies_opt' => [ 'automatic', 'manual' ],
			'time'               => [ 'hour', 'day', 'week', 'month', '3months', '6months', 'year', 'infinity' ],
			'time_rejected'      => [ 'hour', 'day', 'week', 'month', '3months', '6months', 'year', 'infinity' ],
			'link_target'        => [ '_blank', '_self' ],
			'link_position'      => [ 'banner', 'message' ],
			'position'           => [ 'top', 'bottom', 'left', 'right', 'popup' ],
			'displayType'        => [ 'fixed', 'floating' ],
			'hide_effect'        => [ 'none', 'fade', 'slide' ],
			'script_placement'   => [ 'header', 'footer' ],
			'conditional_display' => [ 'hide', 'show' ],
			'ui_mode'             => [ 'react', 'legacy' ],
		];

		foreach ( $select_fields as $field => $allowed ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = sanitize_text_field( $_POST[ $field ] );
				if ( in_array( $value, $allowed, true ) ) {
					$options[ $field ] = $value;
				}
			}
		}

		// Number fields.
		if ( isset( $_POST['on_scroll_offset'] ) ) {
			$options['on_scroll_offset'] = absint( $_POST['on_scroll_offset'] );
		}

		// Nested colors array — text, button, bar, bar_opacity.
		$color_fields = [ 'text', 'button', 'bar' ];
		foreach ( $color_fields as $color_field ) {
			$post_key = 'color_' . $color_field;
			if ( isset( $_POST[ $post_key ] ) ) {
				$val = sanitize_hex_color( $_POST[ $post_key ] );
				if ( $val ) {
					$options['colors'][ $color_field ] = $val;
				}
			}
		}

		// bar_opacity lives inside the nested colors array; clamp to 50–100.
		if ( isset( $_POST['bar_opacity'] ) ) {
			$bar_opacity = absint( $_POST['bar_opacity'] );
			$bar_opacity = max( 50, min( 100, $bar_opacity ) );
			$options['colors']['bar_opacity'] = $bar_opacity;
		}

		// Nested see_more_opt array.
		if ( isset( $_POST['see_more_opt'] ) && is_array( $_POST['see_more_opt'] ) ) {
			$raw = $_POST['see_more_opt'];

			if ( isset( $raw['text'] ) ) {
				$options['see_more_opt']['text'] = sanitize_text_field( $raw['text'] );
			}

			if ( isset( $raw['link_type'] ) ) {
				$link_type = sanitize_text_field( $raw['link_type'] );
				if ( in_array( $link_type, [ 'page', 'custom' ], true ) ) {
					$options['see_more_opt']['link_type'] = $link_type;
				}
			}

			if ( isset( $raw['id'] ) ) {
				$options['see_more_opt']['id'] = absint( $raw['id'] );
			}

			if ( isset( $raw['link'] ) ) {
				$options['see_more_opt']['link'] = esc_url_raw( $raw['link'] );
			}

			if ( isset( $raw['sync'] ) ) {
				$options['see_more_opt']['sync'] = (bool) $raw['sync'];
			}
		}

		// Enforce field ownership partition (#2264) — strip any key that is
		// not declared in Cookie_Notice::$plugin_owned_fields. Nested sub-arrays
		// (colors, see_more_opt, conditional_rules) are already in the allowlist.
		$allowed = Cookie_Notice::$plugin_owned_fields;

		foreach ( array_keys( $options ) as $key ) {
			if ( ! in_array( $key, $allowed, true ) ) {
				unset( $options[ $key ] );
			}
		}

		// Persist — network vs. single-site.
		if ( isset( $_POST['cn_network'] ) && $_POST['cn_network'] ) {
			update_site_option( 'cookie_notice_options', $options );
		} else {
			update_option( 'cookie_notice_options', $options );
		}

		wp_send_json_success( [ 'message' => __( 'Settings saved.', 'cookie-notice' ) ] );
	}

	/**
	 * Return the active API environment URLs.
	 *
	 * Used by integration tests to verify that the WP instance is targeting
	 * stage APIs before any live API calls are made. Always registered —
	 * does not require CN_DEV_MODE.
	 *
	 * @return void
	 */
	/**
	 * Return conditional display rule values for a given parameter type.
	 *
	 * Called when the user changes the param dropdown in the rule builder.
	 * Returns a flat array of { value, label } objects (and optionally grouped).
	 *
	 * @return void
	 */
	public function get_rule_values() {
		$this->verify_request();

		$param = isset( $_POST['param'] ) ? sanitize_key( $_POST['param'] ) : '';

		if ( ! $param ) {
			wp_send_json_error( [ 'message' => 'Missing param' ] );
		}

		$values = [];

		switch ( $param ) {
			case 'page_type':
				$values = [
					[ 'value' => 'front', 'label' => __( 'Front Page', 'cookie-notice' ) ],
					[ 'value' => 'home', 'label' => __( 'Home Page', 'cookie-notice' ) ],
				];
				break;

			case 'page':
				$pages = get_pages( [ 'post_status' => [ 'publish', 'private', 'future' ] ] );
				$front = (int) get_option( 'page_on_front' );
				$blog  = (int) get_option( 'page_for_posts' );

				foreach ( $pages as $page ) {
					if ( $page->ID === $front || $page->ID === $blog ) {
						continue;
					}
					$values[] = [ 'value' => (string) $page->ID, 'label' => $page->post_title ];
				}
				break;

			case 'post_type':
				$types = get_post_types( [ 'public' => true ], 'objects' );

				foreach ( $types as $type ) {
					$values[] = [ 'value' => $type->name, 'label' => $type->labels->singular_name ];
				}
				break;

			case 'post_type_archive':
				$types = get_post_types( [ 'public' => true, 'has_archive' => true ], 'objects' );

				foreach ( $types as $type ) {
					$values[] = [ 'value' => $type->name, 'label' => $type->labels->singular_name ];
				}
				break;

			case 'user_type':
				$values = [
					[ 'value' => 'logged_in', 'label' => __( 'Logged in', 'cookie-notice' ) ],
					[ 'value' => 'guest', 'label' => __( 'Guest', 'cookie-notice' ) ],
				];
				break;

			case 'taxonomy_archive':
				$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

				foreach ( $taxonomies as $taxonomy ) {
					$terms = get_terms( [ 'taxonomy' => $taxonomy->name, 'hide_empty' => false ] );

					if ( is_wp_error( $terms ) || empty( $terms ) ) {
						continue;
					}

					$group = [
						'group' => $taxonomy->labels->name,
						'items' => [],
					];

					foreach ( $terms as $term ) {
						$group['items'][] = [
							'value' => $term->term_id . '|' . $taxonomy->name,
							'label' => $term->name,
						];
					}

					$values[] = $group;
				}
				break;
		}

		wp_send_json_success( [ 'values' => $values ] );
	}

	public function get_api_environment() {
		$this->verify_request();

		$cn = Cookie_Notice();

		wp_send_json_success( [
			'host'              => $cn->get_url( 'host' ),
			'account_api'       => $cn->get_url( 'account_api' ),
			'designer_api'      => $cn->get_url( 'designer_api' ),
			'transactional_api' => $cn->get_url( 'transactional_api' ),
			'widget'            => $cn->get_url( 'widget' ),
		] );
	}

	/**
	 * Build the standardised blocking + config response shape.
	 *
	 * Shared by get_config() and rescan_scripts() to avoid maintaining the
	 * 7-key blocking object in two places.
	 *
	 * @param array $blocking Raw blocking option (cookie_notice_app_blocking).
	 * @return array { 'blocking' => [...], 'config' => object }
	 */
	private function build_blocking_response( $blocking ) {
		if ( empty( $blocking ) ) {
			return [
				'blocking' => [
					'providers'                  => [],
					'patterns'                   => [],
					'google_consent_default'     => null,
					'facebook_consent_default'   => null,
					'microsoft_consent_default'  => null,
					'gpc_support'                => false,
					'do_not_track'               => false,
				],
				'config' => new stdClass(),
			];
		}

		$config = isset( $blocking['banner_config'] ) && is_array( $blocking['banner_config'] )
			? $blocking['banner_config']
			: new stdClass();

		return [
			'blocking' => [
				'providers'                  => isset( $blocking['providers'] ) ? $blocking['providers'] : [],
				'patterns'                   => isset( $blocking['patterns'] ) ? $blocking['patterns'] : [],
				'google_consent_default'     => isset( $blocking['google_consent_default'] ) ? $blocking['google_consent_default'] : null,
				'facebook_consent_default'   => isset( $blocking['facebook_consent_default'] ) ? $blocking['facebook_consent_default'] : null,
				'microsoft_consent_default'  => isset( $blocking['microsoft_consent_default'] ) ? $blocking['microsoft_consent_default'] : null,
				'gpc_support'                => ! empty( $blocking['gpc_support'] ),
				'do_not_track'               => ! empty( $blocking['do_not_track'] ),
				'lastUpdated'                => isset( $blocking['lastUpdated'] ) ? $blocking['lastUpdated'] : '',
			],
			'config' => $config,
		];
	}

	/**
	 * Compute consent breakdown (accept/custom/reject rates) from level totals.
	 *
	 * Shared by get_dashboard() and transform_consent_logs().
	 *
	 * @param array $level_totals Associative [ 1 => reject_count, 2 => custom_count, 3 => accept_count ].
	 * @return array { 'total' => int, 'acceptRate' => int, 'customRate' => int, 'rejectRate' => int, 'levelLabels' => array }
	 */
	private function compute_consent_breakdown( $level_totals ) {
		$total = array_sum( $level_totals );

		return [
			'total'      => $total,
			'acceptRate' => $total > 0 ? round( $level_totals[3] / $total * 100 ) : 0,
			'customRate' => $total > 0 ? round( $level_totals[2] / $total * 100 ) : 0,
			'rejectRate' => $total > 0 ? round( $level_totals[1] / $total * 100 ) : 0,
			'levelLabels' => $this->get_level_labels(),
		];
	}

	/**
	 * Read customer-configured consent level labels from cached Designer API data.
	 *
	 * Labels are cached in cookie_notice_app_design by get_app_config() from
	 * DefaultUserTextJSON. Falls back to platform defaults if not yet cached.
	 *
	 * @return array { 'level1' => string, 'level2' => string, 'level3' => string }
	 */
	private function get_level_labels() {
		$cn      = Cookie_Notice();
		$network = $cn->is_network_options();
		$design  = $network
			? get_site_option( 'cookie_notice_app_design', [] )
			: get_option( 'cookie_notice_app_design', [] );

		return [
			'level1' => ! empty( $design['levelNameText_1'] ) ? $design['levelNameText_1'] : 'Private',
			'level2' => ! empty( $design['levelNameText_2'] ) ? $design['levelNameText_2'] : 'Balanced',
			'level3' => ! empty( $design['levelNameText_3'] ) ? $design['levelNameText_3'] : 'Personalized',
		];
	}
}
