<?php

		// BP HOOKS
add_filter('bp_uri', array(&$this,'filter_bp_uri') );

		// SEARCH
add_filter('bp_core_search_site', array(&$this,'filter_search_redirection') );

add_filter('bp_get_post_category',array(&$this,'filter_hrefs'));

// this needs to be removed /lang/
add_filter('bp_get_activities_member_rss_link',array(&$this,'remove_lang'));