<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

trait Companion_Functions {
	public function hooks(){
		global $pagenow;

		if ( !nebula()->is_background_request() ){
			add_action('nebula_ga_before_send_pageview', array($this, 'poi_custom_dimension'));
			add_filter('nebula_hubspot_identify', array($this, 'poi_hubspot'), 10, 1);
			add_filter('nebula_cf7_debug_data', array($this, 'poi_cf7_debug_info'), 10, 1);
		}
	}

	public function poi_custom_dimension(){
		//Notable POI (IP Addresses)
		$poi = $this->poi();
		if ( nebula()->get_option('cd_notablepoi') && !empty($poi) ){
			echo 'ga("set", nebula.analytics.dimensions.poi, "' . esc_html($poi) . '");';
		}
	}

	public function poi_hubspot($hubspot_identify){
		$hubspot_identify['notable_poi'] = $this->poi();
		return $hubspot_identify;
	}

	public function poi_measurement_protocol($common_parameters){
		if ( nebula()->get_option('cd_notablepoi') ){
			$common_parameters['cd' . nebula()->ga_definition_index(nebula()->get_option('cd_notablepoi'))] = $this->poi();
		}

		return $common_parameters;
	}

	public function poi_cf7_debug_info($debug_data){
		$notable_poi = $this->poi();
		if ( !empty($notable_poi) ){
			$debug_data .= $notable_poi . PHP_EOL;
		}

		return $debug_data;
	}

	//Create a placeholder box as an FPO element
	public function fpo($title='FPO', $description='', $width='100%', $height="250px", $bg='#ddd', $icon='', $styles='', $classes=''){
		$safe_title = strtolower(str_replace(' ', '-', $title));

		if ( nebula()->color_brightness($bg) < 128 ){
			$text_hex = '#fff';
			$text_rgb = '255';
		} else {
			$text_hex = '#000';
			$text_rgb = '0';
		}

		if ( $bg === 'placeholder' ){
			$bg = '';
			$placeholder = '<svg x="0px" y="0px" width="100%" height="100%" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 1px solid #aaa; z-index: 1;">
		<line fill="none" stroke="#aaa" stroke-miterlimit="10" x1="0" y1="0" x2="100%" y2="100%" />
		<line fill="none" stroke="#aaa" stroke-miterlimit="10" x1="100%" y1="0" x2="0" y2="100%" />
	</svg>';
		} else {
			$placeholder = '';
		}

		$icon_html = '';
		if ( $icon !== '' ){
			if ( strpos($icon, 'fa-') === false ){
				$icon = 'fa-' . $icon;
			}
			$icon_html = '<i class="fas ' . $icon . '"></i>';
		}

		$return .= '<div class="nebula-fpo ' . $safe_title . ' valign ' . $classes . '" style="position: relative; text-align: center; width: ' . $width . '; height: ' . $height . '; padding: 10px; background: ' . $bg . '; ' . $styles . '">
			<div style="position: relative; z-index: 5;">
				<h3 style="font-size: 21px; color: ' . $text_hex . ';">' . $icon_html . ' ' . $title . '</h3>
				<p style="font-size: 14px; color: rgba(' . $text_rgb . ',' . $text_rgb . ',' . $text_rgb . ',0.6);">' . $description . '</p>
			</div>
			' . $placeholder . '
		</div>';
		echo $return;
	}

	//Placeholder background image
	/* <div class="row" style="<?php fpo_bg_image(); ?>"> */
	public function fpo_bg_image($type='none', $color='#aaa'){
		$imgsrc = '';
		if ( $type === 'unsplash' || $type === 'photo' ){
			$imgsrc = unsplash_it(800, 600, 1);
		} elseif ( strpos($type, '#') !== false ){
			$color = $type;
		}

		if ( empty($imgsrc) ){
			$return = "background: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' x=\'0px\' y=\'0px\' width=\'100%\' height=\'100%\'><line fill=\'none\' stroke=\'" . $color . "\' stroke-miterlimit=\'10\' x1=\'0\' y1=\'0\' x2=\'100%\' y2=\'100%\'/><line fill=\'none\' stroke=\'" . $color . "\' stroke-miterlimit=\'10\' x1=\'100%\' y1=\'0\' x2=\'0\' y2=\'100%\' /></svg>') no-repeat; border: 1px solid " . $color . ";";
		} else {
			$return = "background: url('" . $imgsrc . "') no-repeat; background-size: cover;";
		}

		echo $return;
	}

	//Placeholder image... Consider deprecating this function
	public function fpo_image($width='100%', $height='200px', $type='none', $color='#000', $styles='', $classes=''){
		if ( $width === 'bg' || $width === 'background' ){
			$height = ( $height === '200px' )? 'none' : $height; //$height is type in this case
			$type = ( $type === 'none' )? '#000' : $type; //$type is color in this case.
			return fpo_bg_image($height, $type);
		}

		if ( is_int($width) ){
			$width .= 'px';
		}

		if ( is_int($height) ){
			$height .= 'px';
		}

		$imgsrc = '';
		if ( $type === 'unsplash' || $type === 'photo' || $width === 'unsplash' || $width === 'photo' ){
			$imgsrc = unsplash_it(800, 600, 1);
		} elseif ( strpos($type, '#') !== false ){
			$color = $type;
		}

		if ( !isset($color) || $color == '' ){
			$color='#000';
		}

		$return = '<div class="nebula-fpo-image ' . $classes . '" style="background: url(' . $imgsrc . ') no-repeat; background-size: 100% 100%; width: ' . $width . '; height: ' . $height . '; ' . $styles . '">';

		if ( $imgsrc == '' ){
			$return .= '<svg x="0px" y="0px" width="100%" height="100%" style="border: 1px solid ' . $color . ';">
		<line fill="none" stroke="' . $color . '" stroke-miterlimit="10" x1="0" y1="0" x2="100%" y2="100%" />
		<line fill="none" stroke="' . $color . '" stroke-miterlimit="10" x1="100%" y1="0" x2="0" y2="100%" />
	</svg>';
		}
		$return .= '</div>';
		echo $return;
	}
}