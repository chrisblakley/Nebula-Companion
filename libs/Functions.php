<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

trait Companion_Functions {
	public function hooks(){
		global $pagenow;

		add_filter('nebula_warnings', array($this, 'nebula_companion_warnings'));
		add_filter('wpcf7_special_mail_tags', array($this, 'cf7_companion_special_mail_tags'), 10, 3);
		add_filter('nebula_cf7_debug_data', array($this, 'nebula_companion_cf7_debug_data'));
	}

	//Add more CF7 special mail tags
	public function cf7_companion_special_mail_tags($output, $name, $html){
		$submission = WPCF7_Submission::get_instance();
		if ( !$submission ){
			return $output;
		}

		//IP Geolocation
		if ( $name === '_nebula_ip_geo' ){
			if ( $this->ip_location() ){
				$ip_location = $this->ip_location('all');
				return $ip_location->city . ', ' . $ip_location->region_name;
			} else {
				return '';
			}
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



	public function nebula_companion_cf7_debug_data($debug_data){

		if ( $this->ip_location() ){
			$ip_location = $this->ip_location('all');
			$debug_data .= 'IP Geolocation: ' . $ip_location->city . ', ' . $ip_location->region_name . '<br>';
		}

		return $debug_data;
	}

	public function is_auditing(){
		if ( nebula()->get_option('audit_mode') || (isset($_GET['audit']) && nebula()->is_dev()) ){
			return true;
		}

		return false;
	}

	//Add more warnings to the Nebula check
	public function nebula_companion_warnings($nebula_warnings){
		nebula()->timer('Nebula Companion Warnings');

		//If Audit Mode is enabled
		if ( $this->is_auditing() ){
			$nebula_audit_mode_expiration = get_transient('nebula_audit_mode_expiration');
			if ( empty($nebula_audit_mode_expiration) ){
				$nebula_audit_mode_expiration = time();
			}

			if ( nebula()->get_option('audit_mode') ){
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'error',
					'description' => '<a href="themes.php?page=nebula_options&tab=advanced&option=audit_mode">Audit Mode</a> is enabled! This is visible to all visitors. It will automatically be disabled in ' . human_time_diff($nebula_audit_mode_expiration+HOUR_IN_SECONDS) . '.',
					'url' => get_admin_url() . 'themes.php?page=nebula_options&tab=advanced&option=audit_mode'
				);
			}
		}

		//Audit mode only warnings (not used on one-off page audits)
		if ( nebula()->get_option('audit_mode') ){
			//Remind to check incognito
			if ( is_plugin_active('query-monitor/query-monitor.php') && nebula()->get_option('jquery_version') === 'footer' ){
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'warn',
					'description' => 'Plugins may move jQuery back to the head. Be sure to check incognito for JavaScript errors.',
				);
			}
		}

		//Only check these when auditing (not on all pageviews) to prevent undesired server load
		if ( $this->is_auditing() ){
			//Check contact email address
			if ( !nebula()->get_option('contact_email') ){
				$default_contact_email = get_option('admin_email', nebula()->get_user_info('user_email', array('id' => 1)));
				$email_domain = substr($default_contact_email, strpos($default_contact_email, "@")+1);
				if ( $email_domain != nebula()->url_components('domain') ){
					$nebula_warnings[] = array(
						'category' => 'Nebula Companion',
						'level' => 'warn',
						'description' => 'Default contact email domain does not match website. This email address will appear in metadata, so please verify this is acceptable.',
						'url' => get_admin_url() . 'themes.php?page=nebula_options&tab=metadata&option=contact_email'
					);
				}
			}

			//Check if readme.html exists. If so, recommend deleting it.
			if ( file_exists(get_home_path() . '/readme.html') ){
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'warn',
					'description' => 'The WordPress core readme.html file exists (which exposes version information) and should be deleted.',
				);
			}

			//Check if session directory is writable
			if ( !is_writable(session_save_path()) ){
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'warn',
					'description' => 'The session directory (' . session_save_path() . ') is not writable. Session data can not be used!',
				);
			}











			//Check each image within the_content()
				//If CMYK: https://stackoverflow.com/questions/8646924/how-can-i-check-if-a-given-image-is-cmyk-in-php
				//If not Progressive JPEG
				//If Quality setting is over 80%: https://stackoverflow.com/questions/2024947/is-it-possible-to-tell-the-quality-level-of-a-jpeg

			if ( 1==2 ){ //if post or page or custom post type? maybe not- just catch everything
				$post = get_post(get_the_ID());
				preg_match_all('/src="([^"]*)"/', $post->post_content, $matches); //Find images in the content... This wont work: I need the image path not url

				foreach ( $matches as $image_url ){
					//Check CMYK
					$image_info = getimagesize($image_url);
					var_dump($image_info); echo '<br>';
					if ( $image_info['channels'] == 4 ){
						echo 'it is cmyk<br><br>'; //ADD WARNING HERE
					} else {
						echo 'it is rgb<br><br>';
					}
				}

			}











