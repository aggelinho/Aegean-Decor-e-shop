<?php extract($berocket_query_var_color); ?>
<p>
    <label>
        <input class="clrimg_use_attrval" type="checkbox" value="1"
               name="br_product_filter[clrimg_use_attrval]"<?php if( ! empty($clrimg_use_attrval) ) echo ' checked'; ?>>
        <?php
        _e('Use attribute value as', 'BeRocket_AJAX_domain');
        echo ' ' . ( $type == 'color' ? __('color', 'BeRocket_AJAX_domain') : __('image', 'BeRocket_AJAX_domain') );
        ?>
    </label>
</p>
<div class="color_image_container">
    <div class="color_image_inner">
        <?php
        if ( is_array( berocket_isset( $terms ) ) and count( $terms ) > 0 ) {
            if ( $type == 'color' ) {
                foreach ( $terms as $term ) {
                    // use hook to replace default output
                    $color_term_selector = apply_filters( 'berocket_aapf_color_term_select_line', '', $term );
                    if ( ! empty( $color_term_selector ) ) {
                        echo $color_term_selector;
                        continue;
                    }
	                $color_meta = berocket_term_get_metadata( $term, 'color' );
                    ?>
                    <div class="color_image_element element-depth-<?=( empty( $term->depth ) ? '0' : $term->depth )?>">
                        <div class="br_colorpicker_field_name"><?=berocket_isset( $term, 'name' )?></div>
                        <div class="br_colorpicker_field" data-color="<?=
                            br_get_value_from_array( $color_meta, 0, 'ffffff' )?>"></div>
                        <input class="br_colorpicker_field_input" type="hidden"
                               value="<?=br_get_value_from_array( $color_meta, 0 )?>"
                               name="br_widget_color[color][<?=$term->term_id?>]" />
                    </div>
                    <?php
                }
                if ( ! empty( $load_script ) ) {
                    ?>
                    <script>
                        (function ($) {
                            var colPick_timer = setInterval(function() {
                                if (typeof $('.br_colorpicker_field').colpick == 'function') {
                                    clearInterval(colPick_timer);
                                    $('.br_colorpicker_field').each(function (i,o) {
                                        var color = $(o).data('color');
                                        color = color+'';
                                        color = color.replace('#', '');
                                        $(o).data('color', color);
                                        $(o).css('backgroundColor', '#'+$(o).data('color'));
                                        if( ! $(o).is('.colorpicker_removed') ) {
                                            $(o).next().val($(o).data('color'));
                                        }
                                        $(o).colpick({
                                            layout: 'hex',
                                            submit: 0,
                                            color: '#'+$(o).data('color'),
                                            onChange: function(hsb,hex,rgb,el,bySetColor) {
                                                $(el).removeClass('colorpicker_removed');
                                                $(el).css('backgroundColor', '#'+hex).next().val(hex).trigger('change');
                                            }
                                        });
                                    });
                                    jQuery('.br_colorpicker_field .colorpicker_remove').on('click', function(event) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        jQuery(this).parent().css('backgroundColor', '#ffffff').colpickSetColor('#ffffff').addClass('colorpicker_removed');
                                        jQuery(this).parent().next().val('');
                                    });
                                }
                            }, 500);
                        })(jQuery);
                    </script>
                    <?php
                }
            } elseif ( $type == 'image' ) {
                ?>
                <table>
                    <?php
                    foreach( $terms as $term ) {
                        $color_meta = berocket_term_get_metadata($term, $type);
                        ?>
                        <tr>
                            <td class="br_aapf_settings_fa"><?php echo '<strong>' . $term->name . '</strong> ' . br_fontawesome_image("br_widget_color[".$term->term_id."]", br_get_value_from_array($color_meta, 0)); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <?php
            }
        } else {
            $taxonomy_data = get_taxonomy($taxonomy);
            $taxonomy_name = '';
            if ( is_a( $taxonomy_data, 'WP_Taxonomy' ) ) {
                $taxonomy_name = $taxonomy_data->label;
            }
            echo '<div style="font-size: 18px;text-align: center;">';
            printf(
                    __('Attribute "%s" do not have values. Please add values for attribute "%s"', 'BeRocket_AJAX_domain'),
                    $taxonomy_name,
                    $taxonomy_name
            );
            echo '</div>';
        }
        ?>
    </div>
</div>
<script>
    function bapf_clrimg_use_attrval_change() {
        if( jQuery('.clrimg_use_attrval').prop('checked') ) {
            jQuery('.clrimg_use_attrval').parent().parent().next().hide();
        } else {
            jQuery('.clrimg_use_attrval').parent().parent().next().show();
        }
    }
    jQuery('.clrimg_use_attrval').on('change', bapf_clrimg_use_attrval_change);
    bapf_clrimg_use_attrval_change();
</script>
