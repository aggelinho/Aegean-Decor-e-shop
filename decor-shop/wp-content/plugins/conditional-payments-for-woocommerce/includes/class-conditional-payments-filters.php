<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Woo_Conditional_Payments_Filters' ) ) {
class Woo_Conditional_Payments_Filters {
	/**
	 * Parse string number into float
	 */
	public static function _parse_number($number) {
		$number = str_replace( ',', '.', trim( $number ) );

		if ( is_numeric( $number ) ) {
			return floatval( $number );
		}

		return FALSE;
	}

	/**
	 * Get number of items in the order
	 */
	public static function get_items_count() {
		$order_id = absint( get_query_var( 'order-pay' ) );
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );

			return $order->get_item_count();
		}
		elseif ( WC()->cart ) {
			return WC()->cart->get_cart_contents_count();
		}

		return 0;
	}

	/**
	 * Get products in the order
	 * 
	 * @return WC_Product[]
	 */
	public static function _get_order_products( $with_qtys = false ) {
		$products = [];

		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order subtotal from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );

			foreach ( $order->get_items() as $key => $item ) {
		    $product = $item->get_product();

				if ( $product && is_callable( [$product, 'get_id'] ) ) {
					if ( $with_qtys ) {
						$products[$product->get_id()] = [
							'qty' => $item->get_quantity(),
							'product' => $product,
						];
					} else {
						$products[$product->get_id()] = $product;
					}
				}
			}
		}
		// Gets order from cart/checkout.
		elseif ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $key => $item ) {
				if ( isset( $item['data'] ) && ! empty( $item['data'] ) && is_callable( [$item['data'], 'get_id'] ) ) {
					if ( $with_qtys ) {
						$products[$item['data']->get_id()] = [
							'qty' => $item['quantity'],
							'product' => $item['data'],
						];
					} else {
						$products[$item['data']->get_id()] = $item['data'];
					}
				}
			}
		}

		return $products;
	}

	/**
	 * Get subtotal for an order
	 */
	public static function _get_order_subtotal() {
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order subtotal from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );
			$subtotal = $order->get_subtotal();

			$tax_display = get_option( 'woocommerce_tax_display_cart' );

			// Add taxes if prices are displayed tax inclusive
			if ( 'incl' === $tax_display ) {
				$subtotal_taxes = 0;
				foreach ( $order->get_items() as $item ) {
					$subtotal_taxes += $item->get_subtotal_tax();
				}
				$subtotal += wc_round_tax_total( $subtotal_taxes );
			}

			return $subtotal;
		}
		// Gets order total from cart/checkout.
		elseif ( WC()->cart ) {
			return (float) WC()->cart->get_displayed_subtotal();
		}

		return NULL;
	}

	/**
	 * Get discount for an order
	 */
	public static function _get_order_discount() {
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order discount from "pay for order" page.
		if ( 0 < $order_id ) {
			$tax_display = get_option( 'woocommerce_tax_display_cart' );
			$order = wc_get_order( $order_id );

			if ( 'incl' === $tax_display ) {
				return floatval( $order->get_discount_total() ) + floatval( $order->get_discount_tax() );
			}
			
			return floatval( $order->get_discount_total() );
		}
		// Gets order discount from cart/checkout.
		elseif ( WC()->cart ) {
			if ( WC()->cart->display_prices_including_tax() ) {
				return floatval( WC()->cart->get_discount_total() ) + floatval( WC()->cart->get_discount_tax() );
			}

			return floatval( WC()->cart->get_discount_total() );
		}

		return NULL;
	}

	/**
	 * Get order shipping method
	 */
	public static function _get_order_shipping_method() {
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order subtotal from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );
			$shipping_methods = $order->get_shipping_methods();

			if ( ! empty( $shipping_methods ) ) {
				$shipping_method = reset( $shipping_methods );

				$instance_id = $shipping_method->get_instance_id();
				$instance_id = apply_filters( 'woo_conditional_payments_get_shipping_method_instance_id', $instance_id, $shipping_method );

				return [
					'instance_id' => $instance_id,
					'rate_id' => false,
					'name' => $shipping_method->get_name(),
				];
			}

			return [
				'instance_id' => false,
				'rate_id' => false,
				'name' => false,
			];
		}
		// Gets shipping method from cart
		elseif ( WC()->cart ) {
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

			// Virtual products only
			if ( ! WC()->cart->needs_shipping() ) {
				return [
					'instance_id' => false,
					'rate_id' => false,
					'name' => false,
				]; 
			}
			
			if ( ! empty( $chosen_methods ) ) {
				$rate_id = reset( $chosen_methods );

				// Special handling for Flexible Shipping plugin
				if ( substr( $rate_id, 0, 18 ) === 'flexible_shipping_' ) {
					$ids = explode( '_', $rate_id );
					if ( count( $ids ) === 4 ) {
						$rate_id = sprintf( "flexible_shipping:%d", $ids[2] );
					}
				}

				$rate_id = apply_filters( 'woo_conditional_payments_get_shipping_method_rate_id', $rate_id );

				return [
					'instance_id' => false,
					'rate_id' => $rate_id,
					'name' => false,
				];
			}
		}

		return NULL;
	}

	/**
	 * Get order product types
	 */
	public static function get_order_product_types() {
		$products = self::_get_order_products();
		$types = [];

		foreach ( $products as $product ) {
			if ( $product->get_virtual() ) {
				$types['virtual'] = true;
			} else {
				$types['physical'] = true;
			}

			if ( strpos( $product->get_type(), 'subscription' ) !== false ) {
				$types['subscription'] = true;
			}

			if ( $product->get_downloadable() ) {
				$types['downloadable'] = true;
			}
		}

		return array_keys( $types );
	}

	/**
	 * Get order shipping classes
	 */
	public static function _get_order_shipping_class_ids() {
		$products = self::_get_order_products();
		$shipping_class_ids = [];

		foreach ( $products as $product ) {
			// Special handling for WPML
			if ( function_exists( 'icl_object_id' ) ) {
				$shipping_class_id = apply_filters( 'wpml_object_id', $product->get_shipping_class_id(), 'product_shipping_class', true, apply_filters( 'wpml_default_language', NULL ) );
			} else {
				$shipping_class_id = $product->get_shipping_class_id();
			}

			$shipping_class_ids[$shipping_class_id] = true;
		}

		return array_keys( $shipping_class_ids );
	}

	/**
	 * Get order coupons
	 */
	public static function get_order_coupon_ids() {
		$order_id = absint( get_query_var( 'order-pay' ) );

		$codes = array();

		// Gets coupons from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );

			$codes = $order->get_coupon_codes();
		}
		// Gets coupons from cart
		elseif ( WC()->cart ) {
			$codes = WC()->cart->get_applied_coupons();
		}

		$ids = array();
		foreach ( $codes as $code ) {
			$id = wc_get_coupon_id_by_code( $code );
			if ( $id ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Get order product categories
	 */
	public static function _get_order_product_cat_ids() {
		$products = self::_get_order_products();
		$cat_ids = array();

		foreach ( $products as $product ) {
			$product_cat_ids = woo_conditional_payments_get_product_cats( $product->get_id() );
			$cat_ids = array_merge( $cat_ids, $product_cat_ids );
		}

		return array_unique( $cat_ids );
	}

	/**
	 * Get order attribute
	 */
	public static function _get_order_attr( $attr ) {
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets attribute from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );
			return call_user_func( array( $order, "get_{$attr}" ) );
		}
		// Gets attribute from cart
		elseif ( WC()->cart ) {
			// Special handling for currency as it won't be available for
			// cart
			if ( $attr === 'currency' ) {
				return get_woocommerce_currency();
			}

			if ( $attr === 'id' ) {
				return isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : absint( WC()->session->get( 'store_api_draft_order', 0 ) );
			}

			return call_user_func( array( WC()->customer, "get_{$attr}" ) );
		}

		return NULL;
	}

	/**
	 * Compare value with given operator
	 */
	public static function _compare_numeric_value( $a, $b, $operator ) {
		switch ( $operator ) {
			case 'e':
				return round( floatval( $a ), wc_get_price_decimals() ) == round( floatval( $b ), wc_get_price_decimals() );
			case 'gt':
				return $a > $b;
			case 'gte':
				return $a >= $b;
			case 'lt':
				return $a < $b;
			case 'lte':
				return $a <= $b;
		}

		error_log( "Invalid operator given" );

		return NULL;
	}

	/**
	 * Check inclusiveness or exclusiveness in an array
	 */
	public static function _group_comparison( $a, $b, $operator ) {
		$a = array_unique( $a );
		$b = array_unique( $b );

		switch ( $operator ) {
			case 'in':
				return count( array_intersect( $a, $b ) ) > 0;
			case 'notin':
				return count( array_intersect( $a, $b ) ) == 0;
			case 'exclusive':
				return count( array_diff( $a, $b ) ) == 0;
		}

		error_log( "Invalid operator given in group comparison" );

		return NULL;
	}

	/**
	 * Check is / is not in an array
	 */
	public static function _is_array_comparison( $needle, $haystack, $operator ) {
		if ( $operator == 'is' ) {
			return in_array( $needle, $haystack );
		} else if ( $operator == 'isnot' ) {
			return ! in_array( $needle, $haystack );
		}

		error_log( "Invalid operator given in array comparison" );

		return NULL;
	}

	/**
	 * Text filtering
	 */
	public static function _text_filtering( $attr, $condition ) {
		$value = self::_get_order_attr( $attr );

		if ( $value !== NULL ) {
			switch ( $condition['operator'] ) {
				case 'exists':
					return strlen( trim( $value ) ) > 0;
				case 'notexists':
					return strlen( trim( $value ) ) == 0;
				case 'contains':
					return strpos( strtolower( $value ), trim( strtolower( $condition['value'] ) ) ) !== FALSE;
			}
		}

		return NULL;
	}

	/**
	 * Postcode filtering
	 */
	public static function _postcode_filtering( $attr, $condition ) {
		$value = self::_get_order_attr( $attr );

		// Get also country as it's needed for postcode formatting
		$country_prefix = strpos( $attr, 'billing_' ) !== false ? 'billing_' : 'shipping_';
		$country = self::_get_order_attr( $country_prefix . 'country' );

		if ( $value !== NULL ) {
			switch ( $condition['operator'] ) {
				case 'exists':
					return strlen( trim( $value ) ) > 0;
				case 'notexists':
					return strlen( trim( $value ) ) == 0;
			}

			// Postcode range or wildcard handling
			if ( $condition['operator'] === 'is' && isset( $condition['postcodes'] ) && ! empty( trim( $condition['postcodes'] ) ) ) {
				// Convert postcodes to cleaned array
				$postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $condition['postcodes'] ) ) ) );

				// Convert postcodes to objects for wc_postcode_location_matcher
				$postcodes_obj = array();
				foreach ( $postcodes as $key => $postcode ) {
					$postcodes_obj[] = (object) array(
						'id' => $key + 1,
						'value' => $postcode,
					);
				}

				// Check if postcode matches
				$matches = wc_postcode_location_matcher( $value, $postcodes_obj, 'id', 'value', strval( $country ) );

				// If there were any matches, postcode passes the condition
				return ! empty( $matches );
			}
		}

		return FALSE;
	}

	/**
	 * Filter by subtotal
	 */
	public static function filter_subtotal( $condition ) {
		$subtotal = self::_get_order_subtotal();

		if ( $subtotal !== NULL ) {
			if ( isset( $condition['subtotal_includes_coupons'] ) && $condition['subtotal_includes_coupons'] ) {
				$discount = self::_get_order_discount();
				$subtotal -= $discount;
			}

			$cond_subtotal = self::_parse_number( $condition['value'] );
			$cond_subtotal = apply_filters( 'wcp_convert_price', $cond_subtotal );

			return ! self::_compare_numeric_value( $subtotal, $cond_subtotal, $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter by products
	 */
	public static function filter_products( $condition ) {
		if ( isset( $condition['product_ids'] ) && ! empty( $condition['product_ids'] ) ) {
			$condition_product_ids = self::merge_product_children_ids( $condition['product_ids'] );

			$products = self::_get_order_products();

			// Special handling for WPML
			if ( function_exists( 'icl_object_id' ) ) {
				$default_lang = apply_filters( 'wpml_default_language', NULL );
				foreach ( $products as $product_id => $product ) {
					$orig_id = apply_filters( 'wpml_object_id', $product_id, $product->post_type, true, $default_lang );

					$products[$orig_id] = $product;
				}
			}

			if ( ! empty( $products ) ) {
				$product_ids = array_keys( $products );

				return ! self::_group_comparison( $product_ids, $condition_product_ids, $condition['operator'] );
			}
		}

		return FALSE;
	}

	/**
	 * Filter by shipping method
	 */
	public static function filter_shipping_method( $condition ) {
		if ( isset( $condition['shipping_method_ids'] ) && ! empty( $condition['shipping_method_ids'] ) ) {
			// We need to handle both rate IDs (e.g. local_pickup:3) and instance IDs (e.g. 3)
			// as cart provides rate ID while order pay page provides instance ID.

			$shipping_method = self::_get_order_shipping_method();

			if ( $shipping_method !== null ) {
				$matches = [];

				foreach ( $condition['shipping_method_ids'] as $combined_id ) {
					list( $rate_id, $instance_id ) = explode( '&', $combined_id );

					// Handle "Match by name"
					if ( $rate_id === '_name_match' ) {
						if ( ! $shipping_method['name'] ) {
							$shipping_method['name'] = wcp_get_shipping_method_title_by_id( $shipping_method );
						}

						if ( $shipping_method['name'] !== false && self::match_by_name( $shipping_method['name'], $condition['shipping_method_name_match'] ) ) {
							$matches[] = $combined_id;
						}

						continue;
					}

					$instance_id_match = $shipping_method['instance_id'] && $shipping_method['instance_id'] == $instance_id ;
					$rate_id_match = $shipping_method['rate_id'] && $shipping_method['rate_id'] == $rate_id ;

					if ( $instance_id_match || $rate_id_match ) {
						$matches[] = $combined_id;
					}
				}

				if ( $condition['operator'] == 'is' ) {
					return empty( $matches );
				} else if ( $condition['operator'] == 'isnot' ) {
					return ! empty( $matches );
				}
			}
		}

		return false;
	}

	/**
	 * Match shipping method by name
	 */
	public static function match_by_name( $title, $names ) {
		$title = strtolower( trim( strval( $title ) ) );
	
		// Split names by newline as they are entered one per line
		$names = array_filter( array_map( 'strtolower', array_map( 'wc_clean', explode( "\n", $names ) ) ) );
	
		// Check if some name matches
		foreach ( $names as $name ) {
			if ( strpos( $name, '*' ) !== false ) {
				if ( fnmatch( $name, $title ) ) {
					return true;
				}
			} else {
				if ( $name === $title ) {
					return true;
				}
			}
		}
	
		return false;
	}

	/**
	 * Filters for billing attributes
	 */
	public static function filter_billing_first_name( $condition ) { return ! self::_text_filtering( 'billing_first_name', $condition ); }
	public static function filter_billing_last_name( $condition ) { return ! self::_text_filtering( 'billing_last_name', $condition ); }
	public static function filter_billing_company( $condition ) { return ! self::_text_filtering( 'billing_company', $condition ); }
	public static function filter_billing_address_1( $condition ) { return ! self::_text_filtering( 'billing_address_1', $condition ); }
	public static function filter_billing_address_2( $condition ) { return ! self::_text_filtering( 'billing_address_2', $condition ); }
	public static function filter_billing_city( $condition ) { return ! self::city_filtering( 'billing_city', $condition ); }
	public static function filter_billing_postcode( $condition ) { return ! self::_postcode_filtering( 'billing_postcode', $condition ); }

	/**
	 * Filters for shipping attributes
	 */
	public static function filter_shipping_first_name( $condition ) { return ! self::_text_filtering( 'shipping_first_name', $condition ); }
	public static function filter_shipping_last_name( $condition ) { return ! self::_text_filtering( 'shipping_last_name', $condition ); }
	public static function filter_shipping_company( $condition ) { return ! self::_text_filtering( 'shipping_company', $condition ); }
	public static function filter_shipping_address_1( $condition ) { return ! self::_text_filtering( 'shipping_address_1', $condition ); }
	public static function filter_shipping_address_2( $condition ) { return ! self::_text_filtering( 'shipping_address_2', $condition ); }
	public static function filter_shipping_city( $condition ) { return ! self::city_filtering( 'shipping_city', $condition ); }
	public static function filter_shipping_postcode( $condition ) { return ! self::_postcode_filtering( 'shipping_postcode', $condition ); }

	/**
	 * City filtering
	 */
	public static function city_filtering( $attr, $condition ) {
		$value = self::_get_order_attr( $attr );

		if ( in_array( $condition['operator'], [ 'is', 'isnot' ], true ) ) {
			if ( $value !== null ) {
				$value = trim( strtolower( $value ) );

				if ( isset( $condition['cities'] ) && ! empty( trim( $condition['cities'] ) ) ) {
					// Convert cities to cleaned array
					$cities = array_filter( array_map( 'strtolower', array_map( 'wc_clean', explode( "\n", $condition['cities'] ) ) ) );

					// Find matches
					$matches = [];
					foreach ( $cities as $city ) {
						if ( strpos( $city, '*' ) !== false ) {
							if ( fnmatch( $city, $value ) ) {
								$matches[] = $city;
							}
						} else {
							if ( $city === $value ) {
								$matches[] = $city;
							}
						}
					}

					// If there were any matches, city passes the condition
					if ( $condition['operator'] === 'is' ) {
						return ! empty( $matches );
					} else if ( $condition['operator'] === 'isnot' ) {
						return empty( $matches );
					}
				}
			}
		} else {
			return self::_text_filtering( $attr, $condition );
		}

		return false;
	}

	/**
	 * Filter by billing state
	 */
	public static function filter_billing_state( $condition ) {
		if ( isset( $condition['states'] ) && ! empty( $condition['states'] ) ) {
			$country = self::_get_order_attr( 'billing_country' );
			$state = self::_get_order_attr( 'billing_state' );
			$value = sprintf( '%s:%s', $country, $state );

			return ! self::_is_array_comparison( $value, $condition['states'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter by shipping state
	 */
	public static function filter_shipping_state( $condition ) {
		if ( isset( $condition['states'] ) && ! empty( $condition['states'] ) ) {
			$country = self::_get_order_attr( 'shipping_country' );
			$state = self::_get_order_attr( 'shipping_state' );
			$value = sprintf( '%s:%s', $country, $state );

			return ! self::_is_array_comparison( $value, $condition['states'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter billing country
	 */
	public static function filter_billing_country( $condition ) {
		$value = self::_get_order_attr( 'billing_country' );

		if ( ! empty( $condition['countries'] ) ) {
			return ! self::_is_array_comparison( $value, $condition['countries'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter shipping country
	 */
	public static function filter_shipping_country( $condition ) {
		$value = self::_get_order_attr( 'shipping_country' );

		if ( ! empty( $condition['countries'] ) ) {
			return ! self::_is_array_comparison( $value, $condition['countries'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Merge children IDs for parent product IDs
	 */
	public static function merge_product_children_ids( $product_ids ) {
		$args = array(
			'post_type' => array( 'product_variation' ),
			'post_parent__in' => $product_ids,
			'fields' => 'ids',
			'posts_per_page' => -1
		);
		$children_ids = get_posts( $args );

		return array_merge( $children_ids, $product_ids );
	}
}
}
