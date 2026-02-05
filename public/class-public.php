<?php

namespace Lightshare;

use Lightshare\LS_Options;
use Lightshare\Share_Button;

class Public_Core {
	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	private function should_batch_clicks($post_id, $network) {
		/**
		 * Filter whether to batch click updates instead of immediate writes.
		 *
		 * @param bool   $should_batch Whether to batch.
		 * @param int    $post_id      Post ID.
		 * @param string $network      Network slug.
		 */
		return (bool) apply_filters('lightshare_batch_clicks', false, $post_id, $network);
	}

	private function get_click_queue() {
		$queue = get_transient('lightshare_click_queue');
		return is_array($queue) ? $queue : array();
	}

	private function update_click_queue($post_id, $network) {
		$queue = $this->get_click_queue();
		if (!isset($queue[$post_id])) {
			$queue[$post_id] = array();
		}
		if (!isset($queue[$post_id][$network])) {
			$queue[$post_id][$network] = 0;
		}
		$queue[$post_id][$network]++;
		set_transient('lightshare_click_queue', $queue, MINUTE_IN_SECONDS * 10);

		if (!wp_next_scheduled('lightshare_flush_counts')) {
			wp_schedule_single_event(time() + 60, 'lightshare_flush_counts');
		}
	}

	private function get_queued_count($post_id, $network = null) {
		$queue = $this->get_click_queue();
		if (!isset($queue[$post_id])) {
			return 0;
		}
		if ($network === null) {
			return array_sum($queue[$post_id]);
		}
		return isset($queue[$post_id][$network]) ? (int) $queue[$post_id][$network] : 0;
	}

	private function should_enqueue_assets() {
		$post_id = get_queried_object_id();
		$should_enqueue = false;

		$inline_enabled = LS_Options::get_option('inline.enabled', false);
		if ($inline_enabled) {
			$post_types = LS_Options::get_option('inline.post_types', array('post'));
			if (is_singular($post_types)) {
				$should_enqueue = true;
			}
		}

		$floating_enabled = LS_Options::get_option('floating.enabled', false);
		if ($floating_enabled) {
			$post_types = LS_Options::get_option('floating.post_types', array('post', 'page'));
			if (is_singular($post_types)) {
				$should_enqueue = true;
			}
		}

		if (!$should_enqueue && $post_id) {
			$post = get_post($post_id);
			if ($post && has_shortcode($post->post_content, 'lightshare')) {
				$should_enqueue = true;
			}
		}

		/**
		 * Filter whether Lightshare assets should be enqueued.
		 *
		 * @param bool $should_enqueue Whether to enqueue assets.
		 * @param int  $post_id         Current post ID.
		 */
		return apply_filters('lightshare_should_enqueue_assets', $should_enqueue, $post_id);
	}

