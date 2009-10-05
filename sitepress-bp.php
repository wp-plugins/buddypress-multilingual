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

define('WPMLBP_PLUGIN_URL', WP_CONTENT_URL . '/' . basename(dirname(dirname(__FILE__))) . '/' . basename(dirname(__FILE__)) );
define('WPMLBP_PLUGIN_DIR', dirname(__FILE__) ); //WP_CONTENT_DIR

class SitePressBp {

	private $settings;
    private $lang;
	private $this_lang;
	
	// Globals
	private $sitepress;
	public $bp;
	public $blog;

	function __construct(){
			// Don't want to use plugin in admin area yet.
		if (is_admin()) return;
			// Before core class.
		add_action ('plugins_loaded', array(&$this,'pre_init'),0);
			// Wait for core class to load // but before Bp
		add_action ('plugins_loaded', array(&$this,'init'),2);
		add_filter ('bp_core_get_root_domain', array(&$this,'blog_option_siteurl_trim'));
			// After Bp globals.
		add_action ('plugins_loaded', array(&$this,'post_init'),9999);
	}

	function pre_init(){}

	function init(){
		if (!defined('BP_VERSION') || !defined('ICL_SITEPRESS_VERSION')) return;
		
		global $current_blog;
		$this->blog = $current_blog;
		
		global $sitepress;
  		/*remove_action('plugins_loaded', array($sitepress,'init'));
  		add_action('plugins_loaded', array($sitepress,'init'), 1);*/
		
		
		$this->sitepress = $sitepress;
		$this->settings = get_option('icl_sitepress_settings');
		
		$this->lang = $this->sitepress->get_current_language();
		$this->this_lang = $this->lang;
		
			// TODO: if not main blog don't add this hooks
		//if ($this->blog->blog_id != BP_ROOT_BLOG) return;
		
		add_action( 'init', array(&$this,'translate_widgets') );
		add_action( 'wp_footer', array(&$this,'footer') );
		
			// Filter Buddypress Top bar
		//add_action( 'wp', array(&$this,'filter_bp_nav'), 99 );
		//add_action( 'admin_menu', array(&$this,'filter_bp_nav'), 99 );
		
			// WP HOOKS
		add_filter( 'the_content', array(&$this,'content') );
		//add_filter('wp_redirect', array(&$this,'redirect') );
		add_filter( 'comment_post_redirect', array(&$this,'comment_post_redirect'),9999,1);
		add_filter( 'logout_url', array(&$this,'filter_wp_logout_url'),9999,2 );
		
			// Converting WP urls
		//add_filter('option_siteurl', array(&$this,'filter_subblog_url'),0,1); // used by bp->root_domain
		//add_filter('option_home', array(&$this,'option_home'),0);
		
			// Converting WPMU blog urls
		add_filter('blog_option_siteurl', array(&$this,'blog_option_siteurl_notrim'),0,1);
		add_filter('site_url', array(&$this,'site_url'),0,1);
		//add_filter('blog_option_home', array(&$this,'blog_option_siteurl_notrim'),0,1); // 1x used

		//if (isset($_GET['hooks'])) include WPMLBP_PLUGIN_DIR . '/hooks.class.php';
		include WPMLBP_PLUGIN_DIR . '/hooks.php';
	}

	function post_init() {
	
		//global $sitepress;
  		//remove_action('plugins_loaded', array($sitepress,'init'));
  		//add_action('plugins_loaded', array($sitepress,'init'), 1);
		// This should be discussed
		//remove_action('pre_option_home', array($sitepress,'pre_option_home'));
		
		if (!defined('BP_VERSION') || !defined('ICL_SITEPRESS_VERSION')) return;
		
		add_action( 'bp_adminbar_menus', array(&$this,'adminbar_lang_switcher_menu'), 99);
		remove_action('bp_adminbar_menus', 'bp_adminbar_blogs_menu', 6);
		add_action('bp_adminbar_menus', array(&$this,'adminbar_blogs_menu'), 6);
		
			// TODO: if not main blog don't add this hooks
		//if ($this->blog->blog_id != BP_ROOT_BLOG) return;
		
		//global $bp; //echo '<pre>'; print_r($bp); echo '</pre>';
		add_filter('post_link',array(&$this,'convert_title_url'));
	}

