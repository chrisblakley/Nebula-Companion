<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Utilities') ){
	//require_once plugin_dir_path(__FILE__) . '/Device.php';

	trait Companion_Utilities {
		//use Companion_Device { Companion_Device::hooks as Companion_DeviceHooks;}

		public function hooks(){
			add_filter('nebula_preconnect', array($this, 'additional_preconnects'));
			add_filter('nebula_get_browser', array($this, 'check_tor_is_browser'));
			add_filter('nebula_session_id', array($this, 'add_session_id_parameter'));
			//$this->Companion_DeviceHooks(); //Register Device hooks
		}


		public function add_session_id_parameter($session_data){
			//Prototype Mode
			if ( nebula()->get_option('prototype_mode') ){
				$session_data['p'] = true;
			}

			return $session_data;
		}

		//Detect location from IP address using https://freegeoip.io/
		public function ip_location($data=null, $ip_address=false){
			if ( nebula()->get_option('ip_geolocation') ){
				if ( empty($ip_address) ){
					$ip_address = nebula()->get_ip_address();

					if ( empty($data) ){
						return true; //If passed with no parameters, simply check if Nebula Option is enabled
					}
				}

				//Check cache first
				$ip_geo_data = wp_cache_get('nebula_ip_geolocation_' . str_replace('.', '_', $ip_address));
				if ( empty($ip_geo_data) ){
					//Check session next
					if ( !empty($_SESSION['nebula_ip_geolocation']) ){
						$ip_geo_data = $_SESSION['nebula_ip_geolocation'];
					}

					//Get new remote data
					if ( empty($_SESSION['nebula_ip_geolocation']) ){
						$response = nebula()->remote_get('http://freegeoip.io/json/' . $ip_address);
						if ( is_wp_error($response) || !is_array($response) || strpos($response['body'], 'Rate limit') === 0 ){
							return false;
						}

						$ip_geo_data = $response['body'];
						$_SESSION['nebula_ip_geolocation'] = $ip_geo_data;
					}

					wp_cache_set('nebula_ip_geolocation_' . str_replace('.', '_', $ip_address), $ip_geo_data); //Cache the result
				}

				if ( !empty($ip_geo_data) ){
					$ip_geo_data = json_decode($ip_geo_data);
					if ( !empty($ip_geo_data) ){
						switch ( str_replace(array(' ', '_', '-'), '', $data) ){
							case 'all':
							case 'object':
							case 'response':
								return $ip_geo_data;
							case 'country':
							case 'countryname':
								return $ip_geo_data->country_name;
								break;
							case 'countrycode':
								return $ip_geo_data->country_code;
								break;
							case 'region':
							case 'state':
							case 'regionname':
							case 'statename':
								return $ip_geo_data->region_name;
								break;
							case 'regioncode':
							case 'statecode':
								return $ip_geo_data->country_code;
								break;
							case 'city':
								return $ip_geo_data->city;
								break;
							case 'zip':
							case 'zipcode':
								return $ip_geo_data->zip_code;
								break;
							case 'lat':
							case 'latitude':
								return $ip_geo_data->latitude;
								break;
							case 'lng':
							case 'longitude':
								return $ip_geo_data->longitude;
								break;
							case 'geo':
							case 'coordinates':
								return $ip_geo_data->latitude . ',' . $ip_geo_data->longitude;
								break;
							case 'timezone':
								return $ip_geo_data->time_zone;
								break;
							default:
								return false;
								break;
						}
					}
				}
			}

			return false;
		}

		//Detect weather for Zip Code (using Yahoo! Weather)
		//https://developer.yahoo.com/weather/
		public function weather($zipcode=null, $data=''){
			if ( nebula()->get_option('weather') ){
				$override = apply_filters('pre_nebula_weather', null, $zipcode, $data);
				if ( isset($override) ){return;}

				if ( !empty($zipcode) && is_string($zipcode) && !ctype_digit($zipcode) ){ //ctype_alpha($zipcode)
					$data = $zipcode;
					$zipcode = nebula()->get_option('postal_code', '13204');
				} elseif ( empty($zipcode) ){
					$zipcode = nebula()->get_option('postal_code', '13204');
				}

				$weather_json = get_transient('nebula_weather_' . $zipcode);
				if ( empty($weather_json) ){ //No ?debug option here (because multiple calls are made to this function). Clear with a force true when needed.
					$yql_query = 'select * from weather.forecast where woeid in (select woeid from geo.places(1) where text=' . $zipcode . ')';

					$response = nebula()->remote_get('http://query.yahooapis.com/v1/public/yql?q=' . urlencode($yql_query) . '&format=json');
					if ( is_wp_error($response) ){
						trigger_error('A Yahoo Weather API error occurred. Yahoo may be down, or forecast for ' . $zipcode . ' may not exist.', E_USER_WARNING);
						return false;
					}

					$weather_json = $response['body'];
					set_transient('nebula_weather_' . $zipcode, $weather_json, MINUTE_IN_SECONDS*30); //30 minute expiration
				}
				$weather_json = json_decode($weather_json);

				if ( !$weather_json || empty($weather_json) || empty($weather_json->query->results) ){
					trigger_error('A Yahoo Weather API error occurred. Yahoo may be down, or forecast for ' . $zipcode . ' may not exist.', E_USER_WARNING);
					return false;
				} elseif ( $data == '' ){
					return true;
				}

				switch ( str_replace(' ', '', $data) ){
					case 'json':
						return $weather_json;
						break;
					case 'reported':
					case 'build':
					case 'lastBuildDate':
						return $weather_json->query->results->channel->lastBuildDate;
						break;
					case 'city':
						return $weather_json->query->results->channel->location->city;
						break;
					case 'state':
					case 'region':
						return $weather_json->query->results->channel->location->region;
						break;
					case 'country':
						return $weather_json->query->results->channel->location->country;
						break;
					case 'location':
						return $weather_json->query->results->channel->location->city . ', ' . $weather_json->query->results->channel->location->region;
						break;
					case 'latitude':
					case 'lat':
						return $weather_json->query->results->channel->item->lat;
						break;
					case 'longitude':
					case 'long':
					case 'lng':
						return $weather_json->query->results->channel->item->long;
						break;
					case 'geo':
					case 'geolocation':
					case 'coordinates':
						return $weather_json->query->results->channel->item->lat . ',' . $weather_json->query->results->channel->item->lat;
						break;
					case 'windchill':
					case 'chill':
						return $weather_json->query->results->channel->wind->chill;
						break;
					case 'windspeed':
						return $weather_json->query->results->channel->wind->speed;
						break;
					case 'sunrise':
						return $weather_json->query->results->channel->astronomy->sunrise;
						break;
					case 'sunset':
						return $weather_json->query->results->channel->astronomy->sunset;
						break;
					case 'temp':
					case 'temperature':
						return $weather_json->query->results->channel->item->condition->temp;
						break;
					case 'condition':
					case 'conditions':
					case 'current':
					case 'currently':
						return $weather_json->query->results->channel->item->condition->text;
						break;
					case 'forecast':
						return $weather_json->query->results->channel->item->forecast;
						break;
					default:
						break;
				}
			}

			return false;
		}

		//Add more preconnects when needed
		public function additional_preconnects($preconnects){
			//Weather
			if ( nebula()->get_option('weather') ){
				$preconnects[] = '//query.yahooapis.com';
			}

			return $preconnects;
		}

		//Hook into nebula()->get_browser() to check Tor too.
		public function check_tor_is_browser($info){
			if ( nebula()->get_option('check_tor') && $this->is_tor_browser() ){
				switch ( strtolower($info) ){
					case 'full':
					case 'name':
					case 'browser':
					case 'client':
						return 'Tor';
					case 'version':
						return ''; //Not possible to detect
					case 'engine':
						return 'Gecko';
					case 'type':
						return 'browser';
					default:
						return false;
				}
			}
		}

		//Check for the Tor browser
		//Nebula only calls this function if Device Detection option is enabled, but it can still be called manually.
		public function is_tor_browser(){
			$override = apply_filters('pre_is_tor_browser', null);
			if ( isset($override) ){return;}

			if ( nebula()->get_option('check_tor') ){
				//Check session and cookies first
				if ( (isset($GLOBALS['tor']) && $GLOBALS['tor'] === true) || (isset($_SESSION['tor']) && $_SESSION['tor'] === true) || (isset($_COOKIE['tor']) && $_COOKIE['tor'] == 'true') ){
					$GLOBALS['tor'] = true;
					return true;
				}

				if ( (isset($GLOBALS['tor']) && $GLOBALS['tor'] === false) && (isset($_SESSION['tor']) && $_SESSION['tor'] === false) ){
					$GLOBALS['tor'] = false;
					return false;
				}

				//Scrape entire exit IP list
				$ip_address = nebula()->get_ip_address();
				if ( isset($ip_address) ){
					$tor_list = get_transient('nebula_tor_list');
					if ( empty($tor_list) || nebula()->is_debug() ){ //If transient expired or is debug
						$response = nebula()->remote_get('https://check.torproject.org/cgi-bin/TorBulkExitList.py?ip=' . $_SERVER['SERVER_ADDR']);
						if ( !is_wp_error($response) ){
							$tor_list = $response['body'];
							set_transient('nebula_tor_list', $tor_list, HOUR_IN_SECONDS*48);
						}
					}

					//Parse the file
					if ( !empty($tor_list) ){
						foreach( explode("\n", $tor_list) as $line ){
							if ( !empty($line) && strpos($line, '#') === false ){
								if ( $line === $ip_address ){
									nebula()->set_global_session_cookie('tor', true);
									return true;
								}
							}
						}
					}
				}

				//Check individual exit point
				//Note: This would make a remote request to every new user. Commented out for optimization. Use the override filter to enable in a child theme.
/*
				if ( nebula()->is_available('http://torproject.org') ){
					$remote_ip_octets = explode(".", nebula()->get_ip_address());
					$server_ip_octets = explode(".", $_SERVER['SERVER_ADDR']);
					if ( gethostbyname($remote_ip_octets[3] . "." . $remote_ip_octets[2] . "." . $remote_ip_octets[1] . "." . $remote_ip_octets[0] . "." . $_SERVER['SERVER_PORT'] . "." . $remote_ip_octets[3] . "." . $remote_ip_octets[2] . "." . $remote_ip_octets[1] . "." . $remote_ip_octets[0] . ".ip-port.exitlist.torproject.org") === "127.0.0.2" ){
				        nebula()->set_global_session_cookie('tor', true);
						return true;
				    }
			    }
*/

			}

			nebula()->set_global_session_cookie('tor', false, array('global', 'session'));
			return false;
		}
	}
}