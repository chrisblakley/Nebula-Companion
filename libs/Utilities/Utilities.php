<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Utilities') ){
	require_once plugin_dir_path(__FILE__) . 'Sass.php';

	trait Companion_Utilities {
		use Companion_Sass { Companion_Sass::hooks as Companion_SassHooks;}

		public function hooks(){
			$this->Companion_SassHooks(); //Register Sass hooks

			add_filter('nebula_session_id', array($this, 'add_session_id_parameter'));
			//$this->Companion_DeviceHooks(); //Register Device hooks
			add_action('wp_footer', array($this, 'visualize_scroll_percent'));

			add_filter('nebula_finalize_timings', array($this, 'additional_final_timings'));

			add_action('nebula_head_open', array($this, 'ga_track_load_abandons')); //This is the earliest anything can be placed in the <head>

			add_action('nebula_options_saved', array($this, 'start_audit_mode'));
			add_action('wp_footer', array($this, 'extend_audit_mode'), 9999); //Late execution as possible

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

		//Detect Notable POI
		public function poi($ip='detect'){
			$timer_name = nebula()->timer('POI Detection', 'start', 'Nebula POI');

			if ( is_null($ip) ){
				return false;
			}

			//Check object cache first
			$poi_match = wp_cache_get('nebula_poi', str_replace('.', '_', $ip));
			if ( !empty($poi_match) ){
				return $poi_match;
			}

			//Allow for other themes/plugins to provide additional detections
			$additional_detections = apply_filters('nebula_poi', $ip);
			if ( !empty($additional_detections) ){
				return $additional_detections;
			}

			if ( nebula()->get_option('notableiplist') ){
				if ( $ip === 'detect' ){
					$ip = nebula()->get_ip_address();
				}

				//Loop through Notable POIs saved in Nebula Options
				$notable_pois = array();
				$notable_ip_lines = explode("\n", esc_html(nebula()->get_option('notableiplist')));

				if ( !empty($notable_ip_lines) ){
					foreach ( $notable_ip_lines as $line ){
						if ( !empty($line) ){
							$ip_info = explode(' ', strip_tags($line), 2); //0 = IP Address or RegEx pattern, 1 = Name
							$notable_pois[] = array(
								'ip' => $ip_info[0],
								'name' => $ip_info[1]
							);
						}
					}
				}

				$all_notable_pois = apply_filters('nebula_notable_pois', $notable_pois);
				$all_notable_pois = array_map("unserialize", array_unique(array_map("serialize", $all_notable_pois))); //De-dupe multidimensional array
				$all_notable_pois = array_filter($all_notable_pois); //Remove empty array elements

				//Finally, loop through all notable POIs to return a match
				if ( !empty($all_notable_pois) ){
					foreach ( $all_notable_pois as $notable_poi ){
						//Check for RegEx
						if ( $notable_poi['ip'][0] === '/' && preg_match($notable_poi['ip'], $ip) ){ //If first character of IP is "/" and the requested IP matches the pattern
							$poi_match = str_replace(array("\r\n", "\r", "\n"), '', $notable_poi['name']);
							wp_cache_set('nebula_poi', $poi_match, str_replace('.', '_', $ip)); //Store in object cache grouped by the IP address
							nebula()->timer($timer_name, 'end');
							return $poi_match;
						}

						//Check direct match
						if ( $notable_poi['ip'] === $ip ){
							$poi_match = str_replace(array("\r\n", "\r", "\n"), '', $notable_poi['name']);
							wp_cache_set('nebula_poi', $poi_match, str_replace('.', '_', $ip)); //Store in object cache grouped by the IP address
							nebula()->timer($timer_name, 'end');
							return $poi_match;
						}
					}

					wp_cache_set('nebula_poi', false, str_replace('.', '_', $ip)); //Store in object cache grouped by the IP address
				}
			}

			nebula()->timer($timer_name, 'end');
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

		//Visualize max scroll percent by adding ?max_scroll=16.12 to the URL
		public function visualize_scroll_percent(){
			if ( isset($_GET['max_scroll']) && nebula()->is_staff() ){
				?>
					<style>
						.nebula-scroll-visual:before {display: inline-block; position: absolute; top: 0; left: 0; font-size: 10px; line-height: 1.4; color: #fff; padding: 2px 6px;}
						.nebula-scroll-seen:before {content: "Top of viewport"; background: orange;}
						.nebula-scroll-unseen:before {content: "Bottom of viewport"; background: red;}
					</style>
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
								    console.log('Current Scroll Percent: ' + currentScrollPercent + '%'); //It would be cool to monitor this variable in Chrome DevTools, but I don't think we can initiate that here.
								});

								jQuery('<div id="nebula-scroll-visualization" class="nebula-scroll-visual nebula-scroll-seen" style="display: none; position: absolute; top: ' + percentTop + 'px; left: 0; width: 100%; height: ' + divHeight + 'px; border-top: 2px solid orange; background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.8) ' + viewportHeight + 'px); z-index: 999999; pointer-events: none; overflow: hidden;"><div class="nebula-scroll-visual nebula-scroll-unseen" style="position: absolute; top: ' + viewportHeight + 'px; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); border-top: 2px solid red;"></div></div>').appendTo('body').fadeIn();
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
				nebula()->add_log('Nebula Audit Mode enabled for 1 hour', 2);
			}
		}

		//Audit Mode front-end output
		public function extend_audit_mode(){
			if ( nebula()->is_auditing() ){
				//Automatically disable this option 1 hour after last usage
				$nebula_audit_mode_expiration = get_transient('nebula_audit_mode_expiration');
				if ( empty($nebula_audit_mode_expiration) ){
					nebula()->update_option('audit_mode', 0); //Disable audit mode
				} else {
					if ( !isset($_GET['audit']) && current_user_can('manage_options') || nebula()->is_dev() ){ //If admin or dev user
						set_transient('nebula_audit_mode_expiration', time(), HOUR_IN_SECONDS); //Extend audit mode to expire 1 hour from now (only if not using temporary "audit" query string)
					}
				}
			}
		}

	}
}