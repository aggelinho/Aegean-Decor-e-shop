<?php
/**
 * General options
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Options
 * @version 4.0.0
 */

/**
 * APPLY_FILTERS: yith_wcan_panel_general_options
 *
 * Filters General panel options.
 *
 * @param array $options Panel options.
 *
 * @return array
 */
return apply_filters(
	'yith_wcan_panel_general_options',
	array(

		'general' => array(
			'general_section_start' => array(
				'name' => _x( 'General options', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'yith_wcan_general_settings',
			),

			'lazy_load'         => array(
				'name'      => _x( 'Lazy filters loading', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose whether to use lazy loading for the filters in the preset to speed up the initial page loading', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_lazy_load_filters',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'paginate_terms'    => array(
				'name'      => _x( 'Paginate terms', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => sprintf(
				// translators: 1. Number of terms shown of first loading.
					_x( 'When enabled, taxonomy filters won\'t show more than %d terms. If there are more terms available, the system will show a "Load more" link to show the remaining terms', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					YITH_WCAN_Filters_Factory::get_terms_on_first_loading(),
				),
				'id'        => 'yith_wcan_paginate_terms',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'general_section_end'   => array(
				'type' => 'sectionend',
				'id'   => 'yith_wcan_general_settings',
			),

			'reset_section_start' => array(
				'name' => _x( 'Reset button', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'yith_wcan_reset_settings',
			),

			'show_reset'            => array(
				'name'      => _x( 'Show reset button', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable to show the "Reset filter" button to allow the user to cancel the filter selection in one click', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_show_reset',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'reset_button_position' => array(
				'name'      => _x( 'Reset button position', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose the default position for reset button', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_reset_button_position',
				'type'      => 'yith-field',
				'yith-type' => 'radio',
				'default'   => 'after_filters',
				'options'   => array(
					'before_filters'  => _x( 'Before filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'after_filters'   => _x( 'After filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'before_products' => _x( 'Above products list<small>When using WooCommerce\'s Gutenberg product blocks, this may not work as expected; in these cases you can place Reset Button anywhere in the page using <code>[yith_wcan_reset_button]</code> shortcode or <code>YITH Filters Reset Button</code> block</small>', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				),
				'deps'      => array(
					'ids'    => 'yith_wcan_show_reset',
					'values' => 'yes',
				),
			),

			'reset_section_end'   => array(
				'type' => 'sectionend',
				'id'   => 'yith_wcan_reset_settings',
			),

		),
	)
);
