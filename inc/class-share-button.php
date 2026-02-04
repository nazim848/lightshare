<?php

namespace Lightshare;

use Lightshare\LS_Options;

/**
 * Class for handling social networks operations
 */
class Share_Button {
	/**
	 * Get allowed social networks.
	 *
	 * @return array
	 */
	public static function get_allowed_networks() {
		$networks = array(
			'facebook',
			'twitter',
			'linkedin',
			'pinterest',
			'reddit',
			'email',
			'bluesky',
			'whatsapp',
			'copy'
		);

		/**
		 * Filter allowed social networks.
		 *
		 * @param array $networks Allowed network slugs.
		 */
		return apply_filters('lightshare_allowed_networks', $networks);
	}

	/**
	 * Process and sanitize social networks data
	 *
	 * @param array $share_data Raw share data from form submission
	 * @return array Processed and sanitized social networks data
	 */
	public static function process_social_networks($share_data) {
		// Verify user capabilities
		if (!current_user_can('manage_options')) {
			return array();
		}

		$network_options = array();
		$allowed_networks = self::get_allowed_networks();

		if (!is_array($share_data)) {
			return $network_options;
		}

		// Handle social networks order
		if (isset($share_data['social_networks_order'])) {
			$order = json_decode(stripslashes($share_data['social_networks_order']), true);
			if (is_array($order) && !empty($order)) {
				$active_networks = isset($share_data['social_networks']) ? (array)$share_data['social_networks'] : array();

				// Validate and sanitize networks
				$active_networks = array_map('sanitize_text_field', $active_networks);
				$active_networks = array_intersect($active_networks, $allowed_networks);

				$order = array_map('sanitize_text_field', $order);
				$order = array_intersect($order, $allowed_networks);

				// Only keep active networks in the order
				$network_options['social_networks'] = array_values(array_intersect($order, $active_networks));
				$network_options['social_networks_order'] = $order;
			}
		} elseif (isset($share_data['social_networks'])) {
			// Validate and sanitize networks
			$active_networks = array_map('sanitize_text_field', (array)$share_data['social_networks']);
			$active_networks = array_intersect($active_networks, $allowed_networks);
			$network_options['social_networks'] = array_values($active_networks);
			$network_options['social_networks_order'] = $active_networks;
		}

		return $network_options;
	}

	/**
	 * Render the share buttons
	 *
	 * @param array $args Arguments for rendering (networks, style, etc.)
	 * @return string HTML output
	 */
	public static function render_buttons($args = array()) {
		// Get configured networks from options
		$configured_networks = LS_Options::get_option('share.social_networks', array('facebook', 'twitter', 'linkedin', 'whatsapp', 'copy'));
		
		// If networks are passed in args (shortcode), use them, otherwise use configured defaults
		$networks = !empty($args['networks']) ? explode(',', $args['networks']) : $configured_networks;
		$networks = array_filter(array_map('trim', (array) $networks));
		
		// Get style from args or options (default to 'default' if not set)
		$style = !empty($args['style']) ? $args['style'] : LS_Options::get_option('share.style', 'default');

		// Label settings
		$show_label = array_key_exists('show_label', $args)
			? filter_var($args['show_label'], FILTER_VALIDATE_BOOLEAN)
			: LS_Options::get_option('share.show_label', true);
		$label_text = !empty($args['label_text'])
			? $args['label_text']
			: LS_Options::get_option('share.label_text', 'Share');
		$label_text = apply_filters('lightshare_label_text', $label_text, $args, $post_id);
		
		// Check for custom post data passed in args (useful for loops or custom queries)
		$post_id = !empty($args['post_id']) ? $args['post_id'] : get_the_ID();

		/**
		 * Filter whether rendered button output should be cached.
		 *
		 * @param bool $cache_enabled Whether to cache.
		 * @param array $args Rendering args.
		 * @param int $post_id Post ID.
		 */
		$cache_enabled = (bool) apply_filters('lightshare_enable_render_cache', false, $args, $post_id);
		$cache_key = '';
		if ($cache_enabled) {
			$options = LS_Options::get_options();
			$cache_key = 'lightshare_render_' . md5(
				$post_id . '|' . wp_json_encode($args) . '|' . wp_json_encode($options['share'])
			);
			$cached = get_transient($cache_key);
			if (is_string($cached) && $cached !== '') {
				return $cached;
			}
		}
		
		// Get URL and Title
		$permalink = !empty($args['url']) ? $args['url'] : get_permalink($post_id);
		$title = !empty($args['title']) ? $args['title'] : get_the_title($post_id);
		
		$encoded_url = urlencode($permalink);
		$encoded_title = urlencode($title);
		
		// Image for Pinterest
		if (!empty($args['image'])) {
			$image_url = $args['image'];
		} else {
			$image_url = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'full') : '';
		}
		$encoded_image = urlencode($image_url);

