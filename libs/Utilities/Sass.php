<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Sass') ){
	trait Companion_Sass {
		public function hooks(){
			add_filter('nebula_scss_locations', array($this, 'companion_sass_locations'));
		}

		public function companion_sass_locations($scss_locations){
			$scss_locations['companion'] = array(
				'directory' => $this->plugin_directory,
				'uri' => $this->plugin_directory_uri,
				'imports' => array($this->plugin_directory . 'assets/scss/partials/')
			);

			return $scss_locations;
		}
	}
}