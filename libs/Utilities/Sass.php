<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Sass') ){
	trait Companion_Sass {
		public function hooks(){
			add_filter('nebula_scss_locations', array($this, 'companion_sass_locations'));
			add_action('nebula_before_sass_compile', array($this, 'combine_dev_stylesheets'));
		}

		public function companion_sass_locations($scss_locations){
			$scss_locations['companion'] = array(
				'directory' => $this->plugin_directory,
				'uri' => $this->plugin_directory_uri,
				'imports' => array($this->plugin_directory . 'assets/scss/partials/')
			);

			return $scss_locations;
		}

		//Combine developer stylesheets
		public function combine_dev_stylesheets($directory=null, $directory_uri=null){
			$override = apply_filters('pre_nebula_combine_dev_stylesheets', null, $directory, $directory_uri);
			if ( isset($override) ){return;}

			$timer_name = nebula()->timer('Combine Dev Styles', 'start');

			if ( nebula()->get_option('dev_stylesheets') ){
				$directory = $location_paths['directory'] . '/assets';
				$directory_uri = $location_paths['uri'] . '/assets';

				if ( empty($directory) ){
					trigger_error('Dev stylesheet directories must be specified for files to be combined.', E_USER_NOTICE);
					return false;
				}

				WP_Filesystem();
				global $wp_filesystem;

				$file_counter = 0;
				$automation_warning = "/**** Warning: This is an automated file! Anything added to this file manually will be removed! ****/\r\n\r\n";
				$dev_stylesheet_files = glob($directory . '/scss/dev/*css');
				$dev_scss_file = $directory . '/scss/dev.scss';

				if ( !empty($dev_stylesheet_files) || strlen($dev_scss_file) > strlen($automation_warning)+10 ){ //If there are dev SCSS (or CSS) files -or- if dev.scss needs to be reset
					$wp_filesystem->put_contents($directory . '/scss/dev.scss', $automation_warning); //Empty /assets/scss/dev.scss
				}
				foreach ( $dev_stylesheet_files as $file ){
					$file_path_info = pathinfo($file);
					if ( is_file($file) && in_array($file_path_info['extension'], array('css', 'scss')) ){
						$file_counter++;

						//Include partials in dev.scss
						if ( $file_counter === 1 ){
							$import_partials = '';
							$import_partials .= '@import "' . get_template_directory() . '/assets/scss/partials/variables;"' . PHP_EOL;
							$import_partials .= '@import "../partials/variables";' . PHP_EOL;
							$import_partials .= '@import "' . get_template_directory() . '/assets/scss/partials/mixins;"' . PHP_EOL;
							$import_partials .= '@import "' . get_template_directory() . '/assets/scss/partials/helpers;"' . PHP_EOL;

							$wp_filesystem->put_contents($dev_scss_file, $automation_warning . $import_partials . PHP_EOL);
						}

						$this_scss_contents = $wp_filesystem->get_contents($file); //Copy file contents
						$empty_scss = ( $this_scss_contents == '' )? ' (empty)' : '';
						$dev_scss_contents = $wp_filesystem->get_contents($directory . '/scss/dev.scss');

						$dev_scss_contents .= "\r\n\r\n\r\n/*! ==========================================================================\r\n   " . 'File #' . $file_counter . ': ' . $directory_uri . "/scss/dev/" . $file_path_info['filename'] . '.' . $file_path_info['extension'] . $empty_scss . "\r\n   ========================================================================== */\r\n\r\n" . $this_scss_contents . "\r\n\r\n/* End of " . $file_path_info['filename'] . '.' . $file_path_info['extension'] . " */\r\n\r\n\r\n";

						$wp_filesystem->put_contents($directory . '/scss/dev.scss', $dev_scss_contents);
					}
				}
				if ( $file_counter > 0 ){
					add_action('wp_enqueue_scripts', function(){
						wp_enqueue_style('nebula-dev_styles-parent', get_template_directory_uri() . '/assets/css/dev.css?c=' . rand(1, 99999), array('nebula-main'), null);
						wp_enqueue_style('nebula-dev_styles-child', get_stylesheet_directory_uri() . '/assets/css/dev.css?c=' . rand(1, 99999), array('nebula-main'), null);
					});
				}
			}

			nebula()->timer($timer_name, 'end');
		}
	}
}