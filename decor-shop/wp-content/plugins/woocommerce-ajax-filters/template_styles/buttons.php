<?php
if( ! class_exists('BeRocket_AAPF_Elemets_Style_button_default') ) {
    class BeRocket_AAPF_Elemets_Style_button_default extends BeRocket_AAPF_Template_Style {
        function __construct() {
            $this->data = array(
                'slug'          => 'button_default',
                'template'      => 'button',
                'name'          => 'Default',
                'file'          => __FILE__,
                'style_file'    => '',
                'script_file'   => '',
                'image'         => plugin_dir_url( __FILE__ ) . 'images/button_default.png',
                'version'       => '1.0',
                'specific'      => 'elements',
                'sort_pos'      => '1',
            );
            parent::__construct();
        }
        function filters($action = 'add') {
            parent::filters($action);
            $filter_func = 'add_filter';
            $action_func = 'add_action';
            if( $action != 'add' ) {
                $filter_func = 'remove_filter';
                $action_func = 'remove_action';
            }
            $filter_func('BeRocket_AAPF_template_full_element_content', array($this, 'template_element_full'), 10, 2);
        }
        function template_element_full($template, $berocket_query_var_title) {
            return $template;
        }
    }
    new BeRocket_AAPF_Elemets_Style_button_default();
}
if( ! class_exists('BeRocket_AAPF_Elemets_Style_button_dark_rounded') ) {
	class BeRocket_AAPF_Elemets_Style_button_dark_rounded extends BeRocket_AAPF_Elemets_Style_button_default {
		function __construct() {
			parent::__construct();
			$this->data['slug'] = 'button_dark_rounded';
			$this->data['name'] = 'Dark & rounded';
			$this->data['image'] = plugin_dir_url( __FILE__ ) . 'images/button_dark_rounded.png';
			$this->data['style_file'] = 'css/button.css';
			$this->data['sort_pos'] = '500';
		}
		function template_element_full($template, $berocket_query_var_title) {
			$template['template']['attributes']['class']['inline'] = 'bapf_button_dark_rounded';
			return $template;
		}
	}
	new BeRocket_AAPF_Elemets_Style_button_dark_rounded();
}
if( ! class_exists('BeRocket_AAPF_Elemets_Style_button_light') ) {
	class BeRocket_AAPF_Elemets_Style_button_light extends BeRocket_AAPF_Elemets_Style_button_default {
		function __construct() {
			parent::__construct();
			$this->data['slug'] = 'button_light';
			$this->data['name'] = 'Light';
			$this->data['image'] = plugin_dir_url( __FILE__ ) . 'images/button_light.png';
			$this->data['style_file'] = 'css/button.css';
			$this->data['sort_pos'] = '600';
		}
		function template_element_full($template, $berocket_query_var_title) {
			$template['template']['attributes']['class']['inline'] = 'bapf_button_light';
			return $template;
		}
	}
	new BeRocket_AAPF_Elemets_Style_button_light();
}
if( ! class_exists('BeRocket_AAPF_Elemets_Style_button_berocket') ) {
    class BeRocket_AAPF_Elemets_Style_button_berocket extends BeRocket_AAPF_Elemets_Style_button_default {
        function __construct() {
            parent::__construct();
            $this->data['slug'] = 'button_berocket';
            $this->data['name'] = 'BeRocket';
            $this->data['image'] = plugin_dir_url( __FILE__ ) . 'images/button_berocket.png';
            $this->data['style_file'] = 'css/button.css';
            $this->data['sort_pos'] = '900';
        }
        function template_element_full($template, $berocket_query_var_title) {
            $template['template']['attributes']['class']['inline'] = 'bapf_button_berocket';
            return $template;
        }
    }
    new BeRocket_AAPF_Elemets_Style_button_berocket();
}