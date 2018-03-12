<?php

//Exit if accessed directly
if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Dashboard') ){
	trait Companion_Dashboard {
		public function hooks(){
			add_action('wp_dashboard_setup', array($this, 'github_metabox'));
			add_action('nebula_user_metabox', array($this, 'more_user_dashboard_data'));
			add_action('nebula_dev_dashboard_directories', array($this, 'more_directory_sizes'));
			add_filter('nebula_directory_search_options', array($this, 'search_prototype_directories'));
			add_filter('nebula_search_directories', array($this, 'add_prototyping_search_directories'));
		}

		//Add a Github metabox for recently updated issues
		public function github_metabox(){
			$repo_name = str_replace('https://github.com/', '', nebula()->get_option('github_url'));

			global $wp_meta_boxes;
			wp_add_dashboard_widget('nebula_github', '<i class="fab fa-fw fa-github"></i>&nbsp;' . $repo_name, array($this, 'dashboard_nebula_github'));
		}

		public function dashboard_nebula_github(){
			$client_id = ''; //get this from Advanced nebula options
			$client_secret = ''; //get this from Advanced nebula options
			if ( !empty($client_id) && !empty($client_secret) ){
				$url = add_query_arg(array(
					'client_id' => $client_id,
					'client_secret' => $client_secret
				), $url);
			}

			$repo_name = str_replace('https://github.com/', '', nebula()->get_option('github_url'));

			//Commits
			$github_commit_json = get_transient('nebula_github_commits');
			if ( empty($github_commit_json) || nebula()->is_debug() ){
				$response = nebula()->remote_get('https://api.github.com/repos/' . $repo_name . '/commits');

				//$response = nebula()->remote_get('https://api.github.com/repos/PinckneyHugoGroup/Dwyer-Strategy/issues?sort=updated'); //delete this

				if ( is_wp_error($response) ){
			        echo '<p>There was an error retrieving the Github commits...</p>';
			        return false;
			    }

			    $github_commit_json = $response['body'];
				set_transient('nebula_github_commits', $github_commit_json, MINUTE_IN_SECONDS*60); //60 minute expiration
			}

			$commits = json_decode($github_commit_json);

			if ( !empty($commits->message) ){
				echo '<p><strong>This repo was not found.</strong><br />If this is a private repo, the <strong>Client ID</strong> and <strong>Client Secret</strong> from your Github app must be added in <a href="themes.php?page=nebula_options&tab=functions&option=comments">Nebula Options</a> to retrieve issues.</p>'; //@todo: update the option
				return false;
			}

			echo '<p><strong>Latest Commit</strong></p>';

			//https://developer.github.com/v3/repos/commits/
			$commit_date_time = strtotime($commits[0]->commit->committer->date);
			$commit_date_icon = ( date('Y-m-d', $commit_date_time) === date('Y-m-d') )? 'fa-clock' : 'fa-calendar';
			echo '<p>
				<i class="far fa-fw ' . $commit_date_icon . '"></i> <a href="' . $commits[0]->html_url . '" target="_blank" title="' . date('F j, Y @ g:ia', $commit_date_time) . '">' . human_time_diff($commit_date_time) . ' ago</a> <small>by ' . $commits[0]->commit->committer->name . '</small><br />
				<small style="display: block;">' . nebula()->excerpt(array('text' => $commits[0]->commit->message, 'words' => 15, 'ellipsis' => true, 'more' => false)) . '</small>
			</p>';

			//Issues
			echo '<p><strong>Recently Updated Issues</strong></p>';

			$github_issues_json = get_transient('nebula_github_issues');
			if ( empty($github_issues_json) || nebula()->is_debug() ){
				$response = nebula()->remote_get('https://api.github.com/repos/' . $repo_name . '/issues?sort=updated');

				//$response = nebula()->remote_get('https://api.github.com/repos/PinckneyHugoGroup/Dwyer-Strategy/issues?sort=updated'); //delete this

				if ( is_wp_error($response) ){
			        echo '<p>There was an error retrieving the Github issues...</p>';
			        return false;
			    }

			    $github_issues_json = $response['body'];
				set_transient('nebula_github_issues', $github_issues_json, MINUTE_IN_SECONDS*15); //15 minute expiration
			}

			$issues = json_decode($github_issues_json);
			$issue_count = 0;

			//https://developer.github.com/v3/issues/
			if ( !empty($issues) ){
				echo '<ul>';
				foreach ( $issues as $issue ){
					$issue_date_time = strtotime($issue->updated_at);
					$issue_date_icon = ( date('Y-m-d', $issue_date_time) === date('Y-m-d') )? 'fa-clock' : 'fa-calendar';

					echo '<li>
						<p>
							<a href="' . $issue->html_url . '" target="_blank">' . $issue->title . '</a><br />
							<small><i class="far fa-fw ' . $issue_date_icon . '"></i> <span title="' . date('F j, Y @ g:ia', $issue_date_time) . '">' . human_time_diff($issue_date_time) . ' ago</span></small>
						</p>
					</li>';

					$issue_count++;
					if ( $issue_count >= 3 ){
						break;
					}
				}
				echo '</ul>';
			} else {
				echo '<p>No issues found.</p>';
			}

			echo '<p><small><a href="' . nebula()->get_option('github_url') . '/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc" target="_blank">View all issues &raquo;</a></small></p>';
		}

		//Add more data to the user dashboard
		public function more_user_dashboard_data(){
			//IP Location
			if ( $this->ip_location() ){
				$ip_location = $this->ip_location('all');

				if ( !empty($ip_location) ){
					echo '<li><i class="fas fa-fw fa-location-arrow"></i> IP Location: <i class="flag flag-' . strtolower($ip_location->country_code) . '"></i> <strong>' . $ip_location->city . ', ' . $ip_location->region_name . '</strong></li>';
				} else {
					echo '<li><i class="fas fa-fw fa-location-arrow"></i> IP Location: <em>GeoIP error or rate limit exceeded.</em></li>';
				}
			}

			//Weather
			if ( nebula()->get_option('weather') ){
				$ip_zip = ( $this->ip_location() )? $this->ip_location('zip') : '';
				$temperature = nebula_companion()->weather($ip_zip, 'temp');
				if ( !empty($temperature) ){
					echo '<li><i class="fas fa-fw fa-cloud"></i> Weather: <strong>' . $temperature . '&deg;F ' . $this->weather($ip_zip, 'conditions') . '</strong></li>';
				} else {
					echo '<li><i class="fas fa-fw fa-cloud"></i> Weather: <em>API error for zip code ' . $ip_zip . '.</em></li>';
				}
			}
		}

		public function more_directory_sizes(){
			if ( nebula()->get_option('prototype_mode') ){
				if ( nebula()->get_option('wireframe_theme') ){
					$nebula_wireframe_size = nebula()->foldersize(get_theme_root() . '/' . nebula()->get_option('wireframe_theme'));
					echo '<li title="' . nebula()->get_option('wireframe_theme') . '"><i class="fas fa-flag"></i> Wireframe directory size: <strong>' . round($nebula_wireframe_size/1048576, 2) . 'mb</strong> </li>';
				}

				if ( nebula()->get_option('staging_theme') ){
					$nebula_staging_size = nebula()->foldersize(get_theme_root() . '/' . nebula()->get_option('staging_theme'));
					echo '<li title="' . nebula()->get_option('staging_theme') . '"><i class="fas fa-flag"></i> Staging directory size: <strong>' . round($nebula_staging_size/1048576, 2) . 'mb</strong> </li>';
				}
			}
		}

		public function search_prototype_directories($directory_search_options){
			//Add prototype themes to directory search options
			if ( nebula()->get_option('prototype_mode') ){
				unset($directory_search_options['child']);
				unset($directory_search_options['theme']);

				$directory_search_options['production'] = '<option value="production">Production</option>';

				if ( nebula()->get_option('staging_theme') ){
					$directory_search_options['staging'] = '<option value="staging">Staging</option>';
				}

				if ( nebula()->get_option('wireframe_theme') ){
					$directory_search_options['wireframe'] = '<option value="wireframe">Wireframe</option>';
				}
			}

			return $directory_search_options;
		}

		public function add_prototyping_search_directories($search_directories){
			//Add prototype themes to directory search
			if ( nebula()->get_option('prototype_mode') ){
				$search_directories['wireframe'] = get_theme_root() . '/' . nebula()->get_option('wireframe_theme');
				$search_directories['staging'] = get_theme_root() . '/' . nebula()->get_option('staging_theme');
				if ( $this->get_option('production_theme') ){
					$search_directories['production'] = get_theme_root() . '/' . nebula()->get_option('production_theme');
				} else {
					$search_directories['production'] = get_stylesheet_directory();
				}
			}

			return $search_directories;
		}
	}
}
