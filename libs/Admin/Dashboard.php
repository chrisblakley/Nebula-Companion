<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Companion_Dashboard') ){
	trait Companion_Dashboard {
		public function hooks(){
			add_action('wp_dashboard_setup', array($this, 'github_metabox'));
			add_action('nebula_dev_dashboard_directories', array($this, 'more_directory_sizes'));
			add_filter('nebula_directory_search_options', array($this, 'search_prototype_directories'));
			add_filter('nebula_search_directories', array($this, 'add_prototyping_search_directories'));
		}

		//Add a Github metabox for recently updated issues
		public function github_metabox(){
			if ( nebula()->get_option('github_url') ){
				$repo_name = str_replace('https://github.com/', '', nebula()->get_option('github_url'));
				global $wp_meta_boxes;
				wp_add_dashboard_widget('nebula_github', '<i class="fab fa-fw fa-github"></i>&nbsp;' . $repo_name, array($this, 'dashboard_nebula_github'));
			}
		}

		public function dashboard_nebula_github(){
			nebula()->timer('Nebula Companion Github Dashboard');

			$client_id = ''; //@todo: get this from Advanced nebula options
			$client_secret = ''; //@todo: get this from Advanced nebula options
			if ( !empty($client_id) && !empty($client_secret) ){
				$url = add_query_arg(array(
					'client_id' => $client_id,
					'client_secret' => $client_secret
				), $url);
			}

			echo '<p><a href="' . nebula()->get_option('github_url') . '" target="_blank">Github Repository &raquo;</a></p>';

			$repo_name = str_replace('https://github.com/', '', nebula()->get_option('github_url'));

			//Commits
			$github_commit_json = get_transient('nebula_github_commits');
			if ( empty($github_commit_json) || nebula()->is_debug() ){
				$response = nebula()->remote_get('https://api.github.com/repos/' . $repo_name . '/commits');
				if ( is_wp_error($response) ){
			        echo '<p>There was an error retrieving the Github commits...</p>';
			        return false;
			    }

			    $github_commit_json = $response['body'];
				set_transient('nebula_github_commits', $github_commit_json, HOUR_IN_SECONDS*3); //3 hour expiration
			}

			$commits = json_decode($github_commit_json);

			if ( !empty($commits->message) ){
				?>
					<p>
						<strong>This repo is not available.</strong><br />
						If this is a private repo, the <strong>Client ID</strong> and <strong>Client Secret</strong> from your Github app must be added in <a href="themes.php?page=nebula_options&tab=functions&option=comments">Nebula Options</a> to retrieve issues.
					</p>
					<p>
						<a href="<?php echo nebula()->get_option('github_url'); ?>/commits/master" target="_blank">Commits &raquo;</a><br />
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

			echo '<p><small><a href="' . nebula()->get_option('github_url') . '/commits/master" target="_blank">View all commits &raquo;</a></small></p>';
			echo '</div>';

			//Issues
			echo '<div class="nebula-metabox-col">';
			echo '<strong>Recently Updated Issues</strong><br />';

			$github_issues_json = get_transient('nebula_github_issues');
			if ( empty($github_issues_json) || nebula()->is_debug() ){
				$response = nebula()->remote_get('https://api.github.com/repos/' . $repo_name . '/issues?sort=updated');
				if ( is_wp_error($response) ){
			        echo '<p>There was an error retrieving the Github issues...</p>';
			        return false;
			    }

			    $github_issues_json = $response['body'];
				set_transient('nebula_github_issues', $github_issues_json, MINUTE_IN_SECONDS*30); //30 minute expiration
			}

			$issues = json_decode($github_issues_json);

			//https://developer.github.com/v3/issues/
			if ( !empty($issues) ){
				echo '<ul>';
				for ( $i=0; $i <= 2; $i++ ){ //Get 3 issues
					$issue_date_time = strtotime($issues[$i]->updated_at);
					$issue_date_icon = ( date('Y-m-d', $issue_date_time) === date('Y-m-d') )? 'fa-clock' : 'fa-calendar';

					echo '<li>
						<p>
							<a href="' . $issues[$i]->html_url . '" target="_blank">' . htmlentities($issues[$i]->title) . '</a><br />
							<small><i class="far fa-fw ' . $issue_date_icon . '"></i> <span title="' . date('F j, Y @ g:ia', $issue_date_time) . '">' . human_time_diff($issue_date_time) . ' ago</span></small>
						</p>
					</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>No issues found.</p>';
			}

			echo '<p><small><a href="' . nebula()->get_option('github_url') . '/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc" target="_blank">View all issues &raquo;</a></small></p>';
			echo '</div></div>';
			nebula()->timer('Nebula Companion Github Dashboard', 'end');
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
