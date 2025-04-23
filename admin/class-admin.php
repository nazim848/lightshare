<?php

namespace Lightshare;

class Admin {
	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('wp_ajax_lightshare_save_settings', array($this, 'ajax_save_settings'));

		add_action('admin_notices', array($this, 'activation_notice'));
		add_action('admin_menu', array($this, 'add_plugin_settings_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_filter('plugin_action_links_' . plugin_basename(LIGHTSHARE_PLUGIN_FILE), array($this, 'add_action_links'));
		add_action('wp_ajax_lightshare_reset_settings', array($this, 'reset_settings'));
	}

	public function enqueue_styles($hook) {
		if ('settings_page_lightshare' !== $hook) {
			return;
		}
		wp_enqueue_style('lightshare-admin', plugin_dir_url(__FILE__) . 'css/lightshare-admin.css', array(), $this->version, 'all');
	}

	public function enqueue_scripts($hook) {
		if ('settings_page_lightshare' !== $hook) {
			return;
		}
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('lightshare-admin', plugin_dir_url(__FILE__) . 'js/lightshare-admin.js', array('jquery', 'jquery-ui-sortable'), $this->version, false);
		wp_localize_script('lightshare-admin', 'lightshare_admin', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('lightshare_options_verify')
		));
	}

	// Save the settings
	public function ajax_save_settings() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		if (
			!isset($_POST['lightshare_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lightshare_nonce'])), 'lightshare_options_verify')
		) {
			wp_send_json_error('Invalid nonce');
		}

		$new_options = array();

		// Handle lightshare_options
		if (isset($_POST['lightshare_options'])) {
			$new_options = $this->sanitize_options(map_deep(wp_unslash($_POST['lightshare_options']), 'sanitize_text_field'));
		}

		// Handle social networks data
		if (isset($_POST['lightshare_options']['share'])) {
			$share_data = map_deep(wp_unslash($_POST['lightshare_options']['share']), 'sanitize_text_field');

			// Handle social networks order
			if (isset($share_data['social_networks_order'])) {
				$order = json_decode($share_data['social_networks_order'], true);
				if (is_array($order) && !empty($order)) {
					$active_networks = isset($share_data['social_networks']) ? (array)$share_data['social_networks'] : array();

					// Only keep active networks in the order
					$ordered_networks = array_values(array_intersect($order, $active_networks));
					$new_options['share']['social_networks'] = $ordered_networks;
				}
			} elseif (isset($share_data['social_networks'])) {
				$new_options['share']['social_networks'] = (array)$share_data['social_networks'];
			}
		}

		$old_options = get_option('lightshare_options', array());

		// Check if the options are actually the same
		$changed = false;
		foreach ($new_options as $key => $value) {
			if (!isset($old_options[$key]) || $old_options[$key] !== $value) {
				$changed = true;
				break;
			}
		}

		if (!$changed) {
			wp_send_json_success('Settings are up to date');
			return;
		}

		// Update with new options directly instead of merging
		$update_result = update_option('lightshare_options', $new_options);

		if ($update_result) {
			wp_send_json_success('Settings saved successfully');
		} else {
			// Check if the options are actually the same
			$current_options = get_option('lightshare_options', array());
			if ($current_options == $new_options) {
				wp_send_json_success('Settings are up to date');
			} else {
				wp_send_json_error('Failed to update the settings.');
			}
		}
	}

	// Display the activation notice
	public function activation_notice() {
		if (get_transient('lightshare_activation_notice')) {
?>
			<div class="updated notice is-dismissible">
				<p><?php esc_html_e('Thank you for installing Lightshare! Please visit the ', 'lightshare'); ?>
					<a href="<?php echo esc_url(admin_url('options-general.php?page=lightshare')); ?>"><?php esc_html_e('settings page', 'lightshare'); ?></a>
					<?php esc_html_e('to configure the plugin.', 'lightshare'); ?>
				</p>
			</div>
<?php
			delete_transient('lightshare_activation_notice');
		}
	}

	// Add action links to the plugin page
	public function add_action_links($links) {
		$settings_link = '<a href="' . admin_url('options-general.php?page=lightshare') . '">' . __('Settings', 'lightshare') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	// Add the main Lightshare menu item to the settings menu
	public function add_plugin_settings_menu() {
		// Add Lightshare to the Settings menu
		add_options_page(
			'Lightshare',
			'Lightshare',
			'manage_options',
			'lightshare',
			array($this, 'display_plugin_setup_page')
		);
	}

	public function display_plugin_setup_page() {

		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'lightshare_tab_nonce')) {
			$active_tab = '#general';
		} else {
			$active_tab = isset($_GET['tab']) ? '#' . sanitize_text_field(wp_unslash($_GET['tab'])) : '#general';
		}

		include_once 'partials/admin-display.php';
	}

	public function register_settings() {
		register_setting('lightshare_options', 'lightshare_options', array(
			'type' => 'array',
			'sanitize_callback' => array($this, 'sanitize_options'),
		));
	}

	public function sanitize_options($options) {
		if (!is_array($options)) {
			return array();
		}
		$sanitization_rules = array(
			// Share Button
			'share' => 'share_settings',
			// Floating Button
			'enable_floating_button' => 'boolean',
			// Tools
			'clean_uninstall' => 'boolean',
			'clean_deactivate' => 'boolean',
		);

		foreach ($sanitization_rules as $option => $rule) {
			if (isset($options[$option])) {
				switch ($rule) {
					case 'boolean':
						$options[$option] = (bool) $options[$option];
						break;
					case 'text_field':
						$options[$option] = sanitize_text_field($options[$option]);
						break;
					case 'share_settings':
						if (is_array($options[$option])) {
							if (isset($options[$option]['social_networks']) && is_array($options[$option]['social_networks'])) {
								$options[$option]['social_networks'] = array_map('sanitize_text_field', $options[$option]['social_networks']);
							} else {
								$options[$option]['social_networks'] = array();
							}
						}
						break;
				}
			}
		}

		return $options;
	}

	public function reset_settings() {
		check_ajax_referer('lightshare_options_verify', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$default_options = [
			// Share Button
			// 'share' => array(
			// 	'social_networks' => array()
			// ),
			// Floating Button
			'enable_floating_button' => '0',

			// Tools
			'clean_uninstall' => '0',
			'clean_deactivate' => '0',
		];
		update_option('lightshare_options', $default_options);
		wp_send_json_success('Settings reset successfully');
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}
