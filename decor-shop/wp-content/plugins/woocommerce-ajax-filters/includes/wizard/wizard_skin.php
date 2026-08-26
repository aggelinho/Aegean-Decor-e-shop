<?php
class BeRocket_custom_upgrader_skin extends Plugin_Installer_Skin {
    function before() {
        return;
    }
    function after() {
        return;
    }
    function feedback( $feedback, ...$args ) {
        return;
    }
    function hide_process_failed($wp_error) {
        return true;
    }
}