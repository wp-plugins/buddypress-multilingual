<?php
/*
 * Upgrade code
 */

function bpml_upgrade() {
    $upgrade_failed = FALSE;
    $upgrade_debug = array();
    $version = get_option('bpml_version', FALSE);
    if (empty($version)) {
        $version = BPML_VERSION;
        bpml_install();
    }
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

function bpml_install() {
    global $wpdb, $bpml;

    bpml_save_settings($bpml);

    // Profiles
    $table_name = $wpdb->prefix . "bp_xprofile_data_bpml";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE " . $table_name . " (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
      field_id bigint(20) NOT NULL,
      user_id bigint(20) NOT NULL,
      value longtext,
      lang varchar(10) NOT NULL,
	  PRIMARY KEY (id),
      KEY field_id (field_id),
      KEY user_id (user_id),
      KEY lang (lang)
	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // For users had 1.0.1 and earlier
    bpml_upgrade_110();
}

function bpml_upgrade_110() {
    global $bpml, $wpdb;

    // New activity
    if (!isset($bpml['collected_activities']['new_blog'])) {
        $bpml['collected_activities']['new_blog'] = array(
            'translate_title' => 1,
            'translate_title_cache' => 1,
            'translate_content' => 1,
            'translate_content_cache' => 1,
            'translate_links' => -1
        );
    }

    // Profiles
    $table_name = $wpdb->prefix . "bp_xprofile_data_bpml";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE " . $table_name . " (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
      field_id bigint(20) NOT NULL,
      user_id bigint(20) NOT NULL,
      value longtext,
      lang varchar(10) NOT NULL,
	  PRIMARY KEY (id),
      KEY field_id (field_id),
      KEY user_id (user_id),
      KEY lang (lang)
	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    if (!isset($bpml['profiles'])) {
        $bpml['profiles'] = array('translation' => 'no');
    }

    bpml_save_settings($bpml);

    return TRUE;
}