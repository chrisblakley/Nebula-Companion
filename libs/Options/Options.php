<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Options') ){
	trait Companion_Options {
		public function hooks(){
			//add_action() calls go here
		}

		//Describe it
		public function options_functions_go_here(){
			//put functions here
		}
	}
}