<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Payments_Frontend {
  private $debug;

  /**
   * Constructor
   */
  public function __construct() {
    $this->debug = Woo_Conditional_Payments_Debug::instance();

    // Load frontend styles and scripts
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10, 0 );

    if ( ! get_option( 'wcp_disable_all', false ) ) {
      // Filter payment methods
      add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_payment_methods' ], 10000, 1 );
      
      // Store all post data into the session so data can be used in filters
      add_action( 'woocommerce_checkout_update_order_review', [ $this, 'store_customer_details' ], 10, 1 );

      // Multicurrency support
      add_filter( 'wcp_convert_price', [ $this, 'convert_price' ], 10, 1 );
      add_filter( 'wcp_convert_price_reverse', [ $this, 'convert_price_reverse' ], 10, 1 );

      // Store order ID that's being currently processed
      add_action( 'woocommerce_checkout_order_processed', [ $this, 'store_order_id' ], 10, 1 );
    }

    add_action( 'woocommerce_blocks_loaded', [ $this, 'register_blocks_support' ], 10, 0 );
  }

  /**
   * Enqueue scripts and styles
   */
  public function enqueue_scripts() {
    if ( ! wcp_is_blocks_checkout() ) {
      wp_enqueue_script(
        'woo-conditional-payments-js',
        WOO_CONDITIONAL_PAYMENTS_URL . 'frontend/js/woo-conditional-payments.js',
        [ 'jquery' ],
        WOO_CONDITIONAL_PAYMENTS_ASSETS_VERSION
      );

      wp_localize_script( 'woo-conditional-payments-js', 'conditional_payments_settings', array(
        'name_address_fields' => $this->name_address_fields(),
        'disable_payment_method_trigger' => apply_filters( 'wcp_disable_payment_method_trigger', false ),
      ) );
    }

    wp_enqueue_style(
      'woo_conditional_payments_css',
      WOO_CONDITIONAL_PAYMENTS_URL . 'frontend/css/woo-conditional-payments.css',
      [],
      WOO_CONDITIONAL_PAYMENTS_ASSETS_VERSION
    );
  }

  /**
   * Register blocks support
   */
  public function register_blocks_support() {
    if ( interface_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
      require_once 'class-conditional-payments-blocks.php';

      add_action(
        'woocommerce_blocks_checkout_block_registration',
        function( $integration_registry ) {
          $integration_registry->register( new Woo_Conditional_Payments_Integration() );
        }
      );

      woocommerce_store_api_register_update_callback(
        [
          'namespace' => 'woo-conditional-payments',
          'callback' => function( $data ) {
            if ( WC()->session ) {
              $payment_method = isset( $data['payment_method'] ) ? $data['payment_method'] : null;

              WC()->session->set( 'chosen_payment_method', $payment_method );
            }
          }
        ]
      );

      woocommerce_store_api_register_endpoint_data(
        [
          'endpoint' => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
          'namespace' => 'woo-conditional-payments',
          'data_callback' => [ $this, 'store_api_data' ],
          'schema_callback' => [ $this, 'store_api_schema' ],
          'schema_type' => ARRAY_A,
        ]
      );
    }
  }

  /**
   * Store API data (Blocks checkout)
   */
  public function store_api_data() {
    $debug = false;
    if ( $this->debug->is_enabled() ) {
      $debug = $this->debug->output_debug_checkout( false );
    }

    $payment_method = false;
    if ( WC()->session ) {
      $payment_method = WC()->session->get( 'chosen_payment_method' );
    }

    return [
      'debug' => $debug,
      'payment_method' => $payment_method,
    ];
  }

  /**
   * Store API schema (Blocks checkout)
   */
  public function store_api_schema() {
    return [
      'debug' => [
        'description' => __( 'Debug information', 'woo-conditional-payments' ),
        'type' => [ 'string', 'null' ],
        'readonly' => true,
      ],
    ];
  }

  /**
   * Store order ID that's being currently processed
   */
  public function store_order_id( $order_id ) {
    if ( $order_id ) {
      $GLOBALS['wcp_processing_order_id'] = $order_id;
    }
  }
  
  /**
   * Get fields which require manual trigger for checkout update
   * 
   * By default changing first name, last name, company and certain other fields
   * do not trigger checkout update. Thus we need to trigger update manually if we have
   * conditions for these fields.
   * 
   * Triggering will be done in JS. However, we check here if we have conditions for these
   * fields. If we dont have, we dont want to trigger update as that would be unnecessary.
   */
  public function name_address_fields() {
    if ( false === ( $found_fields = get_transient( 'wcp_name_address_fields' ) ) ) {
      $rulesets = woo_conditional_payments_get_rulesets( true );

      $fields = array(
        'billing_first_name', 'billing_last_name', 'billing_company',
        'shipping_first_name', 'shipping_last_name', 'shipping_company',
        'billing_email', 'billing_phone',
      );
  
      $found_fields = array();
      foreach ( $rulesets as $ruleset ) {
        foreach ( $ruleset->get_conditions() as $condition ) {
          if ( in_array( $condition['type'], $fields ) ) {
            $found_fields[$condition['type']] = true;
          }

          // Special handling for "previous orders - match guests by email"
          if ( $condition['type'] === 'orders' && isset( $condition['orders_match_guests_by_email'] ) && $condition['orders_match_guests_by_email'] ) {
            $found_fields['billing_email'] = true;
          }

          // Special handling for Kybernaut IČO DIČ
          if ( strpos( $condition['type'], 'woolab_' ) !== false ) {
            $found_fields['billing_ic'] = true;
            $found_fields['billing_dic'] = true;
            $found_fields['billing_dic_dph'] = true;
          }
        }
      }

      $found_fields = array_keys( $found_fields );
      
      set_transient( 'wcp_name_address_fields', $found_fields, 60 * MINUTE_IN_SECONDS );
    }

    return $found_fields;
  }

  /**
   * Store customer details to the session for being used in filters
   */
  public function store_customer_details( $post_data ) {
    $data = array();
    parse_str( $post_data, $data );

    $attrs = array(
      'billing_first_name', 'billing_last_name', 'billing_company',
      'shipping_first_name', 'shipping_last_name', 'shipping_company',
      'billing_email', 'billing_phone'
    );

    $same_addr = FALSE;
    if ( ! isset( $data['ship_to_different_address'] ) || $data['ship_to_different_address'] != '1' ) {
      $same_addr = TRUE;
      $attrs = array(
        'billing_first_name', 'billing_last_name', 'billing_company', 'billing_email', 'billing_phone',
      );
    }

    foreach ( $attrs as $attr ) {
      WC()->customer->set_props( array(
        $attr => isset( $data[$attr] ) ? wp_unslash( $data[$attr] ) : null,
      ) );

      if ( $same_addr ) {
        $attr2 = str_replace( 'billing', 'shipping', $attr );
        WC()->customer->set_props( array(
          $attr2 => isset( $data[$attr] ) ? wp_unslash( $data[$attr] ) : null,
        ) );
      }
    }
  }

  /**
   * Filter payments methods
   */
  public function filter_payment_methods( $gateways ) {
    if ( is_admin() ) {
      return $gateways;
    }

    $this->debug->record_gateways( $gateways, 'before' );

    $rulesets = woo_conditional_payments_get_rulesets( true );
    $GLOBALS['wcp_passed_rule_ids'] = [];

    $disable_keys = [];
    $enable_keys = [];

    foreach ( $rulesets as $ruleset ) {
      $passes = $ruleset->validate();

      if ( $passes ) {
        $GLOBALS['wcp_passed_rule_ids'][] = $ruleset->get_id();
      }

      foreach ( $ruleset->get_actions() as $action_index => $action ) {
        if ( $action['type'] === 'disable_payment_methods' ) {
          if ( $passes ) {
            foreach ( $gateways as $key => $gateway ) {
              if ( wcp_method_selected( $key, $action ) ) {
                $disable_keys[$key] = true;
                unset( $enable_keys[$key] );
              }
            }
          }
        }

        if ( $action['type'] === 'enable_payment_methods' ) {
          foreach ( $gateways as $key => $gateway ) {
            if ( wcp_method_selected( $key, $action ) ) {
              if ( $passes ) {
                $enable_keys[$key] = true;
                unset( $disable_keys[$key] );
              } else {
                $disable_keys[$key] = true;
                unset( $enable_keys[$key] );
              }
            }
          }
        }

        if ( $action['type'] === 'enable_payment_methods_new' ) {
          if ( $passes ) {
            foreach ( $gateways as $key => $gateway ) {
              if ( wcp_method_selected( $key, $action ) ) {
                unset( $disable_keys[$key] );
              }
            }
          }
        }

        $this->debug->add_action( $ruleset->get_id(), $passes, $action_index, $action );
      }
    }

    foreach ( $gateways as $key => $gateway ) {
      if ( isset( $disable_keys[$key] ) && ! isset( $enable_keys[$key] ) ) {
        unset( $gateways[$key] );
      }
    }

    $this->debug->record_gateways( $gateways, 'after' );

    return $gateways;
  }

  /**
   * Convert price to the active currency from the default currency
   */
  public function convert_price( $value ) {
    // WooCommerce Currency Switcher by realmag777
    if ( isset( $GLOBALS['WOOCS'] ) && is_callable( [ $GLOBALS['WOOCS'], 'woocs_exchange_value' ] ) ) {
      return floatval( $GLOBALS['WOOCS']->woocs_exchange_value( $value ) );
    }

    // WPML
    if ( isset( $GLOBALS['woocommerce_wpml'] ) && isset( $GLOBALS['woocommerce_wpml']->multi_currency->prices ) && is_callable( [ $GLOBALS['woocommerce_wpml']->multi_currency->prices, 'convert_price_amount' ] ) ) {
      return floatval( $GLOBALS['woocommerce_wpml']->multi_currency->prices->convert_price_amount( $value ) );
    }

    // Currency Switcher by Aelia
    if ( isset( $GLOBALS['woocommerce-aelia-currencyswitcher'] ) && $GLOBALS['woocommerce-aelia-currencyswitcher'] ) {
      $base_currency = apply_filters( 'wc_aelia_cs_base_currency', false );

      return floatval( apply_filters( 'wc_aelia_cs_convert', $value, $base_currency, get_woocommerce_currency() ) );
    }

    // Price Based on Country for WooCommerce
    if ( class_exists( 'WCPBC_Pricing_Zones' ) ) {
      $zone = WCPBC_Pricing_Zones::get_zone( false );

      if ( ! empty( $zone ) && method_exists( $zone, 'get_exchange_rate_price' ) ) {
        return floatval( $zone->get_exchange_rate_price( $value ) );
      }
    }

    // CURCY – Multi Currency for WooCommerce
    if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
      $settings = WOOMULTI_CURRENCY_Data::get_ins();
      $currencies = $settings->get_list_currencies();
      $current_currency = $settings->get_current_currency();
      $rate = floatval( $currencies[$current_currency]['rate'] );

      if ( $rate <> 1 ) {
        return $value * $rate;
      }
    }

    return $value;
  }

  /**
   * Convert price to the default currency from the active currency
   */
  public function convert_price_reverse( $value ) {
    // WooCommerce Currency Switcher by realmag777
    if ( isset( $GLOBALS['WOOCS'] ) && is_callable( [ $GLOBALS['WOOCS'], 'convert_from_to_currency' ] ) ) {
      return floatval( $GLOBALS['WOOCS']->convert_from_to_currency( $value, $GLOBALS['WOOCS']->current_currency, $GLOBALS['WOOCS']->default_currency ) );
    }

    // WPML
    if ( isset( $GLOBALS['woocommerce_wpml'] ) && isset( $GLOBALS['woocommerce_wpml']->multi_currency->prices ) && is_callable( [ $GLOBALS['woocommerce_wpml']->multi_currency->prices, 'unconvert_price_amount' ] ) ) {
      return floatval( $GLOBALS['woocommerce_wpml']->multi_currency->prices->unconvert_price_amount( $value ) );
    }

    // Currency Switcher by Aelia
    if ( isset( $GLOBALS['woocommerce-aelia-currencyswitcher'] ) && $GLOBALS['woocommerce-aelia-currencyswitcher'] ) {
      $base_currency = apply_filters( 'wc_aelia_cs_base_currency', false );

      return floatval( apply_filters( 'wc_aelia_cs_convert', $value, get_woocommerce_currency(), $base_currency ) );
    }

    // Price Based on Country for WooCommerce
    if ( class_exists( 'WCPBC_Pricing_Zones' ) ) {
      $zone = WCPBC_Pricing_Zones::get_zone( false );

      if ( ! empty( $zone ) && method_exists( $zone, 'get_base_currency_amount' ) ) {
        return floatval( $zone->get_base_currency_amount( $value ) );
      }
    }

    // CURCY – Multi Currency for WooCommerce
    if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
      $settings = WOOMULTI_CURRENCY_Data::get_ins();
      $currencies = $settings->get_list_currencies();
      $current_currency = $settings->get_current_currency();
      $rate = floatval( $currencies[$current_currency]['rate'] );

      if ( $rate <> 1 ) {
        return $value / $rate;
      }
    }

    return $value;
  }
}
