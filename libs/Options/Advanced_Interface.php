<?php
	$active_tab = 'metadata';
	if ( !empty($_GET['tab']) ){
		$active_tab = strtolower($_GET['tab']);
	}

	$direct_option = false;
	if ( !empty($_GET['option']) ){
		$direct_option = $_GET['option'];
	}

	$pre_filter = false;
	if ( !empty($_GET['filter']) ){
		$pre_filter = $_GET['filter'];
	}

	$serverProtocol = 'http://';
	if ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === 443 ){
		$serverProtocol = 'https://';
	}

	$host_url = explode(".", gethostname());
	$host_domain = '';
	if ( !empty($host_url) ){
		$host_domain = $host_url[1] . '.' . $host_url[2];
	}

	$nebula_data = get_option('nebula_data');
	$nebula_options = get_option('nebula_options');
?>

<div id="advanced" class="tab-pane <?php echo ( $active_tab === 'advanced' )? 'active' : ''; ?>">
	<div class="row title-row">
		<div class="col-xl-8">
			<h2>Advanced</h2>
		</div><!--/col-->
	</div><!--/row-->
	<div class="row">
		<div class="col-xl-8">
			<div class="option-group">
				<h3>Detection</h3>
				<div class="form-group" dependent-or="ga_tracking_id">
					<input type="checkbox" name="nebula_options[ga_load_abandon]" id="ga_load_abandon" value="1" <?php checked('1', !empty($nebula_options['ga_load_abandon'])); ?> /><label for="ga_load_abandon">Load Abandonment Tracking</label>
					<p class="nebula-help-text short-help form-text text-muted">Track when visitors leave the page before it finishes loading. (Default: <?php echo nebula()->user_friendly_default('ga_load_abandon'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This is implemented outside of the typical event tracking and because this event happens before the pageview is sent it will slightly alter user/session data (more users than sessions). It is recommended to create a View (and/or a segment) in Google Analytics specific to tracking load abandonment and filter out these hits from the primary reporting view (<code>Sessions > Exclude > Event Category > contains > Load Abandon</code>).</p>
					<p class="option-keywords"></p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[ip_geolocation]" id="ip_geolocation" value="1" <?php checked('1', !empty($nebula_options['ip_geolocation'])); ?> /><label for="ip_geolocation">IP Geolocation</label>
					<p class="nebula-help-text short-help form-text text-muted">Lookup the country, region, and city of the user based on their IP address. (Default: <?php echo nebula()->user_friendly_default('ip_geolocation'); ?>)</p>
					<p class="option-keywords">location remote resource minor page speed impact</p>
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
					<p class="nebula-help-text more-help form-text text-muted">Long help</p>
					<p class="option-keywords">moderate page speed impact</p>
				</div>


				<div class="form-group">
					<input type="checkbox" name="nebula_options[audit_mode]" id="audit_mode" value="1" <?php checked('1', !empty($nebula_options['audit_mode'])); ?> /><label for="audit_mode">Audit Mode</label>
					<p class="nebula-help-text short-help form-text text-muted">Visualize (at list) common issues on the front-end. (Default: <?php echo nebula()->user_friendly_default('audit_mode'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">This option automatically disables itself 1 hour after last use.</p>
					<p class="option-keywords"></p>
				</div>
			</div>

			<div class="option-group">
				<h3>Stylesheets</h3>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[dev_stylesheets]" id="dev_stylesheets" value="1" <?php checked('1', !empty($nebula_options['dev_stylesheets'])); ?> /><label for="dev_stylesheets">Developer Stylesheets</label>
					<p class="nebula-help-text short-help form-text text-muted">Allows multiple developers to work on stylesheets simultaneously. (Default: <?php echo nebula()->user_friendly_default('dev_stylesheets'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">Combines CSS files within /assets/css/dev/ into /assets/css/dev.css to allow multiple developers to work on a project without overwriting each other while maintaining a small resource footprint.</p>
					<p class="option-keywords">sass scss sccs scass css minor page speed impact</p>
				</div>
			</div><!-- /option-group -->

			<div class="option-group">
				<h3>Design References</h3>

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
					<label for="additional_design_references">Additional Design References</label>
					<textarea name="nebula_options[additional_design_references]" id="additional_design_references" class="form-control nebula-validate-textarea" rows="2"><?php echo $nebula_options['additional_design_references']; ?></textarea>
					<p class="nebula-help-text short-help form-text text-muted">Add design references (such as links to brand guides) to the admin dashboard</p>
					<p class="option-keywords"></p>
				</div>

			</div><!-- /option-group -->

			<div class="option-group">
				<h3>Prototyping</h3>

				<?php $themes = wp_get_themes(); ?>

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
			</div><!-- /option-group -->

			<div class="option-group">
				<h3>Examples</h3>

				<div class="form-group">
					<label for="example1">Example URL</label>
					<input type="text" name="nebula_options[example1]" id="example1" class="form-control nebula-validate-url" value="<?php echo $nebula_options['example1']; ?>" placeholder="Yeah Whatever" />
					<p class="nebula-help-text short-help form-text text-muted">Short help.</p>
					<p class="nebula-help-text more-help form-text text-muted">Long Help</p>
					<p class="option-keywords"></p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[example2]" id="example2" value="1" <?php checked('1', !empty($nebula_options['example2'])); ?> /><label for="example2">Example 2</label>
					<p class="nebula-help-text short-help form-text text-muted">Short help. (Default: <?php echo nebula()->user_friendly_default('example2'); ?>)</p>
					<p class="nebula-help-text more-help form-text text-muted">Long help</p>
					<p class="option-keywords"></p>
				</div>
			</div>

			<?php do_action('nebula_options_interface_advanced'); ?>
		</div><!--/col-->
	</div><!--/row-->
	<div class="row save-row">
		<div class="col-xl-8">
			<?php submit_button(); ?>
		</div><!--/col-->
	</div><!--/row-->
</div><!-- /tab-pane -->