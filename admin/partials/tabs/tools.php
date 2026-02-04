<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Lightshare\LS_Options;

?>

<div id="<?php echo esc_attr($tab_id); ?>" class="tab-pane">
	<h2 class="content-title"><span class="dashicons dashicons-admin-tools"></span> Tools</h2>
	<div class="lightshare-card">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<div class="lightshare-title-wrapper">Reset Settings
						<span class="dashicons dashicons-editor-help" data-title="Reset all plugin settings to their default values"></span>
					</div>

				</th>
				<td>
					<div class="checkbox-radio">
						<button type="button" class="button" id="lightshare-reset-settings">Reset Settings</button>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<div class="lightshare-title-wrapper">Reset Share Counts
						<span class="dashicons dashicons-editor-help" data-title="Remove all stored share counts from posts."></span>
					</div>

				</th>
				<td>
					<div class="checkbox-radio">
						<button type="button" class="button" id="lightshare-reset-counts">Reset Counts</button>
					</div>
					<p class="description">This will clear all stored share totals for all posts.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<div class="lightshare-title-wrapper">Clean Deactivate
						<span class="dashicons dashicons-editor-help" data-title="When enabled, all Lightshare settings and data will be deleted from the database when the plugin is deactivated!"></span>
					</div>
				</th>
				<td>
					<div class="checkbox-radio">
						<label>
							<input type="checkbox" name="lightshare_options[tools][clean_deactivate]" value="1" <?php checked(LS_Options::get_option('tools.clean_deactivate'), '1'); ?> />
						</label>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<div class="lightshare-title-wrapper">Clean Uninstall
						<span class="dashicons dashicons-editor-help" data-title="When enabled, all Lightshare settings and data will be deleted from the database when the plugin is uninstalled!"></span>
					</div>
				</th>
				<td>
					<div class="checkbox-radio">
						<label>
							<input type="checkbox" name="lightshare_options[tools][clean_uninstall]" value="1" <?php checked(LS_Options::get_option('tools.clean_uninstall'), '1'); ?> />
						</label>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>
