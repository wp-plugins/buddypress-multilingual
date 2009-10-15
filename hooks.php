<?php
		// BP HOOKS

add_filter('bp_uri', array(&$this,'filter_bp_uri') );
add_filter('post_link',array(&$this,'convert_title_url'));

		// SEARCH
add_filter('bp_core_search_site', array(&$this,'option_siteurl') );

add_filter('bp_get_post_category',array(&$this,'filter_hrefs'));

// this needs to be removed /lang/
add_filter('bp_get_activities_member_rss_link',array(&$this,'remove_lang'));
add_filter('bp_get_sitewide_activity_feed_link',array(&$this,'remove_lang'));
add_filter('feed_link',array(&$this,'remove_lang'));
add_filter('admin_url',array(&$this,'remove_lang'));

add_filter('bp_activity_content_filter',array(&$this,'filter_hrefs'));


add_filter('bp_get_the_site_blog_link', array(&$this,'option_siteurl') );

// my blogs
add_filter('bp_get_blog_permalink',array(&$this,'option_siteurl'));