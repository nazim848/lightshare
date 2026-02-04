<?php

namespace Lightshare;

class LS_Options {
	private static $option_name = 'lightshare_options';

	public static function get_default_options() {
		$defaults = array(
			'share' => array(
				'social_networks' => array('facebook', 'twitter', 'linkedin', 'pinterest', 'email', 'copy'),
				'social_networks_order' => array('facebook', 'twitter', 'linkedin', 'pinterest', 'email', 'copy'),
				'show_counts' => false,
				'style' => 'default',
				'show_label' => true,
				'label_text' => 'Share'
			),
			'floating' => array(
				'enabled' => false,
				'button_alignment' => 'right',
				'button_size' => 'medium',
				'post_types' => array('post', 'page')
			),
			'inline' => array(
				'enabled' => false,
				'position' => 'after',
				'post_types' => array('post')
			),
			'tools' => array(
				'clean_uninstall' => false,
				'clean_deactivate' => false
			)
		);

		/**
		 * Filter default options for Lightshare.
		 *
		 * @param array $defaults Default options array.
		 */
		return apply_filters('lightshare_default_options', $defaults);
	}

	public static function get_options() {
		$options = get_option(self::$option_name, array());
		$defaults = self::get_default_options();
		if (!is_array($options)) {
			$options = array();
		}
		return array_replace_recursive($defaults, $options);
	}

	/**
	 * Get an option value using dot notation for nested options
	 * 
	 * @param string $key Option key (e.g. 'floating.enabled' for nested options)
	 * @param mixed $default Default value if option not found
	 * @return mixed Option value or default if not found
	 */
	public static function get_option($key, $default = false) {
		$options = self::get_options();

		// Handle dot notation for nested options
		if (strpos($key, '.') !== false) {
			$keys = explode('.', $key);
			$value = $options;

			foreach ($keys as $k) {
				if (!isset($value[$k])) {
					return $default;
				}
				$value = $value[$k];
			}

			return $value;
		}

		return isset($options[$key]) ? $options[$key] : $default;
	}

	/**
	 * Update an option value using dot notation for nested options
	 * 
	 * @param string $key Option key (e.g. 'floating.enabled' for nested options)
	 * @param mixed $value New value to set
	 * @return bool True if option was updated, false otherwise
	 */
	public static function update_option($key, $value) {
		$options = self::get_options();

		// Handle dot notation for nested options
		if (strpos($key, '.') !== false) {
			$keys = explode('.', $key);
			$last_key = array_pop($keys);
			$current = &$options;

			foreach ($keys as $k) {
				if (!isset($current[$k]) || !is_array($current[$k])) {
					$current[$k] = array();
				}
				$current = &$current[$k];
			}

			$current[$last_key] = $value;
		} else {
			$options[$key] = $value;
		}

		return update_option(self::$option_name, $options);
	}
}
