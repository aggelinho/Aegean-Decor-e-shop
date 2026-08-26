<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cookie Notice Modules WooCommerce Privacy Consent class.
 *
 * Compatibility since: 4.0.4
 *
 * @class Cookie_Notice_Modules_WooCommerce_Privacy_Consent
 */
class Cookie_Notice_Modules_WooCommerce_Privacy_Consent {

	private $defaults = [
		'wc_registration_form'	=> [
			'status'	=> false
		],
		'wc_checkout_form'	=> [
			'status'	=> false
		]
	];
	private $source = [];
	private $registration_started = false;
	private $registration_ended = false;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// get main instance
		$cn = Cookie_Notice();

		$this->source = [
			'name'			=> __( 'WooCommerce', 'cookie-notice' ),
			'id'			=> 'woocommerce',
			'id_type'		=> 'string',
			'type'			=> 'static',
			'availability'	=> cn_is_plugin_active( 'woocommerce', 'privacy-consent' ),
			'status'		=> $cn->options['privacy_consent']['woocommerce_active'],
			'status_type'	=> $cn->options['privacy_consent']['woocommerce_active_type'],
			'forms'			=> [
				'wc_registration_form'	=> [
					'name'				=> __( 'Registration Form', 'cookie-notice' ),
					'id'				=> 'wc_registration_form',
					'type'				=> 'static',
					'mode'				=> 'automatic',
					'status'			=> false,
					'logged_out_only'	=> true,
					'fields'			=> [
						'email'		=> ''
					],
					'subject'			=> [
						'email'			=> 'email'
					],
					'preferences'		=> [],
					'excluded'			=> []
				],
				'wc_checkout_form'		=> [
					'name'				=> __( 'Checkout Form', 'cookie-notice' ),
					'id'				=> 'wc_checkout_form',
					'type'				=> 'static',
					'mode'				=> 'automatic',
					'status'			=> true,
					'logged_out_only'	=> true,
					'fields'			=> [
						'email'			=> '',
						'country'		=> '',
						'first_name'	=> '',
						'last_name'		=> '',
						'address_1'		=> '',
						'address_2'		=> '',
						'postal_code'	=> '',
						'city'			=> '',
						'phone'			=> '',
						'note'			=> ''
					],
					'subject'			=> [
						'email'			=> '#email',
						'first_name'	=> '#shipping-first_name',
						'last_name'		=> '#shipping-last_name'
					],
					'preferences'		=> [
						'terms'	=> '#terms-and-conditions'
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
		add_action( 'woocommerce_register_form', [ $this, 'register_form' ] );
		add_action( 'wp_loaded', [ $this, 'registration_end' ], 21 );
		add_filter( 'woocommerce_process_registration_errors', [ $this, 'registration_start' ], PHP_INT_MAX );
		add_filter( 'woocommerce_registration_auth_new_customer', [ $this, 'registration_auth_new_customer' ], PHP_INT_MAX );

		// checkout
		add_action( 'woocommerce_new_order', [ $this, 'checkout_new_order' ], 10, 2 );
		add_action( 'woocommerce_checkout_after_order_review', [ $this, 'checkout_form_classic' ] );
		add_filter( 'render_block', [ $this, 'checkout_form_blocks' ], 10, 2 );
	}

	/**
	 * Register source.
	 *
	 * @return void
	 */
	public function register_source() {
		register_setting(
			'cookie_notice_privacy_consent_woocommerce',
			'cookie_notice_privacy_consent_woocommerce',
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

		$input['woocommerce_active'] = isset( $input['woocommerce_active'] );
		$input['woocommerce_active_type'] = isset( $input['woocommerce_active_type'] ) && array_key_exists( $input['woocommerce_active_type'], $cn->privacy_consent->form_active_types ) ? $input['woocommerce_active_type'] : $cn->defaults['privacy_consent']['woocommerce_active_type'];


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

		if ( $args['form_id'] === 'wc_checkout_form' ) {
			if ( $args['form_type'] === 'blocks' ) {
				// force preferences
				$form['preferences']['privacy'] = true;
			} elseif ( $args['form_type'] === 'classic' ) {
				// set default input names
				$form['fields']['subject']['email'] = 'billing_email';
				$form['fields']['subject']['first_name'] = 'billing_first_name';
				$form['fields']['subject']['last_name'] = 'billing_last_name';

				// add terms and conditions input name if needed
				if ( wc_terms_and_conditions_checkbox_enabled() )
					$form['fields']['preferences']['terms'] = 'terms';
				else
					unset( $form['fields']['preferences']['terms'] );

				// force privacy policy
				if ( wc_privacy_policy_page_id() && wc_get_privacy_policy_text( 'checkout' ) !== '' )
					$form['preferences']['privacy'] = true;
			}
		} elseif ( $args['form_id'] === 'wc_registration_form' ) {
			// force privacy policy
			if ( wc_privacy_policy_page_id() && wc_get_privacy_policy_text( 'registration' ) !== '' )
				$form['preferences']['privacy'] = true;
		}

		return $form;
	}

	/**
	 * Registration form.
	 *
	 * @return void
	 */
	public function register_form() {
		$form_id = 'wc_registration_form';

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
				var huFormNode = document.querySelector( \'.woocommerce-form-register\' );

				huFormData[\'node\'] = huFormNode;
				huOptions[\'forms\'].push( huFormData );
			}
			</script>';
		}
	}

	/**
	 * Registration start point.
	 *
	 * @param object $error
	 *
	 * @return object
	 */
	public function registration_start( $error ) {
		if ( Cookie_Notice()->privacy_consent->is_form_active( 'wc_registration_form', $this->source['id'] ) ) {
			// begin registration process
			$this->registration_started = true;
		}

		return $error;
	}

	/**
	 * Registration end point #1.
	 *
	 * @param bool $force_login
	 *
	 * @return bool
	 */
	public function registration_auth_new_customer( $force_login ) {
		if ( $this->registration_started ) {
			$this->registration_ended = true;

			Cookie_Notice()->privacy_consent->set_cookie( 'true' );
		}

		// if $force_login is true registration_end() will not be executed
		return $force_login;
	}

	/**
	 * Registration end point #2.
	 *
	 * @return void
	 */
	public function registration_end() {
		if ( $this->registration_started && ! $this->registration_ended )
			Cookie_Notice()->privacy_consent->set_cookie( 'false' );
	}

	/**
	 * Checkout form.
	 *
	 * @return void
	 */
	public function checkout_form_classic() {
		$form_id = 'wc_checkout_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
			// get form data
			$form_data = $this->get_form( [
				'form_id'	=> $form_id,
				'form_type'	=> 'classic'
			] );

			echo '
			<script>
			if ( typeof huOptions !== \'undefined\' ) {
				jQuery( document.body ).on( \'init_checkout\', function() {
					var huFormData = ' . wp_json_encode( $form_data ) . ';
					var huFormNode = document.querySelector( \'form.woocommerce-checkout\' );

					huFormData[\'node\'] = huFormNode;
					huOptions[\'forms\'].push( huFormData );
				} );
			}
			</script>';
		}
	}

	/**
	 * Add captcha to the checkout block.
	 *
	 * @param string|mixed $block_content
	 * @param array $block
	 *
	 * @return string
	 */
	public function checkout_form_blocks( $block_content, $block ) {
		$block_content = (string) $block_content;

		if ( $block['blockName'] !== 'woocommerce/checkout' )
			return $block_content;

		$form_id = 'wc_checkout_form';

		// active form?
		if ( Cookie_Notice()->privacy_consent->is_form_active( $form_id, $this->source['id'] ) ) {
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
					var huFormNode = null;

					// set empty node
					huFormData[\'node\'] = huFormNode;

					huOptions[\'forms\'].push( huFormData );
				}
				</script>
			</div>';
		}

		return $block_content;
	}

	/**
	 * Checkout new order.
	 *
	 * @param int $order_id
	 * @param object $order
	 *
	 * @return void
	 */
	public function checkout_new_order( $order_id, $order ) {
		// skip new admin area orders
		if ( isset( $_REQUEST[ '_wpnonce' ] ) && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'update-order_' . $order_id ) )
			return;

		// get main instance
		$cn = Cookie_Notice();

		// is checkout enabled?
		if ( $cn->privacy_consent->is_form_active( 'wc_checkout_form', $this->source['id'] ) ) {
			if ( $order->get_billing_email() !== '' )
				$cn->privacy_consent->set_cookie( 'true' );
		}
	}
}

new Cookie_Notice_Modules_WooCommerce_Privacy_Consent();