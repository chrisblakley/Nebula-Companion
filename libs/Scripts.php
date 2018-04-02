<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Scripts') ){
	trait Companion_Scripts {
		public function hooks(){
			//Register styles/scripts
			add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
			add_action('login_enqueue_scripts', array($this, 'register_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'register_scripts'));

			//Enqueue styles/scripts
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('login_enqueue_scripts', array($this, 'login_enqueue_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

			add_filter('nebula_lazy_load_assets', array($this, 'lazy_load_companion_assets'));
		}

		//Register scripts
		public function register_scripts(){
			//Stylesheets
			//wp_register_style($handle, $src, $dependencies, $version, $media);
			wp_register_style('nebula-companion-admin', $this->plugin_directory_uri . 'assets/css/admin.css', null, nebula()->version('full'), 'all');
			wp_register_style('nebula-companion-flags', $this->plugin_directory_uri . 'assets/css/flags.css', null, nebula()->version('full'), 'all');
		}

		//Enqueue frontend scripts
		function enqueue_scripts($hook){
			//frontend stuff
		}

		//Enqueue login scripts
		function login_enqueue_scripts($hook){
			//login stuff
		}

		//Enqueue admin scripts
		function admin_enqueue_scripts($hook){
			$current_screen = get_current_screen();

			//Stylesheets
			wp_enqueue_style('nebula-companion-admin');
			wp_enqueue_style('nebula-companion-flags');
		}

		//Prep companion assets to lazy load too
		public function lazy_load_companion_assets($assets){
			$assets['styles']['nebula-flags'] = '.flag';
			return $assets;
		}
	}
}