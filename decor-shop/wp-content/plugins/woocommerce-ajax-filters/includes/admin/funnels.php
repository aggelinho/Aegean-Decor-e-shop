<?php
if ( ! class_exists( 'braapf_free_funnels' ) ) {
	class braapf_free_funnels {
		function __construct() {
			//delete_option('berocket_admin_notices');
			//update_option('berocket_last_close_notices_time', 1);
			$this->spring_premium_days_2026();
		}

		public function spring_premium_days_2026(): void {
			$plugin_version_capability = apply_filters( 'brfr_get_plugin_version_capability_ajax_filters', 0 );
			$type = ( $plugin_version_capability >= 15 ) ? 'paid' : 'free';

			$name       = 'spring_premium_days_2026';
			$media      = '?utm_source=' . $type . '_plugin&utm_medium=admin_notice&utm_campaign=spring_2026&utm_content=top_banner_cta&utm_term=filters';
			$start_time = mktime(0, 0, 0, 4, 13, 2026);
			$end_time   = mktime(23, 59, 59, 4, 17, 2026);
			$c_notice   = berocket_admin_notices::get_notice_by_priority_and_name( 19, $name );

			$is_closed = false;
			if ( isset( $c_notice['closed'] ) and $c_notice['closed'] > 0 ) {
				$is_closed = true;
			}

			if ( ! $is_closed and $start_time <= time() and $end_time > time() ) {
				new berocket_admin_notices( array(
					'start'     => $start_time,
					'end'       => $end_time,
					'name'      => $name,
					'html'      => '
				<div class="berocket-notice-template-big" style="">
					<div class="berocket-notice-description-container">
						<h1 style="padding-top: 0;line-height: 0.8;">🌿 Spring Premium Days - <b class="br-notice-text-label">Save 30% on Premium</b> Plugins!</h1>
						<h3>Take advantage of Spring to deliver a better shopping experience</h3>
						<ul style="font-size: 16px">
							<li>Advanced product filtering features</li>
							<li>Intelligent (Grouped) Filters with Business license</li>
							<li>Powerful tools to improve store navigation and UX</li>
						</ul>
						<p><i>30% OFF applies to all BeRocket plugins.</i></p>
					</div>
					<div class="berocket-notice-actions-container">
						<div class="berocket-notice-countdown-container">
							<span class="berocket-notice-countdown-title">Hurry Up! Deal ends in:</span>
							<ul class="berocket-notice-countdown-timer" data-endtime="' . ($end_time) . '">
								<li class="berocket-notice-countdown-days"><span>0</span><br />Days</li>
								<li class="berocket-notice-countdown-hours"><span>0</span><br />Hours</li>
								<li class="berocket-notice-countdown-minutes"><span>0</span><br />Minutes</li>
								<li class="berocket-notice-countdown-seconds"><span>0</span><br />Seconds</li>
							</ul>
						</div>
						<div class="berocket-notice-buttons-container">
							<a href="https://berocket.com/woocommerce-ajax-products-filter/' . $media . '" 
							   class="button notice-action-link not_berocket_button" target="_blank" 
							   style="position: static; display: inline-block; right: 0; top: 0; margin: 0;font-size: 24px;padding-left: 25px;padding-right: 25px;">Upgrade Filters → 30% OFF</a>
							<span style="padding: 0 10px;font-size: 16px;"> or </span>
							<a href="https://berocket.com/plugins/' . $media . '" target="_blank" 
							   style="font-size: 18px; text-decoration: none;">Open shop page</a>
						</div> 
					</div>
				</div>',
					'type'      => 'warning',
					'righthtml' => '<span class="berocket-notice-dismiss notice-dismiss berocket_no_thanks" role="button" tabindex="0">
						<span class="screen-reader-text">
						<input class="berocket-notice-dismiss-check" type="checkbox" value="1">Dismiss this notice.
						</span>
					</span>',
					'subscribe' => false,
					'priority'  => 19,
					'image'     => '',
				) );

				$this->br_show_admin_notice_sign();
				$this->br_show_admin_notice_timer();
			}
		}

		public function br_show_admin_notice_sign(): void {
			add_action('admin_head', function () {
				echo "
				<style>
				#toplevel_page_berocket_account .toplevel_page_berocket_account .wp-menu-name:after {
					content: '!';
					display: flex;
					vertical-align: top;
					box-sizing: border-box;
					margin: 0;
					padding: 0 5px;
					min-width: 22px;
					border-radius: 0;
					background-color: #0fc972;
					color: #fff;
					font-size: 18px;
					line-height: 1.6;
					text-align: center;
					z-index: 26;
					position: absolute;
					right: 10px;
					top: 2px;
					bottom: 2px;
					align-items: center;
					justify-content: center;
					font-weight: 600;
				}
				</style>
				";
			});
		}

		public function br_show_admin_notice_timer() {
			add_action('admin_head', function () {
				echo "
				<script>
				function berocket_notice_countdown() {
                    time = jQuery('.berocket-notice-countdown-timer').data('endtime')*1 - Math.floor(Date.now()/1000);
                    berocket_notice_countdown_timer(time);
                }
                function berocket_notice_countdown_timer(time) {
                    formatted_time = berocket_notice_countdown_timer_format_time(time);
                    jQuery('.berocket-notice-countdown-days span').text(formatted_time.days);
                    jQuery('.berocket-notice-countdown-hours span').text(formatted_time.hours);
                    jQuery('.berocket-notice-countdown-minutes span').text(formatted_time.minutes);
                    jQuery('.berocket-notice-countdown-seconds span').text(formatted_time.seconds);
                    if ( time > 0 ) {
                        setTimeout(berocket_notice_countdown_timer, 1000, time-1);
                    }
                }
                function berocket_notice_countdown_timer_format_time(totalSeconds) {
					const days = Math.floor(totalSeconds / 86400);
					totalSeconds %= 86400;
					
					const hours = Math.floor(totalSeconds / 3600);
					totalSeconds %= 3600;
					
					const minutes = Math.floor(totalSeconds / 60);
					const seconds = totalSeconds % 60;
					
					return { days, hours, minutes, seconds };
				}
				jQuery(document).ready(function () {
	                berocket_notice_countdown();
				});
				</script>
				<style>
				.berocket-notice-countdown-container {
					background: #ffd453;
					width: 516px;
					margin: 0 auto;
					border-radius: 10px;
					padding: 13px 0 7px;
				}
				.berocket-notice-countdown-title {
					font-size: 16px;
				}
				.berocket-notice.notice-warning ul.berocket-notice-countdown-timer {
					display: flex;
					gap: 10px;
					justify-content: center;
					margin-bottom: 0;
					padding-left: 10px !important;					
					padding-right: 10px !important;					
				}
				.berocket-notice-countdown-timer li {
					list-style-type: none;
					background: white;
					height: 60px;
					width: 80px;
					border-radius: 10px;
					font-size: 15px;
					padding: 10px 5px;
					box-sizing: border-box;
				}
				.berocket-notice-countdown-timer li span {
					font-size: 24px;
					font-weight: 700;
				}
				</style>
				";
			});
		}
	}

	new braapf_free_funnels();
}