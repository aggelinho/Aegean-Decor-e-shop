<?php
if( ! class_exists('BeRocket_AAPF_compat_siteorigin_builder') ) {
    class BeRocket_AAPF_compat_siteorigin_builder {
        public $bapf_status = false;
        function __construct() {
            add_action('in_widget_form', array($this, 'add_option'), 10, 3);
            add_action('siteorigin_panels_widget_instance', array($this, 'modify_query'), 10, 3);
            add_action('siteorigin_panels_widget_classes', array($this, 'modify_classes'), 10, 4);
            add_filter('aapf_localize_widget_script', array($this, 'modify_products_selector'));
        }
        function modify_classes($classes, $widget_class, $instance, $widget_info) {
            if( $widget_info['class'] == 'SiteOrigin_Panels_Widgets_PostLoop' ) {
                $enabled = braapf_is_shortcode_must_be_filtered();
                if( ! empty($instance['bapf_apply']) && $instance['bapf_apply'] == 'enable' ) {
                    $enabled = true;
                } elseif( ! empty($instance['bapf_apply']) && $instance['bapf_apply'] == 'disable' ) {
                    $enabled = false;
                }
                if( $enabled ) {
                    $classes[] = 'bapf_siteorigin_apply';
                }
            }
            return $classes;
        }
        function modify_query($instance, $the_widget, $widget_class) {
            if( $the_widget->id_base == 'siteorigin-panels-postloop' || $the_widget->id_base == 'woocommerce_products' ) {
                $enabled = braapf_is_shortcode_must_be_filtered();
                if( ! empty($instance['aapf_apply']) )  {
                    $instance['bapf_apply'] = $instance['aapf_apply'];
                }
                if( ! empty($instance['bapf_apply']) && $instance['bapf_apply'] == 'enable' ) {
                    $enabled = true;
                } elseif( ! empty($instance['bapf_apply']) && $instance['bapf_apply'] == 'disable' ) {
                    $enabled = false;
                }
                do_action('brapf_next_shortcode_apply_action', array('apply' => $enabled));
            }
            return $instance;
        }
        function add_option($widget, $return, $instance) {
            if( $widget->id_base == 'siteorigin-panels-postloop' ) {
                $bapf_apply = isset( $instance['bapf_apply'] ) ? $instance['bapf_apply'] : '';
                $options = array(
                    'default' => __( 'Default', 'BeRocket_AJAX_domain' ),
                    'enable'  => __( 'Enable', 'BeRocket_AJAX_domain' ),
                    'disable' => __( 'Disable', 'BeRocket_AJAX_domain' ),
                );
                ?>
<div class="bapf_siteorigin_widget_settings">
    <label for="<?php echo esc_attr( $widget->get_field_id( 'bapf_apply' ) ); ?>"><?php _e( 'Apply BeRocket AJAX Filters', 'BeRocket_AJAX_domain' ); ?></label>
    <select class="widefat" id="<?php echo esc_attr( $widget->get_field_id( 'bapf_apply' ) );?>" name="<?php echo esc_attr( $widget->get_field_name( 'bapf_apply' ) ); ?>">
        <?php
        foreach($options as $value => $name) {
            echo '<option value="' .$value. '"'.($value == $bapf_apply ? ' checked' : '').'>' . $name . '</option>';
        }
        ?>
    </select>
</div>
<script>
function bapf_siteorigin_widget_settings_hide() {
    jQuery('.bapf_siteorigin_widget_settings').each(function() {
        var options = jQuery(this).parent().find('.siteorigin-widget-field-post_type select option:selected');
        if( options.length == 1 && options.val() == 'product' ) {
            jQuery(this).show();
        } else {
            jQuery(this).hide();
        }
    });
}
bapf_siteorigin_widget_settings_hide();
jQuery(document).on('change', '.siteorigin-widget-field-post_type select', bapf_siteorigin_widget_settings_hide);
</script>
<?php
            }
        }
        function modify_products_selector($args) {
            if( ! empty($args['products_holder_id']) ) {
                $args['products_holder_id'] .= ',';
            }
            $args['products_holder_id'] .= '.bapf_siteorigin_apply';
            if( ! empty($args['pagination_class']) ) {
                $args['pagination_class'] .= ',';
            }
            $args['pagination_class'] .= '.bapf_siteorigin_apply  .pagination';
            return $args;
        }
    }
    new BeRocket_AAPF_compat_siteorigin_builder();
}