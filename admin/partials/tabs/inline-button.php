<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Lightshare\LS_Options;

// Get all registered post types
$post_types = get_post_types(array('public' => true), 'objects');

?>

<div id="<?php echo esc_attr($tab_id); ?>" class="tab-pane">
	<h2 class="content-title"><span class="dashicons dashicons-align-left"></span> Inline Button</h2>
	<div class="lightshare-card">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<div class="lightshare-title-wrapper">Enable Inline Button
						<span class="dashicons dashicons-editor-help" data-title="Display social share buttons inline above or below the post content."></span>
					</div>

				</th>
				<td>
					<div class="checkbox-radio">
						<label>
							<input type="checkbox" name="lightshare_options[inline][enabled]" value="1" class="inline-button-toggle" <?php checked(LS_Options::get_option('inline.enabled'), '1'); ?> />
						</label>
					</div>
				</td>
			</tr>
		</table>

		<div class="inline-button-settings" style="display: <?php echo LS_Options::get_option('inline.enabled') ? 'block' : 'none'; ?>;">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<div class="lightshare-title-wrapper">Placement
							<span class="dashicons dashicons-editor-help" data-title="Choose whether to display the share buttons before or after the content."></span>
						</div>
					</th>
					<td>
						<select name="lightshare_options[inline][position]">
							<option value="before" <?php selected(LS_Options::get_option('inline.position', 'after'), 'before'); ?>>Before Content</option>
							<option value="after" <?php selected(LS_Options::get_option('inline.position', 'after'), 'after'); ?>>After Content</option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<div class="lightshare-title-wrapper">Post Types
							<span class="dashicons dashicons-editor-help" data-title="Select which post types should display the inline share buttons."></span>
						</div>
					</th>
					<td>
						<div class="lightshare-checkbox-group">
							<?php foreach ($post_types as $post_type) :
								$post_type_name = $post_type->name;
								$is_checked = in_array($post_type_name, LS_Options::get_option('inline.post_types', array('post')));
							?>
								<label class="lightshare-checkbox">
									<input type="checkbox"
										name="lightshare_options[inline][post_types][]"
										value="<?php echo esc_attr($post_type_name); ?>"
										<?php checked($is_checked); ?>>
									<?php echo esc_html($post_type->labels->singular_name); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
