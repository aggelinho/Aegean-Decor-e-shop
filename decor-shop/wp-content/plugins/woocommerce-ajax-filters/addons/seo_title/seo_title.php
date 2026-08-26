<?php
class BeRocket_aapf_seo_title_addon extends BeRocket_framework_addon_lib {
    public $addon_file = __FILE__;
    public $plugin_name = 'ajax_filters';
    public $php_file_name   = '%plugindir%/business/addons/seo_title/seo_title_include';
    function get_addon_data() {
        $data = parent::get_addon_data();
        return array_merge($data, array(
            'addon_name'    => __('SEO Meta per page', 'BeRocket_AJAX_domain'),
            'tooltip'       => __('', 'BeRocket_AJAX_domain'),
			'image'         => 'https://berocket.ams3.cdn.digitaloceanspaces.com/plugins/addons/filters/filters_seo_per_page.jpg',
			'image_class'   => 'seo_per_page',
            'business'      => true
        ));
    }
}
new BeRocket_aapf_seo_title_addon();
