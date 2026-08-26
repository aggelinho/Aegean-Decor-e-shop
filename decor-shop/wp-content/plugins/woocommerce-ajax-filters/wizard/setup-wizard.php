<?php
if( ! class_exists('BeRocket_Setup_Wizard_v2') ) {
    class BeRocket_Setup_Wizard_v2 {
        public $step   = '';
        public $steps  = array();
        public $page_id = '';
        public $options = '';
        public function __construct($page_id='', $options = array()) {
            if( ! $page_id ) {
                $page_id = 'berocket-setup';
            }
            $this->options = array_merge(array(
                'title' => __( 'Setup Wizard', 'BeRocket_domain' )
            ), $options);
            $this->page_id = $page_id;
            if ( ! empty( $_GET['page'] ) && $_GET['page'] == $this->page_id ) {
                $this->steps = apply_filters('berocket_wizard_steps_'.$this->page_id,
                    array(
                        'start' => array(
                            'name'    => __( 'Start', 'BeRocket_domain' ),
                            'view'    => array( $this, 'start_step' ),
                            'handler' => array( $this, 'start_step_save' ),
                            'fa_icon' => 'fa-cog',
                        ),
                        'end' => array(
                            'name'    => __( 'End', 'BeRocket_domain' ),
                            'view'    => array( $this, 'end_step' ),
                            'handler' => '',
                            'fa_icon' => 'fa-cog',
                        ),
                    )
                );
                add_action( 'admin_menu', array( $this, 'admin_menus' ) );
                add_action( 'admin_init', array( $this, 'setup_wizard' ), 100 );
            }
        }
        public function admin_menus() {
            add_dashboard_page( '', '', 'manage_woocommerce', $this->page_id, '' );
        }
        public function setup_wizard() {
            if ( empty( $_GET['page'] ) || $this->page_id !== $_GET['page'] ) {
                return;
            }
            do_action('before_wizard_run_'.$this->page_id, $this);
            $this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

            if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
                call_user_func( $this->steps[ $this->step ]['handler'], $this );
            }
            wp_admin_css();
            BeRocket_AAPF::wp_enqueue_style( 'wizard-setup' );
            BeRocket_Framework::register_font_awesome('fa5');
            wp_enqueue_style( 'font-awesome-5' );

            $this->remove_deprecated();

            ob_start();
            $this->setup_wizard_header();
            $this->setup_wizard_content();
            $this->setup_wizard_footer();
            exit;
        }
        public function get_next_step_link( $step = '' ) {
            if ( ! $step ) {
                $step = $this->step;
            }
            $keys = array_keys( $this->steps );
            if ( end( $keys ) === $step ) {
                return admin_url();
            }
            $step_index = array_search( $step, $keys );
            if ( false === $step_index ) {
                return '';
            }
            return esc_url_raw(add_query_arg(
                    'step',
                    $keys[ $step_index + 1 ],
                    remove_query_arg( 'activate_error' ) ) );
        }
        public function redirect_to_next_step() {
            wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
        }

        /**
         * Setup Wizard Header.
         */
        public function setup_wizard_header() {
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title><?php esc_html_e( 'BeRocket &rsaquo; Setup Wizard', 'BeRocket_domain' ); ?></title>
                <?php wp_head(); ?>
                <script>
                var brwiz_admin_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
                </script>
            </head>
            <body class="wp-admin wp-core-ui js">
                <div class="br_framework_settings br_setup_wizard">
                    <div class="body">
            <?php
        }

        /**
         * Setup Wizard Footer.
         */
        public function setup_wizard_footer() {
            ?>
                        <div class="br_setup_wizard_bottom_links"><?php
                            if ( $this->step != 'wizard_end' ) {
                                ?>
                            <a class="wc-return-to-dashboard" href="<?php echo esc_url( $this->get_next_step_link() ); ?>"><?php
                                esc_html_e( 'Skip this step', 'BeRocket_domain' ); ?></a>
                             |
                                <?php
                            }
                            ?><a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php
		                        esc_html_e( 'Return to your dashboard', 'BeRocket_domain' ); ?></a>
                        </div>
                    </div>
                </div>
                <div style="display:none!important;">
                <?php 
                wp_footer();
                ?>
                </div>
                </body>
            </html>
            <?php
        }

        /**
         * Output the steps.
         */
        public function setup_wizard_steps() {
            $output_steps = $this->steps;
            ?>
            <ul class="side">
                <?php foreach ( $output_steps as $step_key => $step ) : ?>
                    <li><a class="<?php
                        if ( $step_key === $this->step ) {
                            echo 'active';
                        } elseif ( array_search( $this->step, array_keys( $this->steps ) ) < array_search( $step_key, array_keys( $this->steps ) ) ) {
                            echo 'close';
                        }
                    ?>" href="<?=esc_url_raw(add_query_arg( 'step', $step_key, remove_query_arg( 'activate_error' ) )); ?>"><?php if( ! empty($step['fa_icon']) ) echo '<span class="fa '.$step['fa_icon'].'"></span>'; ?><?php echo esc_html( $step['name'] ); ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php
        }
        public function setup_wizard_content() {
            echo '<div class="header">';
                echo '<div class="logo-image"><img src="https://e8e3g4v6.delivery.rocketcdn.me/wp-content/uploads/2024/10/logo-with-text-1028x304-1.png" alt="BeRocket logo" /></div>';
	            $this->setup_wizard_steps();
	        echo '</div>';

            echo '<div class="content ' . $this->step . '">';
                call_user_func( $this->steps[ $this->step ]['view'], $this, $this->step, $this->steps );
            echo '</div>';
        }
        public function start_step() {
            ?>
            <form method="post" class="br_framework_submit_form">
                <div class="nav-block berocket_framework_menu_general-block nav-block-active">
                </div>
                <?php wp_nonce_field( 'br-ajax-filters-setup' ); ?>
                <p class="wc-setup-actions step">
                    <input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( "Let's go!", 'BeRocket_domain' ); ?>" name="save_step" />
                </p>
            </form>
            <?php
        }
        public function start_step_save() {
            check_admin_referer( 'br-ajax-filters-setup' );
            wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
            exit;
        }
        public function end_step() {
        }
        function remove_deprecated() {
            $has_emoji_styles = has_action( 'wp_print_styles', 'print_emoji_styles' );
            if ( $has_emoji_styles ) {
                remove_action( 'wp_print_styles', 'print_emoji_styles' );
            }
            $has_emoji_styles = has_action( 'wp_head', 'wp_admin_bar_header' );
            if ( $has_emoji_styles ) {
                remove_action( 'wp_head', 'wp_admin_bar_header' );
            }
        }
    }
    function berocket_add_setup_wizard_v2($page_id, $options = array()) {
        new BeRocket_Setup_Wizard_v2($page_id, $options);
    }
}

if ( ! function_exists( 'BeRocket_Setup_Wizard_get_prev_step_link' ) ) {
	function BeRocket_Setup_Wizard_get_prev_step_link( $step = '' ) {
        $steps = (new BeRocket_Setup_Wizard_v2('br-aapf-setup'))->steps;
		if ( ! $step ) {
			$step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $steps ) );
		}

		$keys = array_keys( $steps );
		if ( reset( $keys ) === $step ) {
			return '#';
		}

		$step_index = array_search( $step, $keys );
		if ( false === $step_index ) {
			return '#';
		}

		return esc_url_raw( add_query_arg(
			'step',
			$keys[ $step_index - 1 ],
			remove_query_arg( 'activate_error' ) ) );
	}
}