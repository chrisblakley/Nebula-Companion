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

				add_action('admin_init', array($this, 'plugin_json'));
			}

			//add_action() for all stuff here (admin side and non-admin side)
		}

		//Check for plugin updates
		public function plugin_json(){
			require_once(get_template_directory() . '/inc/vendor/plugin-update-checker/plugin-update-checker.php'); //Use the Nebula theme library
			$plugin_update_checker = Puc_v4_Factory::buildUpdateChecker(
				'https://raw.githubusercontent.com/chrisblakley/Nebula-Companion/master/inc/nebula_plugin.json',
				$this->plugin_directory . 'nebula-companion.php',
				'Nebula-Companion'
			);
		}
	}
}