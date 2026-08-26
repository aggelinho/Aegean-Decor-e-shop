<?php
/**
 * Filter by Tax object
 *
 * Offers method specific to Taxonomy filter
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes\Filters
 * @version 4.16.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Filter_Tax' ) ) {
	/**
	 * Taxonomy Filter Handling
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Filter_Tax extends YITH_WCAN_Filter {

		/**
		 * List of formatted terms for current view (hashed by requested offset/number)
		 *
		 * @var array
		 */
		protected $formatted_terms;

		/**
		 * List of sorted term ids for current view
		 *
		 * @var array
		 */
		protected $sorted_term_ids;

		/**
		 * Flag set to true after retrieving formatted terms, in case there are more terms than those shown
		 *
		 * @var int Number of remaining terms; can be casted to bool to be used as flag that indicates if there are more terms.
		 */
		protected $has_more_terms;

		/**
		 * Checks whether current filter is active
		 *
		 * @return bool Whether current filter is active.
		 */
		public function is_active() {
			/**
			 * APPLY_FILTERS: yith_wcan_is_filter_active
			 *
			 * Filters bool representing whether current filter is active or not.
			 *
			 * @param bool             $is_active Is filter active?
			 * @param YITH_WCAN_Filter $this      Filter object.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_is_filter_active', YITH_WCAN_Query::instance()->is_filtered_by( $this->get_taxonomy() ), $this );
		}

		/**
		 * Checks if filter is relevant to current product selection
		 *
		 * @return bool Whether filter is relevant or not.
		 */
		public function is_relevant() {
			/**
			 * APPLY_FILTERS: yith_wcan_is_filter_relevant
			 *
			 * Filters bool representing whether current filter is relevant for current product selection or not.
			 *
			 * @param bool             $is_relevant Is filter relevant?
			 * @param YITH_WCAN_Filter $this        Filter object.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_is_filter_relevant', $this->is_enabled() && $this->has_relevant_terms(), $this );
		}

		/**
		 * Render start for the filter section or subsection
		 *
		 * @param int $level Current nesting level.
		 * @return string Header template.
		 */
		public function render_start( $level = 0 ) {
			$atts = array(
				/**
				 * APPLY_FILTERS: yith_wcan_all_filters_label
				 *
				 * Filters label shown when no term is selected for current filter.
				 *
				 * @param string $label "All" terms label.
				 *
				 * @return string
				 */
				'all_label' => apply_filters( 'yith_wcan_all_filters_label', _x( 'All', '[FRONTEND] "All" label shown when no term is selected', 'yith-woocommerce-ajax-navigation' ), $this ),
				'filter'    => $this,
				'preset'    => $this->get_preset(),
				'level'     => $level,
			);

			return yith_wcan_get_template( 'filters/filter-tax/filter-start.php', $atts, false );
		}

		/**
		 * Render end for the filter section or subsection
		 *
		 * @param int $level Current nesting level.
		 * @return string Footer template.
		 */
		public function render_end( $level = 0 ) {
			$atts = array(
				'filter' => $this,
				'preset' => $this->get_preset(),
				'level'  => $level,
			);

			return yith_wcan_get_template( 'filters/filter-tax/filter-end.php', $atts, false );
		}

		/**
		 * Render every single item in the list
		 *
		 * @param int|WP_Term $term         Term object, or term id.
		 * @param array       $term_options Array of additional options for the term.
		 *
		 * @return string Item template.
		 */
		public function render_item( $term, $term_options = array() ) {
			$design           = $this->get_filter_design();
			$default_callback = "render_item_{$design}";
			$use_all_terms    = $this->use_all_terms();
			$customize_terms  = $this->customize_terms();
			$taxonomy         = $this->get_taxonomy();

			if ( ! $term instanceof WP_Term ) {
				$term = get_term( $term, $taxonomy );
			}

			if ( ! $term || is_wp_error( $term ) ) {
				return '';
			}

			// populate additional options.
			if ( ! isset( $term_options['level'] ) ) {
				$term_options['level'] = 0;
			}

			if ( ! isset( $term_options['additional_classes'] ) ) {
				$term_options['additional_classes'] = array();
			}

			// configure item classes.
			$term_options['additional_classes'][] = "level-{$term_options['level']}";

			if ( ! empty( $term_options['children'] ) ) {
				$hierarchy = $this->get_hierarchical();

				if ( 'collapsed' === $hierarchy ) {
					$term_options['additional_classes'][] = 'hierarchy-collapsable';
					$term_options['additional_classes'][] = 'closed';
				} elseif ( 'expanded' === $hierarchy ) {
					$term_options['additional_classes'][] = 'hierarchy-collapsable';
					$term_options['additional_classes'][] = 'opened';
				}
			}

			if ( ! $term_options['count'] && 'or' === $this->get_adoptive() && apply_filters( 'yith_wcan_process_filters_intersection', true ) ) {
				$term_options['additional_classes'][] = 'disabled';
			}

			// if we're using terms' default option, override any custom setting user may have entered.
			if ( $use_all_terms || ! $customize_terms ) {
				$term_options['label']   = $term->name;
				$term_options['tooltip'] = '';
				$term_options['color_1'] = '';
				$term_options['color_2'] = '';
				/**
				 * APPLY_FILTERS: yith_wcan_tax_filter_default_image_meta
				 *
				 * Filters term meta key where plugins searches for term image (attachment id).
				 *
				 * @param string $meta_key Image meta key.
				 *
				 * @return string
				 */
				$term_options['image']   = get_term_meta( $term->term_id, apply_filters( 'yith_wcan_tax_filter_default_image_meta', 'thumbnail_id', $taxonomy ), true );
				$term_options['mode']    = ! ! $term_options['image'] ? 'image' : 'color';
			}

			// allow third party dev change attributes for the item.
			/**
			 * APPLY_FILTERS: yith_wcan_tax_filter_item_args
			 *
			 * Filters array with options set for a specific term in the filter.
			 *
			 * @param array            $term_options Array of term options.
			 * @param int              $term_id      Id of current term.
			 * @param YITH_WCAN_Filter $this         Current filter.
			 *
			 * @return array
			 */
			$term_options = apply_filters( 'yith_wcan_tax_filter_item_args', $term_options, $term->term_id, $this );

			// specific filtering for attributes.
			if ( 0 === strpos( $taxonomy, 'pa_' ) && ( $use_all_terms || ! $customize_terms ) ) {
				/**
				 * APPLY_FILTERS: yith_wcan_attribute_filter_item_args
				 *
				 * Filters array with options set for a specific attribute in the filter.
				 *
				 * @param array            $term_options Array of term options.
				 * @param int              $term_id      Id of current term.
				 * @param YITH_WCAN_Filter $this         Current filter.
				 *
				 * @return array
				 */
				$term_options = apply_filters( 'yith_wcan_attribute_filter_item_args', $term_options, $term->term_id, $this );
			}

			if ( 'color' === $term_options['mode'] && empty( $term_options['color_1'] ) ) {
				$term_options['additional_classes'][] = 'no-color';
			}

			if ( 'image' === $term_options['mode'] && empty( $term_options['image'] ) ) {
				$term_options['additional_classes'][] = 'no-image';
			}

			// implode additional classes.
			/**
			 * APPLY_FILTERS: yith_wcan_filter_tax_additional_item_classes
			 *
			 * Filters classes added to each term in the filter
			 *
			 * @param array            $classes Array of additional classes.
			 * @param YITH_WCAN_Filter $this    Current filter.
			 *
			 * @return array
			 */
			$term_options['additional_classes'] = implode( ' ', apply_filters( 'yith_wcan_filter_tax_additional_item_classes', $term_options['additional_classes'], $this ) );

			if ( method_exists( $this, $default_callback ) ) {
				$item = $this->{$default_callback}( $term, $term_options );
			} else {
				/**
				 * APPLY_FILTERS: yith_wcan_filter_tax_render_item_$design
				 *
				 * Used to allow third party code to provide template for a custom design.
				 * <code>$design</code> will be replaced with the design name.
				 *
				 * @param string      $template     Item template.
				 * @param int|WP_Term $term_object  Term object or id.
				 * @param array       $term_options Term params.
				 *
				 * @return string
				 */
				$item = apply_filters( 'yith_wcan_filter_tax_render_item_' . $design, '', $term, $term_options );
			}

			return $item;
		}

		/**
		 * Render every single item in a radio filter
		 *
		 * @param WP_Term $term         The term.
		 * @param array   $term_options Array of term options.
		 * @return string Item HTML template.
		 */
		public function render_item_radio( $term, $term_options ) {
			return $this->render_generic_item( 'radio', $term, $term_options );
		}

		/**
		 * Render every single option in a select filter
		 *
		 * @param WP_Term $term         The term.
		 * @param array   $term_options Array of term options.
		 * @return string Item HTML template.
		 */
		public function render_item_select( $term, $term_options ) {
			return $this->render_generic_item( 'select', $term, $term_options );
		}

		/**
		 * Render every single item in a checkbox filter
		 *
		 * @param WP_Term $term         The term.
		 * @param array   $term_options Array of term options.
		 * @return string Item HTML template.
		 */
		public function render_item_checkbox( $term, $term_options ) {
			return $this->render_generic_item( 'checkbox', $term, $term_options );
		}

		/**
		 * Render every single item in a checkbox filter
		 *
		 * @param WP_Term $term         The term.
		 * @param array   $term_options Array of term options.
		 * @return string Item HTML template.
		 */
		public function render_item_text( $term, $term_options ) {
			return $this->render_generic_item( 'text', $term, $term_options );
		}

		/**
		 * Render every single item in a checkbox filter
		 *
		 * @param WP_Term $term         The term.
		 * @param array   $term_options Array of term options.
		 * @return string Item HTML template.
		 */
		public function render_item_label( $term, $term_options ) {
			$columns        = $this->get_column_number();
			$label_position = $this->get_label_position();

			$term_options['additional_classes'] .= $term_options['image'] ? " with-image filter-has-{$columns}-column" : '';
			$term_options['additional_classes'] .= " label-{$label_position}";

			return $this->render_generic_item( 'label', $term, $term_options );
		}

		/**
		 * Render every single item in a checkbox filter
		 *
		 * @param WP_Term $term         The term.
		 * @param array   $term_options Array of term options.
		 * @return string Item HTML template.
		 */
		public function render_item_color( $term, $term_options ) {
			$columns        = $this->get_column_number();
			$label_position = $this->get_label_position();

			$term_options['additional_classes'] .= " filter-has-{$columns}-column";
			$term_options['additional_classes'] .= " label-{$label_position}";

			return $this->render_generic_item( 'color', $term, $term_options );
		}

		/**
		 * Render every item that doesn't need special processing; will pick up correct template depending on first param
		 *
		 * @param string  $template     Template for the item.
		 * @param WP_Term $term         The term.
		 * @param array   $term_options Array of term options.
		 * @return string Item HTML template.
		 */
		public function render_generic_item( $template, $term, $term_options ) {
			$atts = array_merge(
				$term_options,
				array(
					'filter'         => $this,
					'preset'         => $this->get_preset(),
					'term'           => $term,
					'show_count'     => $this->show_count(),
					'allow_multiple' => 'yes' === $this->get_multiple(),
					'relation'       => $this->get_relation(),
					'adoptive'       => $this->get_adoptive(),
					'item_id'        => "filter_{$this->get_preset()->get_id()}_{$this->get_id()}_{$term->term_id}",
					'item_name'      => "filter[{$this->get_preset()->get_id()}][{$this->get_id()}]",
				)
			);

			return yith_wcan_get_template( "filters/filter-tax/items/{$template}.php", $atts, false );
		}

		/**
		 * Render hierarchy of single item, by cycling through its children
		 *
		 * @param array $children Array of children to print.
		 * @param int   $level    Current nesting level.
		 * @return string Hierarchy template
		 */
		public function render_hierarchy( $children, $level = 0 ) {
			$hierarchy = '';

			if ( empty( $children ) || ! in_array( $this->get_hierarchical(), array( 'collapsed', 'expanded', 'open' ), true ) || ! in_array( $this->get_filter_design(), array( 'checkbox', 'radio', 'text' ), true ) ) {
				return $hierarchy;
			}

			$level ++;

			if ( 'select' !== $this->get_filter_design() ) {
				$hierarchy .= $this->render_start( $level );
			}

			foreach ( $children as $term_id => $term_options ) {
				$term_options['level'] = $level;
				$hierarchy            .= $this->render_item( $term_id, $term_options );
			}

			if ( 'select' !== $this->get_filter_design() ) {
				$hierarchy .= $this->render_end( $level );
			}

			return $hierarchy;
		}

		/**
		 * Render count for a specific term
		 *
		 * @param WP_Term $term  Current term.
		 * @param int     $count Count to render.
		 * @return string Count template
		 */
		public function render_term_count( $term, $count ) {
			/**
			 * APPLY_FILTERS: yith_wcan_term_count
			 *
			 * Filters count shown beside each term in the filter.
			 *
			 * @param int     $count Term's posts count.
			 * @param WP_Term $term  Term object.
			 *
			 * @return int
			 */
			$count = apply_filters( 'yith_wcan_term_count', $count, $term );

			return $this->render_count( $count );
		}

		/* === TERMS UTILS === */

		/**
		 * Checks is a term should be considered active
		 *
		 * @param WP_Term $term Current term.
		 * @return bool
		 */
		public function is_term_active( $term ) {
			return YITH_WCAN_Query()->is_term( $this->get_taxonomy(), $term );
		}

		/**
		 * Checks whether current filter has terms relevant to current query
		 *
		 * @return bool
		 */
		public function has_relevant_terms() {
			/**
			 * APPLY_FILTERS: yith_wcan_filter_has_relevant_terms
			 *
			 * Whether current filter has terms relevant to current product selection.
			 *
			 * @param bool $has_relevant_terms Whether filter has relevant terms.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_filter_has_relevant_terms', ! ! $this->get_formatted_terms(), $this );
		}

		/**
		 * Returns value of the $has_more_terms flags, or false when terms pagination isn't required
		 *
		 * @return bool
		 */
		public function has_more_terms() {
			if ( ! $this->is_terms_pagination_required() ) {
				return false;
			}

			// if flag still needs to be set, retrieve formatted terms first.
			if ( is_null( $this->has_more_terms ) ) {
				$this->get_formatted_terms();
			}

			return $this->has_more_terms;
		}

		/**
		 * Returns true if we need to paginate terms
		 *
		 * @return bool
		 */
		public function is_terms_pagination_required() {
			$pagination_required = yith_plugin_fw_is_true( get_option( 'yith_wcan_paginate_terms' ) ) && YITH_WCAN_Filters_Factory::get_terms_on_first_loading();

			return apply_filters( 'yith_wcaf_filter_tax_term_pagination_required', $pagination_required, $this );
		}

		/**
		 * Returns a formatted list of terms, matching current selection and according to hierarchy options
		 *
		 * @param int|bool $number Number of terms to return.
		 * @param int|bool $offset Number of terms to offset.
		 *
		 * @return array term_id=>term_options
		 */
		public function get_formatted_terms( $number = false, $offset = false ) {
			$paginate = false !== $number || $this->is_terms_pagination_required();
			$number   = false !== $number ? $number : YITH_WCAN_Filters_Factory::get_terms_on_first_loading();
			$offset   = false !== $offset ? $offset : 0;
			$hash_key = md5( implode( '_', compact( 'number', 'offset' ) ) );

			if ( ! empty( $this->formatted_terms[ $hash_key ] ) ) {
				return $this->formatted_terms[ $hash_key ];
			}

			$taxonomy = $this->get_taxonomy();
			$terms    = $this->get_terms_options();
			$children = array();
			$result   = array();

			$sorted_terms = $this->get_sorted_terms();

			if ( ! empty( $sorted_terms ) ) {
				$index = 0;
				$count = count( $result );

				while ( isset( $sorted_terms[ $index ] ) && ( ! $paginate || ! $number || $count < ( $number + $offset ) ) ) {
					$term_id = $sorted_terms[ $index ];
					$index++;

					if ( ! isset( $terms[ $term_id ] ) && ! $this->use_all_terms() ) {
						continue;
					}

					$term = isset( $terms[ $term_id ] ) ? $terms[ $term_id ] : $this->get_default_term_options();

					// set hierarchical data.
					$children_result = $this->get_term_children( $term_id );

					$term['children'] = $children_result['formatted_children'];
					$term['count']    = $children_result['count'];

					// if we need to remove empty terms, skip here when count is 0.
					if ( $this->is_term_hidden( $term ) ) {
						continue;
					}

					// populate children array.
					$children = array_merge( $children, $children_result['children'] );

					$result[ $term_id ] = $term;
					$count++;
				}

				if ( $paginate && $offset ) {
					$result = array_slice( $result, $offset, null, true );
				}

				if ( $paginate && isset( $sorted_terms[ $index ] ) ) {
					$this->has_more_terms = count( $sorted_terms ) - $index;
				}
			}

			/**
			 * APPLY_FILTERS: yith_wcan_filter_get_formatted_terms_for_$taxonomy
			 *
			 * Filters the array of formatted terms for a specific taxonomy
			 * <code>$taxonomy</code> will be replaced with taxonomy slug.
			 *
			 * @param array            $formatted_terms List of formatted terms.
			 * @param YITH_WCAN_Filter $this            Current filter.
			 *
			 * @return array
			 */
			$this->formatted_terms[ $hash_key ] = apply_filters( "yith_wcan_filter_get_formatted_terms_for_{$taxonomy}", $result, $this, $number, $offset );

			return $this->formatted_terms[ $hash_key ];
		}

		/**
		 * Retrieves url to filter by the passed term
		 *
		 * @param WP_Term $term Current term.
		 * @return string Url to filter by passed term
		 */
		public function get_term_url( $term ) {
			$param = array( $this->get_formatted_taxonomy() => $term->slug );

			if ( $this->is_term_active( $term ) ) {
				$url = YITH_WCAN_Query()->get_filter_url( array(), $param, $this->get_relation() );
			} else {
				$url = YITH_WCAN_Query()->get_filter_url( $param, array(), $this->get_relation() );
			}

			return $url;
		}

		/**
		 * Returns a list of term ids in the order that should be used to display them at frontend
		 *
		 * @return array Array of term ids.
		 */
		protected function get_sorted_terms() {
			if ( ! empty( $this->sorted_term_ids ) ) {
				return $this->sorted_term_ids;
			}

			$taxonomy   = $this->get_taxonomy();
			$hide_empty = yith_plugin_fw_is_true( yith_wcan_get_option( 'yith_wcan_hide_empty_terms', 'no' ) );
			$terms      = $this->get_terms_options();

			/**
			 * APPLY_FILTERS: yith_wcan_filter_tax_get_sorted_terms_args
			 *
			 * Allows third party code to filters parameters used in get_terms, when retrieving terms
			 * to show in a specific filter
			 *
			 * @param array                $args   List of arguments for the get_term() call.
			 * @param YITH_WCAN_Filter_Tax $filter This object.
			 *
			 * @return bool
			 */
			$query_args = apply_filters(
				'yith_wcan_filter_tax_get_sorted_terms_args',
				array_merge(
					array(
						'taxonomy'   => $taxonomy,
						'order'      => $this->get_order(),
						/**
						 * APPLY_FILTERS: yith_wcan_filter_tax_term_limit
						 *
						 * Limit applied when retrieving filter's terms.
						 * 0 used as default, to enforce no specific limit.
						 *
						 * @param int $number Term limit.
						 *
						 * @return int
						 */
						'number'     => apply_filters( 'yith_wcan_filter_tax_term_limit', 0 ),
						'fields'     => $this->is_hierarchical() ? 'id=>parent' : 'ids',
						'hide_empty' => $hide_empty,
					),
					$this->use_all_terms() ? array() : array(
						'include' => array_keys( $terms ),
					),
					'term_order' === $this->get_order_by() ? array(
						'orderby'  => 'meta_value_num',
						'meta_key' => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					) : array(
						'orderby' => $this->get_order_by(),
					),
					'parents_only' === $this->get_hierarchical() ? array( 'parent' => 0 ) : array(),
				),
				$this
			);

			// retrieve all terms in the correct order.
			$result = get_terms( $query_args );

			$term_ids = array();
			if ( $this->is_hierarchical() ) {
				foreach ( $result as $term_id => $parent_id ) {
					if ( 0 === $parent_id || ! in_array( $parent_id, array_keys( $result ) ) ) {
						$term_ids[] = $term_id;
					}
				}
			} else {
				$term_ids = $result;
			}

			// if we have some active filters, and we're paginating terms, make sure to include those (or their ancestors) as first results.
			if ( $this->is_active() && $this->is_terms_pagination_required() ) {
				$active_term_slugs = YITH_WCAN_Query()->get_query_var( $this->get_taxonomy() );
				$active_term_ids   = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'slug'       => $active_term_slugs,
						'hide_empty' => $hide_empty,
						'fields'     => 'ids',
					)
				);

				if ( $this->is_hierarchical() ) {
					$active_term_ids = array_map(
						function( $term_id ) use ( $taxonomy ) {
							$ancestors = get_ancestors( $term_id, $taxonomy );
							return ! empty( $ancestors ) ? array_pop( $ancestors ) : $term_id;
						},
						$active_term_ids
					);
				}

				$term_ids = array_unique( array_merge( $active_term_ids, $term_ids ) );
			}

			$this->sorted_term_ids = is_array( $term_ids ) ? array_values( $term_ids ) : array();

			return $this->sorted_term_ids;
		}

		/**
		 * Recursively populate children hierarchy for terms
		 *
		 * @param int $term_id Term id.
		 * @return array Children hierarchy with options
		 */
		protected function get_term_children( $term_id ) {
			$terms              = $this->get_terms_options();
			$formatted_children = array();
			$children           = array();

			$child_terms = get_terms(
				/**
				 * APPLY_FILTERS: yith_wcan_filter_tax_get_term_children_args
				 *
				 * Allows third party code to filters parameters used in get_terms, when retrieving children
				 * of a specific term
				 *
				 * @param array                $args    List of arguments for the get_term() call.
				 * @param int                  $term_id Id of the term whose children will be retrieved
				 * @param YITH_WCAN_Filter_Tax $filter  This object.
				 *
				 * @return bool
				 */
				apply_filters(
					'yith_wcan_filter_tax_get_term_children_args',
					array_merge(
						array(
							'taxonomy'   => $this->get_taxonomy(),
							'parent'     => $term_id,
							'order'      => $this->get_order(),
							'fields'     => 'ids',
							'hide_empty' => 'yes' === yith_wcan_get_option( 'yith_wcan_hide_empty_terms', 'no' ),
						),
						$this->use_all_terms() ? array() : array(
							'include' => array_keys( $terms ),
						),
						'term_order' === $this->get_order_by() ? array(
							'orderby'  => 'meta_value_num',
							'meta_key' => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						) : array(
							'orderby' => $this->get_order_by(),
						)
					),
					$term_id,
					$this
				)
			);

			$count = YITH_WCAN_Cache_Helper::get_for_current_query( 'products_in_term_count', $term_id );

			$count = apply_filters( 'yith_wcan_filter_tax_get_term_children_count', $count, $this->get_taxonomy(), $term_id );

			if ( false === $count ) {
				$products = $this->get_term_products( $term_id );
			}

			foreach ( $child_terms as $child_id ) {
				if ( ! isset( $terms[ $child_id ] ) && ! $this->use_all_terms() ) {
					continue;
				}

				$child = isset( $terms[ $child_id ] ) ? $terms[ $child_id ] : $this->get_default_term_options();

				// set hierarchical data.
				$children_result   = $this->get_term_children( $child_id );
				$child['children'] = $children_result['formatted_children'];
				$child['count']    = $children_result['count'];

				if ( $this->is_term_hidden( $child ) ) {
					continue;
				}

				$formatted_children[ $child_id ] = $child;

				$children[] = $child_id;
				$children   = array_merge( $children, $children_result['children'] );

				if ( false === $count ) {
					$products = array_unique( array_merge( $products, $children_result['products'] ) );
				}
			}

			if ( false === $count ) {
				$count = ! empty( $products ) ? count( $products ) : 0;
				YITH_WCAN_Cache_Helper::set_for_current_query( 'products_in_term_count', $count, $term_id );
			}

			return array(
				'children'           => $children,
				'formatted_children' => $formatted_children,
				'count'              => $count,
				'products'           => $products ?? array(),
			);
		}

		/**
		 * Retrieves products for passed term_id that matches current query
		 *
		 * @param int $term_id Term id.
		 * @return array Array of matching product ids.
		 */
		protected function get_term_products( $term_id ) {
			$filter_by_current_values = 'yes' === $this->get_multiple() && 'and' === $this->get_relation();
			$products                 = YITH_WCAN_Query()->get_query_relevant_term_objects( $this->get_taxonomy(), $term_id, $filter_by_current_values );

			return $products;
		}

		/**
		 * Count products for passed term_id that matches current query
		 *
		 * @param int $term_id Term id.
		 * @return int Count of matching product ids.
		 */
		protected function count_term_products( $term_id ) {
			return count( $this->get_term_products( $term_id ) );
		}

		/**
		 * Checks whether term should be hidden
		 *
		 * @param array $term_options Array describing term and its options.
		 * @return bool Whether to hide term or not
		 */
		protected function is_term_hidden( $term_options ) {
			$hidden = false;

			// hide when term doesn't match current selection.
			/**
			 * APPLY_FILTERS: yith_wcan_process_filters_intersection
			 *
			 * Whether to process intersections between current product list and filter result set
			 *
			 * @param bool $process Whether to process intersection.
			 *
			 * @return bool
			 */
			if ( 'hide' === $this->get_adoptive() && ! $term_options['count'] && empty( $term_options['children'] ) && apply_filters( 'yith_wcan_process_filters_intersection', true ) ) {
				$hidden = true;
			}

			/**
			 * APPLY_FILTERS: yith_wcan_filter_tax_is_term_hidden
			 *
			 * Allow to filter whether a specific term should be hidden.
			 *
			 * @param bool  $hidden       Whether term is hidden.
			 * @param array $term_options Term options.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_filter_tax_is_term_hidden', $hidden, $term_options );
		}
	}
}
