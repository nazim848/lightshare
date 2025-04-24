<?php

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
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
}
