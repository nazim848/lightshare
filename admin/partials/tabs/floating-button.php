<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Lightshare\LS_Options;

// Get all registered post types
$post_types = get_post_types(array('public' => true), 'objects');

?>

<div id="<?php echo esc_attr($tab_id); ?>" class="tab-pane">
	<h2 class="content-title"><span class="dashicons dashicons-share-alt"></span> Floating Button</h2>
	<div class="lightshare-card">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<div class="lightshare-title-wrapper">Enable Floating Button
						<span class="dashicons dashicons-editor-help" data-title="Display social share buttons in floating sidebar"></span>
					</div>
				</th>

				<td>
					<div class="checkbox-radio">
						<label>
							<input type="checkbox" name="lightshare_options[floating][enabled]" value="1" class="floating-button-toggle" <?php checked(LS_Options::get_option('floating.enabled'), '1'); ?>>
						</label>
					</div>
				</td>
			</tr>
		</table>

		<div class="floating-button-settings" style="display: <?php echo LS_Options::get_option('floating.enabled') ? 'block' : 'none'; ?>;">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<div class="lightshare-title-wrapper">Post Types
							<span class="dashicons dashicons-editor-help" data-title="Select which post types should display the floating share buttons"></span>
						</div>
					</th>
					<td>
						<div class="lightshare-checkbox-group">
							<?php foreach ($post_types as $post_type) :
								$post_type_name = $post_type->name;
								$is_checked = in_array($post_type_name, LS_Options::get_option('floating.post_types', array('post', 'page')));
							?>
								<label class="lightshare-checkbox">
									<input type="checkbox"
										name="lightshare_options[floating][post_types][]"
										value="<?php echo esc_attr($post_type_name); ?>"
										<?php checked($is_checked); ?>>
									<?php echo esc_html($post_type->labels->singular_name); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<div class="lightshare-title-wrapper"> Button Alignment
							<span class="dashicons dashicons-editor-help" data-title="Choose the alignment of the floating share buttons."></span>
						</div>
					</th>
					<td>
						<select name="lightshare_options[floating][button_alignment]">
							<option value="left" <?php selected(LS_Options::get_option('floating.button_alignment'), 'left'); ?>>Left</option>
							<option value="right" <?php selected(LS_Options::get_option('floating.button_alignment'), 'right'); ?>>Right</option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<div class="lightshare-title-wrapper"> Button Size
							<span class="dashicons dashicons-editor-help" data-title="Choose the size of the floating share buttons."></span>
						</div>
					</th>
					<td>
						<select name="lightshare_options[floating][button_size]">
							<option value="small" <?php selected(LS_Options::get_option('floating.button_size'), 'small'); ?>>Small</option>
							<option value="medium" <?php selected(LS_Options::get_option('floating.button_size'), 'medium'); ?>>Medium</option>
							<option value="large" <?php selected(LS_Options::get_option('floating.button_size'), 'large'); ?>>Large</option>
						</select>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>