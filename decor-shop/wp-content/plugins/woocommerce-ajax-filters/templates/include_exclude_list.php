<?php extract($berocket_var_exclude_list); ?>
<div>
    <div>
        <?php if ( is_array(berocket_isset($terms)) && count($terms) > 0 ) {
            foreach( $terms as $term ) { ?>
                <div class="element-depth-<?php echo (empty($term->depth) ? '0' : $term->depth); ?>">
                    <label>
                        <input type="checkbox" value="<?php echo berocket_isset($term, 'term_id'); ?>" name="%field_name%[]"<?php if( in_array( $term->term_id, $selected ) ) echo ' checked'; ?>>
                        <?php echo berocket_isset($term, 'name') ?>
                    </label>
                </div>
                <?php
            }
        } else {
            $taxonomy_data = get_taxonomy($taxonomy);
            $taxonomy_name = '';
            if( is_a($taxonomy_data, 'WP_Taxonomy') ) {
                $taxonomy_name = $taxonomy_data->label;
            }
            echo '<div style="font-size: 18px;text-align: center;">';
            printf(__('Attribute "%s" do not have values. Please add values for attribute "%s"', 'BeRocket_AJAX_domain'), $taxonomy_name, $taxonomy_name);
            echo '</div>';
        }
        ?>
    </div>
</div>