			if ( !nebula()->is_admin_page() ){ //Front-end (non-admin) page warnings only
				//Check within all child theme files for various issues
				foreach ( nebula()->glob_r(get_stylesheet_directory() . '/*') as $filepath ){
					if ( is_file($filepath) ){
						$skip_filenames = array('README.md', 'debug_log', 'error_log', '/vendor', 'resources/');
						if ( !nebula()->contains($filepath, nebula()->skip_extensions()) && !nebula()->contains($filepath, $skip_filenames) ){ //If the filename does not contain something we are ignoring
							//Prep an array of strings to look for
							if ( substr(basename($filepath), -3) == '.js' ){ //JavaScript files
								$looking_for['debug_output'] = "/console\./i";
							} elseif ( substr(basename($filepath), -4) == '.php' ){ //PHP files
								$looking_for['debug_output'] = "/var_dump\(|var_export\(|print_r\(/i";
							} elseif ( substr(basename($filepath), -5) == '.scss' ){ //Sass files
								continue; //Remove this to allow checking scss files
								$looking_for['debug_output'] = "/@debug/i";
							} else {
								continue; //Skip any other filetype
							}

							//Check for Bootstrap JS functionality if bootstrap JS is disabled
							if ( !nebula()->get_option('allow_bootstrap_js') ){
								$looking_for['bootstrap_js'] = "/\.modal\(|\.bs\.|data-toggle=|data-target=|\.dropdown\(|\.tab\(|\.tooltip\(|\.carousel\(/i";
							}

							//Search the file and output if found anything
							if ( !empty($looking_for) ){
								foreach ( file($filepath) as $line_number => $full_line ){ //Loop through each line of the file
									foreach ( $looking_for as $category => $regex ){ //Search through each string we are looking for from above
										if ( preg_match("/^\/\/|\/\*|#/", trim($full_line)) == true ){ //Skip lines that begin with a comment
											continue;
										}

										preg_match($regex, $full_line, $details); //Actually Look for the regex in the line

										if ( !empty($details) ){
											if ( $category === 'debug_output' ){
												$nebula_warnings[] = array(
													'category' => 'Nebula Companion',
													'level' => 'warn',
													'description' => 'Possible debug output in <strong>' . str_replace(get_stylesheet_directory(), '', dirname($filepath)) . '/' . basename($filepath) . '</strong> on <strong>line ' . ($line_number+1) . '</strong>.'
												);
											} elseif ( $category === 'bootstrap_js' ){
												$nebula_warnings[] = array(
													'category' => 'Nebula Companion',
													'level' => 'warn',
													'description' => 'Bootstrap JS is disabled, but is possibly needed in <strong>' . str_replace(get_stylesheet_directory(), '', dirname($filepath)) . '/' . basename($filepath) . '</strong> on <strong>line ' . $line_number . '</strong>.',
													'url' => get_admin_url() . 'themes.php?page=nebula_options&tab=functions&option=allow_bootstrap_js'
												);
											}
										}
									}
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

			//Check word count for SEO
			$word_count = nebula()->word_count();
			if ( $word_count < 1900 ){
				$word_count_warning = ( $word_count === 0 )? 'Word count audit is not looking for custom fields outside of the main content editor. <a href="https://gearside.com/nebula/functions/word_count/?utm_campaign=nebula&utm_medium=nebula&utm_source=' . urlencode(get_bloginfo('name')) . '&utm_content=word+count+audit+warning" target="_blank">Hook custom fields into the Nebula word count functionality</a> to properly audit.' : 'Word count (' . $word_count . ') is low for SEO purposes (Over 1,000 is good, but 1,900+ is ideal). <small>Note: Detected word count may not include custom fields!</small>';
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'warn',
					'description' => $word_count_warning,
					'url' => get_edit_post_link(get_the_id())
				);
			}

			//Check for Yoast active
			if ( !is_plugin_active('wordpress-seo/wp-seo.php') ){
				$nebula_warnings[] = array(
					'category' => 'Nebula Companion',
					'level' => 'warn',
					'description' => 'Yoast SEO plugin is not active',
					'url' => get_admin_url() . 'themes.php?page=tgmpa-install-plugins'
				);
			}
		}

		//If website is live and using Prototype Mode
		if ( nebula()->is_site_live() && nebula()->get_option('prototype_mode') ){
			$nebula_warnings[] = array(
				'category' => 'Nebula Companion',
				'level' => 'warn',
				'description' => '<a href="themes.php?page=nebula_options&tab=advanced&option=prototype_mode">Prototype Mode</a> is enabled (' . ucwords($this->dev_phase()) . ')!',
				'url' => get_admin_url() . 'themes.php?page=nebula_options&tab=advanced&option=prototype_mode'
			);
		}

		//If Prototype mode is disabled, but Multiple Theme plugin is still activated
		if ( !nebula()->get_option('prototype_mode') && is_plugin_active('jonradio-multiple-themes/jonradio-multiple-themes.php') ){
			$nebula_warnings[] = array(
				'category' => 'Nebula Companion',
				'level' => 'error',
				'description' => '<a href="themes.php?page=nebula_options&tab=advanced&option=prototype_mode">Prototype Mode</a> is disabled, but <a href="plugins.php">Multiple Theme plugin</a> is still active.',
				'url' => get_admin_url() . 'plugins.php'
			);
		}

		nebula()->timer('Nebula Companion Warnings', 'end');
		return $nebula_warnings;
	}



}