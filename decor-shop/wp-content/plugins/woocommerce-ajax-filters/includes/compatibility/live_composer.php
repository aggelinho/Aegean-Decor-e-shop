<?php
if( ! class_exists('BeRocket_AAPF_compat_live_composer_builder') ) {
    class BeRocket_AAPF_compat_live_composer_builder {
        public $bapf_status = false;
        function __construct() {
            add_action('dslc_module_options', array($this, 'add_option'), 10, 2);
            add_filter('pre_do_shortcode_tag', array($this, 'shortcode_check'), 10, 5);
            add_filter('dslc_module_class', array($this, 'module_class'), 10, 3);
            add_filter('aapf_localize_widget_script', array($this, 'modify_products_selector'));
        }
        function modify_products_selector($args) {
            if( ! empty($args['products_holder_id']) ) {
                $args['products_holder_id'] .= ',';
            }
            $args['products_holder_id'] .= '.bapf_livecomp_apply  .dslc-posts';
            if( ! empty($args['pagination_class']) ) {
                $args['pagination_class'] .= ',';
            }
            $args['pagination_class'] .= '.bapf_livecomp_apply  .dslc-pagination';
            return $args;
        }
        function shortcode_check($content, $tag, $attr, $m) {
            if( 'dslc_module_posts_output' == $tag && is_array($m) && ! empty($m[5]) ) {
                try {
                    $args = $m[5];
                    $data = json_decode( $args, true );

                    if ( $data !== false ) {
                        $options = $data;
                    } else {
                        $fixed_data = preg_replace_callback( '!s:(\d+):"(.*?)";!', function( $match ) {
                            return ( $match[1] == strlen( $match[2] ) ) ? $match[0] : 's:' . strlen( $match[2] ) . ':"' . $match[2] . '";';
                        }, $args );
                        $options = json_decode( $fixed_data, true );
                    }
                    $bapf_status = ( isset($options['bapf_apply']) ? $options['bapf_apply'] : false );
                    $enabled = braapf_is_shortcode_must_be_filtered();
                    if( $bapf_status !== false ) {
                        if( $bapf_status == 'enable' ) {
                            $enabled = true;
                        } elseif( $bapf_status == 'disable' ) {
                            $enabled = false;
                        }
                    }
                    do_action('brapf_next_shortcode_apply_action', array('apply' => $enabled));
                } catch (Exception $err) {}
            }
            return $content;
        }
        function module_class($module_class_arr, $module_id, $options) {
            if( $module_id == 'DSLC_Posts' ) {
                $bapf_status = ( isset($options['bapf_apply']) ? $options['bapf_apply'] : false );
                $enabled = braapf_is_shortcode_must_be_filtered();
                if( $bapf_status !== false ) {
                    if( $bapf_status == 'enable' ) {
                        $enabled = true;
                    } elseif( $bapf_status == 'disable' ) {
                        $enabled = false;
                    }
                }
                if( $enabled ) {
                    $module_class_arr[] = 'bapf_livecomp_apply';
                }
            }
            return $module_class_arr;
        }
        function add_option($dslc_options, $module_id) {
            if( $module_id == 'DSLC_Posts' ) {
                $set_position = FALSE;
                foreach($dslc_options as $i => $dslc_option) {
                    if( $dslc_option['id'] == 'post_type' ) {
                        $set_position = $i;
                        break;
                    }
                }
                if( $set_position !== FALSE ) {
                    $dslc_options['bapf_apply'] = array(
                        'label' => __( 'Apply BeRocket AJAX Filters', 'live-composer-page-builder' ),
                        'id' => 'bapf_apply',
                        'std' => 'grid',
                        'type' => 'select',
                        'tab' => 'posts query',
                        'choices' => array(
                            array(
                                'value' => 'default',
                                'label'  => esc_html__( 'Default', 'BeRocket_AJAX_domain' )
                            ),
                            array(
                                'value' => 'enable',
                                'label'  => esc_html__( 'Enable', 'BeRocket_AJAX_domain' )
                            ),
                            array(
                                'value' => 'disable',
                                'label'  => esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                            )
                        ),
                    );
                }
            }
            return $dslc_options;
        }
    }
    new BeRocket_AAPF_compat_live_composer_builder();
}