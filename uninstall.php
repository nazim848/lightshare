<?php

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Verify user capabilities
if (!current_user_can('activate_plugins')) {
	wp_die(esc_html__('You do not have sufficient permissions to uninstall this plugin.', 'lightshare'));
}

require __DIR__ . '/inc/class-lightshare-options.php';

use Lightshare\LS_Options;

// Check if clean uninstall is enabled
$lightshare_clean_uninstall = LS_Options::get_option('tools.clean_uninstall');

if ($lightshare_clean_uninstall == '1') {
	// Delete all plugin options
	$lightshare_options_to_delete = [
		'lightshare_options',
		'lightshare_version',
	];

	foreach ($lightshare_options_to_delete as $lightshare_option) {
		delete_option($lightshare_option);
	}

	// Delete transients
	delete_transient('lightshare_activation_notice');
	delete_transient('lightshare_click_queue');

	// Delete render cache transients (wildcard).
	global $wpdb;
	if ($wpdb instanceof wpdb) {
		$pattern = $wpdb->esc_like('lightshare_render_') . '%';
		$transient_key_like = '_transient_' . $pattern;
		$timeout_key_like = '_transient_timeout_' . $pattern;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$transient_key_like,
				$timeout_key_like
			)
		);
	}

	// Remove any scheduled events
	wp_clear_scheduled_hook('lightshare_cleanup_cache');
}
