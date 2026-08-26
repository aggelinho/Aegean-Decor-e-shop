<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Evaluate notification rules from notifications.json for the wpDashboard slot.
 *
 * @param float  $threshold_used Usage percentage (0-100).
 * @param string $tier           Current tier: 'basic', 'free', or 'pro'.
 * @return array|null Highest-priority matching rule, or null.
 */
function cn_get_dashboard_notification( $threshold_used, $tier ) {
	$rules_json = file_get_contents( COOKIE_NOTICE_PATH . 'includes/notifications.json' );
	$rules_data = $rules_json !== false ? json_decode( $rules_json, true ) : null;

	if ( ! is_array( $rules_data ) || empty( $rules_data['rules'] ) ) {
		return null;
	}

	$best = null;

	foreach ( $rules_data['rules'] as $rule ) {
		if ( ( $rule['slot'] ?? '' ) !== 'wpDashboard' ) {
			continue;
		}

		// Tier check.
		if ( isset( $rule['condition']['tier'] ) && $rule['condition']['tier'] !== $tier ) {
			continue;
		}

		// Usage range check: [inclusive, exclusive).
		if ( isset( $rule['condition']['usagePercent'] ) ) {
			$min = $rule['condition']['usagePercent'][0];
			$max = $rule['condition']['usagePercent'][1];

			if ( $min !== null && $threshold_used < $min ) {
				continue;
			}

			if ( $max !== null && $threshold_used >= $max ) {
				continue;
			}
		}

		if ( ! $best || $rule['priority'] > $best['priority'] ) {
			$best = $rule;
		}
	}

	return $best;
}

/**
 * Cookie_Notice_Dashboard class.
 *
 * @class Cookie_Notice_Dashboard
 */
