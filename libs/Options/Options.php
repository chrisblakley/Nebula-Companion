<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Options') ){
	trait Companion_Options {
		public function hooks(){
			add_filter('nebula_default_options', array($this, 'companion_default_options'));

			add_action('admin_head', array($this, 'companion_options_metaboxes'));
			add_action('nebula_options_assets_metabox', array($this, 'use_companion_script'));
			add_filter('nebula_options_interface_preset_filters', array($this, 'companion_preset_option_filters'));
			add_action('nebula_options_admin_notifications_metabox', array($this, 'companion_plugin_update_option'));
			add_action('nebula_options_apis_metabox', array($this, 'companion_apis'));
			add_action('nebula_options_custom_dimensions_metabox', array($this, 'companion_custom_dimensions'));

			add_action('nebula_options_staff_users_metabox', array($this, 'companion_administrative_options'));
		}

		//Add Nebula Companion options
		public function companion_default_options($default_options){
			$default_options['use_companion_script'] = 0;
			$default_options['ip_geo_api'] = '';
			$default_options['weather'] = 0;
			$default_options['advanced_form_identification'] = 0;
			$default_options['ga_load_abandon'] = 0;
			$default_options['staging_theme'] = '';
			$default_options['production_theme'] = '';
			$default_options['check_tor'] = 0;
			$default_options['audit_mode'] = 0;
			$default_options['plugin_update_notification'] = 1;
			$default_options['cd_notablepoi'] = '';
			$default_options['notableiplist'] = '';
			$default_options['github_pat'] = '';

			return $default_options;
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
				add_meta_box('nebula_companion_detection_metabox', 'Advanced Detection', array($this, 'nebula_companion_detection_metabox'), 'nebula_options', 'functions_side');
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

				<div class="form-group">
					<label for="github_pat">GitHub Personal Access Token</label>
					<input type="text" name="nebula_options[github_pat]" id="github_pat" class="form-control" placeholder="0000000000000000000000000000000000000000" value="<?php echo $nebula_options['github_pat']; ?>" />
					<p class="nebula-help-text short-help form-text text-muted"><a href="https://github.com/settings/tokens/new" target="_blank">Generate a Personal Access Token</a> to retrieve Issues and commits on the WordPress Dashboard <a href="https://docs.github.com/en/github/authenticating-to-github/creating-a-personal-access-token" target="_blank">(GitHub instructions here)</a>. Nebula only needs basic repo and read discussion scopes.</p>
					<p class="option-keywords">github api pat personal access token issues commits discussions metabox</p>
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
					<input type="checkbox" name="nebula_options[audit_mode]" id="audit_mode" value="1" <?php checked('1', !empty($nebula_options['audit_mode'])); ?> /><label for="audit_mode">Audit Mode</label>
					<p class="nebula-help-text short-help form-text text-muted">Visualize (and list) common issues on the front-end. (Default: <?php echo nebula()->user_friendly_default('audit_mode'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This option automatically disables itself 1 hour after last use.</p>
					<p class="option-keywords">major page speed impact companion</p>
				</div>
			<?php
		}

		public function companion_custom_dimensions($nebula_options){
			$dimension_regex = '^dimension([0-9]{1,3})$';

			?>
				<div class="form-group">
					<div class="input-group">
						<div class="input-group-prepend">
							<div class="input-group-text">Notable POI</div>
						</div>
						<input type="text" name="nebula_options[cd_notablepoi]" id="cd_notablepoi" class="form-control nebula-validate-regex" data-valid-regex="<?php echo $dimension_regex; ?>" value="<?php echo $nebula_options['cd_notablepoi']; ?>" />
					</div>
					<p class="nebula-help-text short-help form-text text-muted">Stores named locations when detected. Scope: User</p>
					<p class="nebula-help-text more-help form-text text-muted">Stores named IP addresses (from the Administration tab). Also passes data using the ?poi query string (useful for email marketing using personalization within links). Also sends value of input fields with class "nebula-poi" on form submits (when applicable).</p>
					<p class="option-keywords">recommended custom dimension</p>
				</div>
			<?php
		}

		public function companion_administrative_options($nebula_options){
			?>
				<div class="form-group">
					<label for="notableiplist">Notable IPs</label>
					<textarea name="nebula_options[notableiplist]" id="notableiplist" class="form-control nebula-validate-textarea" rows="6" placeholder="192.168.0.1 Name Here"><?php echo $nebula_options['notableiplist']; ?></textarea>
					<p class="nebula-help-text short-help form-text text-muted">A list of named IP addresses. Enter each IP (or RegEx to match) on a new line with a space separating the IP address and name.</p>
					<p class="nebula-help-text more-help form-text text-muted">Name IPs by location to avoid <a href="https://support.google.com/analytics/answer/2795983" target="_blank" rel="noopener">Personally Identifiable Information (PII)</a> issues (Do not use peoples' names). Be sure to set up a Custom Dimension in Google Analytics and add the dimension index in the Analytics tab!<br />Tip: IP data can be sent with <a href="https://nebula.gearside.com/examples/contact-form-7/?utm_campaign=documentation&utm_medium=options&utm_source=<?php echo urlencode(get_bloginfo('name')); ?>&utm_content=notable+ips+help<?php echo nebula()->get_user_info('user_email', array('prepend' => '&crm-email=')); ?>" target="_blank" rel="noopener">Nebula contact forms</a>!</p>
					<p class="option-keywords">recommended</p>
				</div>
			<?php
		}
	}
}