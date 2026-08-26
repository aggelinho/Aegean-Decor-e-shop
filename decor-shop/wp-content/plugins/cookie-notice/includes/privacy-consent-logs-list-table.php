<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie_Notice_Privacy_Consent_Logs_List_Table class.
 *
 * @class Cookie_Notice_Privacy_Consent_Logs_List_Table
 */
class Cookie_Notice_Privacy_Consent_Logs_List_Table extends WP_List_Table {

	private $cn_data = [];
	private $cn_item_number = 0;
	private $cn_empty_init = false;

	/**
	 * Set empty init.
	 *
	 * @return void
	 */
	public function cn_empty_init() {
		$this->cn_empty_init = true;
	}

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
	 * Display content.
	 *
	 * @return void
	 */
	public function views() {
		// get main instance
		$cn = Cookie_Notice();
		
		$login_url = esc_url( $cn->get_url( 'login', '?utm_campaign=consentlogs&utm_source=wordpress&utm_medium=link' ) );

		$message = __( 'The table below shows the latest privacy consent records collected from the forms on your website.', 'cookie-notice' );
		$message .= ' ' . sprintf( __( 'Log in to the <a href="%s" target="_blank">Compliance by Hu-manity.co</a> dashboard to view details or export proof of consent.', 'cookie-notice' ), $login_url );
		
		// disable if basic plan and data older than 7 days
		if ( $cn->get_subscription() === 'basic' )
			$message .= '<br/><span class="cn-asterix">*</span> ' . __( 'Note: sites on the Compliance by Hu-manity.co Free Plan can collect up to 100 records.', 'cookie-notice' );

		echo '<p class="description">' . wp_kses_post( $message ) . '</p>';
	}

	/**
	 * Prepare items for table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// prepare items
		$items = [];

		// no data?
		if ( ! empty( $this->cn_data ) ) {
			foreach ( $this->cn_data as $consent ) {
				$items[] = [
					'subject'		=> $consent->subject_id,
					'preferences'	=> $consent->preferences,
					'form_id'		=> $consent->form_id,
					'form_title'	=> ! empty( $consent->form_title ) ? $consent->form_title : __( '—', 'cookie-notice' ),
					'source'		=> ! empty( $consent->source ) ? $consent->source : 'unknown',
					'date'			=> $consent->created_at,
					'ip_address'	=> $consent->ip_address
				];
			}
		}

		// count items
		$noi = count( $items );

		$per_page = 20;

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
	 * Generate table navigation.
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		// avoid different nonce and skip top navigation
		if ( $which === 'top' ) {
			echo '
			<div class="tablenav top">';

			$this->pagination( $which );

			echo '
				<br class="clear" />
			</div>';
		} else {
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
	 * Get a list of CSS classes.
	 *
	 * @return array
	 */
	protected function get_table_classes() {
		return [ 'widefat', 'fixed', 'striped', esc_attr( 'table-view-' . get_user_setting( 'posts_list_mode', 'list' ) ), $this->_args['plural'], 'loading' ];
	}

	/**
	 * Define columns in listing table.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'subject'		=> __( 'Subject', 'cookie-notice' ),
			'preferences'	=> __( 'Preferences', 'cookie-notice' ),
			'source'		=> __( 'Source', 'cookie-notice' ),
			'form_title'	=> __( 'Form', 'cookie-notice' ),
			'date'			=> __( 'Date', 'cookie-notice' ),
			'ip_address'	=> __( 'IP address', 'cookie-notice' )
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
	 * Display source.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_source( $item ) {
		if ( $item['source'] === 'unknown' )
			$label = __( '—', 'cookie-notice' );
		else {
			$source = Cookie_Notice()->privacy_consent->get_source( $item['source'] );

			if ( ! empty( $source ) )
				$label = $source['name'];
			else
				$label = __( '—', 'cookie-notice' );
		}

		return esc_html( $label );
	}

	/**
	 * Display date.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		// get current date
		$datetime = new DateTime( $item['date'] );

		return esc_html( $datetime->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . ' ' . __( 'GMT', 'cookie-notice' ) );
	}

	/**
	 * Display preferences.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_preferences( $item ) {
		$preferences = (array) $item['preferences'];

		return esc_html( empty( $preferences ) ? '—' : implode( ', ', array_keys( $preferences ) ) );
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
		// display spinner for the first visit
		if ( $this->cn_empty_init ) {
			$this->cn_empty_init = false;

			echo '<span class="spinner inside is-active"></span>';
		} else
			echo __( 'No privacy consent logs found.', 'cookie-notice' );
	}
}
