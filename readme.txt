=== Plugin Name ===
Contributors: icanlocalize, jozik
Donate link: http://wpml.org/documentation/related-projects/buddypress-multilingual/
Tags: i18n, translation, localization, language, multilingual, WPML, BuddyPress
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.3.0

BuddyPress Multilingual allows BuddyPress sites to run fully multilingual using the WPML plugin.

== Description ==

The plugin allows building multilingual BuddyPress sites. It works with single-site or multi-site BuddyPress installs. Both the main site and child blogs can run multilingual.

Guest blogs can choose their language and create multilingual contents. Additionally, each guest can choose the admin language individually.

This plugin requires [WPML](http://wpml.org/). It uses WPML's language API and hooks to BuddyPress to make it multilingual.

Requirements:

* WPML 2.4.2 or higher. You must enable 'languages per directories'.
* Supports BuddyPress versions up to 1.5.x

= Features =

 * Enables multilingual BuddyPress components
 * Filters all links to maintain right language selection
 * Records language and allows Google translation for BuddyPress activity component
 * Allows translation control for each type of activity

= New! Customize BuddyPress further using Types and Views =

BPML is now compatible with [Types - The Custom Types and Custom Fields Plugin](http://wp-types.com/home/types-manage-post-types-taxonomy-and-custom-fields/) and [Views - The Custom Content Display Plugin](http://wp-types.com/). Types and Views allow you to customize BuddyPress futher by controlling custom content and displaying it any way you choose.

= Need Support? =

Please submit support requests to **[WPML forum](http://forum.wpml.org)**. Remember to report:

* The versions of BuddyPress, WPML and WordPress that you're using.
* A URL to your site, where we can see the problem happening.
* A description of what you expect to see and what you're seeing in practice.

== Installation ==

1. Unzip and upload contents of sitepress-bp.zip file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the Network 'Plugins' menu in WordPress (only available as 'Network' plugin).
3. Enable WPML (can be enabled before this BuddyPress Multilingual).
4. To set preferences go to Settings/BuddyPress Multilingual on main blog.

== Frequently Asked Questions ==

= Why do I need to enable languages per directories? =

BuddyPress itself uses virtual directories for its elements. The only way we managed to add language information is by adding a language prefix to paths.

For example, /fr/members/ to the French members list.

== Screenshots ==

1. Multilingual main BuddyPress page.

== Changelog ==

= 1.3.0 =
* Support BP 1.5.x
* Language selector doesn't appear on the home page when a page is selected as the front page 
* Pages widget exclude function doesn't work in the right way
* Small fix on main navigation menu

= 1.2.1 =
* Supports BP 1.2

= 1.1.0 =
* Added translation support for XProfile fields

= 1.0.1 =
* Supports BuddyPress 1.2.8 and WP Network mode
* Added Google translation and translation control for BuddyPress activities

= 1.0.0 =
* Supports BuddyPress 1.2

= 0.9.2 =
* Bugfixes

= 0.9.1 =
* Bugfixes

= 0.9 =
* First public release. Supports BuddyPress 1.0

= 0.1.0 =
* Developers version, not recommended for production sites - feedback welcome!

== Upgrade Notice ==

= 1.1.0 =
* Runs on BuddyPress 1.2.8

= 1.3.0 =
* Runs with BuddyPress 1.5.x
