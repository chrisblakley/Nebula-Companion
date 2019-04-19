<?php
/*
Plugin Name: Nebula Companion
Plugin URI: https://gearside.com/nebula/
Description: Advanced features for use with the Nebula theme.
Version: 6.11.18.9633
Author: Chris Blakley
Author URI: https://gearside.com/nebula
*/

//If Nebula is not active, leave this file.
$active_theme = wp_get_theme();
if ( $active_theme->get('Template') == 'Nebula-master' || $active_theme->get('Name') == 'Nebula' || $active_theme->get('Name') == 'Nebula Child' ){
	//Continue into the file.
} else {
	return;
}

if ( !class_exists('Nebula_Companion') ){
	//Require Nebula libraries
	require_once plugin_dir_path(__FILE__) . 'libs/Scripts.php';
	require_once plugin_dir_path(__FILE__) . 'libs/Options/Options.php';
	require_once plugin_dir_path(__FILE__) . 'libs/Utilities/Utilities.php';
	require_once plugin_dir_path(__FILE__) . 'libs/Functions.php';
	require_once plugin_dir_path(__FILE__) . 'libs/Admin/Admin.php';
	require_once plugin_dir_path(__FILE__) . 'libs/Prototyping.php';

	class Nebula_Companion {
		use Companion_Scripts { Companion_Scripts::hooks as Companion_ScriptHooks; }
		use Companion_Options { Companion_Options::hooks as Companion_OptionsHooks; }
		use Companion_Utilities { Companion_Utilities::hooks as Companion_UtilitiesHooks; }
		use Companion_Functions { Companion_Functions::hooks as Companion_FunctionsHooks; }
		use Companion_Admin { Companion_Admin::hooks as Companion_AdminHooks; }
		use Companion_Prototyping { Companion_Prototyping::hooks as Companion_PrototypingHooks; }

		private static $instance;

		//Get active instance
		public static function instance(){
			if ( !self::$instance ){
				self::$instance = new Nebula_Companion();
				self::$instance->constants();
				self::$instance->variables();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		//Setup plugin constants
		private function constants(){
			$this->plugin_directory = plugin_dir_path(__FILE__); //This DOES have a trailing slash
			$this->plugin_directory_uri = plugin_dir_url(__FILE__); //This DOES have a trailing slash
		}

		//Set variables
		private function variables(){
			$this->wp_hook_times = array();
			$this->time_before_companion = microtime(true); //Prep the time before Nebula companion begins
		}

		//Run action and filter hooks
		private function hooks(){
			$this->Companion_ScriptHooks(); //Register Scripts hooks
			$this->Companion_OptionsHooks(); //Register Options hooks
			$this->Companion_UtilitiesHooks(); //Register Utilities hooks
			$this->Companion_FunctionsHooks(); //Register Functions hooks

			if ( nebula()->is_admin_page() || is_admin_bar_showing() ){
				$this->Companion_AdminHooks(); // Register Admin hooks
			}

			if ( nebula()->get_option('prototype_mode') ){
				$this->PrototypingHooks(); //Register Prototyping hooks
			}
		}

		//Activate the plugin
		public static function activate(){
			nebula()->usage('Companion Plugin Activation');
		}

		//Deactivate the plugin
		public static function deactivate(){
			//Do nothing
		}
	}
}

if ( class_exists('Nebula_Companion') ){
	//Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('Nebula_Companion', 'activate'));
	register_deactivation_hook(__FILE__, array('Nebula_Companion', 'deactivate'));

	//If Nebula is not active, leave this file.
	$active_theme = wp_get_theme();
	if ( $active_theme->get('Template') == 'Nebula-master' || $active_theme->get('Name') == 'Nebula' || $active_theme->get('Name') == 'Nebula Child' ){
		//Continue into the file.
		//echo '<p>allowable theme</p>';
	} else {
		return;
	}

	//Instantiate the plugin class
	$Nebula_Companion = new Nebula_Companion();

	//Init WP Core Functions (if not already)
	require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	require_once(ABSPATH . 'wp-admin/includes/file.php');
}

//The main function responsible for returning Nebula_Companion instance
add_action('init', 'nebula_companion', 2); //Priority of 2 to make sure nebula() is initialized first
function nebula_companion(){
	return Nebula_Companion::instance();
}