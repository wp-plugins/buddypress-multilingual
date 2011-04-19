<?php
/*
 * Functions for BP activities
 * @todo Site wide widget
 * @todo Activity loop and google translate button
 */

/**
 * Saves activity language.
 *
 * @global <type> $sitepress
 * @param <type> $item
 */
function bpml_activities_bp_activity_after_save_hook($item) {
    switch ($item->type) {
        case 'new_blog_post':
            $lang = bpml_get_item_language($item);
            if (empty($lang)) {
                return FALSE;
            } else if (!empty($lang['language'])) {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['language']);
            } else {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['default_language']);
            }
            if (!empty($lang['recorded_language'])) {
                bp_activity_update_meta($item->id, 'bpml_lang_recorded', $lang['recorded_language']);
            }
            break;

        case 'new_blog_comment':
            $lang = bpml_get_item_language($item, 'comment');
            if (empty($lang)) {
                return FALSE;
            } else if (!empty($lang['language'])) {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['language']);
            } else {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['default_language']);
            }
            break;

        default:
            if (defined('ICL_LANGUAGE_CODE')) {
                bp_activity_update_meta($item->id, 'bpml_lang', ICL_LANGUAGE_CODE);
            }
            break;
    }
    bpml_activities_clear_cache($item->item_id, 'bpml_google_translation', 'main');
}

/**
 * Triggers indicator that we're in activity loop.
 */
function bpml_activities_bp_before_activity_loop_hook() {
    global $bpml_in_activity_loop;
    $bpml_in_activity_loop = TRUE;
}

/**
 * Filters activities.
 *
 * @todo This filter is called twice on favorites page via hook
 * 'bp_activity_get_specific'. See if that is a problem.
 *
 * @global $sitepress $sitepress
 * @param <type> $activity
 * @param <type> $r
 * @return <type>
 */
function bpml_activities_bp_activity_get_filter($activity, $r = NULL) {
    global $sitepress, $bpml_in_activity_loop;
    static $cache = array();
    $default_language = $sitepress->get_default_language();
    $current_language = $sitepress->get_current_language();
    foreach ($activity['activities'] as $key => $result) {
        if (isset($cache[$result->id])) {
            $activity['activities'][$key] = $cache[$result->id];
            continue;
        }
        $activity['activities'][$key] = bpml_activities_translate_activity($result, $default_language, $current_language);
        if ($activity['activities'][$key] == FALSE) {
            unset($activity['activities'][$key]);
        } else {
            $cache[$result->id] = $activity['activities'][$key];
        }
    }
//    if (!empty($bpml_in_activity_loop) && function_exists('bpml_google_translate_button')) {
    if (function_exists('bpml_google_translate_button')) {
        echo bpml_google_translate_button();
    }
    $activity['total'] = count($activity['activities']);
    return $activity;
}

/**
 * Translates activity entry.
 *
 * @param <type> $result
 * @param <type> $default_language
 * @param <type> $current_language
 * @param <type> $options
 * @return <type>
 */
function bpml_activities_translate_activity($result, $default_language, $current_language) {
    global $bpml;
    // Record activity if isn't registered
    if (!isset($bpml['collected_activities'][$result->type])) {
        $bpml['collected_activities'][$result->type] = bpml_collected_activities_defaults();
        bpml_save_setting('collected_activities', $bpml['collected_activities']);
        bpml_store_admin_notice($result->type, '<p>New activities to handle: ' . $result->type . '</p>');
    }
    // Set options for current activity type (merge to cover missing options)
    $type_options = array_merge(bpml_collected_activities_defaults(), $bpml['collected_activities'][$result->type]);

    // Get language
    $lang = bp_activity_get_meta($result->id, 'bpml_lang');
    $lang_recorded = bp_activity_get_meta($result->id, 'bpml_lang_recorded');

    // Set/fix orphans (missing lang)
    if (empty($lang)) {
        if ($bpml['activities']['orphans_fix']) {
            $lang = bpml_activities_fix_orphan_activity($result, $default_language);
            bpml_debug('<p>Orphaned activity FIXED ID:' . $result->id . ' (Missing language - assigning \'' . $lang . '\')</p>');
        } else {
            bpml_debug('<p>Orphaned activity ID:' . $result->id . ' (Missing language - setting \'' . $default_language . '\')</p>');
            $lang = $default_language;
        }
        if (!$bpml['activities']['orphans_fix']
                && ($bpml['activities']['display_orphans'] === 'none'
                || ($bpml['activities']['display_orphans'] === 'default'
                && $current_language != $default_language))) {
            return FALSE;
        }
    }

    // Filter activities
    if (($bpml['activities']['filter'] && $lang == $current_language)
            || (!$bpml['activities']['filter'])) {

        $result->lang = $lang;
        if (!empty($lang_recorded)) {
            $result->lang_recorded = $lang_recorded;
        }

        // Process children
        if (!empty($result->children)) {
            foreach ($result->children as $child_key => $child) {
                $result->children[$child_key] = bpml_activities_translate_activity($child, $default_language, $current_language, $options);
            }
        }

        // Filter links
        if (0 !== intval($type_options['translate_links'])) {
            $result->action = bpml_filter_hrefs($result->action, $result->lang, intval($type_options['translate_links']));
        }

        // Apply filters to activity
        $result = apply_filters('bpml_activity_filter', $result, $default_language, $current_language, $type_options);

        return $result;
    } else {
        return FALSE;
    }
}

