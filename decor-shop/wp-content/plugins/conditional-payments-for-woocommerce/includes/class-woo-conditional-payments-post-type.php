<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Payments_Post_Type {
  /**
   * Constructor
   */
  public function __construct() {
    // Register custom post type
    add_action( 'init', array( $this, 'register_post_type' ), 10, 0 );
  }

  /**
   * Register custom post type
   */
  public function register_post_type() {
    register_post_type( 'wcp_ruleset',
      array(
        'labels' => array(
          'name' => __( 'Conditional Payments Rulesets', 'woo-conditional-payments' ),
          'singular_name' => __( 'Conditional Payments Ruleset', 'woo-conditional-payments' )
        ),
        'public' => false,
        'publicly_queryable' => false,
				'show_ui' => false,
        'has_archive' => false,
				'supports' => array(
					'title',
				),
      )
    );
  }
}
