<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules WPForms Privacy Consent class.
 *
 * Compatibility since: 1.6.0
 *
 * @class Cookie_Notice_Modules_WPForms_Privacy_Consent
 */
class Cookie_Notice_Modules_WPForms_Privacy_Consent {

	private $defaults = [];
	private $source = [];

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// get main instance
		$cn = Cookie_Notice();

		$this->source = [
			'name'			=> __( 'WPForms', 'cookie-notice' ),
			'id'			=> 'wpforms',
			'id_type'		=> 'integer',
			'type'			=> 'dynamic',
			'availability'	=> cn_is_plugin_active( 'wpforms', 'privacy-consent' ),
			'status'		=> $cn->options['privacy_consent']['wpforms_active'],
			'status_type'	=> $cn->options['privacy_consent']['wpforms_active_type'],
			'forms'			=> []
		];

		// register source
		$cn->privacy_consent->add_instance( $this, $this->source['id'] );
		$cn->privacy_consent->add_source( $this->source );

		add_action( 'admin_init', [ $this, 'register_source' ] );

		// check compliance status
		if ( $cn->get_status() !== 'active' )
			return;

		// forms
		add_action( 'wpforms_frontend_output', [ $this, 'wpforms_shortcode' ], 19, 5 );
	}

	/**
	 * Register source.
	 *
	 * @return void
	 */
	public function register_source() {
		register_setting(
			'cookie_notice_privacy_consent_wpforms',
			'cookie_notice_privacy_consent_wpforms',
			[
				'type' => 'array'
			]
		);
	}

	/**
	 * Validate source.
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public function validate( $input ) {
		// get main instance
		$cn = Cookie_Notice();

		$input['wpforms_active'] = isset( $input['wpforms_active'] );
		$input['wpforms_active_type'] = isset( $input['wpforms_active_type'] ) && array_key_exists( $input['wpforms_active_type'], $cn->privacy_consent->form_active_types ) ? $input['wpforms_active_type'] : $cn->defaults['privacy_consent']['wpforms_active_type'];

		return $input;
	}

	/**
	 * Check whether form exists.
	 *
	 * @param int $form_id
	 *
	 * @return bool
	 */
	public function form_exists( $form_id ) {
		$query = new WP_Query( [
			'p'				=> $form_id,
			'post_status'	=> 'publish',
			'post_type'		=> 'wpforms',
			'fields'		=> 'ids',
			'no_found_rows'	=> true
		] );

		return $query->have_posts();
	}

	/**
	 * Get forms.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_forms( $args ) {
		// get only published forms
		$query = new WP_Query( [
			'post_status'		=> 'publish',
			'post_type'			=> 'wpforms',
			'order'				=> $args['order'],
			'orderby'			=> $args['orderby'],
			'fields'			=> 'all',
			'posts_per_page'	=> 10,
			'no_found_rows'		=> false,
			'paged'				=> $args['page'],
			's'					=> $args['search']
		] );

		$forms = [];

		// any forms?
		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post ) {
				$forms[] = [
					'id'		=> $post->ID,
					'title'		=> $post->post_title,
					'date'		=> $post->post_date,
					'fields'	=> []
				];
			}
		}

		return [
			'forms'		=> $forms,
			'total'		=> $query->found_posts,
			'max_pages'	=> $query->max_num_pages
		];
	}

	/**
	 * Get form.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_form( $args ) {
		// get only one form
		$query = new WP_Query( [
			'p'				=> (int) $args['form_id'],
			'post_status'	=> 'publish',
			'post_type'		=> 'wpforms',
			'fields'		=> 'all',
			'no_found_rows'	=> true
		] );

		// any forms?
		if ( ! empty( $query->posts[0] ) ) {
			$form = [
				'source'	=> $this->source['id'],
				'id'		=> $query->posts[0]->ID,
				'title'		=> Cookie_Notice()->privacy_consent->strcut( sanitize_text_field( $query->posts[0]->post_title ), 100 ),
				'fields'	=> [
					'subject'	=> [
						'first_name'	=> '',
						'last_name'		=> ''
					]
				]
			];
		} else
			$form = [];

		return $form;
	}

	/**
	 * WPForms shortcode output.
	 *
	 * @param array|mixed $data
	 * @param null $deprecated
	 * @param bool $title
	 * @param bool $description
	 * @param array $errors
	 *
	 * @return void
	 */
	public function wpforms_shortcode( $data, $deprecated, $title, $description, $errors ) {
		$data = (array) $data;

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $data['id'], $this->source['id'] ) ) {
			// get form data
			$form_data = $this->get_form( [
				'form_id' => $data['id']
			] );

			echo '
			<script>
			if ( typeof huOptions !== \'undefined\' ) {
				var huFormData = ' . wp_json_encode( $form_data ) . ';
				var huFormNode = document.querySelector( \'[id="wpforms-' . (int) $data['id'] . '"] form\' );

				var firstName = huFormNode.querySelector( \'input.wpforms-field-name-first\' );
				var lastName = huFormNode.querySelector( \'input.wpforms-field-name-last\' );

				if ( firstName )
					huFormData[\'fields\'][\'subject\'][\'first_name\'] = firstName.getAttribute( \'name\' );

				if ( lastName )
					huFormData[\'fields\'][\'subject\'][\'last_name\'] = lastName.getAttribute( \'name\' );

				huFormData[\'node\'] = huFormNode;
				huOptions[\'forms\'].push( huFormData );
			}
			</script>';
		}
	}
}

new Cookie_Notice_Modules_WPForms_Privacy_Consent();