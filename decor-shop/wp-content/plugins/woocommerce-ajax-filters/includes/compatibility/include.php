<?php
$compatibility_features = array(
//WooCommerce widget
    'WCWidget' => array(
        'check' => true,
        'file' => 'wc-widgets.php'
    ),
//Builder compatibility
    'DiviBuilder' => array(
        'check' => defined('ET_CORE_VERSION'),
        'file' => 'divi-theme-builder.php'
    ),
    'BeaverBuilder' => array(
        'check' => defined('FL_BUILDER_VERSION'),
        'file' => 'beaver-builder.php'
    ),
    'Elementor' => array(
        'check' => defined( 'ELEMENTOR_PRO_VERSION'),
        'file' => 'elementor-pro.php'
    ),
    'WPBakery' => array(
        'check' => defined( 'WPB_VC_VERSION'),
        'file' => 'js_composer.php'
    ),
    'Flatsome' => array(
        'check' => defined( 'UXTHEMES_ACCOUNT_URL'),
        'file' => 'flatsome-ux-builder.php'
    ),
    'BreakdanceOxygen' => array(
        'check' => defined( '__BREAKDANCE_VERSION'),
        'file' => 'oxygen_builder.php'
    ),
    'Siteorigin' => array(
        'check' => defined( 'SITEORIGIN_PANELS_VERSION'),
        'file' => 'siteorigin.php'
    ),
    'LiveComposer' => array(
        'check' => defined( 'DS_LIVE_COMPOSER_VER'),
        'file' => 'live_composer.php'
    ),
//Plugin compatibility
    'RankMath' => array(
        'check' => class_exists('RankMath'),
        'file' => 'rank_math_seo.php'
    ),
    'WooMultiCurrency' => array(
        'check' => function_exists('wmc_get_price'),
        'file' => 'woo-multi-currency.php'
    ),
    'WOOCS' => array(
        'check' => defined('WOOCS_VERSION'),
        'file' => 'woocs.php'
    ),
    'WPML' => array(
        'check' => ((defined( 'WCML_VERSION' ) || defined('POLYLANG_VERSION')) && defined( 'ICL_LANGUAGE_CODE' )) || function_exists('wpm_get_language'),
        'file' => 'wpml.php'
    ),
    'WCPBCPricingZones' => array(
        'check' => class_exists('WCPBC_Pricing_Zones'),
        'file' => 'price-based-on-country.php'
    ),
    'bodycommerce' => array(
        'check' => defined( 'DE_DB_WOO_VERSION' ),
        'file' => 'bodycommerce.php'
    ),
    'WooJetpack' => array(
        'check' => defined( 'WCJ_PLUGIN_FILE' ),
        'file' => 'woojetpack.php'
    ),
    'relevanssi' => array(
        'check' => function_exists('relevanssi_do_query'),
        'file' => 'relevanssi.php'
    ),
    'PremmerceMulticurrency' => array(
        'check' => function_exists('premmerce_multicurrency'),
        'file' => 'premmerce-multicurrency.php'
    ),
    'AeliaCurrencySwitcher' => array(
        'check' => ! empty($GLOBALS['woocommerce-aelia-currencyswitcher']),
        'file' => 'aelia-currencyswitcher.php'
    ),
    'WPsearchWC' => array(
        'check' => defined( 'SEARCHWP_WOOCOMMERCE_VERSION'),
        'file' => 'wpsearch_wc_compatibility.php'
    ),
);
$compatibility_features = apply_filters('bapf_compatibility_include', $compatibility_features);
foreach($compatibility_features as $compatibility_feature) {
    if( ! isset($compatibility_feature['check']) || $compatibility_feature['check'] ) {
        include_once(plugin_dir_path( BeRocket_AJAX_filters_file ) . "includes/compatibility/" . $compatibility_feature['file']);
        if( isset($compatibility_feature['file_add']) && is_array($compatibility_feature['file_add']) ) {
            foreach($compatibility_feature['file_add'] as $file_add) {
                include_once(plugin_dir_path( BeRocket_AJAX_filters_file ) . $file_add);
            }
        }
    }
}