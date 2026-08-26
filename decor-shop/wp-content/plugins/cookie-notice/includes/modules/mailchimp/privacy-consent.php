<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules Mailchimp Privacy Consent class.
 *
 * Compatibility since: 4.0.0
 *
 * @class Cookie_Notice_Modules_Mailchimp_Privacy_Consent
 */
class Cookie_Notice_Modules_Mailchimp_Privacy_Consent {

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
			'name'			=> __( 'Mailchimp for WP', 'cookie-notice' ),
			'id'			=> 'mailchimp',
			'id_type'		=> 'integer',
			'type'			=> 'dynamic',
			'availability'	=> cn_is_plugin_active( 'mailchimp', 'privacy-consent' ),
			'status'		=> $cn->options['privacy_consent']['mailchimp_active'],
			'status_type'	=> $cn->options['privacy_consent']['mailchimp_active_type'],
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
		add_filter( 'mc4wp_form_after_fields', [ $this, 'form_html' ], 10, 2 );
		add_action( 'mc4wp_form_success', [ $this, 'handle_form' ] );
	}

	/**
	 * Register source.
	 *
	 * @return void
	 */
	public function register_source() {
		register_setting(
			'cookie_notice_privacy_consent_mailchimp',
			'cookie_notice_privacy_consent_mailchimp',
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

		$input['mailchimp_active'] = isset( $input['mailchimp_active'] );
		$input['mailchimp_active_type'] = isset( $input['mailchimp_active_type'] ) && array_key_exists( $input['mailchimp_active_type'], $cn->privacy_consent->form_active_types ) ? $input['mailchimp_active_type'] : $cn->defaults['privacy_consent']['mailchimp_active_type'];

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
			'post_type'		=> 'mc4wp-form',
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
			'post_type'			=> 'mc4wp-form',
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
			'post_type'		=> 'mc4wp-form',
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
						'first_name'	=> 'FNAME',
						'last_name'		=> 'LNAME',
						'email'			=> 'EMAIL'
					],
					'preferences'	=> [
						'terms'		=> 'AGREE_TO_TERMS'
					]
				]
			];
		} else
			$form = [];

		return $form;
	}

	/**
	 * Get form.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function form_html( $html, $form ) {
		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form->ID, $this->source['id'] ) ) {
			// get form data
			$form_data = $this->get_form( [
				'form_id' => $form->ID
			] );

			$html .= '
			<script>
			if ( typeof huOptions !== \'undefined\' ) {
				var huFormData = ' . wp_json_encode( $form_data ) . ';
				var huFormNode = document.querySelector( \'form[class*="mc4wp-form-' . (int) $form->ID . '"]\' );

				huFormData[\'node\'] = huFormNode;
				huOptions[\'forms\'].push( huFormData );
			}
			</script>';
		}

		return $html;
	}

	/**
	 * Handle form submission.
	 *
	 * @param object $form
	 *
	 * @return void
	 */
	public function handle_form( $form ) {
		// get main instance
		$cn = Cookie_Notice();

		// active registration form?
		if ( $cn->privacy_consent->is_form_active( $form->ID, $this->source['id'] ) )
			$cn->privacy_consent->set_cookie( 'true' );
	}
}

new Cookie_Notice_Modules_Mailchimp_Privacy_Consent();