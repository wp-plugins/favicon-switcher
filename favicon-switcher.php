<?php
/**
Plugin Name: FavIcon Switcher
Plugin Tag: favicon, icon, favorite
Description: <p>This plugin enables multiple favicon based on URL match rules. </p><p>For instance, you may configure that all the page with the word "<code>receipices</code>" or "<code>important</code>" have a specific favicon.</p><p>You may configure as much favicons you want without restriction.</p><p>This plugin is under GPL licence.</p>
Version: 1.2.10
Framework: SL_Framework
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/plugins/favicon-switcher/
License: GPL3
*/

//Including the framework in order to make the plugin work

require_once('core.php') ; 

require_once('include/ico.class.php') ; 

/** ====================================================================================================================================================
* This class has to be extended from the pluginSedLex class which is defined in the framework
*/
class favicon_switcher extends pluginSedLex {

	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;

	protected function _init() {
		global $wpdb ; 
		
		// Name of the plugin (Please modify)
		$this->pluginName = 'FavIcon Switcher' ; 
		
		// The structure of the SQL table if needed (for instance, 'id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)') 
		$this->tableSQL = "" ; 
		// The name of the SQL table (Do no modify except if you know what you do)
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 

		//Configuration of callbacks, shortcode, ... (Please modify)
		// For instance, see 
		//	- add_shortcode (http://codex.wordpress.org/Function_Reference/add_shortcode)
		//	- add_action 
		//		- http://codex.wordpress.org/Function_Reference/add_action
		//		- http://codex.wordpress.org/Plugin_API/Action_Reference
		//	- add_filter 
		//		- http://codex.wordpress.org/Function_Reference/add_filter
		//		- http://codex.wordpress.org/Plugin_API/Filter_Reference
		// Be aware that the second argument should be of the form of array($this,"the_function")
		// For instance add_action( "the_content",  array($this,"modify_content")) : this function will call the function 'modify_content' when the content of a post is displayed
		
		// Important variables initialisation (Do not modify)
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		// activation and deactivation functions (Do not modify)
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('favicon_switcher','uninstall_removedata'));
	}
	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('favicon_switcher'.'_options') ;
		if (is_multisite()) {
			delete_site_option('favicon_switcher'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'favicon_switcher')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'favicon_switcher' ) ; 
		}
		
		// DELETE FILES if needed
		SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/favicon/"); 
		SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/uploads/favicon/"); 
		$plugins_all = 	get_plugins() ; 
		$nb_SL = 0 ; 	
		foreach($plugins_all as $url => $pa) {
			$info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR."/".$url);
			if ($info['Framework_Email']=="sedlex@sedlex.fr"){
				$nb_SL++ ; 
			}
		}
		if ($nb_SL==1) {
			SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/"); 
		}
	}

	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		if (count($this->get_param('list_of_favicon'))==0) {
			$result = array() ; 
			if ($this->get_param('custom1_rule')!="") {
				$result[] = 1 ; 
			}
			if ($this->get_param('custom2_rule')!="") {
				$result[] = 2 ; 
			}
			$this->set_param('list_of_favicon', $result) ; 
		}
	}
	
	/**====================================================================================================================================================
	* Function called to return a number of notification of this plugin
	* This number will be displayed in the admin menu
	*
	* @return int the number of notifications available
	*/
	 
	public function _notify() {
		return 0 ; 
	}
		
	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
		$upload_dir = wp_upload_dir();
		$url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		
		// Search for the right icon
		
		foreach ($this->get_param('list_of_favicon') as $id) {
			if (($this->get_param('custom'.$id.'_rule') != "") && (preg_match("/".$this->get_param('custom'.$id.'_rule')."/i", $url)) && ($this->get_param('custom'.$id.'_favicon') != $this->get_default_option('custom'.$id.'_favicon')) ) {
				$path = $upload_dir["baseurl"].$this->get_param('custom'.$id.'_favicon')  ; 
				echo '<link rel="icon" href="'.$path.'" type="image/x-icon">' ; 
				echo '<link rel="shortcut icon" href="'.$path.'" type="image/x-icon">' ; 	
				return ; 	
			}			
		}
		
		// Default icon
		
		if ($this->get_param('default_favicon') != $this->get_default_option('default_favicon')) {
			$path = $upload_dir["baseurl"].$this->get_param('default_favicon')  ; 
			echo '<link rel="icon" href="'.$path.'" type="image/x-icon">' ; 
			echo '<link rel="shortcut icon" href="'.$path.'" type="image/x-icon">' ; 
		}
	}
	
	/** ====================================================================================================================================================
	* Init CSS for the admin side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('my_plugin_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_css_load() {	
		$this->_public_css_load() ; 
	}
	
	/**====================================================================================================================================================
	* Function to instantiate the class and make it a singleton
	* This function is not supposed to be modified or called (the only call is declared at the end of this file)
	*
	* @return void
	*/
	
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* Define the default option values of the plugin
	* This function is called when the $this->get_param function do not find any value fo the given option
	* Please note that the default return value will define the type of input form: if the default return value is a: 
	* 	- string, the input form will be an input text
	*	- integer, the input form will be an input text accepting only integer
	*	- string beggining with a '*', the input form will be a textarea
	* 	- boolean, the input form will be a checkbox 
	* 
	* @param string $option the name of the option
	* @return variant of the option
	*/
	public function get_default_option($option) {
		switch ($option) {
			// Alternative default return values (Please modify)
			case 'default_favicon' 		: return "[file]/favicon/" 		; break ; 
			case 'list_of_favicon' 		: return array() 		; break ; 
		}
		
		// Use a trick to match any number
		if (preg_match("/custom[0-9]*_favicon/", $option)) {
			return "[file]/favicon/" ; 
		}
		if (preg_match("/custom[0-9]*_rule/", $option)) {
			return "" ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function configuration_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->pluginID;
	
		?>
		<div class="plugin-titleSL">
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		
		<div class="plugin-contentSL">		
				
			<?php echo $this->signature ; ?>

		<?php
		
			// On verifie que les droits sont corrects
			$this->check_folder_rights( array(array(WP_CONTENT_DIR."/sedlex/favicon/", "rwx")) ) ; 
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
			$tabs = new SLFramework_Tabs() ; 
			
			ob_start() ; 
				$params = new SLFramework_Parameters($this, 'tab-parameters') ; 
				
				$params->add_title(__('Default Favicon',$this->pluginID)) ; 
				$old_favicon = $this->get_param('default_favicon') ; 
				$new_favicon = $params->get_new_value('default_favicon') ; 
				
				$params->add_param('default_favicon', __('The default favicon image:',$this->pluginID)) ; 
				
				// There is an error
				if (is_array($new_favicon) && ($new_favicon[0]=='error')) {
					$params->add_comment(__('An error occurred!',$this->pluginID)) ; 
				
				// There is no favicon at all
				} else if ($new_favicon == $this->get_default_option('default_favicon')) {
					$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
				
				// There is no update of the favicon
				} else if (($new_favicon == $old_favicon)||($new_favicon == null)) {
					$upload_dir = wp_upload_dir();
					$path = $upload_dir["basedir"].$old_favicon  ; 
					if (file_exists($path.".ico")) {
						$params->add_comment(sprintf(__('The multiresolution ICO is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$old_favicon.".ico'>", "</a>")) ; 
					} else {
						$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
					}
				
				// There is an update
				} else {				
					$upload_dir = wp_upload_dir();
					$path = $upload_dir["basedir"].$new_favicon  ; 
					$ico = new icoTransform() ; 
					$ret = $ico->loadImage($path) ; 
					if ($ret) {
						$ret = $ico->transformToICO($path.".ico") ; 
					}
					if ($ret) {
						$params->add_comment(sprintf(__('The multiresolution ICO has been generated and is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$new_favicon.".ico'>", "</a>")) ; 
					} else {
						$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
					}
				} 
				
				// We check whether a new entry is deleted
				$array = $this->get_param('list_of_favicon') ; 
				foreach ($array as $j=>$id) {
					$new_rule = $params->get_new_value('custom'.$id.'_rule') ; 
					if (($new_rule!==null)&&($new_rule=="")) {
						unset($array[$j]) ; 
						$_POST["delete_".'custom'.$id.'_favicon'] = 1 ; 
					}
				}
				$this->set_param('list_of_favicon', array_values($array)) ; 
				
				// We check whether a new entry is set
				$array = $this->get_param('list_of_favicon') ; 
				if (isset($array[count($array)-1])) {
					$id = $array[count($array)-1]+1 ; 
					$new_rule = $params->get_new_value('custom'.$id.'_rule') ; 
					if (($new_rule!==null)&&($new_rule!="")) {
						$array[] = $id ; 
						$this->set_param('list_of_favicon', $array) ; 
					}
				}
				
				
				// On print all the stored icons
				
				$i = 0 ; 
				foreach ($this->get_param('list_of_favicon') as $id) {
					$i ++ ; 
					$params->add_title(sprintf(__('Custom Favicon n°%s',$this->pluginID), $i)) ; 
					$params->add_param('custom'.$id.'_rule', sprintf(__('The regexp rule for the image n°%s:',$this->pluginID), $i)) ; 
					$params->add_comment(sprintf(__('For instance, %s to have a specific icon for admin page or %s for posts having the word important in their titles',$this->pluginID), "<code>.*\/wp-admin\/.*</code>", "<code>.*important.*</code>")) ; 
					$params->add_comment(__('Please note that if you delete the regexp, these entry will be deleted',$this->pluginID)) ; 

					$old_favicon = $this->get_param('custom'.$id.'_favicon') ; 
					$new_favicon = $params->get_new_value('custom'.$id.'_favicon') ; 
					
					$params->add_param('custom'.$id.'_favicon', sprintf(__('Custom favicon image n°%s:',$this->pluginID), $i)) ; 
					
					// There is an error
					if (is_array($new_favicon) && ($new_favicon[0]=='error')) {
						$params->add_comment(__('An error occurred!',$this->pluginID)) ; 
					
					// There is no favicon at all
					} else if ($new_favicon == $this->get_default_option('custom'.$id.'_favicon')) {
						$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
					
					// There is no update of the favicon
					} else if (($new_favicon == $old_favicon)||($new_favicon == null)) {
						$upload_dir = wp_upload_dir();
						$path = $upload_dir["basedir"].$old_favicon  ; 
						if (file_exists($path.".ico")) {
							$params->add_comment(sprintf(__('The multiresolution ICO is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$old_favicon.".ico'>", "</a>")) ; 
						} else {
							$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
						}
					
					// There is an update
					} else {				
						$upload_dir = wp_upload_dir();
						$path = $upload_dir["basedir"].$new_favicon  ; 
						$ico = new icoTransform() ; 
						$ret = $ico->loadImage($path) ; 
						if ($ret) {
							$ret = $ico->transformToICO($path.".ico") ; 
						}
						if ($ret) {
							$params->add_comment(sprintf(__('The multiresolution ICO has been generated and is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$new_favicon.".ico'>", "</a>")) ; 
						} else {
							$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
						}
					} 
					
				}
				
				
				// Propose to add a new image icon
				$array = $this->get_param('list_of_favicon') ; 
				$id = 1 ; 
				if (isset($array[count($array)-1]))
					$id = $array[count($array)-1]+1 ; 
				$params->add_title(__('Add a new customized Favicon',$this->pluginID)) ; 
				$params->add_param('custom'.$id.'_rule', __('The new regexp rule:',$this->pluginID)) ; 
				$params->add_comment(sprintf(__('For instance, %s to have a specific icon for admin page',$this->pluginID), "<code>.*\/wp-admin\/.*</code>")) ; 
				$params->add_param('custom'.$id.'_favicon', __('A new image icon:',$this->pluginID)) ; 
				$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
				
				$params->flush() ; 
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			// HOW To
			ob_start() ;
				echo "<p>".__("This plugin is designed to change dynamically the favicon of your website.", $this->pluginID)."</p>" ; 
			$howto1 = new SLFramework_Box (__("Purpose of that plugin", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				echo "<p>".__("You just have to upload a first image (preferably a squared image) that will be used for default favicon: the favicon will be automatically created in the right format.", $this->pluginID)."</p>" ; 
				echo "<p>".sprintf(__("If you want to add a specific favicon for specific pages, you will have to upload a new image and to associate it with a regular expression (e.g. %s) which match the wanted URL (e.g. %s).", $this->pluginID),"<code>.*\/images\/.*</code>","<code>http://domain.tld/images/</code>")."</p>" ; 
			$howto2 = new SLFramework_Box (__("How to have different favicons?", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				echo "<p>".__("This plugin works on multisite instalations.", $this->pluginID)."</p>" ; 
				echo "<p>".__("Each blog work independently: they all can have their own favicon system.", $this->pluginID)."</p>" ; 
				echo "<p>".sprintf(__("If you want to add a specific favicon for specific pages, you will have to upload a new image and to associate it with a regular expression (e.g. %s) which match the wanted URL (e.g. %s).", $this->pluginID),"<code>.*\/images\/.*</code>","<code>http://domain.tld/images/</code>")."</p>" ; 
			$howto3 = new SLFramework_Box (__("How this plugin works of MU websites?", $this->pluginID), ob_get_clean()) ; 
			ob_start() ; 
				 echo $howto1->flush() ; 
				 echo $howto2->flush() ; 
				 echo $howto3->flush() ; 
			$tabs->add_tab(__('How To',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_how.png") ; 	


			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new SLFramework_Translation($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new SLFramework_Feedback($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				$trans = new SLFramework_OtherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
	
			echo $tabs->flush() ; 					
			
			echo $this->signature ; ?>
		</div>
		<?php
	}
}

$favicon_switcher = favicon_switcher::getInstance();

?>