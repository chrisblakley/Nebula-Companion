<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Admin') ){
	require_once plugin_dir_path(__FILE__) . '/Dashboard.php';

	trait Companion_Admin {
		use Companion_Dashboard { Companion_Dashboard::hooks as Companion_DashboardHooks;}

		public function hooks(){
			global $pagenow;

			if ( nebula()->is_admin_page() ){
				$this->Companion_DashboardHooks(); //Register Dashboard hooks

				//add_action() here for admin pages
			}

			//add_action() for all stuff here
		}

		//Describe it
		public function admin_functions_go_here(){
			//put functions here
		}
	}
}