<?php
class BeRocket_AAPF_Wizard {
	function __construct() {
        add_action('wp_loaded', array($this, 'init_wizard'));
        add_action('admin_init', array($this, 'wp_redirect'), 100);
        add_filter('brfr_header_links_ajax_filters', array($this, 'admin_link'));
        add_action('switch_theme', array($this, 'switch_theme'), 10, 2);
	}

    public function switch_theme($new_name, $new_theme) {
        include_once(__DIR__ . "/admin_settings/functions.php");
        $selectors = bapf_settings_get_selectors_preset($new_theme);
        foreach($selectors as $selector_name => $selector) {
            $option = BeRocket_AAPF::get_aapf_option();
            if( ! empty($option['selectors_preset']) ) {
                $option['selectors_preset'] = $selector_name;
                foreach($selector['options'] as $option_name => $option_val) {
                    if( is_array($option_val) ) {
                        $option[$option_name] = array_merge($option[$option_name], $option_val);
                    } else {
                        $option[$option_name] = $option_val;
                    }
                }
                update_option( 'br_filters_options', $option );
                do_action('bapf_update_selectors_preset', $selector_name, $selector, $option);
            }
            break;
        }
    }

    public function admin_link($header_links) {
        $header_links['wizard'] = array(
            'text' => '<i class="fa fa-magic"></i>',
            'link' => admin_url( 'admin.php?page=br-aapf-setup' )
        );
        return $header_links;
    }

    public function init_wizard() {
        if( current_user_can( 'manage_berocket_aapf' ) ) {
            require_once dirname( __FILE__ ) . '/../wizard/setup-wizard.php';
            if( function_exists('berocket_add_setup_wizard_v2') ) {
                add_filter( 'berocket_wizard_steps_br-aapf-setup', array( $this, 'setup_wizard_steps' ) );
                add_action( 'before_wizard_run_br-aapf-setup', array( $this, 'set_wizard_js_css' ) );
                berocket_add_setup_wizard_v2( 'br-aapf-setup', array( 'title' => __( 'AJAX Product Filters Setup Wizard', 'BeRocket_AJAX_domain' ) ) );
                
                add_action('wp_ajax_brapf_wizard_install_plugin', array($this, 'wizard_install_single_plugin'));
                add_action('wp_ajax_brapf_wizard_create_filter', array($this, 'wizard_create_single_filter'));
            }
        }
    }
    public function wp_redirect() {
        if( current_user_can( 'manage_options' ) ) {
            $redirect_to_wizard = get_option('berocket_filter_open_wizard_on_settings');
            if( ! empty($redirect_to_wizard) ) {
                delete_option('berocket_filter_open_wizard_on_settings');
                wp_redirect(admin_url( 'admin.php?page=br-aapf-setup' ));
            }
        }
    }

	public function set_wizard_js_css() {
		wp_enqueue_script( 'common' );
		do_action( 'BeRocket_wizard_javascript', array() );
	}

	public function setup_wizard_steps( $steps ) {
		$steps = array(
			'wizard_selectors'    => array(
				'name'    => __( 'Selectors', 'BeRocket_AJAX_domain' ),
				'view'    => array( $this, 'wizard_selectors' ),
				'handler' => array( $this, 'wizard_selectors_save' ),
				'fa_icon' => 'fa-circle-o',
			),
			'wizard_addons'        => array(
				'name'    => __( 'Add-ons', 'BeRocket_AJAX_domain' ),
				'view'    => array( $this, 'wizard_addons' ),
				'handler' => array( $this, 'wizard_addons_save' ),
				'fa_icon' => 'fa-cogs',
			),
			'wizard_plugins_install'        => array(
				'name'    => __( 'Plugins', 'BeRocket_AJAX_domain' ),
				'view'    => array( $this, 'wizard_plugins_install' ),
				'handler' => array( $this, 'wizard_plugins_install_save' ),
				'fa_icon' => 'fa-solid fa-puzzle-piece',
			),
			'wizard_filters_create'        => array(
				'name'    => __( 'Filters', 'BeRocket_AJAX_domain' ),
				'view'    => array( $this, 'wizard_filters_create' ),
				'handler' => array( $this, 'wizard_filters_create_save' ),
				'fa_icon' => 'fa-solid fa-filter',
			),
			'wizard_end'          => array(
				'name'    => __( 'Ready', 'BeRocket_AJAX_domain' ),
				'view'    => array( $this, 'wizard_ready' ),
				'handler' => array( $this, 'wizard_ready_save' ),
				'fa_icon' => 'fa-check',
			),
		);

		return $steps;
	}

