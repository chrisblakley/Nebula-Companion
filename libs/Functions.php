<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

trait Companion_Functions {
	public function hooks(){
		global $pagenow;

		add_filter('nebula_warnings', array($this, 'nebula_companion_warnings'));
		add_filter('wpcf7_special_mail_tags', array($this, 'cf7_companion_special_mail_tags'), 10, 3);
	}

	//Add more CF7 special mail tags
	public function cf7_companion_special_mail_tags($output, $name, $html){
		$submission = WPCF7_Submission::get_instance();
		if ( !$submission ){
			return $output;
		}

		//Weather
		if ( $name === '_nebula_weather' ){
			if ( $this->get_option('weather') ){
				$ip_zip = ( $this->ip_location() )? $this->ip_location('zip') : '';
				$temperature = $this->weather($ip_zip, 'temp');
				if ( !empty($temperature) ){
					return 'Weather: ' . $temperature . '&deg;F ' . $this->weather($ip_zip, 'conditions');
				} else {
					return '';
				}
			} else {
				return '';
			}
		}

		return $output;
	}

	//Add more warnings to the Nebula check
	public function nebula_companion_warnings($nebula_warnings){
		//If website is live and using Prototype Mode
		if ( nebula()->is_site_live() && nebula()->get_option('prototype_mode') ){
			$nebula_warnings[] = array(
				'level' => 'warn',
				'description' => '<a href="plugins.php">Prototype Mode</a> is enabled (' . ucwords($this->dev_phase()) . ')!'
			);
		}

		//If Prototype mode is disabled, but Multiple Theme plugin is still activated
		if ( !nebula()->get_option('prototype_mode') && is_plugin_active('jonradio-multiple-themes/jonradio-multiple-themes.php') ){
			$nebula_warnings[] = array(
				'level' => 'error',
				'description' => '<a href="plugins.php">Prototype Mode</a> is disabled, but <a href="plugins.php">Multiple Theme plugin</a> is still active.',
				'url' => 'plugins.php'
			);
		}

		return $nebula_warnings;
	}



}