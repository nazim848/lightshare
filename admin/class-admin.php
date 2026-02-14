<?php

namespace Lightshare;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

use Lightshare\Share_Button;

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
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
		add_filter('plugin_action_links_' . plugin_basename(LIGHTSHARE_PLUGIN_FILE), array($this, 'add_action_links'));
		add_action('wp_ajax_lightshare_reset_settings', array($this, 'reset_settings'));
		add_action('wp_ajax_lightshare_reset_counts', array($this, 'reset_counts'));
		add_action('wp_ajax_lightshare_preview_buttons', array($this, 'preview_buttons'));
		add_action('add_meta_boxes', array($this, 'add_share_meta_box'));
		add_action('save_post', array($this, 'save_share_meta_box'));
	}

	public function enqueue_styles($hook) {
		if ('settings_page_lightshare' !== $hook) {
			return;
		}
		wp_enqueue_style('lightshare-admin', plugin_dir_url(__FILE__) . 'css/lightshare-admin.css', array(), $this->version, 'all');
		// Load frontend styles to make preview match frontend output.
		wp_enqueue_style('lightshare-public', LIGHTSHARE_PLUGIN_URL . 'public/css/lightshare-public.css', array(), $this->version, 'all');
		$public_css = Share_Button::sanitize_inline_css(Share_Button::get_network_color_css());
		$admin_css = Share_Button::sanitize_inline_css(Share_Button::get_admin_network_color_css());
		if (!empty($public_css)) {
			wp_add_inline_style('lightshare-public', $public_css);
		}
		if (!empty($admin_css)) {
			wp_add_inline_style('lightshare-admin', $admin_css);
		}
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

	public function enqueue_block_editor_assets() {
		wp_enqueue_script(
			'lightshare-block-editor',
			plugin_dir_url(__FILE__) . 'js/lightshare-block.js',
			array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render'),
			$this->version,
			true
		);

		// Load frontend styles in editor for accurate preview.
		wp_enqueue_style(
			'lightshare-block-preview',
			LIGHTSHARE_PLUGIN_URL . 'public/css/lightshare-public.css',
			array(),
			$this->version
		);
		$block_preview_css = Share_Button::sanitize_inline_css(Share_Button::get_network_color_css());
		if (!empty($block_preview_css)) {
			wp_add_inline_style('lightshare-block-preview', $block_preview_css);
		}
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
			$network_options = Share_Button::process_social_networks($share_data);

			if (!empty($network_options)) {
				if (!isset($new_options['share'])) {
					$new_options['share'] = array();
				}
				$new_options['share'] = array_merge($new_options['share'], $network_options);
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
			wp_send_json_success('Settings are up to date.');
			return;
		}

		// Update with new options directly instead of merging
		$update_result = update_option('lightshare_options', $new_options);

		if ($update_result) {
			wp_send_json_success('Settings saved.');
		} else {
			// Check if the options are actually the same
			$current_options = get_option('lightshare_options', array());
			if ($current_options == $new_options) {
				wp_send_json_success('Settings are up to date.');
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
				<p><?php esc_html_e('Thank you for installing Lightshare! Please visit the ', 'lightshare-social-sharing'); ?>
					<a href="<?php echo esc_url(admin_url('options-general.php?page=lightshare')); ?>"><?php esc_html_e('settings page', 'lightshare-social-sharing'); ?></a>
					<?php esc_html_e('to configure the plugin.', 'lightshare-social-sharing'); ?>
				</p>
			</div>
		<?php
			delete_transient('lightshare_activation_notice');
		}
	}

	// Add action links to the plugin page
	public function add_action_links($links) {
		$settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=lightshare')) . '">' . esc_html__('Settings', 'lightshare-social-sharing') . '</a>';
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

		$sanitized_options = array();
		$sanitization_rules = array(
			// Share Button
			'share' => array(
				'social_networks' => 'array',
				'social_networks_order' => 'array',
				'show_counts' => 'boolean',
				'style' => 'text_field',
				'color_theme' => 'text_field',
				'show_label' => 'boolean',
				'label_text' => 'text_field',
				'ai_association_text' => 'text_field',
				'nudge_text' => 'text_field',
				'utm_enabled' => 'boolean',
				'utm_source' => 'text_field',
				'utm_medium' => 'text_field',
				'utm_campaign' => 'text_field',
				'count_threshold' => 'integer'
			),
			// Floating Button
			'floating' => array(
				'enabled' => 'boolean',
				'button_alignment' => 'text_field',
				'button_size' => 'text_field',
				'hide_on_mobile' => 'boolean',
				'mobile_position' => 'text_field',
				'scroll_offset' => 'text_field',
				'post_types' => 'array'
			),
			// Inline Button
			'inline' => array(
				'enabled' => 'boolean',
				'position' => 'text_field',
				'post_types' => 'array'
			),
			// Tools
			'tools' => array(
				'clean_uninstall' => 'boolean',
				'clean_deactivate' => 'boolean'
			)
		);

		foreach ($sanitization_rules as $section => $rules) {
			if (!isset($options[$section])) {
				continue;
			}

			$sanitized_options[$section] = array();

			foreach ($rules as $key => $rule) {
				if (!isset($options[$section][$key])) {
					continue;
				}

				switch ($rule) {
					case 'boolean':
						$sanitized_options[$section][$key] = (bool) $options[$section][$key];
						break;
					case 'text_field':
						$value = sanitize_text_field($options[$section][$key]);
						if ($section === 'share' && $key === 'style') {
							$allowed_styles = array('default', 'rounded', 'circle');
							if (!in_array($value, $allowed_styles, true)) {
								$value = 'default';
							}
						}
						if ($section === 'floating' && $key === 'mobile_position') {
							$allowed_positions = array('bottom', 'left', 'right');
							if (!in_array($value, $allowed_positions, true)) {
								$value = 'bottom';
							}
						}
						if ($section === 'floating' && $key === 'scroll_offset') {
							$normalized = strtolower(trim($value));
							$value = '';
							if (preg_match('/^\d+(?:\.\d+)?(?:px|%)$/', $normalized)) {
								$value = $normalized;
							}
						}
						$sanitized_options[$section][$key] = $value;
						break;
					case 'array':
						if (is_array($options[$section][$key])) {
							$sanitized_options[$section][$key] = array_map('sanitize_text_field', $options[$section][$key]);
						} else {
							$sanitized_options[$section][$key] = array();
						}
						break;
					case 'integer':
						$sanitized_options[$section][$key] = max(0, absint($options[$section][$key]));
						break;
					default:
						$sanitized_options[$section][$key] = sanitize_text_field($options[$section][$key]);
				}
			}
		}

		return $sanitized_options;
	}

	public function reset_settings() {
		// Verify nonce and capabilities
		if (!check_ajax_referer('lightshare_options_verify', 'nonce', false) || !current_user_can('manage_options')) {
			wp_send_json_error(array(
				'message' => __('Security check failed or insufficient permissions.', 'lightshare-social-sharing')
			));
		}

		// Define default options with proper structure
		$default_options = \Lightshare\LS_Options::get_default_options();

		// Sanitize default options before saving
		$sanitized_defaults = $this->sanitize_options($default_options);

		// Update options
		$update_result = update_option('lightshare_options', $sanitized_defaults);

		if ($update_result) {
			wp_send_json_success(array(
				'message' => __('Settings have been reset to default values.', 'lightshare-social-sharing')
			));
		} else {
			wp_send_json_error(array(
				'message' => __('Failed to reset settings. Please try again.', 'lightshare-social-sharing')
			));
		}
	}

	public function reset_counts() {
		// Verify nonce and capabilities
		if (!check_ajax_referer('lightshare_options_verify', 'nonce', false) || !current_user_can('manage_options')) {
			wp_send_json_error(array(
				'message' => __('Security check failed or insufficient permissions.', 'lightshare-social-sharing')
			));
		}

		$deleted_total = delete_post_meta_by_key('_lightshare_total_shares');

		$total_deleted = is_numeric($deleted_total) ? (int) $deleted_total : 0;
		$allowed_networks = \Lightshare\Share_Button::get_allowed_networks();
		foreach ($allowed_networks as $network) {
			$deleted_network = delete_post_meta_by_key('_lightshare_shares_' . $network);
			if (is_numeric($deleted_network)) {
				$total_deleted += (int) $deleted_network;
			}
		}

		wp_send_json_success(array(
			/* translators: %d number of rows removed */
			'message' => sprintf(__('Share counts cleared. Rows removed: %d', 'lightshare-social-sharing'), $total_deleted)
		));
	}

	public function preview_buttons() {
		// Verify nonce and capabilities
		if (!check_ajax_referer('lightshare_options_verify', 'nonce', false) || !current_user_can('manage_options')) {
			wp_send_json_error(array(
				'message' => __('Security check failed or insufficient permissions.', 'lightshare-social-sharing')
			));
		}

		$networks = isset($_POST['networks']) ? sanitize_text_field(wp_unslash($_POST['networks'])) : '';
		$style = isset($_POST['style']) ? sanitize_text_field(wp_unslash($_POST['style'])) : '';
		$show_label = isset($_POST['show_label']) ? (bool) absint(wp_unslash($_POST['show_label'])) : true;
		$label_text = isset($_POST['label_text']) ? sanitize_text_field(wp_unslash($_POST['label_text'])) : '';
		$nudge_text = isset($_POST['nudge_text']) ? sanitize_text_field(wp_unslash($_POST['nudge_text'])) : '';
		$color_theme = isset($_POST['color_theme']) ? sanitize_key(wp_unslash($_POST['color_theme'])) : '';

		$args = array(
			'networks' => $networks,
			'style' => $style,
			'color_theme' => $color_theme,
			'show_label' => $show_label,
			'label_text' => $label_text,
			'nudge_text' => $nudge_text,
			'url' => home_url('/'),
			'title' => get_bloginfo('name')
		);

		$html = Share_Button::render_buttons($args);
		$css = Share_Button::sanitize_inline_css(Share_Button::get_network_color_css($color_theme, '#lightshare-preview'));

		wp_send_json_success(array(
			'html' => $html,
			'css' => $css
		));
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

	public function add_share_meta_box() {
		$post_types = get_post_types(array('public' => true), 'names');
		if (isset($post_types['attachment'])) {
			unset($post_types['attachment']);
		}
		foreach ($post_types as $post_type) {
			add_meta_box(
				'lightshare_meta_box',
				__('Lightshare Settings', 'lightshare-social-sharing'),
				array($this, 'render_share_meta_box'),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public function render_share_meta_box($post) {
		wp_nonce_field('lightshare_meta_box', 'lightshare_meta_nonce');

		$disabled = get_post_meta($post->ID, '_lightshare_disable', true);
		$position = get_post_meta($post->ID, '_lightshare_inline_position', true);
		$networks = get_post_meta($post->ID, '_lightshare_networks', true);
		$nudge_text = get_post_meta($post->ID, '_lightshare_nudge_text', true);

		if (!is_array($networks)) {
			$networks = array();
		}

		$network_definitions = Share_Button::get_network_definitions();
		?>
		<p>
			<label>
				<input type="checkbox" name="lightshare_disable" value="1" <?php checked((bool) $disabled, true); ?> />
				<?php esc_html_e('Disable Lightshare on this post', 'lightshare-social-sharing'); ?>
			</label>
		</p>
		<p>
			<label for="lightshare_inline_position"><strong><?php esc_html_e('Inline Position', 'lightshare-social-sharing'); ?></strong></label>
			<select name="lightshare_inline_position" id="lightshare_inline_position" style="width: 100%;">
				<option value=""><?php esc_html_e('Inherit', 'lightshare-social-sharing'); ?></option>
				<option value="before" <?php selected($position, 'before'); ?>><?php esc_html_e('Before content', 'lightshare-social-sharing'); ?></option>
				<option value="after" <?php selected($position, 'after'); ?>><?php esc_html_e('After content', 'lightshare-social-sharing'); ?></option>
			</select>
		</p>
		<p><strong><?php esc_html_e('Networks', 'lightshare-social-sharing'); ?></strong></p>
		<div style="max-height: 160px; overflow: auto; padding: 4px 0;">
			<?php foreach ($network_definitions as $slug => $data) : ?>
				<label style="display:block; margin-bottom: 4px;">
					<input type="checkbox" name="lightshare_networks[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $networks, true)); ?> />
					<?php echo esc_html($data['label']); ?>
				</label>
			<?php endforeach; ?>
		</div>
		<p>
			<label for="lightshare_nudge_text"><strong><?php esc_html_e('Nudge Text', 'lightshare-social-sharing'); ?></strong></label>
			<textarea name="lightshare_nudge_text" id="lightshare_nudge_text" rows="3" style="width: 100%;"><?php echo esc_textarea($nudge_text); ?></textarea>
		</p>
<?php
	}

	public function save_share_meta_box($post_id) {
		if (!isset($_POST['lightshare_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lightshare_meta_nonce'])), 'lightshare_meta_box')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		$disabled = isset($_POST['lightshare_disable']) ? '1' : '';
		if ($disabled) {
			update_post_meta($post_id, '_lightshare_disable', '1');
		} else {
			delete_post_meta($post_id, '_lightshare_disable');
		}

		$position = isset($_POST['lightshare_inline_position']) ? sanitize_text_field(wp_unslash($_POST['lightshare_inline_position'])) : '';
		if (in_array($position, array('before', 'after'), true)) {
			update_post_meta($post_id, '_lightshare_inline_position', $position);
		} else {
			delete_post_meta($post_id, '_lightshare_inline_position');
		}

		$networks = array();
		if (isset($_POST['lightshare_networks']) && is_array($_POST['lightshare_networks'])) {
			$networks = array_map('sanitize_text_field', wp_unslash($_POST['lightshare_networks']));
			$networks = array_intersect($networks, Share_Button::get_allowed_networks());
			$networks = array_values(array_unique($networks));
		}
		if (!empty($networks)) {
			update_post_meta($post_id, '_lightshare_networks', $networks);
		} else {
			delete_post_meta($post_id, '_lightshare_networks');
		}

		$nudge_text = isset($_POST['lightshare_nudge_text']) ? sanitize_text_field(wp_unslash($_POST['lightshare_nudge_text'])) : '';
		if ($nudge_text !== '') {
			update_post_meta($post_id, '_lightshare_nudge_text', $nudge_text);
		} else {
			delete_post_meta($post_id, '_lightshare_nudge_text');
		}
	}
}
