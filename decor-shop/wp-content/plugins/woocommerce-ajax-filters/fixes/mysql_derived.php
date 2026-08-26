<?php
if( ! function_exists('berocket_aapf_disable_query_derived_merge') ) {
    function berocket_aapf_disable_query_derived_merge() {
        global $wpdb;
        $wpdb->query("SET SESSION optimizer_switch='derived_merge=off'");
    }
}
if( ! function_exists('berocket_aapf_enable_query_derived_merge') ) {
    function berocket_aapf_enable_query_derived_merge() {
        global $wpdb;
        $wpdb->query("SET SESSION optimizer_switch='derived_merge=on'");
    }
}
if( ! function_exists('berocket_aapf_query_derived_merge_init') ) {
    function berocket_aapf_query_derived_merge_init() {
        global $wpdb;
        $results = $wpdb->get_var("SELECT @@optimizer_switch");
        if( is_string($results) ) {
            $results = strtolower($results);
            if( strpos($results, 'derived_merge=on') !== FALSE ) {
                add_action('brapf_before_query', 'berocket_aapf_disable_query_derived_merge');
                add_action('brapf_after_query', 'berocket_aapf_enable_query_derived_merge');
            }
        }
    }
}
add_action('init', 'berocket_aapf_query_derived_merge_init', 1);