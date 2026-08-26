'use strict';

/* global jQuery */

const filterFieldsDependencies = {
	taxonomy: {
		type: 'tax',
	},
	use_all_terms: {
		type: 'tax',
	},
	term_ids: {
		type: 'tax',
		use_all_terms: '!:checked',
	},
	filter_design: {
		type: [ 'tax', 'review', 'price_range' ],
	},
	customize_terms: {
		type: 'tax',
		use_all_terms: '!:checked',
	},
	terms_options: {
		term_ids: ( v ) => !! v,
		customize_terms: ':checked',
		__show: ( filter ) => filter.afterTermsSelected(),
	},
	label_position: {
		filter_design: [ 'color', 'label' ],
	},
	column_number: {
		filter_design: [ 'label', 'color' ],
		label_position: [ 'below', 'hide' ],
	},
	show_search: {
		type: 'tax',
		filter_design: 'select',
	},
	price_ranges: {
		type: 'price_range',
	},
	price_slider_adaptive_limits: {
		type: 'price_slider',
	},
	price_slider_design: {
		type: 'price_slider',
	},
	price_slider_min: {
		type: 'price_slider',
		price_slider_adaptive_limits: '!:checked',
	},
	price_slider_max: {
		type: 'price_slider',
		price_slider_adaptive_limits: '!:checked',
	},
	price_slider_step: {
		type: 'price_slider',
	},
	order_options: {
		type: 'orderby',
	},
	show_stock_filter: {
		type: 'stock_sale',
	},
	show_sale_filter: {
		type: 'stock_sale',
	},
	show_featured_filter: {
		type: 'stock_sale',
	},
	toggle_style: {
		show_toggle: ':checked',
	},
	order_by: {
		type: 'tax',
	},
	order: {
		type: 'tax',
	},
	show_count: {
		type: [ 'tax', 'price_range', 'review', 'stock_sale' ],
	},
	hierarchical: {
		type: 'tax',
		filter_design: [ 'checkbox', 'radio', 'text' ],
	},
	multiple: {
		type: [ 'tax', 'review', 'price_range' ],
		filter_design: '!radio',
	},
	relation: {
		type: 'tax',
		multiple: ':checked',
	},
	adoptive: {
		type: [ 'tax', 'price_range', 'review', 'stock_sale' ],
	},
};

export { filterFieldsDependencies };
