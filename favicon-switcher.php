<?php
/**
Plugin Name: FavIcon Switcher
Description: <p>This plugin enables multiple favicon based on URL match rules. </p><p>This plugin is under GPL licence. </p>
Version: 1.0.1
Framework: SL_Framework
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/extend/plugins/favicon-switcher/
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
		$this->tableSQL = '' ; 
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
		
		add_action('wp_print_scripts', array( $this, 'add_favicon'));

		
		// Important variables initialisation (Do not modify)
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		// activation and deactivation functions (Do not modify)
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'uninstall'));
	}

	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		
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
		
	/**====================================================================================================================================================
	* Function to add the favicon
	*
	* @return void
	*/
	 
	public function add_favicon() {
		$upload_dir = wp_upload_dir();
		$url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		
		if (($this->get_param('custom1_rule') != "") && (preg_match("/".$this->get_param('custom1_rule')."/i", $url)) && ($this->get_param('custom1_favicon') != $this->get_default_option('custom1_favicon')) ) {
			$path = $upload_dir["baseurl"].$this->get_param('custom1_favicon')  ; 
			echo '<link rel="icon" href="'.$path.'" type="image/x-icon">' ; 
			echo '<link rel="shortcut icon" href="'.$path.'" type="image/x-icon">' ; 		
		} else if (($this->get_param('custom2_rule')!="") && (preg_match("/".$this->get_param('custom2_rule')."/i", $url)) && ($this->get_param('custom2_favicon') != $this->get_default_option('custom2_favicon'))) {
		
		} else {
			if ($this->get_param('default_favicon') != $this->get_default_option('default_favicon')) {
				$path = $upload_dir["baseurl"].$this->get_param('default_favicon')  ; 
				echo '<link rel="icon" href="'.$path.'" type="image/x-icon">' ; 
				echo '<link rel="shortcut icon" href="'.$path.'" type="image/x-icon">' ; 
			}
		}
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
			case 'custom1_favicon' 		: return "[file]/favicon/" 		; break ; 
			case 'custom1_rule' 		: return "" 		; break ; 
			case 'custom2_favicon' 		: return "[file]/favicon/" 		; break ; 
			case 'custom2_rule' 		: return "" 		; break ; 
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
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div style="padding:20px;">
			<?php echo $this->signature ; ?>
			<p><?php echo __('This plugin enables multiple favicon for your website', $this->pluginID) ; ?></p>
		<?php
		
			// On verifie que les droits sont corrects
			$this->check_folder_rights( array(WP_CONTENT_DIR."/sedlex/favicon/") ) ; 
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
			$tabs = new adminTabs() ; 
			
			ob_start() ; 
				$params = new parametersSedLex($this, 'tab-parameters') ; 
				
				$params->add_title(sprintf(__('Default Favicon',$this->pluginID), $title)) ; 
				$old_default_favicon = $this->get_param('default_favicon') ; 
				$params->add_param('default_favicon', __('The default favicon image:',$this->pluginID)) ; 
				$new_default_favicon = $this->get_param('default_favicon') ; 
				if ($old_default_favicon != $new_default_favicon) {
					if ($this->get_param('default_favicon') != $this->get_default_option('default_favicon')) {
						$upload_dir = wp_upload_dir();
						$path = $upload_dir["basedir"].$this->get_param('default_favicon')  ; 
						$ico = new icoTransform() ; 
						$ret = $ico->loadImage($path) ; 
						if ($ret)
							$ret = $ico->transformToICO($path.".ico") ; 
						if ($ret) {
							$params->add_comment(sprintf(__('The multiresolution ICO has been generated and is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$this->get_param('default_favicon').".ico'>", "</a>")) ; 
						} else {
							$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
						}
					} else {
						$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
					}
				} else {
					$upload_dir = wp_upload_dir();
					$path = $upload_dir["basedir"].$this->get_param('default_favicon')  ; 
					if (file_exists($path.".ico")) {
						$params->add_comment(sprintf(__('The multiresolution ICO is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$this->get_param('default_favicon').".ico'>", "</a>")) ; 
					} else {
						$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
					}
				}
				
				$params->add_title(sprintf(__('1st customized Favicon',$this->pluginID), $title)) ; 
				$old_default_favicon = $this->get_param('custom1_favicon') ; 
				$params->add_param('custom1_favicon', __('The 1st customized favicon image:',$this->pluginID)) ; 
				$new_default_favicon = $this->get_param('custom1_favicon') ; 
				if ($old_default_favicon != $new_default_favicon) {
					if ($this->get_param('custom1_favicon') != $this->get_default_option('custom1_favicon')) {
						$upload_dir = wp_upload_dir();
						$path = $upload_dir["basedir"].$this->get_param('custom1_favicon')  ; 
						$ico = new icoTransform() ; 
						$ret = $ico->loadImage($path) ; 
						if ($ret)
							$ret = $ico->transformToICO($path.".ico") ; 
						if ($ret) {
							$params->add_comment(sprintf(__('The multiresolution ICO has been generated and is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$this->get_param('custom1_favicon').".ico'>", "</a>")) ; 
						} else {
							$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
						}
					} else {
						$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
					}
				} else {
					$upload_dir = wp_upload_dir();
					$path = $upload_dir["basedir"].$this->get_param('custom1_favicon')  ; 
					if (file_exists($path.".ico")) {
						$params->add_comment(sprintf(__('The multiresolution ICO is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$this->get_param('custom1_favicon').".ico'>", "</a>")) ; 
					} else {
						$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
					}
				}
				$params->add_param('custom1_rule', __('The 1st regexp rule:',$this->pluginID)) ; 
				$params->add_comment(sprintf(__('For instance, %s to have a specific icon for admin page',$this->pluginID), "<code>.*\/wp-admin\/.*</code>")) ; 

				$params->add_title(sprintf(__('2nd customized Favicon',$this->pluginID), $title)) ; 
				$old_default_favicon = $this->get_param('custom2_favicon') ; 
				$params->add_param('custom2_favicon', __('The 2nd customized favicon image:',$this->pluginID)) ; 
				$new_default_favicon = $this->get_param('custom2_favicon') ; 
				if ($old_default_favicon != $new_default_favicon) {
					if ($this->get_param('custom2_favicon') != $this->get_default_option('custom2_favicon')) {
						$upload_dir = wp_upload_dir();
						$path = $upload_dir["basedir"].$this->get_param('custom2_favicon')  ; 
						$ico = new icoTransform() ; 
						$ret = $ico->loadImage($path) ; 
						if ($ret)
							$ret = $ico->transformToICO($path.".ico") ; 
						if ($ret) {
							$params->add_comment(sprintf(__('The multiresolution ICO has been generated and is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$this->get_param('custom2_favicon').".ico'>", "</a>")) ; 
						} else {
							$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
						}
					} else {
						$params->add_comment(__('You may add ICO, PNG, GIF, JPG and BMP files. It will be converted into a multiresolution ico file.',$this->pluginID)) ; 
					}
				} else {
					$upload_dir = wp_upload_dir();
					$path = $upload_dir["basedir"].$this->get_param('custom2_favicon')  ; 
					if (file_exists($path.".ico")) {
						$params->add_comment(sprintf(__('The multiresolution ICO is stored %shere%s',$this->pluginID), "<a href='".$upload_dir["baseurl"].$this->get_param('custom2_favicon').".ico'>", "</a>")) ; 
					} else {
						$params->add_comment(__('No ICO has been generated because this file is incompatible... Sorry !',$this->pluginID)) ; 
					}
				}
				$params->add_param('custom2_rule', __('The 2nd regexp rule:',$this->pluginID)) ; 
				$params->add_comment(sprintf(__('For instance, %s to have a specific icon for the category with name %s',$this->pluginID), "<code>.*\/important\/.*</code>", "<code>important</code>")) ; 
				
				$params->flush() ; 
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() ) ; 	
			
			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new translationSL($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() ) ; 	

			ob_start() ; 
				echo __('This form is an easy way to contact the author and to discuss issues / incompatibilities / etc.',  $this->pluginID) ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() ) ; 	
			
			ob_start() ; 
				echo "<p>".__('Here is the plugins developped by the author:',  $this->pluginID) ."</p>" ; 
				$trans = new otherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other possible plugins',  $this->pluginID), ob_get_clean() ) ; 	
	
			echo $tabs->flush() ; 					
			
			echo $this->signature ; ?>
		</div>
		<?php
	}
}

$favicon_switcher = favicon_switcher::getInstance();

?>