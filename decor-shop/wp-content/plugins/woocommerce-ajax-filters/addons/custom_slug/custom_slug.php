<?php
class BeRocket_aapf_custom_slug_addon extends BeRocket_framework_addon_lib {
    public $addon_file = __FILE__;
    public $plugin_name = 'ajax_filters';
    public $php_file_name   = '%plugindir%/paid/addons/custom_slug/custom_slug_include';
    function get_addon_data() {
        $data = parent::get_addon_data();
        return array_merge($data, array(
            'addon_name'    => __('Custom Slug', 'BeRocket_AJAX_domain'),
            'tooltip'       => '<a target="_blank" href="https://docs.berocket.com/docs_section/custom-slug">DOCUMENTATION</a><br>'.__('Replaces attribute/taxonomy slug in filtered URL.<br>Provide the possibility to use multiple filters for the same attribute/taxonomy.', 'BeRocket_AJAX_domain'),
            'image'         => 'https://berocket.ams3.cdn.digitaloceanspaces.com/plugins/addons/filters/filters_c_slug.jpg',
			'image_class'   => 'c_slug',
            'paid'          => true
        ));
    }
}
new BeRocket_aapf_custom_slug_addon();
