<?php
/*
 * Google translation functions
 * @todo Auto detect
 * @todo Activity loop and google translate button
 */

/**
 * Sets AJAX indicator.
 *
 * @global boolean $bpml_google_translate_ajax
 */
function bpml_google_translate_indicate_ajax() {
    global $bpml_google_translate_ajax;
    $bpml_google_translate_ajax = TRUE;
}

/**
 * Sets Google translated contents.
 * 
 * @param <type> $result
 * @param <type> $default_language
 * @param <type> $current_language
 * @param <type> $options
 * @return <type>
 */
function bpml_google_translate_activity_filter($result, $default_language,
        $current_language, $type_options) {

    $to_translate_title = FALSE;
    $to_translate_content = FALSE;

    if ((!empty($result->lang_recorded))) {
        if ($result->lang_recorded != $current_language && $type_options['translate_title']) {
            $to_translate_title = TRUE;
        }
    } else if ($result->lang != $current_language && $type_options['translate_title']) {
        $to_translate_title = TRUE;
    }

    if ($result->lang != $current_language && !empty($result->content) && $type_options['translate_content']) {
        $to_translate_content = TRUE;
    }

    // Apply display filters
    $result->action = apply_filters('bp_get_activity_action', $result->action);
    if (!empty($result->content)) {
        $result->content = apply_filters('bp_get_activity_content_body', $result->content);
    }

    global $bpml;
    // Stored in DB
    if ($bpml['activities']['enable_google_translation'] === 'store') {
        // Store original values
        $original_translation_title = $result->action;
        $original_translation_content = $result->content;
        // Check if cached
        $translation = bp_activity_get_meta($result->id, 'bpml_google_translation');

        // Translate title
        if ($to_translate_title && $type_options['translate_title_cache']) {
            if (empty($translation) || !isset($translation[$current_language]) || !isset($translation[$current_language]['title'])) {
                bpml_debug('<p>Fetched Google translation for activity title ID:' . $result->id . ' (original lang \'' . $result->lang . '\', current lang \'' . $current_language . '\')</p>');
                // Translate with Google
                if (!empty($result->lang_recorded)) {
                    $translation_title = bpml_google_translate($result->action, $result->lang_recorded, $current_language);
                } else {
                    $translation_title = bpml_google_translate($result->action, $result->lang, $current_language);
                }
                $translation[$current_language]['title'] = $translation_title;
                // Set cache
                bp_activity_update_meta($result->id, 'bpml_google_translation', $translation);
            } else {
                bpml_debug('<p>Cached Google translation for activity title ID:' . $result->id . ' (original lang \'' . $result->lang . '\', current lang \'' . $current_language . '\')</p>');
                $translation_title = $translation[$current_language]['title'];
            }
        } else if ($to_translate_title) {
            if (!empty($result->lang_recorded)) {
                $translation_title = bpml_google_translate($result->action, $result->lang_recorded, $current_language);
            } else {
                $translation_title = bpml_google_translate($result->action, $result->lang, $current_language);
            }
        }

        // Translate content
        if ($to_translate_content && $type_options['translate_content_cache']) {
            if (empty($translation) || !isset($translation[$current_language]) || !isset($translation[$current_language]['content'])) {
                bpml_debug('<p>Fetched Google translation for activity content ID:' . $result->id . ' (original lang \'' . $result->lang . '\', current lang \'' . $current_language . '\')</p>');
                // Translate with Google
                $translation_content = bpml_google_translate($result->content, $result->lang, $current_language);
                $translation[$current_language]['content'] = $translation_content;
                // Set cache
                bp_activity_update_meta($result->id, 'bpml_google_translation', $translation);
            } else {
                bpml_debug('<p>Cached Google translation for activity content ID:' . $result->id . ' (original lang \'' . $result->lang . '\', current lang \'' . $current_language . '\')</p>');
                $translation_content = $translation[$current_language]['content'];
            }
        } else if ($to_translate_content) {
            $translation_content = bpml_google_translate($result->content, $result->lang, $current_language);
        }

        // Wrap contents
        if ($to_translate_title) {
            $result->action = '<div class="bpml-translation-original-toggle bpml-translation-original-toggle-title bpml-translation-original-toggle-lang-' . $result->lang . '"><a href="#">' . __('Translation', 'bpml') . '</a></div><div class="bpml-translation-original-wrapper bpml-translation-original-wrapper-title">' . $original_translation_title . '</div>' . $translation_title;
        }
        if ($to_translate_content) {
            $result->content = '<div class="bpml-translation-original-toggle bpml-translation-original-toggle-content bpml-translation-original-toggle-lang-' . $result->lang . '"><a href="#">' . __('Translation', 'bpml') . '</a></div><div class="bpml-translation-original-wrapper bpml-translation-original-wrapper-content">' . $original_translation_content . '</div>' . $translation_content;
        }
    } else if ($bpml['activities']['enable_google_translation'] === 'js') {

        // Translate with JS (wrap contents)

        if ($to_translate_title) {
            if (!empty($result->lang_recorded)) {
                $result->action = bpml_google_translate_wrap($result->action, $result->lang_recorded);
            } else {
                $result->action = bpml_google_translate_wrap($result->action, $result->lang);
            }
        }
        if ($to_translate_content) {
            $result->content = bpml_google_translate_wrap($result->content, $result->lang);
        }
    }
    return $result;
}

