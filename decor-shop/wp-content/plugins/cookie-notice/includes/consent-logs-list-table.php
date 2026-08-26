<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Consent_Logs_List_Table class.
 *
 * @class Cookie_Notice_Consent_Logs_List_Table
 */
class Cookie_Notice_Consent_Logs_List_Table extends WP_List_Table {

	private $cn_data = [];
	private $cn_item_number = 0;

	/**
	 * Set data.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function cn_set_data( $data ) {
		$this->cn_data = $data;
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// prepare items
		$items = [];

		// no data?
		if ( ! empty( $this->cn_data ) ) {
			foreach ( $this->cn_data as $no => $consent_log ) {
				$categories = [];

				if ( $consent_log->ev_essential )
					$categories[] = esc_html__( 'Basic Operations', 'cookie-notice' );

				if ( $consent_log->ev_functional )
					$categories[] = esc_html__( 'Content Personalization', 'cookie-notice' );

				if ( $consent_log->ev_analytics )
					$categories[] = esc_html__( 'Site Optimization', 'cookie-notice' );

				if ( $consent_log->ev_marketing )
					$categories[] = esc_html__( 'Ad Personalization', 'cookie-notice' );

				// get current date
				$timestamp = new DateTime( $consent_log->timestamp );

				// get deuration in days
				$duration = (int) $consent_log->ev_eventdetails_expiry;

				if ( $duration === 30 )
					$duration = __( '1 month', 'cookie-notice' );
				elseif ( $duration === 90 )
					$duration = __( '3 months', 'cookie-notice' );
				elseif ( $duration === 182 )
					$duration = __( '6 months', 'cookie-notice' );
				elseif ( $duration === 365 )
					$duration = __( '1 year', 'cookie-notice' );
				elseif ( $duration === 730 )
					$duration = __( '2 years', 'cookie-notice' );

				$items[] = [
					'consent_id'			=> $consent_log->ev_eventdetails_consentid,
					'consent_level'			=> sprintf( __( 'Level %d', 'cookie-notice' ), $consent_log->ev_consentlevel ),
					'consent_categories'	=> implode( ', ', $categories ),
					'consent_duration'		=> $duration,
					'consent_time'			=> $timestamp->format( get_option( 'time_format' ) ) . ' ' . __( 'GMT', 'cookie-notice' ),
					'consent_ip_address'	=> $consent_log->rj_ip
				];
			}
		}

		// count items
		$noi = count( $items );

		$per_page = 10;

		$this->set_pagination_args(
			[
				'total_items'	=> $noi,
				'total_pages'	=> (int) ceil( $noi / $per_page ),
				'per_page'		=> $per_page
			]
		);

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns(), '' ];

		$this->items = $items;
	}

	/**
	 * Define columns in listing table.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'consent_id'			=> __( 'Consent ID', 'cookie-notice' ),
			'consent_level'			=> __( 'Consent Level', 'cookie-notice' ),
			'consent_categories'	=> __( 'Categories', 'cookie-notice' ),
			'consent_duration'		=> __( 'Duration', 'cookie-notice' ),
			'consent_time'			=> __( 'Time', 'cookie-notice' ),
			'consent_ip_address'	=> __( 'IP address', 'cookie-notice' )
		];

		return $columns;
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [];
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
	 * Generate table navigation.
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		parent::display_tablenav( $which );
	}

	/**
	 * Generate content for a single row of the table.
	 *
	 * @param array $item
	 *
	 * @return void
	 */
	public function single_row( $item ) {
		$this->cn_item_number++;

		echo '<tr' . ( $this->cn_item_number > $this->_pagination_args['per_page'] ? ' style="display: none"' : '' ) . '>';

		$this->single_row_columns( $item );

		echo '</tr>';
	}

	/**
	 * Display the pagination.
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		$output = '<span class="displaying-num">' . sprintf(
			/* translators: %s: Number of items. */
			_n( '%s item', '%s items', $total_items ),
			number_format_i18n( $total_items )
		) . '</span>';

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		// first page
		$page_links[] = sprintf(
			"<a class='first-page button disabled' href='#'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
			'</a>',
			/* translators: Hidden accessibility text. */
			__( 'First page' ),
			'&laquo;'
		);

		// previous page
		$page_links[] = sprintf(
			"<a class='prev-page button disabled' href='#'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
			'</a>',
			/* translators: Hidden accessibility text. */
			__( 'Previous page' ),
			'&lsaquo;'
		);

		$html_current_page  = '<span class="current-page">' . (int) $this->get_pagenum() . '</span>';
		$total_pages_before = sprintf(
			'<span class="screen-reader-text">%s</span>' .
			'<span id="table-paging-' . $which . '" class="paging-input">' .
			'<span class="tablenav-paging-text">',
			/* translators: Hidden accessibility text. */
			__( 'Current Page' )
		);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		$page_links[] = $total_pages_before . sprintf(
			/* translators: 1: Current page, 2: Total pages. */
			_x( '%1$s of %2$s', 'paging' ),
			$html_current_page,
			$html_total_pages
		) . $total_pages_after;

		// next page
		$page_links[] = sprintf(
			"<a class='next-page button' href='#'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
			'</a>',
			/* translators: Hidden accessibility text. */
			__( 'Next page' ),
			'&rsaquo;'
		);

		// last page
		$page_links[] = sprintf(
			"<a class='last-page button' href='#'>" .
				"<span class='screen-reader-text'>%s</span>" .
				"<span aria-hidden='true'>%s</span>" .
			'</a>',
			/* translators: Hidden accessibility text. */
			__( 'Last page' ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';

		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class .= ' hide-if-js';

		$output .= "\n<span class='$pagination_links_class' data-total=" . (int) $total_pages . ">" . implode( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
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
		echo __( 'No cookie consent logs found.', 'cookie-notice' );
	}
}
