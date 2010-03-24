<?php
/*
Plugin Name: BuddyPress Multilingual
Plugin URI: http://wpml.org/?page_id=2890
Description: BuddyPress Multilingual. <a href="http://wpml.org/?page_id=2890">Documentation</a>.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version: 1.0.0
Site Wide only: true
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

class SitePressBp {

    private $abspath;
    private $relpath;
    
    private $settings; // WPML settings
    public $lang;
    public $default_lang;
    public $active_langs;
    
    public $siteurl; // Original URL
    public $siteurl_new; // Lang URL
    
    public $blogs = array(); // All blogs info
    public $blogs_search = array(); // Search array to match hrefs
    public $home_blog; // Main BP blog
    public $blog; // Current blog
    
    private $https;

    function __construct() {
        if (defined('WP_ADMIN')) {
            return; // Don't want to use plugin in admin area yet.
        }
        if ($_SERVER['PHP_SELF'] == '/wp-load.php') {
            add_filter('icl_set_current_language', array(&$this, 'ajax_lang'));
        }
        add_action('plugins_loaded', array(&$this,'init'), 2); // after WPML, before Bp
        $this->abspath = dirname(__FILE__);
        $this->relpath = WP_CONTENT_URL . '/' . basename(dirname(dirname(__FILE__))) . '/' . basename(dirname(__FILE__));
        $this->https = ($_SERVER['HTTPS'] == 'on') ? 's' : '';
    }

    function init() {
        if (!defined('BP_VERSION') || !defined('ICL_SITEPRESS_VERSION')) {
            return;
        }
        
        global $current_blog;
        $this->blog = $current_blog;
            // Use only on main blog.
        if ($current_blog->blog_id != BP_ROOT_BLOG) {
            return;
        }
        
        $this->siteurl = 'http' . $this->https . '://' . $this->blog->domain . rtrim($this->blog->path, '/');
        
            // We'll leave this for possible future usage
        /*if (!defined('ICL_SITEPRESS_VERSION')) {
            if ($this->blog->blog_id == BP_ROOT_BLOG) return;
            $home = get_blog_option( BP_ROOT_BLOG, 'siteurl' );
            if ( !$_SERVER['HTTP_REFERER'] && strpos($_SERVER['HTTP_REFERER'],$this->siteurl) !== false ) return;
            $url_lang = $this->get_url_lang();
            $is_lang = $this->is_lang($url_lang);
            if ( $is_lang != false ) {
                $redirect = 'http'.$this->https.'://'.$_SERVER['HTTP_HOST'] . '/' . str_replace('/'.$url_lang, '', ltrim($_SERVER['REQUEST_URI'],'/') );
                header('Location: '.$redirect.'' );
                exit;
            }
            return;
        }*/
        
        $blogs  = get_blog_list( 0, 'all' );
        foreach ($blogs as $k => $v) {
            if ($v['blog_id'] == BP_ROOT_BLOG) {
                $this->home_blog = rtrim('http' . $this->https . '://' . $v['domain'] . $v['path'], '/');
            } else if ($this->siteurl.'/' != 'http' . $this->https . '://' . $v['domain'] . $v['path']) {
                $this->blogs_search[] = rtrim('http' . $this->https . '://' . $v['domain'] . $v['path'], '/');
            }
            $this->blogs[$v['blog_id']]['domain'] = $v['domain'];
            $this->blogs[$v['blog_id']]['path'] = $v['path'];
            $this->blogs[$v['blog_id']]['fullpath'] = rtrim('http' . $this->https . '://'.$v['domain'] . $v['path'], '/');
        }
        
        global $sitepress;
          //remove_action('plugins_loaded', array($sitepress,'init'));
          //add_action('plugins_loaded', array($sitepress,'init'), 1);
        remove_action('pre_option_home', array($sitepress,'pre_option_home'));
        
        $this->settings = get_option('icl_sitepress_settings');
        $this->active_langs = $sitepress->get_active_languages();
        $this->lang = $sitepress->get_current_language();
        $this->default_lang = $sitepress->get_default_language();
		
			// Moved from pre_option_home()
		if ($this->settings['language_negotiation_type'] == 1) {
            if ($this->lang == $this->default_lang) {
                $this->siteurl_new = $this->siteurl;
            } else {
                $this->siteurl_new = $this->check_doubles($this->siteurl . '/'. $this->lang);
            }
        }
        
        add_action('wp_print_styles', array(&$this,'stylesheet'));
        add_action('init', array(&$this,'translate_widgets'));
        
            // Check language switcher CSS loaded
        add_action('template_redirect', array(&$this,'check_css'));
        add_action('wp_head', array(&$this,'check_css_2'));
        
            // Blog URL HOOKS
        add_filter('pre_option_home', array(&$this,'pre_option_home'), 11);
        //add_filter('option_home', array(&$this,'option_siteurl'), 11);
        add_filter('option_siteurl', array(&$this,'option_siteurl'), 11, 2);
        add_filter('blog_option_siteurl', array(&$this,'blog_option_siteurl'), 11, 2);
        add_filter('blog_option_home', array(&$this,'blog_option_siteurl'), 11, 2);
        add_filter('bp_core_get_root_domain', array(&$this,'bp_core_get_root_domain'), 999);
        add_filter('site_url', array(&$this,'site_url'),11,3);
        
            // BP Ajax Hooks
        //add_filter('bp_ajax_querystring', array(&$this,'bp_ajax_querystring'), 0);
        
            // WP HOOKS
        add_filter('wp_redirect', array(&$this,'redirect') ); // messes up subblog login?
        add_filter('comment_post_redirect', array(&$this,'redirect'), 9999, 1);
        add_filter('logout_url', array(&$this,'filter_wp_logout_url'), 9999, 2);
        //add_action( 'switch_blog', array(&$this,'switch_blog'));
        add_action('switch_blog_back', array(&$this,'switch_blog_back'), 0, 2);
        
        add_action('bp_adminbar_menus', array(&$this,'switch_blog'), 0);
        add_action('bp_adminbar_menus', array(&$this,'adminbar_lang_switcher_menu'), 99);
        
            // This checks if we link from main blog to subblog with unactive lang;
        //$this->check_main_to_subblog();
        
        include $this->abspath . '/hooks.php';
    }

    function post_init() {
        if (defined('BP_VERSION') && defined('ICL_SITEPRESS_VERSION')) {
            global $bp;
            $bp->siteurl = $this->siteurl;
        }
    }

    function ajax_lang($lang) {
        global $sitepress;
        return $sitepress->get_language_cookie();
    }

    function filter_member_link($str) {
        preg_match('/href=["\'](.+?)["\']/', $str, $match);
        $str = str_replace($match[1], $this->option_siteurl($match[1]), $str);
        return $str;
    }

    function check_css() {
        $this->switch_template_called = true;
    }

    function check_css_2() {
        if (!$this->switch_template_called) {
            echo '<link rel="stylesheet" href="'. ICL_PLUGIN_URL . '/res/css/language-selector.css?v='.ICL_SITEPRESS_VERSION.'" type="text/css" media="all" />';
        }
    }

    function bp_ajax_querystring($str) {
        return $str;
    }

    function pre_option_home() {
        $dbbt = debug_backtrace();
        if ($dbbt[3]['file'] != @realpath(TEMPLATEPATH . '/header.php') ||  $this->lang == $this->default_lang) {
            return false;
        }
        if ($this->settings['language_negotiation_type'] == 1) {
				// Moved to init()
            //$this->siteurl_new = $this->check_doubles($this->siteurl . '/'. $this->lang);
            return $this->siteurl_new;
        } else {
            return false;
        }
    }

    function option_siteurl($url, $code = false) {
        if ($this->switch_blog) {
            return $url;
        }
        if (!$code && $this->lang == $this->default_lang) {
            return $url;
        }
        $lang = ($code) ? $code : $this->lang;
        if ($this->settings['language_negotiation_type'] == 1) {
            $subblog = $this->check_subblog($url);
            $url_match = ($subblog) ? $subblog : $this->siteurl;
            $url = str_replace ( $url_match, $url_match . '/'. $lang, $url );
            return $this->check_doubles($url);
        } else {
            return $url;
        }
    }

    function blog_option_siteurl($url, $blog_id = false) {
        if ( $this->lang == $this->default_lang ) {
            return $url;
        }
        if ($this->settings['language_negotiation_type'] == 1) {
            $blog_home = ($blog_id) ? $this->blogs[$blog_id]['fullpath'] : $this->siteurl;
            return $blog_home . '/' . $this->lang;
        } else {
            return $url;
        }
    }

    function bp_core_get_root_domain($url) {
        if ( $this->lang == $this->default_lang ) return $url;
        return $this->home_blog . '/'. $this->lang;
    }

    function site_url($url, $path, $orig_scheme) {
        if (preg_match('/(wp-login|wp-admin)/',$path) !== false) {
            return $this->remove_lang($url);
        } else {
            return $url;
        }
    }

    function check_subblog($url) {
        $match = false;
        foreach ($this->blogs_search as $subblog) {
            if ($match) {
                continue;
            }
            if (stripos($url,$subblog) !== false) {
                $match = $subblog;
            }
        }
        return $match;
    }

    function switch_blog() {
        $this->switch_blog = true;
        return; // REVIEW
        if ($new_id == $prev_id) $this->switch_blog = $prev_id;
        else $this->switch_blog = $new_id;
    }

    function switch_blog_back(){
        $this->switch_blog = false;
    }

    function translate($str, $echo = false) {
        if ($echo) {
            _e($str, 'buddypress');
        } else {
            return __($str, 'buddypress');
        }
    }

    function filter_hrefs($string = '') {
            // REVIEW
        /*if (is_object($args)) {
            return $args;
        }*/
        return preg_replace_callback('/href=["\'](.+?)["\']/', array(&$this,'filter_matches'), $string);
    }

    function filter_matches($match = array()) {
        return str_replace($match[1], $this->option_siteurl($match[1]), $match[0]);
    }

    function convert_title_url($args) {
        return $this->check_doubles($args);
    }

    function redirect($args) {
        $this->redirect_lang();
        return ($this->option_siteurl($args));
    }

    function redirect_lang() {
        $al = $this->active_langs;
        foreach($al as $l) {
            $active_languages[] = $l['code'];
        }
        $request = $_SERVER['HTTP_REFERER'];
        $home = $this->siteurl;
        $url_parts = parse_url($home);
        $blog_path = $url_parts['path'] ? $url_parts['path'] : '';
        if ($this->settings['language_negotiation_type'] == 1) {
            $path  = str_replace($home, '', $request);
            $parts = explode('?', $path);
            $path = $parts[0];
            $exp = explode('/', trim($path, '/'));
            
            if (in_array($exp[0], $active_languages)) {
                $this->lang = $exp[0];
            } else {
                $this->lang = $this->default_lang;
            }
        }
    }

    function check_doubles($url){
        if ($this->settings['language_negotiation_type'] == 1 ) {
            $url = str_replace($this->lang.'/' . $this->lang, $this->lang, $url);
            $url = str_replace($this->lang.'//', $this->lang . '/', $url); // Ugly
        }
        return $url;
    }

    function filter_bp_uri($uri) {
        if ($this->settings['language_negotiation_type'] == 1) {
            return str_replace($this->lang . '/', '', $uri);
        } else {
            return $uri;
        }
    }

    function filter_wp_logout_url($url,$redirect) {
        return str_replace(urlencode($redirect), urlencode($this->siteurl_new), $url);
    }

    function filter_search_redirection($args) {
        if ($this->settings['language_negotiation_type'] == 1) {
            return $this->option_siteurl($args);
        } else {
            return $args;
        }
    }

    function remove_lang($link) {
        if ($this->settings['language_negotiation_type'] == 1) {
            $link = str_replace($this->lang.'/','',$link);
        }
        return $link;
    }

    function check_main_to_subblog() {
        if ($this->settings['language_negotiation_type'] != 1 
            && $_SERVER['HTTP_REFERER']
            && strpos($_SERVER['HTTP_REFERER'],$this->home_blog) === false) {
            return;
        }
        $url_lang = $this->get_url_lang();
        $is_lang = $this->is_lang($lang);
        if ($is_lang != false && !array_key_exists($url_lang, $this->active_langs)) {
            $redirect = 'http' . $this->https . '://'.$_SERVER['HTTP_HOST'] . '/' . str_replace('/' . $url_lang, '', ltrim($_SERVER['REQUEST_URI'], '/'));
            header('Location: ' . $redirect . '' );
            exit;
        }
    }

    function get_url_lang() {
        $url_lang = explode($this->siteurl, 'http' . $this->https . '://' . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI']);
        $url_lang = explode('/', $url_lang[1]);
        return $url_lang[1];
    }

    function is_lang($lang) {
        global $wpdb;
        return $wpdb->get_results("SELECT code FROM wp_".BP_ROOT_BLOG."_icl_languages WHERE code = '{$lang}'", ARRAY_A);
    }

    function link_to_page($lang){
        if ($this->default_lang == $lang) {
            return 'http' . $this->https . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            return $this->option_siteurl('http' . $this->https . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $lang);
        }
    }

    function translate_widgets () {
        global $wp_registered_widgets;
        $bp_widgets = array('Recently Active Member Avatars', 'Recent Site Wide Posts', 'Groups', 'Members', 'Site Wide Activity', 'Who\'s Online Avatars');
        foreach ($wp_registered_widgets as $k => $widget) {
            if (in_array($wp_registered_widgets[$k]['name'], $bp_widgets)) {
                $wp_registered_widgets[$k]['name'] = __( $widget['name'],'buddypress' );
            }
        }
    }

    function adminbar_lang_switcher_menu() {
        do_action('switch_blog_back');
        if (defined('WP_ADMIN')) {
            return;
        }
        $settings = $this->settings;
        $active_languages = icl_get_languages('skip_missing=0');
        if (count($active_languages) < 2) {
            return;
        }
        foreach($active_languages as $k => $al) {
            if ($al['active']==1) {
                $main_language = $al;
                unset($active_languages[$k]);
                break;
            }
        }
        echo '
        <li';
        if ($settings['icl_lso_flags']) { echo ' class="flaged"'; }
        echo '>
        <a href="' . $this->siteurl_new . '" class="lang_sel_sel icl-' . $main_language['language_code'] . '">';
            if ($settings['icl_lso_flags']) {
                echo '<img class="iclflag" src="' . $main_language['country_flag_url'] . '" alt="' . $main_language['language_code'] . '" width="18" height="12" />&nbsp;';
            }
        echo icl_disp_language($settings['icl_lso_native_lang'] ? $main_language['native_name'] : null, $settings['icl_lso_display_lang'] ? $main_language['translated_name'] : null);
        echo '</a>';
        echo '<ul>';
        $counter = 0;
        
        foreach ($active_languages as $lang) {
            echo '<li class="icl-' . $lang['language_code']; 
            echo ( 0 == $counter % 2 ) ? ' alt' : '';
            echo '">
            <a href="';
            echo $this->link_to_page($lang['language_code']);
            echo '">';
            if ($settings['icl_lso_flags']) {
                echo '<img class="iclflag" src="' . $lang['country_flag_url'] . '" alt="' . $lang['language_code'] . '" width="18" height="12" />&nbsp;';
            }
            echo icl_disp_language($settings['icl_lso_native_lang'] ? $lang['native_name'] : null, $settings['icl_lso_display_lang'] ? $lang['translated_name'] : null);
            echo '</a>
            </li>';
            $counter++;
        }
        echo '
            </ul>
        </li>
        ';
    }

    function stylesheet() {
        $file = $this->relpath . '/bp-multilingual.css';
        wp_register_style('bpml', $file);
        wp_enqueue_style('bpml');
        // REVIEW
        /*$file = get_template_directory_uri() . '/bp-multilingual.css';
        if (file_exists($file)) {
            wp_register_style('bpml-override', $file);
            wp_enqueue_style('bpml-override');
        }*/
    }

}


    global $sitepress_bp;
    $sitepress_bp = new SitePressBp();