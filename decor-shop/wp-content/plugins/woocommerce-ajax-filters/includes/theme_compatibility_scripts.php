<?php
if( ! class_exists('BeRocket_AAPF_compatibility_theme_scripts') ) {
    class BeRocket_AAPF_compatibility_theme_scripts {
        function __construct() {
            add_action('bapf_class_ready', array($this, 'init'));
        }
        function init() {
            $option = BeRocket_AAPF::get_aapf_option();
            if( ! empty($option['selectors_preset']) ) {
                $selectors_preset = sanitize_text_field($option['selectors_preset']);
                if( method_exists($this, $selectors_preset) ) {
                    $this->$selectors_preset();
                }
            }
        }
        function Enfold() {
            add_action('wp_footer', array($this, 'enfold_js_update'));
        }
        function The7() {
            add_action('wp_footer', array($this, 'the7_js_update'));
        }
        function enfold_js_update() {
?><script>
function bapf_enfold_ordering_update() {
    let links = jQuery('.avia-product-sorting .avia-product-sorting-link');
    links.on('click', function() {
        let el = jQuery(this)
          , href = el.attr('data-href')
          , li = el.closest('li');
        if (li.hasClass('current-param')) {
            return
        }
        ;if ('undefined' != typeof href && '' != href) {
            window.location.href = href
        }
    })
}
jQuery(document).on('berocket_ajax_products_loaded', bapf_enfold_ordering_update);
</script><?php
        }
        function the7_js_update() {
?><script>
function bapf_the7_ordering_update() {
    jQuery('.lazy-load').each(function() {
        jQuery(this).attr('src', jQuery(this).data('src'));
        jQuery(this).attr('srcset', jQuery(this).data('srcset'));
        jQuery(this).removeClass('lazy-load');
    });
}
jQuery(document).on('berocket_ajax_products_loaded', bapf_the7_ordering_update);
</script><?php
        }
    }
    new BeRocket_AAPF_compatibility_theme_scripts();
}