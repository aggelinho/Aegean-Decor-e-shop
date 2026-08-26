<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules WordPress Privacy Consent class.
 *
 * Compatibility since: ?
 *
 * @class Cookie_Notice_Modules_WordPress_Privacy_Consent
 */
class Cookie_Notice_Modules_WordPress_Privacy_Consent {

	private $defaults = [
		'wp_registration_form'	=> [
			'status'	=> true
		],
		'wp_comment_form'	=> [
			'status'	=> true
		]
	];
	private $source = [];
	private $comment_form_active = false;
	private $comment_passed_validation = false;
	private $comment_validation = false;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// get main instance
		$cn = Cookie_Notice();

		$this->source = [
			'name'			=> __( 'WordPress', 'cookie-notice' ),
			'id'			=> 'wordpress',
			'id_type'		=> 'string',
			'type'			=> 'static',
			'availability'	=> true,
			'status'		=> $cn->options['privacy_consent']['wordpress_active'],
			'status_type'	=> $cn->options['privacy_consent']['wordpress_active_type'],
			'forms'			=> [
				'wp_registration_form'	=> [
					'name'				=> __( 'Registration Form', 'cookie-notice' ),
					'id'				=> 'wp_registration_form',
					'type'				=> 'static',
					'mode'				=> 'automatic',
					'status'			=> false,
					'logged_out_only'	=> true,
					'fields'			=> [
						'username'	=> '',
						'email'		=> ''
					],
					'subject'			=> [
						'email'			=> 'user_email',
						'first_name'	=> 'user_login'
					],
					'preferences'		=> [],
					'excluded'			=> []
				],
				'wp_comment_form'		=> [
					'name'				=> __( 'Comment Form', 'cookie-notice' ),
					'id'				=> 'wp_comment_form',
					'type'				=> 'static',
					'mode'				=> 'automatic',
					'status'			=> false,
					'logged_out_only'	=> true,
					'fields'			=> [
						'comment'	=> '',
						'name'		=> '',
						'email'		=> '',
						'website'	=> ''
					],
					'subject'			=> [
						'email'			=> 'email',
						'first_name'	=> 'author'
					],
					'preferences'		=> [
						'privacy'	=> 'wp-comment-cookies-consent'
					],
					'excluded'			=> []
				]
			]
		];

		// register source
		$cn->privacy_consent->add_instance( $this, $this->source['id'] );
		$cn->privacy_consent->add_source( $this->source );

		add_action( 'admin_init', [ $this, 'register_source' ] );

		// check compliance status
		if ( $cn->get_status() !== 'active' )
			return;

		// comments
		add_action( 'comment_form', [ $this, 'comment_form' ] );
		add_action( 'comment_post', [ $this, 'comment_post' ], 10, 3 );
		add_action( 'init', [ $this, 'comment_submission_start' ] );
		add_action( 'shutdown', [ $this, 'comment_submission_end' ] );

		// registration
		add_action( 'register_form', [ $this, 'register_form' ] );
		add_filter( 'registration_errors', [ $this, 'registration_errors' ], PHP_INT_MAX, 3 );
	}

	/**
	 * Register source.
	 *
	 * @return void
	 */
	public function register_source() {
		register_setting(
			'cookie_notice_privacy_consent_wordpress',
			'cookie_notice_privacy_consent_wordpress',
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

		$input['wordpress_active'] = isset( $input['wordpress_active'] );
		$input['wordpress_active_type'] = isset( $input['wordpress_active_type'] ) && array_key_exists( $input['wordpress_active_type'], $cn->privacy_consent->form_active_types ) ? $input['wordpress_active_type'] : $cn->defaults['privacy_consent']['wordpress_active_type'];

		return $input;
	}

	/**
	 * Check whether form exists.
	 *
	 * @param string $form_id
	 *
	 * @return bool
	 */
	public function form_exists( $form_id ) {
		return array_key_exists( $form_id, $this->source['forms'] );
	}

	/**
	 * Get form.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_form( $args ) {
		// invalid form?
		if ( ! $this->form_exists( $args['form_id'] ) )
			return [];

		$form_data = $this->source['forms'][$args['form_id']];

		return [
			'source'	=> $this->source['id'],
			'id'		=> $form_data['id'],
			'title'		=> $form_data['name'],
			'fields'	=> [
				'subject'		=> $form_data['subject'],
				'preferences'	=> $form_data['preferences']
			]
		];
	}

	/**
	 * Comment form.
	 *
	 * @return void
	 */
	public function comment_form() {
		$form_id = 'wp_comment_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
			// get form data
			$form_data = $this->get_form( [
				'form_id' => $form_id
			] );

			echo '
			<script>
			if ( typeof huOptions !== \'undefined\' ) {
				var huFormData = ' . wp_json_encode( $form_data ) . ';
				var huFormNode = document.querySelector( \'[id="commentform"]\' );

				huFormData[\'node\'] = huFormNode;
				huOptions[\'forms\'].push( huFormData );
			}
			</script>';
		}
	}

	/**
	 * Mark comment as valid.
	 *
	 * @param array $comment_data
	 *
	 * @return array
	 */
	public function comment_post( $comment_id, $comment_approved, $comment_data ) {
		if ( is_admin() )
			return;

		if ( ! $this->comment_form_active )
			return;

		if ( $this->comment_validation )
			$this->comment_passed_validation = true;
	}

	/**
	 * Comment validation start process.
	 *
	 * @return void
	 */
	public function comment_submission_start() {
		if ( is_admin() )
			return;

		$this->comment_form_active = Cookie_Notice()->privacy_consent->is_form_active( 'wp_comment_form', $this->source['id'] );

		if ( ! $this->comment_form_active )
			return;

		// adding comment?
		if ( basename( $_SERVER['PHP_SELF'] ) === 'wp-comments-post.php' ) {
			$this->comment_validation = true;
			$this->comment_passed_validation = false;

			Cookie_Notice()->privacy_consent->set_cookie( 'false' );
		}
	}

	/**
	 * Comment validation end process.
	 *
	 * @return void
	 */
	public function comment_submission_end() {
		if ( is_admin() )
			return;

		if ( ! $this->comment_form_active )
			return;

		// adding comment?
		if ( $this->comment_validation && $this->comment_passed_validation )
			Cookie_Notice()->privacy_consent->set_cookie( 'true' );
	}

	/**
	 * Registration form.
	 *
	 * @return void
	 */
	public function register_form() {
		$form_id = 'wp_registration_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
			// get form data
			$form_data = $this->get_form( [
				'form_id' => $form_id
			] );

			echo '
			<script>
			if ( typeof huOptions !== \'undefined\' ) {
				var huFormData = ' . wp_json_encode( $form_data ) . ';
				var huFormNode = document.querySelector( \'[id="registerform"]\' );

				huFormData[\'node\'] = huFormNode;
				huOptions[\'forms\'].push( huFormData );
			}
			</script>';
		}
	}

	/**
	 * Verify registration.
	 *
	 * @param object $errors
	 * @param string $user_login
	 * @param string $user_email
	 *
	 * @return object
	 */
	public function registration_errors( $errors, $user_login, $user_email ) {
		// get main instance
		$cn = Cookie_Notice();

		// active registration form?
		if ( $cn->privacy_consent->is_form_active( 'wp_registration_form', $this->source['id'] ) )
			$cn->privacy_consent->set_cookie( $errors->has_errors() ? 'false' : 'true' );

		return $errors;
	}
}

new Cookie_Notice_Modules_WordPress_Privacy_Consent();