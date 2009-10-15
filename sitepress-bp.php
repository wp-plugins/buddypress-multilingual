<?php
/*
Plugin Name: BuddyPress Multilingual
Plugin URI: http://wpml.org/wordpress-translation/buddypress-multilingual/
Description: BuddyPress Multilingual. <a href="http://wpml.org/wordpress-translation/buddypress-multilingual/">Documentation</a>.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version: 0.1.0
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

	function __construct(){
		if (defined('WP_ADMIN')) return; // Don't want to use plugin in admin area yet.
		add_action ('plugins_loaded', array(&$this,'init'),2); // after WPML, before Bp
		$this->abspath = dirname(__FILE__);
		$this->relpath = WP_CONTENT_URL . '/' . basename(dirname(dirname(__FILE__))) . '/' . basename(dirname(__FILE__));
		$this->https = ($_SERVER['HTTPS'] == 'on') ? 's' : '';
	}

	function init(){
		if (!defined('BP_VERSION')) return;
		
		global $current_blog;
		$this->blog = $current_blog;
		//if($current_blog->blog_id != BP_ROOT_BLOG) return;
		
		$this->siteurl = 'http'.$this->https.'://'.$this->blog->domain.rtrim($this->blog->path,'/');
		
		if (!defined('ICL_SITEPRESS_VERSION')) {
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
		}
		
		$blogs  = get_blog_list( 0, 'all' );
		foreach ($blogs as $k => $v) {
			//if TODO: domains?
			if ($v['blog_id'] == BP_ROOT_BLOG ) $this->home_blog = rtrim('http'.$this->https.'://'.$v['domain'].$v['path'],'/');
			else if ( $this->siteurl.'/' != 'http'.$this->https.'://'.$v['domain'].$v['path'] )
				$this->blogs_search[] = rtrim('http'.$this->https.'://'.$v['domain'].$v['path'],'/');
			$this->blogs[$v['blog_id']]['domain'] = $v['domain'];
			$this->blogs[$v['blog_id']]['path'] = $v['path'];
			$this->blogs[$v['blog_id']]['fullpath'] = rtrim('http'.$this->https.'://'.$v['domain'].$v['path'],'/');
		}
		
		global $sitepress;
  		//remove_action('plugins_loaded', array($sitepress,'init'));
  		//add_action('plugins_loaded', array($sitepress,'init'), 1);
		remove_action('pre_option_home', array($sitepress,'pre_option_home'));
		
		$this->settings = get_option('icl_sitepress_settings');
		$this->active_langs = $sitepress->get_active_languages();
		$this->lang = $sitepress->get_current_language();
		$this->default_lang = $sitepress->get_default_language();
		
		
		add_action('wp_print_styles',  array(&$this,'stylesheet'));
		add_action( 'init', array(&$this,'translate_widgets') );
		add_action( 'wp_footer', array(&$this,'footer') );
		
			// Filter Buddypress Top bar
		//add_action( 'wp', array(&$this,'filter_bp_nav'), 99 );
		//add_action( 'admin_menu', array(&$this,'filter_bp_nav'), 99 );
		
			// Blog URL HOOKS
		add_filter('pre_option_home', array(&$this,'pre_option_home'),11);
		//add_filter('option_home', array(&$this,'option_siteurl'),11);
		add_filter('option_siteurl', array(&$this,'option_siteurl'),11,2);
		add_filter('blog_option_siteurl', array(&$this,'blog_option_siteurl'),11,2);
		add_filter('blog_option_home', array(&$this,'blog_option_siteurl'),11,2);
		add_filter('bp_core_get_root_domain', array(&$this,'bp_core_get_root_domain'),999);
		add_filter('site_url', array(&$this,'site_url'),11,3);
		
			// WP HOOKS
		add_filter( 'the_content', array(&$this,'content') );
		add_filter('wp_redirect', array(&$this,'redirect') ); // messes up subblog login?
		add_filter( 'comment_post_redirect', array(&$this,'redirect'),9999,1);
		add_filter( 'logout_url', array(&$this,'filter_wp_logout_url'),9999,2 );
		//add_action( 'switch_blog', array(&$this,'switch_blog'));
		add_action( 'switch_blog_back', array(&$this,'switch_blog_back'),0,2);
		
		//remove_action('bp_adminbar_menus', 'bp_adminbar_blogs_menu', 6);
		//add_action('bp_adminbar_menus', array(&$this,'adminbar_blogs_menu'), 6);
		add_action( 'bp_adminbar_menus', array(&$this,'switch_blog'),0);
		add_action('bp_adminbar_menus', array(&$this,'adminbar_lang_switcher_menu'), 99);
		
			// This checks if we link from main blog to subblog with unactive lang;
		$this->check_main_to_subblog();
		
		//if (isset($_GET['hooks'])) include WPMLBP_PLUGIN_DIR . '/hooks.class.php';
		include $this->abspath . '/hooks.php';
	}

	function post_init(){
		if (!defined('BP_VERSION') || !defined('ICL_SITEPRESS_VERSION')) return;
		global $bp;
		$bp->siteurl = $this->siteurl;
	}

	function pre_option_home(){
		$dbbt = debug_backtrace();
		if( $dbbt[3]['file'] != @realpath(TEMPLATEPATH . '/header.php') ||  $this->lang == $this->default_lang )
			return false;
		if ( $this->settings['language_negotiation_type'] == 1 ) {
			$this->siteurl_new = $this->check_doubles($this->siteurl . '/'. $this->lang);
			return $this->siteurl_new;
		} else {
			return false;
		}
	}

	function option_siteurl( $url, $code = false ){
		if ( $this->switch_blog ) return $url;
		if ( !$code && $this->lang == $this->default_lang ) return $url;
		$lang = ($code) ? $code : $this->lang;
		if ( $this->settings['language_negotiation_type'] == 1 ) {
			$subblog = $this->check_subblog($url);
			$url_match = ($subblog) ? $subblog : $this->siteurl;
			$url = str_replace ( $url_match, $url_match . '/'. $lang, $url );
			return $this->check_doubles($url);
		} else {
			return $url;
		}
	}

	function blog_option_siteurl($url, $blog_id = false){
		if ( $this->lang == $this->default_lang ) return $url;
		if ( $this->settings['language_negotiation_type'] == 1 ) {
			$blog_home = ($blog_id) ? $this->blogs[$blog_id]['fullpath'] : $this->siteurl;
			return $blog_home . '/'. $this->lang;
		} else {
			return $url;
		}
	}

	function bp_core_get_root_domain($url) {
		if ( $this->lang == $this->default_lang ) return $url;
		return $this->home_blog . '/'. $this->lang;
	}

	function site_url($url, $path, $orig_scheme) {
		if (preg_match('/(wp-login|wp-admin)/',$path) !== false) return $this->remove_lang($url);
		//if (strpos($path,'wp-admin') !== false) return $this->remove_lang($url);
		else return $url;
	}

	function check_subblog($url) {
		$match = false;
		foreach ($this->blogs_search as $subblog) {
			if ($match) continue;
			if ( stripos($url,$subblog) !== false ) { $match = $subblog; }
		}
		return $match;
	}

	function switch_blog(){ //$new_id,$prev_id
		$this->switch_blog = true;
		return;
		if ($new_id == $prev_id) $this->switch_blog = $prev_id;
		else $this->switch_blog = $new_id;
	}

	function switch_blog_back(){
		$this->switch_blog = false;
	}

	function translate ( $str, $echo = false ) {
		if ($echo) _e($str,'buddypress');
		else return __($str,'buddypress');
	}

	function filter_hrefs( $string = '' ){
		if (is_object($args)) return $args;
		return preg_replace_callback('/href=["\'](.+?)["\']/',array(&$this,'filter_matches'),$string);
	}

	function filter_matches( $match = array() ){
		return str_replace($match[1],$this->option_siteurl($match[1]),$match[0]);
	}

	function convert_title_url($args){
		return $this->check_doubles($args);
	}

	function redirect($args){
		$this->redirect_lang();
		return ($this->option_siteurl($args));
	}

	function redirect_lang() {
		$al = $this->active_langs;
		foreach($al as $l){ $active_languages[] = $l['code']; }
		$request = $_SERVER['HTTP_REFERER'];
		$home = $this->siteurl;
		$url_parts = parse_url($home);
		$blog_path = $url_parts['path']?$url_parts['path']:'';
		if ($this->settings['language_negotiation_type'] == 1) {
			$path  = str_replace($home,'',$request);
			$parts = explode('?', $path);
			$path = $parts[0];
			$exp = explode('/',trim($path,'/'));
			if (in_array($exp[0], $active_languages)) $this->lang = $exp[0];
			else $this->lang = $this->default_lang;
		}
	}

	function check_doubles($url){
		if ($this->settings['language_negotiation_type'] == 1 ) {
			$url = str_replace($this->lang.'/'.$this->lang, $this->lang, $url);
			$url = str_replace($this->lang.'//', $this->lang.'/', $url); // Ugly
		}
		return $url;
	}

	function filter_bp_uri($uri) {
		if ($this->settings['language_negotiation_type'] == 1)
			return str_replace($this->lang.'/','',$uri);
		else return $uri;
	}

	function filter_wp_logout_url($url,$redirect) {
		return str_replace(urlencode($redirect),urlencode($this->siteurl_new),$url);
	}

	function filter_search_redirection($args){
		if ($this->settings['language_negotiation_type'] == 1)
			return $this->option_siteurl($args);
		else return $args;
	}

	function remove_lang($args){
		if ( $this->settings['language_negotiation_type'] == 1 ) 
			$link = str_replace($this->lang.'/','',$args);
		return $link;
	}

	function check_main_to_subblog() {
		if ( $this->settings['language_negotiation_type'] != 1 
			&& $_SERVER['HTTP_REFERER']
			&& strpos($_SERVER['HTTP_REFERER'],$this->home_blog) === false ) return;
			
			$url_lang = $this->get_url_lang();
			$is_lang = $this->is_lang($lang);
			if ( $is_lang != false && !array_key_exists($url_lang,$this->active_langs ) ) {
				$redirect = 'http'.$this->https.'://'.$_SERVER['HTTP_HOST'] . '/' . str_replace('/'.$url_lang, '', ltrim($_SERVER['REQUEST_URI'],'/') );
				header('Location: '.$redirect.'' );
				exit;
			}
	}

	function get_url_lang(){
		$url_lang = explode($this->siteurl,'http'.$this->https.'://'.$_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI']);
		$url_lang = explode('/',$url_lang[1]);
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
			return $this->option_siteurl('http'.$this->https.'://'.$_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],$lang);
		}
	}

	function translate_widgets () {
		global $wp_registered_widgets;
		$bp_widgets = array('Recently Active Member Avatars', 'Recent Site Wide Posts', 'Groups', 'Members', 'Site Wide Activity', 'Who\'s Online Avatars');
		foreach ( $wp_registered_widgets as $k => $widget ) {
			if (in_array($wp_registered_widgets[$k]['name'], $bp_widgets))
			$wp_registered_widgets[$k]['name'] = __( $widget['name'],'buddypress' );
		}
	}

	function content($str){
    	return '<div class="wpml-post-aviability">'.$this->this_post_is_available().'</div>'.$str;
	}

	function this_post_is_available($args='before=1'){
		if ( count($this->active_langs) < 2 ) return;
		global $wpdb,$post,$sitepress;
		$trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$post->ID}' AND element_type='post'");
		$translations = $sitepress->get_element_translations($trid,'post');
		parse_str($args);
		$echo = '';
		if ($before) $echo .= __('This post is also available in: ','sitepress');
		foreach ($translations as $t) {
			if ( $this->lang == $t->language_code || !array_key_exists( $t->language_code, $this->active_langs ) ) continue;
			$echo .= '<a href="'.get_permalink($translations[$t->language_code]->element_id) .'">' .  $sitepress->get_display_language_name($t->language_code, $this->lang) . '</a>';
		}
		if ($after) $echo .= $after;
		return $echo;
	}


	function adminbar_lang_switcher_menu() {
		do_action('switch_blog_back');
			// ie ver ???
		if (defined('WP_ADMIN')) return;
		$settings = $this->settings;
		 $active_languages = icl_get_languages('skip_missing=0');
		 if (count($active_languages) < 2) return;
		 foreach($active_languages as $k=>$al){
            if($al['active']==1){
                $main_language = $al;
                unset($active_languages[$k]);
                break;
            }
        }
		echo '
		<style>#wp-admin-bar .iclflag { position: relative; left: -1px; top: 1px; }</style>
        <li><a href="'.$this->siteurl_new.'" class="lang_sel_sel icl-'.$w_this_lang['code'].'">';
            if($settings['icl_lso_flags']):
            	echo '<img class="iclflag" src="'.$main_language['country_flag_url'].'" alt="'.$main_language['language_code'].'" width="18" height="12" />&nbsp;'; endif; 
            echo icl_disp_language($settings['icl_lso_native_lang']?$main_language['native_name']:null, $settings['icl_lso_display_lang']?$main_language['translated_name']:null);
            if(!isset($ie_ver) || $ie_ver > 6): print '</a>'; endif;
            if(!empty($active_languages)):
            	if(isset($ie_ver) && $ie_ver <= 6): print '<table><tr><td>'; endif;
            echo '<ul>';
				$counter = 0;
				
                foreach($active_languages as $lang):
					
                	echo '<li class="icl-'.$lang['language_code']; 
				 	echo ( 0 == $counter % 2 ) ? ' alt' : '';
					echo '">
                    <a href="';
					echo $this->link_to_page($lang['language_code']);
					echo '">';
                    if($settings['icl_lso_flags']):
                    	echo '<img class="iclflag" src="'.$lang['country_flag_url'].'" alt="'.$lang['language_code'].'" width="18" height="12" />&nbsp;';
                    endif;
                    echo icl_disp_language($settings['icl_lso_native_lang']?$lang['native_name']:null, $settings['icl_lso_display_lang']?$lang['translated_name']:null);
                    echo '</a>
                </li>';
					$counter++;
                endforeach;
            echo '</ul>';
            if(isset($ie_ver) && $ie_ver <= 6): echo '</td></tr></table></a>'; endif; 
            endif;
        echo '</li>';
    }

	function footer_switcher( $skip_missing=0, $div_id = "footer_language_list" ) {
		if(function_exists('icl_get_languages')){
			$languages = icl_get_languages('skip_missing='.intval($skip_missing));
			if(!empty($languages)){
				echo '<div id="'.$div_id.'"><ul>';
				foreach($languages as $l){
					echo '<li>';
					if(!$l['active']) echo '<a href="'.$this->link_to_page($l['language_code']).'">';
					echo '<img src="'.$l['country_flag_url'].'" alt="'.$l['language_code'].'" width="18" height="12" />';
					if(!$l['active']) echo '</a>';
					if(!$l['active']) echo '<a href="'.$this->link_to_page($l['language_code']).'">';
					echo $l['native_name'];
					//if(!$l['active']) echo ' ('.$l['translated_name'].')';
					if(!$l['active']) echo '</a>';
					echo '</li>';
				}
			echo '</ul></div>';
			}
		}
	}

	function stylesheet() {
		$file = get_template_directory_uri() . '/bp-multilingual.css';
		wp_register_style('bpml', $file);
		wp_enqueue_style('bpml');
		if (!file_exists($file)) {
			$file = $this->relpath . '/bp-multilingual.css';
			wp_register_style('bpml-override', $file);
			wp_enqueue_style('bpml-override');
		}
	}

	function footer() {
		//global $wp_query;
		//print_r($wp_query);
		//global $bp;
		//echo '<pre>';print_r($bp);echo'</pre>';
		//echo '<a href="http://wpmu.localhost/wpmu/test/pt-br">test</a>';
		$this->footer_switcher();
	}

	/*function adminbar_blogs_menu() {
		if ( is_user_logged_in() ) {
			global $bp; 
	
		if ( function_exists('bp_blogs_install') ) {
			
			if ( !$blogs = wp_cache_get( 'bp_blogs_of_user_' . $bp->loggedin_user->id, 'bp' ) ) {
				$blogs = get_blogs_of_user( $bp->loggedin_user->id );
				wp_cache_set( 'bp_blogs_of_user_' . $bp->loggedin_user->id, $blogs, 'bp' );
			}

			echo '<li id="bp-adminbar-blogs-menu"><a href="' . $bp->loggedin_user->domain . $bp->blogs->slug . '/my-blogs'.'">';
			
			_e( 'My Blogs', 'buddypress' );
			
			echo '</a>';
	
			echo '<ul>';			
			if ( is_array( $blogs )) {
		
				$counter = 0;
				foreach( $blogs as $blog ) {
					$role = get_blog_role_for_user( $bp->loggedin_user->id, $blog->userblog_id );

					$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';
					echo '<li' . $alt . '>';
					echo '<a href="' . $this->filter_blog_url($blog->siteurl) . '">' . $blog->blogname . ' (' . $role . ')</a>';
					if ( !( 'Subscriber' == $role ) ) { // then they have something to display on the flyout menu
						echo '<ul>';
						echo '<li class="alt"><a href="' . $blog->siteurl  . '/wp-admin/">' . __('Dashboard', 'buddypress') . '</a></li>';
						echo '<li><a href="' . $blog->siteurl  . '/wp-admin/post-new.php">' . __('New Post', 'buddypress') . '</a></li>';
						echo '<li class="alt"><a href="' . $blog->siteurl  . '/wp-admin/edit.php">' . __('Manage Posts', 'buddypress') . '</a></li>';
						echo '<li><a href="' . $blog->siteurl  . '/wp-admin/edit-comments.php">' . __('Manage Comments', 'buddypress') . '</a></li>';					
						if ( 'Admin' == $role ) {	
							echo '<li class="alt"><a href="' . $blog->siteurl  . '/wp-admin/themes.php">' . __('Switch Theme', 'buddypress') . '</a></li>'; 
						}					
						echo '</ul>';					
					}
					echo '</li>';
					$counter++;
				}
			}
	
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

			echo '<li' . $alt . '>';
			echo '<a href="' . $bp->loggedin_user->domain . $bp->blogs->slug . '/create-a-blog'.'">' . __('Create a Blog!', 'buddypress') . '</a>';
			echo '</li>';
	
			echo '</ul>';
			echo '</li>';
			}
		}
	}*/

	/*	function filter_bp_nav() {
			// This function will probably filter all added custom navs
		global $bp;
		$this->bp = $bp;
		if (!is_array($this->bp->bp_nav)) return;
		foreach($this->bp->bp_nav as $k => $nav_item) {
			$this->bp->bp_nav[$k]['link'] = $this->convert_subblog_url($nav_item['link']);
			if ( is_array( $this->bp->bp_options_nav[$nav_item['css_id']] ) ) {
				
				foreach( $this->bp->bp_options_nav[$nav_item['css_id']] as $k => $subnav_item ) {
					$this->bp->bp_options_nav[$nav_item['css_id']][$k]['link'] = $this->convert_subblog_url($subnav_item['link']);
				}
			
			}
		}
	}*/
}


	global $sitepress_bp;
	$sitepress_bp = new SitePressBp();