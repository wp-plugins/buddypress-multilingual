<?php
/*
  Plugin Name: BuddyPress Multilingual
  Plugin URI: http://wpml.org/?page_id=2890
  Description: BuddyPress Multilingual. <a href="http://wpml.org/?page_id=2890">Documentation</a>.
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com
  Version: 1.4.2
  Network: true
 */

/*
  BuddyPress Multilingual is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  BuddyPress Multilingual is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with BuddyPress Multilingual.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Define constants
 */
define('BPML_VERSION', '1.4.2');
define('BPML_PLUGIN_URL', plugins_url(basename(dirname(__FILE__))));

add_action('plugins_loaded', 'bpml_plugins_loaded_hook', 0);
add_action('plugins_loaded', 'bpml_init_check', 11);

/**
 * Trigger this before anything.
 *
 * Force getting cookie language on:
 * Search
 * AJAX call
 */
function bpml_plugins_loaded_hook() {
    if (defined('BP_VERSION') && defined('ICL_SITEPRESS_VERSION')) {
        if (!is_admin() && (isset($_POST['search-terms']) || isset($_REQUEST['action'])
                || (defined('DOING_AJAX') && DOING_AJAX))) {
            add_filter('icl_set_current_language', 'bpml_get_cookie_lang');
        }
    }
}

/**
 * Returns WPML cookie.
 * 
 * @global  $sitepress
 * @param <type> $lang
 * @return <type>
 */
function bpml_get_cookie_lang($lang = '') {
    global $sitepress;
    $lang_cookie = $sitepress->get_language_cookie();
    if (empty($lang_cookie)) {
        return empty($lang) ? ICL_LANGUAGE_CODE : $lang;
    }
    return $lang_cookie;
}

/**
 * Checks if necessary conditions are met.
 * 
 * @global <type> $sitepress_settings
 */
function bpml_init_check() {
    global $sitepress_settings;
    if (defined('BP_VERSION') && defined('ICL_SITEPRESS_VERSION')) {
        if ((!isset($sitepress_settings['language_negotiation_type'])
                || $sitepress_settings['language_negotiation_type'] != 1)
                && is_main_site()) {
            bpml_admin_message('<p>' . __('For BuddyPress Multilingual to work you must set WPML language negotiation to "languages in different directories".') . '</p>');
        } else {
		
			if(isset($_GET['bpml_action']) && $_GET['bpml_action'] = 'dismiss'){
				update_option('bpml_dismiss_notice', 'yes');
			}

            global $bpml;
            $bpml = bpml_get_settings();
            define('BPML_DEBUG', bpml_get_setting('debug', 0));
			
			add_filter('bp_core_get_directory_page_ids', 'bpml_filter_page_ids', 0);
			add_filter('icl_ls_languages', 'bpml_icl_ls_languages_filter');
            
            // Main blog
            if (is_main_site ()) {
                // Profiles
                if (isset($bpml['profiles']) && $bpml['profiles']['translation'] != 'no') {
                    require_once dirname(__FILE__) . '/profiles.php';
                    if (!is_admin() && isset($bpml['profiles']['translate_fields_title'])) {
                        add_filter('bp_get_the_profile_field_name', 'bpml_bp_get_the_profile_field_name_filter');
                    }
                    add_action('xprofile_data_before_save', 'bpml_xprofile_data_before_save_hook');
                    add_action('init', 'bpml_profiles_init');
                    add_action('bp_after_profile_edit_content', 'bpml_profiles_bp_after_profile_edit_content_hook');
                    add_action('bpml_ajax', 'bpml_profiles_ajax');
                    add_filter('bp_get_the_profile_field_value', 'bpml_profiles_bp_get_the_profile_field_value_filter', 0, 3);
                }

                if (!is_admin()) {
                    require_once dirname(__FILE__) . '/frontend.php';
                    add_action('wp_head', 'bpml_wp_head_hook');
                    add_action('wp_footer', 'bpml_wp_footer', 9999);
                    add_action('wp', 'bpml_blogs_redirect_to_random_blog', 0);
                    add_action('bp_core_render_message', 'bpml_show_frontend_notices');

                    // Filter site_url on regular pages
                    add_action('bp_before_header', 'bpml_bp_before_header_hook');
                    add_action('bp_after_footer', 'bpml_bp_after_footer_hook');

                    // Force filtering site_url on:
                    // Search
                    // AJAX call
                    if (isset($_POST['search-terms']) || isset($_REQUEST['action']) || (defined('DOING_AJAX') && DOING_AJAX)) {
                        add_filter('site_url', 'bpml_site_url_filter', 0);
                    }

                    add_filter('admin_url', 'bpml_admin_url_filter', 0, 3);
                    add_filter('bp_core_get_root_domain', 'bpml_bp_core_get_root_domain_filter', 0);
                    add_filter('bp_uri', 'bpml_bp_uri_filter', 0);
					
					add_action('init', 'additional_css_js');
                } else {
                    require_once dirname(__FILE__) . '/admin.php';
                    $version = get_option('bpml_version', FALSE);
                    if (empty($version) || version_compare($version, BPML_VERSION, '<')) {
                        require_once dirname(__FILE__) . '/upgrade.php';
                        bpml_upgrade();
                    }
                    add_action('admin_init', 'bpml_admin_show_stored_admin_notices');
                    add_action('admin_menu', 'bpml_admin_menu');
                    if (isset($_GET['page']) && $_GET['page'] == 'bpml') {
                        require_once dirname(__FILE__) . '/admin-form.php';
                        add_action('admin_init', 'bpml_admin_save_settings_submit');
						add_action('admin_init', 'admin_additional_css_js');
						
						add_action('admin_notices', 'admin_page_notice');
                    }
					
					add_action('admin_notices', 'after_activation_notice');
                }
                add_action('wp_ajax_bpml_ajax', 'bpml_ajax');
                add_action('bpml_ajax', 'bpml_activities_ajax');
            }
        }
    } else if (is_main_site ()) {
        bpml_admin_message('<p>' . __('For BuddyPress Multilingual to work you must enable WPML and BuddyPress.') . '</p>');
    }
}

