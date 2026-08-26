<?php
class BeRocket_aapf_grouped_tax_addon extends BeRocket_framework_addon_lib {
	public $addon_file = __FILE__;
	public $plugin_name = 'ajax_filters';
	public $php_file_name   = '%plugindir%/business/addons/grouped_tax/grouped_tax_include';
	function get_addon_data() {
		$data = parent::get_addon_data();
		return array_merge($data, array(
			'addon_name'    => __('Intelligent Filters', 'BeRocket_AJAX_domain'),
			'tooltip'       => __('<b>Intelligent Filters</b> allow you to create advanced filters using custom value logic.<br />
									To use this feature, set <b>Filter by</b> to <b>Grouped Values</b>, then define filter 
									values by combining multiple attributes and taxonomies into a single selectable option.',
									'BeRocket_AJAX_domain'),
			'image'         => 'https://berocket.ams3.cdn.digitaloceanspaces.com/plugins/addons/filters/filters_grouped_tax.jpg',
			'image_class'   => 'grouped_tax',
			'business'      => true,
		));
	}
}
new BeRocket_aapf_grouped_tax_addon();
