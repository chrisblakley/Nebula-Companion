<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Options') ){
	trait Companion_Options {
		public function hooks(){
			add_filter('nebula_default_options', array($this, 'companion_default_options'));

			add_filter('nebula_option_categories', array($this, 'add_advanced_option_category'));
			add_action('nebula_options_interface_additional_panes', array($this, 'add_advanced_option_pane'));
		}




		public function companion_default_options($default_options){
			$default_options['example1'] = '';
			$default_options['example2'] = 0;

			$default_options['check_tor'] = 0;
			return $default_options;
		}

		//Add Advanced category to Nebula Options navigation
		public function add_advanced_option_category($categories){
			$categories[] = array('name' => 'Advanced', 'icon' => 'fa-puzzle-piece');
			return $categories;
		}

		public function add_advanced_option_pane(){
			require_once plugin_dir_path(__FILE__) . '/Advanced_Interface.php'; //Uncomment this after moving the panes to that file
		}
	}
}