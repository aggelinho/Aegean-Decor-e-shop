=== Advanced AJAX Product Filters ===
Plugin Name: Advanced AJAX Product Filters
Contributors: dholovnia, berocket
Donate link: https://berocket.com/woocommerce-ajax-products-filter/?utm_source=wordpress_org&utm_medium=donate&utm_campaign=ajax_filters
Tags: product filters, ajax product filters, woocommerce filters, wc filters, category filter
Requires at least: 5.0
Tested up to: 6.9.4
Stable tag: 3.2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fast and flexible AJAX product filters for WooCommerce. Filter by categories, attributes, price, tags, rating, and more. No page reloads.

== Description ==

Give your customers the power to find products instantly.  
**WooCommerce Ajax Products Filter** lets users filter, sort, and search products without reloading the page. Fast, flexible, and compatible with most themes and page builders.  

Already packed with features in the free version, and even more powerful with [Pro and Business versions](https://berocket.com/woocommerce-ajax-products-filter/).

= ✨ Free Features =
Everything you need to create professional product filters:

* **Ajax Filtering, Pagination and Sorting** – update results instantly without reloading.
* **Drag & Drop Filter Builder** – design filters with ease.
* **Filter Types**: checkboxes, dropdowns, radio buttons, sliders, color/image swatches.
* **Filter Options**: categories, attributes, tags, price, ratings, product meta (custom fields).
* **Filter Controls**: apply button, reset/clear button, selected filters area (vertical & horizontal).
* **Filter Layouts**: collapse on load, collapse on title click, nested filters, hierarchical display, limit height with scroll, hide extra values under “Show More” button.
* **Filter Styles**: multiple slider styles, button styles, checkbox styles, color box, image box, checked image style, Select + Select2 support.
* **Advanced Display**: show product counts, hide empty values, remove out-of-stock variations, child values on taxonomy pages.
* **Widgets & Shortcodes** – display filters/groups anywhere.
* **Custom CSS Styling** – style filters your way.
* **Icons Before/After** – add icons to titles and values.
* **Selected Filters Area** – horizontal/vertical styles, custom placement.
* **Compatibility**:
  * Works with most WooCommerce themes.
  * Page builders: Elementor, Divi, Beaver Builder.
  * Plugins: ACF Pro, WPML, Polylang, Relevanssi, Barn2 Product Table, other BeRocket plugins.
  * WooCommerce shortcodes.
* **SEO Friendly URLs** – clean filter links.
* **Integration**: Permalink Manager for WooCommerce.
* **Performance**: minified JS/CSS, optimized code, scroll-to-top after filtering.
* **Developer Friendly** – hooks and custom code options for full control.
* **Translation Ready** – translate via .po/.mo files or plugin settings.
* **Browser Back Button Support** – smooth navigation with AJAX.

👉 In short: almost everything you expect from a professional filter plugin — already free.

= 🚀 Pro Features =
Upgrade to [WooCommerce Ajax Products Filter Pro](https://berocket.com/woocommerce-ajax-products-filter/) to unlock advanced features:

* **SEO Enhancements**
  * SEO-friendly URLs with advanced control.
  * Canonical links, meta & titles for filtered pages.
  * Add filters to page title/description/header.
* **New Filter Types**
  * Filter by stock status, sale status.
  * Filters by custom taxonomies.
  * Slider range for attributes/taxonomies.
  * Datepicker for attributes & product publication date.
  * Availability( in stock | out of stock | any ).
* **Advanced Styling & Layout**
  * Filters above products, custom sidebar, 1–4 filters per row.
  * Collapsed filters above products.
  * Show filter titles only.
* **Filter Options**
  * Product count per value.
  * Checkbox/value list for price ranges.
  * Multiple colors (up to 4) for attributes.
  * Display variation image/price matching selected filters.
  * Open product directly with matching variation.
* **Navigation Tools**
  * Search box block for redirect filters.
  * Link setup after filtering.

= 💼 Business Features =
For large stores and professional needs:

* **Filter Statistics** – see which filters your customers use most.
* **Custom SEO Meta per Page** – individual SEO control for each filter result.
* **Advanced Filter Styling** – fine-tune filter design.
* **Priority Support** – direct help from the BeRocket team.

= 📌 Live Demo & Docs =
* [Pro & Business](https://berocket.com/woocommerce-ajax-products-filter/?utm_source=wordpress_org&utm_medium=plugin_links&utm_campaign=ajax_filters)
* [Frontend Demo](https://woocommerce-products-filter.berocket.com/shop?utm_source=wordpress_org&utm_medium=plugin_links&utm_campaign=ajax_filters)
* [Admin Demo](https://berocket.com/woocommerce-ajax-products-filter/?utm_source=wordpress_org&utm_medium=admin_demo&utm_campaign=ajax_filters#try-admin)
* [Documentation](https://docs.berocket.com/plugin/woocommerce-ajax-products-filter?utm_source=wordpress_org&utm_medium=plugin_links&utm_campaign=ajax_filters)

= 🎬 Premium plugin video =
* [youtube https://youtu.be/PQTXzp9Tpbc]
* [youtube https://youtu.be/Ltz82Zs5pl0]
* [youtube https://youtu.be/GA3O1F6YVNE]
* [youtube https://youtu.be/GPA77L0XBxM]
*we don't have a video with the free plugin right now, but we are working on it*

= 🤝 Compatibility with WooCommerce plugins =
Advanced AJAX Product Filters has been tested and compatibility is certain with the following WooCommerce plugins that you can add to your site:

&#128312; [**Advanced Product Labels for WooCommerce**](https://wordpress.org/plugins/advanced-product-labels-for-woocommerce/)
&#128312; [**Load More Products for WooCommerce**](https://wordpress.org/plugins/load-more-products-for-woocommerce/)
&#128312; [**Brands for WooCommerce**](https://wordpress.org/plugins/brands-for-woocommerce/)
&#128312; [**Grid/List View for WooCommerce**](https://wordpress.org/plugins/gridlist-view-for-woocommerce/)
&#128312; [**Product Preview for WooCommerce**](https://wordpress.org/plugins/product-preview-for-woocommerce/)
&#128312; [**Products Compare for WooCommerce**](https://wordpress.org/plugins/products-compare-for-woocommerce/)
&#128312; [**Wishlist and Waitlist for WooCommerce**](https://wordpress.org/plugins/wish-wait-list-for-woocommerce/)

= 🧩 Shortcode =
* In editor `[br_filters attribute=price type=slider title="Price Filter"]`
* In PHP `do_shortcode('[br_filters attribute=price type=slider title="Price Filter"]');`

= ⚙️ Shortcode Options: =
* `attribute`(required) - product attribute, e.g. price or length. Don't forget that WooCommerce adds the pa_ suffix for created attributes.
 So if you create a new attribute `jump`, its name is `pa_jump`
* `type`(required) - checkbox, radio, slider or select
* `operator` - OR or AND
* `title` - whatever you want to see as a title. Can be empty
* `product_cat` - parent category id
* `cat_propagation` - should we propagate this filter to child categories? Set 1 to turn this on
* `height` - max filter box height. When height is met, the scroll will be added
* `scroll_theme` - used if height is set and actual height of the box is more

---

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/woocommerce-ajax-filters/`, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Appearance > Widgets** or use shortcodes to add your filters.
4. Configure your filter groups in the plugin settings.

---

== Frequently Asked Questions ==

= Does this plugin work with my theme? =
Yes, the plugin is compatible with most themes for WooCommerce. If you experience any issues, we provide ways to adjust via custom code or settings.

= Can I use this plugin with Elementor / Divi / Beaver Builder? =
Yes, the plugin integrates with popular page builders and works inside custom layouts.

= Is it translation ready? =
Yes, you can translate all texts via .po/.mo files or from the plugin’s settings page.

= Do I need coding knowledge to use this plugin? =
No coding is required. Filters can be built with drag-and-drop. Developers can use hooks for advanced customization.

= What’s the difference between Free, Pro, and Business? =
The free version includes everything most stores need. Pro adds advanced filter types, SEO options, and styling. Business adds filter statistics, custom SEO per page, and priority support.

= How can I get support if my WooCommerce plugin is not working? =
If you have problems with our plugins or something is not working as it should, first follow these preliminary steps:

* Test the plugin with a WordPress default theme to ensure that the error is not caused by the theme you are currently using.
* Deactivate all plugins you use and check if the problem is still occurring.
* Ensure that your plugin, theme, and WordPress and WooCommerce versions (if required) are updated and that the problem you are experiencing has not already been solved in a later plugin update.

If none of the previously listed actions help you resolve the issue, please submit a ticket in the forum and accurately describe your problem. Specify the versions of WordPress and WooCommerce you are using, as well as any other information that may assist us in resolving your issue as swiftly as possible. Thank you!

= How can I get more features for my WooCommerce plugin? =
You can get more features with the premium version of Advanced AJAX Product Filters, available on [BeRocket page](https://berocket.com/woocommerce-ajax-products-filter/?utm_source=wordpress_org&utm_medium=faq&utm_campaign=ajax_filters). Here you can read more about the premium features of the plugin and make it give it its best shot!

= How can I try the full-featured plugin? =
You can try this plugin's admin side [here](https://berocket.com/woocommerce-ajax-products-filter/?utm_source=wordpress_org&utm_medium=faq&utm_campaign=ajax_filters#try-admin). Configure plugin the way you need to check the results.

---

== Screenshots ==
1. Example of AJAX filtering on a shop page.
2. Filter builder interface in the admin panel.
3. Custom sidebar with filters.
4. Selected filters area showing active selections.
5. Sliders with price and attributes.
6. Group settings with added filters.

---

== Changelog ==

= 3.2.0.1 =
* Enhancement - Compatibility version: WooCommerce 10.7
* Fix - Plugins notice mark on BeRocket element

= 3.2 =
* Enhancement - Compatibility version: Wordpress 6.9.4 and WooCommerce 10.6.2
* Fix - PHP warning in admin area

= 3.1.9.9 =
* Enhancement - Compatibility with other BeRocket plugins
* Fix - Updater PHP error
* Fix - Issue with autoselectors

= 3.1.9.8 =
* Enhancement - Compatibility version: WooCommerce 10.6 and WordPress 6.9.3
* Enhancement - Move global options
* Fix - Tables generation
* Fix - Permalinks and settings

= 3.1.9.7 =
* Fix - Some styles in admin area
* Fix - Live composer compatibility
* Fix - Some PHP errors

= 3.1.9.6 =
* Enhancement - New add-on images
* Fix - JS error on filtering
* Fix - JS error with new slider template
* Fix - JS remove some logs

= 3.1.9.5 =
* Enhancement - Compatibility version: WooCommerce 10.4 and WordPress 6.9
* Enhancement - Framework Version
* Enhancement - RTL language styles
* Enhancement - New errors for groups
* Enhancement - Changing show/hide to slide
* Fix - Selected filters area style
* Fix - Woodmart theme compatibility
* Fix - Styles for filters with scroll when scroll jumping

= 1.6.9.4 =
* Enhancement - Framework Version
* Enhancement - Optimization for plugin settings
* Enhancement - New admin bar statistic
* Fix - Compatibility version: WooCommerce 10.2
* Fix - Category shortcode replace
* Fix - Single filter edit page options
* Fix - Compatibility with some themes
* Fix - Infinite Additional table generation

= 1.6.9.3 =
* Fix - Compatibility version: WooCommerce 10.1
* Enhancement - Framework Version

= 1.6.9.2 =
* Fix - Compatibility version: WooCommerce 10.0

= 1.6.9.1 =
* Enhancement - New hook to disable any compatibility for theme/builder/plugin.
* Fix - Error with wizard on some site
* Fix - WPbakery page builder compatibility

= 1.6.9 =
* Enhancement - Better support Divi Builder
* Enhancement - Better support Elementor Builder
* Enhancement - Support Live Composer Page Builder
* Enhancement - Support WPBakery Page Builder
* Enhancement - Support Page Builder by SiteOrigin
* Enhancement - Support Oxygen Builder
* Enhancement - Support Breakdance Builder
* Fix - Select2 initialize error
* Fix - Additional table variation update error
* Fix - Meta query parameters support in some cases
* Fix - Divi theme/builder issue in some case page not displayed or header has incorrect style
* Fix - Admin bar styles

= 1.6.8.2 =
* HOTFIX - Vulnerability in plugin
* Enhancement - New button designs
* Enhancement - New checkbox and selected filters area designs
* Enhancement - Style for selected filters area in global settings
* Enhancement - Better compatibility with Elementor page builder
* Fix - Compatibility with Advanced Custom Field
* Fix - Issues in storefront, button images text and few small issues

= 1.6.8.1 =
* HOTFIX - Display message for deprecated errors

= 1.6.8 =
* Enhancement - Compatibility version: WooCommerce 9.6
* Enhancement - Setup wizard
* Fix - JavaScript error for pagination when multiple selectors used
* Fix - Divi module script on some themes
* Fix - Initialize additional tables

= 1.6.7.1 =
* Enhancement - Option to disable MySQL derived merge for filter queries

= 1.6.7 =
* Enhancement - Compatibility version: WooCommerce 9.4 and WordPress 6.7
* Fix - Translation init errors with WordPress 6.7

= 1.6.6 =
* Enhancement - Compatibility version: WooCommerce 9.3
* Enhancement - WooCommerce requirements
* Fix - Plugin update to premium PHP error

= 1.6.5 =
* Enhancement - Compatibility version: Wordpress 6.6 and WooCommerce 9.1
* Enhancement - Button to regenerate additional tables
* Enhancement - Option to remove some variations data
* Enhancement - Recount all attributes when woocommerce recount used
* Enhancement - Advanced Custom Fields compatibility
* Enhancement - Add option to use values as color/image
* Fix - Additional table generation
* Fix - Correct filters update from different group
* Fix - Some templates with different attribute data
* Fix - New plugin framework

= 1.6.4.6 =
* Enhancement - Message that attribute do not have values
* Enhancement - Bottom position for price new slider
* Enhancement - New Selected Filters Area template
* Fix - Some UX texts
* Fix - Addon filters settings
* Fix - Trailing slash for canonical
* Fix - Link changes
* Fix - Template for price
* Fix - Additional tables generation for variations

= 1.6.4.5 =
* Enhancement - Add-on Filter Additional Settings
* Enhancement - Option to replace categories shortcode with products
* Fix - Divi module styles
* Fix - Pagination with incorrect selectors
* Fix - MariaDB 10.4 compatibility for database update

= 1.6.4.4 =
* Enhancement - WooCommerce High-Performance Order Storage support enable

= 1.6.4.3 =
* Fix - Link like WooCommerce add-on with slider

= 1.6.4.2 =
* Enhancement - New Divi module functionality
* Fix - Pagination replace with translation
* Fix - Price range on search page and some other pages
* Fix - Link like WooCommerce add-on
* Fix - Compatibility with WooCommerce 7.8
* Fix - Primary key for tables in plugin

= 1.6.4.1 =
* Fix - Divi Module for group do not display filters

= 1.6.4 =
* Enhancement - Divi Modules with more options
* Fix - Additional table generation for some database
* Fix - Price Range filtering
* Fix - Error filters do not exist
* Fix - Divi Module preview styles

= 1.6.3.4 =
* Enhancement - Additional tables generation to not change collation
* Enhancement - Additional tables check is tables exist
* Enhancement - Additional tables clear tables instead remove
* Fix - Barn2 Product table new check

= 1.6.3.3 =
* Enhancement - Compatibility version: Wordpress 6.1 and WooCommerce 7.1
* Fix - Some plugin links to match new BeRocket Site

= 1.6.3.2 =
* Enhancement - Option to fix pagination position after filter page without pagination
* Enhancement - Regenerate additional tables if it was removed
* Enhancement - Compatibility version: WooCommerce 7.0
* Enhancement - Remove some PHP 8.1 notices

= 1.6.3.1 =
* Fix - Post meta not displayed in filter by list

= 1.6.3 =
* Enhancement - Compatibility version: Wordpress 6.0 and WooCommerce 6.7
* Enhancement - POST META FILTERING ADD-ON
* Enhancement - Hierarchical view for taxonomies list
* Fix - Color/Image select with polylang
* Fix - Additional tables generation for some site
* Fix - Currency exchange compatibility
* Fix - Module for Divi theme
* Fix - Style of admin elements

= 1.6.2 =
* Enhancement - Compatibility version: WooCommerce 6.4
* Enhancement - Hierarchical view for color/image pick
* Enhancement - Compatibility for non latin slug for attributes
* Fix - Get collation from other tables

= 1.6.1.5 =
* Fix - XSS Vulnerability

= 1.6.1.4 =
* Enhancement - Compatibility version: WordPress 5.9
* Fix - Empty hook issue
* Fix - Link like WooCommerce with some optimization plugin
* Fix - Not exist attribute cause PHP errors

= 1.6.1.3 =
* Fix - Relevanssi Compatibility

= 1.6.1.2 =
* Enhancement - Compatibility version: WooCommerce 6.1
* Enhancement - Compatibility with WP Search WooCommerce
* Fix - Compatibility with Product Table

= 1.6.1.1 =
* Fix - Compatibility with Product Table plugin
* Fix - URL decoding option with Product Table plugin
* Fix - Link generation for price filters

= 1.6.1 =
* Fix - Compatibility filtering with WPML and Polylang
* Fix - Compatibility with WPML taxonomy translation
* Fix - Price filtering for variable products
* Fix - Attribute values with numeric slug

= 1.6.0.2 =
* Fix - Selected filters options do not work
* Fix - Link like WooCommerce add-on work incorrect in some cases
* Fix - Support query with product variations and other post types

= 1.6.0.1 =
* Fix - Incorrect query when used not default 'wp_' database prefix

= 1.6 =
* Enhancement - Less database queries
* Enhancement - Optimization for database queries. Speed up request to database
* Enhancement - Updated Additional tables for optimized requests and more correct filtering
* Enhancement - Possibility to filter any products request on the page with help of shortcode [brapf_next_shortcode_apply]
* Enhancement - Support for some page builders products elements with shortcode [brapf_next_shortcode_apply]
* Enhancement - Hide reset products button on page load with help of CSS code
* Enhancement - (DEV) New data for filtered page to get more control on selected elements
* Enhancement - Removed Deprecated Filters Add-on
* Fix - Multiple blocks with products in Divi Page Builder, when only single block must be filtered
* Fix - Stock status "On Backorder" work as "In stock" for filtering

= < 1.6 =
* Please check older versions of the plugin for older changelog
