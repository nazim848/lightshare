<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Lightshare\LS_Options;

?>

<div id="<?php echo esc_attr($lightshare_tab_id); ?>" class="tab-pane" role="tabpanel">
	<div class="ls-stack">
		<section class="ls-card">
			<header class="ls-card__header">
				<div class="ls-card__header-main">
					<div class="ls-card__icon"><span class="dashicons dashicons-admin-tools" aria-hidden="true"></span></div>
					<div>
						<h2 class="ls-card__title"><?php esc_html_e('Maintenance', 'lightshare-social-sharing'); ?></h2>
						<p class="ls-card__desc"><?php esc_html_e('Reset plugin data when you need a clean slate.', 'lightshare-social-sharing'); ?></p>
					</div>
				</div>
			</header>
			<div class="ls-card__body">
				<div class="ls-field">
					<div class="ls-field__label">
						<div class="ls-field__label-row">
							<span class="ls-label"><?php esc_html_e('Reset Settings', 'lightshare-social-sharing'); ?></span>
							<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Reset all plugin settings to their default values', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
						</div>
						<p class="ls-field__hint"><?php esc_html_e('Restores every option to factory defaults. This cannot be undone.', 'lightshare-social-sharing'); ?></p>
					</div>
					<div class="ls-field__control">
						<button type="button" class="ls-btn ls-btn--danger" id="lightshare-reset-settings">
							<?php esc_html_e('Reset Settings', 'lightshare-social-sharing'); ?>
						</button>
					</div>
				</div>

				<div class="ls-field">
					<div class="ls-field__label">
						<div class="ls-field__label-row">
							<span class="ls-label"><?php esc_html_e('Reset Share Counts', 'lightshare-social-sharing'); ?></span>
							<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Remove all stored share counts from posts.', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
						</div>
						<p class="ls-field__hint"><?php esc_html_e('Clears stored share totals for all posts.', 'lightshare-social-sharing'); ?></p>
					</div>
					<div class="ls-field__control">
						<button type="button" class="ls-btn ls-btn--danger" id="lightshare-reset-counts">
							<?php esc_html_e('Reset Counts', 'lightshare-social-sharing'); ?>
						</button>
					</div>
				</div>
			</div>
		</section>

		<section class="ls-card ls-card--danger">
			<header class="ls-card__header">
				<div class="ls-card__header-main">
					<div class="ls-card__icon"><span class="dashicons dashicons-warning" aria-hidden="true"></span></div>
					<div>
						<h2 class="ls-card__title"><?php esc_html_e('Data Cleanup', 'lightshare-social-sharing'); ?></h2>
						<p class="ls-card__desc"><?php esc_html_e('Control what happens to Lightshare data when the plugin is turned off.', 'lightshare-social-sharing'); ?></p>
					</div>
				</div>
			</header>
			<div class="ls-card__body">
				<div class="ls-callout ls-callout--warning" style="margin: 12px 0 4px;">
					<?php esc_html_e('These options permanently delete settings and stored data. Leave them off unless you intend to remove Lightshare completely.', 'lightshare-social-sharing'); ?>
				</div>

				<div class="ls-field">
					<div class="ls-field__label">
						<div class="ls-field__label-row">
							<label for="lightshare-clean-deactivate"><?php esc_html_e('Clean Deactivate', 'lightshare-social-sharing'); ?></label>
							<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('When enabled, all Lightshare settings and data will be deleted from the database when the plugin is deactivated!', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
						</div>
						<p class="ls-field__hint"><?php esc_html_e('Delete all plugin data on deactivation.', 'lightshare-social-sharing'); ?></p>
					</div>
					<div class="ls-field__control">
						<div class="ls-switch checkbox-radio">
							<label>
								<input id="lightshare-clean-deactivate" type="checkbox" name="lightshare_options[tools][clean_deactivate]" value="1" <?php checked(LS_Options::get_option('tools.clean_deactivate'), '1'); ?> />
							</label>
						</div>
					</div>
				</div>

				<div class="ls-field">
					<div class="ls-field__label">
						<div class="ls-field__label-row">
							<label for="lightshare-clean-uninstall"><?php esc_html_e('Clean Uninstall', 'lightshare-social-sharing'); ?></label>
							<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('When enabled, all Lightshare settings and data will be deleted from the database when the plugin is uninstalled!', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
						</div>
						<p class="ls-field__hint"><?php esc_html_e('Delete all plugin data on uninstall.', 'lightshare-social-sharing'); ?></p>
					</div>
					<div class="ls-field__control">
						<div class="ls-switch checkbox-radio">
							<label>
								<input id="lightshare-clean-uninstall" type="checkbox" name="lightshare_options[tools][clean_uninstall]" value="1" <?php checked(LS_Options::get_option('tools.clean_uninstall'), '1'); ?> />
							</label>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>
