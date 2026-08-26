<?php

/*
 *  Customizer Notifications
 */ 

require get_template_directory() . '/inc/customizer/customizer-notice/aasta-customizer-notify.php';
$aasta_config_customizer = array(
    'recommended_plugins' => array( 
        'arile-super' => array(
            'recommended' => true,
            'description' => sprintf( 
                /* translators: %s: plugin name */
                esc_html__( 'If you want to show all the features and business sections of the FrontPage. please install and activate %s plugin', 'aasta' ), '<strong>Arile Super</strong>' 
            ),
        ),
    ),
	'recommended_actions'       => array(),
	'recommended_actions_title' => esc_html__( 'Recommended Actions', 'aasta' ),
	'recommended_plugins_title' => esc_html__( 'Recommended Plugin', 'aasta' ),
	'install_button_label'      => esc_html__( 'Install and Activate', 'aasta' ),
	'activate_button_label'     => esc_html__( 'Activate', 'aasta' ),
	'aasta_deactivate_button_label'   => esc_html__( 'Deactivate', 'aasta' ),
);
Aasta_Customizer_Notify::init( apply_filters( 'aasta_customizer_notify_array', $aasta_config_customizer ) );