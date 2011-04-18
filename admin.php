<?php
/*
 * Admin functions
 */

/**
 * Adds admin menu
 */
function bpml_admin_menu() {
    add_options_page(
            'BuddyPress Multilingual',
            'BuddyPress Multilingual',
            'manage_options',
            'bpml',
            'bpml_admin_page');
}

/**
 * Renders stored admin notices.
 * 
 * @return <type>
 */
function bpml_admin_show_stored_admin_notices() {
    $messages = bpml_get_setting('admin_notices');
    if (empty($messages)) {
        return '';
    }
    foreach ($messages as $message) {
        bpml_admin_message($message, 'updated', 'all_admin_notices');
    }
}