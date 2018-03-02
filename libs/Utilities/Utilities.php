<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Utilities') ){
	//require_once plugin_dir_path(__FILE__) . '/Device.php';

	trait Companion_Utilities {
		//use Companion_Device { Companion_Device::hooks as Companion_DeviceHooks;}

		public function hooks(){
			add_filter('nebula_get_browser', array($this, 'check_tor_is_browser'));
			//$this->Companion_DeviceHooks(); //Register Device hooks
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