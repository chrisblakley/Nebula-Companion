<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Admin') ){
	require_once plugin_dir_path(__FILE__) . '/Dashboard.php';

	trait Companion_Admin {
		use Companion_Dashboard { Companion_Dashboard::hooks as Companion_DashboardHooks;}

		public function hooks(){
			global $pagenow;

			if ( nebula()->is_admin_page() ){ //Admin side
				$this->Companion_DashboardHooks(); //Register Dashboard hooks
				add_action('admin_init', array($this, 'plugin_json'));

				add_filter('nebula_user_column_ip', array($this, 'user_ip_poi'), 10, 1);

			} else { //Front-end for admin users
				add_action('admin_bar_menu',  array($this, 'companion_admin_bar_menus'), 801);
			}
		}


		//Top-level companion admin bar menu items
		public function companion_admin_bar_menus($wp_admin_bar){
			$wp_admin_bar->add_node(array(
				'parent' => 'nebula',
				'id' => 'nebula-audit',
				'title' => '<i class="nebula-admin-fa fas fa-fw fa-list-alt"></i> Audit This Page',
				'href' => esc_url(add_query_arg('audit', 'true')),
			));
		}

		//Check for plugin updates
		public function plugin_json(){
			if ( $this->allow_plugin_update() ){
				require_once(get_template_directory() . '/inc/vendor/plugin-update-checker/plugin-update-checker.php'); //Use the library in the Nebula theme itself
				$plugin_update_checker = Puc_v4_Factory::buildUpdateChecker(
					'https://raw.githubusercontent.com/chrisblakley/Nebula-Companion/master/inc/nebula_plugin.json',
					$this->plugin_directory . 'nebula-companion.php',
					'Nebula-Companion'
				);
			}
		}

		//Check if automated Nebula Companion plugin updates are allowed
		public function allow_plugin_update(){
			if ( !nebula()->get_option('plugin_update_notification') ){
				return false;
			}

			$nebula_data = get_option('nebula_data');
			if ( $nebula_data['version_legacy'] === 'true' ){
				return false;
			}

			return true;
		}

		//Append the POI of an IP address in the user column
		public function user_ip_poi($last_ip){
			$notable_poi = $this->poi($last_ip);
			if ( !empty($notable_poi) ){
				$last_ip .= '<br><small>(' . esc_html($notable_poi) . ')</small>';
			}

			return $last_ip;
		}
	}
}