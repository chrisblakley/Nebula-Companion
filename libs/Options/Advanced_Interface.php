<?php
	$active_tab = 'metadata';
	if ( !empty($_GET['tab']) ){
		$active_tab = strtolower($_GET['tab']);
	}

	$nebula_data = get_option('nebula_data');
	$nebula_options = get_option('nebula_options');
?>

<div id="advanced" class="tab-pane <?php echo ( $active_tab === 'advanced' )? 'active' : ''; ?>">
	<div class="row title-row">
		<div class="col-xl-8">
			<h2 class="pane-title">Advanced</h2>
		</div><!--/col-->
	</div><!--/row-->
	<div class="row">
		<div class="col">
			<div class="nebula-options-widgets-wrap">
				<div class="nebula-options-widgets metabox-holder">
					<div class="postbox-container">
						<?php do_meta_boxes('nebula_options', 'advanced', $nebula_options); ?>
					</div>
					<div class="postbox-container">
						<?php do_meta_boxes('nebula_options', 'advanced_side', $nebula_options); ?>
					</div>
				</div>
			</div>
		</div><!--/col-->
	</div><!--/row-->
	<div class="row save-row">
		<div class="col">
			<?php submit_button(); ?>
		</div><!--/col-->
	</div><!--/row-->
</div><!--/tab-pane-->