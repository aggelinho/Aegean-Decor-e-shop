<div class="berocket_filter_groups">
    <div class="berocket_filter_groups_content">
        <div class="braapf_attribute_setup_flex">
            <div class="braapf_filter_title braapf_full_select_full">
                <label class="braapf_filter_title_label" for="braapf_filter_title"><?php _e('Custom CSS class', 'BeRocket_AJAX_domain'); ?></label>
                <input id="braapf_filter_title" type="text" name="<?php echo $post_name; ?>[custom_class]"
                       value="<?php echo br_get_value_from_array($filters, 'custom_class'); ?>" placeholder="Empty" />
            </div>
        </div>

        <?php do_action('berocket_aapf_filters_group_settings', $filters, $post_name, $post); ?>

        <div class="berocket_group_filters_content">
            <h3><?php _e('Filters In Group', 'BeRocket_AJAX_domain'); ?></h3>
            <?php
            $filters_correct = 0;
            $query = new WP_Query(array('post_type' => 'br_product_filter', 'nopaging' => true));
            if ( $query->have_posts() ) {
                echo '
                <div class="berocket_group_filters_choose_filter"><div><select class="berocket_filter_list">';
                while ( $query->have_posts() ) {
                    $query->the_post();
                    echo '<option data-name="' . get_the_title() . '" value="' . get_the_id() . '">' . get_the_title() . ' (ID:' . get_the_id() . ')</option>';
                }
                echo '</select>';
                echo '<a class="button berocket_add_filter_to_group" href="#add_filter">' . __('Add filter', 'BeRocket_AJAX_domain') . '</a>';
                echo '</div>
                      <a href="' . admin_url('edit.php?post_type=br_product_filter') . '" target="_blank">' . __('Manage filters', 'BeRocket_AJAX_domain') . '</a>';
                echo '</div>';
                wp_reset_postdata();
                $filters_correct++;
            }
            $errors = array();
            if( isset($filters['filters']) && is_array($filters['filters']) ) {
                echo '<ul class="berocket_filter_added_list" data-name="' . $post_name . '[filters][]" data-url="' . admin_url('post.php') . '">';
                foreach($filters['filters'] as $filter) {
                    $filter_id = $filter;
                    $filter_post = get_post($filter_id);
                    if( ! empty($filter_post) ) {
                        ?>
                        <li class="berocket_filter_added_<?=$filter_id?>">
                            <fa class="fa fa-bars"></fa>
                            <input type="hidden" name="<?=$post_name?>[filters][]" value="<?=$filter_id?>">
                            <div class="filter_name"><?=$filter_post->post_title?> <small>ID:<?=$filter_id?></small></div>
                            <div class="berocket_filter_added_list_actions">
                                <div class="berocket_hidden_clickable_options">
                                    <label for="filters_data_<?=$filter_id?>_width"><?php
                                        _e('Width', 'BeRocket_AJAX_domain')?></label>
                                    <input id="filters_data_<?=$filter_id?>_width" type="text"
                                           name="<?=$post_name?>[filters_data][<?=$filter_id?>][width]"
                                           value="<?=br_get_value_from_array(
                                                    $filters,
                                                    array('filters_data', $filter_id, 'width')
                                                   )?>" placeholder="100%" />
                                </div>
                                <a class="berocket_edit_filter fas fa-pencil-alt" target="_blank"
                                   href="<?=get_edit_post_link( $filter_id )?>"></a>
                                <i class="fa fa-times"></i>
                            </div>
                        </li>
                        <?php
                    } else {
                        $errors[] = $filter_id;
                    }
                }
                echo '</ul>';
            }
            if( count($errors) > 0 ) {
                BeRocket_error_notices::add_plugin_error(1, 'The filter was removed but is still in the group.', array(
                    'filter_ids'   => $errors
                ));
            }
            if($filters_correct == 0) {
                echo '<p style="font-size: 24px;">' . __('No filter has been created. First ', 'BeRocket_AJAX_domain')
                . ' <a href="' . admin_url('post-new.php?post_type=br_product_filter') . '">' . __('Create a Filter', 'BeRocket_AJAX_domain') . '</a></p>';
            }
            $popup_text = '<p style="font-size:24px;">'
            . __('The group do not have filters. Please add filters before saving it.', 'BeRocket_AJAX_domain')
            . '</p>'
            . '<p style="font-size:24px;">' . __('You can create new filters or edit them on', 'BeRocket_AJAX_domain')
            . ' <a href="' . admin_url('edit.php?post_type=br_product_filter') . '">' . __('FILTERS PAGE', 'BeRocket_AJAX_domain') . '</a></p>';
            BeRocket_popup_display::add_popup(
                array(
                    'height'        => '250px',
                    'width'         => '700px',
                ),
                $popup_text,
                array('event_new' => array('type' => 'event', 'event' => 'braapf_group_required_filters'))
            );
            ?>
        </div>
    </div>
    <div class="berocket_group_docs">
        <div class="berocket_group_docs_content">
            <p class="docs_title"><?php _e('Display filters in line', 'BeRocket_AJAX_domain'); ?></p>
            <p><?php _e('Use to show filters in a line where a maximum number of filters per 
                line is configurable.', 'BeRocket_AJAX_domain'); ?></p>

            <p class="docs_title"><?php _e('Show title only', 'BeRocket_AJAX_domain'); ?></p>
            <p><?php _e('Only title will be visible. Filter will be displayed after click on 
                title and hide after click everywhere else.', 'BeRocket_AJAX_domain'); ?></p>

            <p class="docs_title"><?php _e('Display filters on mouse over', 'BeRocket_AJAX_domain'); ?></p>
            <p><?php _e('Display on mouse over and hide on mouse leave.', 'BeRocket_AJAX_domain'); ?></p>

            <p class="docs_title"><?php _e('Collapsed on page load', 'BeRocket_AJAX_domain'); ?></p>
            <p><?php _e('Collapse group on page load and show icon instead. When icon is clicked 
                filters will be shown.', 'BeRocket_AJAX_domain'); ?></p>

            <p class="docs_title"><a href="https://docs.berocket.com/plugin/woocommerce-ajax-products-filter#filter-group-settings"
                                     target="_blank"><?php _e('Group settings docs', 'BeRocket_AJAX_domain'); ?></a></p>
        </div>
    </div>
</div>
