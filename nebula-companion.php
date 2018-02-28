<?php
/*
Plugin Name: Nebula Companion
Plugin URI: https://gearside.com/nebula/
Description: Advanced features for use with the Nebula theme
Version: 1.0.0
Author: Chris Blakley
Author URI: https://gearside.com/nebula
*/

if ( !class_exists('Nebula_Companion') ){


	//@todo: how to wait for Nebula class itself to exist first (so we can use nebula functions)?



	//Require Nebula libraries
	require_once plugin_dir_path(__FILE__) . '/libs/Options/Options.php';
	require_once plugin_dir_path(__FILE__) . '/libs/Utilities/Utilities.php';
	require_once plugin_dir_path(__FILE__) . '/libs/Functions.php';
	require_once plugin_dir_path(__FILE__) . '/libs/Admin/Admin.php';

	class Nebula_Companion {
		use Companion_Options { Companion_Options::hooks as Companion_OptionsHooks; }
		use Companion_Utilities { Companion_Utilities::hooks as Companion_UtilitiesHooks; }
		use Companion_Functions { Companion_Functions::hooks as Companion_FunctionsHooks; }
		use Companion_Admin { Companion_Admin::hooks as Companion_AdminHooks; }

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
			//do stuff
		}

		//Set variables
		private function variables(){
			//do stuff
		}

		//Run action and filter hooks
		private function hooks(){
			$this->Companion_OptionsHooks(); //Register Options hooks
			$this->Companion_UtilitiesHooks(); //Register Utilities hooks
			$this->Companion_FunctionsHooks(); //Register Functions hooks

			if ( is_admin() || is_admin_bar_showing() ){ //@todo: use nebula()->is_admin_page() here
				$this->Companion_AdminHooks(); // Register Admin hooks
			}
		}

		//Activate the plugin
		public static function activate(){
			//Do nothing
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



//@todo: Make "Advanced" Nebula Options tab


//@todo: Move Tor detection here and add it to Advanced options




//The main function responsible for returning Nebula instance
add_action('init', 'nebula_companion', 1);
function nebula_companion(){
	return Nebula_Companion::instance();
}
