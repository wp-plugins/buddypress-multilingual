<?php
/*
 * Frontend functions
 */

/**
 * Test function.
 * 
 * @param <type> $a
 */
function bpml_test($a = '') {
    echo '<pre>';
    print_r($a);
    echo '</pre>';
}

/**
 * Before BP header hook.
 *
 * Activate site_url() filter.
 */
function bpml_bp_before_header_hook() {
    add_filter('site_url', 'bpml_site_url_filter', 0);
}

/**
 * After BP footer hook.
 *
 * Remove site_url() filter.
 */
function bpml_bp_after_footer_hook() {
    remove_filter('site_url', 'bpml_site_url_filter', 0);
}

/**
 * Filters site_url() calls.
 *
 * @global  $sitepress
 * @param <type> $url
 * @return <type>
 */
function bpml_site_url_filter($url, $path = '') {
    global $sitepress;
    if ($sitepress->get_default_language() == ICL_LANGUAGE_CODE) {
        return $url;
    }
    return rtrim($sitepress->convert_url($url), '/');
}

/**
 * Removes site_url() filter when redirecting to random blog.
 */
function bpml_blogs_redirect_to_random_blog() {
    global $bp;
    if ($bp->current_component == $bp->blogs->slug && isset($_GET['random-blog'])) {
        remove_filter('site_url', 'bpml_site_url_filter', 0);
    }
}

/**
 * Filters BuddyPress root domain.
 * 
 * @global $sitepress $sitepress
 * @param <type> $url
 * @return <type>
 */
function bpml_bp_core_get_root_domain_filter($url) {
    global $sitepress;
    if ($sitepress->get_default_language() == ICL_LANGUAGE_CODE) {
        return $url;
    }
    return rtrim($sitepress->convert_url($url), '/');
}

/**
 * Filters admin URL (removes language).
 * 
 * @global $sitepress $sitepress
 * @param <type> $url
 * @param <type> $path
 * @param <type> $blog_id
 * @return <type>
 */
function bpml_admin_url_filter($url, $path, $blog_id) {
    $url = str_replace('/' . ICL_LANGUAGE_CODE . '/wp-admin', '/wp-admin/', $url);
    return $url;
}

/**
 * Translates all links in given string.
 * 
 * @global <type> $bpml_filter_hrefs_lang
 * @param <type> $string
 * @param <type> $lang
 * @param <type> $limit
 * @return <type>
 */
function bpml_filter_hrefs($string = '', $lang = '', $limit = -1,
        $position = NULL) {
    global $bpml_filter_hrefs_lang, $bpml_filter_hrefs_count;
    $bpml_filter_hrefs_count = 0;
    $bpml_filter_hrefs_lang = $lang;
    if (!is_null($position)) {
        global $bpml_filter_hrefs_position;
        $bpml_filter_hrefs_position = $position;
    }
    $return = preg_replace_callback('/href=["\'](.+?)["\']/', 'bpml_filter_href_matches', $string, $limit);
    $bpml_filter_hrefs_count = 0;
    return $return;
}

/**
 * Translates all links in given string from to.
 *
 * @global <type> $bpml_filter_hrefs_lang
 * @param <type> $string
 * @param <type> $lang
 * @param <type> $limit
 * @return <type>
 */
function bpml_filter_hrefs_from_to($string = '', $lang_from = '', $lang_to = '',
        $limit = -1, $position = NULL) {
    global $bpml_filter_hrefs_lang, $bpml_filter_hrefs_lang_to, $bpml_filter_hrefs_count;
    $bpml_filter_hrefs_count = 0;
    $bpml_filter_hrefs_lang = $lang_from;
    $bpml_filter_hrefs_lang_to = $lang_to;
    if (!is_null($position)) {
        global $bpml_filter_hrefs_position;
        $bpml_filter_hrefs_position = $position;
    }
    $return = preg_replace_callback('/href=["\'](.+?)["\']/', 'bpml_filter_href_matches', $string, $limit);
    $bpml_filter_hrefs_count = 0;
    return $return;
}

/**
 * Translates links in matches provided from bpml_filter_hrefs().
 *
 * @global $sitepress $sitepress
 * @global  $bpml_filter_hrefs_lang
 * @param <type> $match
 * @return <type>
 */
