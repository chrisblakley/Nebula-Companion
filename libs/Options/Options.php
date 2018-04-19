<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Options') ){
	trait Companion_Options {
		public function hooks(){
			add_filter('nebula_default_options', array($this, 'companion_default_options'));

			add_filter('nebula_option_categories', array($this, 'add_advanced_option_category'));
			add_action('nebula_options_interface_additional_panes', array($this, 'add_advanced_option_pane'));

			add_action('admin_head', array($this, 'companion_options_metaboxes'));
			add_action('nebula_options_frontend_metabox', array($this, 'nebula_companion_stylesheets_metabox'));
		}

		//Add Nebula Companion options
		public function companion_default_options($default_options){
			$default_options['example1'] = '';
			$default_options['example2'] = 0;

			$default_options['ip_geolocation'] = 0;
			$default_options['dev_stylesheets'] = 0;
			$default_options['weather'] = 0;
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


		/*==========================
		 Nebula Options Companion Metaboxes
		 ===========================*/

		public function companion_options_metaboxes(){
			$current_screen = get_current_screen();
			if ( $current_screen->base === 'appearance_page_nebula_options' ){
				add_meta_box('nebula_companion_detection_metabox', 'Detection', array($this, 'nebula_companion_detection_metabox'), 'nebula_options', 'functions');
				add_meta_box('nebula_companion_prototyping_metabox', 'Prototyping', array($this, 'nebula_companion_prototyping_metabox'), 'nebula_options', 'advanced');
				add_meta_box('nebula_companion_design_references_metabox', 'Design References', array($this, 'nebula_companion_design_references_metabox'), 'nebula_options', 'administration');
			}
		}

		public function nebula_companion_stylesheets_metabox($nebula_options){
			?>
				<div class="form-group">
					<input type="checkbox" name="nebula_options[dev_stylesheets]" id="dev_stylesheets" value="1" <?php checked('1', !empty($nebula_options['dev_stylesheets'])); ?> /><label for="dev_stylesheets">Developer Stylesheets</label>
					<p class="nebula-help-text short-help form-text text-muted">Allows multiple developers to work on stylesheets simultaneously. (Default: <?php echo nebula()->user_friendly_default('dev_stylesheets'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">Combines CSS files within /assets/css/dev/ into /assets/css/dev.css to allow multiple developers to work on a project without overwriting each other while maintaining a small resource footprint.</p>
					<p class="option-keywords">sass scss sccs scass css minor page speed impact</p>
				</div>
			<?php
		}

		public function nebula_companion_detection_metabox($nebula_options){
			?>
				<div class="form-group" dependent-or="ga_tracking_id">
					<input type="checkbox" name="nebula_options[ga_load_abandon]" id="ga_load_abandon" value="1" <?php checked('1', !empty($nebula_options['ga_load_abandon'])); ?> /><label for="ga_load_abandon">Load Abandonment Tracking</label>
					<p class="nebula-help-text short-help form-text text-muted">Track when visitors leave the page before it finishes loading. (Default: <?php echo nebula()->user_friendly_default('ga_load_abandon'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This is implemented outside of the typical event tracking and because this event happens before the pageview is sent it will slightly alter user/session data (more users than sessions). It is recommended to create a View (and/or a segment) in Google Analytics specific to tracking load abandonment and filter out these hits from the primary reporting view (<code>Sessions > Exclude > Event Category > contains > Load Abandon</code>).</p>
					<p class="option-keywords"></p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[ip_geolocation]" id="ip_geolocation" value="1" <?php checked('1', !empty($nebula_options['ip_geolocation'])); ?> /><label for="ip_geolocation">IP Geolocation</label>
					<p class="nebula-help-text short-help form-text text-muted">Lookup the country, region, and city of the user based on their IP address. (Default: <?php echo nebula()->user_friendly_default('ip_geolocation'); ?>)</p>
					<p class="option-keywords">location remote resource moderate page speed impact</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[weather]" id="weather" value="1" <?php checked('1', !empty($nebula_options['weather'])); ?> /><label for="weather">Weather Detection</label>
					<p class="nebula-help-text short-help form-text text-muted">Lookup weather conditions for locations. (Default: <?php echo nebula()->user_friendly_default('weather'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">Can be used for changing content as well as analytics.</p>
					<p class="option-keywords">location remote resource major page speed impact</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[check_tor]" id="check_tor" value="1" <?php checked('1', !empty($nebula_options['check_tor'])); ?> /><label for="check_tor">Check for Tor browser</label>
					<p class="nebula-help-text short-help form-text text-muted">Include Tor in browser checks. (Default: <?php echo nebula()->user_friendly_default('check_tor'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted"></p>
					<p class="option-keywords">moderate page speed impact</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[advanced_warnings]" id="advanced_warnings" value="1" <?php checked('1', !empty($nebula_options['advanced_warnings'])); ?> /><label for="advanced_warnings">Advanced Warnings</label>
					<p class="nebula-help-text short-help form-text text-muted">Check for more strict Nebula warnings. (Default: <?php echo nebula()->user_friendly_default('advanced_warnings'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This will cause slightly longer page load times for admins and developers.</p>
					<p class="option-keywords">minor page speed impact</p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[audit_mode]" id="audit_mode" value="1" <?php checked('1', !empty($nebula_options['audit_mode'])); ?> /><label for="audit_mode">Audit Mode</label>
					<p class="nebula-help-text short-help form-text text-muted">Visualize (and list) common issues on the front-end. (Default: <?php echo nebula()->user_friendly_default('audit_mode'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This option automatically disables itself 1 hour after last use.</p>
					<p class="option-keywords">major page speed impact</p>
				</div>
			<?php
		}

		public function nebula_companion_design_references_metabox($nebula_options){
			?>
				<div class="form-group">
					<input type="checkbox" name="nebula_options[design_reference_metabox]" id="design_reference_metabox" value="1" <?php checked('1', !empty($nebula_options['design_reference_metabox'])); ?> /><label for="design_reference_metabox">Design Reference Metabox</label>
					<p class="nebula-help-text short-help form-text text-muted">Show the Design Reference dashboard metabox. (Default: <?php echo nebula()->user_friendly_default('dev_stylesheets'); ?>)</p>
					<p class="option-keywords"></p>
				</div>

				<div class="form-group">
					<label for="design_reference_link">Design File(s) URL</label>
					<input type="text" name="nebula_options[design_reference_link]" id="design_reference_link" class="form-control nebula-validate-url" value="<?php echo $nebula_options['design_reference_link']; ?>" placeholder="http://" />
					<p class="nebula-help-text short-help form-text text-muted">Link to the design file(s).</p>
					<p class="option-keywords"></p>
				</div>

				<div class="form-group">
					<label for="additional_design_references">Additional Design Notes</label>
					<textarea name="nebula_options[additional_design_references]" id="additional_design_references" class="form-control nebula-validate-textarea" rows="2"><?php echo $nebula_options['additional_design_references']; ?></textarea>
					<p class="nebula-help-text short-help form-text text-muted">Add design references (such as links to brand guides) to the admin dashboard</p>
					<p class="option-keywords"></p>
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
					<p class="option-keywords"></p>
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
					<p class="option-keywords"></p>
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
					<p class="option-keywords"></p>
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
					<p class="option-keywords"></p>
				</div>
			<?php
		}







	}
}