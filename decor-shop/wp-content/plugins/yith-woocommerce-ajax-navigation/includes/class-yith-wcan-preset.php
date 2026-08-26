<?php
/**
 * Preset object
 * Offers method to read and set properties of the preset and filters
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes\Presets
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Preset' ) ) {
	/**
	 * Filter Presets Handling
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Preset extends WC_Data {

		/**
		 * Preset slug (Unique identifier)
		 *
		 * @var string
		 */
		protected $slug = '';

		/**
		 * Preset Data array
		 *
		 * @var array
		 */
		protected $data;

		/**
		 * Original post for the preset
		 *
		 * @var WP_Post
		 */
		protected $post;

		/**
		 * Preset filters will be stored here, sometimes before they persist in the DB.
		 *
		 * @var array
		 */
		protected $filters = array();

		/**
		 * Whenever ::get_filters is called, filters objects are created and stored here for future usage
		 *
		 * @var array
		 */
		protected $filters_objects = array();

		/**
		 * Preset filters that need deleting are stored here.
		 *
		 * @var array
		 */
		protected $filters_to_delete = array();

		/**
		 * Stores meta in cache for future reads.
		 *
		 * A group must be set to to enable caching.
		 *
		 * @var string
		 */
		protected $cache_group = 'filter_presets';

		/**
		 * Constructor
		 *
		 * @param int|string|\YITH_WCAN_Preset $preset Preset identifier.
		 *
		 * @throws Exception When not able to load Data Store class.
		 */
		public function __construct( $preset = 0 ) {
			// set default values.
			$this->data = array(
				/**
				 * APPLY_FILTERS: yith_wcan_default_preset_title
				 *
				 * Filters default preset title.
				 *
				 * @param string $title Default title.
				 *
				 * @return string
				 */
				'title'    => apply_filters( 'yith_wcan_default_preset_title', '' ),
				/**
				 * APPLY_FILTERS: yith_wcan_default_preset_slug
				 *
				 * Filters default preset slug.
				 *
				 * @param string $slug Default slug.
				 *
				 * @return string
				 */
				'slug'     => apply_filters( 'yith_wcan_default_preset_slug', '' ),
				'layout'   => 'default',
				'selector' => '',
				'filters'  => array(),
				'enabled'  => true,
			);

			parent::__construct();

			if ( is_numeric( $preset ) && $preset > 0 ) {
				$this->set_id( $preset );
			} elseif ( $preset instanceof self ) {
				$this->set_id( $preset->get_id() );
			} elseif ( is_string( $preset ) ) {
				$this->set_slug( $preset );
			} else {
				$this->set_object_read( true );
			}

			$this->data_store = WC_Data_Store::load( 'filter_preset' );

			if ( $this->get_id() > 0 || ! empty( $this->get_slug() ) ) {
				$this->data_store->read( $this );
			}
		}

		/* === GETTERS === */

		/**
		 * Magic getter method
		 *
		 * @param string $key Key to get.
		 * @return mixed Value retrieved, or null.
		 */
		public function __get( $key ) {
			$method = 'get_' . $key;

			if ( method_exists( $this, $method ) ) {
				return $this->{$method}();
			} elseif ( isset( $this->get_post()->$key ) ) {
				return $this->get_post()->$key;
			}

			return null;
		}

		/**
		 * Get preset title
		 *
		 * @param string $context Context of the operation.
		 *
		 * @return string Preset title
		 */
		public function get_title( $context = 'view' ) {
			return $this->get_prop( 'title', $context );
		}

		/**
		 * Get preset slug
		 *
		 * @param string $context Context of the operation.
		 *
		 * @return string Preset slug
		 */
		public function get_slug( $context = 'view' ) {
			return $this->slug;
		}

		/**
		 * Get preset slug
		 *
		 * @param string $context Context of the operation.
		 *
		 * @return string Preset slug
		 */
		public function get_layout( $context = 'view' ) {
			return $this->get_prop( 'layout', $context );
		}

		/**
		 * Get preset selector
		 *
		 * @param string $context Context of the operation.
		 *
		 * @return string Preset slug
		 */
		public function get_selector( $context = 'view' ) {
			return $this->get_prop( 'selector', $context );
		}

		/**
		 * Check if preset is enabled
		 *
		 * @param string $context Context of the operation.
		 *
		 * @return bool Preset status
		 */
		public function is_enabled( $context = 'view' ) {
			return yith_plugin_fw_is_true( $this->get_prop( 'enabled', $context ) );
		}

		/**
		 * Returns additional classes for the preset
		 *
		 * @return string Additional classes for the preset.
		 */
		public function get_additional_classes() {
			$additional_classes = array();

			if ( 'custom' === yith_wcan_get_option( 'yith_wcan_filters_style', 'default' ) ) {
				$additional_classes[] = 'custom-style';
			}

			$layout = $this->get_layout();

			if ( 'default' !== $layout ) {
				$additional_classes[] = $layout;
			}

			if ( ! yith_wcan_get_option( 'yith_wcan_filters_title', '' ) ) {
				$additional_classes[] = 'no-title';
			}

			/**
			 * APPLY_FILTERS: yith_wcan_preset_additional_classes
			 *
			 * List of classes added to preset HTML wrapper.
			 *
			 * @param array            $classes List of preset classes.
			 * @param YITH_WCAN_Preset $this    Preset object.
			 *
			 * @return array
			 */
			$additional_classes = apply_filters( 'yith_wcan_preset_additional_classes', $additional_classes, $this );

			return implode( ' ', $additional_classes );
		}

		/**
		 * Returns original post object for current preset
		 *
		 * @return WP_Post|bool Original post, or false if an error occurred.
		 */
		public function get_post() {
			if ( $this->get_id() && empty( $this->post ) ) {
				$this->post = get_post( $this->get_id() );
			}

			return $this->post;
		}

		/**
		 * Checks whether system should lazy load filters for this preset.
		 *
		 * @return bool Whether to lazy load or not.
		 */
		public function should_lazy_load_filters() {
			/**
			 * APPLY_FILTERS: yith_wcan_should_lazy_load_filters
			 *
			 * Whether to lazy load filters or not.
			 *
			 * @param bool             $lazy_load Whether to lazy load filters of not.
			 * @param int              $preset_id Preset id.
			 * @param YITH_WCAN_Preset $this      Preset object.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_should_lazy_load_filters', yith_plugin_fw_is_true( get_option( 'yith_wcan_lazy_load_filters' ) ), $this->get_id(), $this );
		}

		/**
		 * Checks whether system should show placeholders instead of rendering actual filters for this preset.
		 *
		 * @return bool Whether to show placeholders for filters..
		 */
		public function should_show_filters_placeholders() {
			/**
			 * APPLY_FILTERS: yith_wcan_should_show_filters_placeholders
			 *
			 * Whether to show placeholders for filters.
			 *
			 * @param bool             $lazy_load Whether to show placeholders instead of rendering filters..
			 * @param int              $preset_id Preset id.
			 * @param YITH_WCAN_Preset $this      Preset object.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_should_show_filters_placeholders', $this->should_lazy_load_filters() && ! YITH_WCAN_Ajax::is_processing( 'render_filter' ), $this->get_id(), $this );
		}

		/**
		 * Return an array of supported fields
		 *
		 * @return array Array of fields
		 */
		public static function get_fields() {
			return include YITH_WCAN_DIR . 'plugin-options/preset-options.php';
		}

		/* === SETTERS === */

		/**
		 * Set preset slug
		 *
		 * @param string $title Filter preset unique token.
		 */
		public function set_title( $title ) {
			$this->set_prop( 'title', $title );
		}

		/**
		 * Set preset slug
		 *
		 * @param string $slug Filter preset unique token.
		 */
		public function set_slug( $slug ) {
			$this->slug = $slug;
		}

		/**
		 * Set preset layout
		 *
		 * @param string $layout Filter preset layout.
		 */
		public function set_layout( $layout ) {
			$layout = in_array( $layout, array_keys( YITH_WCAN_Presets_Factory::get_supported_layouts() ), true ) ? $layout : 'default';

			$this->set_prop( 'layout', $layout );
		}

		/**
		 * Set preset selector
		 *
		 * @param string $selector Filter preset selector.
		 */
		public function set_selector( $selector ) {
			$this->set_prop( 'selector', $selector );
		}

		/**
		 * Set preset status
		 *
		 * @param string $enabled Filter preset status (yes/no for enabled/not enabled).
		 */
		public function set_enabled( $enabled ) {
			$this->set_prop( 'enabled', $enabled );
		}

		/**
		 * Set preset as enabled
		 *
		 * @return void
		 */
		public function enable() {
			$this->set_prop( 'enabled', 'yes' );
		}

		/**
		 * Set preset as disabled
		 *
		 * @return void
		 */
		public function disable() {
			$this->set_prop( 'enabled', 'no' );
		}

		/* === CRUD METHODS === */

		/**
		 * Save data to the database.
		 *
		 * @return int Preset ID
		 */
		public function save() {
			if ( $this->data_store ) {
				/**
				 * DO_ACTION: yith_wcan_before_$object_type_object_save
				 *
				 * Triggered before saving to the DB. Allows you to adjust object props before save.
				 * <code>$object_type</code> will be replaced with the object type the operation will be performed to.
				 *
				 * @param YITH_WCAN_Preset $this Current object.
				 * @param WC_Data_Store    $data_store Data store for current object.
				 */
				do_action( 'yith_wcan_before_' . $this->object_type . '_object_save', $this, $this->data_store );

				if ( $this->get_id() ) {
					$this->data_store->update( $this );
				} else {
					$this->data_store->create( $this );
				}
			}
			return $this->get_id();
		}

		/* === FILTERS METHODS === */

		/**
		 * Return number of filter pages available
		 *
		 * @return int nuber of pages
		 */
		public function get_pages() {
			return ceil( $this->count_filters() / YITH_WCAN_Presets::FILTERS_PER_PAGE );
		}

		/**
		 * Check if preset has a filter with a specific id
		 *
		 * @param int    $filter_id Filter id.
		 * @param string $context Context for the operation.
		 *
		 * @return bool Whether filter with specified ID exists or not.
		 */
		public function has_filter( $filter_id, $context = 'view' ) {
			$filters = $this->get_raw_filters( $context );

			return array_key_exists( $filter_id, $filters );
		}

		/**
		 * Check if preset has any filter
		 *
		 * @param string $context Context for the operation.
		 *
		 * @return bool Whether preset has filters.
		 */
		public function has_filters( $context = 'view' ) {
			return ! ! $this->get_raw_filters( $context );
		}

		/**
		 * Checks if preset has any relevant filter for current product selection
		 *
		 * @return bool Whether preset has relevant filters.
		 */
		public function has_relevant_filters() {
			$filters = $this->get_filters();

			if ( ! $filters ) {
				return false;
			}

			if ( $this->should_lazy_load_filters() ) {
				return true;
			}

			foreach ( $filters as $filter ) {
				if ( $filter->is_relevant() ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns true if preset needs pagination
		 *
		 * @return bool Whether preset needs pagination or not.
		 */
		public function needs_pagination() {
			return YITH_WCAN_Presets::FILTERS_PER_PAGE < $this->count_filters();
		}

		/**
		 * Returns raw array of filters (instead of objects). For internal use only
		 *
		 * @param string $context Context for the operation.
		 * @param int    $page    Page to retrieve; false if you want all filters (default: false).
		 *
		 * @return array Array of filters.
		 */
		public function get_raw_filters( $context = 'view', $page = false ) {
			$filters = $this->get_prop( 'filters', $context );

			// slice array according to offset a limit parameters.
			if ( $page ) {
				$limit   = YITH_WCAN_Presets::FILTERS_PER_PAGE;
				$offset  = ( $page - 1 ) * $limit;
				$filters = array_slice( $filters, $offset, $limit, true );
			}

			return $filters;
		}

		/**
		 * Returns filters for current preset
		 *
		 * @param int $page Page to retrieve; false if you want all filters (default: false).
		 *
		 * @return YITH_WCAN_Filter[]
		 */
		public function get_filters( $page = false ) {
			if ( ! empty( $this->filters_objects ) ) {
				return $this->filters_objects;
			}

			$filters = $this->get_raw_filters( 'edit', $page );
			$results = array();

			if ( empty( $filters ) ) {
				return array();
			}

			foreach ( $filters as $filter_id => $filter ) {
				// set ids.
				$filter['id']        = $filter_id;
				$filter['preset_id'] = $this->get_id();

				$results[ $filter_id ] = yith_wcan_get_filter( $filter );
			}

			/**
			 * APPLY_FILTERS: yith_wcan_get_filters
			 *
			 * Filters the list of preset's filters.
			 *
			 * @param array    $filters Presets filters.
			 * @param int|bool $page    Requested page of filters; false if all are needed.
			 *
			 * @return array
			 */
			$results = apply_filters( 'yith_wcan_get_filters', $results, $page );

			if ( $page ) {
				return $results;
			}

			$this->filters_objects = $results;
			return $this->filters_objects;
		}

		/**
		 * Count how many filters current preset have
		 *
		 * @return int Number of filters for this preset
		 */
		public function count_filters() {
			return count( $this->get_raw_filters() );
		}

		/**
		 * Returns max ID used for filters (useful when you need to assign ID to a new filter)
		 *
		 * @return int
		 */
		public function get_max_filter_id() {
			$filters = $this->get_raw_filters();

			return ! empty( $filters ) ?  max( array_keys( $filters ) ) : 0;
		}

		/**
		 * Set filters for current preset
		 *
		 * @param array $filters Formatted array of filters.
		 * @param int   $page    Page to overwrite; false if the entire set should be overridden (default: false).
		 *
		 * @return void
		 */
		public function set_filters( $filters, $page = false ) {
			if ( $page ) {
				$original_filters = $this->get_raw_filters();

				$limit = YITH_WCAN_Presets::FILTERS_PER_PAGE;
				$tail  = array_slice( $original_filters, $page * $limit, count( $original_filters ), true );

				$filters = array_merge(
					$filters,
					$tail
				);
			}

			$this->set_prop( 'filters', $filters );
		}

		/**
		 * Set filters for current preset
		 *
		 * @param int   $filter_id Id of the filter to set.
		 * @param array $filter Formatted filter.
		 * @param int   $prev Id of the filter that current filter should follow; if not provided,
		 *                    filter will be appended. If -1 is provided, filter will be prepended.
		 *
		 * @return void
		 */
		public function set_filter( $filter_id, $filter, $prev = false ) {
			$filters = $this->get_raw_filters();

			// remove filter with current ID from the list.
			if ( isset( $filters[ $filter_id ] ) ) {
				$filter  = array_merge( $filters[ $filter_id ], $filter );
				unset( $filters[ $filter_id ] );
			}

			// append filter in the correct location.
			if ( -1 === $prev ) {
				$filters = array( $filter_id => $filter ) + $filters;
			} elseif ( false !== $prev && isset( $filters[ $prev ] ) ) {
				$pos = array_search( $prev, array_keys( $filters ), true );

				$part_1 = array_slice( $filters, 0, $pos + 1, true );
				$part_2 = array_slice( $filters, $pos + 1, null, true );

				$filters = $part_1 + array( $filter_id => $filter ) + $part_2;
			} else {
				$filters[ $filter_id ] = $filter;
			}

			// set new value for filters prop.
			$this->set_prop( 'filters', $filters );
		}

		/**
		 * Remove all filters for current preset
		 *
		 * @return void
		 */
		public function delete_filters() {
			$this->set_filters( array() );
		}

		/**
		 * Remove all filters for current preset
		 *
		 * @param int $filter_id Filter id.
		 *
		 * @return void
		 */
		public function delete_filter( $filter_id ) {
			if ( ! $this->has_filter( $filter_id ) ) {
				return;
			}

			$filters = $this->get_raw_filters();
			unset( $filters[ $filter_id ] );

			$this->set_filters( $filters );
		}

		/* === HELPER METHODS === */

		/**
		 * Return admin edit url for current item
		 *
		 * @return string Edit url
		 */
		public function get_admin_edit_url() {
			return YITH_WCAN()->admin->get_panel_url(
				'filter-preset',
				array(
					'action' => 'edit',
					'preset' => $this->get_id(),
				)
			);
		}

		/**
		 * Get admin url to visit to clone this preset
		 *
		 * @return string Url to clone preset
		 */
		public function get_admin_clone_url() {
			if ( ! $this->current_user_can( 'clone' ) ) {
				return false;
			}

			return add_query_arg(
				array(
					'action' => 'yith_wcan_clone_preset',
					'preset' => $this->get_id(),
				),
				wp_nonce_url( admin_url( 'admin.php' ), 'clone_preset' )
			);
		}

		/**
		 * Get admin url to visit to delete this preset
		 *
		 * @return string Url to delete preset
		 */
		public function get_admin_delete_url() {
			if ( ! $this->current_user_can( 'clone' ) ) {
				return false;
			}

			return add_query_arg(
				array(
					'action' => 'yith_wcan_delete_preset',
					'preset' => $this->get_id(),
				),
				wp_nonce_url( admin_url( 'admin.php' ), 'delete_preset' )
			);
		}

		/**
		 * Check that a specific user has a certain capability over this preset
		 *
		 * @param int    $user_id User id.
		 * @param string $cap Capability to check.
		 *
		 * @return bool Whether user has capability or not
		 */
		public function user_can( $user_id, $cap ) {
			$default = user_can( $user_id, 'manage_woocommerce' );
			$public  = array(
				'read',
			);

			if ( ! $default && in_array( $cap, $public, true ) ) {
				$default = true;
			}

			/**
			 * APPLY_FILTERS: yith_wcan_preset_user_can
			 *
			 * Allow third party code filter the capability a user has over current preset
			 *
			 * @param bool             $can     Whether user has required cap over current preset.
			 * @param int              $user_id Id of the user to test.
			 * @param string           $cap     Capability to test.
			 * @param YITH_WCAN_Preset $this    Preset object.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_preset_user_can', $default, $user_id, $cap, $this );
		}

		/**
		 * Check that current user has a certain capability over this preset
		 *
		 * @param string $cap Capability to check.
		 *
		 * @return bool Whether current user has capability or not
		 */
		public function current_user_can( $cap ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$default = $this->user_can( get_current_user_id(), $cap );

			/**
			 * APPLY_FILTERS: yith_wcan_preset_current_user_can
			 *
			 * Allow third party code filter the capability current user has over current preset
			 *
			 * @param bool             $can     Whether current user has required cap over current preset.
			 * @param string           $cap     Capability to test.
			 * @param YITH_WCAN_Preset $this    Preset object.
			 *
			 * @return bool
			 */
			return apply_filters( 'yith_wcan_preset_current_user_can', $default, $cap, $this );
		}
	}
}
