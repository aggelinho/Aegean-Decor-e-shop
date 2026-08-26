<?php
if( class_exists('BeRocket_admin_bar_plugin_data') ) {
    class BeRocket_aapf_admin_bar_debug extends BeRocket_admin_bar_plugin_data {
        function __construct() {
            $BeRocket_AAPF = BeRocket_AAPF::getInstance();
            $this->slug = $BeRocket_AAPF->info['plugin_name'];
            $this->name = $BeRocket_AAPF->info['norm_name'];
            parent::__construct();
        }
        function is_not_footer() {
            $filter_data = BeRocket_AAPF::$current_page_filters;
            unset($filter_data['added']);
            return (count($filter_data) > 0);
        }
        function in_footer() {
            $filter_data = BeRocket_AAPF::$current_page_filters;
            unset($filter_data['added']);
            return (count($filter_data) > 0);
        }
		function get_html() {
            $filter_data = BeRocket_AAPF::$current_page_filters;
            $added_id = $filter_data['added'];
            unset($filter_data['added']);
            $html = '<div class="brapf_admin_link"><a href="https://docs.berocket.com/plugin/woocommerce-ajax-products-filter#how-do-i-check-filter-problems">'.__('How do I check filter problems?', 'BeRocket_AJAX_domain').'</a></div>';
			foreach($filter_data as $data_type => $filter_status) {
				if( count($filter_status) > 0 ) {
					$html2 = '';
					foreach($filter_status as $data_status => $filters) {
						if( count($filters) > 0 ) {
							$html2 .= '<div><h3>'.esc_html(ucfirst(trim(str_replace('_', ' ', $data_status)))).'</h3><ul>';
							foreach($filters as $filter_id => $filter_message) {
								$filter_id = intval($filter_id);
								$title = get_the_title($filter_id);
								if( ! empty($title) ) {
									$filter_message = '('.$title.')'.$filter_message;
								}
								$html2 .= '<li title="'.esc_html($filter_message).'"><a href="'.admin_url('post.php?post='.$filter_id.'&action=edit').'" target="_blank">'.esc_html($filter_id).'</a></li>';
							}
							$html2 .= '</ul></div>';
						}
					}
					if( ! empty($html2) ) {
						$html .= '<div class="bapf_admin_bar_section"><h2>'.esc_html(strtoupper(trim(str_replace('_', ' ', $data_type)))).'</h2>'.$html2.'</div>';
					}
				}
			}
			if( empty($html) ) {
				$html = '<h2>'.__('Filters not detected on page. Please add filter or group as shortcode or widget to display it on page', 'BeRocket_AJAX_domain').'</h2>';
			}
			$html .= '<div class="bapf_adminbar_status">';
			$html .= '</div>';
            $html = apply_filters('BeRocket_aapf_admin_bar_debug_html', $html);
			return $html;
		}
		function get_js() {
			global $br_aapf_wc_footer_widget;
			$html = '<script>
            var berocket_admin_inited = false;
            if( typeof(braapf_admin_error_catch) != "function" ) {
                function braapf_admin_error_catch(is_error, error_name, var1, var2, var3) {
                    var correct_error = false;
                    var critical_error = false;
                    html = "";
                    if(error_name == "same_filters_multiple_times") {
                        html += \'Same filters with ID \'+var1+\' added multiple times to the page\';
                        correct_error = true;
                        critical_error = true;
                    } else if(error_name == "multiple_filters_for_same_taxonomy") {
                        html += \'Multiple filters with taxonomy \'+var1+\' added to the page\';
                        correct_error = true;
                        critical_error = true;
                    } else if(error_name ==  "error_notsame_block_qty") {
                        html += \'New page has another quantity of blocks with selector <span class="bapf_admin_error_code">\'+var1+\'</span><br>\';
                        html += \'Current page: \'+var3+\'<br>\';
                        html += \'New page: \'+var2;
                        correct_error = true;
                        critical_error = true;
                    }
                    if( correct_error ) {
                        brapf_admin_error_bar_add(html, critical_error);
                    }
                    return true;
                }
                if( typeof(berocket_add_filter) == "function" ) {
                    berocket_add_filter("berocket_throw_error", braapf_admin_error_catch, 1);
                } else {
                    jQuery(document).on("berocket_hooks_ready", function() {
                        berocket_add_filter("berocket_throw_error", braapf_admin_error_catch, 1);
                    });
                }
            }
            function brapf_admin_error_bar_add(text, critical_error) {
                if( typeof(critical_error) == "undefined" ) {
                    critical_error = false;
                }
                var html = \'<div><span class="dashicons dashicons-info-outline"></span><p>\';
                html += text;
                html += \'</p></div>\';
                jQuery(".berocket_adminbar_errors").prepend(jQuery(html));
                if( critical_error ) {
                    jQuery(".berocket_adminbar_errors").trigger("critical_error");
                }
            }
            jQuery(document).ready(function() {
				if( ! berocket_admin_inited && typeof(the_ajax_script) != "undefined" && jQuery(".bapf_sfilter").length ) {
                    berocket_admin_inited = true;
					var html = "<h2>STATUS</h2>";
					var products_on_page = '.(is_shop() || is_product_taxonomy() || $br_aapf_wc_footer_widget ? 'true' : 'false').';
					html += "<div class=\'bapf_adminbar_status_element\'>Is WC page";
					html += "<span class=\'dashicons dashicons-'.(is_shop() || is_product_taxonomy() ? 'yes\' title=\'Yes, it is default WooCommerce archive page' : 'no\' title=\'No, it is not WooCommerce archive page').'\'></span>";
					html += "</div>";
					
					html += "<div class=\'bapf_adminbar_status_element\'>Shortcode";
					html += "<span class=\'dashicons dashicons-'.($br_aapf_wc_footer_widget ? 'yes\' title=\'Yes, WooCommerce products shortcode detected' : 'no\' title=\'No, page do not have any custom WooCommerce products').'\'></span>";
					html += "</div>";
					
					html += "<div class=\'bapf_adminbar_status_element\'>Products";
					try {
						var products_elements = jQuery(the_ajax_script.products_holder_id).length;
						var error = false;
						if( products_elements == 0 ) {
							error = "Products element not detected. Please check that selectors setuped correct";
                            if( products_on_page ) {
                                brapf_admin_error_bar_add("Page has products that will be filtered, but products selector is incorrect", true);
                            }
						} else if( products_elements > 1 ) {
							error = "Multiple Products element detected on page("+products_elements+"). It can cause issue on filtering";
						}
						if( error === false ) {
							html += "<span class=\'dashicons dashicons-yes\' title=\'Products element detected on page\'></span>";
						} else {
							html += "<span class=\'dashicons dashicons-no\' title=\'"+error+"\'></span>";
						}
					} catch(e) {
						html = +"<strong>ERROR</strong>";
						console.log(e);
					}
					html += "</div>";
					html += "<div class=\'bapf_adminbar_status_element\'>Pagination";
					try {
						var products_elements = jQuery(the_ajax_script.products_holder_id).length;
						var pagination_elements = jQuery(the_ajax_script.pagination_class).length;
						var error = false;
						if( pagination_elements == 0 ) {
							error = "Pagination element not detected. If page has pagination or infinite scroll/load more button, then Please check that selectors setuped correct";
						} else if( pagination_elements > 1 ) {
							error = "Multiple Pagination element detected on page("+pagination_elements+"). It can cause issue on filtering if pagination from different products list";
						}
						if( error === false ) {
							html += "<span class=\'dashicons dashicons-yes\' title=\'Pagination element detected on page\'></span>";
						} else {
							html += "<span class=\'dashicons dashicons-no\' title=\'"+error+"\'></span>";
						}
					} catch(e) {
						html = +"<strong>ERROR</strong>";
						console.log(e);
					}
					html += "</div>";
					jQuery(".bapf_adminbar_status").html(html);
				}
			});</script>';
			return apply_filters('BeRocket_aapf_admin_bar_debug_js', $html);
		}
		function get_css() {
			$html = '<style>
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters {width: 100%;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .bapf_admin_bar_section,
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status,
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_custom_sidebar{border-top: 1px solid #999; width: 100%; position: relative;margin-bottom: 12px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status,
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_custom_sidebar{padding-top:4px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .bapf_admin_bar_section h2,
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status h2,
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_custom_sidebar h2{position: absolute; top: -10px; left: 0; background: #2c3338;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .bapf_admin_bar_section h3{font-weight:bold;color:#0085ba;font-size: 1.25em;text-align:center;}

            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status .dashicons,
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status_element .dashicons{display:inline-block;}

            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_custom_sidebar a{height:1em;}

			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters ul {display: flex; flex-wrap: wrap; max-width: 300px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters ul li {display:inline-block!important;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters ul li a {height:initial;margin:0;padding:2px;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status,
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_custom_sidebar{text-align:center;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status_element {line-height:2em;display:inline-block;text-align:center; padding:3px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_ajax_filters .bapf_adminbar_status_element
            #wp-admin-bar-berocket_debug_bar .berocket_admin_bar_plugin_block_ajax_filters .brapf_admin_link {text-align:center;}
            #wp-admin-bar-berocket_debug_bar .berocket_admin_bar_plugin_block_ajax_filters .brapf_admin_link a {font-size: 18px;}
			</style>';
			return $html;
		}
    }
    new BeRocket_aapf_admin_bar_debug();
}