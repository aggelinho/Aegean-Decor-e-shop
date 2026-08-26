<?php
//Fix for theme that not support WooCommerce
if( ! function_exists('berocket_aapf_wcshortcode_is_filtering_fix_custom') ) {
    function berocket_aapf_wcshortcode_is_filtering_fix_custom($is_filtering) {
        if( has_filter( 'woocommerce_shortcode_products_query', array( 'WC_Template_Loader', 'unsupported_archive_layered_nav_compatibility' ) ) ) {
            $is_filtering = true;
        }
        return $is_filtering;
    }
}
add_filter('berocket_aapf_wcshortcode_is_filtering', 'berocket_aapf_wcshortcode_is_filtering_fix_custom');
function berocket_enable_woocommerce_brand_order($args) {
    if( $args['taxonomy'] == 'product_brand' && empty($args['orderby']) ) {
        $args['force_menu_order_sort'] = true;
    }
    return $args;
}
add_filter('berocket_aapf_get_terms_class_args', 'berocket_enable_woocommerce_brand_order');