<?php
class BeRocket_aapf_statistics_addon extends BeRocket_framework_addon_lib {
	public $addon_file = __FILE__;
	public $plugin_name = 'ajax_filters';
	public $php_file_name   = '%plugindir%/business/addons/statistics/statistics_include';
	function get_addon_data() {
		$data = parent::get_addon_data();
		return array_merge($data, array(
			'addon_name'    => __('Statistics', 'BeRocket_AJAX_domain'),
			'tooltip'       => __('', 'BeRocket_AJAX_domain'),
			'image'         => 'https://berocket.ams3.cdn.digitaloceanspaces.com/plugins/addons/filters/filters_statistics.jpg',
			'image_class'   => 'b_statistics',
			'business'      => true
		));
	}
}
new BeRocket_aapf_statistics_addon();
