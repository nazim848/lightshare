<?php

namespace Lightshare;

class LS_Options {
	private static $option_name = 'lightshare_options';

	public static function get_options() {
		return get_option(self::$option_name, array());
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

	/**
	 * Delete an option using dot notation for nested options
	 * 
	 * @param string $key Option key (e.g. 'floating.enabled' for nested options)
	 * @return bool True if option was deleted, false otherwise
	 */
	public static function delete_option($key) {
		$options = self::get_options();

		// Handle dot notation for nested options
		if (strpos($key, '.') !== false) {
			$keys = explode('.', $key);
			$last_key = array_pop($keys);
			$current = &$options;

			foreach ($keys as $k) {
				if (!isset($current[$k]) || !is_array($current[$k])) {
					return false;
				}
				$current = &$current[$k];
			}

			if (isset($current[$last_key])) {
				unset($current[$last_key]);
				return update_option(self::$option_name, $options);
			}
			return false;
		}

		if (isset($options[$key])) {
			unset($options[$key]);
			return update_option(self::$option_name, $options);
		}
		return false;
	}
}
