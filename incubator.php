<?php
/*
 * Code in progress/test
 */
/*
 * TODOS
 * - translation for group title
 * - translation for group description
 *
 */

if ($options['groups']['translate_name']) {
    add_filter('bp_get_group_name', 'bpml_bp_get_group_name_filter', 9999);
}
if ($options['groups']['translate_description']) {
    add_filter('bp_get_group_description', 'bpml_bp_get_group_description_filter', 9999);
//    add_filter('bp_get_group_description_excerpt', 'bpml_bp_get_group_description_filter', 9999);
}

// Groups

add_filter('bpml_default_settings', 'bpml_groups_default_settings');

function bpml_groups_default_settings() {
    return array('groups' => array(
            'filter' => 0,
            'display_orphans' => 'all',
            'orphans_fix' => 0,
            'translate_name' => 0,
            'translate_description' => 0,
    ));
}



function bpml_bp_get_group_name_filter($name) {
    global $bp, $sitepress;
    $default_language = $sitepress->get_default_language();
    $original_language = groups_get_groupmeta($bp->groups->current_group->id, 'bpml_lang');
    if ($original_language == ICL_LANGUAGE_CODE) {
        return $name;
    }
    $options = bpml_settings();
    if ($options['groups']['translate_name'] === 'google_translate_store') {
        $translations = groups_get_groupmeta($bp->groups->current_group->id, 'bpml_google_translation');
        if (!isset($translations[ICL_LANGUAGE_CODE])) {
            bpml_google_translate_wrap($name);
        }
    } else if ($options['groups']['translate_name'] === 'google_translate_js') {

    }
    return $name;
}
