<?php

//if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Utilities') ){
	//require_once plugin_dir_path(__FILE__) . '/Device.php';

	trait Companion_Utilities {
		//use Companion_Device { Companion_Device::hooks as Companion_DeviceHooks;}

		public function hooks(){
			//add_action() calls go here
			//$this->Companion_DeviceHooks(); //Register Device hooks
		}

		//Describe it
		public function utilities_functions_go_here(){
			//put functions here
		}
	}
}