function bpml_filter_href_matches($match = array()) {
    global $sitepress, $bpml_filter_hrefs_lang, $bpml_filter_hrefs_lang_to, $bpml_filter_hrefs_position, $bpml_filter_hrefs_count;

    if (!empty($bpml_filter_hrefs_position)) {
        if ($bpml_filter_hrefs_count != $bpml_filter_hrefs_position) {
            $bpml_filter_hrefs_count += 1;
            return $match[0];
        }
    }

    if (!empty($bpml_filter_hrefs_lang_to)) {
        $lang_to = ($bpml_filter_hrefs_lang_to == $sitepress->get_default_language()) ? '' : $bpml_filter_hrefs_lang_to . '/';
        $converted = preg_replace('/\/' . $bpml_filter_hrefs_lang . '\//', '/' . $lang_to, $match[1], 1);
    } else if ($sitepress->get_current_language() != $sitepress->get_default_language()) {
        if ($bpml_filter_hrefs_lang !== $sitepress->get_default_language()) {
            $converted = preg_replace('/\/' . $bpml_filter_hrefs_lang . '\//', '/' . $sitepress->get_current_language() . '/', $match[1], 1);
        } else {
            $converted = $sitepress->convert_url($match[1]);
        }
        // Check doubled
        $converted = preg_replace('/\/' . $sitepress->get_current_language() . '\/' . $sitepress->get_current_language() . '\//', '/' . $sitepress->get_current_language() . '/', $converted, 1);
    } else {
        $replace = !empty($bpml_filter_hrefs_lang) ? '/\/' . $bpml_filter_hrefs_lang . '\//' : '/\//';
        $converted = preg_replace($replace, '/', $match[1], 1);
    }
    return str_replace($match[1], $converted, $match[0]);
}

/**
 * Filters bp_uri.
 *
 * This URI is important for BuddyPress.
 * By that it determines some components and actions.
 * We remove language component so BP can determine things right.
 *
 * @param <type> $url
 * @return <type>
 */
function bpml_bp_uri_filter($url) {
    global $sitepress;
    $default_language = $sitepress->get_default_language();
    if ($default_language == ICL_LANGUAGE_CODE) {
        return $url;
    }
    return preg_replace('/\/' . ICL_LANGUAGE_CODE . '\//', '/', $url, 1);
}

/**
 * Filters WPML languages switcher.
 *
 * This filtering is performed on BP pages
 * where WPML actually don't detect any post/page.
 *
 * @global $sitepress $sitepress
 * @global <type> $bp
 * @global <type> $bp_unfiltered_uri
 * @return <type>
 */
function bpml_icl_ls_languages_filter($langs) {
    global $sitepress, $bp, $bp_unfiltered_uri;
    if (!in_array($bp_unfiltered_uri[0], $bp->root_components)) {
        return $langs;
    }
    foreach ($langs as $key => $lang) {
        $langs[$key]['url'] = $sitepress->convert_url(get_option('home')
                        . '/' . implode('/', $bp_unfiltered_uri), $lang['language_code']);
    }
    return $langs;
}

/**
 * Stores/returns debug messages.
 * 
 * @staticvar array $messages
 * @param <type> $message
 * @param <type> $class
 * @return array
 */
function bpml_debug($message, $class = 'bpml-debug-updated') {
    if (!current_user_can('administrator') || !defined('BPML_DEBUG') || !BPML_DEBUG) {
        return '';
    }
    static $messages = array();
    if ($message == 'get') {
        return $messages;
    }
    $messages[] = array('message' => $message, 'class' => 'updated bpml-debug ' . $class);
}

/**
 * Header hook.
 */
function bpml_wp_head_hook() {

}

/**
 * Renders debug info.
 * 
 * @global  $bp
 * @return <type>
 */
function bpml_wp_footer() {
    if (!current_user_can('administrator') || !defined('BPML_DEBUG') || !BPML_DEBUG) {
        return '';
    }
    echo '<div id="bpml-debug"><h2>BPML Debug</h2>';
    $messages = bpml_debug('get');
    foreach ($messages as $message) {
        echo bpml_message($message['message'], $message['class']);
    }
    global $bp, $bpml;
    echo '<pre>';
    echo '
BPML Settings
';
    var_dump($bpml);
    echo '

BuddyPress ';
    print_r($bp);
    echo '</pre></div>';
}