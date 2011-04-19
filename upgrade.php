<?php
/*
 * Upgrade code
 */

function bpml_upgrade() {
    $upgrade_failed = FALSE;
    $upgrade_debug = array();
    $version = get_option('bpml_version', '1.0.1');
    if (version_compare($version, BPML_VERSION, '<')) {
        $first_step = str_replace('.', '', $version);
        $last_step = str_replace('.', '', BPML_VERSION);
        for ($index = $first_step; $index <= $last_step; $index++) {
            if (function_exists('bpml_upgrade_' . $index)) {
                $response = call_user_func('bpml_upgrade_' . $index);
                if ($response !== TRUE) {
                    $upgrade_failed = TRUE;
                    $upgrade_debug[$first_step][$index] = $response;
                }
            }
        }
    }
    if ($upgrade_failed == TRUE) {
        update_option('bpml_upgrade_debug', $upgrade_debug);
        bpml_store_admin_notice('upgrade_error', '<p>BuddyPress Multilingual: There has been problems with upgrade.</p>');
    }
    update_option('bpml_version', BPML_VERSION);
}

function bpml_upgrade_110() {
    global $bpml;
    if (!isset($bpml['collected_activities']['new_blog'])) {
        $bpml['collected_activities']['new_blog'] = array(
            'translate_title' => 1,
            'translate_title_cache' => 1,
            'translate_content' => 1,
            'translate_content_cache' => 1,
            'translate_links' => -1
        );
        bpml_save_settings($bpml);
    }
    return TRUE;
}