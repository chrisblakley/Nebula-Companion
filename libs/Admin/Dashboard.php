<?php

//Exit if accessed directly
//if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Dashboard') ){
	trait Companion_Dashboard {
		public function hooks(){
			add_action('wp_dashboard_setup', array($this, 'github_metabox'));
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

			$github_issues_json = get_transient('nebula_github_issues');
			if ( empty($github_issues_json) || nebula()->is_debug() ){
				$repo_name = str_replace('https://github.com/', '', nebula()->get_option('github_url'));
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

			if ( !empty($issues->message) ){
				echo '<p><strong>This repo was not found.</strong><br />If this is a private repo, the <strong>Client ID</strong> and <strong>Client Secret</strong> from your Github app must be added in <a href="themes.php?page=nebula_options&tab=functions&option=comments">Nebula Options</a> to retrieve issues.</p>'; //@todo: update the option
				return false;
			}

			echo '<p><strong>Latest Issues</strong></p>';
			$issue_count = 0;

			echo '<ul>';
			foreach ( $issues as $issue ){
				$date_time = strtotime($issue->updated_at);
				$icon = ( date('Y-m-d', $date_time) === date('Y-m-d') )? 'fa-clock' : 'fa-calendar';

				echo '<li>
					<p>
						<a href="' . $issue->html_url . '" target="_blank">' . $issue->title . '</a><br />
						<small title="' . date('F j, Y @ g:ia', $date_time) . '"><i class="far fa-fw ' . $icon . '"></i> ' . human_time_diff($date_time) . ' ago</small>
					</p>
				</li>';

				$issue_count++;
				if ( $issue_count >= 5 ){
					break;
				}
			}
			echo '</ul>';

			echo '<p><small><a href="' . nebula()->get_option('github_url') . '/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc" target="_blank">View all issues &raquo;</a></small></p>';
		}
	}
}


