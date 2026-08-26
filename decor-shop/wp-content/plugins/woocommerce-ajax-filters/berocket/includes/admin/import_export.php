<?php
class BeRocket_import_export {
    const PLUGIN_URL_TOKEN = '__BRFR_PLUGIN_URL__';
    public function __construct() {
        add_action('BeRocket_framework_updater_account_form_after', array($this, 'account_form'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_brfr_get_export_settings', array($this, 'get_export') );
        add_action('wp_ajax_brfr_set_import_settings', array($this, 'set_import') );
        add_action('wp_ajax_brfr_confirm_import_settings', array($this, 'confirm_import') );
        add_action('wp_ajax_brfr_get_import_backups', array($this, 'get_backups') );
        add_action('wp_ajax_brfr_restore_import_backups', array($this, 'restore_backups') );
    }
    public static function admin_enqueue_scripts() {
        $script_handle = 'berocket_import_export_admin';
        $style_handle = 'berocket_import_export_admin';
        wp_register_style(
            $style_handle,
            plugins_url('../../assets/css/import-export.css', __FILE__),
            array(),
            false
        );
        wp_register_script(
            $script_handle,
            plugins_url('../../assets/js/import-export.js', __FILE__),
            array('jquery'),
            false,
            true
        );
        wp_localize_script($script_handle, 'berocket_import_export', array(
            'nonce' => wp_create_nonce('brfr_import_export'),
            'text' => array(
                'incorrect_data'        => __('Incorrect data', 'BeRocket_domain'),
                'import_failed'         => __('Import failed', 'BeRocket_domain'),
                'link'                  => __('Link', 'BeRocket_domain'),
                'leave_as_is'           => __('Leave as is', 'BeRocket_domain'),
                'replace_site'          => __('Use same path on this site', 'BeRocket_domain'),
                'custom'                => __('Custom', 'BeRocket_domain'),
                'select_image'          => __('Select image', 'BeRocket_domain'),
                'use_this_image'        => __('Use this image', 'BeRocket_domain'),
                'enter_url'             => __('Enter URL', 'BeRocket_domain'),
                'review_links_notice'   => __('Choose how to handle found links before the final import.', 'BeRocket_domain'),
                'imported_custom_posts' => __('Imported custom posts', 'BeRocket_domain'),
                'delete_existing_posts' => __('Delete existing custom posts of this type before import', 'BeRocket_domain'),
                'name'                  => __('Name', 'BeRocket_domain'),
                'update'                => __('Update', 'BeRocket_domain'),
                'create_new'            => __('Create New', 'BeRocket_domain'),
            ),
        ));
        wp_enqueue_style($style_handle);
        wp_enqueue_script($script_handle);
    }
    public static function get_export() {
        $nonce = $_GET['nonce'];
        $result = wp_verify_nonce( $nonce, 'brfr_import_export' );
        if( ! $result || ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }
        $plugin_slug = sanitize_text_field($_GET['plugin']);
        $plugin_instance = apply_filters('brfr_plugin_get_instance_' . $plugin_slug, FALSE);
        $export = array();
        if( $plugin_instance !== FALSE && $plugin_instance->import_export !== FALSE ) {
            $export = self::export_generate($plugin_slug);
            if( $plugin_instance->import_export_posts  !== FALSE ) {
                $posts_list = $plugin_instance->import_export_posts;
                $export_posts = array();
                foreach($posts_list as $post_name) {
                    $export_post = self::export_generate_post($post_name);
                    if( $export_post != false && is_array($export_post) && count($export_post) > 0 ) {
                        $export_posts[$post_name] = $export_post;
                    }
                }
                if( count($export_posts) > 0 ) {
                    $export['import_custom_posts'] = $export_posts;
                }
            }
            $export = self::replace_plugin_url_with_token_in_settings($export, self::get_plugin_url($plugin_instance));
            $export['brfr_service'] = self::get_export_service_data($plugin_instance);
        }
        echo json_encode($export);
        wp_die();
    }
    public static function set_import() {
        $nonce = $_POST['nonce'];
        $result = wp_verify_nonce( $nonce, 'brfr_import_export' );
        if( ! $result || ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }
        $plugin_slug = sanitize_text_field($_POST['plugin']);
        $data = stripslashes($_POST['data']);
        if( empty($plugin_slug) || empty($data) ) {
            _e('Empty data', 'BeRocket_domain');
            wp_die();
        }
        $data = json_decode($data, true);
        if( empty($data) ) {
            _e('Incorrect data', 'BeRocket_domain');
            wp_die();
        }
        $plugin_instance = apply_filters('brfr_plugin_get_instance_' . $plugin_slug, FALSE);
        if( $plugin_instance === FALSE || $plugin_instance->import_export === FALSE ) {
            _e('Import for this plugin not allowed', 'BeRocket_domain');
            wp_die();
        }
        $service_data = self::get_import_service_data($data);
        $service_data['current_plugin_url'] = self::get_plugin_url($plugin_instance);
        $data = self::replace_plugin_token_with_url_in_settings($data, $service_data['current_plugin_url']);
        $custom_posts_data = self::get_import_custom_posts_data($data);
        $links = self::collect_import_links($data, $custom_posts_data, $service_data);
        $import_token = self::store_import_package(array(
            'plugin_slug'        => $plugin_slug,
            'data'               => $data,
            'custom_posts_data'  => $custom_posts_data,
            'service_data'       => $service_data,
        ));
        $custom_post_groups = self::prepare_custom_post_groups_for_review($custom_posts_data);
        wp_send_json_success(array(
            'token' => $import_token,
            'links' => $links,
            'custom_post_types' => array_keys($custom_posts_data),
            'has_custom_posts' => ! empty($custom_post_groups),
            'custom_posts' => self::prepare_custom_posts_for_review($custom_posts_data),
            'custom_post_groups' => $custom_post_groups,
        ));
    }
    public static function confirm_import() {
        $nonce = $_POST['nonce'];
        $result = wp_verify_nonce( $nonce, 'brfr_import_export' );
        if( ! $result || ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }
        $plugin_slug = sanitize_text_field($_POST['plugin']);
        $import_token = sanitize_text_field($_POST['import_token']);
        $package = self::get_import_package($import_token);
        if( empty($plugin_slug) || empty($package) || empty($package['plugin_slug']) || $package['plugin_slug'] !== $plugin_slug ) {
            wp_send_json_error(array(
                'message' => __('Import data expired. Please try again.', 'BeRocket_domain'),
            ));
        }
        $link_actions = array();
        if( ! empty($_POST['link_actions']) ) {
            $link_actions = json_decode(stripslashes($_POST['link_actions']), true);
            if( ! is_array($link_actions) ) {
                $link_actions = array();
            }
        }
        $import_options = array(
            'delete_existing_custom_posts' => self::sanitize_custom_post_type_flags(
                ! empty($_POST['delete_existing_custom_posts']) ? json_decode(stripslashes($_POST['delete_existing_custom_posts']), true) : array()
            ),
            'custom_post_slug_strategies' => self::sanitize_custom_post_slug_strategies(
                ! empty($_POST['custom_post_slug_strategies']) ? json_decode(stripslashes($_POST['custom_post_slug_strategies']), true) : array()
            ),
        );
        $result = self::process_import_package($plugin_slug, $package, $link_actions, $import_options);
        delete_transient(self::get_import_transient_name($import_token));
        if( empty($result['success']) ) {
            wp_send_json_error(array(
                'message' => $result['message'],
            ));
        }
        wp_send_json_success(array(
            'message' => $result['message'],
        ));
        wp_die();
    }
    public static function get_backups() {
        $nonce = $_GET['nonce'];
        $result = wp_verify_nonce( $nonce, 'brfr_import_export' );
        if( ! $result || ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }
        $plugin_slug = sanitize_text_field($_GET['plugin']);
        $exist_ids = array();
        foreach(array('1', '2', '3') as $save_id) {
            $transient_option = get_transient('brfr_bckp_' . $plugin_slug . '_' . $save_id);
            if( $transient_option != false && is_array($transient_option) && ! empty($transient_option['import_export_date']) ) {
                $exist_ids[] = array(
                    'id' => $save_id,
                    'time' => $transient_option['import_export_date']
                );
            }
        }
        if( count($exist_ids) > 0 ) {
            echo '<select name="backup">';
            echo '<option value="0">' . __('-= Select backup to restore =-', 'BeRocket_domain') . '</option>';
            foreach($exist_ids as $exist_id) {
                echo '<option value="'.$exist_id['id'].'">' . date('Y-m-d H:i:s', $exist_id['time']) . '</option>';
            }
            echo '</select>';
        }
        wp_die();
    }
    public static function restore_backups() {
        $nonce = $_GET['nonce'];
        $result = wp_verify_nonce( $nonce, 'brfr_import_export' );
        if( ! $result || ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }
        $plugin_slug = sanitize_text_field($_GET['plugin']);
        $backup_id = intval($_GET['backup']);
        if( empty($backup_id) || empty($plugin_slug) ) {
            echo 'Incorect data';
            wp_die();
        }
        $exist_ids = array();
        $transient_option = get_transient('brfr_bckp_' . $plugin_slug . '_' . $backup_id);
        $plugin_instance = apply_filters('brfr_plugin_get_instance_' . $plugin_slug, FALSE);
        if( $plugin_instance !== FALSE && $plugin_instance->import_export !== FALSE
            && $transient_option != false && is_array($transient_option) && ! empty($transient_option['import_export_date']) ) {
            $import = $plugin_instance->save_settings_callback( $transient_option );
            update_option($plugin_instance->values['settings_name'], $import);
            echo 'OK';
        } else {
            echo 'Backup cannot be used for plugin: ' . $plugin_slug . ' ID: ' . $backup_id;
        }
        wp_die();
    }
    public static function account_form($plugin_info) {
        $nonce = wp_create_nonce('brfr_import_export');
        do_action('berocket_enqueue_media');
        ?><div><span class="brfr_import_export_open button"><?php _e('Import/Export', 'BeRocket_domain') ?></span></div>
    <div class="brfr_import_export_block" style="display: none;">
        <form class="brfr_import_export_form">
            <h3><?php _e('Import/Export', 'BeRocket_domain') ?></h3>
            <input name="action" type="hidden" value="brfr_set_import_settings">
            <input name="nonce" type="hidden" value="<?php echo $nonce; ?>">
            <select class="brfr_import_export_form_plugin" name="plugin">
                <?php
                if( is_array($plugin_info) ) {
                    foreach($plugin_info as $plugin_info_single) {
                        $plugin_instance = apply_filters('brfr_plugin_get_instance_' . $plugin_info_single['plugin_name'], FALSE);
                        if( $plugin_instance !== FALSE && $plugin_instance->import_export !== FALSE ) {
                            echo "<option value='{$plugin_info_single['plugin_name']}'>{$plugin_info_single['norm_name']}</option>";
                        }
                    }
                }
                ?>
            </select>
            <div class="brfr_export_all">
                <h3><?php _e('EXPORT', 'BeRocket_domain') ?></h3>
                <div class="brfr_export_wrap">
                    <div class="brapf_export_loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i></div>
                    <textarea class="brfr_export" readonly></textarea>
                </div>
            </div>
            <div class="brfr_import_all">
                <h3><?php _e('IMPORT', 'BeRocket_domain') ?></h3>
                <div class="brfr_import_wrap">
                    <div class="brapf_import_loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i></div>
                    <textarea name="data" class="brfr_import"></textarea>
                </div>
                <button class="button brfr_import_send"><?php _e('Import', 'BeRocket_domain') ?></button>
            </div>
            <div class="brfr_import_links_step" style="display:none;">
                <h4><?php _e('Review links before import', 'BeRocket_domain') ?></h4>
                <div class="brfr_import_links_notice"></div>
                <div class="brfr_import_links_list"></div>
                <div class="brfr_import_links_actions">
                    <button type="button" class="button button-primary brfr_import_confirm"><?php _e('Confirm import', 'BeRocket_domain') ?></button>
                    <button type="button" class="button brfr_import_cancel"><?php _e('Cancel', 'BeRocket_domain') ?></button>
                </div>
            </div>
        </form>
        <div class="brfr_backup_all">
            <form class="brfr_backup_form" style="display: none;">
                <input name="action" type="hidden" value="brfr_restore_import_backups">
                <input name="nonce" type="hidden" value="<?php echo $nonce; ?>">
                <h3></h3>
                <div class="brfr_backup_form_select"></div>
                <button class="button brfr_backup_form_send"><?php _e('Restore', 'BeRocket_domain') ?></button>
                <i class="fa fa-spinner fa-spin" style="display:none;"></i>
                <i class="fa fa-check" style="display:none;"></i>
            </form>
        </div>
    </div><?php
    }
    public static function export_generate($plugin_slug) {
        $plugin_slug = sanitize_text_field($plugin_slug);
        $plugin_instance = apply_filters('brfr_plugin_get_instance_' . $plugin_slug, FALSE);
        if( $plugin_instance != FALSE && $plugin_instance->import_export !== FALSE ) {
            $current_option = $plugin_instance->get_option();
            $default_option = $plugin_instance->defaults;
            $export = self::check_settings_remove($current_option, $default_option);
            $export = self::type_check_export($export, $plugin_instance->import_export);
            return $export;
        }
        return array();
    }
    public static function export_generate_post($post_slug) {
        $post_slug = sanitize_text_field($post_slug);
        $post_instance = apply_filters('brfr_custom_post_get_instance_' . $post_slug, FALSE);
        if( $post_instance != FALSE && $post_instance->import_export !== FALSE ) {
            $posts_options = array();
            $default_option = $post_instance->default_settings;
            $posts = $post_instance->get_custom_posts();
            foreach($posts as $post_id) {
                $current_option = $post_instance->get_option($post_id);
                $post_export = self::check_settings_remove($current_option, $default_option);
                $post_export = self::type_check_export($post_export, $post_instance->import_export);
                $post_export['brfr_import_slug'] = get_post_field('post_name', $post_id);
                $post_export['brfr_import_title'] = get_the_title($post_id);
                $posts_options[$post_id] = $post_export;
            }
            return $posts_options;
        }
        return array();
    }
    public static function import_generate($plugin_slug, $options = array()) {
        $plugin_slug = sanitize_text_field($plugin_slug);
        $plugin_instance = apply_filters('brfr_plugin_get_instance_' . $plugin_slug, FALSE);
        if( $plugin_instance != FALSE && $plugin_instance->import_export !== FALSE ) {
            $default_option = $plugin_instance->defaults;
            $import = self::check_settings_create($options, $default_option);
            $import = self::type_check_import($import, $plugin_instance->import_export);
            return $import;
        }
        return array();
    }
    public static function import_generate_post($post_slug, $options = array(), $import_settings = array()) {
        $post_slug = sanitize_text_field($post_slug);
        $post_instance = apply_filters('brfr_custom_post_get_instance_' . $post_slug, FALSE);
        $import_settings = array_merge(array(
            'custom_post_slug_strategies' => array(),
        ), ( is_array($import_settings) ? $import_settings : array() ));
        if( $post_instance != FALSE && $post_instance->import_export !== FALSE && is_array($options) ) {
            foreach( $options as $post_id => $post_option ) {
                if( ! is_array($post_option) ) {
                    continue;
                }
                $post_strategy = self::get_custom_post_slug_strategy($post_slug, $post_id, $import_settings);
                $post_data = self::get_import_post_data($post_slug, $post_id, $post_option, array(
                    'custom_post_slug_strategy' => $post_strategy,
                ));
                unset($post_option['brfr_import_slug'], $post_option['brfr_import_title']);
                $import = self::check_settings_create($post_option, $post_instance->default_settings);
                $import = self::type_check_import($import, $post_instance->import_export);
                $exist_post_id = false;
                if( $post_strategy !== 'create_new' && ! empty($post_data['post_name']) ) {
                    $exist_post_id = self::get_post_by_slug($post_data['post_name'], $post_slug);
                }
                if( ! empty($exist_post_id) ) {
                    $post_data['ID'] = $exist_post_id;
                    wp_update_post($post_data);
                    $_POST[$post_instance->post_name] = $import;
                    $post_instance->wc_save_product_without_check($exist_post_id, get_post($exist_post_id));
                } else {
                    unset($post_data['post_name']);
                    $post_instance->create_new_post($post_data, $import);
                }
            }
        }
    }
    public static function get_import_post_data($post_slug, $post_id, $settings = array(), $import_settings = array()) {
        $import_settings = array_merge(array(
            'custom_post_slug_strategy' => 'update',
        ), ( is_array($import_settings) ? $import_settings : array() ));
        $post_title = '';
        if( ! empty($settings['brfr_import_title']) ) {
            $post_title = sanitize_text_field($settings['brfr_import_title']);
        } elseif( ! empty($settings['text']) ) {
            $post_title = sanitize_text_field($settings['text']);
        }
        if( empty($post_title) ) {
            $post_title = sprintf(__('Imported %1$s #%2$s', 'BeRocket_domain'), $post_slug, $post_id);
        }

        $post_name = '';
        if( $import_settings['custom_post_slug_strategy'] !== 'create_new' && ! empty($settings['brfr_import_slug']) ) {
            $post_name = sanitize_title($settings['brfr_import_slug']);
        }

        return array(
            'post_title' => $post_title,
            'post_name'  => $post_name,
        );
    }
    public static function process_import_package($plugin_slug, $package, $link_actions = array(), $import_options = array()) {
        $plugin_slug = sanitize_text_field($plugin_slug);
        $plugin_instance = apply_filters('brfr_plugin_get_instance_' . $plugin_slug, FALSE);
        if( $plugin_instance === FALSE || $plugin_instance->import_export === FALSE ) {
            return array(
                'success' => false,
                'message' => __('Import for this plugin not allowed', 'BeRocket_domain'),
            );
        }
        $data = ( ! empty($package['data']) && is_array($package['data']) ? $package['data'] : array() );
        $custom_posts_data = ( ! empty($package['custom_posts_data']) && is_array($package['custom_posts_data']) ? $package['custom_posts_data'] : array() );
        $service_data = ( ! empty($package['service_data']) && is_array($package['service_data']) ? $package['service_data'] : array() );
        $import_options = array_merge(array(
            'delete_existing_custom_posts' => array(),
            'custom_post_slug_strategies' => array(),
        ), ( is_array($import_options) ? $import_options : array() ));
        $import_options['delete_existing_custom_posts'] = self::sanitize_custom_post_type_flags($import_options['delete_existing_custom_posts']);
        $import_options['custom_post_slug_strategies'] = self::sanitize_custom_post_slug_strategies($import_options['custom_post_slug_strategies']);
        $data = self::apply_link_actions_to_settings($data, $link_actions, $service_data);
        $custom_posts_data = self::apply_link_actions_to_settings($custom_posts_data, $link_actions, $service_data);
        $import = self::import_generate($plugin_slug, $data);
        $has_custom_posts = ! empty($custom_posts_data);
        if( empty($import) && ! $has_custom_posts ) {
            return array(
                'success' => false,
                'message' => __('This data cannot be used for import', 'BeRocket_domain'),
            );
        }

        self::backup_plugin_options($plugin_slug, $plugin_instance);
        if( ! empty($import) ) {
            $import = $plugin_instance->save_settings_callback( $import );
            update_option($plugin_instance->values['settings_name'], $import);
        }
        if( $has_custom_posts ) {
            foreach($custom_posts_data as $post_name => $posts_data) {
                if( ! empty($import_options['delete_existing_custom_posts'][sanitize_key($post_name)]) ) {
                    self::delete_existing_custom_posts(array($post_name));
                }
                self::import_generate_post($post_name, $posts_data, $import_options);
            }
        }

        return array(
            'success' => true,
            'message' => __('Imported', 'BeRocket_domain'),
        );
    }
    public static function get_custom_post_slug_strategy($post_slug, $post_id, $import_settings = array()) {
        $map_key = self::get_custom_post_map_key($post_slug, $post_id);
        if( ! empty($import_settings['custom_post_slug_strategies'][$map_key]) ) {
            return $import_settings['custom_post_slug_strategies'][$map_key];
        }
        return 'update';
    }
    public static function sanitize_custom_post_slug_strategies($strategies = array()) {
        $result = array();
        if( ! is_array($strategies) ) {
            return $result;
        }
        foreach($strategies as $map_key => $strategy) {
            $map_key = sanitize_text_field($map_key);
            $strategy = sanitize_text_field($strategy);
            if( in_array($strategy, array('update', 'create_new')) ) {
                $result[$map_key] = $strategy;
            }
        }
        return $result;
    }
    public static function sanitize_custom_post_type_flags($flags = array()) {
        $result = array();
        if( ! is_array($flags) ) {
            return $result;
        }
        foreach($flags as $post_type => $flag) {
            $post_type = sanitize_key($post_type);
            $result[$post_type] = ! empty($flag);
        }
        return $result;
    }
    public static function prepare_custom_posts_for_review($custom_posts_data = array()) {
        $result = array();
        if( ! is_array($custom_posts_data) ) {
            return $result;
        }
        foreach($custom_posts_data as $post_type => $posts_data) {
            if( ! is_array($posts_data) ) {
                continue;
            }
            $post_type_label = self::get_custom_post_type_label($post_type);
            foreach($posts_data as $post_id => $post_settings) {
                if( ! is_array($post_settings) ) {
                    continue;
                }
                $title = '';
                if( ! empty($post_settings['brfr_import_title']) ) {
                    $title = $post_settings['brfr_import_title'];
                } elseif( ! empty($post_settings['text']) ) {
                    $title = $post_settings['text'];
                } else {
                    $title = sprintf(__('Imported %1$s #%2$s', 'BeRocket_domain'), $post_type, $post_id);
                }
                $slug = ( ! empty($post_settings['brfr_import_slug']) ? $post_settings['brfr_import_slug'] : '' );
                $existing_post_id = false;
                if( ! empty($slug) ) {
                    $existing_post_id = self::get_post_by_slug($slug, $post_type);
                }
                if( empty($existing_post_id) ) {
                    continue;
                }
                $result[] = array(
                    'map_key'   => self::get_custom_post_map_key($post_type, $post_id),
                    'post_type_key' => sanitize_key($post_type),
                    'post_id'   => (string) $post_id,
                    'title_with_id' => sprintf('%1$s (ID: %2$s)', $title, $post_id),
                );
            }
        }
        return $result;
    }
    public static function get_custom_post_map_key($post_type, $post_id) {
        return sanitize_key($post_type . '__' . $post_id);
    }
    public static function get_custom_post_type_label($post_type) {
        $post_type = sanitize_text_field($post_type);
        $post_instance = apply_filters('brfr_custom_post_get_instance_' . $post_type, FALSE);
        if( $post_instance !== FALSE ) {
            if( ! empty($post_instance->post_settings['labels']['name']) ) {
                return $post_instance->post_settings['labels']['name'];
            }
            if( ! empty($post_instance->post_settings['label']) ) {
                return $post_instance->post_settings['label'];
            }
        }

        return $post_type;
    }
    public static function prepare_custom_post_groups_for_review($custom_posts_data = array()) {
        $groups = array();
        foreach(self::prepare_custom_posts_for_review($custom_posts_data) as $post_item) {
            $post_type_key = $post_item['post_type_key'];
            if( empty($groups[$post_type_key]) ) {
                $groups[$post_type_key] = array(
                    'post_type' => $post_item['post_type'],
                    'post_type_label' => $post_item['post_type_label'],
                    'post_type_key' => $post_type_key,
                    'posts' => array(),
                );
            }
            $groups[$post_type_key]['posts'][] = $post_item;
        }

        return array_values($groups);
    }
    public static function apply_link_actions_to_settings($settings, $link_actions = array(), $service_data = array()) {
        if( is_array($settings) ) {
            foreach($settings as $key => $value) {
                $settings[$key] = self::apply_link_actions_to_settings($value, $link_actions, $service_data);
            }
            return $settings;
        }

        if( ! is_string($settings) || $settings === '' ) {
            return $settings;
        }

        foreach($link_actions as $original_url => $action_data) {
            if( strpos($settings, $original_url) === false ) {
                continue;
            }
            $replacement = self::get_link_replacement($original_url, $action_data, $service_data);
            if( $replacement !== false ) {
                $settings = str_replace($original_url, $replacement, $settings);
            }
        }

        return $settings;
    }
    public static function get_link_replacement($original_url, $action_data = array(), $service_data = array()) {
        $action = ( ! empty($action_data['action']) ? $action_data['action'] : 'keep' );
        switch($action) {
            case 'replace_site':
                return self::build_current_site_url($original_url, ( ! empty($service_data['site_url']) ? $service_data['site_url'] : '' ));
            case 'media':
            case 'custom':
                return ( ! empty($action_data['replacement']) ? esc_url_raw($action_data['replacement']) : false );
            case 'keep':
            default:
                return false;
        }
    }
    public static function build_current_site_url($url, $site_url = '') {
        $url = trim((string) $url);
        if( empty($url) ) {
            return $url;
        }

        $source_site_url = self::normalize_site_url($site_url);
        $current_site_url = self::normalize_site_url(home_url('/'));
        if( empty($current_site_url) ) {
            return $url;
        }

        if( empty($source_site_url) ) {
            $url_parts = wp_parse_url($url);
            if( empty($url_parts['scheme']) || empty($url_parts['host']) ) {
                return $url;
            }
            $path = ( ! empty($url_parts['path']) ? $url_parts['path'] : '' );
            $path_parts = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
            $source_site_url = $url_parts['scheme'] . '://' . $url_parts['host'];
            if( isset($url_parts['port']) ) {
                $source_site_url .= ':' . $url_parts['port'];
            }
            if( ! empty($path_parts) ) {
                $source_site_url .= '/' . $path_parts[0];
            }
            $source_site_url = self::normalize_site_url($source_site_url);
        }

        if( empty($source_site_url) ) {
            return $url;
        }

        return str_replace($source_site_url, $current_site_url, $url);
    }
    public static function get_export_service_data($plugin_instance = false) {
        return array(
            'site_url' => self::normalize_site_url(home_url('/')),
        );
    }
    public static function get_import_service_data(&$data) {
        $service_data = array();
        if( is_array($data) && ! empty($data['brfr_service']) && is_array($data['brfr_service']) ) {
            $service_data = $data['brfr_service'];
            unset($data['brfr_service']);
        }

        return array(
            'site_url' => ( ! empty($service_data['site_url']) ? $service_data['site_url'] : '' ),
            'current_plugin_url' => ( ! empty($service_data['current_plugin_url']) ? $service_data['current_plugin_url'] : '' ),
        );
    }
    public static function get_import_custom_posts_data(&$data) {
        $custom_posts_data = array();
        if( is_array($data) && ! empty($data['import_custom_posts']) && is_array($data['import_custom_posts']) ) {
            $custom_posts_data = $data['import_custom_posts'];
            unset($data['import_custom_posts']);
        }

        return $custom_posts_data;
    }
    public static function collect_import_links($data, $custom_posts_data = array(), $service_data = array()) {
        $links = array();
        self::collect_links_from_settings($data, $links, $service_data);
        self::collect_links_from_settings($custom_posts_data, $links, $service_data);
        return array_values($links);
    }
    public static function collect_links_from_settings($settings, &$links, $service_data = array()) {
        if( is_array($settings) ) {
            foreach($settings as $value) {
                self::collect_links_from_settings($value, $links, $service_data);
            }
            return;
        }
        if( ! is_string($settings) || $settings === '' ) {
            return;
        }
        if( preg_match_all('#https?://[^\s"\'<>\)\(]+#i', $settings, $matches) ) {
            foreach($matches[0] as $url) {
                $url = self::normalize_detected_url($url);
                if( empty($url) || isset($links[$url]) ) {
                    continue;
                }
                if( ! empty($service_data['current_plugin_url']) && strpos($url, $service_data['current_plugin_url']) === 0 ) {
                    continue;
                }
                $links[$url] = array(
                    'url'  => $url,
                    'type' => ( self::is_image_url($url) ? 'image' : 'link' ),
                );
            }
        }
    }
    public static function is_image_url($url) {
        $path = wp_parse_url($url, PHP_URL_PATH);
        if( empty($path) ) {
            $path = $url;
        }

        return (bool) preg_match('#\.(jpg|jpeg|png|gif|webp|svg|bmp|ico)$#i', $path);
    }
    public static function normalize_detected_url($url) {
        $url = trim((string)$url);
        $url = rtrim($url, " \t\n\r\0\x0B,;:");
        $url = esc_url_raw($url);

        return $url;
    }
    public static function replace_plugin_url_with_token_in_settings($settings, $plugin_url = '') {
        if( is_array($settings ) ) {
            foreach($settings as $key => $value) {
                $settings[$key] = self::replace_plugin_url_with_token_in_settings($value, $plugin_url);
            }
            return $settings;
        }
        if( ! is_string($settings) || $settings === '' ) {
            return $settings;
        }
        $plugin_url = self::normalize_site_url($plugin_url);
        if( empty($plugin_url) ) {
            return $settings;
        }
        return str_replace($plugin_url, self::PLUGIN_URL_TOKEN, $settings);
    }
    public static function replace_plugin_token_with_url_in_settings($settings, $plugin_url = '') {
        if( is_array($settings ) ) {
            foreach($settings as $key => $value) {
                $settings[$key] = self::replace_plugin_token_with_url_in_settings($value, $plugin_url);
            }
            return $settings;
        }
        if( ! is_string($settings) || $settings === '' ) {
            return $settings;
        }
        $plugin_url = self::normalize_site_url($plugin_url);
        if( empty($plugin_url) ) {
            return $settings;
        }
        return str_replace(self::PLUGIN_URL_TOKEN, $plugin_url, $settings);
    }
    public static function get_plugin_url($plugin_instance) {
        if( ! empty($plugin_instance->info['plugin_file']) ) {
            return self::normalize_site_url(plugins_url('/', $plugin_instance->info['plugin_file']));
        }
        return '';
    }
    public static function get_import_transient_name($import_token) {
        return 'brfr_import_' . get_current_user_id() . '_' . sanitize_key($import_token);
    }
    public static function store_import_package($package) {
        $import_token = wp_generate_password(20, false, false);
        set_transient(self::get_import_transient_name($import_token), $package, HOUR_IN_SECONDS);
        return $import_token;
    }
    public static function get_import_package($import_token) {
        if( empty($import_token) ) {
            return false;
        }
        return get_transient(self::get_import_transient_name($import_token));
    }
    public static function backup_plugin_options($plugin_slug, $plugin_instance) {
        $old_option = $plugin_instance->get_option();
        $save_id_use = '1';
        $save_id_time = false;
        foreach(array('1', '2', '3') as $save_id) {
            $transient_option = get_transient('brfr_bckp_' . $plugin_slug . '_' . $save_id);
            if( $transient_option == false ) {
                $save_id_use = $save_id;
                break;
            } elseif( $save_id_time === false || $transient_option['import_export_date'] < $save_id_time ) {
                $save_id_use = $save_id;
                $save_id_time = $transient_option['import_export_date'];
            }
        }
        $old_option['import_export_date'] = time();
        set_transient( 'brfr_bckp_' . $plugin_slug . '_' . $save_id_use, $old_option, DAY_IN_SECONDS );
    }
    public static function delete_existing_custom_posts($post_types = array()) {
        if( ! is_array($post_types) ) {
            return;
        }
        foreach($post_types as $post_type) {
            $post_type = sanitize_text_field($post_type);
            if( empty($post_type) ) {
                continue;
            }
            $post_instance = apply_filters('brfr_custom_post_get_instance_' . $post_type, FALSE);
            if( $post_instance === FALSE ) {
                continue;
            }
            $post_ids = $post_instance->get_custom_posts(array(
                'post_status' => array('publish', 'draft', 'pending', 'future', 'private', 'trash'),
            ));
            if( empty($post_ids) || ! is_array($post_ids) ) {
                continue;
            }
            foreach($post_ids as $post_id) {
                if( current_user_can('delete_post', $post_id) ) {
                    wp_delete_post($post_id, true);
                }
            }
        }
    }
    public static function normalize_site_url($url) {
        $url = trim((string)$url);
        if( empty($url) ) {
            return '';
        }

        return untrailingslashit($url);
    }
    public static function check_settings_create($option, $default) {
        if( is_array($option) && is_array($default) ) {
            foreach( $option as $key => $value ) {
                $key = sanitize_text_field($key);
                if( isset( $default[$key] ) && is_array($value) && is_array($default[$key]) ) {
                    $default[$key] = self::check_settings_create($value, $default[$key]);
                } elseif( is_array($value) ) {
                    $default[$key] = self::check_settings_create($value, array());
                } else {
                    $default[$key] = sanitize_textarea_field($value);
                }
            }
        } else {
            $default = $option;
        }
        return $default;
    }
    public static function check_settings_remove($option, $default) {
        $new_option = array();
        if( is_array($option) && is_array($default) ) {
            foreach( $option as $key => $value ) {
                if( isset( $default[$key] ) ) {
                    if( is_array($value) && is_array($default[$key]) ) {
                        $new_value = self::check_settings_remove($value, $default[$key]);
                        if( is_array($new_value) && count($new_value) > 0 ) {
                            $new_option[$key] = $new_value;
                        }
                    } elseif( $value !== $default[$key] ) {
                        $new_option[$key] = $value;
                    }
                } else {
                    $new_option[$key] = $value;
                }
            }
        } else {
            $new_option = $option;
        }
        return $new_option;
    }
    
    public static function type_check_export($options, $types = array()) {
        foreach($types as $key => $type) {
            if( is_array($type) && isset($options[$key]) ) {
                if( isset($type['export_type']) ) {
                    switch($type['export_type']) {
                        case 'remove':
                            unset($options[$key]);
                            break;
                        case 'post':
                        case 'taxonomy':
                            $options[$key] = self::id_to_slug($options[$key], $type['export_type'], $type);
                            break;
                        case 'conditions':
                            include(__DIR__ . '/import_export_conditions_export.php');
                            break;
                    }
                } else {
                    $options[$key] = self::type_check_export($options[$key], $type);
                }
            }
        }
        return $options;
    }
    public static function type_check_import($options, $types = array()) {
        foreach($types as $key => $type) {
            if( is_array($type) && isset($options[$key]) ) {
                if( isset($type['export_type']) ) {
                    switch($type['export_type']) {
                        case 'post':
                        case 'taxonomy':
                            $options[$key] = self::slug_to_id($options[$key], $type['export_type'], $type);
                            break;
                        case 'conditions':
                            include(__DIR__ . '/import_export_conditions_import.php');
                            break;
                    }
                } else {
                    $options[$key] = self::type_check_import($options[$key], $type);
                }
            }
        }
        return $options;
    }
    public static function id_to_slug($id, $type, $additional = false) {
        $result = false;
        switch($type) {
            case 'post':
                $post = false;
                if( ! empty($additional['post_type']) ) {
                    $post = get_post($id);
                    if( empty($post) || $post->post_type !== $additional['post_type'] ) {
                        $post = false;
                    }
                } else {
                    $post = get_post($id);
                }
                if( ! empty($post) ) {
                    $result = array('s' => $post->post_name);
                }
                break;
            case 'taxonomy':
                $field = 'term_id';
                if( isset($additional['field']) ) {
                    $field = $additional['field'];
                }
                $taxonomy = 'product_cat';
                if( isset($additional['taxonomy']) ) {
                    $taxonomy = $additional['taxonomy'];
                }
                $term = get_term_by($field, $id, $taxonomy);
                if( ! empty($term) ) {
                    $result = array('s' => $term->slug, 'tx' => $taxonomy);
                }
                break;
            default:
                break;
        }
        return $result;
    }
    public static function slug_to_id($data, $type, $additional = false) {
        $result = false;
        if( ! is_array($data) ) {
            return $result;
        }
        switch($type) {
            case 'post':
                if( ! empty($data['s']) ) {
                    $post_type = ( ! empty($additional['post_type']) ? $additional['post_type'] : '' );
                    $post_id = self::get_post_by_slug($data['s'], $post_type);
                    if( ! empty($post_id) ) {
                        $result = $post_id;
                    }
                }
                break;
            case 'taxonomy':
                if( ! empty($data['s']) && ! empty($data['tx']) ) {
                    $taxonomy = 'product_cat';
                    if( isset($additional['taxonomy']) ) {
                        $taxonomy = $additional['taxonomy'];
                    }
                    $term = get_term_by('slug', $data['s'], $taxonomy);
                    if( ! empty($term) ) {
                        $field = 'term_id';
                        if( isset($additional['field']) ) {
                            $field = $additional['field'];
                        }
                        $result = $term->$field;
                    }
                }
                break;
        }
        return $result;
    }
    public static function get_post_by_slug($slug, $post_type = '') {
        $args = array(
            'name'           => $slug,
            'posts_per_page' => 1,
        );
        if( ! empty($post_type) ) {
            $args['post_type'] = $post_type;
        }
        $posts = get_posts($args);

        if ($posts) {
            return $posts[0]->ID;
        } else {
            return false;
        }
    }
}
new BeRocket_import_export();
