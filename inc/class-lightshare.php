<?php

namespace Lightshare;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

class Lightshare {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->version = LIGHTSHARE_VERSION;
		$this->plugin_name = 'lightshare';
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once LIGHTSHARE_PATH . 'inc/class-loader.php';
		require_once LIGHTSHARE_PATH . 'inc/class-lightshare-options.php';
		require_once LIGHTSHARE_PATH . 'inc/class-share-button.php';
		require_once LIGHTSHARE_PATH . 'admin/class-admin.php';
		require_once LIGHTSHARE_PATH . 'public/class-public.php';

		$this->loader = new \Lightshare\Loader();
	}

	private function define_public_hooks() {
		$plugin_public = new \Lightshare\Public_Core($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_filter('the_content', $plugin_public, 'add_share_buttons');
		$this->loader->add_action('wp_footer', $plugin_public, 'add_floating_buttons');
		$this->loader->add_action('init', $plugin_public, 'register_shortcodes');
		$this->loader->add_action('init', $plugin_public, 'register_block');
		$this->loader->add_action('wp_ajax_lightshare_track_click', $plugin_public, 'track_click');
		$this->loader->add_action('wp_ajax_nopriv_lightshare_track_click', $plugin_public, 'track_click');
		$this->loader->add_action('lightshare_flush_counts', $plugin_public, 'flush_queued_counts');
	}

	private function define_admin_hooks() {
		$plugin_admin = new \Lightshare\Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}
