<?php
if( ! class_exists('BeRocket_AAPF_compat_wc_widgets') ) {
    class BeRocket_AAPF_compat_wc_widgets {
        function __construct() {
            add_action('in_widget_form', array($this, 'form'), 10, 3);
            add_filter('widget_update_callback', array($this, 'update'), 10, 4);
            add_filter('widget_display_callback', array($this, 'apply_filters'), 10, 2);
        }
        function form($widget, $return, $instance) {
            if( ! empty($widget->widget_id) && $widget->widget_id == 'woocommerce_products' ) {
                $options = array(
                    'default'   => esc_html__( 'Default', 'BeRocket_AJAX_domain' ),
                    'enable'    => esc_html__( 'Enable', 'BeRocket_AJAX_domain' ),
                    'disable'   => esc_html__( 'Disable', 'BeRocket_AJAX_domain' )
                );
                $key = 'aapf_apply';
                $value = isset( $instance[ $key ] ) ? $instance[ $key ] : 'default';
                ?>
                <p>
                    <label for="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>"><?php echo esc_html__( 'Apply BeRocket AJAX Filters', 'BeRocket_AJAX_domain' );?></label>
                    <select class="widefat" id="<?php echo esc_attr( $widget->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( $key ) ); ?>">
                        <?php foreach ( $options as $option_key => $option_value ) : ?>
                            <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $value ); ?>><?php echo esc_html( $option_value ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <?php
            }
        }
        function update($instance, $new_instance, $old_instance, $widget) {
            if( ! empty($widget->widget_id) && $widget->widget_id == 'woocommerce_products' ) {
                $instance['aapf_apply'] = isset( $new_instance['aapf_apply'] ) ? sanitize_text_field( $new_instance['aapf_apply'] ) : 'default';
            }
            return $instance;
        }
        function apply_filters($instance, $widget) {
            if( ! empty($widget->widget_id) && $widget->widget_id == 'woocommerce_products' ) {
                $enabled = false;
                if( ! empty($instance['aapf_apply']) && $instance['aapf_apply'] == 'enable' ) {
                    $enabled = true;
                }
                do_action('brapf_next_shortcode_apply_action', array('apply' => $enabled));
            }
            return $instance;
        }
    }
    new BeRocket_AAPF_compat_wc_widgets();
}
        