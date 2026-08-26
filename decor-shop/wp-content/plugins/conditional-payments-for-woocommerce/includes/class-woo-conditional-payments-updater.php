<?php

/**
 * Plugin update functions
 */
class Woo_Conditional_Payments_Updater {
  public $db_version;
  public $db_version_option;
  public $version;

  public function __construct() {
    if ( class_exists( 'Woo_Conditional_Payments_Pro' ) ) {
      $this->db_version_option = 'woo_conditional_payments_pro_version';
      $this->version = CONDITIONAL_PAYMENTS_FOR_WOO_PRO_VERSION;
    } else {
      $this->db_version_option = 'woo_conditional_payments_version';
      $this->version = CONDITIONAL_PAYMENTS_FOR_WOO_VERSION;
    }

    $this->db_version = get_option( $this->db_version_option, '0.0.0' );
  }

  public function run_updates() {
    if ( version_compare( '2.0.0', $this->db_version ) >= 1 ) {
      $this->run_200();
    }

    if ( version_compare( '2.1.4', $this->db_version ) >= 1 ) {
      $this->run_214();
    }

    // Set version to the latest version
    if ( $this->db_version != $this->version ) {
      update_option( $this->db_version_option, $this->version );
    }
  }

  /**
   * Run 2.0.0 update
   *
   * In 2.0.0 conditions were moved from WordPress options table to custom post types.
   * UI was also refactored to make stronger base for further development.
   */
  private function run_200() {
    // Check if new conditions has been already mapped (e.g. Free version before Pro)
    $already_done = get_option( 'wcp_updated_200', 'no' );
    if ( $already_done === 'yes' ) {
      return;
    }

    if ( ! function_exists( 'WC' ) ) {
      return;
    }

    foreach ( WC()->payment_gateways->payment_gateways() as $gateway_id => $gateway ) {
      $conditions = get_option( 'wcp_' . $gateway_id, array() );

      if ( ! empty( $conditions ) ) {
        $post_id = wp_insert_post( array(
          'post_type' => 'wcp_ruleset',
          'post_title' => $gateway->get_method_title(),
          'post_status' => 'publish',
        ) );

        // Add conditions
        update_post_meta( $post_id, '_wcp_conditions', $conditions );

        // Add enable actions (pre 2.0.0 all actions were enable actions)
        $actions = array(
          array(
            'type' => 'enable_payment_methods',
            'payment_method_ids' => array( strval( $gateway_id ) ),
          ),
        );
        update_post_meta( $post_id, '_wcp_actions', $actions );
      }
    }

    update_option( 'wcp_updated_200', 'yes' );
  }

  /**
   * Run 2.1.4 update
   *
   * In 2.1.4 postcode "contains" filter were replaced with "is" which allows
   * postcode range and wildcard matching
   */
  private function run_214() {
    // Check if new conditions has been already mapped (e.g. Free version before Pro)
    $already_done = get_option( 'wcp_updated_214', 'no' );
    if ( $already_done === 'yes' ) {
      return;
    }

    if ( ! function_exists( 'WC' ) ) {
      return;
    }

    foreach ( woo_conditional_payments_get_rulesets() as $ruleset ) {
      $conditions = get_post_meta( $ruleset->get_id(), '_wcp_conditions', true );
  
      $changed = false;
  
      foreach ( $conditions as $key => $condition ) {
        if ( $condition['type'] === 'billing_postcode' || $condition['type'] === 'shipping_postcode' ) {
          if ( $condition['operator'] === 'contains' ) {
            $conditions[$key]['operator'] = 'is';

            if ( ! isset( $conditions[$key]['postcodes'] ) || empty( $conditions[$key]['postcodes'] ) ) {
              $conditions[$key]['postcodes'] = $condition['value'];
            }

            $changed = true;
          }
        }
      }
  
      if ( $changed ) {
        update_post_meta( $ruleset->get_id(), '_wcp_conditions', $conditions );
      }
    }

    update_option( 'wcp_updated_214', 'yes' );
  }
}

add_action( 'init', 'woo_conditional_payments_updater', 1000 );
function woo_conditional_payments_updater() {
  // WooCommerce not activated, abort
  if ( ! defined( 'WC_VERSION' ) ) {
    return;
  }

  $updater = new Woo_Conditional_Payments_Updater();
  $updater->run_updates();
}