	function filter_link ($url) { // Used to test switch
		return $this->sitepress->convert_url($url);
	}

	function translate ( $str, $echo = false ) {
		if ($echo) _e($str,'buddypress');
		else return __($str,'buddypress');
	}

	function test_hook ($p1=false,$p2=false,$p3=false) {
		echo '<font color="#FF0000"><strong>THIS IS NOT FILTERED</strong></font><br>';
		if ($p1) print_r($p1); if ($p2) print_r($p2); if ($p3) print_r($p3); 
		echo '<br><br>';
		return $p1;
	}

	function blog_option_siteurl_trim($url){
			// Called by $bp->root_domain filter on construct.
		if (!defined('BP_VERSION') || !defined('ICL_SITEPRESS_VERSION')) return;
		return $this->filter_blog_url($url,true);
	}

	function blog_option_siteurl_notrim($url){
		return $this->filter_blog_url($url);
	}

	function site_url($url){
		if ( strpos($url,'/'.BP_REGISTER_SLUG) !== false ) 
			return $this->sitepress->convert_url($url);
		else return $url;
	}

	function filter_url( $url, $blog_id = false, $code = null ){
		if(is_null($code)){ $code = $this->this_lang; }
		if($code && $code != $this->sitepress->get_default_language()){
			if (!$blog_id) $blog_id = $this->blog->blog_id;
			//$abshome = preg_replace('@\?lang=' . $code . '@i','',get_option('home'));
			else $abshome = preg_replace('@\?lang=' . $code . '@i','',get_blog_option($blog_id,'siteurl'));
			switch($this->settings['language_negotiation_type']){
				case '1':
					if($abshome==$url) $url .= '/';
					$url = str_replace($abshome, $abshome . '/' . $code, $url);
					break;
				case '2':
					$url = str_replace($abshome, $this->settings['language_domains'][$code], $url);
					break;
				case '3':
				default:
					if ( false === strpos($url,'?') ) $url_glue = '?';
					else $url_glue = '&';
					$url .= $url_glue . 'lang=' . $code;
			}
		}
		return $this->check_doubles($url);
	}

	function filter_blog_url( $url, $trim = false, $code = null ) {
		if(is_null($code)){ $code = $this->this_lang; }
		if($code && $code == $this->sitepress->get_default_language()) {
			if ($trim) return $this->check_doubles(rtrim($url,'/'));
			else return $this->check_doubles($url);
		}
		switch($this->settings['language_negotiation_type']){
			case 1:
				$url = rtrim($url,'/').'/'.$this->lang.'/';
				break;
			case 2:
				// not enabled
				break;
			case 3:
			default:
			$url = $this->sitepress->convert_url($url);
		}
		if ($trim) return rtrim($this->check_doubles($url),'/');
		else return $this->check_doubles($url);
	}


	function filter_hrefs( $string = '' ){
		if (is_object($args)) return $args;
		return preg_replace_callback('/href=["\'](.+?)["\']/',array(&$this,'filter_matches'),$string);
	}

	function filter_matches( $match = array() ){
		return str_replace($match[1],$this->filter_url($match[1]),$match[0]);
	}

	function convert_title_url($args){
		return $this->check_doubles($args);
	}

	function comment_post_redirect($args){
		$this->redirect_lang();
		return ($this->sitepress->convert_url($args,$this->this_lang));
	}

