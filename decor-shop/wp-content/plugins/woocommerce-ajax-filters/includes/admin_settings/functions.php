<?php
function bapf_settings_get_elements_position() {
    $elements_position = array(
        array('value' => 'woocommerce_archive_description', 'text' => __('WooCommerce Description(in header)', 'BeRocket_AJAX_domain')),
        array('value' => 'woocommerce_before_shop_loop', 'text' => __('WooCommerce Before Shop Loop', 'BeRocket_AJAX_domain')),
        array('value' => 'woocommerce_after_shop_loop', 'text' => __('WooCommerce After Shop Loop', 'BeRocket_AJAX_domain')),
    );
    $additional_elements_position = apply_filters('bapf_elements_position_hook_additional', array());
    if( ! is_array($additional_elements_position) ) {
        if( is_string($additional_elements_position) ) {
            $additional_elements_position = array($additional_elements_position);
        } else {
            $additional_elements_position = array();
        }
    }
    foreach($additional_elements_position as $additional_elements_position_element) {
        if( is_string($additional_elements_position_element) ) {
            $elements_position[] = array('value' => $additional_elements_position_element, 'text' => $additional_elements_position_element);
        }
    }
    return $elements_position;
}
//Theme seletor presets
function bapf_settings_get_selectors_preset($wptheme = false) {
    if( ! $wptheme || ! is_a($wptheme, 'WP_Theme') ) {
        $wptheme = wp_get_theme();
    }
    if( $wptheme ) {
        $parent_wptheme = $wptheme->parent();
        $wptheme = $wptheme->get('Name');
        if( $parent_wptheme ) {
            $parent_wptheme = $parent_wptheme->get('Name');
        } else {
            $parent_wptheme = '';
        }
    } else {
        $wptheme = $parent_wptheme = '';
    }
    $selectors_preset = array(
        'default' => array(
            'name'      => __('Default Selectors', 'BeRocket_AJAX_domain'),
            'options'   => array(
                'products_holder_id'                => 'ul.products',
                'woocommerce_result_count_class'    => '.woocommerce-result-count',
                'woocommerce_ordering_class'        => 'form.woocommerce-ordering',
                'woocommerce_pagination_class'      => '.woocommerce-pagination',
            ),
            'themes' => array()
        ),
        'main' => array(
            'name'      => __('Main', 'BeRocket_AJAX_domain'),
            'options'   => array(
                'products_holder_id'                => 'ul.products',
                'woocommerce_result_count_class'    => '.woocommerce-result-count',
                'woocommerce_ordering_class'        => 'form.woocommerce-ordering',
                'woocommerce_pagination_class'      => '.woocommerce-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'Divi'              => array('name' => 'Divi'),
                'Astra'             => array('name' => 'Astra'),
                'Bridge'            => array('name' => 'Bridge'),
                'Flatsome'          => array('name' => 'Flatsome'),
                'JupiterX'          => array('name' => 'JupiterX'),
                'Neptune by Osetin' => array('name' => 'Neptune by Osetin'),
                'SiteOrigin Corp'   => array('name' => 'SiteOrigin Corp'),
                'Storefront'        => array('name' => 'Storefront'),
                'Twenty Nineteen'   => array('name' => 'Twenty Nineteen'),
                'Twenty Twenty'     => array('name' => 'Twenty Twenty'),
                'Twenty Twenty-One' => array('name' => 'Twenty Twenty-One'),
                'Vantage'           => array('name' => 'Vantage'),
                'X'                 => array('name' => 'X'),
                'Blogfull'          => array('name' => 'Blogfull'),
                'Blogus'            => array('name' => 'Blogus'),
                'Bridge'            => array('name' => 'Bridge'),
                'CelebNews'         => array('name' => 'CelebNews'),
                'Colibri WP'        => array('name' => 'Colibri WP'),
                'Entr'              => array('name' => 'Entr'),
                'Envo One'          => array('name' => 'Envo One'),
                'Envo Royal'        => array('name' => 'Envo Royal'),
                'Futurio Storefront'=> array('name' => 'Futurio Storefront'),
                'GeneratePress'     => array('name' => 'GeneratePress'),
                'Go'                => array('name' => 'Go'),
                'Hello Biz'         => array('name' => 'Hello Biz'),
                'Hello Elementor'   => array('name' => 'Hello Elementor'),
                'Hestia'            => array('name' => 'Hestia'),
                'HybridMag'         => array('name' => 'HybridMag'),
                'Inspiro'           => array('name' => 'Inspiro'),
                'Iris WP'           => array('name' => 'Iris WP'),
                'Kadence'           => array('name' => 'Kadence'),
                'Kubio'             => array('name' => 'Kubio'),
                'Lightning'         => array('name' => 'Lightning'),
                'MoreNews'          => array('name' => 'MoreNews'),
                'Neve'              => array('name' => 'Neve'),
                'NewsBlogger'       => array('name' => 'NewsBlogger'),
                'NewsCorp'          => array('name' => 'NewsCorp'),
                'Newscrunch'        => array('name' => 'Newscrunch'),
                'News Magazine X'   => array('name' => 'News Magazine X'),
                'Newsmatic'         => array('name' => 'Newsmatic'),
                'OnePress'          => array('name' => 'OnePress'),
                'PopularFX'         => array('name' => 'PopularFX'),
                'Popularis eCommerce' => array('name' => 'Popularis eCommerce'),
                'Royal Elementor Kit' => array('name' => 'Royal Elementor Kit'),
                'Rufous'            => array('name' => 'Rufous'),
                'Silverstorm'       => array('name' => 'Silverstorm'),
                'Spacr'             => array('name' => 'Spacr'),
                'Sydney'            => array('name' => 'Sydney'),
                'Woostify'          => array('name' => 'Woostify'),
            )
        ),
        'blocks' => array(
            'name'      => __('Blocks', 'BeRocket_AJAX_domain'),
            'options'   => array(
                'products_holder_id'                => '.wp-block-woocommerce-product-collection .wc-block-product-template',
                'woocommerce_result_count_class'    => '.woocommerce-result-count',
                'woocommerce_ordering_class'        => 'form.woocommerce-ordering',
                'woocommerce_pagination_class'      => '.wp-block-woocommerce-product-collection .wp-block-query-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'Twenty Twenty-Two'     => array('name' => 'Twenty Twenty-Two'),
                'Twenty Twenty-Three'   => array('name' => 'Twenty Twenty-Three'),
                'Twenty Twenty-Four'    => array('name' => 'Twenty Twenty-Four'),
                'Twenty Twenty-Five'    => array('name' => 'Twenty Twenty-Five'),
                'Bakery and Pastry'     => array('name' => 'Bakery and Pastry'),
                'Extendable'            => array('name' => 'Extendable'),
                'Frontis'               => array('name' => 'Frontis'),
                'SaasLauncher'          => array('name' => 'SaasLauncher'),
                'Variations'            => array('name' => 'Variations'),
                'YITH Wonder'           => array('name' => 'YITH Wonder'),
            )
        ),
        'betheme' => array(
            'name'      => 'Betheme',
            'options'   => array(
                'products_holder_id'                => 'div.products_wrapper',
                'woocommerce_result_count_class'    => '',
                'woocommerce_ordering_class'        => '',
                'woocommerce_pagination_class'      => '.pager_wrapper',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'Betheme' => array('name' => 'Betheme')
            )
        ),
        'Enfold' => array(
            'name'      => 'Enfold',
            'options'   => array(
                'products_holder_id'                => 'ul.products',
                'woocommerce_result_count_class'    => '',
                'woocommerce_ordering_class'        => '.product-sorting',
                'woocommerce_pagination_class'      => 'nav.pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'Enfold' => array('name' => 'Enfold')
            )
        ),
        'Avada' => array(
            'name'      => 'Avada',
            'options'   => array(
                'products_holder_id'                => 'ul.products',
                'woocommerce_result_count_class'    => '',
                'woocommerce_ordering_class'        => '.catalog-ordering',
                'woocommerce_pagination_class'      => '.woocommerce-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'Avada' => array('name' => 'Avada')
            )
        ),
        'Blockpress' => array(
            'name'      => 'Blockpress',
            'options'   => array(
                'products_holder_id'                => '.wp-block-woocommerce-product-template',
                'woocommerce_result_count_class'    => '.woocommerce-result-count',
                'woocommerce_ordering_class'        => '.woocommerce-ordering',
                'woocommerce_pagination_class'      => '.wp-block-query-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'Blockpress' => array('name' => 'Blockpress')
            )
        ),
        'Blocksy' => array(
            'name'      => 'Blocksy',
            'options'   => array(
                'products_holder_id'                => 'ul.products',
                'woocommerce_result_count_class'    => '.woocommerce-result-count',
                'woocommerce_ordering_class'        => '.woocommerce-ordering',
                'woocommerce_pagination_class'      => '.ct-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'Blocksy' => array('name' => 'Blocksy')
            )
        ),
        'The7' => array(
            'name'      => 'The7',
            'options'   => array(
                'products_holder_id'                => 'div.products',
                'woocommerce_result_count_class'    => '',
                'woocommerce_ordering_class'        => '.woocommerce-ordering',
                'woocommerce_pagination_class'      => 'div.woocommerce-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'The7' => array('name' => 'The7')
            )
        ),
        'Woodmart' => array(
            'name'      => 'Woodmart',
            'options'   => array(
                'products_holder_id'                => 'div.products',
                'woocommerce_result_count_class'    => '.woocommerce-result-count',
                'woocommerce_ordering_class'        => '.woocommerce-ordering',
                'woocommerce_pagination_class'      => 'nav.woocommerce-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => true),
            ),
            'themes' => array(
                'Woodmart' => array('name' => 'Woodmart')
            )
        ),
        'BlogHash' => array(
            'name'      => 'BlogHash',
            'options'   => array(
                'products_holder_id'                => 'ul.products',
                'woocommerce_result_count_class'    => '.woocommerce-result-count',
                'woocommerce_ordering_class'        => '.woocommerce-ordering',
                'woocommerce_pagination_class'      => '.bloghash-pagination nav',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'BlogHash' => array('name' => 'BlogHash')
            )
        ),
        'OceanWP' => array(
            'name'      => 'OceanWP',
            'options'   => array(
                'products_holder_id'                => 'ul.products',
                'woocommerce_result_count_class'    => '.result-count',
                'woocommerce_ordering_class'        => 'form.woocommerce-ordering',
                'woocommerce_pagination_class'      => '.woocommerce-pagination',
                'woocommerce_removes'               => array('pagination_ajax' => false),
            ),
            'themes' => array(
                'OceanWP' => array('name' => 'OceanWP')
            )
        ),
    );
    $selectors_preset = apply_filters('bapf_settings_get_selectors_preset', $selectors_preset);
    foreach($selectors_preset as $selector_key => $selector) {
        $check_theme = false;
        foreach($selector['themes'] as $theme_name => $theme_data) {
            $check_theme = ( isset($theme_data['check']) ? $theme_data['check'] : ( $theme_name == $wptheme || $theme_name == $parent_wptheme ) );
            if( $check_theme ) {
                $selector['check'] = true;
                if( isset($theme_data['name']) ) {
                    $selector['name'] = $theme_data['name'];
                }
                unset($selectors_preset[$selector_key]);
                $selectors_preset = array($selector_key => $selector) + $selectors_preset;
                break;
            }
        }
        if( $check_theme ) {
            break;
        }
    }
    return $selectors_preset;
}
function bapf_settings_get_selectors_preset_js($args = array('select' => '.berocket_selectors_preset', 'option_name_template' => 'br_filters_options[%optname%]')) {
    $selectors_preset = bapf_settings_get_selectors_preset();
    ?><script>
var bapf_settings_selectors_presets = <?php echo json_encode($selectors_preset); ?>;
var bapf_settings_selectors_presets_args = <?php echo json_encode($args); ?>;
function bapf_settings_selectors_preset_js() {
    var selected_option = jQuery(bapf_settings_selectors_presets_args.select).val();
    if( typeof(bapf_settings_selectors_presets[selected_option]) != 'undefined' ) {
        jQuery.each(bapf_settings_selectors_presets[selected_option].options, function(option_slug) {
            var option_selector = '[name="'+bapf_settings_selectors_presets_args.option_name_template+'%opt%"]';
            option_selector = option_selector.replace('\%optname\%', option_slug);
            if( typeof(this) === 'object' && this !== null && Object.getPrototypeOf(this) === Object.prototype ) {
                jQuery.each(this, function(option_slug2) {
                    var option_selector2 = option_selector.replace('\%opt\%', '[' + option_slug2 + ']');
                    bapf_settings_selectors_preset_js_value_set(option_selector2, this);
                });
            } else {
                option_selector = option_selector.replace('\%opt\%', '');
                bapf_settings_selectors_preset_js_value_set(option_selector, this);
            }
        });
    }
}
function bapf_settings_selectors_preset_js_value_set(selector, value) {
    if( typeof(value) == 'boolean' || Object.getPrototypeOf(value) == Boolean.prototype ) {
        if( Object.getPrototypeOf(value) == Boolean.prototype ) {
            value = value.valueOf();
        }
        jQuery(selector).prop('checked', value);
    } else {
        jQuery(selector).val(value);
    }
}
jQuery(document).on('change', bapf_settings_selectors_presets_args.select, bapf_settings_selectors_preset_js);
<?php
$options_to_update = array();
foreach($selectors_preset as $selectors_preset_option) {
    $options_to_update = array_merge($options_to_update, $selectors_preset_option['options']);
}
$additional_options_to_update = array();
foreach($options_to_update as $options_to_update_key => $options_to_update_val) {
    if( is_array($options_to_update_val) ) {
        $additional_options_to_update[$options_to_update_key] = array_keys($options_to_update_val);
        unset($options_to_update[$options_to_update_key]);
    }
}
$options_to_update = array_keys($options_to_update);
$options_to_update = array_merge($options_to_update, $additional_options_to_update);
foreach($options_to_update as $option_to_update_key => $option_to_update) {
    $option_selector = '[name="'.$args['option_name_template'].'%opt%"]';
    if( is_array( $option_to_update ) ) {
        $option_selector = str_replace('%optname%', $option_to_update_key, $option_selector);
        $option_selector_arr = array();
        foreach($option_to_update as $option_to_update_val) {
            $option_selector_arr[] = str_replace('%opt%', '[' . $option_to_update_val . ']', $option_selector);
        }
        $option_selector = implode(', ', $option_selector_arr);
    } else {
        $option_selector = str_replace('%optname%', $option_to_update, $option_selector);
        $option_selector = str_replace('%opt%', '', $option_selector);
    }
?>jQuery(document).on('change', '<?php echo $option_selector; ?>', function() {
    jQuery(bapf_settings_selectors_presets_args.select).val('');
});<?php
}
?>
</script><?php
}
function bapf_update_selectors_preset_woodmart($selector_name = false) {
    if( $selector_name == 'Woodmart' ) {
        $options = get_option('xts-woodmart-options');
        if( ! is_array($options) ) {
            $options = array();
        }
        $options['icons_font_display'] = 'block';
        update_option('xts-woodmart-options', $options);
        $global_option = BeRocket_Framework::get_global_option();
        $global_option['fontawesome_frontend_version'] = 'fontawesome5';
        BeRocket_Framework::save_global_option($global_option);
    }
}
add_action('bapf_update_selectors_preset', 'bapf_update_selectors_preset_woodmart', 10, 1);