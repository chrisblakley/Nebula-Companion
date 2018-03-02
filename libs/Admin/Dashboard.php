<?php

//Exit if accessed directly
if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

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
				<small style="display: block;">' . $commits[0]->commit->message . '</small>
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
	}
}
