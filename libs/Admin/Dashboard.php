<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Dashboard') ){
	trait Companion_Dashboard {
		public function hooks(){
			if ( current_user_can('edit_others_posts') && !nebula()->is_background_request() ){
				add_action('wp_dashboard_setup', array($this, 'github_metabox'));
			}
		}

		//Add a GitHub metabox for recently updated issues/discussions
		public function github_metabox(){
			if ( nebula()->get_option('github_url') && nebula()->get_option('github_pat') ){
				$repo_name = str_replace('https://github.com/', '', nebula()->get_option('github_url'));
				global $wp_meta_boxes;
				wp_add_dashboard_widget('nebula_github', '<i class="fab fa-fw fa-github"></i>&nbsp;' . $repo_name, array($this, 'dashboard_nebula_github'));
			}
		}

		public function dashboard_nebula_github(){
			nebula()->timer('Nebula Companion GitHub Dashboard');
			echo '<p><a href="' . nebula()->get_option('github_url') . '" target="_blank">GitHub Repository &raquo;</a></p>';

			$repo_name = str_replace('https://github.com/', '', nebula()->get_option('github_url'));
			$github_personal_access_token = nebula()->get_option('github_pat');

			//Commits
			$github_commit_json = get_transient('nebula_github_commits');
			if ( empty($github_commit_json) || nebula()->is_debug() ){
				$commits_response = nebula()->remote_get('https://api.github.com/repos/' . $repo_name . '/commits', array(
					'headers' => array(
						'Authorization' => 'token ' . $github_personal_access_token,
					)
				));

				if ( is_wp_error($commits_response) ){
			        echo '<p>There was an error retrieving the GitHub commits...</p>';
			        return false;
			    }

			    $github_commit_json = $commits_response['body'];
				set_transient('nebula_github_commits', $github_commit_json, HOUR_IN_SECONDS*3); //3 hour expiration
			}

			$commits = json_decode($github_commit_json);

			if ( !empty($commits->message) ){
				?>
					<p>
						<strong>This repo is not available.</strong><br />
						If this is a private repo, the <strong>Client ID</strong> and <strong>Client Secret</strong> from your GitHub app must be added in <a href="themes.php?page=nebula_options&tab=functions&option=comments">Nebula Options</a> to retrieve issues.
					</p>
					<p>
						<a href="<?php echo nebula()->get_option('github_url'); ?>/commits/main" target="_blank">Commits &raquo;</a><br />
						<a href="<?php echo nebula()->get_option('github_url'); ?>/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc" target="_blank">Issues &raquo;</a><br />
					</p>
				<?php
				return false;
			}

			echo '<div class="nebula-metabox-row"><div class="nebula-metabox-col">';
			echo '<strong>Latest Commits</strong><br />';

			//https://developer.github.com/v3/repos/commits/
			for ( $i=0; $i <= 2; $i++ ){ //Get 3 commits
				$commit_date_time = strtotime($commits[$i]->commit->committer->date);
				$commit_date_icon = ( date('Y-m-d', $commit_date_time) === date('Y-m-d') )? 'fa-clock' : 'fa-calendar';
				echo '<p>
					<i class="far fa-fw ' . $commit_date_icon . '"></i> <a href="' . $commits[$i]->html_url . '" target="_blank" title="' . date('F j, Y @ g:ia', $commit_date_time) . '">' . human_time_diff($commit_date_time) . ' ago</a><br />
					<small style="display: block;">' . nebula()->excerpt(array('text' => $commits[$i]->commit->message, 'words' => 15, 'ellipsis' => true, 'more' => false)) . '</small>
				</p>';
			}

			echo '<p><small><a href="' . nebula()->get_option('github_url') . '/commits/main" target="_blank">View all commits &raquo;</a></small></p>';
			echo '</div>';

			//Issues and Discussions
			echo '<div class="nebula-metabox-col">';
			echo '<strong>Recent Issues, Pull Requests, &amp; Discussions</strong><br />';

			$github_combined_posts = get_transient('nebula_github_posts');
			if ( empty($github_combined_posts) || nebula()->is_debug() ){
				//Get the Issues first https://developer.github.com/v3/issues/
				//Note: The Issues endpoint also returns pull requests (which is fine because we want that)
				$issues_response = nebula()->remote_get('https://api.github.com/repos/' . $repo_name . '/issues?state=open&sort=updated&direction=desc&per_page=3', array(
					'headers' => array(
						'Authorization' => 'token ' . $github_personal_access_token,
					)
				));

				if ( is_wp_error($issues_response) ){
			        echo '<p>There was an error retrieving the GitHub issues...</p>';
			        return false;
			    }

			    $github_issues_json = json_decode($issues_response['body']);

				//Get the Discussions next
				//GraphQL API is available, but webhooks not ready yet per (Feb 2021): https://github.com/github/feedback/discussions/43
				// $discussions_response = nebula()->remote_get('https://api.github.com/repos/' . $repo_name . '/discussions?sort=updated&direction=desc&per_page=3', array(
				// 	'headers' => array(
				// 		'Authorization' => 'token ' . $github_personal_access_token,
				// 	)
				// ));

				//if ( is_wp_error($discussions_response) ){
					$discussions_response = array('body' => '{}'); //Ignore discussions errors
				//}

				$github_discussions_json = json_decode($discussions_response['body']);


				//Then combine the issues and discussions by most recent first
				$github_combined_posts = json_encode($github_issues_json); //Replace this when discussions api is available

				set_transient('nebula_github_posts', $github_combined_posts, MINUTE_IN_SECONDS*30); //30 minute expiration
			}

			$github_combined_posts = json_decode($github_combined_posts);

			if ( !empty($github_combined_posts) ){
				echo '<ul>';
				for ( $i=0; $i <= 2; $i++ ){ //Get 3 issues
					$github_post_type = 'Unknown';
					if ( strpos($github_combined_posts[$i]->html_url, 'issue') > 0 ){
						$github_post_type = 'Issue';
					} elseif ( strpos($github_combined_posts[$i]->html_url, 'pull') > 0 ){
						$github_post_type = 'Pull Request';
					} elseif ( strpos($github_combined_posts[$i]->html_url, 'discussion') > 0 ){
						$github_post_type = 'Discussion';
					}

					$github_post_date_time = strtotime($github_combined_posts[$i]->updated_at);
					$github_post_date_icon = ( date('Y-m-d', $github_post_date_time) === date('Y-m-d') )? 'fa-clock' : 'fa-calendar';

					echo '<li>
						<p>
							<a href="' . $github_combined_posts[$i]->html_url . '" target="_blank">' . htmlentities($github_combined_posts[$i]->title) . '</a><br />
							<small><i class="far fa-fw ' . $github_post_date_icon . '"></i> <span title="' . date('F j, Y @ g:ia', $github_post_date_time) . '">' . $github_post_type . ' updated ' . human_time_diff($github_post_date_time) . ' ago</span></small>
						</p>
					</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>No issues or discussions found.</p>';
			}

			echo '<p><small>View all <a href="' . nebula()->get_option('github_url') . '/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc" target="_blank">issues</a>, <a href="' . nebula()->get_option('github_url') . '/pulls?q=is%3Apr+is%3Aopen+sort%3Aupdated-desc" target="_blank">pull requests</a>, or <a href="' . nebula()->get_option('github_url') . '/discussions" target="_blank">discussions &raquo;</a></small></p>';
			echo '</div></div>';
			nebula()->timer('Nebula Companion GitHub Dashboard', 'end');
		}
	}
}