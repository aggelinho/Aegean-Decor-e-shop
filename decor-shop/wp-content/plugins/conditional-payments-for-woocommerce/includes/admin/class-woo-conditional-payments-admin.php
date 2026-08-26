<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Payments_Admin {
  /**
   * Constructor
   */
  public function __construct() {
    add_filter( 'woocommerce_get_sections_checkout', array( $this, 'register_section' ), 10, 1 );

    add_action( 'woocommerce_settings_checkout', array( $this, 'output' ) );
    
    add_action( 'woocommerce_settings_save_checkout', array( $this, 'save_ruleset' ), 10, 0 );
    add_action( 'woocommerce_settings_save_checkout', array( $this, 'save_settings' ), 10, 0 );

    // Add admin JS
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

    // Dequeue scripts
    add_action( 'admin_footer', [ $this, 'admin_dequeue_scripts' ], 100, 0 );
    
    // Add plugin links
    add_filter( 'plugin_action_links_' . WOO_CONDITIONAL_PAYMENTS_BASENAME, array( $this, 'add_plugin_links' ) );

    // Admin AJAX action for toggling ruleset activity
    add_action( 'wp_ajax_wcp_toggle_ruleset', array( $this, 'toggle_ruleset' ) );

    // Admin AJAX action for searching products
    add_action( 'wp_ajax_wcp_json_search_products', [ $this, 'search_products' ] );

    // Add link to the rulesets for the new React based payment method settings
    add_action( 'woocommerce_sections_checkout', [ $this, 'add_ruleset_link' ], 10, 0 );
  }

  /**
   * Add plugin links
   */
  public function add_plugin_links( $links ) {
    $url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' );
		$link = '<a href="' . $url . '">' . __( 'Conditions', 'woo-conditional-payments' ) . '</a>';

		$links = array_merge( [ $link ], $links );
		
		if ( ! class_exists( 'Woo_Conditional_Payments_Pro' ) ) {
			$link = '<span style="font-weight:bold;"><a href="https://wptrio.com/products/conditional-payments/" style="color:#46b450;" target="_blank">' . __( 'Go Pro', 'woo-conditional-payments' ) . '</a></span>';

			$links = array_merge( [ $link ], $links );
		}

    return $links;
  }

  /**
   * Add admin JS
   */
  public function admin_enqueue_scripts() {
    // Only load on Conditional Shipping page to avoid JS conflicts
    if ( ! isset( $_GET['section'] ) || $_GET['section'] !== 'woo_conditional_payments' ) {
      return;
    }

    wp_enqueue_script(
      'woo-conditional-payments-admin-js',
      WOO_CONDITIONAL_PAYMENTS_URL . 'admin/js/woo-conditional-payments.js',
      [ 'jquery', 'wp-util', 'jquery-ui-datepicker', 'jquery-ui-dialog' ],
      WOO_CONDITIONAL_PAYMENTS_ASSETS_VERSION
    );

    wp_enqueue_style(
      'woo-conditional-payments-admin-css',
      WOO_CONDITIONAL_PAYMENTS_URL . 'admin/css/woo-conditional-payments.css',
      [ 'jquery-ui-style' ],
      WOO_CONDITIONAL_PAYMENTS_ASSETS_VERSION
    );

    $ajax_urls = [
      'ruleset_toggle' => admin_url( 'admin-ajax.php?action=wcp_toggle_ruleset' ),
      'welcome_submit' => admin_url( 'admin-ajax.php?action=wcp_welcome_submit' ),
    ];

    wp_localize_script( 'woo-conditional-payments-admin-js', 'woo_conditional_payments', [
      'disable_sortable' => apply_filters( 'wcp_disable_sortable', false ),
      'ajax_urls' => $ajax_urls,
      'nonces' => [
        'ruleset_toggle' => wp_create_nonce( 'wcp-toggle-ruleset' ),
        'welcome_submit' => wp_create_nonce( 'wcp-welcome-submit' ),
      ]
    ] );
  }

  /**
   * Dequeue scripts
   */
  public function admin_dequeue_scripts() {
    // Only run on Conditional Payments page
    if ( ! isset( $_GET['section'], $_GET['ruleset_id'] ) || $_GET['section'] !== 'woo_conditional_payments' || empty( $_GET['ruleset_id'] ) ) {
      return;
    }

    // Dequeue WooCommerce admin settings because its editPrompt
    // decreases performance when clicking 'Add condition'
    wp_dequeue_script( 'woocommerce_settings' );
  }

  /**
   * Register section under "Payments" settings in WooCommerce
   */
  public function register_section( $sections ) {
    $sections['woo_conditional_payments'] = __( 'Conditions', 'woo-conditional-payments' );

    return $sections;
	}
	
  /**
   * Output conditions page
   */
  public function output() {
    global $current_section;
    global $hide_save_button;

    if ( 'woo_conditional_payments' !== $current_section ) {
      return;
    }

    $action = isset( $_GET['action'] ) ? $_GET['action'] : false;
    $ruleset_id = isset( $_GET['ruleset_id'] ) ? $_GET['ruleset_id'] : false;
    $hide_save_button = true;

    if ( $ruleset_id ) {
      if ( $ruleset_id === 'new' ) {
        $ruleset_id = false;
      } else {
        $ruleset_id = absint( wc_clean( wp_unslash( $ruleset_id ) ) );
      }

      // Delete ruleset
      if ( $ruleset_id && 'delete' === $action && 'wcp_ruleset' === get_post_type( $ruleset_id ) ) {
        check_ajax_referer( 'wcp-delete-ruleset' );

        wp_delete_post( $ruleset_id, false );

        // Clear cache
        delete_transient( 'wcp_name_address_fields' );

        $url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' );
        wp_safe_redirect( $url );
        exit;
      }

      // Duplicate ruleset
      if ( $ruleset_id && 'duplicate' === $action && 'wcp_ruleset' === get_post_type( $ruleset_id ) ) {
        check_ajax_referer( 'wcp-duplicate-ruleset' );

        $cloned_ruleset_id = $this->clone_ruleset( $ruleset_id );

        wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments&ruleset_id=' . $cloned_ruleset_id ) );
        exit;
      }

      $ruleset = new Woo_Conditional_Payments_Ruleset( $ruleset_id );

      include 'views/ruleset.html.php';
    } else {
      $health = $this->health_check();

      $add_ruleset_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments&ruleset_id=new' );

      $rulesets = woo_conditional_payments_get_rulesets();

      $disable_sortable = apply_filters( 'wcp_disable_sortable', false );
      
      include apply_filters( 'wcp_settings_tmpl', 'views/settings.html.php' );
    }
  }

  /**
   * Clone ruleset
   */
  public function clone_ruleset( $ruleset_id ) {
    $ruleset = get_post( $ruleset_id );

    $post_id = wp_insert_post( [
      'post_type' => 'wcp_ruleset',
      'post_title' => sprintf( __( '%s (Clone)', 'woo-conditional-payments' ), $ruleset->post_title ),
      'post_status' => 'publish',
    ] );

    $meta_keys = [
      '_wcp_operator', '_wcp_conditions', '_wcp_actions',
    ];

    foreach ( $meta_keys as $meta_key ) {
      $values = get_post_meta( $ruleset->ID, $meta_key, true );

      // Generate GUIDs for actions and conditions
      if ( in_array( $meta_key, [ '_wcp_actions' ], true ) && is_array( $values ) ) {
        foreach ( $values as $key => $value ) {
          $values[$key]['guid'] = uniqid();
        }
      }

      update_post_meta( $post_id, $meta_key, $values );
    }

    // Cloned ruleset should be disabled by default
    update_post_meta( $post_id, '_wcp_enabled', 0 ); 

    return $post_id;
  }

	/**
	 * Save ruleset
	 */
	public function save_ruleset() {
		global $current_section;
    
    if ( 'woo_conditional_payments' === $current_section && isset( $_POST['ruleset_id'] ) ) {
      $post = false;
      if ( $_POST['ruleset_id'] ) {
        $post = get_post( $_POST['ruleset_id'] );

        if ( ! $post && 'wcp_ruleset' !== get_post_type( $post ) ) {
          $post = false;
        }
      }

      if ( ! $post ) {
        $post_id = wp_insert_post( array(
          'post_type' => 'wcp_ruleset',
          'post_title' => wp_strip_all_tags( $_POST['ruleset_name'] ),
          'post_status' => 'publish',
        ) );

        $post = get_post( $post_id );
      } else {
        $post->post_title = wp_strip_all_tags( $_POST['ruleset_name'] );

        wp_update_post( $post, false );
      }

      $operator = isset( $_POST['wcp_operator'] ) ? $_POST['wcp_operator'] : 'and';
      update_post_meta( $post->ID, '_wcp_operator', $operator );

      $conditions = isset( $_POST['wcp_conditions'] ) ? $_POST['wcp_conditions'] : array();
      update_post_meta( $post->ID, '_wcp_conditions', array_values( (array) $conditions ) );

      $actions = isset( $_POST['wcp_actions'] ) ? $_POST['wcp_actions'] : array();

      // Generate GUIDs for actions
      foreach ( $actions as $key => $action ) {
        if ( ! isset( $action['guid'] ) || empty( $action['guid'] ) ) {
          $actions[$key]['guid'] = uniqid();
        }
      }

			update_post_meta( $post->ID, '_wcp_actions', array_values( (array) $actions ) );
      
			$enabled = ( isset( $_POST['ruleset_enabled'] ) && $_POST['ruleset_enabled'] ) ? 'yes' : 'no';
			update_post_meta( $post->ID, '_wcp_enabled', $enabled );

      $pro_features = isset( $_POST['wcp_pro_features'] ) ? (bool) $_POST['wcp_pro_features'] : false;
      update_option( 'wcp_pro_features', ($pro_features ? '1' : '0') );
			
			// Clear cache
			delete_transient( 'wcp_name_address_fields' );

      // Register strings for WPML
      if ( function_exists( 'icl_object_id' ) ) {
        foreach ( $actions as $key => $action ) {
          if ( $action['type'] === 'add_fee' ) {
            do_action( 'wpml_register_single_string', 'Conditional Payments for WooCommerce', sprintf( 'Fee description (GUID: %s)', $action['guid'] ), $action['fee_title'] );
          }

          if ( $action['type'] === 'set_no_payments_methods_msg' ) {
            do_action( 'wpml_register_single_string', 'Conditional Payments for WooCommerce', sprintf( 'No payment methods message (GUID: %s)', $action['guid'] ), $action['error_msg'] );
          }
        }
      }

      $url = add_query_arg( array(
        'ruleset_id' => $post->ID,
      ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' ) );
      wp_safe_redirect( $url );
      exit;
    }
  }

  /**
   * Save general settings
   */
  public function save_settings() {
    global $current_section;
    
    if ( 'woo_conditional_payments' === $current_section && isset( $_POST['wcp_settings'] ) ) {
      $debug = '';
      if ( isset( $_POST['wcp_debug_mode'] ) && in_array( $_POST['wcp_debug_mode'], [ '', 'admin', '1' ], true ) ) {
        $debug = $_POST['wcp_debug_mode'];
      }
      update_option( 'wcp_debug_mode', $debug );

      update_option( 'wcp_disable_all', ( isset( $_POST['wcp_disable_all'] ) && $_POST['wcp_disable_all'] ) );

      // Ruleset ordering
      $ruleset_order = isset( $_POST['wcp_ruleset_order'] ) ? wc_clean( wp_unslash( $_POST['wcp_ruleset_order'] ) ) : '';
      $order = [];
      if ( is_array( $ruleset_order ) && count( $ruleset_order ) > 0 ) {
        $loop = 0;
        foreach ( $ruleset_order as $ruleset_id ) {
          $order[ esc_attr( $ruleset_id ) ] = $loop;
          $loop++;
        }
      }
      update_option( 'wcp_ruleset_order', $order );
    }
  }
  
  /**
   * Toggle reulset
   */
  public function toggle_ruleset() {
    check_ajax_referer( 'wcp-toggle-ruleset', 'security' );

    if ( ! current_user_can( 'manage_woocommerce' ) ) {
      http_response_code( 403 );
      die( 'Permission denied' );
    }

    $ruleset_id = $_POST['id'];

    $post = get_post( $ruleset_id );

    if ( $post && get_post_type( $post ) === 'wcp_ruleset' ) {
      $enabled = get_post_meta( $post->ID, '_wcp_enabled', true ) === 'yes';
      $new_status = $enabled ? 'no' : 'yes';
      update_post_meta( $post->ID, '_wcp_enabled', $new_status );

      echo json_encode( array(
        'enabled' => ( get_post_meta( $post->ID, '_wcp_enabled', true ) === 'yes' ),
      ) );
      
      die;
    }

    http_response_code(422);
    die;
  }


  /**
   * Health check
   */
  private function health_check() {
    return array(
      'enables' => $this->health_check_enables(),
      'disables' => $this->health_check_disables(),
    );
  }

  /**
   * Search products
   */
  public function search_products() {
    $GLOBALS['wcp_search_products'] = true;

    WC_AJAX::json_search_products_and_variations();
  }

  /**
   * Add link to the rulesets for the new React based
   * payment method settings
   */
  public function add_ruleset_link() {
    global $current_section;

    if ( ! empty( $current_section ) ) {
      return;
    }

    if ( ! class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
      return;
    }

    if ( ! \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'reactify-classic-payments-settings' ) && version_compare( WC()->version, '9.9.0' ) === -1 ) {
      return;
    }

    $sections = apply_filters( 'woocommerce_get_sections_checkout', [
      '' => __( 'General', 'woocommerce' )
    ] );

    echo '<ul class="subsubsub">';

    $array_keys = array_keys( $sections );

    foreach ( $sections as $id => $label ) {
      $url = admin_url( 'admin.php?page=wc-settings&tab=' . 'checkout' . '&section=' . sanitize_title( $id ) );
      $class = ( $current_section === $id ? 'current' : '' );
      $separator = ( end( $array_keys ) === $id ? '' : '|' );
      $text = esc_html( $label );
      echo "<li><a href='$url' class='$class'>$text</a> $separator </li>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    echo '</ul><br class="clear" />';
  }

  /**
   * Check if there are disabled payment methods in the rulesets
   * 
   * Conditional Payments can only process payments methods which are enabled
   */
  private function health_check_disables() {
    // Get all rulesets
    $rulesets = woo_conditional_payments_get_rulesets( true );

    $payment_method_actions = array(
      'enable_payment_methods', 'disable_payment_methods',
      'enable_payment_methods_new', 'add_fee'
    );

    $disables = array();
    foreach ( $rulesets as $ruleset ) {
      foreach ( $ruleset->get_actions() as $action ) {
        if ( in_array( $action['type'], $payment_method_actions, true ) && isset( $action['payment_method_ids'] ) && is_array( $action['payment_method_ids'] ) ) {
          foreach ( $action['payment_method_ids'] as $instance_id ) {
            $gateway = woo_conditional_payments_get_payment_method( $instance_id );

            if ( $gateway && is_object( $gateway ) && isset( $gateway->enabled ) && $gateway->enabled !== 'yes' ) {
              $disables[] = array(
                'gateway' => $gateway,
                'ruleset' => $ruleset,
                'action' => $action,
              );
            }
          }
        }
      }
    }

    return $disables;
  }

  /**
   * Check for multiple "Enable payment methods" for the same payment method
   */
  private function health_check_enables() {
    // Get all rulesets
    $rulesets = woo_conditional_payments_get_rulesets( true );

    // Check if there are overlapping "Enable payment methods"
    $enables = array();
    foreach ( $rulesets as $ruleset ) {
      foreach ( $ruleset->get_actions() as $action ) {
        if ( $action['type'] === 'enable_payment_methods' && isset( $action['payment_method_ids'] ) && is_array( $action['payment_method_ids'] ) ) {
          foreach ( $action['payment_method_ids'] as $id ) {
            if ( ! isset( $enables[$id] ) ) {
              $enables[$id] = array();
            }

            $enables[$id][] = $ruleset->get_id();
          }
        }
      }
    }

    // Filter out if there is only one "Enable payment methods" for a payment method
    $enables = array_filter( $enables, function( $ruleset_ids ) {
      return count( $ruleset_ids ) > 1;
    } );

    return $enables;
  }
}
