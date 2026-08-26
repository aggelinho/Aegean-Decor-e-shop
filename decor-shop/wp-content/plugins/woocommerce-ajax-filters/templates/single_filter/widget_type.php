<?php
$settings_name = $braapf_filter_setings['settings_name'];
$widget_type_setting = br_get_value_from_array($braapf_filter_setings, 'widget_type', '');
$filter_title = br_get_value_from_array($braapf_filter_setings, 'filter_title', '');
$widget_types = apply_filters('braapf_new_widget_edit_page_widget_types', array(
    'filter' => array(
        'value' => 'filter',
        'name'  => __('Filter', 'BeRocket_AJAX_domain'),
        'image' => plugin_dir_url( BeRocket_AJAX_filters_file ) . 'assets/admin/images/filters.png',
    ),
    'update_button' => array(
        'value' => 'update_button',
        'name'  => __('Update Products button', 'BeRocket_AJAX_domain'),
        'image' => plugin_dir_url( BeRocket_AJAX_filters_file ) . 'assets/admin/images/apply_filters.png',
        'templates' => array('button'),
        'specific'  => array('elements'),
    ),
    'reset_button' => array(
        'value' => 'reset_button',
        'name'  => __('Reset Products button', 'BeRocket_AJAX_domain'),
        'image' => plugin_dir_url( BeRocket_AJAX_filters_file ) . 'assets/admin/images/clear_filters.png',
        'templates' => array('button'),
        'specific'  => array('elements'),
    ),
    'selected_area' => array(
        'value' => 'selected_area',
        'name'  => __('Selected Filters area', 'BeRocket_AJAX_domain'),
        'image' => plugin_dir_url( BeRocket_AJAX_filters_file ) . 'assets/admin/images/selected_filters.png',
        'templates' => array('selected_filters'),
        'specific'  => array('elements'),
    ),
));
echo '<p>'.__('Select widget type that you need to create', 'BeRocket_AJAX_domain').'</p>';
$widget_types_build = array(
    'template'=> array(
        'type'          => 'tag',
        'tag'           => 'div',
        'attributes'    => array(
            'class'         => array(
                'braapf_widget_type'
            ),
        ),
        'content'       => array()
    ),
);
foreach($widget_types as $widget_slug => $widget_type) {
    $widget_types_build['template']['content'][$widget_slug] = array(
        'type'      => 'tag',
        'tag'       => 'div',
        'attributes'=> array(
            'id'        => 'braapf_widget_type_'.$widget_slug.'_div',
            'class'     => array(
                'braapf_widget_type_'.$widget_slug
            )
        ),
        'content'   => array(
            'input'     => array(
                'type'      => 'tag_open',
                'tag'       => 'input',
                'attributes'=> array(
                    'id'        => 'braapf_widget_type_'.$widget_slug,
                    'type'      => 'radio',
                    'name'      => $settings_name.'[widget_type]',
                    'value'     => $widget_type['value'],
                ),
            ),
            'label'     => array(
                'type'      => 'tag',
                'tag'       => 'label',
                'attributes'=> array(
                    'for'      => 'braapf_widget_type_'.$widget_slug,
                    'type'     => 'radio',
                    'name'     => $settings_name.'[widget_type]',
                    'value'    => $widget_type['value'],
                ),
                'content'   => array(
                    'img'  => array(
                        'type'      => 'tag_open',
                        'tag'       => 'img',
                        'attributes'=> array(
                            'alt'       => $widget_type['name'],
                            'src'       => $widget_type['image'],
                        ),
                    ),
                    'h3'  => array(
                        'type'      => 'tag',
                        'tag'       => 'h3',
                        'attributes'=> array(),
                        'content'   => array(
                            'title'     => $widget_type['name']
                        ),
                    ),
                )
            ),
        )
    );
    if( $widget_type_setting == $widget_type['value'] ) {
        $widget_types_build['template']['content'][$widget_slug]['content']['input']['attributes']['checked'] = 'checked';
    }
    if( ! empty($widget_type['templates']) && is_array($widget_type['templates']) && count($widget_type['templates']) ) {
        $widget_types_build['template']['content'][$widget_slug]['content']['input']['attributes']['data-templates'] = json_encode($widget_type['templates']);
    }
    if( ! empty($widget_type['specific']) && is_array($widget_type['specific']) && count($widget_type['specific']) ) {
        $widget_types_build['template']['content'][$widget_slug]['content']['input']['attributes']['data-specific'] = json_encode($widget_type['specific']);
    }
    if( ! empty($widget_type['info']) ) {
        BeRocket_AAPF::add_tooltip('#braapf_widget_type_'.$widget_slug.'_div', $widget_type['info']);
    }
}
echo BeRocket_AAPF_Template_Build($widget_types_build);
