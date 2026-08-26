<?php
if( ! class_exists('BeRocket_AAPF_compat_oxygen_builder') ) {
    class BeRocket_AAPF_compat_oxygen_builder {
        public $bapf_status = false;
        function __construct() {
            add_filter('breakdance_render_show_node', array($this, 'node_get'), 1000, 2);
            add_filter('breakdance_element_controls', array($this, 'control'), 10, 2);
            add_filter('breakdance_render_element_class_list', array($this, 'modify_class'), 10, 2);
            add_filter('aapf_localize_widget_script', array($this, 'modify_products_selector'));
        }
        function modify_products_selector($args) {
            if( ! empty($args['products_holder_id']) ) {
                $args['products_holder_id'] .= ',';
            }
            $args['products_holder_id'] .= '.bapf_oxy_apply  .bde-loop, .bapf_oxy_apply .products';
            if( ! empty($args['pagination_class']) ) {
                $args['pagination_class'] .= ',';
            }
            $args['pagination_class'] .= '.bapf_oxy_apply  .bde-posts-pagination, .bapf_oxy_apply  .pagination';
            return $args;
        }
        function node_get($show, $node) {
            if( 'EssentialElements\Wooproductslist' == $node['data']['type'] ) {
                $this->bapf_status = br_get_value_from_array($node, array('data', 'properties', 'content', 'content', 'bapf_apply'));
            } else
            if( 'OxygenElements\PostsLoop' == $node['data']['type'] ) {
                $this->bapf_status = br_get_value_from_array($node, array('data', 'properties', 'content', 'query', 'bapf_apply'));
            } else {
                $this->bapf_status = false;
            }
            return $show;
        }
        function control($controls, $element) {
            if( 'bde-post-loop' == $element::className() ) {
                $controls['contentSections'][1]['children'] = berocket_insert_to_array(
                    $controls['contentSections'][1]['children'],
                    0,
                    array(
                        'bapf_apply' => array(
                            'slug'      => 'bapf_apply',
                            'label'     => esc_html__( 'Apply BeRocket AJAX Filters', 'BeRocket_AJAX_domain' ),
                            'options'   => array(
                                'type'      => 'dropdown',
                                'layout'    => 'inline',
                                'items'     => array(
                                    array(
                                        'value' => 'default',
                                        'text'  => esc_html__( 'Default', 'BeRocket_AJAX_domain' )
                                    ),
                                    array(
                                        'value' => 'enable',
                                        'text'  => esc_html__( 'Enable', 'BeRocket_AJAX_domain' )
                                    ),
                                    array(
                                        'value' => 'disable',
                                        'text'  => esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                                    )
                                ),
                                'condition' => array(
                                    'path' => 'content.query.query.custom.postTypes',
                                    'operand' => 'equals',
                                    'value' => 'product'
                                )
                            ),
                            'enableMediaQueries' => false,
                            'enableHover' => false,
                            'children' => array(),
                            'keywords' => array()
                        )
                    )
                );
                $controls['contentSections'][1]['children'] = array_values($controls['contentSections'][1]['children']);
            } elseif( 'bde-wooproductslist' == $element::className() ) {
                $controls['contentSections'][0]['children'] = berocket_insert_to_array(
                    $controls['contentSections'][0]['children'],
                    0,
                    array(
                        'bapf_apply' => array(
                            'slug'      => 'bapf_apply',
                            'label'     => esc_html__( 'Apply BeRocket AJAX Filters', 'BeRocket_AJAX_domain' ),
                            'options'   => array(
                                'type'      => 'dropdown',
                                'layout'    => 'inline',
                                'items'     => array(
                                    array(
                                        'value' => 'default',
                                        'text'  => esc_html__( 'Default', 'BeRocket_AJAX_domain' )
                                    ),
                                    array(
                                        'value' => 'enable',
                                        'text'  => esc_html__( 'Enable', 'BeRocket_AJAX_domain' )
                                    ),
                                    array(
                                        'value' => 'disable',
                                        'text'  => esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                                    )
                                )
                            ),
                            'enableMediaQueries' => false,
                            'enableHover' => false,
                            'children' => array(),
                            'keywords' => array()
                        )
                    )
                );
                $controls['contentSections'][0]['children'] = array_values($controls['contentSections'][0]['children']);
            }
            return $controls;
        }
        function modify_class($classList, $element) {
            if( 'bde-post-loop' == $element::className() || 'bde-wooproductslist' == $element::className() ) {
                $enabled = braapf_is_shortcode_must_be_filtered();
                if( $this->bapf_status !== false ) {
                    if( $this->bapf_status == 'enable' ) {
                        $enabled = true;
                    } elseif( $this->bapf_status == 'disable' ) {
                        $enabled = false;
                    }
                }
                if( $enabled ) {
                    $classList[] = 'bapf_oxy_apply';
                }
                do_action('brapf_next_shortcode_apply_action', array('apply' => $enabled));
            }
            return $classList;
        }
    }
    new BeRocket_AAPF_compat_oxygen_builder();
}