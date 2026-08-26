<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules Easy Digital Downloads Privacy Consent class.
 *
 * Compatibility since: 3.0.0
 *
 * @class Cookie_Notice_Modules_EasyDigitalDownloads_Privacy_Consent
 */
class Cookie_Notice_Modules_EasyDigitalDownloads_Privacy_Consent {

	private $defaults = [
		'edd_registration_form'	=> [
			'status'	=> false
		],
		'edd_checkout_form'	=> [
			'status'	=> false
		]
	];
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
			'name'			=> __( 'Easy Digital Downloads', 'cookie-notice' ),
			'id'			=> 'easydigitaldownloads',
			'id_type'		=> 'string',
			'type'			=> 'static',
			'availability'	=> cn_is_plugin_active( 'easydigitaldownloads', 'privacy-consent' ),
			'status'		=> $cn->options['privacy_consent']['easydigitaldownloads_active'],
			'status_type'	=> $cn->options['privacy_consent']['easydigitaldownloads_active_type'],
			'forms'			=> [
				'edd_registration_form'	=> [
					'name'				=> __( 'Registration Form', 'cookie-notice' ),
					'id'				=> 'edd_registration_form',
					'type'				=> 'static',
					'mode'				=> 'automatic',
					'status'			=> false,
					'logged_out_only'	=> true,
					'fields'			=> [
						'username'	=> '',
						'email'		=> ''
					],
					'subject'			=> [
						'email'			=> '#edd-user-email'
					],
					'preferences'		=> [],
					'excluded'			=> []
				],
				'edd_checkout_form'		=> [
					'name'				=> __( 'Checkout Form', 'cookie-notice' ),
					'id'				=> 'edd_checkout_form',
					'type'				=> 'static',
					'mode'				=> 'automatic',
					'status'			=> true,
					'logged_out_only'	=> true,
					'fields'			=> [
						'email'			=> '',
						'first_name'	=> '',
						'last_name'		=> '',
						'country'		=> '',
						'address'		=> '',
						'city'			=> '',
						'postal_code'	=> '',
						'phone'			=> ''
					],
					'subject'			=> [
						'email'			=> '#edd-email',
						'first_name'	=> '#edd-first',
						'last_name'		=> '#edd-last'
					],
					'preferences'		=> [
						'terms'		=> '#edd_agree_to_terms',
						'privacy'	=> '#edd-agree-to-privacy-policy'
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

		// registration
		add_action( 'edd_register_form_fields_after', [ $this, 'registration_form_classic' ] );
		add_filter( 'render_block', [ $this, 'registration_form_blocks' ], 10, 2 );
		add_filter( 'edd_errors', [ $this, 'errors' ] );

		// checkout
		add_action( 'edd_checkout_form_bottom', [ $this, 'checkout_form_classic' ] );
		add_filter( 'render_block', [ $this, 'checkout_form_blocks' ], 10, 2 );
		add_action( 'edd_built_order', [ $this, 'checkout_new_order' ], 10, 2 );
	}

	/**
	 * Register source.
	 *
	 * @return void
	 */
	public function register_source() {
		register_setting(
			'cookie_notice_privacy_consent_easydigitaldownloads',
			'cookie_notice_privacy_consent_easydigitaldownloads',
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

		$input['easydigitaldownloads_active'] = isset( $input['easydigitaldownloads_active'] );
		$input['easydigitaldownloads_active_type'] = isset( $input['easydigitaldownloads_active_type'] ) && array_key_exists( $input['easydigitaldownloads_active_type'], $cn->privacy_consent->form_active_types ) ? $input['easydigitaldownloads_active_type'] : $cn->defaults['privacy_consent']['easydigitaldownloads_active_type'];

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
		$form = [
			'source'	=> $this->source['id'],
			'id'		=> $form_data['id'],
			'title'		=> $form_data['name'],
			'fields'	=> [
				'subject'		=> $form_data['subject'],
				'preferences'	=> $form_data['preferences']
			]
		];

		return $form;
	}

	/**
	 * Registration classic form.
	 *
	 * @return void
	 */
	public function registration_form_classic() {
		$form_id = 'edd_registration_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
			if ( $this->source['forms'][ $form_id ]['logged_out_only'] && is_user_logged_in() )
				return;

			// get form data
			$form_data = $this->get_form( [
				'form_id'	=> $form_id,
				'form_type'	=> 'classic'
			] );

			echo '
			<script>
			if ( typeof huOptions !== \'undefined\' ) {
				var huFormData = ' . wp_json_encode( $form_data ) . ';
				var huFormNode = document.getElementById( \'edd_register_form\' );';

			// edd 3.2.3+ uses different id for registration form in blocks
			if ( defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '3.2.3', '>=' ) ) {
				echo '
				if ( huFormNode === null )
					huFormNode = document.getElementById( \'edd-blocks-form__register\' );';
			}

			echo '
				huFormData[\'node\'] = huFormNode;
				huOptions[\'forms\'].push( huFormData );
			}
			</script>';
		}
	}

	/**
	 * Registration blocks form.
	 *
	 * @param string|mixed $block_content
	 * @param array $block
	 *
	 * @return string
	 */
	public function registration_form_blocks( $block_content, $block ) {
		// edd version 3.2.3+ has native support of edd_checkout_form_bottom in blocks
		if ( defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '3.2.3', '>=' ) )
			return $block_content;

		// not edd checkout block?
		if ( $block['blockName'] !== 'edd/register' )
			return $block_content;

		$form_id = 'edd_registration_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
			if ( $this->source['forms'][ $form_id ]['logged_out_only'] && is_user_logged_in() )
				return;

			// get form data
			$form_data = $this->get_form( [
				'form_id'	=> $form_id,
				'form_type'	=> 'blocks'
			] );

			$block_content = '
			<div class="wp-block" data-blockType="' . esc_attr( $block['blockName'] ) . '">
				' . $block_content . '
				<script>
				if ( typeof huOptions !== \'undefined\' ) {
					var huFormData = ' . wp_json_encode( $form_data ) . ';
					var huFormNode = document.getElementById( \'edd-blocks-form__register\' );

					huFormData[\'node\'] = huFormNode;
					huOptions[\'forms\'].push( huFormData );
				}
				</script>
			</div>';
		}

		return $block_content;
	}

	/**
	 * Registration errors.
	 *
	 * @param array $errors
	 *
	 * @return array
	 */
	public function errors( $errors ) {
		if ( isset( $_POST['edd_action'] ) && $_POST['edd_action'] === 'user_register' ) {
			// prevent headers already sent
			if ( headers_sent() )
				return $errors;

			// get main instance
			$cn = Cookie_Notice();

			// active registration form?
			if ( $cn->privacy_consent->is_form_active( 'edd_registration_form', $this->source['id'] ) )
				$cn->privacy_consent->set_cookie( empty( $errors ) ? 'true' : 'false' );
		}

		return $errors;
	}

	/**
	 * Checkout blocks form.
	 *
	 * @param string|mixed $block_content
	 * @param array $block
	 *
	 * @return string
	 */
	public function checkout_form_blocks( $block_content, $block ) {
		// edd version 3.6.0+ has native support of edd_checkout_form_bottom in blocks
		if ( defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '3.6.0', '>=' ) )
			return $block_content;

		// not edd checkout block?
		if ( $block['blockName'] !== 'edd/checkout' )
			return $block_content;

		$form_id = 'edd_checkout_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
			if ( $this->source['forms'][ $form_id ]['logged_out_only'] && is_user_logged_in() )
				return;

			// get form data
			$form_data = $this->get_form( [
				'form_id'	=> $form_id,
				'form_type'	=> 'blocks'
			] );

			$block_content = '
			<div class="wp-block" data-blockType="' . esc_attr( $block['blockName'] ) . '">
				' . $block_content . '
				' . $this->checkout_form_script( $form_data ) . '
			</div>';
		}

		return $block_content;
	}

	/**
	 * Checkout classic form.
	 *
	 * @return void
	 */
	public function checkout_form_classic() {
		$form_id = 'edd_checkout_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
			if ( $this->source['forms'][ $form_id ]['logged_out_only'] && is_user_logged_in() )
				return;

			// get form data
			$form_data = $this->get_form( [
				'form_id'	=> $form_id,
				'form_type'	=> 'classic'
			] );

			echo $this->checkout_form_script( $form_data );
		}
	}

	/**
	 * Add form script.
	 *
	 * @param array $form_data
	 *
	 * @return string
	 */
	public function checkout_form_script( $form_data ) {
		return '<script>
		if ( typeof huOptions !== \'undefined\' ) {
			var huFormData = ' . wp_json_encode( $form_data ) . ';
			var huFormNode = document.getElementById( \'edd_purchase_form\' );

			huFormData[\'node\'] = huFormNode;
			huOptions[\'forms\'].push( huFormData );
		}
		</script>';
	}

	/**
	 * Checkout new order.
	 *
	 * @param int $order_id
	 * @param array $order_data
	 *
	 * @return void
	 */
	public function checkout_new_order( $order_id, $order_data ) {
		// get main instance
		$cn = Cookie_Notice();

		// active checkout form?
		if ( $cn->privacy_consent->is_form_active( 'edd_checkout_form', $this->source['id'] ) ) {
			if ( ! empty( $order_data['user_email'] ) || ! empty( $order_data['user_info']['email'] ) )
				$cn->privacy_consent->set_cookie( 'true' );
			else
				$cn->privacy_consent->set_cookie( 'false' );
		}
	}
}

new Cookie_Notice_Modules_EasyDigitalDownloads_Privacy_Consent();