=== Plugin Name ===
Contributors: ICanLocalize, jozik
Donate link: http://wpml.org/?page_id=2312
Tags: i18n, translation, localization, language, multilingual, WPML, BuddyPress
Requires at least: 2.8.4
Tested up to: 2.9.2
Stable tag: 1.0.0

BuddyPress Multilingual allows BuddyPress sites to run fully multilingual using the WPML plugin.

== Description ==

The plugin allows visitors to choose their language. It makes all the BuddyPress elements multilingual including the main site and all guest sites.

Guest blogs can choose their language and create multilingual contents. Additionally, each guest can choose the admin language individually.

This plugin requires [WPML](http://wordpress.org/extend/plugins/sitepress-multilingual-cms/). It uses WPML's language API and hooks to BuddyPress to make it multilingual.

Requirements:

* WPML 1.7.3 or higher. You must enable 'languages per directories'.
* Supports BuddyPress versions up to 1.2.

= Features =

 * Enables language switcher in BuddyPress top bar menu
 * Enables multilingual BuddyPress components
 * Filters all links to maintain right language selection

== Installation ==

1. Unzip and upload contents of sitepress-bp.zip file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress (only available as 'site wide' plugin).
3. Enable WPML (can be enabled before this BuddyPress Multilingual).

== Frequently Asked Questions ==

= Why do I need to enable languages per directories? =

BuddyPress itself uses virtual directories for its elements. The only way we managed to add language information is by adding a language prefix to paths.

For example, /fr/members/ to the French members list.

== Screenshots ==

1. Multilingual main BuddyPress page.

== Changelog ==

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

= 1.0.0 =
* Finally, runs on BuddyPress 1.2.