function bpml_activities_fix_orphan_activity($item, $default_language) {
    switch ($item->type) {
        case 'new_blog_post':
            $lang = bpml_get_item_language($item, 'post_post', TRUE);
            if (empty($lang)) {
                bp_activity_update_meta($item->id, 'bpml_lang', $default_language);
            } else if (!empty($lang['language'])) {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['language']);
            } else {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['default_language']);
            }
            break;

        case 'new_blog_comment':
            $lang = bpml_get_item_language($item, 'comment', TRUE);
            if (empty($lang)) {
                bp_activity_update_meta($item->id, 'bpml_lang', $default_language);
            } else if (!empty($lang['language'])) {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['language']);
            } else {
                bp_activity_update_meta($item->id, 'bpml_lang', $lang['default_language']);
            }
            break;

        default:
            bp_activity_update_meta($item->id, 'bpml_lang', $default_language);
            break;
    }
    bp_activity_update_meta($result->id, 'bpml_lang_orphan', $default_language);
    bpml_activities_clear_cache($item->item_id, 'bpml_google_translation', 'main');
    return bp_activity_get_meta($item->id, 'bpml_lang');
}

/**
 * Returns default collected activity settings.
 *
 * @return <type>
 */
function bpml_collected_activities_defaults() {
    return array(
        'translate_title' => 0,
        'translate_title_cache' => 0,
        'translate_content' => 0,
        'translate_content_cache' => 0,
        'translate_links' => 0
    );
}

/**
 * Clears activity cache
 * 
 * @global <type> $wpdb
 * @param <type> $ID
 * @param <type> $type
 */
function bpml_activities_clear_cache($ID, $type = 'bpml_google_translation',
        $blog_id = NULL) {
    if ($blog_id == 'main' && !is_main_site()) {
        global $current_site;
        $blog_id = $current_site->blog_id;
    } else if ($blog_id == 'main') {
        $blog_id = NULL;
    }
    if (!is_null($blog_id)) {
        switch_to_blog($blog_id);
    }
    global $wpdb;
    if ($ID == 'all') {
        $wpdb->query("DELETE FROM {$wpdb->prefix}bp_activity_meta WHERE meta_key='" . $type . "'");
    } else {
        $wpdb->query("DELETE FROM {$wpdb->prefix}bp_activity_meta WHERE meta_key='" . $type . "' AND activity_id=" . $ID);
    }
    if (!is_null($blog_id)) {
        restore_current_blog();
    }
}

/**
 * Clears all BPML activity data.
 */
function bpml_activities_clear_all_data($ID = 'all') {
    bpml_activities_clear_cache($ID, 'bpml_google_translation');
    bpml_activities_clear_cache($ID, 'bpml_lang');
    bpml_activities_clear_cache($ID, 'bpml_lang_recorded');
    bpml_activities_clear_cache($ID, 'bpml_lang_orphan');
}

/**
 * Admin language assign dropdown for single activity.
 * 
 * @global <type> $activities_template
 * @global  $sitepress
 * @return <type>
 */
function bpml_activities_assign_language_dropdown() {
    if (!current_user_can('administrator')) {
        return '';
    }
    global $activities_template, $sitepress;
    $langs = $sitepress->get_active_languages();
    $data = '';
    foreach ($langs as $lang) {
        $selected = $activities_template->activity->lang == $lang['code'] ? ' selected="selected"' : '';
        $data .= '<option value="' . $lang['code'] . '"' . $selected . '>' . $lang['english_name'] . '</option>';
    }

    echo '<li class="bpml-activity-assign-language-wrapper"><form class="bpml-activity-assign-language" action="' . admin_url() . '/admin-ajax.php" method="post">';
    echo '<select name="bpml-activity-assign-language[' . $activities_template->activity->id . ']">' . $data . '</select>&nbsp;<input type="submit" value="Change language" /><span style="margin-left:10px;" class="bmp-ajax-update"></span></form></li>';
}

/**
 * BPML activities AJAX process.
 */
function bpml_activities_ajax() {
    if (current_user_can('administrator') && isset($_POST['bpml-activity-assign-language'])) {
        $ID = key($_POST['bpml-activity-assign-language']);
        $lang = $_POST['bpml-activity-assign-language'][$ID];
        bp_activity_update_meta($ID, 'bpml_lang', $lang);
        bpml_activities_clear_cache($ID, 'bpml_google_translation');
        echo json_encode(array('output' => 'Language assigned'));
    }
}

/**
 * Translates latest update on the fly.
 * 
 * @global <type> $bpml
 * @param <type> $content
 * @return <type>
 */
function bpml_bp_get_activity_latest_update_filter($content) {
    global $bpml;
    if ($bpml['activities']['enable_google_translation'] === 0) {
        return $content;
    }
    require_once dirname(__FILE__) . '/google-translate.php';
    return bpml_google_translate($content, '', ICL_LANGUAGE_CODE);
}