<?php
if( ! class_exists('BeRocket_AAPF_compat_js_composer') ) {
    class BeRocket_AAPF_compat_js_composer {
        public $is_enabled = false;
        function __construct() {
            add_filter('vc_element_settings_filter', array($this, 'add_option'), 10, 2);
            add_filter('pre_do_shortcode_tag', array($this, 'builder_check'), 100, 3);
            add_filter('do_shortcode_tag', array($this, 'builder_check_replace'), 100, 3);
            add_action('wp_footer', array($this, 'custom_js'));
            add_action('wp_ajax_vc_get_vc_grid_data', array($this, 'init'), 1);
            add_action('wp_ajax_nopriv_vc_get_vc_grid_data', array($this, 'init'), 1);
            add_filter('aapf_localize_widget_script', array($this, 'modify_products_selector'));
        }
        function modify_products_selector($args) {
            if( ! empty($args['products_holder_id']) ) {
                $args['products_holder_id'] .= ',';
            }
            $args['products_holder_id'] .= '.brapf_wpb_replace_grid.vc_grid-container';
            return $args;
        }
        public $brfilter_ajax_list = array(
            'group' => array(),
            'single' => array()
        );
        function init() {
            if( ! empty($_REQUEST['tag']) && ('vc_basic_grid' == $_REQUEST['tag'] || 'vc_masonry_grid' == $_REQUEST['tag'])
            && ! empty($_REQUEST['data']) && is_array($_REQUEST['data']) && isset($_REQUEST['data']['brfilter']) ) {
                $bapf_apply = true;
                bapf_set_filter_field_ajax($_REQUEST['data']['brfilter']);
                $this->builder_parameter_apply($bapf_apply);
                add_filter('vc_basic_grid_filter_query_suppress_filters', array($this, 'enable_filters'));
                if( isset($_REQUEST['data']['brfilter_list'] ) && is_array($_REQUEST['data']['brfilter_list']) ) {
                    foreach($_REQUEST['data']['brfilter_list'] as $filter) {
                        $filter['wid'] = intval($filter['wid']);
                        $filter['id'] = intval($filter['id']);
                        if( ! empty($filter['wid']) ) {
                            if( ! in_array($filter['wid'], $this->brfilter_ajax_list['group']) ) {
                                $this->brfilter_ajax_list['group'][] = $filter['wid'];
                            }
                        } else {
                            if( ! in_array($filter['id'], $this->brfilter_ajax_list['single']) ) {
                                $this->brfilter_ajax_list['single'][] = $filter['id'];
                            }
                        }
                    }
                    add_filter('vc_get_vc_grid_data_response', array($this, 'add_filters_ajax'));
                }
            }
        }
        function enable_filters() {
            return false;
        }
        function add_filters_ajax($output) {
            ob_start();
            $output .= '<div class="bapf_ajax_load_replace" style="display: none;">';
            foreach($this->brfilter_ajax_list['group'] as $group_id) {
                $output .= do_shortcode('[br_filters_group group_id='.$group_id.']');
            }
            foreach($this->brfilter_ajax_list['single'] as $filter_id) {
                $output .= do_shortcode('[br_filter_single filter_id='.$filter_id.']');
            }
            $output .= '<script>jQuery(document).trigger("bapf_ajax_load_replace");</script>';
            $output .= '</div>';
            $output .= ob_get_clean();
            return $output;
        }
        function builder_check_replace($output, $tag, $attr) {
            if( 'vc_basic_grid' == $tag || 'vc_masonry_grid' == $tag ) {
                $bapf_apply = empty($attr['aapf_apply']) ? '' : $attr['aapf_apply'];
                $this->builder_parameter_apply($bapf_apply);
                if( $this->is_enabled ) {
                    $replace = false;
                    $firstpos = strpos($output, 'class="');
                    $secondpos = strpos($output, "class='");
                    if( $firstpos !== false || $secondpos !== false ) {
                        if( $secondpos === false || ($firstpos !== false && $firstpos <= $secondpos) ) {
                            $replace = 'class="';
                        } elseif( $firstpos === false || ($secondpos !== false && $secondpos <= $firstpos) ) {
                            $replace = "class='";
                        }
                    }
                    $count_str = 1;
                    $output = str_replace($replace, $replace.'brapf_wpb_replace_grid ', $output, $count_str);
                }
            }
            return $output;
        }
        function builder_check($return, $tag, $attr) {
            if( ! $return && ('vc_basic_grid' == $tag || 'vc_masonry_grid' == $tag) ) {
                $bapf_apply = empty($attr['aapf_apply']) ? '' : $attr['aapf_apply'];
                $this->builder_parameter_apply($bapf_apply);
            }
            return $return;
        }
        function builder_parameter_apply($aapf_apply) {
            $enabled = braapf_is_shortcode_must_be_filtered();
            if( ! empty($aapf_apply) && $aapf_apply == 'enable' ) {
                $enabled = true;
            } elseif( ! empty($aapf_apply) && $aapf_apply == 'disable' ) {
                $enabled = false;
            }
            $this->is_enabled = $enabled;
            do_action('brapf_next_shortcode_apply_action', array('apply' => $enabled));
        }
        function add_option($settings, $tag) {
            if( $tag == 'vc_basic_grid' || $tag == 'vc_masonry_grid' ) {
                $new_option = array(
                    'aapf_apply' => array(
                        "type" => "dropdown",
                        "heading" => esc_html__( 'Apply BeRocket AJAX Filters', "BeRocket_AJAX_domain" ),
                        "param_name" => "aapf_apply",
                        "value" => array(
                            array(
                                'default',
                                esc_html__( 'Default', 'BeRocket_AJAX_domain' )
                            ),
                            array(
                                'enable',
                                esc_html__( 'Enable', 'BeRocket_AJAX_domain' )
                            ),
                            array(
                                'disable',
                                esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                            ),
                        ),
                        "save_always" => false,
                        "description" => esc_html__( 'All Filters will be applied to this module. You need correct unique selectors to work correct', 'BeRocket_AJAX_domain' ),
                        "dependency" => array(
                            "element" => "post_type",
                            "value" => array( "product" )
                        )
                    ),
                );
                $position_id = 0;
                $settings['params'] = berocket_insert_to_array(
                    $settings['params'],
                    $position_id,
                    $new_option
                );
            } elseif( $tag == 'products' ) {
                $new_option = array(
                    'berocket_aapf' => array(
                        "type" => "dropdown",
                        "heading" => esc_html__( 'Apply BeRocket AJAX Filters', "BeRocket_AJAX_domain" ),
                        "param_name" => "berocket_aapf",
                        "value" => array(
                            array(
                                'default',
                                esc_html__( 'Default', 'BeRocket_AJAX_domain' )
                            ),
                            array(
                                'true',
                                esc_html__( 'Enable', 'BeRocket_AJAX_domain' )
                            ),
                            array(
                                'false',
                                esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                            ),
                        ),
                        "save_always" => false,
                        "description" => esc_html__( 'All Filters will be applied to this module. You need correct unique selectors to work correct', 'BeRocket_AJAX_domain' ),
                        "dependency" => array(
                            "element" => "post_type",
                            "value" => array( "product" )
                        )
                    ),
                );
                $position_id = 3;
                $settings['params'] = berocket_insert_to_array(
                    $settings['params'],
                    $position_id,
                    $new_option
                );
            }
            return $settings;
        }
        function custom_js() {
            global $berocket_parse_page_obj;
            $data = $berocket_parse_page_obj->get_current();
?><script>
function bapf_wpbakery_get_all_filters() {
    var filters_list = [];
    jQuery(".berocket_single_filter_widget").each(function() {
        filters_list.push({id:jQuery(this).data('id'),wid:jQuery(this).data('wid')});
    });
    return filters_list;
}
function bapf_init_wpbakery_grid_filters() {
    jQuery('.brapf_wpb_replace_grid.vc_grid-container').each(function() {
        var data = jQuery(this).data('vc-grid-settings');
        data.brfilter = "<?php echo $data['fullline']; ?>";
        data.brfilter_list = bapf_wpbakery_get_all_filters();
        jQuery(this).data('vc-grid-settings', data);
    });
}
bapf_init_wpbakery_grid_filters();
jQuery(document).on('berocket_ajax_products_loaded', function() {
    jQuery('.brapf_wpb_replace_grid.vc_grid-container').each(function() {
        var data = jQuery(this).data('vc-grid-settings');
        data.brfilter = braapf_get_current_url_data().filter;
        data.brfilter_list = bapf_wpbakery_get_all_filters();
        jQuery(this).data('vc-grid-settings', data);
        if( typeof(jQuery(this).vcGrid) == 'function' ) {
            jQuery(this).data('vcGrid', null).vcGrid();
        }
    });
});
jQuery(document).on('bapf_ajax_load_replace', function() {
    if( jQuery('.bapf_ajax_load_replace').length ) {
        braapf_replace_each_filter(jQuery('.bapf_ajax_load_replace').html());
        jQuery('.bapf_ajax_load_replace').remove();
    }
    braapf_remove_loader_element('');
});

bapf_apply_filters_to_page_js_composer = function(filter_products, context, element, url_filtered) {
    if( jQuery('.brapf_wpb_replace_grid.vc_grid-container').length > 0 && jQuery(the_ajax_script.products_holder_id).length == jQuery('.brapf_wpb_replace_grid.vc_grid-container').length ) {
        braapf_selected_filters_area_set();
        braapf_change_url_history_api(url_filtered, {replace:the_ajax_script.seo_friendly_urls});
        braapf_add_loader_element('', '', '', '', 'default');
        jQuery('.brapf_wpb_replace_grid.vc_grid-container').each(function() {
            var data = jQuery(this).data('vc-grid-settings');
            data.brfilter = braapf_get_current_url_data().filter;
            data.brfilter_list = bapf_wpbakery_get_all_filters();
            jQuery(this).data('vc-grid-settings', data);
            if( typeof(jQuery(this).vcGrid) == 'function' ) {
                jQuery(this).html('');
                jQuery(this).data('vcGrid', null).vcGrid();
            }
        });
        return false;
    }
    return filter_products;
}
if ( typeof(berocket_add_filter) == 'function' ) {
    berocket_add_filter('apply_filters_to_page', bapf_apply_filters_to_page_js_composer);
} else {
    jQuery(document).on('berocket_hooks_ready', function() {
        berocket_add_filter('apply_filters_to_page', bapf_apply_filters_to_page_js_composer);
    });
}
</script><?php
        }
    }
    new BeRocket_AAPF_compat_js_composer();
}
    