/**
 * Translates strings using Google Translate.
 *
 * @staticvar string $client
 * @param <type> $item
 * @param <type> $current_language
 * @return <type>
 */
function bpml_google_translate($content, $from_language, $to_language) {
    static $client = NULL;
    if (is_null($client)) {
        $client = new WP_Http();
    }
    $gtranslateurl = 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=%s&langpair=%s|%s';
    $url = sprintf($gtranslateurl, urlencode($content), $from_language, $to_language);
    $url = str_replace('|', '%7C', $url);
    $response = $client->request($url);
    if (!is_wp_error($response) && ($response['response']['code'] == '200')) {
        $translation = json_decode($response['body']);
        $content = $translation->responseData->translatedText;
    }
    return $content;
}

/**
 * Renders Google translate control button.
 *
 * Only rendered once per page.
 *
 * @global <type> $bpml_google_translate_js
 * @staticvar boolean $called
 * @param string $output
 * @return string
 */
function bpml_google_translate_button($output = '') {
    global $bpml_google_translate_js, $bpml_in_activity_loop;
//    if (empty($bpml_in_activity_loop)) {
//        return '';
//    }
    static $called = FALSE;
    if ($called) {
        return '';
    }
    $called = TRUE;
    if (!empty($bpml_google_translate_js)) {
        $output .= '<div id="bpml_google_sectional_element"></div>' . bpml_google_translate_js();
    }
    return $output;
}

/**
 * Wraps content in divs found by Google translate.
 *
 * @global boolean $bpml_google_translate_js
 *      Sets flag that translate button is needed
 * @param <type> $content
 * @param <type> $lang
 * @param <type> $element
 * @return <type>
 */
function bpml_google_translate_wrap($content, $lang, $element = 'div') {
    global $bpml_google_translate_js, $bpml_google_translate_ajax;
    $suffix = !empty($bpml_google_translate_ajax) ? '-ajax' : '';
    $bpml_google_translate_js = TRUE;
    return '<div class="bpml-goog-trans-section' . $suffix . '" lang="' . $lang . '"><div class="bpml-goog-trans-control' . $suffix . '"></div>' . $content . '</div>';
}

/**
 * Returns JS needed for Google translate.
 *
 * @param string $lang
 * @return <type>
 */
function bpml_google_translate_js($lang = NULL) {
    if (is_null($lang)) {
        $lang = ICL_LANGUAGE_CODE;
    }
    global $bpml_google_translate_ajax;
    $suffix = !empty($bpml_google_translate_ajax) ? '-ajax' : '';
    return '<script type=\'text/javascript\'>
function googleSectionalElementInit() {
  new google.translate.SectionalElement({
    sectionalNodeClassName: "bpml-goog-trans-section' . $suffix . '",
    controlNodeClassName: "bpml-goog-trans-control' . $suffix . '",
    background: "#ffffcc"
  }, "bpml_google_sectional_element");
}
</script>
<script src="http://translate.google.com/translate_a/element.js?cb=googleSectionalElementInit&amp;ug=section&amp;hl=' . $lang . '"></script>';
}