	public function wizard_selectors( $wizard ) {
		$option = BeRocket_AAPF::get_aapf_option();
		?>
        <form method="post" class="br_framework_submit_form">
            <div class="nav-block berocket_framework_menu_general-block nav-block-active">
                <div>
                    <p><?php _e( 'It\'s crucial to note that selectors can differ per theme. The AJAX feature, 
                    which is turned on by default, requires the correct selectors.', 'BeRocket_AJAX_domain' ) ?></p>
                    <p><?php _e( 'If your theme uses Isotope/Masonry or any type of image Lazy-Load, it will 
                    require custom Javascript. To obtain the correct Javascript code, contact your theme author. 
                    This code can be added to the After Update field.', 'BeRocket_AJAX_domain' ) ?></p>
                </div>
                <table class="framework-form-table berocket_framework_menu_selectors">
                    <tbody>
                    <tr style="display: table-row;">
                        <th scope="row"><?php _e( 'Get selectors automatically', 'BeRocket_AJAX_domain' ) ?></th>
                        <td>
                            
							<?php
                            $popup_html = '<div>
                                <h4 style="margin-top: 0;">' . 
                                    __( 'How it works', 'BeRocket_AJAX_domain' ) . '
                                </h4>
                                <div>
                                    <ol>
                                        <li>' . __( '- Run Auto-selector', 'BeRocket_AJAX_domain' ) . '</li>
                                        <li>' . __( '- Wait until the end. <strong style="color:red;">Do not close this page</strong>', 'BeRocket_AJAX_domain' ) . '</li>
                                        <li>' . __( '- Save settings with new selectors', 'BeRocket_AJAX_domain' ) . '</li>
                                    </ol>
                                </div>
                            </div>
                            <p>
                                <span style="color: red;">*</span> ' .
                                __( 'Also, you may manually check for selectors or contact the theme author. 
                                Selectors can be edited on the plugin settings page.',
			                        'BeRocket_AJAX_domain' ) . '
                            </p>
                            <p>
                                <strong>' . __( 'IMPORTANT: It will generate several products on your site. 
                                    Please turn off all SEO plugins and plugins that affect product creation.',
                                    'BeRocket_AJAX_domain' ) . '</strong>
                            </p>';
                            
							$output_text = array(
								'important'            => '',
								'was_runned'           => __( 'The script was run, but the page was closed until the end. Please stop it to prevent any problems on your site.', 'BeRocket_AJAX_domain' ),
								'run_button'           => __( 'Auto-Selectors', 'BeRocket_AJAX_domain' ),
								'was_runned_stop'      => __( 'Stop', 'BeRocket_AJAX_domain' ),
								'steps'                => __( 'Steps:', 'BeRocket_AJAX_domain' ),
								'step_create_products' => __( 'Creating products ', 'BeRocket_AJAX_domain' ),
								'step_get_selectors'   => __( 'Retrieving selectors ', 'BeRocket_AJAX_domain' ),
								'step_remove_product'  => __( 'Removing products ', 'BeRocket_AJAX_domain' ),
                                'popup_before_run'     => $popup_html
							);
							echo BeRocket_wizard_generate_autoselectors_v2( array(
								'products'     => '.berocket_aapf_products_selector',
								'pagination'   => '.berocket_aapf_pagination_selector',
								'result_count' => '.berocket_aapf_product_count_selector'
							), array(), $output_text ); ?>
                        </td>
                    </tr>
                    <tr style="display: none;">
                        <th scope="row"><?php _e( 'Products Container Selector', 'BeRocket_AJAX_domain' ); ?></th>
                        <td><label>
                                <input type="text" name="berocket_aapf_wizard_settings[products_holder_id]"
                                       value="<?php if ( ! empty( $option['products_holder_id'] ) ) {
									       echo $option['products_holder_id'];
								       } ?>"
                                       class="berocket_aapf_products_selector">
                            </label></td>
                    </tr>
                    <tr style="display: none;">
                        <th scope="row"><?php _e( 'Pagination Selector', 'BeRocket_AJAX_domain' ); ?></th>
                        <td><label>
                                <input type="text" name="berocket_aapf_wizard_settings[woocommerce_pagination_class]"
                                       value="<?php if ( ! empty( $option['woocommerce_pagination_class'] ) ) {
									       echo $option['woocommerce_pagination_class'];
								       } ?>"
                                       class="berocket_aapf_pagination_selector">
                            </label></td>
                    </tr>
                    <tr style="display: none;">
                        <th scope="row"><?php _e( 'Product count selector', 'BeRocket_AJAX_domain' ) ?></th>
                        <td><label>
                                <input type="text" name="berocket_aapf_wizard_settings[woocommerce_result_count_class]"
                                       value="<?php if ( ! empty( $option['woocommerce_result_count_class'] ) ) {
									       echo $option['woocommerce_result_count_class'];
								       } ?>"
                                       class="berocket_aapf_product_count_selector">
                            </label></td>
                    </tr>
                    <tr style="display: none;">
                        <th scope="row"><?php _e( 'Product order by selector', 'BeRocket_AJAX_domain' ) ?></th>
                        <td><label>
                                <input type="text" name="berocket_aapf_wizard_settings[woocommerce_ordering_class]"
                                       value="<?php if ( ! empty( $option['woocommerce_ordering_class'] ) ) {
									       echo $option['woocommerce_ordering_class'];
								       } ?>"
                                       class="">
                            </label></td>
                    </tr>
                    <tr style="display: table-row;">
                        <th scope="row"><?php _e( 'Nice URLs', 'BeRocket_AJAX_domain' ) ?></th>
                        <td>
                            <div class="button_container">
                                <input class="berocket_wizard_seo_friendly"
                                       name="berocket_aapf_wizard_settings[url_change]" type="checkbox"
                                       value="1"<?php if ( ! empty( $option['seo_friendly_urls'] ) ) { echo " checked"; } ?> />
                                <label>
                                <?php _e( 'Page URL will be changed on filtration.', 'BeRocket_AJAX_domain' ) ?>
                                </label>
                            </div>
                            <?php
		                    echo '<div class="settings-grey-description">';
                                _e( 'Example URL:', 'BeRocket_AJAX_domain');
                                $plugin_version_capability = apply_filters( 'brfr_get_plugin_version_capability_ajax_filters', 0 );
                                if ( empty( $plugin_version_capability ) || $plugin_version_capability < 10 ) {
                                    echo ' https://example.site/shop/?filters=color[green]|category[decor]';
                                } else {
                                    echo ' https://example.site/shop/filters/color/green/category/decor/';
                                }
                            echo '</div>';
                            ?> 
                        </td>
                    </tr>
                    <tr style="display: table-row;">
                        <th scope="row"><?php _e( 'Scroll page to the top', 'BeRocket_AJAX_domain' ) ?></th>
                        <td>
                            <div class="button_container">
                                <input name="berocket_aapf_wizard_settings[scroll_shop_top]" type="checkbox" value="1"
									<?php if ( ! empty( $option['scroll_shop_top'] ) ) { echo " checked"; } ?> />
                                <label>
								    <?php _e( 'Check if you want scroll page to the top of shop after filters change', 'BeRocket_AJAX_domain' ) ?>
                                </label>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
			<?php wp_nonce_field( $wizard->page_id ); ?>
            <p class="next-step">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e( "Next Step", 'BeRocket_AJAX_domain' ); ?>" name="save_step"/>
            </p>
        </form>
		<?php
	}

    public function options_that_can_be_saved() {
        return array(
            'products_holder_id' => array(
                'type' => 'text',
            ),
            'woocommerce_pagination_class' => array(
                'type' => 'text',
            ),
            'woocommerce_result_count_class' => array(
                'type' => 'text',
            ),
            'woocommerce_ordering_class' => array(
                'type' => 'text',
            ),
            'seo_friendly_urls' => array(
                'type' => 'checkbox',
            ),
            'slug_urls' => array(
                'type' => 'checkbox',
            ),
            'nice_urls' => array(
                'type' => 'checkbox',
            ),
            'recount_hide' => array(
                'type' => 'select',
                'values' => array(
                    'disable',
                    'recount',
                    'removeRecount',
                )
            ),
            'scroll_shop_top' => array(
                'type' => 'checkbox',
            ),
        );
    }

    public function sanitize_options($options) {
        $sanitized_options = array();
        $options_data = $this->options_that_can_be_saved();
        if( is_array($options) ) {
            foreach($options as $name => $option) {
                if( isset($options_data[$name]) ) {
                    switch($options_data[$name]['type']) {
                        case 'checkbox':
                            $option = empty($option) ? '0' : '1';
                            break;
                        case 'select':
                            $option = sanitize_text_field($option);
                            if( ! in_array($option, $options_data[$name]['values']) ) {
                                $option = $options_data[$name]['values'][0];
                            }
                            break;
                        default:
                            $option = sanitize_text_field($option);
                    }
                    $sanitized_options[$name] = $option;
                }
            }
        }
        return $sanitized_options;
    }

	public function wizard_selectors_save( $wizard ) {
		if( ! check_admin_referer( $wizard->page_id ) ) {
            $same_link = esc_url_raw(add_query_arg(
                'step',
                $this->step,
                remove_query_arg( 'activate_error' ) ) );
            wp_redirect($same_link);
        }
		$option = BeRocket_AAPF::get_aapf_option();
        $new_option = array();
		if ( ! empty( $_POST['berocket_aapf_wizard_settings'] ) && is_array( $_POST['berocket_aapf_wizard_settings'] ) ) {
            $options_checkbox = array(
                'url_change',
                'scroll_shop_top',
            );
            $new_option = $this->sanitize_options($_POST['berocket_aapf_wizard_settings']);
            foreach($options_checkbox as $option_checkbox) {
                if( ! isset($new_option[$option_checkbox]) ) {
                    $new_option[$option_checkbox] = '';
                }
            }
            $plugin_version_capability = apply_filters( 'brfr_get_plugin_version_capability_ajax_filters', 0 );
            if( ! empty($_POST['berocket_aapf_wizard_settings']['url_change']) ) {
                $new_option['seo_friendly_urls'] = '1';
                $new_option['slug_urls'] = '1';
                
                if ( ! empty( $plugin_version_capability ) && $plugin_version_capability >= 10 ) {
                    $new_option['nice_urls'] = '1';
                }
            } else {
                $new_option['seo_friendly_urls'] = '';
                $new_option['slug_urls'] = '';
                
                if ( ! empty( $plugin_version_capability ) && $plugin_version_capability >= 10 ) {
                    $new_option['nice_urls'] = '';
                }
            }
			$new_option = array_merge( array(
                'woocommerce_removes' => array(
                    'pagination'   => '',
                    'result_count' => '',
                    'ordering'     => ''
                ),
                'recount_hide'      => 'removeRecount',
                'seo_friendly_urls' => '',
                'slug_urls'         => '',
                'nice_urls'         => ''
            ), $new_option );
            if ( empty( $new_option['seo_friendly_urls'] ) ) {
                $new_option['slug_urls'] = '';
                $new_option['nice_urls'] = '';
            }
			$option     = array_merge( $option, $new_option );
		}
		$BeRocket_AAPF = BeRocket_AAPF::getInstance();
		$option        = $BeRocket_AAPF->sanitize_option( $option );
		update_option( 'br_filters_options', $option );
        wp_cache_delete('br_filters_options', 'berocket_framework_option');

        do_action('brapf_wizard_selectors_save', $option);
        
		$wizard->redirect_to_next_step();
	}


	public function wizard_addons( $wizard ) {
		$options = BeRocket_AAPF::get_aapf_option();
		?>
        <form method="post" class="br_framework_submit_form">
            <div class="nav-block berocket_framework_menu_general-block nav-block-active">
                <table class="framework-form-table berocket_framework_menu_selectors">
                    <tbody>
                    <tr style="display: table-row;">
                    <?php 
                    $BeRocket_AAPF = BeRocket_AAPF::getInstance();
                    add_filter('brfr_addonslib_html_elements', array($this, 'addonslib_html_elements'), 10, 2);
                    $section = apply_filters('brfr_' . $BeRocket_AAPF->info[ 'plugin_name' ] . '_addons', '', array(), $options, 'berocket_aapf_wizard_settings');
                    echo $section;
                    ?>
                    </tr>
                    </tbody>
                </table>
            </div>
			<?php wp_nonce_field( $wizard->page_id ); ?>
            <p class="next-step">
                <a class="button-primary button button-large button-next" href="<?php echo esc_url( BeRocket_Setup_Wizard_get_prev_step_link() ); ?>"><?php
		            esc_attr_e( "Previous Step", 'BeRocket_AJAX_domain' ); ?></a>
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e( "Next Step", 'BeRocket_AJAX_domain' ); ?>" name="save_step"/>
            </p>
        </form>
		<?php
	}

    public function addonslib_addons_to_dispaly() {
        $separator = DIRECTORY_SEPARATOR;
        $addons_to_dispaly = array(
            "{$separator}custom_postmeta{$separator}custom_postmeta.php",
            "{$separator}custom_slug{$separator}custom_slug.php",
            "{$separator}filtering_conditions{$separator}filtering_conditions.php",
            "{$separator}custom_sidebar{$separator}custom_sidebar.php",
            "{$separator}custom_search{$separator}custom_search.php",
            "{$separator}add_classes{$separator}add_classes.php",
        );
        return $addons_to_dispaly;
    }

    public function addonslib_html_elements($elements, $addons) {
        $new_elements = array(
            'all'    => array(
                'title' => '',
                'html'  => array()
            )
        );
        $addons_to_dispaly = $this->addonslib_addons_to_dispaly();
        $new_elements['all']['html'] = array_fill_keys($addons_to_dispaly, '');
        foreach( $elements as $element ) {
            foreach( $element['html'] as $addon => $html ) {
                if( in_array($addon, $addons_to_dispaly) ) {
                    $new_elements['all']['html'][$addon] = $html;
                }
            }
        }
        return $new_elements;
    }

	public function wizard_addons_save( $wizard ) {
		if( ! check_admin_referer( $wizard->page_id ) ) {
            $same_link = esc_url_raw(add_query_arg(
                'step',
                $this->step,
                remove_query_arg( 'activate_error' ) ) );
            wp_redirect($same_link);
        }
		$option = BeRocket_AAPF::get_aapf_option();
        $new_option = array();
		$BeRocket_AAPF = BeRocket_AAPF::getInstance();
		if ( ! empty( $_POST['berocket_aapf_wizard_settings'] ) && is_array( $_POST['berocket_aapf_wizard_settings'] )
            && isset($_POST['berocket_aapf_wizard_settings']['addons']) && is_array($_POST['berocket_aapf_wizard_settings']['addons']) ) {
            $addons_exist = $BeRocket_AAPF->libraries->libraries_class['addons']->get_addons_info();
            $addons_files = array();
            foreach($addons_exist as $addon_exist) {
                $addons_files[] = $addon_exist['addon_file'];
            }
            $addons = array();
            foreach($_POST['berocket_aapf_wizard_settings']['addons'] as $addon) {
                if( is_string($addon) ) {
                    if( in_array($addon, $addons_files) ) {
                        $addons[] = $addon;
                        continue;
                    }
                    $addon = stripslashes($addon);
                    if( in_array($addon, $addons_files) ) {
                        $addons[] = $addon;
                        continue;
                    }
                    
                }
            }
            if( ! empty($option['addons']) && is_array($option['addons']) ) {
                $addons_to_dispaly = $this->addonslib_addons_to_dispaly();
                $old_addons = $option['addons'];
                foreach($old_addons as $old_addon) {
                    if( ! in_array($old_addon, $addons_to_dispaly) ) {
                        $addons[] = $old_addon;
                    }
                }
            }
			$new_option['addons'] = $addons;
			$option     = array_merge( $option, $new_option );
		}
		$option        = $BeRocket_AAPF->sanitize_option( $option );
		update_option( 'br_filters_options', $option );
        wp_cache_delete('br_filters_options', 'berocket_framework_option');

        do_action('brapf_wizard_addons_save', $option);
        
		$wizard->redirect_to_next_step();
	}

    public function get_plugins_list($api = false) {
        $plugins = array(
            'load_more' => array(
                'slug'      => 'load-more-products-for-woocommerce',
                'paid_slug' => 'woocommerce-load-more-products',
                'main_file' => 'load-more-products.php',
                'name'      => __('Load More', 'BeRocket_AJAX_domain'),
                'extra_info'=> __('Load products from the next page via AJAX with Infinite scrolling or Load more products button.', 'BeRocket_AJAX_domain'),
                'default'   => true,
                'api'       => array(),
                'update_option_func' => array($this, 'load_more_update_options'),
                'class'     => 'BeRocket_LMP',
            ),
            'labels' => array(
                'slug'      => 'advanced-product-labels-for-woocommerce',
                'paid_slug' => 'woocommerce-advanced-products-labels',
                'main_file' => 'woocommerce-advanced-products-labels.php',
                'name'      => __('Advanced Labels', 'BeRocket_AJAX_domain'),
                'extra_info'=> __('Drive sales with WooCommerce Advanced Product Labels! Create standout labels for free products, shipping, and promotions. Improve your store’s appeal.', 'BeRocket_AJAX_domain'),
                'default'   => false,
                'api'       => array(),
                'class'     => 'BeRocket_products_label',
            ),
            'brands' => array(
                'slug'      => 'brands-for-woocommerce',
                'main_file' => 'woocommerce-brand.php',
                'name'      => __('Brands', 'BeRocket_AJAX_domain'),
                'extra_info'=> __('Brands have become an inseparable part of our lives, they offer certainty, clarity and high quality comfort. Therefore, when it comes to consumer choices, brands matter!', 'BeRocket_AJAX_domain'),
                'default'   => false,
                'api'       => array(),
                'class'     => 'BeRocket_product_brand',
            ),
            'permalink' => array(
                'slug'      => 'permalink-manager-for-woocommerce',
                'main_file' => 'main.php',
                'name'      => __('Permalink manager', 'BeRocket_AJAX_domain'),
                'extra_info'=> __('Permalink Manager for WooCommerce is developed to provide your store nicer urls.', 'BeRocket_AJAX_domain'),
                'default'   => false,
                'api'       => array(),
                'class'     => 'BeRocketLinkManager',
            ),
        );
        if( $api ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
            foreach($plugins as &$plugin) {
                $api = plugins_api( 'plugin_information', array(
                    'slug' => wp_unslash( $plugin['slug'] ),
                    'is_ssl' => is_ssl(),
                    'fields' => array(
                        'banners' => true,
                        'reviews' => false,
                        'downloaded' => false,
                        'active_installs' => true,
                        'icons' => true
                    )
                ) );
                $plugin['api'] = (array)$api;
            }
        }
        return $plugins;
    }

    public function load_more_update_options() {
        $BeRocket_LMP = BeRocket_LMP::getInstance();
        $lm_options = $BeRocket_LMP->get_option();
        $BeRocket_AAPF = BeRocket_AAPF::getInstance();
        $options = $BeRocket_AAPF->get_option();
        $lm_options['br_lmp_selectors_settings']['products'] = $options['products_holder_id'];
        $lm_options['br_lmp_selectors_settings']['item'] = '.product';
        $lm_options['br_lmp_selectors_settings']['pagination'] = $options['woocommerce_pagination_class'];
        $lm_options['br_lmp_selectors_settings']['next_page'] = $options['woocommerce_pagination_class'] . ' .next';
        $lm_options['br_lmp_selectors_settings']['prev_page'] = $options['woocommerce_pagination_class'] . ' .prev';
        $lm_options['br_lmp_general_settings']['type'] = 'infinity_scroll';
        update_option( 'br_load_more_products', $lm_options );
    }

    public function wizard_install_single_plugin() {
        $nonce = $_POST['nonce'];
        if( ! wp_verify_nonce($nonce, 'br-aapf-setup') ) {
            echo 'false';
            wp_die();
        }
        $plugins_to_install = $this->get_plugins_list();
        $plugin_slug = empty($_POST['plugin']) ? '' : $_POST['plugin'];
        $status = false;
        if( isset($plugins_to_install[$plugin_slug]) ) {
            if( ! isset($plugins_to_install[$plugin_slug]['paid_slug']) || ! $this->if_plugin_install_activate($plugins_to_install[$plugin_slug]) ) {
                $status = $this->install_plugin($plugins_to_install[$plugin_slug]['slug'], $plugins_to_install[$plugin_slug]['main_file']);
            } else {
                $status = true;
            }
            if( $status && isset($plugins_to_install[$plugin_slug]['update_option_func']) ) {
                call_user_func($plugins_to_install[$plugin_slug]['update_option_func']);
            }
        }
        if( is_wp_error($status) ) {
            $status = array('status' => false, 'error' => $status->get_error_message());
        } elseif($status == false) {
            $status = array('status' => false, 'error' => __('Plugin cannot be installed or activated', 'BeRocket_AJAX_domain'));
        } else {
            $status = array('status' => true, 'error' => __('Plugin installed and activated', 'BeRocket_AJAX_domain'));
        }
        echo json_encode($status);
        wp_die();
    }

	public function wizard_plugins_install( $wizard ) {
        $plugins_to_install = $this->get_plugins_list(true);
		?>
        <form method="post" class="br_framework_submit_form">
            <div class="nav-block berocket_framework_menu_general-block nav-block-active">
                <table class="framework-form-table berocket_framework_menu_selectors">
                    <tbody>
                    <?php
                    foreach($plugins_to_install as $plugin_slug => $plugin_data) {
                        ?>
                        <tr style="display: table-row;">
                            <td class="berocket_install_plugins_from_framework_td">
                                <div class="button_container">
                                <?php
                                $checked = $disabled = false;
                                $checked = ! empty( $plugin_data['default'] );
                                if( $this->is_plugin_active($plugin_data) ) {
                                    $checked = true;
                                    $disabled = true;
                                }
                                ?>
                                    <input id="plugin_<?=$plugin_slug?>" class="berocket_install_plugins_from_framework"
                                           data-plugin="<?=$plugin_slug?>" name="berocket_aapf_wizard_settings[<?=$plugin_slug?>]"
                                           type="checkbox" value="1" <?=( $checked ? ' checked' : '' ) ?> <?=( $disabled ? ' disabled' : '' ) ?> />
                                    <i class="fa"></i>
                                    <label for="plugin_<?=$plugin_slug?>">
                                        <?php
                                        if ( is_array($plugin_data['api']) && isset($plugin_data['api']['icons']) ) {
                                            if( isset($plugin_data['api']['icons']['2x']) ) {
                                                echo '<img src="'.$plugin_data['api']['icons']['2x'].'">';
                                            } elseif( isset($plugin_data['api']['icons']['1x']) ) {
                                                echo '<img src="'.$plugin_data['api']['icons']['1x'].'">';
                                            }
                                        }
                                        ?>
                                        <span><strong><?=$plugin_data['name']?></strong>
                                        <span><?=$plugin_data['extra_info']?></span></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
			<?php wp_nonce_field( $wizard->page_id ); ?>
            <p class="next-step">
                <a class="button-primary button button-large button-next" href="<?php echo esc_url( BeRocket_Setup_Wizard_get_prev_step_link() ); ?>"><?php
		            esc_attr_e( "Previous Step", 'BeRocket_AJAX_domain' ); ?></a>
                <input type="hidden" name="save_step" value="1">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e( "Next Step", 'BeRocket_AJAX_domain' ); ?>" />
            </p>
        </form>
		<?php
	}

    public function get_filters_list() {
        $filters = array(
            'category' => array(
                'name'      => __( 'Category', 'BeRocket_AJAX_domain' ),
                'type'      => 'checkbox',
                'default'   => true,
                'options'   => array(
                    'widget_type'   => 'filter',
                    'filter_title'  => __( 'Category', 'BeRocket_AJAX_domain' ),
                    'filter_type'   => 'all_product_cat',
                    'style'         => 'grey-check',
                )
            ),
            'height' => array(
                'name' => __( 'Height', 'BeRocket_AJAX_domain' ),
                'type' => 'checkbox',
                'default' => false,
                'required' => array(
                    'addon' => array(
                        'slug'  => DIRECTORY_SEPARATOR . 'custom_postmeta'. DIRECTORY_SEPARATOR . 'custom_postmeta.php',
                        'msg'   => __( 'Custom Post Meta Filtering add-on will be activated', 'BeRocket_AJAX_domain' )
                    )
                ),
                'options'   => array(
                    'widget_type'       => 'filter',
                    'filter_title'      => __( 'Height', 'BeRocket_AJAX_domain' ),
                    'filter_type'       => 'custom_postmeta',
                    'custom_postmeta'   => '_height',
                    'style'             => 'grey-check',
                )
            ),
            'price' => array(
                'name'      => __( 'Price', 'BeRocket_AJAX_domain' ),
                'type'      => 'checkbox',
                'default'   => true,
                'options'   => array(
                    'widget_type'   => 'filter',
                    'filter_title'  => __( 'Price', 'BeRocket_AJAX_domain' ),
                    'filter_type'   => 'price',
                    'style'         => 'new_slider',
                )
            ),
            'width' => array(
                'name' => __( 'Width', 'BeRocket_AJAX_domain' ),
                'type' => 'checkbox',
                'default' => false,
                'required' => array(
                    'addon' => array(
                        'slug'  => DIRECTORY_SEPARATOR . 'custom_postmeta'. DIRECTORY_SEPARATOR . 'custom_postmeta.php',
                        'msg'   => __( 'Custom Post Meta Filtering add-on will be activated', 'BeRocket_AJAX_domain' )
                    )
                ),
                'options'   => array(
                    'widget_type'       => 'filter',
                    'filter_title'      => __( 'Width', 'BeRocket_AJAX_domain' ),
                    'filter_type'       => 'custom_postmeta',
                    'custom_postmeta'   => '_width',
                    'style'             => 'grey-check',
                )
            ),
            'attribute' => array(
                'name'      => __( 'Attribute', 'BeRocket_AJAX_domain' ),
                'type'      => 'select',
                'default'   => true,
                'select'    => array(),
                'select_option' => 'attribute',
                'options'   => array(
                    'widget_type'   => 'filter',
                    'filter_title'  => __( 'Category', 'BeRocket_AJAX_domain' ),
                    'filter_type'   => 'attribute',
                    'style'         => 'grey-check',
                )
            ),
            'length' => array(
                'name' => __( 'Length', 'BeRocket_AJAX_domain' ),
                'type' => 'checkbox',
                'default' => false,
                'required' => array(
                    'addon' => array(
                        'slug'  => DIRECTORY_SEPARATOR . 'custom_postmeta'. DIRECTORY_SEPARATOR . 'custom_postmeta.php',
                        'msg'   => __( 'Custom Post Meta Filtering add-on will be activated', 'BeRocket_AJAX_domain' )
                    )
                ),
                'options'   => array(
                    'widget_type'       => 'filter',
                    'filter_title'      => __( 'Length', 'BeRocket_AJAX_domain' ),
                    'filter_type'       => 'custom_postmeta',
                    'custom_postmeta'   => '_length',
                    'style'             => 'grey-check',
                )
            ),
            'tag' => array(
                'name'      => __( 'Tag', 'BeRocket_AJAX_domain' ),
                'type'      => 'checkbox',
                'default'   => true,
                'options'   => array(
                    'widget_type'   => 'filter',
                    'filter_title'  => __( 'Tag', 'BeRocket_AJAX_domain' ),
                    'filter_type'   => 'tag',
                    'style'         => 'grey-check',
                )
            ),
            'brands' => array(
                'name'      => __( 'Brands', 'BeRocket_AJAX_domain' ),
                'type'      => 'checkbox',
                'default'   => false,
                'required'  => array(
                    'plugin' => array(
                        'slug'  => 'brands-for-woocommerce/woocommerce-brand.php',
                        'msg'   => __( 'Brands plugin must be activated', 'BeRocket_AJAX_domain' )
                    ),
                ),
                'options'   => array(
                    'widget_type'   => 'filter',
                    'filter_title'  => __( 'Brands', 'BeRocket_AJAX_domain' ),
                    'filter_type'   => 'berocket_brand',
                    'style'         => 'grey-check',
                )
            ),
        );
        $attributes_list = br_aapf_get_attributes();
        if( count($attributes_list) > 0 ) {
            $filters['attribute']['select'] = $attributes_list;
        } else {
            unset($filters['attribute']);
        }
        return apply_filters('berocket_aapf_setup_wizard_filters_list', $filters);
    }

    public function check_plugin_requirements($filter_data) {
        $result = array(
            'status'    => false,
            'selected'  => true,
            'msg'       => array()
        );
        if( empty($filter_data['required']) || ! is_array($filter_data['required']) ) {
            return $result;
        }
        $requirements = $filter_data['required'];
        if( isset($requirements['addon']) ) {
            $result['status'] = true;
            $result['msg'][] = $requirements['addon']['msg'];
        }
        if( isset($requirements['plugin']) ) {
            if( ! is_array($requirements['plugin']['slug']) ) {
                $requirements['plugin']['slug'] = array($requirements['plugin']['slug']);
            }
            $is_active = false;
            foreach($requirements['plugin']['slug'] as $plugin_slug) {
                if( is_plugin_active($plugin_slug) ) {
                    $is_active = true;
                    break;
                }
            }
            if( ! $is_active ) {
                $result['status'] = true;
                $result['selected'] = false;
                $result['msg'][] = $requirements['plugin']['msg'];
            }
        }
        return $result;
    }

	public function wizard_plugins_install_save( $wizard ) {
		if( ! check_admin_referer( $wizard->page_id ) ) {
            $same_link = esc_url_raw(add_query_arg(
                'step',
                $this->step,
                remove_query_arg( 'activate_error' ) ) );
            wp_redirect($same_link);
        }

        do_action('brapf_wizard_plugins_install_save', $option);
		$wizard->redirect_to_next_step();
	}

    public function group_search_posts_where( $where, $wp_query ) {
        global $wpdb;
        $where .= " AND " . $wpdb->posts . ".post_title LIKE '" . esc_sql( __('Group by Wizard', 'BeRocket_AJAX_domain') ) . "'";
        return $where;
    }

    public function filter_search_posts_where( $where, $wp_query ) {
        global $wpdb;
        $where .= " AND " . $wpdb->posts . ".post_title LIKE '" . esc_sql( $this->current_post_search ) . "'";
        return $where;
    }

    public function get_wizard_group_posts() {
        add_filter( 'posts_where', array($this, 'group_search_posts_where'), 10, 2 );
        $filters_get = new WP_Query(array(
            'post_type' => 'br_filters_group'
        ));
        $is_group_posts = $filters_get->get_posts();
        remove_filter( 'posts_where', array($this, 'group_search_posts_where'), 10, 2 );
        return $is_group_posts;
    }

    public $current_post_search = '';

    public function get_specific_filter_posts($title) {
        $this->current_post_search = $title;
        add_filter( 'posts_where', array($this, 'filter_search_posts_where'), 10, 2 );
        $filters_get = new WP_Query(array(
            'post_type'     => 'br_product_filter',
            'post_status'   => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
        ));
        $is_filters_posts = $filters_get->get_posts();
        remove_filter( 'posts_where', array($this, 'filter_search_posts_where'), 10, 2 );
        return $is_filters_posts;
    }

    public function wizard_create_single_filter() {
        $nonce = $_POST['nonce'];
        if( ! wp_verify_nonce($nonce, 'br-aapf-setup') ) {
            echo 'false';
            wp_die();
        }
        $status = false;
        $filters_list = $this->get_filters_list();
        $filter_slug = empty($_POST['filter']) ? '' : $_POST['filter'];
        $filter_slug = sanitize_text_field($filter_slug);
        if( $filter_slug == 'create_group' ) {
            $filters = empty($_POST['filters']) ? array() : $_POST['filters'];
            if( is_array($filters) ) {
                $is_group_posts = $this->get_wizard_group_posts();
                $is_group_exist = count($is_group_posts) > 0;

                $filters_for_group = array();
                $option = BeRocket_AAPF::get_aapf_option();
                foreach($filters as $filter) {
                    $filter_name = sanitize_text_field($filter['name']);
                    if( isset($filters_list[$filter_name]) ) {
                        $filter_value = sanitize_text_field($filter['val']);
                        $current_filter = $filters_list[$filter_name];
                        if( isset($current_filter['required']) && isset($current_filter['required']['addon']) ) {
                            if( isset($option['addons']) && is_array($option['addons']) 
                                && ! in_array($current_filter['required']['addon']['slug'], $option['addons']) ) {
                                $option['addons'][] = $current_filter['required']['addon']['slug'];
                            }
                        }
                        $post_name = $current_filter['name'];
                        switch( $current_filter['type'] ) {
                            case 'select':
                                $post_name .= ': ' . $current_filter['select'][$filter_value];
                                break;
                        }
                        $post_name .= ' - build in wizard';
                        $is_filters_posts = $this->get_specific_filter_posts($post_name);
                        $is_filters_exist = count($is_filters_posts) > 0;
                        if( $is_filters_exist ) {
                            $filters_for_group[] = $is_filters_posts[0]->ID;
                        }
                    }
                }
                update_option( 'br_filters_options', $option );
                wp_cache_delete('br_filters_options', 'berocket_framework_option');
                if( ! $is_group_exist ) {
                    $new_filter_id = wp_insert_post(array(
                        'post_title'    => __('Group by Wizard', 'BeRocket_AJAX_domain'),
                        'post_type'     => 'br_filters_group',
                        'post_status'   => 'publish'
                    ));
                } else {
                    $new_filter_id = $is_group_posts[0]->ID;
                }
                if( ! is_wp_error($new_filter_id) ) {
                    $BeRocket_AAPF_group_filters = BeRocket_AAPF_group_filters::getInstance();
                    $filter_post = get_post($new_filter_id);
                    $_POST[$BeRocket_AAPF_group_filters->post_name] = array('filters' => $filters_for_group);
                    $BeRocket_AAPF_group_filters->wc_save_product_without_check($new_filter_id, $filter_post);
                    $status = true;
                }
            }
        } elseif( $filter_slug == 'add_to_sidebar' ) {
            $sidebar = empty($_POST['value']) ? '' : $_POST['value'];
            $is_group_posts = $this->get_wizard_group_posts();
            $is_group_exist = count($is_group_posts) > 0;
            if( $is_group_exist ) {
                $active_widgets = get_option( 'sidebars_widgets' );
                $is_group_posts = $is_group_posts[0];
                $group_id_search = $is_group_posts->ID;
                $widget_data = array('group_id' => $group_id_search);
                $sidebar = strtolower(sanitize_text_field($sidebar));
                $widget_exist = false;
                if( isset($active_widgets[$sidebar]) && count($active_widgets[$sidebar]) > 0 ) {
                    $widget_data_check = get_option( 'widget_berocket_aapf_group' );
                    foreach($active_widgets[$sidebar] as $widget_id) {
                        if( strpos($widget_id, 'berocket_aapf_group') !== FALSE ) {
                            $widget_id = str_replace('berocket_aapf_group-', '', $widget_id);
                            if( isset($widget_data_check[$widget_id]) && isset($widget_data_check[$widget_id]['group_id']) 
                                && $widget_data_check[$widget_id]['group_id'] == $group_id_search ) {
                                $widget_exist = true;
                                $status = true;
                            }
                        }
                    }
                }
                if( ! $widget_exist ) {
                    $this->insert_widget_in_sidebar('berocket_aapf_group', $widget_data, $sidebar);
                    $status = true;
                }
            }
        } else {
            $filter_value = sanitize_text_field($_POST['value']);
            if( isset($filters_list[$filter_slug]) ) {
                $current_filter = $filters_list[$filter_slug];
                $post_name = $current_filter['name'];
                switch( $current_filter['type'] ) {
                    case 'select':
                        $current_filter['options'][$current_filter['select_option']] = $filter_value;
                        $current_filter['options']['filter_title'] = $current_filter['select'][$filter_value];
                        $post_name .= ': ' . $current_filter['select'][$filter_value];
                        break;
                    default:
                        $current_filter['options']['filter_title'] = $post_name;
                }
                $post_name .= ' - build in wizard';
                $is_filters_exist = $this->get_specific_filter_posts($post_name);
                $is_filters_exist = count($is_filters_exist) > 0;
                if( ! $is_filters_exist ) {
                    $new_filter_id = wp_insert_post(array(
                        'post_title'    => $post_name,
                        'post_type'     => 'br_product_filter',
                        'post_status'   => 'publish'
                    ));
                    if( ! is_wp_error($new_filter_id) ) {
                        $BeRocket_AAPF_single_filter = BeRocket_AAPF_single_filter::getInstance();
                        $filter_post = get_post($new_filter_id);
                        $_POST[$BeRocket_AAPF_single_filter->post_name] = $current_filter['options'];
                        $BeRocket_AAPF_single_filter->wc_save_product_without_check($new_filter_id, $filter_post);
                        $status = true;
                    }
                } else {
                    $status = true;
                }
            }
        }
        
        echo $status ? 'true' : 'false';
        wp_die();
    }

    public function insert_widget_in_sidebar( $widget_id, $widget_data, $sidebar ) {
        // Retrieve sidebars, widgets and their instances
        $sidebars_widgets = get_option( 'sidebars_widgets', array() );
        $widget_instances = get_option( 'widget_' . $widget_id, array() );

        // Retrieve the key of the next widget instance
        $numeric_keys = array_filter( array_keys( $widget_instances ), 'is_int' );
        $next_key = $numeric_keys ? max( $numeric_keys ) + 1 : 2;

        // Add this widget to the sidebar
        if ( ! isset( $sidebars_widgets[ $sidebar ] ) ) {
            $sidebars_widgets[ $sidebar ] = array();
        }
        $sidebars_widgets[ $sidebar ][] = $widget_id . '-' . $next_key;

        // Add the new widget instance
        $widget_instances[ $next_key ] = $widget_data;

        // Store updated sidebars, widgets and their instances
        update_option( 'sidebars_widgets', $sidebars_widgets );
        update_option( 'widget_' . $widget_id, $widget_instances );
    }

    public function display_filter_template($filter_slug, $filter_data) {
        $html = '';
        $required = $this->check_plugin_requirements($filter_data);
        $post_name = $filter_data['name'];
        $post_name .= ' - build in wizard';
        switch($filter_data['type']) {
            case 'select':
                $html .= '<div class="button_container"><i class="fa"></i><input ' . (empty($required['selected']) ? 'disabled ' : '') . 'id="filter_' . $filter_slug .
                    '" class="brwizard_create_filters_enabled" type="checkbox" value="1"' .
                    ( empty( $filter_data['default'] ) ? '' : " checked" ) . '>';
                $html .= '<label for="filter_' . $filter_slug . '">';
                $html .= '<strong>' . $filter_data['name'] . '</strong>';
	            $html .= '</label>';
                $html .= '<select class="brwizard_create_filters" data-type="' . $filter_data['type'] . '" data-filter="' . $filter_slug . 
                    '" name="berocket_aapf_wizard_settings[' . $filter_slug . ']">';
                if( isset($filter_data['select']) && is_array($filter_data['select']) ) {
                    foreach($filter_data['select'] as $select_val => $select_name) {
                        $html .= '<option value="' . $select_val . '">' . $select_name . '</option>';
                    }
                }
                $html .= '</select></div>';
                break;
            case 'checkbox':
                $is_filters_exist = $this->get_specific_filter_posts($post_name);
                $is_filters_exist = count($is_filters_exist) > 0;
                $html .= '<div class="button_container"><i class="fa"></i><input ' . ((empty($required['selected']) || $is_filters_exist) ? 'disabled ' : '') . 'id="filter_' . $filter_slug .
                    '" class="brwizard_create_filters brwizard_create_filters_enabled" data-type="' . $filter_data['type'] . '" data-filter="' . $filter_slug . 
                    '" name="berocket_aapf_wizard_settings[' . $filter_slug . ']" type="checkbox" value="1"' .
                    ( ( empty( $filter_data['default'] ) && ! $is_filters_exist ) ? '' : " checked" ) . '>';
                $html .= '<label for="filter_' . $filter_slug . '">';
                $html .= '<strong>' . $filter_data['name'] . '</strong>';
                $html .= '</label></div>';
                break;
        }
        if( $required['status'] ) {
            $html .= '<span>';
            foreach($required['msg'] as $msg) {
                $html .= '<span>' . $msg . '</span>';
            }
            $html .= '</span>';
        }
        return $html;
    }

	public function wizard_filters_create( $wizard ) {
		$option = BeRocket_AAPF::get_aapf_option();
        $filters_list = $this->get_filters_list();
		?>
        <form method="post" class="br_framework_submit_form">
            <div class="nav-block berocket_framework_menu_general-block nav-block-active">
                <table class="framework-form-table berocket_framework_menu_selectors">
                    <tbody>
                    <?php
                    foreach($filters_list as $filter_slug => $filter_data) {
                        $required = $this->check_plugin_requirements($filter_data);
                        echo '<tr style="display: table-row;">';
                        echo '<td class="brwizard_create_filters_td">';
                        echo $this->display_filter_template($filter_slug, $filter_data);
                        echo '</td></tr>';
                    } ?>
                    </tbody>
                </table>
                <table class="framework-form-table berocket_framework_menu_selectors">
                    <tbody>
                    <tr style="display: table-row;">
                        <td class="brwizard_create_group_td">
                            <div class="button_container">
                                <i class="fa"></i>
                                <input type="checkbox" disabled checked />
                                <label><?php _e('Create Group', 'BeRocket_AJAX_domain'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <?php if( isset($GLOBALS['wp_registered_sidebars']) && is_array($GLOBALS['wp_registered_sidebars']) && count($GLOBALS['wp_registered_sidebars']) > 0 ) { ?>
                    <tr style="display: table-row;">
                        <td class="brwizard_add_to_sidebar_td">

                            <div class="button_container">
                                <i class="fa"></i>
                                <input type="checkbox" class="brwizard_create_filters_enabled" checked>
                                <label><?php _e('Add to sidebar', 'BeRocket_AJAX_domain'); ?></label>
                                <select class="brwizard_create_filters">
                                <?php
                                foreach ( $GLOBALS['wp_registered_sidebars'] as $sidebar ) { ?>
                                     <option value="<?php echo ucwords( $sidebar['id'] ); ?>">
                                          <?php echo ucwords( $sidebar['name'] ); ?>
                                     </option>
                                <?php } ?>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
			<?php wp_nonce_field( $wizard->page_id ); ?>
            <p class="next-step">
                <a class="button-primary button button-large button-next" href="<?php echo esc_url( BeRocket_Setup_Wizard_get_prev_step_link() ); ?>"><?php
		            esc_attr_e( "Previous Step", 'BeRocket_AJAX_domain' ); ?></a>
                <input type="hidden" name="save_step" value="1">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e( "Next Step", 'BeRocket_AJAX_domain' ); ?>" />
            </p>
        </form>
		<?php
	}

	public function wizard_filters_create_save( $wizard ) {
		if( ! check_admin_referer( $wizard->page_id ) ) {
            $same_link = esc_url_raw(add_query_arg(
                'step',
                $this->step,
                remove_query_arg( 'activate_error' ) ) );
            wp_redirect($same_link);
        }

        do_action('brapf_wizard_filters_create_save', $option);
		$wizard->redirect_to_next_step();
	}

	public function wizard_ready( $wizard ) {
		$option = BeRocket_AAPF::get_aapf_option();
        $filters = array();
		$query = new WP_Query(array('post_type' => 'br_product_filter', 'nopaging' => true));
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
                $filters[] = $query->post;
			}
			wp_reset_postdata();
        }
		?>
        <form method="post" class="br_framework_submit_form">
            <div class="nav-block berocket_framework_menu_general-block nav-block-active">
                <div class="wizard_end_content">
                    <h2><?php _e( 'The plugin is ready to use', 'BeRocket_AJAX_domain' ) ?></h2>
                    <div class="wizard_end_video">
                        <iframe width="560" height="315"
                                src="https://www.youtube.com/embed/8gaMj-IxUj0?rel=0&amp;showinfo=0" frameborder="0"
                                gesture="media" allow="encrypted-media" allowfullscreen></iframe>
                    </div>
                    <h2><?php _e('Add-ons', 'BeRocket_AJAX_domain'); ?></h2>
                    <p>
                        <b><?php _e('Custom Post Meta Filtering', 'BeRocket_AJAX_domain'); ?></b> — <?php 
                        _e('Option allows you to filter by the post\'s meta(weight, length, etc.).', 'BeRocket_AJAX_domain'); ?>
                    </p>
                    <p>
                        <b><?php _e('Custom Slug', 'BeRocket_AJAX_domain'); ?></b> — <?php
                        _e('Replaces attribute/taxonomy slug in filtered URL.<br>Provide the possibility to use multiple filters for the same attribute/taxonomy.', 'BeRocket_AJAX_domain'); ?>
                        <a target="_blank" href="https://docs.berocket.com/docs_section/custom-slug"><?php _e('Read more', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <p>
                        <b><?php _e('Nested Filters', 'BeRocket_AJAX_domain'); ?></b> — <?php
                        _e('The ability to set conditions to output the selected filter only after another filtering.', 'BeRocket_AJAX_domain') ?>
                        <a target="_blank" href="https://docs.berocket.com/docs_section/nested-filters-beta"><?php _e('Read more', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <p>
                        <b><?php _e('Custom Sidebar', 'BeRocket_AJAX_domain'); ?></b> — <?php _e('Option enables a floating sidebar where filter or filter group widgets
                        can be added. This custom sidebar will be hidden until the button is clicked. It is a must for
                        mobiles or themes without a sidebar.', 'BeRocket_AJAX_domain'); ?>
                        <a target="_blank" href="https://docs.berocket.com/docs_section/custom-sidebar"><?php _e('Read more', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <p>
                        <b><?php _e('Custom Search', 'BeRocket_AJAX_domain'); ?></b> — <?php _e('Option adds better management for how product search works.', 'BeRocket_AJAX_domain'); ?>
                        <a target="_blank" href="https://docs.berocket.com/docs_section/custom-search"><?php _e('Read more', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <p>
                        <b><?php _e('Add more classes', 'BeRocket_AJAX_domain'); ?></b> — <?php
                        _e('Adds extra classes to the filter\'s HTML structure so that you can better control the custom styling.', 'BeRocket_AJAX_domain'); ?>
                    </p>

                    <h2><?php _e('Plugins', 'BeRocket_AJAX_domain'); ?></h2>
                    <p>
                        <b><?php _e('Load More', 'BeRocket_AJAX_domain'); ?></b> - <?php
                        _e('Load products from the next page via AJAX with Infinite scrolling or Load more products button.', 'BeRocket_AJAX_domain'); ?>
                        <?php _e('Plugin pages', 'BeRocket_AJAX_domain'); ?>: <a target="_blank" href="https://wordpress.org/plugins/load-more-products-for-woocommerce/"><?php _e('Free', 'BeRocket_AJAX_domain'); ?></a> |
                            <a target="_blank" href="https://berocket.com/woocommerce-load-more-products/"><?php _e('Premium', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <p>
                        <b><?php _e('Advanced Labels', 'BeRocket_AJAX_domain'); ?></b> - <?php
                        _e('Drive sales with WooCommerce Advanced Product Labels! Create standout labels for free products, shipping, and promotions. Improve your store’s appeal.', 'BeRocket_AJAX_domain'); ?>
                        <?php _e('Plugin pages', 'BeRocket_AJAX_domain'); ?>: <a target="_blank" href="https://wordpress.org/plugins/advanced-product-labels-for-woocommerce/"><?php _e('Free', 'BeRocket_AJAX_domain'); ?></a> |
                            <a target="_blank" href="https://berocket.com/woocommerce-advanced-product-labels/"><?php _e('Premium', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <p>
                        <b><?php _e('Brands', 'BeRocket_AJAX_domain'); ?></b> - <?php
                        _e('Brands have become an inseparable part of our lives, they offer certainty, clarity and high quality comfort. Therefore, when it comes to consumer choices, brands matter!', 'BeRocket_AJAX_domain'); ?>
                        <a target="_blank" href="https://wordpress.org/plugins/brands-for-woocommerce/"><?php _e('Free plugin page', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <p>
                        <b><?php _e('Permalink manager', 'BeRocket_AJAX_domain'); ?></b> - <?php
                        _e('Permalink Manager for WooCommerce is developed to provide your store nicer urls.', 'BeRocket_AJAX_domain') ?>
                        <a target="_blank" href="https://wordpress.org/plugins/permalink-manager-for-woocommerce/"><?php _e('Free plugin page', 'BeRocket_AJAX_domain'); ?></a>
                    </p>
                    <?php if ( count( $filters ) > 0 ) { ?>
                        <h2><?php _e('Created filters', 'BeRocket_AJAX_domain'); ?></h2>
                        <ul>
	                        <?php foreach( $filters as $filter ) { ?>
                                <li>
                                    <?=
                                    'ID ' . $filter->ID . ': ' .
                                    ( $filter->post_title ?: '[no title set]') .
                                    ' <a class="berocket_edit_filter" target="_blank" 
                                        href="' . get_edit_post_link( $filter->ID ) . '">[edit]</a>'?>
                                </li>
	                        <?php } ?>
                        </ul>
			        <?php } ?>

                    <h4><?php _e( 'Widget', 'BeRocket_AJAX_domain' ) ?></h4>
                    <p><?php _e( 'Now, you can add widgets AJAX Product Filters to your sidebar.', 'BeRocket_AJAX_domain' ) ?></p>
                    <p><?php _e( 'More information about widget options can be found in the <a target="_blank" href="https://docs.berocket.com/plugin/woocommerce-ajax-products-filter#how-do-i-add-a-new-filter">BeRocket Documentation</a>.', 'BeRocket_AJAX_domain' ) ?></p>
                    <?php
                    $old_filter_widgets = get_option( 'widget_berocket_aapf_widget' );
                    if ( ! is_array( $old_filter_widgets ) ) {
                        $old_filter_widgets = array();
                    }
                    foreach ( $old_filter_widgets as $key => $value ) {
                        if ( ! is_numeric( $key ) ) {
                            unset( $old_filter_widgets[ $key ] );
                        }
                    }
                    ?>
                </div>
            </div>
			<?php
			wp_nonce_field( $wizard->page_id ); ?>

            <p class="next-step">
                <a class="button-primary button button-large button-next"
                   href="<?=admin_url('admin.php?page=br-product-filters')?>">
                    <?php esc_attr_e( "Plugin Settings", 'BeRocket_AJAX_domain' ) ?></a>

                <?php if ( count( $filters ) > 0 ) { ?>
                    <a class="button-primary button button-large button-next"
                       href="<?=get_permalink( wc_get_page_id( 'shop' ) )?>"><?php esc_attr_e( "Open Shop page", 'BeRocket_AJAX_domain' ); ?></a>
                <?php } ?>

                <a class="button-primary button button-large button-next"
                   href="<?=admin_url('post-new.php?post_type=br_product_filter')?>"><?php esc_attr_e( "Create a filter", 'BeRocket_AJAX_domain' ); ?></a>
            </p>
        </form>
		<?php
	}

	public function wizard_ready_save( $wizard ) {
		if( ! check_admin_referer( $wizard->page_id ) ) {
            $same_link = esc_url_raw(add_query_arg(
                'step',
                $this->step,
                remove_query_arg( 'activate_error' ) ) );
            wp_redirect($same_link);
        }
		$BeRocket_AAPF = BeRocket_AAPF::getInstance();
		wp_redirect( admin_url( 'admin.php?page=br-product-filters' ) );
	}

    public function is_plugin_active ($plugin_data) {
        if( isset($plugin_data['class']) && class_exists($plugin_data['class']) ) {
            return true;
        }
        if( isset($plugin_data['paid_slug']) ) {
            $paid_plugin_path = $plugin_data['paid_slug'] . '/' . $plugin_data['main_file'];
            if( is_plugin_active($paid_plugin_path) ) {
                return true;
            }
        }
        $plugin_path = $plugin_data['slug'] . '/' . $plugin_data['main_file'];
        return is_plugin_active($plugin_path);
    }

    public function if_plugin_install_activate($plugin_data) {
        if( $this->is_plugin_active($plugin_data) ) {
            return true;
        }
        if( isset($plugin_data['paid_slug']) ) {
            $paid_plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_data['paid_slug'];
            if (is_dir($paid_plugin_dir)) {
                $paid_plugin_path = $paid_plugin_dir . '/' . $plugin_data['main_file'];
                if (file_exists($paid_plugin_path)) {
                    activate_plugin($paid_plugin_path);
                    return true;
                }
            }
        }
        $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_data['slug'];
        if (is_dir($plugin_dir)) {
            $plugin_path = $plugin_dir . '/' . $plugin_data['main_file'];
            if (file_exists($plugin_path)) {
                activate_plugin($plugin_path);
                return true;
            }
        }
        
        return false;
    }

    public function install_plugin($slug, $main_file) {
        $plugin_dir = WP_PLUGIN_DIR . '/' . $slug;
        $install = false;
        if (! is_dir($plugin_dir)) {
            include_once( ABSPATH . 'wp-admin/includes/file.php' );
            include_once( ABSPATH . 'wp-admin/includes/misc.php' );
            include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
            include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
            include_once( 'wizard/wizard_skin.php' );
            $api = plugins_api(
                'plugin_information',
                array(
                    'slug' => $slug,
                    'fields' => array(
                        'short_description' => false,
                        'sections' => false,
                        'requires' => false,
                        'rating' => false,
                        'ratings' => false,
                        'downloaded' => false,
                        'last_updated' => false,
                        'added' => false,
                        'tags' => false,
                        'compatibility' => false,
                        'homepage' => false,
                        'donate_link' => false,
                    ),
                )
            );
            
            $skin = new BeRocket_custom_upgrader_skin(array('api' => $api));

            $upgrader = new Plugin_Upgrader($skin);

            ob_start();
            $install = $upgrader->install($api->download_link);
            ob_end_clean();
        }
        $plugin_path = $plugin_dir . '/' . $main_file;
        if (file_exists($plugin_path)) {
            $install = activate_plugin($plugin_path);
            if( $install === NULL ) {
                $install = true;
            }
        }
        return $install;
    }
}
new BeRocket_AAPF_Wizard();