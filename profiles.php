<?php
/*
 * Profile functions
 * @todo Register strings from field names
 * @todo global turn off
 * @todo separate admin/frontend
 * @todo see bpml registered and apply_filters
 */

function bpml_profiles_init() {
    if (!get_option('bpml_xprofile_installed', FALSE)) {
        bpml_profiles_install();
    }
//    global $bp;
//    $profile_link = $bp->loggedin_user->domain . $bp->profile->slug . '/';
//    bp_core_new_subnav_item( array( 'name' => __( 'Translate Profile', 'buddypress' ), 'slug' => 'bpml-translate', 'parent_url' => $profile_link, 'parent_slug' => $bp->profile->slug, 'screen_function' => 'bpml_xprofile_screen_edit_profile', 'position' => 20 ) );
}

function bpml_profiles_bp_after_profile_edit_content_hook() {
    global $bp, $bpml, $sitepress, $bpml_profiles_field_value_suppress_filter;
    $bpml_profiles_field_value_suppress_filter = TRUE;
    require_once dirname(__FILE__) . '/google-translate.php';
    if ($bpml['profiles']['translation'] != 'user'
            && $bpml['profiles']['translation'] != 'user-missing') {
        return '';
    }
    $default_language = $sitepress->get_default_language();
    $langs = $sitepress->get_active_languages();
    $group = BP_XProfile_Group::get(array(
                'fetch_fields' => true,
                'profile_group_id' => $bp->action_variables[1],
                'fetch_field_data' => true
            ));
    echo '<h4>Translate fields</h4>';
    foreach ($group[0]->fields as $field) {
        if (!isset($bpml['profiles']['fields'][$field->id]) || empty($field->data->value)) {
            continue;
        }
        echo '<div><a href="javascript:void(0);" onclick="jQuery(this).next(\'div\').toggle();">' . $field->name . '</a><div style="display:none;">';
        foreach ($langs as $lang) {
            if ($lang['code'] == $default_language) {
                continue;
            }
//            echo '<button onclick="jQuery(\'#bpml-profiles-form-field-' . $field->id . '-' . $lang['code'] . '\').toggle();">' . $field->name . ' ' . $lang['english_name'] . '</button>';

            echo '<input class="bpml-profiles-field-toggle-button" type="button" onclick="jQuery(\'#bpml-profiles-form-field-' . $field->id . '-' . $lang['code'] . '\').toggle();" value="' . $lang['english_name'] . '" />';
            echo '<form style="display:none;margin:0;" id="bpml-profiles-form-field-' . $field->id . '-' . $lang['code'] . '" class="bpml-form-ajax standard-form" method="post" action="' . admin_url() . '/admin-ajax.php">';
            echo $field->type == 'textarea' ? '<textarea class="bpml-profiles-field-content" name="content" cols="40" rows="5">' . apply_filters('bp_get_the_profile_field_edit_value', bpml_profiles_get_field_translation($field->id, $lang['code'], $field->data->value)) . '</textarea>' : '<input type="text" class="bpml-profiles-field-content" name="content" value="' . apply_filters('bp_get_the_profile_field_edit_value', bpml_profiles_get_field_translation($field->id, $lang['code'], $field->data->value)) . '" />';
            echo '
                        <br />
                    <input type="hidden" value="' . $field->id . '" name="bpml_profiles_translate_field" />
                    <input type="hidden" value="' . $lang['code'] . '" name="bpml_profiles_translate_field_lang" />
                    <input type="hidden" name="dummy" class="bpml_profiles_translate_field_google_translated" value="' . bpml_google_translate(apply_filters('bp_get_the_profile_field_edit_value', $field->data->value), $default_language, $lang['code']) . '" />
                    <input type="submit" value="Save translation" name="bpml_profiles_translate_field" />
                    <input type="submit" value="Translate with Google" name="bpml_profiles_translate_with_google" class="bpml_profiles_translate_with_google" />
                    <div class="bmp-ajax-update"></div>
</form><br />';
//            $selected = $activities_template->activity->lang == $lang['code'] ? ' selected="selected"' : '';
//            $data .= '<option value="' . $lang['code'] . '"' . $selected . '>' . $lang['english_name'] . '</option>';
        }
        echo '</div></div>';
    }
//    echo '<pre>';print_r($group);
}

function bpml_profiles_ajax() {
    if (isset($_POST['bpml_profiles_translate_field'])) {
        global $current_user;
        $field_id = $_POST['bpml_profiles_translate_field'];
        $lang = $_POST['bpml_profiles_translate_field_lang'];
        bpml_profile_update_translation($current_user->ID, $field_id, $lang, $_POST['content']);
        echo json_encode(array('output' => 'Done'));
    }
}

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

