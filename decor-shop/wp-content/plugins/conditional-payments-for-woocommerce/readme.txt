=== Conditional Payments for WooCommerce ===
Contributors: wooelements
Tags: woocommerce payments, conditional payments, payment methods
Requires at least: 4.5
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 3.5.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict WooCommerce payment methods based on conditions. Works with your existing payment methods.

== Description ==
Conditional Payments for WooCommerce allows you to restrict payment methods based on conditions. For example, you can enable Cash on Delivery only when the shipping method is Local pickup.

The plugin works with your existing payment methods. You can restrict PayPal, Stripe, Cash on Delivery and any other payment method.

= Example 1 =

You have two payment methods, PayPal and Cash on Delivery. PayPal can be used for all orders but COD only when the customer pickups the order.

With Conditional Payments you can add shipping method condition to Cash on Delivery which checks that the shipping method is Local pickup.

= Example 2 =

You want to provide invoice payment method only for business customers. You can add billing company condition to Invoice to prevent private customers from ordering with Invoice.

= Features =

* Hide payment methods based on conditions
* Show payment methods based on conditions
* Works with built-in and 3rd party payment methods
* Debug mode for easy troubleshooting

= Available Conditions =

* Products
* Order Subtotal
* Shipping method
* Billing address (all fields)
* Shipping address (all fields)

= Pro Features =

* All free features
* Add payment method fees conditionally
* More conditions
    * Coupon
    * Currency
    * Stock status (in stock, backorders)
    * Shipping class
    * Product category
    * Logged in / out
    * User role
    * [Groups](https://wordpress.org/plugins/groups/)
    * Language (Polylang or WPML)
    * And more

[Upgrade to Pro](https://wptrio.com/products/conditional-payments/)

= Support Policy =

If you need any help with the plugin, please create a new post on the [WordPress plugin support forum](https://wordpress.org/support/plugin/conditional-payments-for-woocommerce/). It will be checked regularly but please note that response cannot be guaranteed to all issues. Priority email support is available for the Pro version.

= Other Useful Plugins =

Make sure to check out other useful plugins from the author.

* [Conditional Shipping for WooCommerce](https://wordpress.org/plugins/conditional-shipping-for-woocommerce)
* [Stock Sync for WooCommerce](https://wordpress.org/plugins/stock-sync-for-woocommerce/)
* [Product Sync for WooCommerce](https://wptrio.com/products/woocommerce-product-sync-pro/)

== Installation ==
Conditional Payments is installed just like any other WordPress plugin.

1. Download the plugin zip file
2. Go to Plugins in the WordPress admin panel
3. Click Add new and Upload plugin
4. Choose the downloaded zip file and upload it
5. Activate the plugin

Once the plugin is activated, you can add rulesets in *WooCommerce > Settings > Payments > Conditions*.

== Changelog ==

= 3.5.0 =

* Renamed **Enable payment methods** to **Allow payment methods** and added new **Enable payment methods**. **Allow payment methods** will disable payment methods if conditions do not pass. New **Enable payment methods** will enable payment methods which have been disabled by previous rulesets.
* Fixed PHP warning for empty conditions in the debug mode
* Added better input validation for the rulesets
* Minor bug fixes

= 3.4.1 =

* Added **is** and **is not** operators for billing and shipping city conditions. City input now allows a list separated by newlines

= 3.4.0 =

* Added **Admin only** mode for the debug mode to make it visible only for administrators (previously visible to all users)
* Improved compatibility with the blocks checkout

= 3.3.3 =

* Restored **Conditions** link for WooCommerce versions 9.9.x and 10.x.x

= 3.3.2 =

* Restored **Conditions** link in the **Payments** section of the settings, which was inadvertently removed in WooCommerce 9.8.5
* Improved compatibility with TeraWallet
* Minor UI improvements

= 3.3.1 =

* Improved compatibility with the blocks checkout
* Improved plugin security related to CSRF

= 3.3.0 =

* Added multicurrency support for **CURCY - Multi Currency for WooCommerce**

= 3.2.1 =

* Fixed a bug that caused the **Shipping method - is** condition to be evaluated incorrectly for virtual orders

= 3.2.0 =

* Added **Duplicate ruleset** feature
* Added **equals** operator for numerical conditions
* Improved **Shipping method - is - Match by name** feature to work better with dynamic shipping rates
* Improved user interface performance
* Minor bug fixes

= 3.0.3 =

* Declared compatibility with WordPress 6.4.x

= 3.0.2 =

* Added multicurrency support for *_Price Based on Country for WooCommerce_*

= 3.0.1 =

* Declared compatibility with High-Performance Order Storage (HPOS)
* Added option for hiding Pro features

= 3.0.0 =

* Added debug mode for easy troubleshooting
* Rulesets can now be ordered by drag-and-drop. Rulesets are evaluated from top to bottom
* Improved user interface

= 2.4.1 =

* Fixed bug which crashed the checkout if WooCommerce Multilingual & Multicurrency by WPML was activated but multicurrency functionality was not enabled

= 2.4.0 =

* Added AND / OR selection for rulesets (one / all conditions have to pass)
* Added support for the following multi-currency plugins: *_Aelia Currency Switcher for WooCommerce_*, *_FOX - Currency Switcher Professional for WooCommerce_* and *_WooCommerce Multilingual & Multicurrency (by WPML)_*
* Improved compatibility with WPML

= 2.3.2 =

* CSRF fix

= 2.3.1 =

* Removed unnecessary error logging

= 2.3.0 =

* Added *Disable all* setting for disabling all rulesets at once (*WooCommerce > Settings > Payments > Conditions > Disable all*) for easy troubleshooting
* Added condition for customer billing / shipping state
* Updated WooCommerce compatibility info

= 2.2.3 =

* Updated WooCommerce compatibility info

= 2.2.2 =

* Removed debug message causing unnecessary log messages

= 2.2.1 =

* Fixed bug with Products condition which prevented it to work with a lot of product variations

= 2.2.0 =

* Added AJAX toggle for ruleset state (enabled / disabled)
* Added Health Check to catch common issues with rulesets
* Excluded taxes from the subtotal condition if the store displays subtotal excluding tax (_WooCommerce > Settings > Tax > Display prices during cart and checkout_). *Please note!* Ensure rulesets are working correctly after updating if you have subtotal conditions.
* For developers: added better support for implementing custom conditions

= 2.1.5 =

* WooCommerce 4.1.x compatibility check
* Made address filters case-insensitive (previously case-sensitive)
* For developers: added WP filters for adding support for 3rd party shipping method plugins

= 2.1.4 =

* Improved product search
* Added range and wildcard filtering for postcode condition

= 2.0.2 =

* Added functionality for enabling / disabling rulesets

= 2.0.1 =

* Fixed bug which caused error message on frontend related to JavaScript file enqueuing

= 2.0.0 =

* Moved conditions from payment method setting pages to separate settings page (WooCommerce > Settings > Payments > Conditions). This change will allow more advanced functionality in upcoming versions. Important! Check that conditions are working correctly after updating.

= 1.0.3 =

* Updated compatibility info

= 1.0.2 =

* Fixed Javascript issue which caused conditions disappear in some cases
* Added support for Flexible Shipping plugin

= 1.0.1 =

* Added link to Pro version

= 1.0.0 =
* Initial version
