<?php
class BeRocket_aapf_filtering_conditions_addon extends BeRocket_framework_addon_lib {
    public $addon_file = __FILE__;
    public $plugin_name = 'ajax_filters';
    public $php_file_name   = 'add_conditions';
    function get_addon_data() {
        $data = parent::get_addon_data();
        return array_merge($data, array(
            'addon_name'    => __('Nested Filters', 'BeRocket_AJAX_domain'),
            'tooltip'       => __('Nested Filters allows you to control filter visibility based on other filters’ selections.', 'BeRocket_AJAX_domain'),
            'image'         => 'https://berocket.ams3.cdn.digitaloceanspaces.com/plugins/addons/filters/filters_nested_filters.jpg',
            'image_class'   => 'nested_filters',
        ));
    }
}
new BeRocket_aapf_filtering_conditions_addon();
