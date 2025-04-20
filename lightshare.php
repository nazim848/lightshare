<?php
/*
Plugin Name: Lightshare - Lightweight Social Sharing
Description: LightShare is a lightweight, high-performance social media sharing plugin built with a focus on speed and minimal code footprint.
Version: 1.0.0
Author: Nazim Husain
Author URI: https://nazimansari.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: lightshare
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2

Lightshare is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Lightshare is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Lightshare. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

require __DIR__ . '/inc/class-lightshare-options.php';

use Lightshare\LS_Options;

define('LIGHTSHARE_VERSION', '1.0');
define('LIGHTSHARE_PATH', plugin_dir_path(__FILE__));
define('LIGHTSHARE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LIGHTSHARE_PLUGIN_FILE', __FILE__);
define('LIGHTSHARE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load the main plugin class
require plugin_dir_path(__FILE__) . 'inc/class-lightshare.php';

// Check if the plugin version is different from the current version
$lightshare_version = get_option('lightshare_version');
//update version
if ($lightshare_version != LIGHTSHARE_VERSION) {
	update_option('lightshare_version', LIGHTSHARE_VERSION, false);
}

// Run the plugin
function lightshare_run() {
	$plugin = new Lightshare\Lightshare();
	$plugin->run();
}
lightshare_run();

// Activation hook
register_activation_hook(__FILE__, 'lightshare_activate');

// Deactivation hook
register_deactivation_hook(__FILE__, 'lightshare_deactivate');

// Activation code
function lightshare_activate() {
	// Activation code here
	set_transient('lightshare_activation_notice', true, 5);
}

// Deactivation code
function lightshare_deactivate() {
	// Check if clean deactivate is enabled
	$clean_deactivate = LS_Options::get_option('clean_deactivate');

	if ($clean_deactivate == '1') {
		// Delete all plugin options
		$options_to_delete = [
			'lightshare_options',
			'lightshare_version',
		];

		foreach ($options_to_delete as $option) {
			delete_option($option);
		}
	}
}