		$wrapper_class = 'lightshare-container lightshare-style-' . esc_attr($style);
		if (!empty($args['class'])) {
			$wrapper_class .= ' ' . esc_attr($args['class']);
		}
		$wrapper_class = apply_filters('lightshare_wrapper_class', $wrapper_class, $args, $post_id);

		$html = '<div class="' . esc_attr($wrapper_class) . '">';

		// Add Count Display
		$show_counts = LS_Options::get_option('share.show_counts', false);
		$total_shares = (int) get_post_meta($post_id, '_lightshare_total_shares', true);

		$count_html = '';
		if ($show_counts && $total_shares > 0) {
			$count_html = ' <span class="lightshare-total-count" aria-live="polite">(' . self::format_count($total_shares) . ')</span>';
		}

		// Optional label
		if ($show_label) {
			$html .= '<span class="lightshare-label">' . esc_html($label_text) . $count_html . ':</span>';
		}

		$html .= '<div class="lightshare-buttons" data-post-id="' . esc_attr($post_id) . '">';

		$networks = apply_filters('lightshare_networks', $networks, $args, $post_id);
		foreach ($networks as $network) {
			$network = trim($network);
			$share_url = '';
			$icon = '';
			$label = ucfirst($network);

			switch ($network) {
				case 'facebook':
					$share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/></svg>';
					break;
				case 'twitter':
					$share_url = 'https://twitter.com/intent/tweet?text=' . $encoded_title . '&url=' . $encoded_url;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z"/></svg>';
					break;
				case 'linkedin':
					$share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z"/></svg>';
					break;
				case 'whatsapp':
					$share_url = 'https://api.whatsapp.com/send?text=' . $encoded_title . '%20' . $encoded_url;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>';
					break;
				case 'bluesky':
					$share_url = 'https://bsky.app/intent/compose?text=' . $encoded_title . '%20' . $encoded_url;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 512 512"><path d="M111.8 62.2C170.2 105.9 233 194.7 256 242.4c23-47.6 85.8-136.4 144.2-180.2c42.1-31.6 110.3-56 110.3 21.8c0 15.5-8.9 130.5-14.1 149.2C478.2 298 412 314.6 353.1 304.5c102.9 17.5 129.1 75.5 72.5 133.5c-107.4 110.2-154.3-27.6-166.3-62.9l0 0c-11.9 35.5-58.7 173.9-166.3 62.9c-56.5-58-30.4-116 72.5-133.5c-58.9 10-125.1-6.6-143.3-71.3C14.9 216.7 6 101.7 6 86.2C6 8.4 74.1 32.8 116.2 64.4L111.8 62.2z"/></svg>';
					break;
				case 'pinterest':
					$share_url = 'https://pinterest.com/pin/create/button/?url=' . $encoded_url . '&media=' . $encoded_image . '&description=' . $encoded_title;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 0 0-2.915 15.452c-.105-.275-.195-.694-.042-1.453l1.192-5.071s-.3-.598-.3-1.482c0-1.39.806-2.427 1.81-2.427.854 0 1.265.64 1.265 1.408 0 .858-.546 2.14-0.828 3.33-.236.995.5 1.807 1.48 1.807 1.778 0 3.144-1.874 3.144-4.58 0-2.393-1.72-4.068-4.176-4.068-3.045 0-4.833 2.281-4.833 4.641 0 .92.354 1.905.796 2.439a.368.368 0 0 1 .083.352c-.09.376-.293 1.192-.333 1.36-.053.22-.172.267-.398.161-1.487-.695-2.415-2.873-2.415-4.625 0-3.769 2.737-7.229 7.892-7.229 4.144 0 7.365 2.953 7.365 6.83 0 4.075-2.569 7.356-6.135 7.356-1.197 0-2.323-.622-2.71-1.354l-.736 2.802c-.268 1.025-1.002 2.308-1.492 3.09C4.908 15.895 6.42 16 8 16a8 8 0 0 0 8-8 8 8 0 0 0-8-8z"/></svg>';
					break;
				case 'reddit':
					$share_url = 'https://www.reddit.com/submit?url=' . $encoded_url . '&title=' . $encoded_title;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M6.167 8a.831.831 0 0 0-.83.83c0 .459.372.84.83.831a.831.831 0 0 0 0-1.661zm1.843 3.647c.315 0 1.403-.038 1.976-.611a.232.232 0 0 0 0-.306.213.213 0 0 0-.306 0c-.353.363-1.126.487-1.67.487-.545 0-1.308-.124-1.671-.487a.213.213 0 0 0-.306 0 .213.213 0 0 0 0 .306c.564.563 1.652.61 1.977.61zm.992-2.807c0 .458.373.83.831.83.458 0 .83-.381.83-.83a.831.831 0 0 0-1.66 0z"/><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.828-1.165c-.315 0-.602.124-.812.325-.801-.573-1.9-.945-3.121-.993l.534-2.501 1.738.372a.83.83 0 1 0 .83-.869.83.83 0 0 0-.744.468l-1.938-.41a.203.203 0 0 0-.153.028.186.186 0 0 0-.086.134l-.592 2.788c-1.24.038-2.358.41-3.17.992-.21-.2-.496-.324-.81-.324a1.163 1.163 0 0 0-.478 2.224c-.02.115-.029.23-.029.353 0 1.795 2.091 3.256 4.669 3.256 2.577 0 4.668-1.451 4.668-3.256 0-.114-.01-.238-.029-.353.401-.181.688-.592.688-1.069 0-.65-.525-1.165-1.165-1.165z"/></svg>';
					break;
				case 'email':
					$share_url = 'mailto:?subject=' . $encoded_title . '&body=' . $encoded_url;
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/></svg>';
					break;
				case 'copy':
					$share_url = '#';
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3z"/></svg>';
					break;
			}

			$share_url = apply_filters('lightshare_share_url', $share_url, $network, $post_id, $args);

			if ($share_url) {
				$class_suffix = $network === 'copy' ? ' lightshare-copy' : '';
				$data_attr = $network === 'copy' ? ' data-url="' . esc_attr($permalink) . '"' : '';
				$target = ($network === 'copy' || $network === 'email') ? '' : ' target="_blank" rel="noopener noreferrer"';
				$aria_label = ($network === 'copy') ? __('Copy link', 'lightshare') : sprintf(__('Share on %s', 'lightshare'), $label);
				
				$html .= sprintf(
					'<a href="%s" class="lightshare-button lightshare-%s%s"%s%s title="Share on %s" aria-label="%s">',
					esc_url($share_url),
					esc_attr($network),
					$class_suffix,
					$target,
					$data_attr,
					esc_attr($label),
					esc_attr($aria_label)
				);
				$html .= '<span class="lightshare-icon">' . $icon . '</span>';
				$html .= '<span class="lightshare-text">' . esc_html($label) . '</span>';
				$html .= '</a>';
			}
		}

		$html .= '</div></div>';

		/**
		 * Filter the final share buttons HTML.
		 *
		 * @param string $html    Rendered HTML.
		 * @param array  $args    Rendering arguments.
		 * @param int    $post_id Post ID.
		 */
		$html = apply_filters('lightshare_buttons_html', $html, $args, $post_id);

		if ($cache_enabled && $cache_key) {
			$ttl = (int) apply_filters('lightshare_render_cache_ttl', 300, $args, $post_id);
			set_transient($cache_key, $html, max(30, $ttl));
		}

		return $html;
	}

	/**
	 * Format share count
	 *
	 * @param int $count Number of shares
	 * @return string Formatted count
	 */
	public static function format_count($count) {
		if ($count >= 1000000) {
			return round($count / 1000000, 1) . 'M';
		} elseif ($count >= 1000) {
			return round($count / 1000, 1) . 'k';
		}
		return (string)$count;
	}
}
