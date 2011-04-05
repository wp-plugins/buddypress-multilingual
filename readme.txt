=== Plugin Name ===
Contributors: icanlocalize, jozik
Donate link: http://wpml.org/?page_id=2312
Tags: i18n, translation, localization, language, multilingual, WPML, BuddyPress
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 1.0.1

BuddyPress Multilingual allows BuddyPress sites to run fully multilingual using the WPML plugin.

== Description ==

The plugin allows building multilingual BuddyPress sites. It works with single-site or multi-site BuddyPress installs. Both the main site and child blogs can run multilingual.

Guest blogs can choose their language and create multilingual contents. Additionally, each guest can choose the admin language individually.

This plugin requires [WPML](http://wpml.org/). It uses WPML's language API and hooks to BuddyPress to make it multilingual.

Requirements:

* WPML 2.2.1 or higher. You must enable 'languages per directories'.
* Supports BuddyPress versions up to 1.2.8

= Features =

 * Enables multilingual BuddyPress components
 * Filters all links to maintain right language selection
 * Records language and allows Google translation for BuddyPress activity component
 * Allows translation control for each type of activity

For support, please visit [WPML forum](http://forum.wpml.org).

== Installation ==

1. Unzip and upload contents of sitepress-bp.zip file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the Network 'Plugins' menu in WordPress (only available as 'Network' plugin).
3. Enable WPML (can be enabled before this BuddyPress Multilingual).

== Frequently Asked Questions ==

= Why do I need to enable languages per directories? =

BuddyPress itself uses virtual directories for its elements. The only way we managed to add language information is by adding a language prefix to paths.

For example, /fr/members/ to the French members list.

== Screenshots ==

1. Multilingual main BuddyPress page.

== Changelog ==

= 1.0.1 =
* Supports BuddyPress 1.2.8 and Wordpress Network mode
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

= 1.0.1 =
* Runs on BuddyPress 1.2.8
