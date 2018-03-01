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
				<h3>Stuff</h3>
				<div class="form-group">
					<input type="checkbox" name="nebula_options[check_tor]" id="check_tor" value="1" <?php checked('1', !empty($nebula_options['check_tor'])); ?> /><label for="check_tor">Check for Tor browser</label>
					<p class="nebula-help-text short-help form-text text-muted">Include Tor in browser checks.</p>
					<p class="nebula-help-text more-help form-text text-muted">Long help</p>
					<p class="option-keywords">moderate page speed impact</p>
				</div>
			</div>

			<div class="option-group">
				<h3>Examples</h3>

				<div class="form-group">
					<label for="example1">Example URL</label>
					<input type="text" name="nebula_options[example1]" id="example1" class="form-control nebula-validate-url" value="<?php echo $nebula_options['example1']; ?>" placeholder="Yeah Whatever" />
					<p class="nebula-help-text short-help form-text text-muted">Short help</p>
					<p class="nebula-help-text more-help form-text text-muted">Long Help</p>
					<p class="option-keywords"></p>
				</div>

				<div class="form-group">
					<input type="checkbox" name="nebula_options[example2]" id="example2" value="1" <?php checked('1', !empty($nebula_options['example2'])); ?> /><label for="example2">Example 2</label>
					<p class="nebula-help-text short-help form-text text-muted">Short help</p>
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