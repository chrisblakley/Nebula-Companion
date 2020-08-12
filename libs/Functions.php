<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

trait Companion_Functions {
	public function hooks(){
		global $pagenow;

		add_action('nebula_ga_before_send_pageview', array($this, 'poi_custom_dimension'));
		add_filter('nebula_hubspot_identify', array($this, 'poi_hubspot'), 10, 1);
		add_filter('nebula_cf7_debug_data', array($this, 'poi_cf7_debug_info'), 10, 1);
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
}