<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Options') ){
	trait Companion_Options {
		public function hooks(){
			add_filter('nebula_default_options', array($this, 'companion_default_options'));

			add_filter('nebula_option_categories', array($this, 'add_advanced_option_category'));
			add_action('nebula_options_interface_additional_panes', array($this, 'add_advanced_option_pane'));

			add_action('admin_head', array($this, 'companion_options_metaboxes'));
			add_action('nebula_options_assets_metabox', array($this, 'use_companion_script'));
			add_filter('nebula_options_interface_preset_filters', array($this, 'companion_preset_option_filters'));
			add_action('nebula_options_admin_notifications_metabox', array($this, 'companion_plugin_update_option'));
			add_action('nebula_options_apis_metabox', array($this, 'companion_apis'));
		}

		//Add Nebula Companion options
		public function companion_default_options($default_options){
			$default_options['use_companion_script'] = 0;
			$default_options['ip_geo_api'] = '';
			$default_options['weather'] = 0;
			$default_options['advanced_form_identification'] = 0;
			$default_options['ga_load_abandon'] = 0;
			$default_options['prototype_mode'] = 0;
			$default_options['wireframe_theme'] = '';
			$default_options['staging_theme'] = '';
			$default_options['production_theme'] = '';
			$default_options['check_tor'] = 0;
			$default_options['design_reference_metabox'] = 0;
			$default_options['design_reference_link'] = '';
			$default_options['additional_design_references'] = '';
			$default_options['advanced_warnings'] = 0;
			$default_options['audit_mode'] = 0;
			$default_options['plugin_update_notification'] = 1;

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

		//Add preset filters to the select menu
		public function companion_preset_option_filters($preset_filters){
			$preset_filters['Companion'] = 'companion';

			return $preset_filters;
		}

		/*==========================
		 Nebula Options Companion Metaboxes
		 ===========================*/

		//Add individual options to existing Nebula metaboxes
		public function use_companion_script($nebula_options){
			?>
				<div class="form-group">
					<input type="checkbox" name="nebula_options[use_companion_script]" id="use_companion_script" value="1" <?php checked('1', !empty($nebula_options['use_companion_script'])); ?> /><label for="use_companion_script">Use Companion Script</label>
					<p class="nebula-help-text short-help form-text text-muted">Enables the companion.js file for additional functionality. (Default: <?php echo nebula()->user_friendly_default('use_companion_script'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted"></p>
					<p class="option-keywords">moderate page speed impact companion script</p>
				</div>
			<?php
		}

		public function companion_plugin_update_option($nebula_options){
			?>
				<div class="form-group">
					<input type="checkbox" name="nebula_options[plugin_update_notification]" id="plugin_update_notification" value="1" <?php checked('1', !empty($nebula_options['plugin_update_notification'])); ?> /><label for="plugin_update_notification">Nebula Companion Plugin Update Notification</label>
					<p class="nebula-help-text short-help form-text text-muted">Enable easy updates to the Nebula Companion plugin. (Default: <?php echo nebula()->user_friendly_default('plugin_update_notification'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted"></p>
					<p class="option-keywords">discretionary companion</p>
				</div>
			<?php
		}

		//Add full metaboxes to Nebula options tabs
		public function companion_options_metaboxes(){
			$current_screen = get_current_screen();
			if ( $current_screen->base === 'appearance_page_nebula_options' ){
				add_meta_box('nebula_companion_detection_metabox', 'Advanced Detection', array($this, 'nebula_companion_detection_metabox'), 'nebula_options', 'functions');
				add_meta_box('nebula_companion_prototyping_metabox', 'Prototyping', array($this, 'nebula_companion_prototyping_metabox'), 'nebula_options', 'advanced');
				add_meta_box('nebula_companion_design_references_metabox', 'Design References', array($this, 'nebula_companion_design_references_metabox'), 'nebula_options', 'administration');
			}
		}

		public function companion_apis($nebula_options){
			?>
				<div class="form-group">
					<label for="ip_geo_api">IP Geolocation API Key</label>
					<input type="text" name="nebula_options[ip_geo_api]" id="ip_geo_api" class="form-control" value="<?php echo $nebula_options['ip_geo_api']; ?>" />
					<p class="nebula-help-text short-help form-text text-muted">Lookup the country, region, and city of the user based on their IP address by entering your API key from <a href="https://ipstack.com/signup/free" target="_blank">IP Stack</a>.</p>
					<p class="option-keywords">location remote resource moderate page speed impact companion</p>
				</div>
			<?php
		}

		public function nebula_companion_detection_metabox($nebula_options){
			?>
				<div class="form-group" dependent-or="ga_tracking_id">
					<input type="checkbox" name="nebula_options[ga_load_abandon]" id="ga_load_abandon" value="1" <?php checked('1', !empty($nebula_options['ga_load_abandon'])); ?> /><label for="ga_load_abandon">Load Abandonment Tracking</label>
					<p class="nebula-help-text short-help form-text text-muted">Track when visitors leave the page before it finishes loading. (Default: <?php echo nebula()->user_friendly_default('ga_load_abandon'); ?>)</p>
					<p class="dependent-note hidden">This option is dependent on a Google Analytics Tracking ID</p>
					<p class="nebula-help-text more-help form-text text-muted">This is implemented outside of the typical event tracking and because this event happens before the pageview is sent it will slightly alter user/session data (more users than sessions). It is recommended to create a View (and/or a segment) in Google Analytics specific to tracking load abandonment and filter out these hits from the primary reporting view (<code>Sessions > Exclude > Event Category > contains > Load Abandon</code>).</p>
					<p class="option-keywords">companion</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[weather]" id="weather" value="1" <?php checked('1', !empty($nebula_options['weather'])); ?> /><label for="weather">Weather Detection</label>
					<p class="nebula-help-text short-help form-text text-muted">Lookup weather conditions for locations. (Default: <?php echo nebula()->user_friendly_default('weather'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">Can be used for changing content as well as analytics.</p>
					<p class="option-keywords">location remote resource major page speed impact companion</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[check_tor]" id="check_tor" value="1" <?php checked('1', !empty($nebula_options['check_tor'])); ?> /><label for="check_tor">Check for Tor browser</label>
					<p class="nebula-help-text short-help form-text text-muted">Include Tor in browser checks. (Default: <?php echo nebula()->user_friendly_default('check_tor'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted"></p>
					<p class="option-keywords">moderate page speed impact companion</p>
				</div>

				<div class="form-group" dependent-or="use_companion_script">
					<input type="checkbox" name="nebula_options[advanced_form_identification]" id="advanced_form_identification" value="1" <?php checked('1', !empty($nebula_options['advanced_form_identification'])); ?> /><label for="advanced_form_identification">Real-Time Form Identification</label>
					<p class="nebula-help-text short-help form-text text-muted">Use advanced methods of identification to send to the CRM. (Default: <?php echo nebula()->user_friendly_default('advanced_form_identification'); ?>)</p>
					<p class="dependent-note hidden">This option is dependent on the use of the companion script (companion.js).</p>
					<p class="nebula-help-text more-help form-text text-muted">This includes the use of query parameters and real-time form input listeners.</p>
					<p class="option-keywords">gdpr hubspot companion script</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[advanced_warnings]" id="advanced_warnings" value="1" <?php checked('1', !empty($nebula_options['advanced_warnings'])); ?> /><label for="advanced_warnings">Advanced Warnings</label>
					<p class="nebula-help-text short-help form-text text-muted">Check for more strict Nebula warnings. (Default: <?php echo nebula()->user_friendly_default('advanced_warnings'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This will cause slightly longer page load times for admins and developers.</p>
					<p class="option-keywords">minor page speed impact companion</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[audit_mode]" id="audit_mode" value="1" <?php checked('1', !empty($nebula_options['audit_mode'])); ?> /><label for="audit_mode">Audit Mode</label>
					<p class="nebula-help-text short-help form-text text-muted">Visualize (and list) common issues on the front-end. (Default: <?php echo nebula()->user_friendly_default('audit_mode'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This option automatically disables itself 1 hour after last use.</p>
					<p class="option-keywords">major page speed impact companion</p>
				</div>
			<?php
		}

		public function nebula_companion_design_references_metabox($nebula_options){
			?>
				<div class="form-group">
					<input type="checkbox" name="nebula_options[design_reference_metabox]" id="design_reference_metabox" value="1" <?php checked('1', !empty($nebula_options['design_reference_metabox'])); ?> /><label for="design_reference_metabox">Design Reference Metabox</label>
					<p class="nebula-help-text short-help form-text text-muted">Show the Design Reference dashboard metabox. (Default: <?php echo nebula()->user_friendly_default('design_reference_metabox'); ?>)</p>
					<p class="option-keywords">companion</p>
				</div>

				<div class="form-group">
					<label for="design_reference_link">Design File(s) URL</label>
					<input type="text" name="nebula_options[design_reference_link]" id="design_reference_link" class="form-control nebula-validate-url" value="<?php echo $nebula_options['design_reference_link']; ?>" placeholder="http://" />
					<p class="nebula-help-text short-help form-text text-muted">Link to the design file(s).</p>
					<p class="option-keywords">companion</p>
				</div>

				<div class="form-group">
					<label for="additional_design_references">Additional Design Notes</label>
					<textarea name="nebula_options[additional_design_references]" id="additional_design_references" class="form-control nebula-validate-textarea" rows="2"><?php echo $nebula_options['additional_design_references']; ?></textarea>
					<p class="nebula-help-text short-help form-text text-muted">Add design references (such as links to brand guides) to the admin dashboard</p>
					<p class="option-keywords">companion</p>
				</div>
			<?php
		}

		public function nebula_companion_prototyping_metabox($nebula_options){
			$themes = wp_get_themes();
			?>
				<div class="form-group">
					<input type="checkbox" name="nebula_options[prototype_mode]" id="prototype_mode" value="1" <?php checked('1', !empty($nebula_options['prototype_mode'])); ?> /><label for="prototype_mode">Prototype Mode</label>
					<p class="nebula-help-text short-help form-text text-muted">When prototyping, enable this setting. (Default: <?php echo nebula()->user_friendly_default('prototype_mode'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">Use the wireframe theme and production theme settings to develop the site while referencing the prototype. Use the staging theme to edit the site or develop new features while the site is live. If the staging theme is the active theme, use the Advanced Setting dropdown for "Theme For Everything" and choose a theme there for general visitors (Note: If using this setting, you may need to select that same theme for the admin-ajax option too!).</p>
					<p class="option-keywords">companion</p>
				</div>

				<div class="form-group" dependent-of="prototype_mode">
					<label for="wireframe_theme">Wireframe Theme</label>
					<select name="nebula_options[wireframe_theme]" id="wireframe_theme" class="form-control nebula-validate-select">
						<option value="" <?php selected('', $nebula_options['wireframe_theme']); ?>>None</option>
                        <?php foreach ( $themes as $key => $value ): ?>
                            <option value="<?php echo $key; ?>" <?php selected($key, $nebula_options['wireframe_theme']); ?>><?php echo $value->get('Name') . ' (' . $key . ')'; ?></option>
                        <?php endforeach; ?>
					</select>
					<p class="nebula-help-text short-help form-text text-muted">The theme to use as the wireframe. Viewing this theme will trigger a greyscale view.</p>
					<p class="dependent-note hidden">This option is dependent on Prototype Mode.</p>
					<p class="option-keywords">companion</p>
				</div>

				<div class="form-group" dependent-of="prototype_mode">
					<label for="staging_theme">Staging Theme</label>
					<select name="nebula_options[staging_theme]" id="staging_theme" class="form-control nebula-validate-select">
						<option value="" <?php selected('', $nebula_options['staging_theme']); ?>>None</option>
                        <?php foreach ( $themes as $key => $value ): ?>
                            <option value="<?php echo $key; ?>" <?php selected($key, $nebula_options['staging_theme']); ?>><?php echo $value->get('Name') . ' (' . $key . ')'; ?></option>
                        <?php endforeach; ?>
					</select>
					<p class="nebula-help-text short-help form-text text-muted">The theme to use for staging new features. This is useful for site development after launch.</p>
					<p class="dependent-note hidden">This option is dependent on Prototype Mode.</p>
					<p class="option-keywords">companion</p>
				</div>

				<div class="form-group" dependent-of="prototype_mode">
					<label for="production_theme">Production (Live) Theme</label>
					<select name="nebula_options[production_theme]" id="production_theme" class="form-control nebula-validate-select">
						<option value="" <?php selected('', $nebula_options['production_theme']); ?>>None</option>
                        <?php foreach ( $themes as $key => $value ): ?>
                            <option value="<?php echo $key; ?>" <?php selected($key, $nebula_options['production_theme']); ?>><?php echo $value->get('Name') . ' (' . $key . ')'; ?></option>
                        <?php endforeach; ?>
					</select>
					<p class="nebula-help-text short-help form-text text-muted">The theme to use for production/live. This theme will become the live site.</p>
					<p class="dependent-note hidden">This option is dependent on Prototype Mode.</p>
					<p class="option-keywords">companion</p>
				</div>
			<?php
		}
	}
}