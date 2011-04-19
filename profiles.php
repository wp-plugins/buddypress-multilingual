<?php
/*
 * Profile functions
 * @todo delete field hook NO HOOK
 */

/**
 * Init hook.
 */
function bpml_profiles_init() {}

/**
 * Adds translation options on 'Edit Profile' page.
 * 
 * @global <type> $bp
 * @global <type> $bpml
 * @global <type> $sitepress
 * @global boolean $bpml_profiles_field_value_suppress_filter
 * @return <type>
 */
function bpml_profiles_bp_after_profile_edit_content_hook() {
    global $bp, $bpml, $sitepress, $bpml_profiles_field_value_suppress_filter;
    $bpml_profiles_field_value_suppress_filter = TRUE;
    require_once dirname(__FILE__) . '/google-translate.php';
    $default_language = $sitepress->get_default_language();
    $langs = $sitepress->get_active_languages();
    $group = BP_XProfile_Group::get(array(
                'fetch_fields' => true,
                'profile_group_id' => $bp->action_variables[1],
                'fetch_field_data' => true
            ));
    echo '<a name="bpml-translate-fields">&nbsp;</a><br /><h4>Translate fields</h4>';
    foreach ($group[0]->fields as $field) {
        if (!isset($bpml['profiles']['fields'][$field->id]) || empty($field->data->value)) {
            continue;
        }
        echo '<div><a href="javascript:void(0);" onclick="jQuery(this).next(\'div\').toggle();">' . $field->name . '</a><div style="display:none;">';
        foreach ($langs as $lang) {
            if ($lang['code'] == $default_language) {
                continue;
            }
            echo '<input class="bpml-profiles-field-toggle-button" type="button" onclick="jQuery(\'#bpml-profiles-form-field-' . $field->id . '-' . $lang['code'] . '\').toggle();" value="' . $lang['english_name'] . '" />';
            echo '<form style="display:none;margin:0;" id="bpml-profiles-form-field-' . $field->id . '-' . $lang['code'] . '" class="bpml-form-ajax standard-form" method="post" action="' . admin_url('admin-ajax.php') . '">';
            echo $field->type == 'textarea' ? '<textarea class="bpml-profiles-field-content" name="content" cols="40" rows="5">' . apply_filters('bp_get_the_profile_field_edit_value', bpml_profiles_get_field_translation($field->id, $lang['code'], $field->data->value)) . '</textarea>' : '<input type="text" class="bpml-profiles-field-content" name="content" value="' . apply_filters('bp_get_the_profile_field_edit_value', bpml_profiles_get_field_translation($field->id, $lang['code'], $field->data->value)) . '" />';
            echo '
                        <br />
                    <input type="hidden" value="' . $field->id . '" name="bpml_profiles_translate_field" />
                    <input type="hidden" value="' . $lang['code'] . '" name="bpml_profiles_translate_field_lang" />
                    <input type="hidden" name="dummy" class="bpml_profiles_translate_field_google_translated" value="' . bpml_google_translate(apply_filters('bp_get_the_profile_field_edit_value', $field->data->value), $default_language, $lang['code']) . '" />
                    <input type="submit" value="Save translation" name="bpml_profiles_translate_field" />
                    <input type="submit" value="Get translation from Google" name="bpml_profiles_translate_with_google" class="bpml_profiles_translate_with_google" />
                    <div class="bmp-ajax-update"></div>
</form><br />';
        }
        echo '</div></div>';
    }
}

/**
 * Processes AJAX call for updating field translation.
 * 
 * @global <type> $current_user
 */
function bpml_profiles_ajax() {
    if (isset($_POST['bpml_profiles_translate_field']) && is_user_logged_in()) {
        global $current_user;
        $field_id = $_POST['bpml_profiles_translate_field'];
        $lang = $_POST['bpml_profiles_translate_field_lang'];
        bpml_profile_update_translation($current_user->ID, $field_id, $lang, $_POST['content']);
        echo json_encode(array('output' => 'Done'));
    }
}

/**
 * Updates field translation.
 * 
 * @global $wpdb $wpdb
 * @param <type> $user_id
 * @param <type> $field_id
 * @param <type> $lang
 * @param <type> $content
 */
