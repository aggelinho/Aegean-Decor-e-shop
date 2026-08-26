<?php
if( ! class_exists('BeRocket_admin_bar_debug') ) {
    class BeRocket_admin_bar_debug{
		public $footer_run = false, $admin_bar_run = false;
        public $plugins_data = false;
        function __construct() {
            if( empty($_GET['et_fb']) ) {
                add_action( 'admin_bar_menu', array($this, 'debug_admin_bar_menu'), 1000 );
                add_action( 'wp_footer', array($this, 'footer_bar'), 1000 );
            }
        }
        function get_plugins_data() {
            if( $this->plugins_data === false ) {
                $this->plugins_data = apply_filters('berocket_admin_bar_plugins_data', array());
            }
            return $this->plugins_data;
        }
		function footer_bar() {
			$this->footer_run = true;
            if ( ! current_user_can( 'edit_posts' ) ) return;
            $plugins_data = $this->get_plugins_data();
            foreach($plugins_data as $plugin_slug => $plugin_name) {
                if( apply_filters('berocket_admin_bar_in_footer_' . $plugin_slug, false) ) {
                    echo '<div style="display: none;">';
                    echo '<div class="berocket_wp_admin_bar_replacement' . $plugin_slug . '">';
                    echo apply_filters('berocket_admin_bar_html_' . $plugin_slug, '');
                    echo '</div>';
                    echo '<script>try{ jQuery(".berocket_wp_admin_bar_replace' . $plugin_slug . '").replaceWith(jQuery(".berocket_wp_admin_bar_replacement' . $plugin_slug . '")); } catch(e){}</script>';
                    echo apply_filters('berocket_admin_bar_js_' . $plugin_slug, '');
                    echo apply_filters('berocket_admin_bar_css_' . $plugin_slug, '');
                    echo '</div>';
                }
            }
		}
        function debug_admin_bar_menu() {
			$this->admin_bar_run = true;
            global $wp_admin_bar, $wpdb;
            if ( ! current_user_can( 'edit_posts' ) ) return;
            $html = '';
            $plugins_data = $this->get_plugins_data();
            if( ! $plugins_data || count($plugins_data) == 0 ) return;
            foreach($plugins_data as $plugin_slug => $plugin_name) {
                $html .= '<h2 class="berocket_admin_bar_plugin_header">' . $plugin_name . '<span class="dashicons dashicons-arrow-down-alt2"></span></h2>';
                $html .= '<div class="berocket_admin_bar_plugin_block berocket_admin_bar_plugin_block_'.$plugin_slug.'" style="display:none;">';
                if( apply_filters('berocket_admin_bar_footer_' . $plugin_slug, true) ) {
                    $html .= apply_filters('berocket_admin_bar_html_' . $plugin_slug, '');
                    $html .= apply_filters('berocket_admin_bar_js_' . $plugin_slug, '');
                    $html .= apply_filters('berocket_admin_bar_css_' . $plugin_slug, '');
                } else {
                    $html .= '<div class="berocket_wp_admin_bar_replace' . $plugin_slug . '">Filters cannot be detected</div>';
                }
                $html .= '</div>';
            }
			$html .= '<div class="berocket_adminbar_errors">';
			$html .= '</div>';
			$html .= $this->get_css();
			$html .= $this->get_js();
            $title = '<img style="width:22px;height:22px;display:inline;" src="' . plugin_dir_url(BeRocket_framework_file) . '/includes/ico.png" alt="">BeRocket';
            $wp_admin_bar->add_menu( array( 'id' => 'berocket_debug_bar', 'title' => $title, 'href' => FALSE ) );
            $wp_admin_bar->add_menu( array( 'id' => 'berocket_debug_bar_content', 'parent' => 'berocket_debug_bar', 'title' => $html, 'href' => FALSE ) );
        }
        function get_css() {
            $html = '<style>
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_adminbar_errors {text-align:center; max-height: 200px; min-height:40px; overflow: auto; margin-left: -10px; margin-right: -10px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_adminbar_errors > div {display: flex; border-top: 1px solid #555;text-align:left;align-items: center;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_adminbar_errors > div p {padding: 3px;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_adminbar_errors .dashicons {font-family: dashicons;font-size: 34px;line-height: 34px;display: block;cursor:pointer;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_adminbar_errors .dashicons-info-outline {color: red; transform: rotate(180deg);}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_adminbar_errors .bapf_admin_error_code {color: #777; background-color:#ccc; line-height:1em; display:inline-block; padding: 3px;}
            #wp-admin-bar-berocket_debug_bar.berocket_adminbar_errors_alert {animation-duration: 3s;animation-name: berocket_debug_bar;}
            @keyframes berocket_debug_bar {
              0% {
                background-color: #23282d;
              }
              10% {
                background-color: #ee3333;
              }
              20% {
                background-color: #23282d;
              }
              30% {
                background-color: #ee3333;
              }
              40% {
                background-color: #23282d;
              }
              50% {
                background-color: #ee3333;
              }
              60% {
                background-color: #23282d;
              }
              70% {
                background-color: #ee3333;
              }
              80% {
                background-color: #23282d;
              }
              90% {
                background-color: #ee3333;
              }
              100% {
                background-color: #23282d;
              }
            }
            #wp-admin-bar-berocket_debug_bar img {margin-right: 6px;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .dashicons {font-family: dashicons;font-size: 34px;line-height: 26px;display: block;cursor:pointer;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_header .dashicons {display:inline-block; font-size:1em; line-height:2em;}
			#wp-admin-bar-berocket_debug_bar.berocket_adminbar_errors_alert .ab-item .dashicons.dashicons-info-outline {margin-left: 5px;font-family: dashicons;font-size: 24px;line-height:32px;cursor:pointer;color: red; transform: rotate(180deg);}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .dashicons-yes {color:green;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .dashicons-no {color:red;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item *{line-height:1em;color:#ccc;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item h2{color:white;font-size: 1.5em;text-align:center;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item {height:initial!important;line-height:1em;}
			#wp-admin-bar-berocket_debug_bar .ab-item {display: flex!important;align-items: center;flex-direction: row;}
			#wp-admin-bar-berocket_debug_bar #wp-admin-bar-berocket_debug_bar_content .ab-item {display: flex!important;align-items: center;flex-direction: column; max-height:80vh;overflow:auto;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_header {line-height:2em; cursor:pointer; width: 100%;}
            </style>';
            return $html;
        }
        function get_js() {
            $html = '<script>
            jQuery(document).on("critical_error", "#wp-admin-bar-berocket_debug_bar .berocket_adminbar_errors", function() {
                jQuery("#wp-admin-bar-berocket_debug_bar").removeClass("berocket_adminbar_errors_alert");
                setTimeout(function() {jQuery("#wp-admin-bar-berocket_debug_bar").addClass("berocket_adminbar_errors_alert")});
                jQuery("#wp-admin-bar-berocket_debug_bar > .ab-item .dashicons").remove();
                jQuery("#wp-admin-bar-berocket_debug_bar > .ab-item").append(jQuery(\'<span class="dashicons dashicons-info-outline"></span>\'));
            });
            jQuery(document).on("click", ".berocket_admin_bar_plugin_header", function() {
                jQuery(".berocket_admin_bar_plugin_block").hide();
                if( jQuery(this).find(".dashicons").is(".dashicons-arrow-down-alt2") ) {
                    jQuery(".berocket_admin_bar_plugin_header .dashicons").removeClass("dashicons-arrow-up-alt2").addClass("dashicons-arrow-down-alt2");
                    jQuery(this).find(".dashicons").removeClass("dashicons-arrow-down-alt2").addClass("dashicons-arrow-up-alt2");
                    jQuery(this).next(".berocket_admin_bar_plugin_block").show();
                } else {
                    jQuery(".berocket_admin_bar_plugin_header .dashicons").removeClass("dashicons-arrow-up-alt2").addClass("dashicons-arrow-down-alt2");
                }
            });
            </script>';
            return $html;
        }
	}
    new BeRocket_admin_bar_debug();
}
if( ! class_exists('BeRocket_admin_bar_plugin_data') ) {
    class BeRocket_admin_bar_plugin_data {
        public $name = 'Main', $slug = 'main';
        function __construct() {
            add_filter('berocket_admin_bar_plugins_data', array($this, 'add_plugin_data'));
            add_filter('berocket_admin_bar_html_'.$this->slug, array($this, 'get_html'));
            add_filter('berocket_admin_bar_css_'.$this->slug, array($this, 'get_css'));
            add_filter('berocket_admin_bar_js_'.$this->slug, array($this, 'get_js'));
            add_filter('berocket_admin_bar_footer_'.$this->slug, array($this, 'is_not_footer'));
            add_filter('berocket_admin_bar_in_footer_'.$this->slug, array($this, 'in_footer'));
        }
        function add_plugin_data($plugins) {
            $plugins[$this->slug] = $this->name;
            return $plugins;
        }
        function get_html() {
            return '';
        }
        function get_css() {
            return '';
        }
        function get_js() {
            return '';
        }
        function is_not_footer() {
            return true;
        }
        function in_footer() {
            return false;
        }
    }
}