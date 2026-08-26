<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Privacy_Consent_List_Table class.
 *
 * @class Cookie_Notice_Privacy_Consent_List_Table
 */
class Cookie_Notice_Privacy_Consent_List_Table extends WP_List_Table {

	private $cn_empty_init = false;
	private $cn_source = [];
	private $cn_forms = [];

	/**
	 * Set source.
	 *
	 * @param array $source
	 *
	 * @return void
	 */
	public function cn_set_source( $source ) {
		$this->cn_source = $source;
	}

	/**
	 * Set source forms.
	 *
	 * @param array $forms
	 *
	 * @return void
	 */
	public function cn_set_forms( $forms ) {
		$this->cn_forms = $forms;
	}

	/**
	 * Set empty init.
	 *
	 * @return void
	 */
	public function cn_empty_init() {
		$this->cn_empty_init = true;
	}

	/**
	 * Display extra controls between bulk actions and pagination.
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		// skip top navigation
		if ( $which === 'top' )
			return;

		echo '<span class="spinner"></span>';
	}

	/**
	 * Generate table navigation.
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		// avoid different nonce and skip top navigation
		if ( $which === 'top' )
			return;

		// skip static source and sources with less than 11 forms
		if ( $this->cn_source['type'] === 'static' || $this->get_pagination_arg( 'total_items' ) < 11 )
			return;

		echo '
		<div class="tablenav bottom">';

		if ( $this->has_items() ) {
			echo '
			<div class="alignleft actions bulkactions">';

			$this->bulk_actions( $which );

			echo '
			</div>';
		}

		$this->pagination( $which );
		$this->extra_tablenav( $which );

		echo '
			<br class="clear" />
		</div>';
	}

	/**
	 * Get a list of CSS classes.
	 *
	 * @return array
	 */
	protected function get_table_classes() {
		if ( $this->cn_source['type'] === 'static' )
			return parent::get_table_classes();

		return [ 'widefat', 'fixed', 'striped', esc_attr( 'table-view-' . get_user_setting( 'posts_list_mode', 'list' ) ), $this->_args['plural'], 'loading' ];
	}

	/**
	 * Display search box.
	 *
	 * @param string $text
	 * @param string $input_id
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() )
			return;

		/* update input id
		$input_id .= '-search-input';

		echo '
		<p class="search-box">
			<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . ':</label>
			<input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="" />
			<a id="' . esc_attr( $this->cn_source['id'] . '-search-submit' ) . '" class="button" href="' .esc_url( remove_query_arg( wp_removable_query_args(), set_url_scheme( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ) ) . '">' . esc_html( $text ) . '</a>
		</p>';
		*/
	}

	/**
	 * Print column headers.
	 *
	 * @param bool $with_id
	 *
	 * @return void
	 */
	public function print_column_headers( $with_id = true ) {
		// do not print column ids
		parent::print_column_headers( false );
	}

	/**
	 * Handle AJAX request.
	 *
	 * @return void
	 */
	function ajax_response() {
		check_ajax_referer( 'cn-privacy-consent-list-table-nonce', 'nonce' );

		$this->prepare_items();

		ob_start();

		if ( ! empty( $_REQUEST['no_placeholder'] ) )
			$this->display_rows();
		else
			$this->display_rows_or_placeholder();

		$rows = ob_get_clean();

		ob_start();

		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();

		$this->pagination( 'bottom' );
		$pagination = ob_get_clean();

		$response = [
			'rows'				=> $rows,
			'column_headers'	=> $headers,
			'pagination'		=> $pagination
		];

		// get pagination data
		$total_items = $this->get_pagination_arg( 'total_items' );
		$total_pages = $this->get_pagination_arg( 'total_pages' );

		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 form', '%s forms', $total_items ), number_format_i18n( $total_items ) );

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Prepare items for table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// get main instance
		$cn = Cookie_Notice();

		// get consent logs
		if ( is_multisite() && $cn->is_network_admin() && $cn->is_plugin_network_active() && $cn->network_options['general']['global_override'] )
			$privacy_consent = get_site_option( 'cookie_notice_privacy_consent_' . $this->cn_source['id'], [] );
		else
			$privacy_consent = get_option( 'cookie_notice_privacy_consent_' . $this->cn_source['id'], [] );

		$items = [];
		$data = $this->cn_forms;

		foreach ( $data['forms'] as $form ) {
			$items[] = [
				'title'		=> $this->cn_source['type'] === 'dynamic' ? $form['title'] : $form['name'],
				'id'		=> $form['id'],
				'fields'	=> count( $form['fields'] ),
				'date'		=> $this->cn_source['type'] === 'dynamic' ? $form['date'] : '-',
				'status'	=> array_key_exists( $form['id'], $privacy_consent ) && array_key_exists( 'status', $privacy_consent[$form['id']] ) ? $privacy_consent[$form['id']]['status'] : false
			];
		}

		if ( $this->cn_source['type'] === 'dynamic' ) {
			$this->set_pagination_args(
				[
					'total_items'	=> empty( $data['total'] ) ? 0 : (int) $data['total'],
					'total_pages'	=> empty( $data['max_pages'] ) ? 0 : (int) $data['max_pages'],
					// 'per_page'		=> $per_page,
					'orderby'		=> ! empty( $_POST['orderby'] ) ? sanitize_key( $_POST['orderby'] ) : 'title',
					'order'			=> ! empty( $_POST['order'] ) ? sanitize_key( $_POST['order'] ) : 'asc'
				]
			);
		}

		$this->_column_headers = [ $this->get_columns(), [ 'date' ], $this->get_sortable_columns(), '' ];

		$this->items = $items;
	}

	/**
	 * Define columns in listing table.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'title'		=> __( 'Form Title', 'cookie-notice' ),
			'id'		=> __( 'Form ID', 'cookie-notice' ),
			'fields'	=> __( 'Fields', 'cookie-notice' ),
			'date'		=> __( 'Date', 'cookie-notice' ),
			'status'	=> __( 'Status', 'cookie-notice' )
		];

		return $columns;
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		if ( $this->cn_source['type'] === 'static' )
			return [];
		else
			return [
				'title'	=> [ 'title', false ]
				// 'date'	=> [ 'date', true ]
			];
	}

	/**
	 * Define what data to show on each column of the table.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return esc_html( $item[$column_name] );
	}

	/**
	 * Display form title.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_title( $item ) {
		return '<a href="#">' . esc_html( $item['title'] ) . '</a>';
	}

	/**
	 * Display status.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		return '<input type="checkbox" name="" value="1" class="cn-privacy-consent-form-status" data-source="' . esc_attr( $this->cn_source['id'] ) . '" data-form_id="' . esc_attr( $item['id'] ) . '" ' . checked( $item['status'], true, false ) . ' />';
	}

	/**
	 * Display bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return [];
	}

	/**
	 * Display empty result.
	 *
	 * @return void
	 */
	public function no_items() {
		// display spinner for the first visit for dynamic source
		if ( $this->cn_source['type'] === 'dynamic' && $this->cn_empty_init ) {
			$this->cn_empty_init = false;

			echo '<span class="spinner inside is-active"></span>';
		} else
			echo __( 'No forms found.', 'cookie-notice' );
	}
}
