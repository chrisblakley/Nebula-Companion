<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Utilities') ){
	require_once plugin_dir_path(__FILE__) . 'Sass.php';

	trait Companion_Utilities {
		use Companion_Sass { Companion_Sass::hooks as Companion_SassHooks;}

		public function hooks(){
			$this->Companion_SassHooks(); //Register Sass hooks

			add_filter('nebula_preconnect', array($this, 'additional_preconnects'));
			add_filter('nebula_get_browser', array($this, 'check_tor_is_browser'));
			add_filter('nebula_session_id', array($this, 'add_session_id_parameter'));
			//$this->Companion_DeviceHooks(); //Register Device hooks
			add_action('wp_footer', array($this, 'visualize_scroll_percent'));

			add_filter('nebula_finalize_timings', array($this, 'additional_final_timings'));

			add_action('nebula_head_open', array($this, 'ga_track_load_abandons')); //This is the earliest anything can be placed in the <head>

			add_action('nebula_options_saved', array($this, 'start_audit_mode'));
			add_action('wp_footer', array($this, 'audit_mode_output'), 9999); //Late execution as possible

			add_action('nebula_ga_before_send_pageview', array($this, 'analytics_before_pageview'));

			add_filter('nebula_poi', array($this, 'log_provided_poi'));





			//https://codex.wordpress.org/Plugin_API/Action_Reference
/*
			add_action('muplugins_loaded', array($this, 'all_wp_hook_times'), 1);
			add_action('registered_taxonomy', array($this, 'all_wp_hook_times'), 1);
			add_action('registered_post_type', array($this, 'all_wp_hook_times'), 1);
			add_action('plugins_loaded', array($this, 'all_wp_hook_times'), 1);
			add_action('sanitize_comment_cookies', array($this, 'all_wp_hook_times'), 1);
			add_action('setup_theme', array($this, 'all_wp_hook_times'), 1);
			add_action('load_textdomain', array($this, 'all_wp_hook_times'), 1);
			add_action('after_setup_theme', array($this, 'all_wp_hook_times'), 1);
			add_action('auth_cookie_malformed', array($this, 'all_wp_hook_times'), 1);
			add_action('auth_cookie_valid', array($this, 'all_wp_hook_times'), 1);
			add_action('set_current_user', array($this, 'all_wp_hook_times'), 1);
			add_action('init', array($this, 'all_wp_hook_times'), 1);
			add_action('widgets_init', array($this, 'all_wp_hook_times'), 1);
			add_action('register_sidebar', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_register_sidebar_widget', array($this, 'all_wp_hook_times'), 1);
			add_action('admin_bar_init', array($this, 'all_wp_hook_times'), 1);
			add_action('add_admin_bar_menus', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_loaded', array($this, 'all_wp_hook_times'), 1);
			add_action('parse_request', array($this, 'all_wp_hook_times'), 1);
			add_action('send_headers', array($this, 'all_wp_hook_times'), 1);
			add_action('parse_query', array($this, 'all_wp_hook_times'), 1);
			add_action('pre_get_posts', array($this, 'all_wp_hook_times'), 1);
			add_action('posts_selection', array($this, 'all_wp_hook_times'), 1);
			add_action('wp', array($this, 'all_wp_hook_times'), 1);
			add_action('template_redirect', array($this, 'all_wp_hook_times'), 1);
			add_action('get_header', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_enqueue_scripts', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_head', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_print_styles', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_print_scripts', array($this, 'all_wp_hook_times'), 1);
			add_action('get_search_form', array($this, 'all_wp_hook_times'), 1);
			add_action('get_template_part_content', array($this, 'all_wp_hook_times'), 1);
			add_action('get_sidebar', array($this, 'all_wp_hook_times'), 1);
			add_action('dynamic_sidebar', array($this, 'all_wp_hook_times'), 1);
			add_action('get_search_form', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_meta', array($this, 'all_wp_hook_times'), 1);
			add_action('get_footer', array($this, 'all_wp_hook_times'), 1);
			add_action('get_sidebar', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_footer', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_print_footer_scripts', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_before_admin_bar_render', array($this, 'all_wp_hook_times'), 1);
			add_action('wp_after_admin_bar_render', array($this, 'all_wp_hook_times'), 1);
			add_action('shutdown', array($this, 'all_wp_hook_times'), 1);
*/



















		}

		public function add_session_id_parameter($session_data){
			//Prototype Mode
			if ( nebula()->get_option('prototype_mode') ){
				$session_data['p'] = true;
			}

			return $session_data;
		}

		//Detect location from IP address using https://freegeoip.net/
		public function ip_location($data=null, $ip_address=false){
			if ( nebula()->get_option('ip_geo_api') ){
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
						$response = nebula()->remote_get('http://api.ipstack.com/' . $ip_address . '?access_key=' . nebula()->get_option('ip_geo_api') . '&format=1');
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
							case 'countryabbr':
								return $ip_geo_data->country_code;
								break;
							case 'flag':
								return $ip_geo_data->location->country_flag_emoji;
								break;
							case 'region':
							case 'state':
							case 'regionname':
							case 'statename':
								return $ip_geo_data->region_name;
								break;
							case 'regioncode':
							case 'statecode':
							case 'stateabbr':
								return $ip_geo_data->region_code;
								break;
							case 'city':
								return $ip_geo_data->city;
								break;
							case 'zip':
							case 'zipcode':
								return $ip_geo_data->zip;
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

		//Log provided POIs
		public function log_provided_poi($ip){
			$log_file = get_stylesheet_directory() . '/notable_pois.log';

			//Check if poi query string exists
			if ( isset($_GET['poi']) ){
				$ip_logged = file_put_contents($log_file, nebula()->get_ip_address() . ' ' . $_GET['poi'] . PHP_EOL, FILE_APPEND | LOCK_EX); //Log the notable POI. Can't use WP_Filesystem here.
				return str_replace(array('%20', '+'), ' ', $_GET['poi']);
			}
		}

		//Loop through Notable POIs log file (updated when using poi query parameter above).
		public function search_poi_logs($notable_pois){
			//Only use when manageable file size.
			if ( file_exists($log_file) && filesize($log_file) < 10000 ){ //If log file exists and is less than 10kb
				foreach ( array_unique(file($log_file)) as $line ){
					$ip_info = explode(' ', strip_tags($line), 2); //0 = IP Address or RegEx pattern, 1 = Name
					$notable_pois[] = array(
						'ip' => $ip_info[0],
						'name' => $ip_info[1]
					);
				}
			}

			return $notable_pois;
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

		//Automatically convert HEX colors to RGB.
		public function hex2rgb($color){
			$override = apply_filters('pre_hex2rgb', false, $color);
			if ( $override !== false ){return $override;}

			if ( $color[0] == '#' ){
				$color = substr($color, 1);
			}

			if ( strlen($color) == 6 ){
				list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
			} elseif ( strlen($color) == 3 ){
				list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
			} else {
				return false;
			}

			$r = hexdec($r);
			$g = hexdec($g);
			$b = hexdec($b);

			return array('r' => $r, 'g' => $g, 'b' => $b);
		}

		//Time all core WordPress hooks
		//@todo: Why isn't this getting called for all hooks?
		public function all_wp_hook_times(){
			//@todo: Set a "last time" variable to subtract against to figure out duration?
			$this->wp_hook_times['WP ' . current_filter()] = microtime(true);
		}

		//Additional finalized timings
		public function additional_final_timings($server_timings){

			//echo '<pre>' . var_export($this->wp_hook_times, true) . '</pre>';
			foreach ( $this->wp_hook_times as $hook => $time ){
				$server_timings[$hook] = array(
					'start' => -1,
					'end' => $time,
					'time' => $time-$_SERVER['REQUEST_TIME_FLOAT']
				);
			}

			//Before Nebula Companion Plugin
			$server_timings['Before Companion'] = array(
				'start' => $_SERVER['REQUEST_TIME_FLOAT'],
				'end' => $_SERVER['REQUEST_TIME_FLOAT']+$this->time_before_companion,
				'time' => $this->time_before_companion-$_SERVER['REQUEST_TIME_FLOAT']
			);

			//echo '<br><br><pre>' . var_export($server_timings, true) . '</pre>';

			return $server_timings;
		}

		//Load abandonment tracking
		//Note: Unlike ga_send_event() this function ignores session page count. These events are typically sent before the pageview.
		public function ga_track_load_abandons(){
			if ( nebula()->get_option('ga_load_abandon') && !nebula()->is_bot() && !is_customize_preview() ){
				$custom_metric_hitID = ( nebula()->get_option('cd_hitid') )? "'cd" . str_replace('dimension', '', nebula()->get_option('cd_hitid')) . "=" . nebula()->ga_generate_UUID() . "'," : ''; //Create the Measurement Protocol parameter for cd

				$common_parameters = '';
				foreach ( nebula()->ga_common_parameters() as $parameter => $value ){
					$common_parameters .= $parameter . '="' . $value . '",';
				}

				?>
				<script>
					document.addEventListener('visibilitychange', loadAbandonTracking);
					window.onbeforeunload = loadAbandonTracking;

					function loadAbandonTracking(e){
						if ( e.type === 'visibilitychange' && document.visibilityState === 'visible' ){
							return false;
						}

						//Remove listeners so this can only trigger once
						document.removeEventListener('visibilitychange', loadAbandonTracking);
						window.onbeforeunload = null;

						var loadAbandonLevel = 'Unload'; //Typically only desktop browsers trigger this event (sometimes)
						if ( e.type === 'visibilitychange' ){
							loadAbandonLevel = 'Visibility Change'; //This more accurately captures mobile browsers and the majority of abandon types
						}

						var newReturning = "<?php echo ( isset($_COOKIE['_ga']) )? 'Returning visitor or multiple pageview session' : 'New user or blocking Google Analytics cookie'; ?>";

						//Event
						navigator.sendBeacon && navigator.sendBeacon('https://www.google-analytics.com/collect', [
							<?php echo $common_parameters; ?>
							't=event', //Hit Type
							'ec=Load Abandon', //Event Category
							'ea=' + loadAbandonLevel, //Event Action
							'el=' + newReturning, //Event Label
							'ev=' + Math.round(performance.now()), //Event Value
							'ni=1', //Non-Interaction Hit
							<?php echo $custom_metric_hitID; //Unique Hit ID ?>
						].join('&'));

						//User Timing
						navigator.sendBeacon && navigator.sendBeacon('https://www.google-analytics.com/collect', [
							<?php echo $common_parameters; ?>
							't=timing', //Hit Type
							'utc=Load Abandon', //Timing Category
							'utv=' + loadAbandonLevel, //Timing Variable Name
							'utt=' + Math.round(performance.now()), //Timing Time (milliseconds)
							'utl=' + newReturning, //Timing Label
							<?php echo $custom_metric_hitID; //Unique Hit ID ?>
						].join('&'));
					}

					//Remove abandonment listeners on window load
					window.addEventListener('load', function(){
						document.removeEventListener('visibilitychange', loadAbandonTracking);
						if ( window.onbeforeunload === loadAbandonTracking ){
							window.onbeforeunload = null;
						}
					});
				</script>
				<?php
			}
		}

		//Additional Google Analytics detections
		public function analytics_before_pageview(){
			//Detect privacy mode
			if ( nebula()->get_option('cd_privacymode') ){
				?>
					var fileSystem = window.RequestFileSystem || window.webkitRequestFileSystem;
					if ( fileSystem ){
						fileSystem(
							window.TEMPORARY,
							100,
							function(){
								ga('set', nebula.analytics.dimensions.browseMode, 'Normal');
							},
							function(){
								ga('set', nebula.analytics.dimensions.browseMode, 'Private');
							}
						);
					}
				<?php
			}
		}

		//Visualize max scroll percent by adding ?max=16.12 to the URL
		public function visualize_scroll_percent(){
			if ( isset($_GET['max_scroll']) && nebula()->is_staff() ){
				?>
					<script>
						jQuery(window).on('load', function(){
							setTimeout(function(){
								scrollTop = jQuery(window).scrollTop();
								pageHeight = jQuery(document).height();
								viewportHeight = jQuery(window).height();
								var percentTop = ((pageHeight-viewportHeight)*<?php echo $_GET['max_scroll']; ?>)/100;
								var divHeight = pageHeight-percentTop;

								jQuery(window).on('scroll', function(){
									scrollTop = jQuery(window).scrollTop();
									var currentScrollPercent = ((scrollTop/(pageHeight-viewportHeight))*100).toFixed(2);
								    console.log('Current Scroll Percent: ' + currentScrollPercent + '%');
								});

								jQuery('<div style="display: none; position: absolute; top: ' + percentTop + 'px; left: 0; width: 100%; height: ' + divHeight + 'px; border-top: 2px solid orange; background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.8) ' + viewportHeight + 'px); z-index: 999999; pointer-events: none; overflow: hidden;"><div style="position: absolute; top: ' + viewportHeight + 'px; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); border-top: 2px solid red;"></div></div>').appendTo('body').fadeIn();
							}, 500);
						});
					</script>
				<?php
			}
		}

		//Start Audit Mode expiration transient when options saved
		public function start_audit_mode(){
			if ( nebula()->get_option('audit_mode') ){
				set_transient('nebula_audit_mode_expiration', time(), HOUR_IN_SECONDS);
			}
		}

		//Audit Mode front-end output
		public function audit_mode_output(){
			if ( (nebula()->get_option('audit_mode') || (isset($_GET['audit']) && nebula()->is_dev())) && !nebula()->is_admin_page() ){
				//Automatically disable this option 1 hour after last usage
				$nebula_audit_mode_expiration = get_transient('nebula_audit_mode_expiration');
				if ( empty($nebula_audit_mode_expiration) ){
					nebula()->update_option('audit_mode', 0); //Disable audit mode
				} else {
					if ( !isset($_GET['audit']) && current_user_can('manage_options') || nebula()->is_dev() ){ //If admin or dev user
						set_transient('nebula_audit_mode_expiration', time(), HOUR_IN_SECONDS); //Extend audit mode to expire 1 hour from now (only if not using temporary "audit" query string)
					}
				}

				$nebula_warnings = json_encode(nebula()->check_warnings());
				?>
					<style>
						.nebula-audit .audit-desc {position: absolute; bottom: 0; right: 0; color: #fff; background: grey; font-size: 10px; padding: 3px 5px; z-index: 9999;}
							.nebula-audit .nebula-audit .audit-desc {right: auto; left: 0; top: 0; bottom: auto;}
								.nebula-audit .nebula-audit .nebula-audit .audit-desc {right: auto; left: 0; bottom: 0; top: auto;}
									.nebula-audit .nebula-audit .nebula-audit .nebula-audit .audit-desc {right: 0; left: auto; bottom: auto; top: 0;}
						.audit-error {position: relative; border: 2px solid red;}
							.audit-error .audit-desc {background: red;}
						.audit-warn {position: relative; border: 2px solid orange;}
							.audit-warn .audit-desc {background: orange;}
						.audit-notice {position: relative; border: 2px solid blue;}
							.audit-notice .audit-desc {background: blue;}
						#audit-results {position: relative; background: #444; color: #fff; padding: 50px;}
							#audit-results p {color: #fff;}
							#audit-results a {color: #0098d7;}
								#audit-results a:hover {color: #95d600;}
					</style>
					<script>
						jQuery(window).on('load', function(){
							setTimeout(function(){
								jQuery('body').append(jQuery('<div id="audit-results"><p><strong>Nebula Audit Results:</strong></p><ul></ul></div>'));

								//Reporting Observer deprecations and interventions
								if ( typeof window.ReportingObserver !== undefined ){ //Chrome 68+
									var nebulaAuditModeReportingObserver = new ReportingObserver(function(reports, observer){
										for ( report of reports ){
											if ( report.body.sourceFile.indexOf('extension') < 0 ){ //Ignore browser extensions
												jQuery("#audit-results ul").append('<li>Reporting Observer (' + report.type + '): ' + report.body.message + ' in ' + report.body.sourceFile + ' on line ' + report.body.lineNumber + '</li>');
											}
										}
									}, {buffered: true});
									nebulaAuditModeReportingObserver.observe();
								}

								//@todo: consider checking WebPageTest timing if API key is available

								//Check protocol
								if ( window.location.href.indexOf('http://') === 0 ){
									jQuery("#audit-results ul").append('<li>Non-secure http protocol</li>');
								} else if ( window.location.href.indexOf('https://') === 0 ){
									//check for non-secure resource requests here?
								}

								//Empty meta description
								if ( !jQuery('meta[name="description"]').length ){
									jQuery("#audit-results ul").append('<li>Missing meta description</li>');
								} else {
									if ( !jQuery('meta[name="description"]').attr('content').length ){
										jQuery("#audit-results ul").append('<li>Meta description tag exists but is empty</li>');
									} else {
										if ( jQuery('meta[name="description"]').attr('content').length < 60 ){
											jQuery("#audit-results ul").append('<li>Short meta description</li>');
										}
									}
								}

								//Check title
								if ( !document.title.length ){
									jQuery("#audit-results ul").append('<li>Missing page title</li>');
								} else {
									if ( document.title.length < 25 ){
										jQuery("#audit-results ul").append('<li>Short page title</li>');
									}

									if ( document.title.indexOf('Home') > -1 ){
										jQuery("#audit-results ul").append('<li>Improve page title keywords (remove "Home")</li>');
									}
								}

								//Check H1
								if ( !jQuery('h1').length ){
									jQuery("#audit-results ul").append('<li>Missing H1 tag</li>');

									if ( jQuery('h1').length > 1 ){
										jQuery("#audit-results ul").append('<li>Too many H1 tags</li>');
									}
								}

								//Check H2
								if ( !jQuery('h2').length ){
									jQuery("#audit-results ul").append('<li>Missing H2 tag</li>');
								} else if ( jQuery('h2') <= 2 ){
									jQuery("#audit-results ul").append('<li>Very few H2 tags</li>');
								}

								//Check that each <article> has a heading tag
								jQuery('article').each(function(){
									if ( !jQuery(this).find('h1, h2, h3, h4, h5, h6').length ){
										jQuery(this).addClass('nebula-audit audit-warn').append(jQuery('<div class="audit-desc">Missing heading tag in this article</div>'));
										jQuery("#audit-results ul").append('<li>Mising heading tag within an &lt;article&gt; tag.</li>');
									}
								});

								//Check for placeholder text (in the page content and metadata)
								var entireDOM = jQuery('html').clone();
								entireDOM.find('#qm, #wpadminbar, script, #audit-results').remove(); //Remove elements to ignore (must ignore scripts so this audit doesn't find itself)
								var commonPlaceholderWords = ['lorem', 'ipsum', 'dolor', 'amet', 'consectetur', 'adipiscing', 'elit'];
								jQuery.each(commonPlaceholderWords, function(i, word){
									if ( entireDOM.html().indexOf(word) > -1 ){
										jQuery("#audit-results ul").append('<li>Placeholder text found.</li>');
										return false;
									}
								});

								//Broken images
								jQuery('img').on('error', function(){
									if ( jQuery(this).parents('#wpadminbar').length ){
										return false;
									}

									jQuery(this).addClass('nebula-audit audit-error').append(jQuery('<div class="audit-desc">Broken image</div>'));
									jQuery("#audit-results ul").append('<li>Broken image</li>');
								});

								//Check img alt
								jQuery('img:not([alt]), img[alt=""]').each(function(){
									if ( jQuery(this).parents('#wpadminbar, iframe, #map_canvas').length ){
										return false;
									}

									jQuery(this).wrap('<div class="nebula-audit audit-error"></div>').after('<div class="audit-desc">Missing ALT attribute</div>');
									jQuery("#audit-results ul").append('<li>Missing ALT attribute</li>');
								});

								//Images
								jQuery('img').each(function(){
									if ( jQuery(this).parents('#wpadminbar, iframe, #map_canvas').length ){
										return false;
									}

									//Check image filesize. Note: cached files are 0
									if ( window.performance ){ //IE10+
										var iTime = performance.getEntriesByName(jQuery(this).attr('src'))[0];
										if ( iTime && iTime.transferSize >= 500000 ){
											jQuery(this).wrap('<div class="nebula-audit audit-warn"></div>').after('<div class="audit-desc">Image filesize over 500kb</div>');
											jQuery("#audit-results ul").append('<li>Image filesize over 500kb</li>');
										}
									}

									//Check image width
									if ( jQuery(this)[0].naturalWidth > 1200 ){
										jQuery(this).wrap('<div class="nebula-audit audit-warn"></div>').after('<div class="audit-desc">Image wider than 1200px</div>');
										jQuery("#audit-results ul").append('<li>Image wider than 1200px</li>');
									}

									//Check image link
									if ( !jQuery(this).parents('a').length ){
										jQuery(this).wrap('<div class="nebula-audit audit-notice"></div>').after('<div class="audit-desc">Unlinked Image</div>');
										jQuery("#audit-results ul").append('<li>Unlinked image</li>');
									}
								});

								//Videos
								jQuery('video').each(function(){
									//Check video filesize. Note: cached files are 0
									if ( window.performance ){ //IE10+
										var vTime = performance.getEntriesByName(jQuery(this).find('source').attr('src'))[0];

										if ( vTime && vTime.transferSize >= 5000000 ){ //5mb+
											jQuery(this).wrap('<div class="nebula-audit audit-warn"></div>').after('<div class="audit-desc">Video filesize over 5mb</div>');
											jQuery("#audit-results ul").append('<li>Video filesize over 5mb</li>');
										}
									}

									//Check unmuted autoplay
									if ( jQuery(this).is('[autoplay]') && !jQuery(this).is('[muted]') ){
										jQuery(this).wrap('<div class="nebula-audit audit-warn"></div>').after('<div class="audit-desc">Autoplay without muted attribute</div>');
										jQuery("#audit-results ul").append('<li>Videos set to autoplay without being muted will not autoplay in Chrome.</li>');
									}
								});

								//Check Form Fields
								jQuery('form').each(function(){
									if ( jQuery(this).find('input[name=s]').length ){
										return false;
									}

									if ( jQuery(this).parents('#wpadminbar, iframe').length ){
										return false;
									}

									var formFieldCount = 0;
									jQuery(this).find('input:visible, textarea:visible, select:visible').each(function(){
										formFieldCount++;
									});

									if ( formFieldCount > 6 ){
										jQuery(this).wrap('<div class="nebula-audit audit-notice"></div>').after('<div class="audit-desc">Many form fields</div>');
										jQuery("#audit-results ul").append('<li>Many form fields</li>');
									}
								});

								//Check for modals inside of #body-wrapper
								if ( jQuery('#body-wrapper .modal').length ){
									jQuery("#audit-results ul").append('<li>Modal found inside of #body-wrapper. Move modals to the footer outside of the #body-wrapper div.</li>');
								}

								var nebulaWarnings = <?php echo $nebula_warnings; ?> || {};
								jQuery.each(nebulaWarnings, function(i, warning){
									if ( warning.description.indexOf('Audit Mode') > 0 ){
										return true; //Skip
									}
									jQuery("#audit-results ul").append('<li>' + warning.description + '</li>');
								});

								<?php if ( !(is_home() || is_front_page()) ): ?>
									//Check breadcrumb schema tag
									if ( !jQuery('[itemtype*=BreadcrumbList]').length ){
										jQuery("#audit-results ul").append('<li>Missing breadcrumb schema tag</li>');
									}
								<?php endif; ?>

								//Check issue count (do this last)
								if ( jQuery("#audit-results ul li").length <= 0 ){
									jQuery("#audit-results").append('<p><strong><i class="fas fa-fw fa-check"></i> No issues were found on this page.</strong> Be sure to check other pages (and run <a href="https://gearside.com/nebula/get-started/checklists/testing-checklist/" target="_blank">more authoritative tests</a>)!</p>');
								} else {
									jQuery("#audit-results").append('<p><strong><i class="fas fa-fw fa-times"></i> Found issues: ' + jQuery("#audit-results ul li").length + '<strong></p>');
								}
								jQuery("#audit-results").append('<p><small>Note: This does not check for @todo comments. Use the Nebula To-Do Manager in the WordPress admin dashboard to view.</small></p>');
							}, 1);
						});
					</script>
				<?php
			}
		}

	}
}