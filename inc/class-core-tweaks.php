<?php

namespace Lightshare;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

class Core_Tweaks {

	public function __construct() {
		$this->initialize_tweaks();
	}

	public function initialize_tweaks() {
		$tweaks = [
			'disable_comments',
			'disable_rest_api',
			'limit_post_revisions',
		];

		foreach ($tweaks as $option) {
			if (LS_Options::get_option($option)) {
				$this->$option();
			}
		}
	}

	// Disable Comments
	private function disable_comments() {
		add_action('admin_init', [$this, 'disable_comments_admin_actions']);
		add_action('admin_bar_menu', [$this, 'disable_comments_admin_bar'], 999);
		add_filter('comments_open', '__return_false', 20, 0);
		add_filter('pings_open', '__return_false', 20, 0);
		add_filter('comments_array', '__return_empty_array', 10, 0);
		add_action('init', [$this, 'disable_comments_post_types_support'], 100);
	}

	public function disable_comments_admin_actions() {
		// Remove comments menu and redirect
		remove_menu_page('edit-comments.php');
		remove_submenu_page('options-general.php', 'options-discussion.php');

		global $pagenow;
		if ($pagenow === 'edit-comments.php' || $pagenow === 'options-discussion.php') {
			wp_safe_redirect(admin_url());
			exit;
		}

		// Remove dashboard widgets
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
		remove_action('dashboard_activity_widget_content', 'wp_dashboard_recent_comments');
	}

	public function disable_comments_admin_bar($wp_admin_bar) {
		$wp_admin_bar->remove_node('comments');
	}

	public function disable_comments_post_types_support() {
		$post_types = get_post_types();
		if (is_array($post_types)) {
			foreach ($post_types as $post_type) {
				if (post_type_supports($post_type, 'comments')) {
					remove_post_type_support($post_type, 'comments');
					remove_post_type_support($post_type, 'trackbacks');
				}
			}
		}
	}

	// Disable REST API
	private function disable_rest_api() {
		$rest_api_option = LS_Options::get_option('disable_rest_api');
		if ($rest_api_option === 'non_admins') {
			add_filter('rest_authentication_errors', [$this, 'disable_rest_api_for_non_admins']);
		}
	}

	public function disable_rest_api_for_non_admins($access) {

		if (!current_user_can('manage_options')) {

			$excluded_plugins = [
				'contact-form-7',
				'wordfence',
				'elementor',
				'ws-form',
				'litespeed',
				'wp-recipe-maker',
				'iawp'
			];

			$current_route = $this->get_current_rest_route();

			// Check if the current route belongs to an excluded plugin
			foreach ($excluded_plugins as $plugin_slug) {
				if (strpos($current_route, $plugin_slug) === 0) {
					return $access;
				}
			}

			return new \WP_Error('rest_api_disabled', __('Sorry, you do not have permission to make REST API requests.', 'lightshare'), ['status' => rest_authorization_required_code()]);
		}

		return $access;
	}

	private function get_current_rest_route() {
		$rest_route = $GLOBALS['wp']->query_vars['rest_route'];
		return untrailingslashit($rest_route);
	}

	// Limit Post Revisions
	private function limit_post_revisions() {
		if (defined('WP_POST_REVISIONS')) {
			add_action('admin_notices', [$this, 'admin_notice_post_revisions']);
		} else {
			$limit_post_revisions = LS_Options::get_option('limit_post_revisions');
			if ($limit_post_revisions == 'false') {
				$limit_post_revisions = false;
			}
			define('WP_POST_REVISIONS', $limit_post_revisions);
		}
	}

	public function admin_notice_post_revisions() {
		echo "<div class='notice notice-error'>";
		echo "<p>";
		echo "<strong>" . esc_html(__('Lightshare Warning', 'lightshare')) . ":</strong> ";
		echo esc_html(__('WP_POST_REVISIONS is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'lightshare'));
		echo "</p>";
		echo "</div>";
	}
}