function after_activation_notice(){
	if(get_option('bpml_dismiss_notice') != 'yes' && $_GET['page'] !== 'bpml'){
		$url = $_SERVER['REQUEST_URI'];
		
		if(strpos($url, '?') !== false){ $url .= '&bpml_action=dismiss'; } else { $url .= '?bpml_action=dismiss'; }
	?>
	<div id="message" class="updated message fade" style="clear: both; margin-top: 5px;">
		<p>
			<?php _e('Translation for activity streams is disabled because it relied on the discontinued Google translate API. We can bring it back with paid Google translation.', 'bpml'); ?>
		</p>
		<p>
			<a class="button-primary" href="<?php echo admin_url('options-general.php?page=bpml'); ?>"><?php _e('Tell us if you are interested', 'bpml'); ?></a>
			<a class="button-secondary" href="<?php echo $url; ?>"><?php _e('Dismiss', 'bpml'); ?></a>
		</p>
	</div>
    <?php
	}
}

function admin_page_notice(){
?>
	<div id="message" class="updated message fade" style="clear: both; margin-top: 5px;">
		<p>
			<?php printf(__('The older activity translation has been disabled because Google discontinued their free machine translation API. Instead of the free translation API, Google now offers paid machine translation. We are considering adding support for this new API, which will allow BuddyPress activity streams to be translated again. This will require paying Google for the translation.
If you are interested in the new paid translation for activity streams, please let us know. Visit <a href="%s" target="_blank">BuddyPress Multilingual</a> forum page and leave a comment. We will be looking at these comments and see if there is real interest in this.', 'elm'), 'http://wordpress.org/support/plugin/buddypress-multilingual');
			?>
		</p>
	</div>
<?php
}

/**
 * Adds hook for CSS styles and jQuery to client-side.
 *
 */
function additional_css_js() {
	wp_enqueue_style('sitepress-language-switcher', ICL_PLUGIN_URL . '/res/css/language-selector.css', array(), ICL_SITEPRESS_VERSION);
	wp_enqueue_style('bpml', BPML_PLUGIN_URL . '/style.css', array(), BPML_VERSION);
	wp_enqueue_script('bpml', BPML_PLUGIN_URL . '/scripts.js', array('jquery'), BPML_VERSION);
}

/**
 * Adds hook for Admin CSS styles and jQuery to admin-side.
 *
 */
function admin_additional_css_js() {
	wp_enqueue_style('bpml', BPML_PLUGIN_URL . '/style.css', array(), BPML_VERSION);
	wp_enqueue_script('bpml', BPML_PLUGIN_URL . '/scripts.js', array('jquery'), BPML_VERSION);
}

/**
 * Returns HTML output for admin message.
 *
 * @param <type> $message
 * @param <type> $class
 * @return <type>
 */
function bpml_message($message, $class = 'updated', $ID = NULL) {
    $ID = is_null($ID) ? '' : ' id="' . $ID . '"';
    return '<div class="message ' . $class . '"' . $ID . '>' . $message . '</div>';
}

/**
 * Adds hook for admin_notices.
 *
 * @param <type> $message
 * @param <type> $class
 */
function bpml_admin_message($message, $class = 'updated',
        $action = 'admin_notices') {
    add_action($action, create_function('$a=1', 'echo \'<div class="message ' . $class . '">' . $message . '</div>\';'));
}

/**
 * Returns default BPML settings.
 * 
 * @return <type>
 */
function bpml_default_settings() {
    return array(
        'debug' => 0
    );
}

/**
 * Returns all settings.
 * 
 * @return <type>
 */
function bpml_get_settings() {
    return apply_filters('bpml_default_settings',
            get_option('bpml', bpml_default_settings()));
}

/**
 * Returns specific setting.
 * 
 * @global <type> $bpml
 * @param <type> $ID
 * @param <type> $default
 * @return <type>
 */
function bpml_get_setting($ID, $default = NULL) {
    global $bpml;
    if (isset($bpml[$ID])) {
        return $bpml[$ID];
    } else if (!is_null($default)) {
        $bpml[$ID] = $default;
        bpml_save_setting($ID, $default);
        return $default;
    }
}

/**
 * Saves all settings.
 *
 * @global array $bpml
 * @param <type> $data
 */
function bpml_save_settings($data) {
    global $bpml;
    $bpml = $data;
    update_option('bpml', $bpml);
}

/**
 * Saves specific setting.
 * 
 * @global array $bpml
 * @param <type> $ID
 * @param <type> $data
 */
function bpml_save_setting($ID, $data) {
    global $bpml;
    $bpml[$ID] = $data;
    update_option('bpml', $bpml);
}

/**
 * Deletes specific setting.
 * 
 * @global  $bpml
 * @param <type> $ID
 */
function bpml_delete_setting($ID) {
    global $bpml;
    if (isset($bpml[$ID])) {
        unset($bpml[$ID]);
        update_option('bpml', $bpml);
    }
}

/**
 * Caches admin notices.
 *
 * Notices are kept until BPML admin page is visited.
 *
 * @global array $bpml
 * @param <type> $ID
 * @param <type> $message
 */
function bpml_store_admin_notice($ID, $message) {
    global $bpml;
    $bpml['admin_notices'][$ID] = $message;
    bpml_save_setting('admin_notices', $bpml['admin_notices']);
}

/**
 * Caches frontend notices.
 *
 * @global array $bpml
 * @param <type> $ID
 * @param <type> $message
 */
function bpml_store_frontend_notice($ID, $message) {
    global $bpml;
    if (!isset($bpml['frontend_notices'])) {
        $bpml['frontend_notices'] = array();
    }
    $bpml['frontend_notices'][$ID] = $message;
    bpml_save_setting('frontend_notices', $bpml['frontend_notices']);
}

/**
 * Displays frontend notices.
 *
 * @global array $bpml
 * @param <type> $ID
 * @param <type> $message
 */
function bpml_show_frontend_notices() {
    global $bpml;
    if (empty($bpml['frontend_notices'])) {
        return '';
    }
    foreach ($bpml['frontend_notices'] as $message) {
        echo bpml_message('<p>' . $message . '</p>', 'bpml-frontend-notice');
    }
    bpml_delete_setting('frontend_notices');
}

/**
 * Fetches WPML language data from subblogs.
 *
 * @global <type> $wpdb
 * @param <type> $blog_id
 * @param <type> $type
 * @param <type> $element_id
 * @return <type>
 */
function bpml_get_wpml_language_data($blog_id, $type = 'post_post',
        $element_id = NULL, $switch_db = FALSE) {
    static $sitepress_settings = array();

    if (isset($sitepress_settings[$blog_id][$type][$element_id])) {
        return $sitepress_settings[$blog_id][$type][$element_id];
    }

    $result = array();
    if ($switch_db) {
        switch_to_blog($blog_id);
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'icl_translations';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        if ($switch_db) {
            restore_current_blog();
        }
        return FALSE;
    }
    if (!isset($sitepress_settings[$blog_id])) {
        $fetch = unserialize($wpdb->get_var("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name='icl_sitepress_settings'"));
        $sitepress_settings[$blog_id]['default_language'] = $fetch['default_language'];
    }
    switch ($type) {
        case 'comment':
            $post_id = $wpdb->get_var("SELECT comment_post_ID FROM {$wpdb->prefix}comments WHERE comment_ID=" . $element_id);
            $post_type = 'post_' . $wpdb->get_var("SELECT post_type FROM {$wpdb->prefix}posts WHERE ID=" . $post_id);
            $result['language'] = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='" . $post_type . "' AND element_id=" . $post_id);
            break;

        default:
            $result['language'] = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='" . $type . "' AND element_id=" . $element_id);
            break;
    }
    $result['default_language'] = $sitepress_settings[$blog_id]['default_language'];
    $sitepress_settings[$blog_id][$type][$element_id] = $result;
    if ($switch_db) {
        restore_current_blog();
    }
    return $result;
}

/**
 * Tries to get language data for item.
 *
 * @global <type> $sitepress
 * @param <type> $item
 * @param <type> $type
 * @param <type> $switch_db
 * @return <type>
 */
function bpml_get_item_language($item = NULL, $type = 'post_post',
        $switch_db = FALSE) {
    $lang = array();
    global $sitepress;
    if ($type == 'post_post' && !empty($sitepress) && isset($_POST['icl_post_language'])) { // In moment of save if WPML is active
        $lang['language'] = $_POST['icl_post_language'];
        $lang['default_language'] = $sitepress->get_default_language();
        $lang['recorded_language'] = $sitepress->get_current_language();
    } else if (!is_null($item)) { // Check in DB
        $lang = bpml_get_wpml_language_data($item->item_id, $type, $item->secondary_item_id, $switch_db);
        if (!empty($lang)) {
            $lang['recorded_language'] = $lang['default_language'];
        }
    }
    // Try to get WPLANG
    if (empty($lang) && !empty($sitepress)) {
        $temp = get_site_option('WPLANG');
        if (!empty($temp)) {
            if (strlen($temp) > 2) {
                $temp = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE locale='" . $temp . "'");
            }
            $lang['default_language'] = $lang['language'] = $lang['recorded_language'] = $temp;
        }
    }
    // Try to get RSS lang
    if (empty($lang)) {
        $temp = get_site_option('rss_language');
        if (!empty($temp)) {
            $lang['default_language'] = $lang['language'] = $lang['recorded_language'] = $temp;
        }
    }
    return $lang;
}

/**
 * BPML AJAX hook.
 */
function bpml_ajax() {
    do_action('bpml_ajax');
    exit;
}

/**
 * Filters pages IDs.
 *
 * @global <type> $sitepress
 * @param <type> $page_ids
 * @return <type>
 */
function bpml_filter_page_ids( $page_ids ){
	global $sitepress;
	
	$page_ids = bp_get_option( 'bp-pages' );
	
	foreach( $page_ids as $k => $page_id ){
		$filtered_page_ids[$k] = icl_object_id( $page_id, 'page', true );
	}

	return $filtered_page_ids;
}