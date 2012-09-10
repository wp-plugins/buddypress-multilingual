<?php
/*
 * Functions for BP activities
 * @todo Check activity loop and google translate button
 */

/**
 * Before saving activity hook.
 *
 * @param <type> $item
 */
function bpml_activities_bp_activity_before_save_hook($item) {
    if ($item->type == 'new_blog') {
        global $sitepress;
        $item->action = bpml_filter_hrefs_from_to($item->action, ICL_LANGUAGE_CODE,
                $sitepress->get_default_language(), -1, 1);
    }
}

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
   // bpml_activities_clear_cache($item->item_id, 'bpml_google_translation', 'main');
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
		
		$lang = bp_activity_get_meta($result->id, 'bpml_lang');
			
		if(!empty($lang)){
			if($current_language != $lang){
				unset($activity['activities'][$key]);
			}
		}
    }
	
//    if (!empty($bpml_in_activity_loop) && function_exists('bpml_google_translate_button')) {
    if (function_exists('bpml_google_translate_button')) {
        echo bpml_google_translate_button();
    }
    $activity['total'] = count($activity['activities']);
    return $activity;
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
 //   bpml_activities_clear_cache($item->item_id, 'bpml_google_translation', 'main');
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