	function redirect_lang() {
		$al = $this->sitepress->get_active_languages();
		foreach($al as $l){ $active_languages[] = $l['code']; }
		$request = $_SERVER['HTTP_REFERER'];
		$home = get_blog_option($this->blog->blog_id,'siteurl');
		$url_parts = parse_url($home);
		$blog_path = $url_parts['path']?$url_parts['path']:'';
		if ($this->settings['language_negotiation_type'] == 1) {
			$path  = str_replace($home,'',$request);
			$parts = explode('?', $path);
			$path = $parts[0];
			$exp = explode('/',trim($path,'/'));
			if (in_array($exp[0], $active_languages)) $this->this_lang = $exp[0];
			else $this->this_lang = $this->get_default_language();
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

	function filter_catbase($args){}

	function filter_wp_logout_url($url,$redirect) {
		return str_replace(urlencode($redirect),urlencode(get_blog_option($this->blog->blog_id,'siteurl')),$url);
	}

	function filter_search_redirection($args){
		if ($this->settings['language_negotiation_type'] == 1)
			return rtrim($this->sitepress->convert_url($args),'/'.$this->lang.'/');
		else return $args;
	}

	function remove_lang($args){
		switch($this->settings['language_negotiation_type']){
                    case 1:
						$link = str_replace($this->lang.'/','',$args);
                        break;
                    case 2:
						// not enabled
                        break;
                    case 3:
                    default:
		}
		return $link;
	}

	function translate_widgets () {
		global $wp_registered_widgets;
		$bp_widgets = array('Recently Active Member Avatars', 'Recent Site Wide Posts', 'Groups', 'Members', 'Site Wide Activity', 'Who\'s Online Avatars');
		foreach ( $wp_registered_widgets as $k => $widget ) {
			if (in_array($wp_registered_widgets[$k]['name'], $bp_widgets))
			$wp_registered_widgets[$k]['name'] = __( $widget['name'],'buddypress' );
		}
	}

	function this_post_is_available($args=''){
    	parse_str($args);
		$echo = '';
    	if(function_exists('icl_get_languages')){
        	$languages = icl_get_languages('skip_missing=1');
        	if(1 < count($languages)){
            	$echo .= isset($before) ? $before : __('This post is also available in: ','sitepress');
            	foreach($languages as $l){
                	if(!$l['active']) $langs[] = '<a href="'.$l['url'].'">'.$l['translated_name'].'</a>';
            	}
            	$echo .= join(', ', $langs);
            	$echo .= isset($after) ? $after : '';
        	}
    	}
		return $echo;
	}


	function adminbar_lang_switcher_menu() {
			// ie ver ???
		if (is_admin()) return;
		global $sitepress;
		$settings = get_option('icl_sitepress_settings');
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
        <li><a href="#" class="lang_sel_sel icl-'.$w_this_lang['code'].'">';
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
                    <a href="'.$lang['url'].'">';
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

	function adminbar_blogs_menu() {
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
	}

	function footer_switcher($skip_missing=0, $div_id = "footer_language_list"){
		if(function_exists('icl_get_languages')){
			$languages = icl_get_languages('skip_missing='.intval($skip_missing));
			if(!empty($languages)){
				echo '<div id="'.$div_id.'"><ul>';
				foreach($languages as $l){
					echo '<li>';
					if(!$l['active']) echo '<a href="'.$l['url'].'">';
					echo '<img src="'.$l['country_flag_url'].'" alt="'.$l['language_code'].'" width="18" height="12" />';
					if(!$l['active']) echo '</a>';
					if(!$l['active']) echo '<a href="'.$l['url'].'">';
					echo $l['native_name'];
					//if(!$l['active']) echo ' ('.$l['translated_name'].')';
					if(!$l['active']) echo '</a>';
					echo '</li>';
				}
			echo '</ul></div>';
			}
		}
	}

	function footer() {
		//$this->footer_switcher();
		//echo '<div id="footer_language_list">' . wpml_languages_list() . '</div>';
	}

	function content($str){
    	return '<div class="wpml-post-aviability">'.$this->this_post_is_available().'</div>'.$str;
	}

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

	/*	function filter_logout_link ( $string ) {
		return preg_replace_callback('/redirect_to=(.+?)(&)/',array(&$this,'filter_logout'),$string);
	}

	function filter_logout( $match = array() ){
		return str_replace($match[1],$this->filter_blog_url($match[1]),$match[0]);
	}*/

}


	global $sitepress_bp;
	$sitepress_bp = new SitePressBp();



		// Template main navigation wrapper
	function wpmlbp_get_bp_url($link) {
		global $current_blog;
		return get_blog_option($current_blog->blog_id,'siteurl').ltrim($link,'/');
	}
