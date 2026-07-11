<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Lightshare\LS_Options;

// Get all registered post types
$lightshare_post_types = get_post_types(array('public' => true), 'objects');
$lightshare_floating_enabled = (bool) LS_Options::get_option('floating.enabled');
?>

<div id="<?php echo esc_attr($lightshare_tab_id); ?>" class="tab-pane" role="tabpanel">
	<div class="ls-stack">
		<section class="ls-card">
			<header class="ls-card__header">
				<div class="ls-card__header-main">
					<div class="ls-card__icon"><span class="dashicons dashicons-share-alt" aria-hidden="true"></span></div>
					<div>
						<h2 class="ls-card__title"><?php esc_html_e('Floating Button', 'lightshare-social-sharing'); ?></h2>
						<p class="ls-card__desc"><?php esc_html_e('Show share buttons in a floating sidebar as visitors scroll.', 'lightshare-social-sharing'); ?></p>
					</div>
				</div>
				<div class="ls-switch checkbox-radio">
					<label>
						<input type="checkbox" name="lightshare_options[floating][enabled]" value="1" class="floating-button-toggle" <?php checked($lightshare_floating_enabled, true); ?>>
					</label>
					<span class="ls-switch__label"><?php esc_html_e('Enabled', 'lightshare-social-sharing'); ?></span>
				</div>
			</header>

			<div class="floating-button-settings" style="display: <?php echo $lightshare_floating_enabled ? 'block' : 'none'; ?>;">
				<div class="ls-card__body">
					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<span class="ls-label"><?php esc_html_e('Post Types', 'lightshare-social-sharing'); ?></span>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Select which post types should display the floating share buttons', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
							<p class="ls-field__hint"><?php esc_html_e('Only public post types are listed.', 'lightshare-social-sharing'); ?></p>
						</div>
						<div class="ls-field__control">
							<div class="ls-chip-group lightshare-checkbox-group">
								<?php foreach ($lightshare_post_types as $post_type) :
									$lightshare_post_type_name = $post_type->name;
									$lightshare_is_checked = in_array($lightshare_post_type_name, LS_Options::get_option('floating.post_types', array('post', 'page')), true);
									?>
									<label class="ls-chip lightshare-checkbox">
										<input type="checkbox"
											name="lightshare_options[floating][post_types][]"
											value="<?php echo esc_attr($lightshare_post_type_name); ?>"
											<?php checked($lightshare_is_checked); ?>>
										<?php echo esc_html($post_type->labels->singular_name); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<label for="lightshare-floating-alignment"><?php esc_html_e('Button Alignment', 'lightshare-social-sharing'); ?></label>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Choose the alignment of the floating share buttons.', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
						</div>
						<div class="ls-field__control">
							<select id="lightshare-floating-alignment" name="lightshare_options[floating][button_alignment]">
								<option value="left" <?php selected(LS_Options::get_option('floating.button_alignment'), 'left'); ?>><?php esc_html_e('Left', 'lightshare-social-sharing'); ?></option>
								<option value="right" <?php selected(LS_Options::get_option('floating.button_alignment'), 'right'); ?>><?php esc_html_e('Right', 'lightshare-social-sharing'); ?></option>
							</select>
						</div>
					</div>

					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<label for="lightshare-floating-size"><?php esc_html_e('Button Size', 'lightshare-social-sharing'); ?></label>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Choose the size of the floating share buttons.', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
						</div>
						<div class="ls-field__control">
							<select id="lightshare-floating-size" name="lightshare_options[floating][button_size]">
								<option value="small" <?php selected(LS_Options::get_option('floating.button_size'), 'small'); ?>><?php esc_html_e('Small', 'lightshare-social-sharing'); ?></option>
								<option value="medium" <?php selected(LS_Options::get_option('floating.button_size'), 'medium'); ?>><?php esc_html_e('Medium', 'lightshare-social-sharing'); ?></option>
								<option value="large" <?php selected(LS_Options::get_option('floating.button_size'), 'large'); ?>><?php esc_html_e('Large', 'lightshare-social-sharing'); ?></option>
							</select>
						</div>
					</div>

					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<label for="lightshare-hide-on-mobile"><?php esc_html_e('Hide on Mobile', 'lightshare-social-sharing'); ?></label>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Hide floating share buttons on mobile devices (up to 768px).', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
						</div>
						<div class="ls-field__control">
							<div class="ls-switch checkbox-radio">
								<label>
									<input id="lightshare-hide-on-mobile" type="checkbox" name="lightshare_options[floating][hide_on_mobile]" value="1" <?php checked(LS_Options::get_option('floating.hide_on_mobile'), '1'); ?>>
								</label>
							</div>
						</div>
					</div>

					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<label for="lightshare-mobile-position"><?php esc_html_e('Mobile Position', 'lightshare-social-sharing'); ?></label>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Choose floating share button position on mobile devices.', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
						</div>
						<div class="ls-field__control">
							<select id="lightshare-mobile-position" name="lightshare_options[floating][mobile_position]">
								<option value="bottom" <?php selected(LS_Options::get_option('floating.mobile_position', 'bottom'), 'bottom'); ?>><?php esc_html_e('Bottom', 'lightshare-social-sharing'); ?></option>
								<option value="left" <?php selected(LS_Options::get_option('floating.mobile_position', 'bottom'), 'left'); ?>><?php esc_html_e('Left', 'lightshare-social-sharing'); ?></option>
								<option value="right" <?php selected(LS_Options::get_option('floating.mobile_position', 'bottom'), 'right'); ?>><?php esc_html_e('Right', 'lightshare-social-sharing'); ?></option>
							</select>
						</div>
					</div>

					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<label for="lightshare-scroll-offset"><?php esc_html_e('Show After Scroll', 'lightshare-social-sharing'); ?></label>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Show floating buttons only after this scroll amount (e.g. 300px or 25%).', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
							<p class="ls-field__hint"><?php esc_html_e('Leave empty to show immediately.', 'lightshare-social-sharing'); ?></p>
						</div>
						<div class="ls-field__control">
							<input
								id="lightshare-scroll-offset"
								type="text"
								name="lightshare_options[floating][scroll_offset]"
								value="<?php echo esc_attr(LS_Options::get_option('floating.scroll_offset', '')); ?>"
								placeholder="<?php esc_attr_e('300px or 25%', 'lightshare-social-sharing'); ?>"
							/>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>
