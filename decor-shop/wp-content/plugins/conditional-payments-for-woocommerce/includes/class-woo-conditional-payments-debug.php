<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Payments_Debug {
  private static $instance = null;

  private $block_rendered = false;

  private $data = [];
  private $customer_roles = [];
  private $states = [];
  private $countries = [];
  private $hours = [];
  private $mins = [];
  private $payment_methods = [];

  /**
   * Constructor
   */
  public function __construct() {
    if ( ! $this->is_enabled() ) {
      return;
    }

    // Format data
    add_action( 'woocommerce_init', [ $this, 'format' ], 10, 0 );

    // Enqueue scripts
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10, 0 );

    // Render debug information (blocks-based checkout)
    add_filter( 'render_block', [ $this, 'render_block' ], 10, 1 );

    // Output debug information
    add_action( 'woocommerce_before_checkout_form', [ $this, 'output_debug_checkout' ], 10, 0 );

    // Output debug information in "Pay for order" page
    add_action( 'before_woocommerce_pay_form', [ $this, 'output_debug_checkout' ], 10, 0 );

    // Add debug info to fragments
    add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'debug_fragment' ], 10, 1 );
  }

  /**
   * Get instance
   */
  public static function instance() {
    if ( self::$instance == null ) {
      self::$instance = new Woo_Conditional_Payments_Debug();
    }
 
    return self::$instance;
  }

  /**
   * Render debug information in the checkout (blocks-based checkout) 
   */
  public function render_block( $content ) {
    if ( ! $this->block_rendered && wcp_is_blocks_checkout() && function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() && ! isset( $_GET['pay_for_order'] ) ) {
      $this->output_debug_checkout( true );
    }

    $this->block_rendered = true;

    return $content;
  }

  /**
   * Enqueue scripts and styles
   */
  public function enqueue_scripts() {
    wp_enqueue_script(
      'woo-conditional-payments-debug-js',
      plugin_dir_url( WOO_CONDITIONAL_PAYMENTS_FILE ) . 'frontend/js/woo-conditional-payments-debug.js',
      [ 'jquery' ],
      WOO_CONDITIONAL_PAYMENTS_ASSETS_VERSION
    );
  }

  /**
   * Get debug mode status
   */
  public function is_enabled() {
    if ( function_exists( 'WC' ) ) {
      $debug_mode = strval( get_option( 'wcp_debug_mode', '' ) );

      switch ( $debug_mode ) {
        case '1':
          return true;
        case 'admin':
          return current_user_can( 'manage_woocommerce' );
      }
    }

    return false;
  }

  /**
   * Format debug data
   */
  public function format() {
    // Do not run for admin
    if ( is_admin() ) {
      return;
    }

    // Do not run if all rulesets are disabled
    if ( get_option( 'wcp_disable_all', false ) ) {
      return;
    }
    
    $this->data = [];

    $this->data['payment_methods'] = [
      'before' => [],
      'after' => [],
    ];

    $this->data['rulesets'] = [];
    foreach ( woo_conditional_payments_get_rulesets( true ) as $ruleset ) {
      $this->data['rulesets'][$ruleset->get_id()] = [
        'conditions' => [],
        'actions' => [],
        'ruleset_id' => $ruleset->get_id(),
        'ruleset_title' => $ruleset->get_title(),
        'result' => false,
      ];

      foreach ( $ruleset->get_conditions( true ) as $index => $condition ) {
        $this->add_condition( $ruleset->get_id(), $index, $condition, false );
      }

      foreach ( $ruleset->get_actions() as $index => $action ) {
        $this->add_action( $ruleset->get_id(), false, $index, $action );
      }
    }
  }

  /**
   * Add debug info to fragments
   */
  public function debug_fragment( $fragments ) {
    $fragments['#wcp-debug'] = $this->output_debug_checkout( false );

    return $fragments;
  }

  /**
   * Output debug information
   */
  public function output_debug_checkout( $echo = true ) {
    $debug = $this->data;

    ob_start();

    include 'frontend/views/debug.html.php';

    $contents = ob_get_clean();

    if ( $echo ) {
      echo $contents;
    } else {
      return $contents;
    }
  }

  /**
   * Add condition result
   */
  public function add_condition( $ruleset_id, $condition_index, $condition, $result ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    // Skip empty condition
    if ( ! isset( $condition['type'] ) || empty( $condition['type'] ) ) {
      return;
    }

    $desc = $this->translate_condition( $condition );

    $this->data['rulesets'][$ruleset_id]['conditions'][$condition_index] = [
      'desc' => $desc,
      'result' => $result,
    ];
  }

  /**
   * Record payment methods before / after filtering
   */
  public function record_gateways( $gateways, $mode ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $simplified_gateways = [];
    if ( is_array( $gateways ) ) {
      foreach ( $gateways as $gateway ) {
        $simplified_gateways[$gateway->id] = sprintf( '%s (%s)', $gateway->get_method_title(), $gateway->id );
      }
    }

    $this->data['payment_methods'][$mode] = $simplified_gateways;
  }

  /**
   * Add action result
   */
  public function add_action( $ruleset_id, $passes, $action_index, $action ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    // Skip empty action
    if ( ! isset( $action['type'] ) || empty( $action['type'] ) ) {
      return;
    }

    $this->data['rulesets'][$ruleset_id]['actions'][$action_index] = $this->translate_action( $action, $passes );
  }

  /**
   * Translate action into human-readable format
   */
  public function translate_action( $action, $passes ) {
    $actions = woo_conditional_payments_actions();

    $cols = [
      isset( $actions[$action['type']] ) ? $actions[$action['type']]['title'] : __( 'N/A', 'woo-conditional-payments' ),
    ];

    $note = false;
    $status = $passes ? 'pass' : 'skip';
    $label = $passes ? __( 'Run', 'woo-conditional-payments' ) : __( 'Skip', 'woo-conditional-payments' );

    switch ( $action['type'] ) {
      case 'disable_payment_methods':
      case 'enable_payment_methods':
      case 'enable_payment_methods_new':
        $cols['methods'] = implode( ', ', $this->get_payment_method_titles( $action ) );
        break;
      case 'add_fee':
        $fee_modes = wcp_get_fee_modes();

        $cols['methods'] = implode( ', ', $this->get_payment_method_titles( $action ) );

        $cols['value'] = sprintf( '%s %s', $action['fee_amount'], $fee_modes[$action['fee_mode']] );
        break;
      case 'set_no_payments_methods_msg':
        $cols['value'] = $action['error_msg'];
        break;
    }

    if ( ! $passes && $action['type'] === 'enable_payment_methods' ) {
      $note = __( 'Payment methods were disabled by this action because conditions did not pass', 'woo-conditional-payments' );
      $status = 'pass';
      $label = __( 'Run', 'woo-conditional-payments' );
    }

    return [
      'cols' => $cols,
      'label' => $label,
      'status' => $status,
      'note' => $note,
    ];
  }

  /**
   * Get shipping method titles
   */
  public function get_payment_method_titles( $action ) {
    // Fix for Tera Wallet which doesn't like if it's initialized
    // prematurely in woocommerce_init. Return only gateway IDs
    if ( current_action() != 'woocommerce_available_payment_gateways' ) {
      if ( is_array( $action['payment_method_ids'] ) ) {
        return $action['payment_method_ids'];
      } else {
        return [];
      }
    }

    if ( ! $this->payment_methods ) {
      $options = woo_conditional_payments_get_payment_method_options();

      foreach ( $options as $id => $title ) {
        $this->payment_methods[$id] = $title;
      }
    }

    return $this->ids_to_list( $action['payment_method_ids'], $this->payment_methods );
  }

  /**
   * Add total result for ruleset
   */
  public function add_result( $ruleset_id, $result ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $this->data['rulesets'][$ruleset_id]['result'] = $result;
  }

  /**
   * Translate condition to human-readable format
   */
  private function translate_condition( $condition ) {
    $operators = woo_conditional_payments_operators();
    $filters = woo_conditional_payments_filters();

    $filter = isset( $filters[$condition['type']] ) ? $filters[$condition['type']]['title'] : __( 'N/A', 'woo-conditional-payments' );
    $operator = isset( $operators[$condition['operator']] ) ? $operators[$condition['operator']] : __( 'N/A', 'woo-conditional-payments' );

    $value = $this->translate_condition_value( $condition );

    $cols = [ $filter ];

    // Product meta
    if ( $condition['type'] === 'product_meta' ) {
      $cols[0] = sprintf( '%s (%s)', $cols[0], $condition['meta_key'] );

      // Add highest / lowest for numerical product meta
      if ( wcp_is_operator_numerical( $condition['operator'] ) ) {
        $cols[] = $condition['product_measurement_mode'];
      }
    }

    $cols[] = $operator;

    // Some conditions only has operator and not value (e.g. customer logged in condition)
    if ( $value !== null ) {
      $cols[] = $value;
    }

    return implode( ' - ', $cols );
  }

  /**
   * Get condition value depending on the type
   */
  private function translate_condition_value( $condition ) {
    switch( $condition['type'] ) {
      case 'subtotal':
      case 'items':
      case 'billing_first_name':
      case 'billing_last_name':
      case 'billing_company':
      case 'billing_address_1':
      case 'billing_address_2':
      case 'shipping_first_name':
      case 'shipping_last_name':
      case 'shipping_company':
      case 'shipping_address_1':
      case 'shipping_address_2':
      case 'orders':
        return $condition['value'];
      case 'billing_postcode':
      case 'shipping_postcode':
        return $this->convert_list( $condition['postcodes'] );
      case 'billing_city':
      case 'shipping_city':
        if ( in_array( $condition['operator'], [ 'is', 'isnot' ], true ) ) {
          return $this->convert_list( $condition['cities'] );
        } else {
          return $condition['value'];
        }
      case 'billing_phone':
      case 'shipping_phone':
        return $this->convert_list( $condition['phones'] );
      case 'billing_email':
      case 'shipping_email':
        return $this->convert_list( $condition['emails'] );
      case 'date':
        return $condition['date'];
      case 'products':
        return implode( ', ', array_map( 'get_the_title', (array) $condition['product_ids'] ) );
      case 'shipping_method':
        return implode( ', ', $this->get_shipping_method_titles( $condition ) );
      case 'shipping_class':
        return implode( ', ', $this->get_term_titles( (array) $condition['shipping_class_ids'], 'product_shipping_class' ) );
      case 'product_meta':
        if ( wcp_is_operator_numerical( $condition['operator'] ) ) {
          return $condition['value'];
        } else if ( wcp_is_operator_set( $condition['operator'] ) ) {
          return $this->convert_list( $condition['textarea'] );
        } else if ( wcp_is_operator_boolean( $condition['operator'] ) ) {
          return null;
        }
      case 'product_cats':
        return implode( ', ', $this->get_term_titles( (array) $condition['product_cat_ids'], 'product_cat' ) );
      case 'product_tags':
        return implode( ', ', $this->get_term_titles( (array) $condition['product_tags'], 'product_tag' ) );
      case 'product_brands':
        return implode( ', ', $this->get_term_titles( (array) $condition['product_brands'], 'product_brand' ) );
      case 'product_types':
        return implode( ', ', $this->get_product_type_titles( (array) $condition['product_types'] ) );
      case 'stock_status':
        return implode( ', ', $this->get_stock_status_titles( (array) $condition['stock_status'] ) );
      case 'coupon':
        $coupon_ids = isset( $condition['coupon_ids'] ) ? (array) $condition['coupon_ids'] : [];
        return implode( ', ', array_map( 'wcp_get_coupon_title', $coupon_ids ) );
      case 'customer_authenticated':
        return null; // This condition doesn't has value, only operator
      case 'customer_role':
        return implode( ', ', $this->get_role_titles( $condition['user_roles'] ) );
      case 'billing_state':
      case 'shipping_state':
        return implode( ', ', $this->get_state_titles( $condition['states'] ) );
      case 'billing_country':
      case 'shipping_country':
        return implode( ', ', $this->get_country_titles( $condition['countries'] ) );
      case 'currency':
        return implode( ', ', (array) $condition['currencies'] );
      case 'time':
        return $this->get_time_title( $condition );
      case 'lang_polylang':
        return implode( ', ', (array) $condition['lang_polylang'] );
      case 'lang_wpml':
        return implode( ', ', (array) $condition['lang_wpml'] );
      case 'groups':
        return implode( ', ', $this->get_group_titles( $condition['user_groups'] ) );
      case 'vat_exempt':
      case 'vat_id_germanized':
      case 'vat_number_aelia':
      case 'woolab_billing_ic':
      case 'woolab_billing_dic':
      case 'woolab_billing_dic_dph':
        return null;
      case 'ip_address':
        return $this->convert_list( $condition['ip_addresses'] );
      default:
        return 'N/A';
    }
  }

  /**
   * Convert line separated list into a comma separated list
   */
  private function convert_list( $items ) {
    return implode( ', ', array_map( 'trim', explode( "\n", $items ) ) );
  }

  /**
   * Get group titles
   */
  private function get_group_titles( $groups ) {
    $titles = woo_conditional_payments_groups_options();

    return array_map( function( $group ) use ( $titles ) {
      return isset( $titles[$group] ) ? $titles[$group] : $group;
    }, $groups );
  }

  /**
   * Get product type titles
   */
  private function get_product_type_titles( $types ) {
    $titles = wcp_get_product_type_options();

    return array_map( function( $type ) use ( $titles ) {
      return isset( $titles[$type] ) ? $titles[$type] : $type;
    }, $types );
  }

  /**
   * Get stock status titles
   */
  private function get_stock_status_titles( $types ) {
    $titles = wcp_get_stock_status_options();

    return array_map( function( $type ) use ( $titles ) {
      return isset( $titles[$type] ) ? $titles[$type] : $type;
    }, $types );
  }

  /**
   * Get shipping method titles
   */
  private function get_shipping_method_titles( $condition ) {
    $options = woo_conditional_payments_get_shipping_method_options();

    // Key by combined ID
    $titles = [];
    foreach ( $options as $zone_id => $zone ) {
      foreach ( $zone['methods'] as $method ) {
        $titles[$method['combined_id']] = $method['title'];
      }
    }

    // Special handling for "Match by name"
    if ( in_array( '_name_match&_name_match', $condition['shipping_method_ids'], true ) ) {
      $name_match = isset( $condition['shipping_method_name_match'] ) ? $condition['shipping_method_name_match'] : '';

      $titles['_name_match&_name_match'] = sprintf( __( 'Match by name: %s', 'woo-conditional-payments' ), $name_match );
    }

    return $this->ids_to_list( $condition['shipping_method_ids'], $titles );
  }

  /**
   * Get term titles
   */
  private function get_term_titles( $ids, $taxonomy ) {
    $titles = [];
    foreach ( $ids as $id ) {
      $term = get_term_by( 'id', $id, $taxonomy );

      $titles[] = $term ? $term->name : __( 'N/A', 'woo-conditional-payments' );
    }

    return $titles;
  }

  /**
   * Get role titles
   */
  private function get_role_titles( $role_ids ) {
    if ( ! $this->customer_roles ) {
      $this->customer_roles = woo_conditional_payments_role_options();
    }

    return $this->ids_to_list( $role_ids, $this->customer_roles );
  }

  /**
   * Get state titles
   */
  private function get_state_titles( $state_ids ) {
    if ( ! $this->states ) {
      $options = woo_conditional_payments_state_options();
      foreach ( $options as $country_id => $states ) {
        foreach ( $states['states'] as $state_id => $state ) {
          $this->states["{$country_id}:{$state_id}"] = $state;
        }
      }
    }

    return $this->ids_to_list( $state_ids, $this->states );
  }

  /**
   * Get country titles
   */
  private function get_country_titles( $country_ids ) {
    if ( ! $this->countries ) {
      $this->countries = woo_conditional_payments_country_options();
    }

    return $this->ids_to_list( $country_ids, $this->countries );
  }

  /**
   * Get time title
   */
  private function get_time_title( $condition ) {
    if ( ! $this->hours ) {
      $this->hours = wcp_time_hours_options();
    }

    if ( ! $this->mins ) {
      $this->mins = wcp_time_mins_options();
    }

    $hours = isset( $condition['time_hours'] ) ? $condition['time_hours'] : '0';
    $mins = isset( $condition['time_mins'] ) ? $condition['time_mins'] : '0';


    return sprintf( '%s:%s', $this->hours[$hours], $this->mins[$mins] );
  }

  /**
   * Convert IDs to human-readable list from options
   */
  private function ids_to_list( $values, $options ) {
    $titles = [];

    foreach ( $values as $value ) {
      $titles[] = isset( $options[$value] ) ? $options[$value] : $value;
    }

    return $titles;
  }
}