function bpml_profile_update_translation($user_id, $field_id, $lang, $content) {
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}bp_xprofile_data_bpml WHERE field_id=%d AND user_id=%d AND lang=%s", $field_id, $user_id, $lang));

    if (empty($exists)) {
        $wpdb->insert($wpdb->prefix . 'bp_xprofile_data_bpml', array(
            'field_id' => $field_id,
            'user_id' => $user_id,
            'lang' => $lang,
            'value' => $content,
                ), array('%d', '%d', '%s', '%s'));
    } else {
        $wpdb->update($wpdb->prefix . 'bp_xprofile_data_bpml', array(
            'value' => $_POST['content']
                ), array(
            'user_id' => $user_id,
            'field_id' => $field_id,
            'lang' => $lang),
                array('%s'),
                array('%d', '%d', '%s'));
    }
}

/**
 * Fetches field translation.
 *
 * @global $wpdb $wpdb
 * @global  $bpml
 * @global  $sitepress
 * @global boolean $bpml_profiles_field_value_suppress_filter
 * @param <type> $field_id
 * @param <type> $lang
 * @param <type> $value
 * @return <type>
 */
function bpml_profiles_get_field_translation($field_id, $lang, $value = '') {
    global $wpdb, $bpml, $sitepress, $bpml_profiles_field_value_suppress_filter;
    if ($sitepress->get_default_language() == $lang) {
        return $value;
    }
    $translation = apply_filters('bp_get_the_profile_field_edit_value', $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}bp_xprofile_data_bpml WHERE field_id=%d and lang=%s", $field_id, $lang)));
    if (empty($translation)) {
        bpml_debug('Missing tranlsation for field: ' . $field_id);
        if ($bpml['profiles']['translation']['user-missing'] && empty($bpml_profiles_field_value_suppress_filter)) {
            require_once dirname(__FILE__) . '/google-translate.php';
            $value = bpml_google_translate(apply_filters('bp_get_the_profile_field_edit_value', $value), $sitepress->get_default_language(), $lang);
            bpml_debug('Fetching Google translation for field: ' . $field_id);
        }
        return $value;
    } else {
        return $translation;
    }
}

/**
 * Profilae field value filter.
 * 
 * @global  $sitepress
 * @global  $bpml
 * @global boolean $bpml_profiles_field_value_suppress_filter
 * @param <type> $value
 * @param <type> $type
 * @param <type> $field_id
 * @return <type>
 */
function bpml_profiles_bp_get_the_profile_field_value_filter($value, $type,
        $field_id) {
    global $sitepress, $bpml, $bpml_profiles_field_value_suppress_filter;
    if (!empty($bpml_profiles_field_value_suppress_filter)) {
        return $value;
    }
    if (!isset($bpml['profiles']['fields'][$field_id])) {
        return $value;
    }
    $lang = $sitepress->get_current_language();
    $value = bpml_profiles_get_field_translation($field_id, $lang, $value);
    return $value;
}

/**
 * Notices user about changed fields.
 * 
 * @param <type> $field
 */
function bpml_xprofile_data_before_save_hook($field) {
    bpml_store_frontend_notice('profile-field-updated', '<a href="#bpml-translate-fields">Check if fields need translation updated.</a>');
}

/**
 * Translates field names.
 * 
 * @global  $sitepress
 * @global <type> $field
 * @staticvar array $cache
 * @param <type> $name
 * @return array
 */
function bpml_bp_get_the_profile_field_name_filter($name) {
    global $sitepress, $field;
    if ($sitepress->get_default_language() == ICL_LANGUAGE_CODE) {
        return $name;
    }
    static $cache = NULL;
    if (is_null($cache)) {
        $cache = get_option('bpml_profile_fileds_names', array());
    }
    if (isset($cache[$field->id][ICL_LANGUAGE_CODE])) {
        return $cache[$field->id][ICL_LANGUAGE_CODE];
    }
    require_once dirname(__FILE__) . '/google-translate.php';
    $name = bpml_google_translate($name, $sitepress->get_default_language(), ICL_LANGUAGE_CODE);
    $cache[$field->id][ICL_LANGUAGE_CODE] = $name;
    update_option('bpml_profile_fileds_names', $cache);
    return $name;
}