	public function enqueue_styles() {
		if (!$this->should_enqueue_assets()) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-public',
			plugin_dir_url(__FILE__) . 'css/lightshare-public.css',
			array(),
			$this->version,
			'all'
		);
		wp_add_inline_style($this->plugin_name . '-public', Share_Button::get_network_color_css());
	}

	public function enqueue_scripts() {
		if (!$this->should_enqueue_assets()) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-public',
			plugin_dir_url(__FILE__) . 'js/lightshare-public.js',
			array('jquery'),
			$this->version,
			false
		);

		wp_localize_script(
			$this->plugin_name . '-public',
			'lightshare_ajax',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce'    => wp_create_nonce('lightshare_nonce')
			)
		);
	}

	public function track_click() {
		check_ajax_referer('lightshare_nonce', 'nonce');

		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		$network = isset($_POST['network']) ? sanitize_text_field(wp_unslash($_POST['network'])) : '';

		if ($post_id > 0 && !empty($network)) {
			$post = get_post($post_id);
			if (!$post) {
				wp_send_json_error();
			}

			$post_type = get_post_type_object($post->post_type);
			if (!$post_type || empty($post_type->public) || $post->post_status !== 'publish') {
				wp_send_json_error();
			}

			$allowed_networks = Share_Button::get_allowed_networks();
			if (!in_array($network, $allowed_networks, true)) {
				wp_send_json_error();
			}

			if ($this->should_batch_clicks($post_id, $network)) {
				$this->update_click_queue($post_id, $network);
			} else {
				// Increment total shares
				$total_shares = (int) get_post_meta($post_id, '_lightshare_total_shares', true);
				$total_shares++;
				update_post_meta($post_id, '_lightshare_total_shares', $total_shares);

				// Increment network shares
				$network_shares = (int) get_post_meta($post_id, '_lightshare_shares_' . $network, true);
				$network_shares++;
				update_post_meta($post_id, '_lightshare_shares_' . $network, $network_shares);
			}

			$stored_total = (int) get_post_meta($post_id, '_lightshare_total_shares', true);
			$queued_total = $this->get_queued_count($post_id);
			$total_shares = $stored_total + $queued_total;

			wp_send_json_success(array('count' => Share_Button::format_count($total_shares)));
		}

		wp_send_json_error();
	}

	public function flush_queued_counts() {
		$queue = $this->get_click_queue();
		if (empty($queue)) {
			return;
		}

		foreach ($queue as $post_id => $networks) {
			$post_id = (int) $post_id;
			if ($post_id <= 0 || empty($networks) || !is_array($networks)) {
				continue;
			}

			$total_increment = 0;
			foreach ($networks as $network => $count) {
				$count = (int) $count;
				if ($count <= 0) {
					continue;
				}
				$total_increment += $count;
				$meta_key = '_lightshare_shares_' . sanitize_key($network);
				$current = (int) get_post_meta($post_id, $meta_key, true);
				update_post_meta($post_id, $meta_key, $current + $count);
			}

			if ($total_increment > 0) {
				$current_total = (int) get_post_meta($post_id, '_lightshare_total_shares', true);
				update_post_meta($post_id, '_lightshare_total_shares', $current_total + $total_increment);
			}
		}

		delete_transient('lightshare_click_queue');
	}

	public function register_shortcodes() {
		add_shortcode('lightshare', array($this, 'render_shortcode'));
	}

	public function register_block() {
		if (!function_exists('register_block_type')) {
			return;
		}

		register_block_type('lightshare/share-buttons', array(
			'attributes' => array(
				'networks' => array(
					'type' => 'string',
					'default' => ''
				),
				'style' => array(
					'type' => 'string',
					'default' => ''
				),
				'showLabel' => array(
					'type' => 'boolean',
					'default' => true
				),
				'labelText' => array(
					'type' => 'string',
					'default' => ''
				)
			),
			'render_callback' => array($this, 'render_block')
		));
	}

	public function render_block($attributes) {
		$args = array(
			'networks' => isset($attributes['networks']) ? $attributes['networks'] : '',
			'style' => isset($attributes['style']) ? $attributes['style'] : '',
			'show_label' => isset($attributes['showLabel']) ? (bool) $attributes['showLabel'] : true,
			'label_text' => isset($attributes['labelText']) ? $attributes['labelText'] : ''
		);

		return Share_Button::render_buttons($args);
	}

	public function render_shortcode($atts) {
		$atts = shortcode_atts(
			array(
				'networks' => '',
				'style'    => '',
			),
			$atts,
			'lightshare'
		);

		return Share_Button::render_buttons($atts);
	}

	public function add_share_buttons($content) {
		if (is_singular() && is_main_query()) {
			// Check if inline sharing is enabled
			$inline_enabled = LS_Options::get_option('inline.enabled', false);

			if ($inline_enabled) {
				// Check if enabled for this post type
				$post_types = LS_Options::get_option('inline.post_types', array('post'));
				if (is_singular($post_types)) {
					$buttons = Share_Button::render_buttons();
					$position = LS_Options::get_option('inline.position', 'after');
					if ($position === 'before') {
						return $buttons . $content;
					}
					return $content . $buttons;
				}
			}
		}
		return $content;
	}

	public function add_floating_buttons() {
		$enabled = LS_Options::get_option('floating.enabled', false);
		
		if ($enabled) {
			$post_types = LS_Options::get_option('floating.post_types', array('post', 'page'));
			
			if (is_singular($post_types)) {
				$alignment = LS_Options::get_option('floating.button_alignment', 'left');
				$size = LS_Options::get_option('floating.button_size', 'medium');
				echo wp_kses_post(Share_Button::render_buttons(array(
					'class' => 'lightshare-floating lightshare-floating-' . esc_attr($alignment) . ' lightshare-floating-size-' . esc_attr($size),
					'show_label' => false
				)));
			}
		}
	}
}
