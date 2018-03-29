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
		//If Audit Mode is enabled
		if ( nebula()->get_option('audit_mode') || (isset($_GET['audit']) && nebula()->is_dev()) ){
			$nebula_audit_mode_expiration = get_transient('nebula_audit_mode_expiration');
			if ( empty($nebula_audit_mode_expiration) ){
				$nebula_audit_mode_expiration = time();
			}

			$nebula_warnings[] = array(
				'category' => 'Nebula Companion',
				'level' => 'error',
				'description' => '<a href="themes.php?page=nebula_options&tab=advanced&option=audit_mode">Audit Mode</a> is enabled! This is visible to all visitors. It will automatically be disabled in ' . human_time_diff($nebula_audit_mode_expiration+HOUR_IN_SECONDS) . '.'
			);
		}

		//Strict warnings (also used with Audit Mode)
		if ( nebula()->get_option('audit_mode') || nebula()->get_option('advanced_warnings') ){

			if ( !nebula()->is_admin_page() ){ //Non-Admin page warnings only
				//Search individual files for debug output
				foreach ( nebula()->glob_r(get_stylesheet_directory() . '/*') as $filepath ){
					if ( is_file($filepath) ){
						$skip_filenames = array('README.md', 'debug_log', 'error_log', '/vendor', 'resources/');
						if ( !nebula()->contains($filepath, nebula()->skip_extensions()) && !nebula()->contains($filepath, $skip_filenames) ){
							if ( substr(basename($filepath), -3) == '.js' ){ //JavaScript files
								$looking_for = "/console\./i";
							} elseif ( substr(basename($filepath), -4) == '.php' ){ //PHP files
								$looking_for = "/var_dump\(|var_export\(|print_r\(/i";
							} elseif ( substr(basename($filepath), -5) == '.scss' ){ //Sass files
								continue; //Remove this to allow checking scss files
								$looking_for = "/@debug/i";
							} else {
								continue;
							}

							foreach ( file($filepath) as $line_number => $full_line ){
								preg_match($looking_for, $full_line, $details);

								if ( !empty($details) ){
									$nebula_warnings[] = array(
										'category' => 'Nebula Companion',
										'level' => 'warn',
										'description' => 'Possible debug output in <strong>' . str_replace(get_stylesheet_directory(), '', dirname($filepath)) . '/' . basename($filepath) . '</strong> on <strong>line ' . $line_number . '</strong>.'
									);
								}
							}
						}
					}
				}
			}

			//Check for sitemap
			if ( !nebula()->is_available(home_url('/') . 'sitemap_index.xml', false, true) ){
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'warn',
					'description' => 'Missing sitemap XML'
				);
			}

			//Check for Yoast active
			if ( !is_plugin_active('wordpress-seo/wp-seo.php') ){
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'warn',
					'description' => 'Yoast SEO plugin is not active'
				);
			}
		}

		//If website is live and using Prototype Mode
		if ( nebula()->is_site_live() && nebula()->get_option('prototype_mode') ){
			$nebula_warnings[] = array(
				'category' => 'Nebula Companion',
				'level' => 'warn',
				'description' => '<a href="themes.php?page=nebula_options&tab=advanced&option=prototype_mode">Prototype Mode</a> is enabled (' . ucwords($this->dev_phase()) . ')!'
			);
		}

		//If Prototype mode is disabled, but Multiple Theme plugin is still activated
		if ( !nebula()->get_option('prototype_mode') && is_plugin_active('jonradio-multiple-themes/jonradio-multiple-themes.php') ){
			$nebula_warnings[] = array(
				'category' => 'Nebula Companion',
				'level' => 'error',
				'description' => '<a href="themes.php?page=nebula_options&tab=advanced&option=prototype_mode">Prototype Mode</a> is disabled, but <a href="plugins.php">Multiple Theme plugin</a> is still active.',
				'url' => 'plugins.php'
			);
		}

		return $nebula_warnings;
	}



}