function bpml_profiles_bp_custom_profile_edit_fields_hook() {
    global $field, $bpml;
    if (($bpml['profiles']['translation'] == 'user'
            || $bpml['profiles']['translation'] == 'user-missing')
            && isset($bpml['profiles']['fields'][$field->id])) {
        global $sitepress;
        $default_language = $sitepress->get_default_language();
        $langs = $sitepress->get_active_languages();
        $data = '';
        foreach ($langs as $lang) {
            if ($lang['code'] == $default_language) {
                continue;
            }
            echo '<form class="bpml-form-ajax">
                <textarea name="bpml[profiles][fields][' . $field->id . '][' . $lang['code'] . ']">' . apply_filters('bp_get_the_profile_field_edit_value', bpml_profiles_get_field_translation($field->id, $lang['code'], $field->data->value)) . '</textarea>
                    <div class="bmp-ajax-update"></div>
                    <input type="submit" />

</form>';
//            $selected = $activities_template->activity->lang == $lang['code'] ? ' selected="selected"' : '';
//            $data .= '<option value="' . $lang['code'] . '"' . $selected . '>' . $lang['english_name'] . '</option>';
        }
        echo '<pre>';
        print_r($field);
    }
}

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
//            bpml_profile_update_translation($current_user->ID, $field_id, $lang, $_POST['content']);
            bpml_debug('Fetching Google translation for field: ' . $field_id);
        }
        return $value;
    } else {
        return $translation;
    }
}

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

function bpml_profiles_default_settings() {
    return array(
        'profiles' => array(
            'translation' => 'no'
        ),
    );
}

function bpml_profiles_bpml_default_settings_filter($settings) {
    return array_merge(bpml_profiles_default_settings(), $settings);
}

function bpml_profiles_admin_form() {
    global $bpml;
    echo '<h2>Profile fields</h2>';
    // Use google translate (DB|JS), enable user to translate
    echo '<label><input type="radio" name="bpml[profiles][translation]" value="no"' . (($bpml['profiles']['translation'] == 'no' || !isset($bpml['profiles']['translation'])) ? ' checked="checked"' : '') . ' />&nbsp;No translation</label>&nbsp;&nbsp;<br />';
    echo '<label><input type="radio" name="bpml[profiles][translation]" value="user"' . ($bpml['profiles']['translation'] == 'user' ? ' checked="checked"' : '') . ' />&nbsp;Allow user to translate</label>&nbsp;&nbsp;<br />';
    echo '<label><input type="radio" name="bpml[profiles][translation]" value="user-missing"' . ($bpml['profiles']['translation'] == 'user-missing' ? ' checked="checked"' : '') . ' />&nbsp;Allow user to translate but fill missing with Google translation</label>&nbsp;&nbsp;<br />';
//    echo '<label><input type="radio" name="bpml[profiles][translation]" value="google-store"' . ($bpml['profiles']['translation'] == 'google-store' ? ' checked="checked"' : '') . ' />&nbsp;Google Translate store in DB</label>&nbsp;&nbsp;<br />';
//    echo '<label><input type="radio" name="bpml[profiles][translation]" value="google-js"' . ($bpml['profiles']['translation'] == 'google-js' ? ' checked="checked"' : '') . ' />&nbsp;Google Translate JS</label>&nbsp;&nbsp;';

    echo '<br /><br />';

    echo 'Select fields that can be translated:';
    // get fields
    $groups = BP_XProfile_Group::get(array(
                'fetch_fields' => true
            ));
    if (empty($groups)) {
        echo 'No profile fields.';
        return FALSE;
    }

    foreach ($groups as $group) {
        if (empty($group->fields)) {
            echo 'No fields in this group';
            continue;
        }
        echo '<h4>' . $group->name . '</h4>';
        foreach ($group->fields as $field) {
            $checked = isset($bpml['profiles']['fields'][$field->id]) ? ' checked="checked"' : '';
            echo '<label><input type="checkbox" name="bpml[profiles][fields][' . $field->id . ']" value="1"' . $checked . ' />&nbsp;' . $field->name . '</label>&nbsp;&nbsp;';
        }
    }
//    echo '<pre>'; print_r($groups);
    echo '<br /><br /><input type="submit" value="Save Settings" name="bpml_save_options" class="submit button-primary" />';
    echo '<br /><br />';
}

/**
 * Install.
 *
 * @global  $wpdb
 */
function bpml_profiles_install() {
    global $wpdb;
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
    } else {
        update_option('bpml_xprofile_installed', 1);
    }
}