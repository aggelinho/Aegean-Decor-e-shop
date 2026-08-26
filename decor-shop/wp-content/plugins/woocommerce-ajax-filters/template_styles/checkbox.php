<?php
if( ! class_exists('BeRocket_AAPF_Template_Style_checkbox') ) {
    class BeRocket_AAPF_Template_Style_checkbox extends BeRocket_AAPF_Template_Style {
        function __construct() {
            $this->data = array(
                'slug'          => 'checkbox',
                'template'      => 'checkbox',
                'name'          => 'Checkbox',
                'file'          => __FILE__,
                'style_file'    => '',
                'script_file'   => '',
                'image'         => plugin_dir_url( __FILE__ ) . 'images/checkbox.png',
                'version'       => '1.0',
                'sort_pos'      => '1',
                'name_price'    => 'Price Ranges',
                'image_price'   => plugin_dir_url( BeRocket_AJAX_filters_file ) . 'paid/template_styles/images/checkbox-price.png',
            );
            parent::__construct();
        }
    }
    new BeRocket_AAPF_Template_Style_checkbox();
}
if( ! class_exists('BeRocket_AAPF_Template_Style_checkbox_clean_rounded') ) {
	class BeRocket_AAPF_Template_Style_checkbox_clean_rounded extends BeRocket_AAPF_Template_Style_checkbox {
		function __construct() {
			parent::__construct();
			$this->data['slug'] = 'clean_rounded';
			$this->data['name'] = 'Clean & rounded';
			$this->data['name_price'] = 'Price Ranges clean';
			$this->data['image'] = plugin_dir_url( __FILE__ ) . 'images/checkbox-clean-rounded.png';
			$this->data['image_price'] = plugin_dir_url( BeRocket_AJAX_filters_file ) . 'paid/template_styles/images/checkbox-clean-rounded-price.png';
			$this->data['style_file'] = 'css/checkbox.css';
			$this->data['sort_pos'] = '600';
		}
		function template_full($template, $terms, $berocket_query_var_title) {
			$template = parent::template_full($template, $terms, $berocket_query_var_title);
			if( ! isset($template['template']['attributes']) || ! is_array($template['template']['attributes']) ) {
				$template['template']['attributes'] = array();
			}
			if( ! isset($template['template']['attributes']['class']) ) {
				$template['template']['attributes']['class'] = array();
			}
			if( ! is_array($template['template']['attributes']['class']) ) {
				$template['template']['attributes']['class'] = array($template['template']['attributes']['class']);
			}
			$template['template']['attributes']['class'][] = 'bapf-clean-rounded';

			foreach ( $template['template']['content']['filter']['content']['list']['content'] as $i => $content ) {
				if ( isset( $content['content']['qty']['content'][0] ) ) {
					$value = $content['content']['qty']['content'][0];
					$template['template']['content']['filter']['content']['list']['content'][ $i ]['content']['label']['content']['name'] .= $value;
					unset( $template['template']['content']['filter']['content']['list']['content'][ $i ]['content']['qty'] );
				}
			}

			return $template;
		}
	}
	new BeRocket_AAPF_Template_Style_checkbox_clean_rounded();
}
if( ! class_exists('BeRocket_AAPF_Template_Style_checkbox_hide') ) {
    class BeRocket_AAPF_Template_Style_checkbox_hide extends BeRocket_AAPF_Template_Style_checkbox {
        function __construct() {
            parent::__construct();
            $this->data['slug'] = 'checkbox_hide';
            $this->data['name'] = 'Checkbox Hide';
            $this->data['name_price'] = 'Price Ranges Hide';
            $this->data['image'] = plugin_dir_url( __FILE__ ) . 'images/checkbox-hide.png';
            $this->data['image_price'] = plugin_dir_url( BeRocket_AJAX_filters_file ) . 'paid/template_styles/images/checkbox-hide-price.png';
            $this->data['sort_pos'] = '1900';
        }
        function template_full($template, $terms, $berocket_query_var_title) {
            $template = parent::template_full($template, $terms, $berocket_query_var_title);
            if( ! isset($template['template']['attributes']) || ! is_array($template['template']['attributes']) ) {
                $template['template']['attributes'] = array();
            }
            if( ! isset($template['template']['attributes']['class']) ) {
                $template['template']['attributes']['class'] = array();
            }
            if( ! is_array($template['template']['attributes']['class']) ) {
                $template['template']['attributes']['class'] = array($template['template']['attributes']['class']);
            }
            $template['template']['attributes']['class'][] = 'bapf_hideckbox';
            return $template;
        }
    }
    new BeRocket_AAPF_Template_Style_checkbox_hide();
}