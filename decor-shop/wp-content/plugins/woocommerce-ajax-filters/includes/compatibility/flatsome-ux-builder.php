<?php
if( ! class_exists('BeRocket_AAPF_compat_flatsome_ux_builder') ) {
    class BeRocket_AAPF_compat_flatsome_ux_builder {
        public $is_enabled = false;
        function __construct() {
            add_filter('ux_builder_shortcode_data_ux_products', array($this, 'add_option'));
            add_filter('ux_builder_shortcode_data_ux_products_list', array($this, 'add_option_list'));
            add_filter('pre_do_shortcode_tag', array($this, 'ux_builder_check'), 1, 3);
            add_filter('do_shortcode_tag', array($this, 'ux_builder_div'), 1, 3);
            add_filter('aapf_localize_widget_script', array($this, 'modify_products_selector'));
            add_action('wp_ajax_flatsome_ajax_apply_shortcode', array($this, 'init'), 1);
            add_action('wp_ajax_nopriv_flatsome_ajax_apply_shortcode', array($this, 'init'), 1);
            add_action('wp_footer', array($this, 'footer_script'));
        }
        function modify_products_selector($args) {
            if( ! empty($args['products_holder_id']) ) {
                $args['products_holder_id'] .= ',';
            }
            $args['products_holder_id'] .= '.bapf_apply_flatsome';
            return $args;
        }
        function add_option($data) {
            $data['options']['style_options']['options']['aapf_apply'] = array(
                'type' => 'select',
                'heading' => esc_html__( 'Apply BeRocket AJAX Filters', 'BeRocket_AJAX_domain' ),
                'default' => 'default',
                'options' => array(
                    'default'   => esc_html__( 'Default', 'BeRocket_AJAX_domain' ),
                    'enable'    => esc_html__( 'Enable', 'BeRocket_AJAX_domain' ),
                    'disable'   => esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                )
            );
            return $data;
        }
        function add_option_list($data) {
            $data['options']['post_options']['options']['aapf_apply'] = array(
                'type' => 'select',
                'heading' => esc_html__( 'Apply BeRocket AJAX Filters', 'BeRocket_AJAX_domain' ),
                'default' => 'default',
                'options' => array(
                    'default'   => esc_html__( 'Default', 'BeRocket_AJAX_domain' ),
                    'enable'    => esc_html__( 'Enable', 'BeRocket_AJAX_domain' ),
                    'disable'   => esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                )
            );
            return $data;
        }
        function ux_builder_check($return, $tag, $attr) {
            if( ! $return && ('ux_products' == $tag || 'ux_products_list' == $tag) ) {
                $bapf_apply = empty($attr['aapf_apply']) ? '' : $attr['aapf_apply'];
                $this->ux_builder_parameter_apply($bapf_apply);
            }
            return $return;
        }
        function ux_builder_parameter_apply($aapf_apply) {
            $enabled = braapf_is_shortcode_must_be_filtered();
            if( ! empty($aapf_apply) && $aapf_apply == 'enable' ) {
                $enabled = true;
            } elseif( ! empty($aapf_apply) && $aapf_apply == 'disable' ) {
                $enabled = false;
            }
            $this->is_enabled = $enabled;
            do_action('brapf_next_shortcode_apply_action', array('apply' => $enabled));
        }
        function ux_builder_div($output, $tag, $attr) {
            if( ('ux_products' == $tag || 'ux_products_list' == $tag) && $this->is_enabled ) {
                return '<div class="bapf_apply_flatsome">' . $output . '</div>';
            }
            return $output;
        }
        function init() {
            if( ! empty($_REQUEST['tag']) && ('ux_products' == $_REQUEST['tag'] || 'ux_products_list' == $_REQUEST['tag'])
            && ! empty($_REQUEST['atts']) && is_array($_REQUEST['atts']) && isset($_REQUEST['atts']['brfilter']) ) {
                $bapf_apply = empty($_REQUEST['atts']['aapf_apply']) ? '' : $_REQUEST['atts']['aapf_apply'];
                bapf_set_filter_field_ajax($_REQUEST['atts']['brfilter']);
                $this->ux_builder_parameter_apply($bapf_apply);
            }
        }
        function footer_script() {
            ?>
            <script>
            jQuery(document).on('berocket_filters_first_load', function() {
                var flatsomeRelays = jQuery(".bapf_apply_flatsome .ux-relay");
                if( flatsomeRelays.length > 0 ) {
                    flatsomeRelays.each(function() {
                        var flatsomeData = jQuery(this).data('flatsomeRelay');
                        if( flatsomeData ) {
                            flatsomeData.atts.brfilter = braapf_get_current_url_data().filter;
                            jQuery(this).data('flatsomeRelay', flatsomeData);
                        }
                    });
                }
            });
            </script>
            <?php
            
        }
    }
    new BeRocket_AAPF_compat_flatsome_ux_builder();
}
        