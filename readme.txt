=== Plugin Name ===

Contributors: JustinSainton
Donate link: http://zaowebdesign.com
Tags: wp-e-commerce, e-commerce, wordpress e-commerce, categories, pricing
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 1.0.2

== Description ==

This plugin allows WP E-Commerce store admins to select certain categories as 'bulk pricing' categories, add a product threshold and discount to that category.  On the front-end, when any combination of products from one of these categories is in the shopping cart, and has met or exceeded the specified threshold, the specified discount is applied to each product.

== Installation ==

1. Upload `wpec_bulk_cat.php` to the `/wp-content/plugins/` directory

2. Activate the plugin through the 'Plugins' menu in WordPress

3. Go to Product Categories and edit category meta as necessary.


== Frequently Asked Questions ==

None yet.  Props to jn0101 for initial patches for multiple category support.


== Changelog ==

1.0.2

* Fixes wpdb::prepare notices

1.0.1

* Adds ability for multiple category assignment, also tiered category pricing (different bulk rates per category at different cart totals for said category)

1.0

* Initial commit


== Screenshots ==



None yet.