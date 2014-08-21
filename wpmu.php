<?php
/*
 * WPMU functions.
 * TODO Check if deprecated.
 */


/**
 * [WPMU][DEPRECATED](???) Fetches WPML language data from subblogs.
 *
 * @global <type> $wpdb
 * @param <type> $blog_id
 * @param <type> $type
 * @param <type> $element_id
 * @return <type>
 */
function bpml_get_wpml_language_data($blog_id, $type = 'post_post',
        $element_id = NULL, $switch_db = FALSE) {
    static $sitepress_settings = array();

    if (isset($sitepress_settings[$blog_id][$type][$element_id])) {
        return $sitepress_settings[$blog_id][$type][$element_id];
    }

    $result = array();
    if ($switch_db) {
        switch_to_blog($blog_id);
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'icl_translations';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        if ($switch_db) {
            restore_current_blog();
        }
        return FALSE;
    }
    if (!isset($sitepress_settings[$blog_id])) {
        $fetch = unserialize($wpdb->get_var("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name='icl_sitepress_settings'"));
        $sitepress_settings[$blog_id]['default_language'] = $fetch['default_language'];
    }
    switch ($type) {
        case 'comment':
            $post_id = $wpdb->get_var("SELECT comment_post_ID FROM {$wpdb->prefix}comments WHERE comment_ID=" . $element_id);
            $post_type = 'post_' . $wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts WHERE ID=" . $post_id);
            $result['language'] = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='" . $post_type . "' AND element_id=" . $post_id);
            break;

        default:
            $result['language'] = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='" . $type . "' AND element_id=" . $element_id);
            break;
    }
    $result['default_language'] = $sitepress_settings[$blog_id]['default_language'];
    $sitepress_settings[$blog_id][$type][$element_id] = $result;
    if ($switch_db) {
        restore_current_blog();
    }
    return $result;
}

/**
 * [WPMU][DEPRECATED](???) Tries to get language data for item.
 *
 * @global <type> $sitepress
 * @param <type> $item
 * @param <type> $type
 * @param <type> $switch_db
 * @return <type>
 */
function bpml_get_item_language($item = NULL, $type = 'post_post',
        $switch_db = FALSE) {
    $lang = array();
    global $sitepress;
    if ($type == 'post_post' && !empty($sitepress) && isset($_POST['icl_post_language'])) { // In moment of save if WPML is active
        $lang['language'] = $_POST['icl_post_language'];
        $lang['default_language'] = $sitepress->get_default_language();
        $lang['recorded_language'] = $sitepress->get_current_language();
    } else if (!is_null($item)) { // Check in DB
        $lang = bpml_get_wpml_language_data($item->item_id, $type, $item->secondary_item_id, $switch_db);
        if (!empty($lang)) {
            $lang['recorded_language'] = $lang['default_language'];
        }
    }
    // Try to get WPLANG
    if (empty($lang) && !empty($sitepress)) {
        $temp = get_site_option('WPLANG');
        if (!empty($temp)) {
            if (strlen($temp) > 2) {
                $temp = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE locale='" . $temp . "'");
            }
            $lang['default_language'] = $lang['language'] = $lang['recorded_language'] = $temp;
        }
    }
    // Try to get RSS lang
    if (empty($lang)) {
        $temp = get_site_option('rss_language');
        if (!empty($temp)) {
            $lang['default_language'] = $lang['language'] = $lang['recorded_language'] = $temp;
        }
    }
    return $lang;
}
