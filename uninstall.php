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
$clean_uninstall = LS_Options::get_option('tools.clean_uninstall');

if ($clean_uninstall == '1') {
	// Delete all plugin options
	$options_to_delete = [
		'lightshare_options',
		'lightshare_version',
	];

	foreach ($options_to_delete as $option) {
		delete_option($option);
	}

	// Delete transients
	delete_transient('lightshare_activation_notice');

	// Clear any cached data
	wp_cache_flush();

	// Remove any scheduled events
	wp_clear_scheduled_hook('lightshare_cleanup_cache');
}
