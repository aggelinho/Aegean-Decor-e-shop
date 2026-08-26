<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Payments_Ruleset {
  private $post_id;
  private $debug;

  /**
   * Constructor
   */
  public function __construct( $post_id = false ) {
    $this->post_id = $post_id;

    $this->debug = Woo_Conditional_Payments_Debug::instance();
  }

  /**
   * Get ID
   */
  public function get_id() {
    return $this->post_id;
  }

  /**
   * Get title
   */
  public function get_title( $context = 'view' ) {
    $post = $this->get_post();

    if ( $post && $post->post_title ) {
      return $post->post_title;
    }

    if ( $context === 'edit' ) {
      return '';
    }

    return __( 'Ruleset', 'woo-conditional-payments' );
  }

  /**
   * Get whether or not ruleset is enabled
   */
  public function get_enabled() {
    $enabled = get_post_meta( $this->post_id, '_wcp_enabled', true );
    $enabled_exists = metadata_exists( 'post', $this->post_id, '_wcp_enabled' );

    // Metadata doesn't exist yet so we assume it's enabled
    if ( ! $enabled_exists ) {
      return true;
    }

    return $enabled === 'yes';
  }

  /**
   * Get row actions
   */
  public function get_row_actions() {
    return [
      'edit' => [
        'title' => __( 'Edit', 'woo-conditional-payments' ),
        'url' => $this->get_admin_edit_url(),
        'class' => 'wcp-ruleset-edit',
      ],
      'delete' => [
        'title' => __( 'Delete', 'woo-conditional-payments' ),
        'url' => $this->get_admin_delete_url(),
        'class' => 'wcp-ruleset-delete',
      ],
      'clone' => [
        'title' => __( 'Duplicate', 'woo-conditional-payments' ),
        'url' => $this->get_admin_duplicate_url(),
        'class' => 'wcp-ruleset-duplicate',
      ],
    ];
  }

  /**
   * Get admin edit URL
   */
  public function get_admin_edit_url() {
    $url = add_query_arg( array(
      'ruleset_id' => $this->post_id,
    ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' ) );

    return $url;
  }

  /**
   * Get admin delete URL
   */
  public function get_admin_delete_url() {
    $url = add_query_arg( array(
      'ruleset_id' => $this->post_id,
      'action' => 'delete',
    ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' ) );

    return wp_nonce_url( $url, 'wcp-delete-ruleset' );
  }

  /**
   * Get admin duplicate URL
   */
  public function get_admin_duplicate_url() {
    $url = add_query_arg( [
      'ruleset_id' => $this->post_id,
      'action' => 'duplicate',
    ], admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_conditional_payments' ) );

    return wp_nonce_url( $url, 'wcp-duplicate-ruleset' );
  }

  /**
   * Get post
   */
  public function get_post() {
    if ( $this->post_id ) {
      return get_post( $this->post_id );
    }

    return false;
  }

  /**
	 * Get products which are selected in conditions
	 */
	public function get_products() {
    $product_ids = array();

		foreach ( $this->get_conditions() as $condition ) {
			if ( isset( $condition['product_ids'] ) && is_array( $condition['product_ids'] ) ) {
				$product_ids = array_merge( $product_ids, $condition['product_ids'] );
			}
		}

		$products = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$products[$product_id] = wp_kses_post( $product->get_formatted_name() );
			}
		}

		return $products;
  }

  /**
   * Get coupons which are selected in conditions
   */
  public function get_coupons() {
    $coupon_ids = [];

    foreach ( $this->get_conditions() as $condition ) {
      if ( isset( $condition['coupon_ids'] ) && is_array( $condition['coupon_ids'] ) ) {
        $coupon_ids = array_merge( $coupon_ids, $condition['coupon_ids'] );
      }
    }

    $general_options = [
      '_all' => __( '- All coupons -', 'woo-conditional-payments' ),
    ];

    $coupons = [];
    foreach ( $coupon_ids as $coupon_id ) {
      if ( isset( $general_options[$coupon_id] ) ) {
        $coupons[$coupon_id] = $general_options[$coupon_id];
      } else {
        $coupon_code = wc_get_coupon_code_by_id( $coupon_id );
        if ( $coupon_code ) {
          $coupons[$coupon_id] = $coupon_code;
        }
      }
    }

    return $coupons;
  }

  /**
   * Get tags which are selected in conditions
   */
  public function get_tags() {
    $tag_ids = [];

    foreach ( $this->get_conditions() as $condition ) {
      if ( isset( $condition['product_tags'] ) && is_array( $condition['product_tags'] ) ) {
        $tag_ids = array_merge( $tag_ids, $condition['product_tags'] );
      }
    }

    $tags = [];
    foreach ( $tag_ids as $tag_id ) {
      $tag = get_term( $tag_id, 'product_tag' );
      if ( $tag ) {
        $tags[$tag->term_id] = wp_kses_post( $tag->name );
      }
    }

    return $tags;
  }

  /**
   * Get brands which are selected in conditions
   */
  public function get_brands() {
    $brand_ids = [];

    foreach ( $this->get_conditions() as $condition ) {
      if ( isset( $condition['product_brands'] ) && is_array( $condition['product_brands'] ) ) {
        $brand_ids = array_merge( $brand_ids, $condition['product_brands'] );
      }
    }

    $brands = [];
    foreach ( $brand_ids as $brand_id ) {
      $brand = get_term( $brand_id, 'product_brand' );
      if ( $brand ) {
        $brands[$brand->term_id] = wp_kses_post( $brand->name );
      }
    }

    return $brands;
  }
  
  /**
   * Get conditions for the ruleset
   */
  public function get_conditions( $skip_empty = false ) {
    $conditions = get_post_meta( $this->post_id, '_wcp_conditions', true );

    if ( ! $conditions ) {
      return array();
    }

    $conditions = (array) $conditions;

    if ( $skip_empty ) {
      foreach ( $conditions as $key => $condition ) {
        if ( ! isset( $condition['type'] ) || empty( $condition['type'] ) ) {
          unset( $conditions[$key] );
        }
      }

      $conditions = array_values( $conditions );
    }

    return $conditions;
  }

  /**
   * Get actions for the ruleset
   */
  public function get_actions() {
    $actions = get_post_meta( $this->post_id, '_wcp_actions', true );

    if ( ! $actions ) {
      return array();
    }

    return (array) $actions;
  }

  /**
   * Get operator for conditions (AND / OR)
   */
  public function get_conditions_operator() {
    $operator = get_post_meta( $this->post_id, '_wcp_operator', true );

    if ( $operator && in_array( $operator, [ 'and', 'or' ], true ) ) {
      return $operator;
    }

    return 'and';
  }

  /**
   * Check if conditions pass for the given package
   */
  public function validate() {
    $filters = woo_conditional_payments_filters();

    $results = [];
    foreach ( $this->get_conditions( true ) as $index => $condition ) {
      $function = "filter_{$condition['type']}";

      if ( isset( $filters[$condition['type']] ) && isset( $filters[$condition['type']]['callback'] ) ) {
        $callable = $filters[$condition['type']]['callback'];
      } else if ( class_exists( 'Woo_Conditional_Payments_Filters_Pro' ) && method_exists( 'Woo_Conditional_Payments_Filters_Pro', $function ) ) {
        $callable = array( 'Woo_Conditional_Payments_Filters_Pro', "filter_{$condition['type']}" );
      } else {
        $callable = array( 'Woo_Conditional_Payments_Filters', "filter_{$condition['type']}" );
      }

      $results[$index] = (bool) call_user_func( $callable, $condition );

      $this->debug->add_condition( $this->get_id(), $index, $condition, $results[$index] );
    }

    // If operator is OR, it is enough that one condition passed
    if ( $this->get_conditions_operator() === 'or' ) {
      $passed = in_array( false, $results, true ) === true;
    } else {
      $passed = in_array( true, $results, true ) === false;
    }

    $this->debug->add_result( $this->get_id(), $passed );

    return $passed;
  }
}
