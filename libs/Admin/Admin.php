<?php

//if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Admin') ){
	require_once plugin_dir_path(__FILE__) . '/Dashboard.php';

	trait Companion_Admin {
		use Companion_Dashboard { Companion_Dashboard::hooks as Companion_DashboardHooks;}

		public function hooks(){
			global $pagenow;

			if ( is_admin() ){ //@todo: use nebula()->is_admin_page() here
				$this->Companion_DashboardHooks(); //Register Dashboard hooks

				//add_filter('admin_body_class', array($this, 'admin_body_classes'));
				//add_action('upgrader_process_complete', array($this, 'theme_update_automation'), 10, 2); //Action 'upgrader_post_install' also exists.
				//add_action('load-post-new.php', array($this, 'post_meta_boxes_setup'));
			}

			//Disable auto curly quotes (smart quotes)
			//remove_filter('the_content', 'wptexturize');
			//remove_filter('the_excerpt', 'wptexturize');
			//remove_filter('comment_text', 'wptexturize');
			//add_filter('run_wptexturize', '__return_false');
		}

		//Describe it
		public function admin_functions_go_here(){
			//put functions here
		}
	}
}