class Cookie_Notice_Dashboard {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'wp_dashboard_setup', [ $this, 'wp_dashboard_setup' ], 11 );
		add_action( 'wp_network_dashboard_setup', [ $this, 'wp_dashboard_setup' ], 11 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts_styles' ] );

		// site status
		add_filter( 'site_status_tests', [ $this, 'add_tests' ] );
	}

	/**
	 * Initialize widget.
	 *
	 * @global array $wp_meta_boxes
	 *
	 * @return void
	 */
	public function wp_dashboard_setup() {
		// filter user_can_see_stats
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			return;

		// get main instance
		$cn = Cookie_Notice();

		// check when to hide widget
		if ( is_multisite() ) {
			// site dashboard
			if ( current_action() === 'wp_dashboard_setup' && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] )
				return;

			// network dashboard
			if ( current_action() === 'wp_network_dashboard_setup' ) {
				if ( $cn->is_plugin_network_active() ) {
					if ( ! $cn->network_options['general']['global_override'] )
						return;
				} else
					return;
			}
		}

		// check is it network admin
		if ( $cn->is_network_admin() )
			$dashboard_key = 'dashboard-network';
		else
			$dashboard_key = 'dashboard';

		global $wp_meta_boxes;

		// set widget key
		$widget_key = 'cn_dashboard_stats';

		// add dashboard chart widget
		wp_add_dashboard_widget( $widget_key, __( 'Compliance by Hu-manity.co', 'cookie-notice' ), [ $this, 'dashboard_widget' ] );

		// get widgets
		$normal_dashboard = $wp_meta_boxes[$dashboard_key]['normal']['core'];

		// attempt to place the widget at the top
		$widget_instance = [
			$widget_key	=> $normal_dashboard[ $widget_key ]
		];

		// remove new widget
		unset( $normal_dashboard[ $widget_key ] );

		// merge widgets
		$sorted_dashboard = array_merge( $widget_instance, $normal_dashboard );

		// update widgets
		$wp_meta_boxes[$dashboard_key]['normal']['core'] = $sorted_dashboard;
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $pagenow
	 * @return void
	 */
	public function admin_scripts_styles( $pagenow ) {
		if ( $pagenow !== 'index.php' )
			return;

		// filter user_can_see_stats
		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			return;

		// get main instance
		$cn = Cookie_Notice();

		$date_format = get_option( 'date_format' );

		if ( is_multisite() && $cn->is_network_admin() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] )
			$analytics = get_site_option( 'cookie_notice_app_analytics', [] );
		else
			$analytics = get_option( 'cookie_notice_app_analytics', [] );

		// styles
		wp_enqueue_style( 'cookie-notice-admin-dashboard', COOKIE_NOTICE_URL . '/css/admin-dashboard.css', [], $cn->defaults['version'] );
		wp_enqueue_style( 'cookie-notice-microtip', COOKIE_NOTICE_URL . '/assets/microtip/microtip.min.css', [], $cn->defaults['version'] );

		// bail if compliance is not active
		if ( $cn->get_status() !== 'active' )
			return;

		// scripts
		wp_register_script( 'cookie-notice-admin-chartjs', COOKIE_NOTICE_URL . '/assets/chartjs/chart.min.js', [ 'jquery' ], '4.5.1', true );
		wp_enqueue_script( 'cookie-notice-admin-dashboard', COOKIE_NOTICE_URL . '/js/admin-dashboard.js', [ 'jquery', 'cookie-notice-admin-chartjs' ], $cn->defaults['version'], true );

		// cycle usage data
		$cycle_usage = [
			'threshold'	=> ! empty( $analytics['cycleUsage']->threshold ) ? (int) $analytics['cycleUsage']->threshold : 0,
			'visits'	=> ! empty( $analytics['cycleUsage']->visits ) ? (int) $analytics['cycleUsage']->visits : 0
		];

		// no more than threshold available
		$cycle_usage['visits'] = $cycle_usage['visits'] > $cycle_usage['threshold'] ? $cycle_usage['threshold'] : $cycle_usage['visits'];

		// available visits, -1 for no pro plans
		$cycle_usage['visits_available'] = $cycle_usage['threshold'] ? $cycle_usage['threshold'] - $cycle_usage['visits'] : -1;

		// get used threshold info
		if ( $cycle_usage['threshold'] > 0 ) {
			$threshold_used = ( $cycle_usage['visits'] / $cycle_usage['threshold'] ) * 100;

			if ( $threshold_used > 100 )
				$threshold_used = 100;
		} else
			$threshold_used = 0;

		// CN_DEV_MODE: override usage % for testing. Param: ?cn_usage=0-100
		if ( defined( 'CN_DEV_MODE' ) && CN_DEV_MODE && current_user_can( 'manage_options' ) && isset( $_GET['cn_usage'] ) ) {
			$cn_usage_override = (int) $_GET['cn_usage'];

			if ( $cn_usage_override >= 0 && $cn_usage_override <= 100 ) {
				$threshold_used = $cn_usage_override;

				if ( $cycle_usage['threshold'] <= 0 )
					$cycle_usage['threshold'] = 10000;

				$cycle_usage['visits']           = (int) round( $cycle_usage['threshold'] * ( $threshold_used / 100 ) );
				$cycle_usage['visits_available'] = $cycle_usage['threshold'] - $cycle_usage['visits'];
			}
		}

		$chartdata = [
			'usage' => [
				'type'		=> 'doughnut',
				'options'	=> [
					'responsive'	=> true,
					'plugins'		=> [
						'legend' => [
							'position' => 'top'
						]
					],
					'hover'			=> [
						'mode' => 'label'
					],
					'layout'		=> [
						'padding' => 0
					]
				],
				'data'		=> [
					'labels'	=> [
						_x( 'Used', 'threshold limit', 'cookie-notice' ),
						_x( 'Free', 'threshold limit', 'cookie-notice' )
					],
					'datasets'	=> [
						[
							'data'				=> [ $cycle_usage['visits'], $cycle_usage['visits_available'] ],
							'backgroundColor'	=> [
								'rgb(32, 193, 158)',
								'rgb(235, 233, 235)'
							]
						]
					]
				]
			],
			'consent-activity' => [
				'type'		=> 'line',
				'options'	=> [
					'maintainAspectRatio'	=> false,
					'responsive'			=> true,
					'scales'				=> [
						'x'	=> [
							'display'	=> true,
							'title'		=> [
								'display' => false
							]
						],
						'y'	=> [
							'display'		=> true,
							'grace'			=> 0,
							'beginAtZero'	=> true,
							'title'			=> [
								'display' => false
							],
							'ticks'			=> [
								'precision'		=> 0,
								'maxTicksLimit'	=> 12
							]
						]
					]
				]
			],
			'privacy-consent-logs-activity' => [
				'type'		=> 'line',
				'options'	=> [
					'maintainAspectRatio'	=> false,
					'responsive'			=> true,
					'scales'				=> [
						'x'	=> [
							'display'	=> true,
							'title'		=> [
								'display' => false
							]
						],
						'y'	=> [
							'display'		=> true,
							'grace'			=> 0,
							'beginAtZero'	=> true,
							'title'			=> [
								'display' => false
							],
							'ticks'			=> [
								'precision'		=> 0,
								'maxTicksLimit'	=> 12
							]
						]
					],
					'plugins' =>  [
						'legend' => [
							'display' => false
						]
					]
				]
			]
		];

		// warning usage color
		if ( $threshold_used > 80 && $threshold_used < 100 )
			$chartdata['usage']['data']['datasets'][0]['backgroundColor'][0] = 'rgb(255, 193, 7)';
		// danger usage color
		elseif ( $threshold_used == 100 )
			$chartdata['usage']['data']['datasets'][0]['backgroundColor'][0] = 'rgb(220, 53, 69)';

		$consent_activity_data = [
			'labels' => [],
			'datasets' => [
				0 => [
					'label'					=> sprintf( __( 'Level %s', 'cookie-notice' ), 1 ),
					'data'					=> [],
					'fill'					=> true,
					'backgroundColor'		=> 'rgba(196, 196, 196, 0.3)',
					'borderColor'			=> 'rgba(196, 196, 196, 1)',
					'borderWidth'			=> 1.2,
					'borderDash'			=> [],
					'pointBorderColor'		=> 'rgba(196, 196, 196, 1)',
					'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
					'pointBorderWidth'		=> 1.2
				],
				1 => [
					'label'					=> sprintf( __( 'Level %s', 'cookie-notice' ), 2 ),
					'data'					=> [],
					'fill'					=> true,
					'backgroundColor'		=> 'rgba(213, 181, 101, 0.3)',
					'borderColor'			=> 'rgba(213, 181, 101, 1)',
					'borderWidth'			=> 1.2,
					'borderDash'			=> [],
					'pointBorderColor'		=> 'rgba(213, 181, 101, 1)',
					'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
					'pointBorderWidth'		=> 1.2
				],
				2 => [
					'label'					=> sprintf( __( 'Level %s', 'cookie-notice' ), 3 ),
					'data'					=> [],
					'fill'					=> true,
					'backgroundColor'		=> 'rgba(152, 145, 177, 0.3)',
					'borderColor'			=> 'rgba(152, 145, 177, 1)',
					'borderWidth'			=> 1.2,
					'borderDash'			=> [],
					'pointBorderColor'		=> 'rgba(152, 145, 177, 1)',
					'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
					'pointBorderWidth'		=> 1.2
				]
			]
		];

		// generate chart days
		$chart_date_format = 'j/m';

		for ( $i = 29; $i >= 0; $i-- ) {
			// set label
			$consent_activity_data['labels'][] = date( $chart_date_format, strtotime( '-'. ( $i + 1 ) .' days' ) );

			// reset datasets
			$consent_activity_data['datasets'][0]['data'][] = 0;
			$consent_activity_data['datasets'][1]['data'][] = 0;
			$consent_activity_data['datasets'][2]['data'][] = 0;
		}

		if ( ! empty( $analytics['consentActivities'] ) && is_array( $analytics['consentActivities'] ) ) {
			// set consent records in charts days
			foreach ( $analytics['consentActivities'] as $index => $entry ) {
				$time = date_i18n( $chart_date_format, strtotime( $entry->eventdt ) );
				$i = array_search( $time, $consent_activity_data['labels'] );

				if ( $i !== false )
					$consent_activity_data['datasets'][(int) $entry->consentlevel - 1]['data'][$i] = (int) $entry->totalrecd;
			}
		}

		$chartdata['consent-activity']['data'] = $consent_activity_data;

		$privacy_consent_logs_activity_data = [
			'labels' => [],
			'datasets' => [
				0 => [
					'label'					=> __( 'Privacy Content Logs', 'cookie-notice' ),
					'data'					=> [],
					'fill'					=> true,
					'backgroundColor'		=> 'rgba(32, 193, 158, 0.3)',
					'borderColor'			=> 'rgba(32, 193, 158, 1)',
					'borderWidth'			=> 1.2,
					'borderDash'			=> [],
					'pointBorderColor'		=> 'rgba(32, 193, 158, 1)',
					'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
					'pointBorderWidth'		=> 1.2
				]
			]
		];

		for ( $i = 29; $i >= 0; $i-- ) {
			// set label
			$privacy_consent_logs_activity_data['labels'][] = date( $chart_date_format, strtotime( '-'. ( $i + 1 ) .' days' ) );

			// reset dataset
			$privacy_consent_logs_activity_data['datasets'][0]['data'][] = 0;
		}

		if ( ! empty( $analytics['privacyActivities'] ) && is_array( $analytics['privacyActivities'] ) ) {
			// set consent records in charts days
			foreach ( $analytics['privacyActivities'] as $index => $entry ) {
				$time = date_i18n( $chart_date_format, strtotime( $entry->date ) );
				$i = array_search( $time, $privacy_consent_logs_activity_data['labels'] );

				if ( $i !== false )
					$privacy_consent_logs_activity_data['datasets'][0]['data'][$i] = (int) $entry->count;
			}
		}

		$chartdata['privacy-consent-logs-activity']['data'] = $privacy_consent_logs_activity_data;

		// prepare script data
		$script_data = [
			'ajaxURL'	=> admin_url( 'admin-ajax.php' ),
			'nonce'		=> wp_create_nonce( 'cn-dashboard-widget' ),
			'nonceUser'	=> wp_create_nonce( 'cn-dashboard-user-options' ),
			'charts'	=> $chartdata
		];

		wp_add_inline_script( 'cookie-notice-admin-dashboard', 'var cnDashboardArgs = ' . wp_json_encode( $script_data ) . ";\n", 'before' );
	}

	/**
	 * Render dashboard widget.
	 *
	 * @return void
	 */
	public function dashboard_widget() {
		// get main instance
		$cn = Cookie_Notice();

		if ( $cn->is_network_admin() )
			$upgrade_url = network_admin_url( 'admin.php?page=cookie-notice&welcome=1' );
		else
			$upgrade_url = admin_url( 'admin.php?page=cookie-notice&welcome=1' );

		$html = '';

		// compliance active, display chart
		if ( $cn->get_status() === 'active' ) {
			// get user options
			$user_options = get_user_meta( get_current_user_id(), 'pvc_dashboard', true );

			// empty options?
			if ( empty( $user_options ) || ! is_array( $user_options ) )
				$user_options = [];

			// sanitize options
			$user_options = map_deep( $user_options, 'sanitize_text_field' );

			// get menu items
			$menu_items = ! empty( $user_options['menu_items'] ) ? $user_options['menu_items'] : [];

			$items = [
				[
					'id'			=> 'visits',
					'title'			=> esc_html__( 'Traffic Overview', 'cookie-notice' ),
					'description'	=> esc_html__( 'Displays the general visits information for your domain.', 'cookie-notice' )
				],
				[
					'id'			=> 'consent-activity',
					'title'			=> esc_html__( 'Cookie Consent Activity', 'cookie-notice' ),
					'description'	=> esc_html__( 'Displays the chart of the domain cookie consent activity in the last 30 days.', 'cookie-notice' )
				],
				[
					'id'			=> 'privacy-consent-logs-activity',
					'title'			=> esc_html__( 'Privacy Consent Activity', 'cookie-notice' ),
					'description'	=> esc_html__( 'Displays the chart of the domain privacy consent activity in the last 30 days.', 'cookie-notice' )
				]
			];

			$html .= '
			<div id="cn-dashboard-accordion" class="cn-accordion">';

			foreach ( $items as $item ) {
				$html .= $this->widget_item( $item, $menu_items );
			}

			$html .= '
			</div>';
		// compliance inactive, display image
		} else {
			$html .= '
			<div id="cn-dashboard-accordion" class="cn-accordion cn-widget-block">
				<img src="' . esc_url( COOKIE_NOTICE_URL ) . '/img/cookie-compliance-widget.png" alt="Compliance by Hu-manity.co widget" />
				<div id="cn-dashboard-upgrade">
					<div id="cn-dashboard-modal">
						<h2>' . esc_html__( 'View consent activity inside WordPress Dashboard', 'cookie-notice' ) . '</h2>
						<p>' . esc_html__( 'Display information about the visits.', 'cookie-notice' ) . '</p>
						<p>' . esc_html__( 'Get Consent logs data for the last 30 days.', 'cookie-notice' ) . '</p>
						<p>' . esc_html__( 'Enable consent purpose categories, automatic cookie blocking and more.', 'cookie-notice' ) . '</p>
						<p><a href="' . esc_url( $upgrade_url ) . '" class="button button-primary button-hero cn-button">' . esc_html__( 'Try Compliance by Hu-manity.co free', 'cookie-notice' ) . '</a></p>
					</div>
				</div>
			</div>';
		}

		// allows a list of html entities such as
		$allowed_html = wp_kses_allowed_html( 'post' );
		$allowed_html['canvas'] = [
			'id'		=> true,
			'height'	=> true
		];

		echo wp_kses( $html, $allowed_html );
	}

	/**
	 * Generate dashboard widget item HTML.
	 *
	 * @param array $item
	 * @param array $menu_items
	 * @return string
	 */
	public function widget_item( $item, $menu_items ) {
		return '
		<div id="cn-' . esc_attr( $item['id'] ) . '" class="cn-accordion-item' . ( in_array( $item['id'], $menu_items, true ) ? ' cn-collapsed' : '' ) . '">
			<div class="cn-accordion-header">
				<div class="cn-accordion-toggle"><span class="cn-accordion-title">' . esc_html( $item['title'] ) . '</span><span class="cn-tooltip" aria-label="' . esc_attr( $item['description'] ) . '" data-microtip-position="top" data-microtip-size="large" role="tooltip"><span class="cn-tooltip-icon"></span></span></div>
			</div>
			<div class="cn-accordion-content">
				<div class="cn-dashboard-container">
					<div class="cn-data-container">
						' . $this->widget_item_content( $item['id'] ) . '
						<span class="spinner"></span>
					</div>
				</div>
			</div>
		</div>';
	}

	/**
	 * Generate dashboard widget item content HTML.
	 *
	 * @param array $item
	 * @return void
	 */
	public function widget_item_content( $item ) {
		$html = '';

		switch ( $item ) {
			case 'visits':
				// get main instance
				$cn = Cookie_Notice();

				$date_format = get_option( 'date_format' );

				// get analytics data options
				if ( is_multisite() && $cn->is_network_admin() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] )
					$analytics = get_site_option( 'cookie_notice_app_analytics', [] );
				else
					$analytics = get_option( 'cookie_notice_app_analytics', [] );

				// thirty days data
				$thirty_days_usage = [
					'visits'			=> ! empty( $analytics['thirtyDaysUsage']->visits ) ? (int) $analytics['thirtyDaysUsage']->visits : 0,
					'consents'			=> 0,
					'consents_updated'	=> ! empty( $analytics['lastUpdated'] ) ? date_create_from_format( 'Y-m-d H:i:s', $analytics['lastUpdated'] ) : date_create_from_format( 'Y-m-d H:i:s', current_time( 'mysql', true ) )
				];

				// set current timezone
				$current_timezone = new DateTimeZone( $this->timezone_string() );

				// update date
				$thirty_days_usage['consents_updated']->setTimezone( $current_timezone );

				if ( ! empty( $analytics['consentActivities'] ) ) {
					foreach ( $analytics['consentActivities'] as $index => $entry ) {
						$thirty_days_usage['consents'] += (int) $entry->totalrecd;
					}
				}

				// cycle usage data
				$cycle_usage = [
					'threshold'		=> ! empty( $analytics['cycleUsage']->threshold ) ? (int) $analytics['cycleUsage']->threshold : 0,
					'visits'		=> ! empty( $analytics['cycleUsage']->visits ) ? (int) $analytics['cycleUsage']->visits : 0,
					'days_to_go'	=> ! empty( $analytics['cycleUsage']->daysToGo ) ? (int) $analytics['cycleUsage']->daysToGo : 0,
					'start_date'	=> ! empty( $analytics['cycleUsage']->startDate ) ? date_create_from_format( '!Y-m-d', $analytics['cycleUsage']->startDate ) : ''
				];

				// get used threshold info
				if ( $cycle_usage['threshold'] > 0 ) {
					$threshold_used = ( $cycle_usage['visits'] / $cycle_usage['threshold'] ) * 100;

					if ( $threshold_used > 100 )
						$threshold_used = 100;
				} else
					$threshold_used = 0;

				// CN_DEV_MODE: override usage % for testing. Param: ?cn_usage=0-100
				if ( defined( 'CN_DEV_MODE' ) && CN_DEV_MODE && current_user_can( 'manage_options' ) && isset( $_GET['cn_usage'] ) ) {
					$cn_usage_override = (int) $_GET['cn_usage'];

					if ( $cn_usage_override >= 0 && $cn_usage_override <= 100 ) {
						$threshold_used = $cn_usage_override;

						if ( $cycle_usage['threshold'] <= 0 )
							$cycle_usage['threshold'] = 10000;

						$cycle_usage['visits']           = (int) round( $cycle_usage['threshold'] * ( $threshold_used / 100 ) );
						$cycle_usage['visits_available'] = $cycle_usage['threshold'] - $cycle_usage['visits'];
					}
				}

				$html .= '
					<div id="cn-dashboard-' . esc_attr( $item ) . '">
						<div id="cn-' . esc_attr( $item ) . '-infobox-traffic-overview" class="cn-infobox-container">
							<div id="cn-' . esc_attr( $item ) . '-infobox-visits" class="cn-infobox">
								<div class="cn-infobox-title">' . esc_html__( 'Total Visits', 'cookie-notice' ) . '</div>
								<div class="cn-infobox-number">' . esc_html( number_format_i18n( $thirty_days_usage['visits'], 0 ) ) . '</div>
								<div class="cn-infobox-subtitle">' . esc_html__( 'Last 30 days', 'cookie-notice' ) . '</div>
							</div>
							<div id="cn-' . esc_attr( $item ) . '-infobox-consents" class="cn-infobox">
								<div class="cn-infobox-title">' . esc_html__( 'Consent Logs', 'cookie-notice' ) . '</div>
								<div class="cn-infobox-number">' . esc_html( number_format_i18n( $thirty_days_usage['consents'], 0 ) ) . '</div>
								<div class="cn-infobox-subtitle">' . esc_html( sprintf( __( 'Updated %s', 'cookie-notice' ), date_i18n( $date_format, $thirty_days_usage['consents_updated']->getTimestamp() ) ) ) . '</div>
							</div>
						</div>';

				if ( $cycle_usage['threshold'] ) {
					$usage_class = 'success';

					// warning usage color
					if ( $threshold_used > 80 && $threshold_used < 100 )
						$usage_class = 'warning';
					// danger usage color
					elseif ( $threshold_used === 100 )
						$usage_class = 'danger';

					$html .= '
						<div id="cn-' . esc_attr( $item ) . '-infobox-traffic-usage" class="cn-infobox-container">
							<div id="cn-' . esc_attr( $item ) . '-infobox-limits" class="cn-infobox">
								<div class="cn-infobox-title">' . esc_html__( 'Traffic Usage', 'cookie-notice' ) . '</div>
								<div class="cn-infobox-number cn-text-' . esc_attr( $usage_class ) . '">' . esc_html( number_format_i18n( $threshold_used, 1 ) ) . ' %</div>
								<div class="cn-infobox-subtitle">
									<p>' . esc_html( sprintf( __( 'Visits usage: %1$s / %2$s', 'cookie-notice' ), $cycle_usage['visits'], $cycle_usage['threshold'] ) ) . '</p>
									<p>' . esc_html( sprintf( __( 'Cycle started: %s', 'cookie-notice' ), date_i18n( $date_format, $cycle_usage['start_date']->getTimestamp() ) ) ) . '</p>
									<p>' . esc_html( sprintf( __( 'Days to go: %s', 'cookie-notice' ), $cycle_usage['days_to_go'] ) ) . '</p>
								</div>
							</div>
							<div id="cn-' . esc_attr( $item ) . '-chart-limits" class="cn-infobox cn-chart-container">
								<canvas id="cn-usage-chart" style="height: 100px"></canvas>
							</div>';

					// Near-limit nudge — reads copy from shared notifications.json.
					$cn_dash_tier = 'basic';
					if ( ! empty( $cn->options['general']['app_id'] ) ) {
						$cn_dash_tier = ( $cn->get_subscription() === 'pro' ) ? 'pro' : 'free';
					}
					$cn_dash_notification = cn_get_dashboard_notification( $threshold_used, $cn_dash_tier );

					if ( $cn_dash_notification ) {
						$react_welcome_url = $cn->is_network_admin()
							? network_admin_url( 'admin.php?page=cookie-notice&cn_react_welcome=1' )
							: admin_url( 'admin.php?page=cookie-notice&cn_react_welcome=1' );

						$cn_dash_desc = str_replace(
							[ '{usagePercent}', '{sessionTotal}', '{sessionUsed}' ],
							[ number_format_i18n( $threshold_used, 0 ), number_format_i18n( $cycle_usage['threshold'] ), number_format_i18n( $cycle_usage['visits'] ) ],
							$cn_dash_notification['description'] ?? ''
						);
						$cn_dash_cta_label = $cn_dash_notification['cta']['label'] ?? 'Upgrade &rarr;';

						$html .= '
							<div id="cn-' . esc_attr( $item ) . '-traffic-notice" class="cn-infobox-notice">
								<p><b>' . esc_html( $cn_dash_desc ) . '</b></p>
								<p><a href="' . esc_url( $react_welcome_url ) . '">' . esc_html( $cn_dash_cta_label ) . '</a></p>
							</div>';
					}

					$html .= '
						</div>';
				}

				$html .= '
					</div>';
				break;

			case 'consent-activity':
				$html .= '
					<div id="cn-dashboard-' . esc_attr( $item ) . '">
						<div id="cn-' . esc_attr( $item ) . '-chart-container cn-chart-container">
							<canvas id="cn-' . esc_attr( $item ) . '-chart" style="height: 300px"></canvas>
						</div>
					</div>';
				break;

			case 'privacy-consent-logs-activity':
				$html .= '
					<div id="cn-dashboard-' . esc_attr( $item ) . '">
						<div id="cn-' . esc_attr( $item ) . '-chart-container cn-chart-container">
							<canvas id="cn-' . esc_attr( $item ) . '-chart" style="height: 300px"></canvas>
						</div>
					</div>';
				break;
		}

		return $html;
	}

	/**
	 * Add site test.
	 *
	 * @param array $tests
	 * @return array
	 */
	public function add_tests( $tests ) {
		$tests['direct']['cookie_compliance_status'] = [
			'label'	=> esc_html__( 'Compliance by Hu-manity.co Status', 'cookie-notice' ),
			'test'	=> [ $this, 'test_cookie_compliance' ]
		];

		return $tests;
	}

	/**
	 * Test for Cookie Compliance.
	 *
	 * @return array|void
	 */
	public function test_cookie_compliance() {
		if ( Cookie_Notice()->get_status() !== 'active' ) {
			return [
				'label'			=> esc_html__( 'Your site does not have Compliance by Hu-manity.co', 'cookie-notice' ),
				'status'		=> 'recommended',
				'description'	=> esc_html__( "Run Compliance Check to determine your site's compliance with updated data processing and consent rules under GDPR, CCPA and other international data privacy laws.", 'cookie-notice' ),
				'actions'		=> sprintf( '<p><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>', admin_url( 'admin.php?page=cookie-notice&welcome=1' ), esc_html__( 'Run Compliance Check', 'cookie-notice' ) ),
				'test'			=> 'cookie_compliance_status',
				'badge'			=> [
					'label'	=> esc_html__( 'Compliance', 'cookie-notice' ),
					'color'	=> 'blue'
				]
			];
		} else {
			return [
				'label'			=> esc_html__( 'Compliance by Hu-manity.co is active', 'cookie-notice' ),
				'status'		=> 'good',
				'description'	=> esc_html__( 'Compliance by Hu-manity.co is configured with active Compliance by Hu-manity.co protection. Your site is collecting consent in accordance with GDPR, CCPA, and other applicable privacy laws.', 'cookie-notice' ),
				'actions'		=> sprintf( '<p><a href="%s">%s</a></p>', admin_url( 'admin.php?page=cookie-notice' ), esc_html__( 'View compliance dashboard', 'cookie-notice' ) ),
				'test'			=> 'cookie_compliance_status',
				'badge'			=> [
					'label'	=> esc_html__( 'Compliance', 'cookie-notice' ),
					'color'	=> 'green'
				]
			];
		}
	}

	/**
	 * Retrieve the timezone of the site as a string.
	 *
	 * @return string
	 */
	public function timezone_string() {
		if ( function_exists( 'wp_timezone_string' ) )
			return wp_timezone_string();

		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string )
			return $timezone_string;

		$offset = (float) get_option( 'gmt_offset' );
		$hours = (int) $offset;
		$minutes = ( $offset - $hours );
		$sign = ( $offset < 0 ) ? '-' : '+';
		$abs_hour = abs( $hours );
		$abs_mins = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}
}
