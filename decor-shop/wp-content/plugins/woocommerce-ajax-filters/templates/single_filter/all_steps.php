<?php
include_once('single_settings_elements.php');
$settings_name = $braapf_filter_setings['settings_name'];
echo '<input type="hidden" name="'.$settings_name.'[version]" value="1.0">';
$steps = apply_filters('braapf_new_widget_edit_page_all_steps', array(
    'widget_type' => array(
        'header' => __('Widget Type', 'BeRocket_AJAX_domain'),
        'content_file' => __DIR__ . '/widget_type.php',
	    'docs_file' => __DIR__ . '/docs_widget_type.php',
    ),
    'attribute_setup' => array(
        'header' => __('Attribute and Values', 'BeRocket_AJAX_domain'),
        'docs_file' => __DIR__ . '/docs_attribute_setup.php',
	    'has_advanced' => true,
    ),
    'style' => array(
        'header' => __('Style', 'BeRocket_AJAX_domain'),
        'docs_file' => __DIR__ . '/docs_style.php',
    ),
    'required' => array(
        'header' => __('Required Options', 'BeRocket_AJAX_domain'),
        'docs_file' => __DIR__ . '/docs_required.php',
    ),
    'additional' => array(
        'header' => __('Additional', 'BeRocket_AJAX_domain'),
        'docs_file' => __DIR__ . '/docs_additional.php',
        'has_advanced' => true,
    ),
    'save' => array(
        'header' => '',
    ),
));
echo '<div class="berocket_sbs">';
    echo '<div class="braapf_attribute_setup_flex">';
        echo '<div class="braapf_filter_title braapf_full_select_full">';
            $filter_title = br_get_value_from_array($braapf_filter_setings, 'filter_title', '');
            echo '<label class="braapf_filter_title_label" for="braapf_filter_title">'.__('Filter Title', 'BeRocket_AJAX_domain').'</label>';
            echo '<label class="braapf_filter_title_button" for="braapf_filter_title">'.__('Text on Button', 'BeRocket_AJAX_domain').'</label>';
            echo '<input id="braapf_filter_title" type="text" name="' . $settings_name . '[filter_title]" value="'.$filter_title.'" placeholder="'.__('Empty', 'BeRocket_AJAX_domain').'">';
        echo '</div>';
    echo '</div>';
foreach($steps as $step_name => $step) {
    echo '<div class="berocket_sbs_step brsbs_'.$step_name.'">';
		echo '<div class="berocket_sbs_inner">';
            echo '<div class="berocket_sbs_header">';
                echo '<h3><span class="brsbs_before"></span>'.$step['header'].'<span class="brsbs_after"></span></h3>';
				if ( ! empty ( $step['has_advanced'] ) ) {
					echo '<div class="advanced_button_container"><a href="#" class="turn_on_advanced">'.__('Advanced', 'BeRocket_AJAX_domain').'</a></div>';
				}
            echo '</div>';
            echo '<div class="berocket_sbs_content">';
	            if ( ! empty($step['content_file']) && file_exists($step['content_file']) ) {
	                include $step['content_file'];
	            }
	            do_action('braapf_single_filter_'.$step_name, $settings_name, $braapf_filter_setings);

				ob_start();
				do_action('braapf_advanced_single_filter_'.$step_name, $settings_name, $braapf_filter_setings);

	            $additional = ob_get_clean();
		        if ( ! empty($additional) ) {
		            echo '<div class="berocket_sbs_advanced show_on_advanced">';
		                echo '<h3>'.__('Advanced Settings', 'BeRocket_AJAX_domain').'</h3>';
		                echo '<div class="berocket_sbs_advanced_inner">' . $additional . '</div>';
		            echo '</div>';
		        }
			echo '</div>';
        echo '</div>';
		echo '<div class="berocket_sbs_step_docs">';
			do_action('braapf_single_filter_docs_' . $step_name, $settings_name, $braapf_filter_setings);

			if ( ! empty( $step['docs_file'] ) && file_exists( $step['docs_file'] ) ) {
				include $step['docs_file'];
			}
		echo '</div>';
    echo '</div>';
}
echo '</div>';

BeRocket_popup_display::add_popup(
    array(
        'height'        => '200px',
        'width'         => '700px',
    ),
    '<p>' . __('There are required steps not filled in. Please select <b>Widget Type</b>', 'BeRocket_AJAX_domain') . '</p>',
    array('event_new' => array('type' => 'event', 'event' => 'braapf_error_select_widget_type'))
);
BeRocket_popup_display::add_popup(
    array(
        'height'        => '200px',
        'width'         => '700px',
    ),
    '<p>' . __('There are required steps not filled in. Please select <b>Style</b>', 'BeRocket_AJAX_domain') . '</p>',
    array('event_new' => array('type' => 'event', 'event' => 'braapf_error_select_style'))
);
echo '<style>#br_popup .br_popup_inner{display: flex;justify-content: center;align-items: center;} #br_popup .br_popup_inner p{font-size:20px}</style>';