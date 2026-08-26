<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if blocks based checkout is active
 */
function wcp_is_blocks_checkout() {
  if ( class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) && is_callable( [ 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default'] ) && \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() ) {
    return true;
  }

  return false;
}

/**
 * Check if there are any active rulesets which has
 * "Add payment method fee" action
 */
function wcp_has_fee_action() {
  global $wpdb;

  $count = $wpdb->get_var(
    "SELECT COUNT(meta_id) FROM {$wpdb->postmeta} WHERE meta_key = '_wcp_actions' AND meta_value LIKE '%add_fee%' AND post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wcp_enabled' AND meta_value = 'yes')"
  );

  return $count > 0;
}

/**
 * Get rulesets
 * 
 * @return Woo_Conditional_Payments_Ruleset[]
 */
function woo_conditional_payments_get_rulesets( $only_enabled = false ) {
	$args = array(
		'post_status' => [ 'publish' ],
		'post_type' => 'wcp_ruleset',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
	);

  $posts = get_posts( $args );
  
  $rulesets = [];
  foreach ( $posts as $post ) {
    $ruleset = new Woo_Conditional_Payments_Ruleset( $post->ID );

    if ( ! $only_enabled || $ruleset->get_enabled() ) {
      $rulesets[] = $ruleset;
    }
  }

  $ordering = get_option( 'wcp_ruleset_order', false );
  if ( $ordering && is_array( $ordering ) ) {
    $order_end = 999;
    $ordered_rulesets = [];

    foreach ( $rulesets as $ruleset ) {
      $ruleset_id = $ruleset->get_id();

      if ( isset( $ordering[ $ruleset_id ] ) && is_numeric( $ordering[ $ruleset_id ] ) ) {
        $ordered_rulesets[ $ordering[ $ruleset_id ] ] = $ruleset;
      } else {
        $ordered_rulesets[ $order_end ] = $ruleset;
        $order_end++;
      }
    }

    ksort( $ordered_rulesets );

    $rulesets = $ordered_rulesets;
  }

  return $rulesets;
}

/**
 * Get a list of operators
 */
function woo_conditional_payments_operators() {
  return array(
    'gt' => __( 'greater than', 'woo-conditional-payments' ),
    'gte' => __( 'greater than or equal', 'woo-conditional-payments' ),
    'lt' => __( 'less than', 'woo-conditional-payments' ),
    'lte' => __( 'less than or equal', 'woo-conditional-payments' ),
    'e' => __( 'equals', 'woo-conditional-payments' ),
    'in' => __( 'include', 'woo-conditional-payments' ),
    'exclusive' => __( 'include only', 'woo-conditional-payments' ),
    'notin' => __( 'exclude', 'woo-conditional-payments' ),
    'is' => __( 'is', 'woo-conditional-payments' ),
    'isnot' => __( 'is not', 'woo-conditional-payments' ),
    'exists' => __( 'is not empty', 'woo-conditional-payments' ),
    'notexists' => __( 'is empty', 'woo-conditional-payments' ),
    'contains' => __( 'contains', 'woo-conditional-payments' ),
    'loggedin' => __( 'logged in', 'woo-conditional-payments' ),
    'loggedout' => __( 'logged out', 'woo-conditional-payments' ),
  );
}

/**
 * Get a list of filters
 */
function woo_conditional_payments_filters() {
  $groups = woo_conditional_payments_filter_groups();

  $filters = array();
  foreach ( $groups as $group ) {
    foreach ( $group['filters'] as $key => $filter ) {
      $filters[$key] = $filter;
    }
  }

  return $filters;
}

/**
 * Get a list of filter groups
 */
function woo_conditional_payments_filter_groups() {
  $filters = [
    'general' => array(
      'title' => __( 'General', 'woo-conditional-payments' ),
      'filters' => array(
        'subtotal' => array(
          'title' => __( 'Order Subtotal', 'woo-conditional-payments' ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte', 'e' ),
        ),
        'shipping_method' => array(
          'title' => __( 'Shipping Method', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot' )
        ),
        'items' => array(
          'title' => __( 'Number of Items', 'woo-conditional-payments' ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte', 'e' ),
          'pro' => true,
        ),
        'coupon' => array(
          'title' => __( 'Coupon', 'woo-conditional-payments' ),
          'operators' => array( 'in', 'notin' ),
          'pro' => true,
        ),
      ),
    ),
    'products' => [
      'title' => __( 'Products', 'woo-conditional-payments' ),
      'filters' => [
        'products' => [
          'title' => __( 'Products', 'woo-conditional-payments' ),
          'operators' => [ 'in', 'exclusive', 'notin' ]
        ],
        'product_cats' => [
          'title' => __( 'Product Categories', 'woo-conditional-payments' ),
          'operators' => [ 'in', 'exclusive', 'notin' ],
          'pro' => true,
        ],
        'product_tags' => [
          'title' => __( 'Product Tags', 'woo-conditional-payments' ),
          'operators' => [ 'in', 'exclusive', 'notin' ],
          'pro' => true,
        ],
        'product_brands' => [
          'title' => __( 'Product Brands', 'woo-conditional-payments' ),
          'operators' => [ 'in', 'exclusive', 'notin' ],
          'pro' => true,
        ],
        'product_types' => [
          'title' => __( 'Product Types', 'woo-conditional-payments' ),
          'operators' => [ 'in', 'exclusive', 'notin' ],
          'pro' => true,
        ],
        'shipping_class' => [
          'title' => __( 'Shipping Class', 'woo-conditional-payments' ),
          'operators' => [ 'in', 'exclusive', 'notin' ],
          'pro' => true,
        ],
        'stock_status' => [
          'title' => __( 'Stock Status', 'woo-conditional-payments' ),
          'operators' => [ 'in', 'notin' ],
          'pro' => true,
        ],
        'product_meta' => [
          'title' => __( 'Product Meta', 'woo-conditional-payments' ),
          'operators' => [
            'gt', 'gte', 'lt', 'lte', 'e',
            'in', 'exclusive', 'notin',
            'exists', 'notexists',
          ],
          'pro' => true,
        ],
      ],
    ],
    'billing_address' => array(
      'title' => __( 'Billing Address', 'woo-conditional-payments' ),
      'filters' => array(
        'billing_first_name' => array(
          'title' => __( 'First Name (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'billing_last_name' => array(
          'title' => __( 'Last Name (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'billing_company' => array(
          'title' => __( 'Company (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'billing_state' => array(
          'title' => __( 'State (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot' ),
        ),
        'billing_country' => array(
          'title' => __( 'Country (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot' ),
        ),
        'billing_address_1' => array(
          'title' => __( 'Address (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'billing_address_2' => array(
          'title' => __( 'Address 2 (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'billing_city' => array(
          'title' => __( 'City (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains', 'is', 'isnot' ),
        ),
        'billing_postcode' => array(
          'title' => __( 'Postcode (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'is' ),
        ),
        'billing_email' => array(
          'title' => __( 'Email (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot', 'exists', 'notexists' ),
          'pro' => true,
        ),
        'billing_phone' => array(
          'title' => __( 'Phone (billing)', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot', 'exists', 'notexists' ),
          'pro' => true,
        ),
      ),
    ),
    'shipping_address' => array(
      'title' => __( 'Shipping Address', 'woo-conditional-payments' ),
      'filters' => array(
        'shipping_first_name' => array(
          'title' => __( 'First Name (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'shipping_last_name' => array(
          'title' => __( 'Last Name (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'shipping_company' => array(
          'title' => __( 'Company (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'shipping_state' => array(
          'title' => __( 'State (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot' ),
        ),
        'shipping_country' => array(
          'title' => __( 'Country (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot' ),
        ),
        'shipping_address_1' => array(
          'title' => __( 'Address (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'shipping_address_2' => array(
          'title' => __( 'Address 2 (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains' ),
        ),
        'shipping_city' => array(
          'title' => __( 'City (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'contains', 'is', 'isnot' ),
        ),
        'shipping_postcode' => array(
          'title' => __( 'Postcode (shipping)', 'woo-conditional-payments' ),
          'operators' => array( 'exists', 'notexists', 'is' ),
        ),
      ),
    ),
    'customer' => array(
      'title' => __( 'Customer', 'woo-conditional-payments' ),
      'filters' => array(
        'customer_authenticated' => array(
          'title' => __( 'Logged in / out', 'woo-conditional-payments' ),
          'operators' => array( 'loggedin', 'loggedout' ),
          'pro' => true,
        ),
        'customer_role' => array(
          'title' => __( 'Role', 'woo-conditional-payments' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'orders' => array(
          'title' => __( 'Previous orders', 'woo-conditional-payments' ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte', 'e' ),
          'pro' => true,
        ),
        'ip_address' => [
          'title' => __( 'IP address', 'woo-conditional-payments' ),
          'operators' => [ 'is', 'isnot' ],
          'pro' => true,
        ],
        'vat_exempt' => [
          'title' => __( 'VAT exempt', 'woo-conditional-payments' ),
          'operators' => [ 'is', 'isnot' ],
          'pro' => true,
        ],
      ),
    )
  ];

  // WooCommerce Germanized Pro
  if ( class_exists( 'WooCommerce_Germanized_Pro' ) ) {
    $customer_filters['filters']['vat_id_germanized'] = [
      'title' => __( 'VAT ID (Germanized for WooCommerce)', 'woo-conditional-payments' ),
      'operators' => [ 'exists', 'notexists' ],
      'pro' => true,
    ];
  }

  // https://wordpress.org/plugins/woocommerce-eu-vat-assistant/
  if ( class_exists( '\Aelia\WC\EU_VAT_Assistant\WC_Aelia_EU_VAT_Assistant' ) ) {
    $customer_filters['filters']['vat_number_aelia'] = [
      'title' => __( 'VAT number (Aelia EU VAT Assistant)', 'woo-conditional-payments' ),
      'operators' => [ 'exists', 'notexists' ],
      'pro' => true,
    ];
  }

  // https://wordpress.org/plugins/woolab-ic-dic/
  if ( function_exists( 'woolab_icdic_init' ) ) {
    $customer_filters['filters']['woolab_billing_ic'] = [
      'title' => __( 'Business ID (Kybernaut IČO DIČ)', 'woo-conditional-payments' ),
      'operators' => [ 'exists', 'notexists' ],
      'callback' => [ 'Woo_Conditional_Payments_Filters_Pro', 'filter_woolab_fields' ],
      'pro' => true,
    ];
    
    $customer_filters['filters']['woolab_billing_dic'] = [
      'title' => __( 'Tax ID (Kybernaut IČO DIČ)', 'woo-conditional-payments' ),
      'operators' => [ 'exists', 'notexists' ],
      'callback' => [ 'Woo_Conditional_Payments_Filters_Pro', 'filter_woolab_fields' ],
      'pro' => true,
    ];
    
    $customer_filters['filters']['woolab_billing_dic_dph'] = [
      'title' => __( 'VAT reg. no. (Kybernaut IČO DIČ)', 'woo-conditional-payments' ),
      'operators' => [ 'exists', 'notexists' ],
      'callback' => [ 'Woo_Conditional_Payments_Filters_Pro', 'filter_woolab_fields' ],
      'pro' => true,
    ];
  }

  $language_filters = array(
    'title' => __( 'Language', 'woo-conditional-payments' ),
    'filters' => array(
      'lang_polylang' => array(
        'title' => __( 'Language - Polylang (inactive)', 'woo-conditional-payments' ),
        'operators' => array( 'is', 'isnot' ),
        'pro' => true,
      ),
      'lang_wpml' => array(
        'title' => __( 'Language - WPML (inactive)', 'woo-conditional-payments' ),
        'operators' => array( 'is', 'isnot' ),
        'pro' => true,
      ),
    ),
  );
  
  $filters['language'] = $language_filters;

  // Polylang language
  if ( function_exists( 'pll_the_languages' ) ) {
    $filters['language']['filters']['lang_polylang']['title'] = __( 'Language - Polylang (active)', 'woo-conditional-payments' );
  }

  // WPML language
  if ( function_exists( 'icl_object_id' ) ) {
    $filters['language']['filters']['lang_wpml']['title'] = __( 'Language - WPML (active)', 'woo-conditional-payments' );
  }

  // Groups (https://wordpress.org/plugins/groups/)
  if ( defined( 'GROUPS_CORE_VERSION' ) ) {
    $filters['customer']['filters']['groups'] = array(
      'title' => __( 'Groups', 'woo-conditional-payments' ),
      'operators' => array( 'in', 'notin' ),
      'pro' => true,
    );
  }

  $misc_filters = [
    'title' => __( 'Misc', 'woo-conditional-payments' ),
    'filters' => [
      'date' => [
        'title' => __( 'Date', 'woo-conditional-payments' ),
        'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
        'pro' => true,
      ],
      'time' => [
        'title' => __( 'Time', 'woo-conditional-payments' ),
        'operators' => [ 'gt', 'gte', 'lt', 'lte' ],
        'pro' => true,
      ],
    ]
  ];

  $filters['misc'] = $misc_filters;

  return apply_filters( 'woo_conditional_payments_filters', $filters );
}

/**
 * Get a list of actions
 */
function woo_conditional_payments_actions() {
  return apply_filters( 'woo_conditional_payments_actions', [
    'disable_payment_methods' => [
      'title' => __( 'Disable payment methods', 'woo-conditional-payments' ),
      'group' => 'availability',
    ],
    'enable_payment_methods_new' => [
      'title' => __( 'Enable payment methods', 'woo-conditional-payments' ),
      'group' => 'availability',
    ],
    'enable_payment_methods' => [
      'title' => __( 'Allow payment methods', 'woo-conditional-payments' ),
      'group' => 'availability',
    ],
    'add_fee' => [
      'title' => __( 'Add payment method fee', 'woo-conditional-payments' ),
      'pro' => true,
      'group' => 'fees',
    ],
    'set_no_payments_methods_msg' => [
      'title' => __( 'Set no payment methods available message', 'woo-conditional-payments' ),
      'pro' => true,
      'group' => 'messages',
    ],
  ] );
}

/**
 * Get grouped actions
 */
function wcp_get_grouped_actions() {
  $actions = woo_conditional_payments_actions();
  $groups = [
    'availability' => [
      'title' => __( 'Availability', 'woo-conditional-payments' ),
      'actions' => [],
    ],
    'fees' => [
      'title' => __( 'Fees', 'woo-conditional-payments' ),
      'actions' => [],
    ],
    'messages' => [
      'title' => __( 'Messages', 'woo-conditional-payments' ),
      'actions' => [],
    ],
    'other' => [
      'title' => __( 'Other', 'woo-conditional-payments' ),
      'actions' => [],
    ],
  ];

  foreach ( $actions as $key => $action ) {
    $group = isset( $action['group'] ) ? $action['group'] : 'other';

    if ( isset( $groups[$group] ) ) {
      $groups[$group]['actions'][$key] = $action;
    } else {
      $groups['other']['actions'][$key] = $action;
    }
  }

  // Remove empty groups
  foreach ( $groups as $key => $group ) {
    if ( empty( $group['actions'] ) ) {
      unset( $groups[$key] );
    }
  }

  return $groups;
}

/**
 * Get all payment methods to be used in a select field
 */
function woo_conditional_payments_get_payment_method_options() {
  $gateways = WC()->payment_gateways->payment_gateways();

  $options = [
    '_all' => __( 'All payment methods', 'woo-conditional-payments' ),
  ];

  foreach ( $gateways as $id => $gateway ) {
    $options[$id] = $gateway->get_method_title() ? $gateway->get_method_title() : $id;
  }

  return apply_filters( 'wcp_payment_method_options', $options );
}

/**
 * Check if payment method is selected
 */
function wcp_method_selected( $method_id, $action ) {
  $method_ids = isset( $action['payment_method_ids'] ) ? (array) $action['payment_method_ids'] : [];

  $passes = [
    'all' => in_array( '_all', $method_ids, true ),
    'instance' => ( $method_id !== false && in_array( $method_id, (array) $method_ids ) ),
  ];

  return in_array( true, $passes, true );
}

/**
 * Get payment gateway instance
 */
function woo_conditional_payments_get_payment_method( $id ) {
  $gateways = WC()->payment_gateways->payment_gateways();

  if ( isset( $gateways[$id] ) ) {
    return $gateways[$id];
  }

  return null;
}

/**
 * Get list of price modes
 */
function wcp_get_fee_modes() {
  $currency_symbol = get_woocommerce_currency_symbol();

  return [
    'fixed' => $currency_symbol,
    'pct' => '% of subtotal',
    'pct_no_tax' => '% of subtotal excl. tax',
    'pct_total' => '% of total',
    'pct_total_no_tax' => '% of total excl. tax',
  ];
}

/**
 * Get ruleset admin edit URL
 */
function wcp_get_ruleset_admin_url( $ruleset_id ) {
  $url = add_query_arg( array(
    'ruleset_id' => $ruleset_id,
  ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' ) );

  return $url;
}

/**
 * Get label for ruleset operator
 */
function wcp_get_ruleset_operator_label( $ruleset_id ) {
  $operator = get_post_meta( $ruleset_id, '_wcp_operator', true );

  switch ( $operator ) {
    case 'or':
      return __( 'One condition has to pass (OR)', 'woo-conditional-payments' );
    default:
      return __( 'All conditions have to pass (AND)', 'woo-conditional-payments' );
  }
}

/**
 * Get coupon title
 */
function wcp_get_coupon_title( $coupon_id ) {
  $general_options = [
    '_all' => __( '- All coupons -', 'woo-conditional-payments' ),
    '_free_shipping' => __( '- Free shipping coupons -', 'woo-conditional-payments' ),
  ];

  if ( isset( $general_options[$coupon_id] ) ) {
    return $general_options[$coupon_id];
  }

  return get_the_title( $coupon_id );
}

/**
 * Get gateway title
 */
function woo_conditional_payments_get_method_title( $id ) {
  $gateway = woo_conditional_payments_get_payment_method( $id );

  if ( $gateway ) {
    return $gateway->get_method_title();
  }

  return __( 'N/A', 'woo-conditional-payments' );
}

/**
 * Get action title
 */
function woo_conditional_payments_action_title( $action_id ) {
  $actions = woo_conditional_payments_actions();

  if ( isset( $actions[$action_id] ) ) {
    return $actions[$action_id]['title'];
  }

  return __( 'N/A', 'woo-conditional-payments' );
}

/**
 * Get settings link for a gateway
 */
function woo_conditional_payments_get_gateway_url( $gateway ) {
  return add_query_arg( array(
    'page' => 'wc-settings',
    'tab' => 'checkout',
    'section' => $gateway->id,
  ), admin_url( 'admin.php' ) );
}

/**
 * Format ruleset IDs into a list of links
 */
function woo_conditional_payments_format_ruleset_ids( $ids ) {
  $items = array();

  foreach ( $ids as $id ) {
    $ruleset = new Woo_Conditional_Payments_Ruleset( $id );

    if ( $ruleset->get_post() ) {
      $items[] = sprintf( '<a href="%s" target="_blank">%s</a>', $ruleset->get_admin_edit_url(), $ruleset->get_title() );
    }
  }

  return implode( ', ', $items );
}

/**
 * Load all shipping methods to be used in a select field
 */
function woo_conditional_payments_get_shipping_method_options() {
  $shipping_zones = array( new WC_Shipping_Zone( 0 ) );
  $shipping_zones = array_merge( $shipping_zones, WC_Shipping_Zones::get_zones() );

  $options = [
    'general' => [
      'title' => __( 'General', 'woo-conditional-payments' ),
      'methods' => [
        [
          'title' => __( 'Match by name', 'woo-conditional-payments' ),
          'rate_id' => '_name_match',
          'instance_id' => '_name_match',
          'combined_id' => '_name_match&_name_match',
        ],
      ],
    ]
  ];

  foreach ( $shipping_zones as $shipping_zone ) {
    if ( is_array( $shipping_zone ) && isset( $shipping_zone['zone_id'] ) ) {
      $shipping_zone = WC_Shipping_Zones::get_zone( $shipping_zone['zone_id'] );
    } else if ( ! is_object( $shipping_zone ) ) {
      // Skip
      continue;
    }

    $methods = [];
    foreach ( $shipping_zone->get_shipping_methods() as $shipping_method ) {
      if ( method_exists( $shipping_method, 'get_rate_id' ) ) {
        $methods[] = [
          'title' => $shipping_method->title,
          'rate_id' => $shipping_method->get_rate_id(),
          'instance_id' => $shipping_method->get_instance_id(),
          'combined_id' => implode( '&', array( $shipping_method->get_rate_id(), $shipping_method->get_instance_id()) ),
        ];
      }
    }

    if ( ! empty( $methods ) ) {
      $options[$shipping_zone->get_id()] = [
        'title' => $shipping_zone->get_zone_name(),
        'methods' => $methods,
      ];
    }
  }

  $options = apply_filters( 'woo_conditional_payments_shipping_method_options', $options );

  return $options;
}

/**
 * Get shipping method name by rate ID
 */
function wcp_get_shipping_method_title_by_id( $shipping_method ) {
  // Rates are stored in session
  if ( WC()->session && is_callable( [ WC()->session, 'get' ] ) ) {
    $shipping = WC()->session->get( 'shipping_for_package_0' );
    if ( is_array( $shipping ) && isset( $shipping['rates'] ) && is_array( $shipping['rates'] ) ) {
      foreach ( $shipping['rates'] as $rate_id => $rate ) {
        if ( $rate_id && $rate_id === $shipping_method['rate_id'] && is_a( $rate, 'WC_Shipping_Rate' ) ) {
          return $rate->get_label();
        }
      }
    }
  }

  return false;
}

/**
 * Get shipping class options
 */
function woo_conditional_payments_get_shipping_class_options() {
  $shipping_classes = WC()->shipping->get_shipping_classes();
  $shipping_class_options = array();
  foreach ( $shipping_classes as $shipping_class ) {
    $shipping_class_options[$shipping_class->term_id] = $shipping_class->name;
  }

  return $shipping_class_options;
}

/**
 * Get product type options
 */
function wcp_get_product_type_options() {
  $options = [
    'physical' => __( 'Physical products', 'woo-conditional-payments' ),
    'virtual' => __( 'Virtual products', 'woo-conditional-payments' ),
    'downloadable' => __( 'Downloadable products', 'woo-conditional-payments' ),
    'subscription' => __( 'Subscription products', 'woo-conditional-payments' ),
  ];

  return $options;
}

/**
 * Get stock status options
 */
function wcp_get_stock_status_options() {
  $options = [
    'instock' => __( 'In stock', 'woo-conditional-payments' ),
    'backorders' => __( 'Backorders', 'woo-conditional-payments' ),
  ];

  return $options;
}

/**
 * Get order status options
 */
function wcp_order_status_options() {
  if ( ! function_exists( 'wc_get_order_statuses' ) ) {
    return [];
  }

  return wc_get_order_statuses();
}

/**
 * Get category options
 */
function woo_conditional_payments_get_category_options() {
  $categories = get_terms( 'product_cat', array(
    'hide_empty' => false,
  ) );

  $sorted = array();
  woo_conditional_payments_sort_terms_hierarchicaly( $categories, $sorted );

  // Flatten hierarchy
  $options = array();
  woo_conditional_payments_flatten_terms( $options, $sorted );

  return $options;
}

/**
 * Output term tree into a select field options
 */
function woo_conditional_payments_flatten_terms( &$options, $cats, $depth = 0 ) {
  foreach ( $cats as $cat ) {
    if ( $depth > 0 ) {
      $prefix = str_repeat( ' - ', $depth );
      $options[$cat->term_id] = "{$prefix} {$cat->name}";
    } else {
      $options[$cat->term_id] = "{$cat->name}";
    }

    if ( isset( $cat->children ) && ! empty( $cat->children ) ) {
      woo_conditional_payments_flatten_terms( $options, $cat->children, $depth + 1 );
    }
  }
}

/**
 * Sort categories hierarchically
 */
function woo_conditional_payments_sort_terms_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {
  foreach ( $cats as $i => $cat ) {
    if ( $cat->parent == $parentId ) {
      $into[$cat->term_id] = $cat;
      unset( $cats[$i] );
    }
  }

  foreach ( $into as $topCat ) {
    $topCat->children = array();
    woo_conditional_payments_sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
  }
}

/**
 * Load all roles to be used in a select field
 */
function woo_conditional_payments_role_options() {
  global $wp_roles;
  
  $options = [];

  if ( is_a( $wp_roles, 'WP_Roles' ) && isset( $wp_roles->roles ) ) {
    $roles = $wp_roles->roles;

    foreach ( $roles as $role => $details ) {
      $name = translate_user_role( $details['name'] );
      $options[$role] = $name;
    }
  }

  return $options;
}

/**
 * Load all groups (from itthinx 3rd party plugin) to be used in a select field
 */
function woo_conditional_payments_groups_options() {
  if ( ! defined( 'GROUPS_CORE_VERSION' ) || ! function_exists( '_groups_get_tablename' ) ) {
    return array();
  }

  global $wpdb;

  $groups_table = _groups_get_tablename( 'group' );
  $groups = $wpdb->get_results( "SELECT * FROM $groups_table ORDER BY name" );

  $options = array();
  if ( $groups ) {
    foreach ( $groups as $group ) {
      $options[$group->group_id] = $group->name;
    }
  }

  return $options;
}

/**
 * Load all Polylang languages to be used in a select field
 */
function woo_conditional_payments_polylang_options() {
  $options = array();

  if ( function_exists( 'pll_languages_list' ) ) {
    $langs = pll_languages_list( array(
      'fields' => NULL, // return all fields
    ) );

    foreach ( $langs as $lang ) {
      $options[$lang->slug] = $lang->name;
    }
  }

  return $options;
}

/**
 * Load all WPML languages to be used in a select field
 */
function woo_conditional_payments_wpml_options() {
  $options = array();

  if ( function_exists( 'icl_object_id' ) ) {
    $langs = apply_filters( 'wpml_active_languages', NULL, 'orderby=name&order=asc' );

    foreach ( $langs as $lang ) {
      $options[$lang['code']] = $lang['translated_name'];
    }
  }

  return $options;
}

/**
 * Currency options
 */
function wcp_currency_options() {
  return get_woocommerce_currencies();
}

/**
 * Country options
 */
function woo_conditional_payments_country_options() {
  $countries_obj = new WC_Countries();

  return $countries_obj->get_countries();
}

/**
 * State options
 */
function woo_conditional_payments_state_options() {
  $countries_obj = new WC_Countries();
  $countries = $countries_obj->get_countries();
  $states = array_filter( $countries_obj->get_states() );

  $options = [];

  foreach ( $states as $country_id => $state_list ) {
    $options[$country_id] = [
      'states' => $state_list,
      'country' => $countries[$country_id],
    ];
  }

  // Move US as first as it is the most commonly used
  $us = $options['US'];
  unset( $options['US'] );
  $options = ['US' => $us] + $options;

  return $options;
}

/**
 * Fee taxation options
 */
function woo_conditional_payments_fee_tax_options() {
  $options = array(
    '_none' => __( '- Not taxable -', 'woo-conditional-payments' ),
  );

  $options += wc_get_product_tax_class_options();

  return $options;
}

/**
 * Get product categories
 */
function woo_conditional_payments_get_product_cats( $product_id ) {
  $cat_ids = array();

  if ( $product = wc_get_product( $product_id ) ) {
    $terms = get_the_terms( $product->get_id(), 'product_cat' );
    if ( $terms ) {
      foreach ( $terms as $term ) {
        $cat_ids[$term->term_id] = true;
      }
    }

    // If this is variable product, append parent product categories
    if ( $product->get_parent_id() ) {
      $terms = get_the_terms( $product->get_parent_id(), 'product_cat' );
      if ( $terms ) {
        foreach ( $terms as $term ) {
          $cat_ids[$term->term_id] = true;
        }
      }
    }

    // Finally add all parent terms
    if ( apply_filters( 'woo_conditional_payments_incl_parent_cats', true ) ) {
      foreach ( array_keys( $cat_ids ) as $term_id ) {
        $ancestors = (array) get_ancestors( $term_id, 'product_cat', 'taxonomy' );

        foreach ( $ancestors as $ancestor_id ) {
          $cat_ids[$ancestor_id] = true;
        }
      }
    }
  }

  $cat_ids = array_keys( $cat_ids );

  // Special handling for WPML
  if ( function_exists( 'icl_object_id' ) ) {
    $default_lang = apply_filters( 'wpml_default_language', NULL );

    foreach ( $cat_ids as $key => $cat_id ) {
      $orig_cat_id = apply_filters( 'wpml_object_id', $cat_id, 'product_cat', true, $default_lang );

      $cat_ids[$key] = $orig_cat_id;
    }
  }

  return $cat_ids;
}

/**
 * Get product tags
 */
function wcp_get_product_tags( $product_id ) {
  $tag_ids = [];

  if ( $product = wc_get_product( $product_id ) ) {
    $terms = get_the_terms( $product->get_id(), 'product_tag' );
    if ( $terms ) {
      foreach ( $terms as $term ) {
        $tag_ids[$term->term_id] = true;
      }
    }

    // If this is variable product, append parent product categories
    if ( $product->get_parent_id() ) {
      $terms = get_the_terms( $product->get_parent_id(), 'product_tag' );
      if ( $terms ) {
        foreach ( $terms as $term ) {
          $tag_ids[$term->term_id] = true;
        }
      }
    }
  }

  $tag_ids = array_keys( $tag_ids );

  // Special handling for WPML
  if ( function_exists( 'icl_object_id' ) ) {
    $default_lang = apply_filters( 'wpml_default_language', NULL );

    foreach ( $tag_ids as $key => $tag_id ) {
      $orig_tag_id = apply_filters( 'wpml_object_id', $tag_id, 'product_tag', true, $default_lang );

      $tag_ids[$key] = $orig_tag_id;
    }
  }

  return $tag_ids;
}

/**
 * Get product brands
 */
function wcp_get_product_brands( $product_id ) {
  $brand_ids = [];

  if ( $product = wc_get_product( $product_id ) ) {
    $terms = get_the_terms( $product->get_id(), 'product_brand' );
    if ( $terms ) {
      foreach ( $terms as $term ) {
        $brand_ids[$term->term_id] = true;
      }
    }

    // If this is variable product, append parent product brands
    if ( $product->get_parent_id() ) {
      $terms = get_the_terms( $product->get_parent_id(), 'product_brand' );
      if ( $terms ) {
        foreach ( $terms as $term ) {
          $brand_ids[$term->term_id] = true;
        }
      }
    }
  }

  $brand_ids = array_keys( $brand_ids );

  // Special handling for WPML
  if ( function_exists( 'icl_object_id' ) ) {
    $default_lang = apply_filters( 'wpml_default_language', NULL );

    foreach ( $brand_ids as $key => $brand_id ) {
      $orig_brand_id = apply_filters( 'wpml_object_id', $brand_id, 'product_brand', true, $default_lang );

      $brand_ids[$key] = $orig_brand_id;
    }
  }

  return $brand_ids;
}

/**
 * Get condition or action title
 */
function wcp_get_control_title( $control ) {
  if ( isset( $control['pro'] ) && $control['pro'] ) {
    return sprintf( __( '%s (Pro)', 'woo-conditional-payments' ), $control['title'] );
  }

  return $control['title'];
}

/**
 * Escape text to be used in JS template
 */
function wcp_esc_html( $text ) {
  // Escape curly braces because they will be intepreted as JS variables
  $text = str_replace( '{', '&#123;', $text );
  $text = str_replace( '}', '&#125;', $text );

  // Normal HTML escape
  return esc_html( $text );
}

/**
 * Options for time hours filter
 */
function wcp_time_hours_options() {
  $options = array();

  for ( $i = 0; $i < 24; $i++ ) {
    $timestamp = strtotime( 'monday midnight' ) + $i * 3600;

    $options[$i] = date_i18n( 'H', $timestamp );
  }

  return $options;
}

/**
 * Options for time minutes filter
 */
function wcp_time_mins_options() {
  $options = array();

  for ( $i = 0; $i < 60; $i++ ) {
    $timestamp = strtotime( 'monday midnight' ) + $i * 60;

    $options[$i] = date_i18n( 'i', $timestamp );
  }

  return $options;
}

/**
 * Check if WPML has translatable strings for this plugin
 */
function wcp_wpml_has_strings() {
  if ( function_exists( 'icl_st_get_contexts' ) ) {
    $contexts = icl_st_get_contexts( false );

    if ( is_array( $contexts ) ) {
      foreach ( $contexts as $context ) {
        if ( is_object( $context ) && isset( $context->context ) && $context->context === 'Conditional Payments for WooCommerce' ) {
          return true;
        }
      }
    }
  }

  return false;
}

/**
 * Debug mode options
 */
function wcp_debug_mode_options() {
  return [
    '' => __( 'Disabled', 'woo-conditional-payments' ),
    'admin' => __( 'Admin only', 'woo-conditional-payments' ),
    '1' => __( 'Public', 'woo-conditional-payments' ),
  ];
}

/**
 * Check if operator is numerical
 */
function wcp_is_operator_numerical( $operator ) {
  return in_array( $operator, [ 'gt', 'gte', 'lt', 'lte', 'e' ], true );
}

/**
 * Check if operator is set based
 */
function wcp_is_operator_set( $operator ) {
  return in_array( $operator, [ 'in', 'exclusive', 'notin', 'allin'  ], true );
}

/**
 * Check if operator is boolean
 */
function wcp_is_operator_boolean( $operator ) {
  return in_array( $operator, [ 'exists', 'notexists' ], true );
}
