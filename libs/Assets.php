<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Assets') ){
	trait Companion_Assets {
		public function hooks(){
			if ( !nebula()->is_background_request() ){
				//Register styles/scripts
				add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
				add_action('login_enqueue_scripts', array($this, 'register_scripts'));
				add_action('admin_enqueue_scripts', array($this, 'register_scripts'));

				//Enqueue styles/scripts
				add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
				add_action('login_enqueue_scripts', array($this, 'login_enqueue_scripts'));
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

				add_filter('nebula_brain', array($this, 'companion_brain'));

				add_filter('nebula_lazy_load_assets', array($this, 'lazy_load_companion_assets'));
			}
		}

		//Register scripts
		public function register_scripts(){
			//Stylesheets
			//wp_register_style($handle, $src, $dependencies, $version, $media);
			//wp_register_style('nebula-companion-admin', $this->plugin_directory_uri . 'assets/css/admin.css', null, nebula()->version('full'), 'all'); //This file is empty...
			wp_register_style('nebula-companion-flags', $this->plugin_directory_uri . 'assets/css/flags.css', null, nebula()->version('full'), 'all');

			nebula()->register_script('nebula-companion', $this->plugin_directory_uri . 'assets/js/companion.js', array('defer', 'module'), array('jquery-core', 'nebula-nebula'), nebula()->version('full'), true); //nebula.js (in the parent Nebula theme) is defined as a dependant here.
		}

		//Enqueue frontend scripts
		function enqueue_scripts($hook){
			if ( nebula()->get_option('use_companion_script') ){
				wp_enqueue_script('nebula-companion');
			}
		}

		//Enqueue login scripts
		function login_enqueue_scripts($hook){
			//login stuff
		}

		//Enqueue admin scripts
		function admin_enqueue_scripts($hook){
			$current_screen = get_current_screen();

			//Stylesheets
			//wp_enqueue_style('nebula-companion-admin'); //This file is empty
		}

		//Prep companion assets to lazy load too
		public function lazy_load_companion_assets($assets){
			$assets['styles']['nebula-flags'] = '.flag';
			return $assets; //Always return on a filter or else it will break
		}

		public function companion_brain($brain){
			$brain['site']['options']['advanced_form_identification'] = nebula()->get_option('advanced_form_identification');
			return $brain; //Always return on a filter or else it will break
		}
	}
}