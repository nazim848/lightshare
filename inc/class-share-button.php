<?php

namespace Lightshare;

/**
 * Class for handling social networks operations
 */
class Share_Button {
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
		$allowed_networks = array(
			'facebook',
			'twitter',
			'linkedin',
			'pinterest',
			'bluesky',
			'whatsapp',
			'copy'
		);

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
}
