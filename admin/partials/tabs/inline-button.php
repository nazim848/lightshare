<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Lightshare\LS_Options;

// Get all registered post types
$lightshare_post_types = get_post_types(array('public' => true), 'objects');
$lightshare_inline_enabled = (bool) LS_Options::get_option('inline.enabled');
?>

<div id="<?php echo esc_attr($lightshare_tab_id); ?>" class="tab-pane" role="tabpanel">
	<div class="ls-stack">
		<section class="ls-card">
			<header class="ls-card__header">
				<div class="ls-card__header-main">
					<div class="ls-card__icon"><span class="dashicons dashicons-align-left" aria-hidden="true"></span></div>
					<div>
						<h2 class="ls-card__title"><?php esc_html_e('Inline Button', 'lightshare-social-sharing'); ?></h2>
						<p class="ls-card__desc"><?php esc_html_e('Place share buttons before or after post content automatically.', 'lightshare-social-sharing'); ?></p>
					</div>
				</div>
				<div class="ls-switch checkbox-radio">
					<label>
						<input type="checkbox" name="lightshare_options[inline][enabled]" value="1" class="inline-button-toggle" <?php checked($lightshare_inline_enabled, true); ?> />
					</label>
					<span class="ls-switch__label"><?php esc_html_e('Enabled', 'lightshare-social-sharing'); ?></span>
				</div>
			</header>

			<div class="inline-button-settings" style="display: <?php echo $lightshare_inline_enabled ? 'block' : 'none'; ?>;">
				<div class="ls-card__body">
					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<label for="lightshare-inline-position"><?php esc_html_e('Placement', 'lightshare-social-sharing'); ?></label>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Choose whether to display the share buttons before or after the content.', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
						</div>
						<div class="ls-field__control">
							<select id="lightshare-inline-position" name="lightshare_options[inline][position]">
								<option value="before" <?php selected(LS_Options::get_option('inline.position', 'after'), 'before'); ?>><?php esc_html_e('Before Content', 'lightshare-social-sharing'); ?></option>
								<option value="after" <?php selected(LS_Options::get_option('inline.position', 'after'), 'after'); ?>><?php esc_html_e('After Content', 'lightshare-social-sharing'); ?></option>
							</select>
						</div>
					</div>

					<div class="ls-field">
						<div class="ls-field__label">
							<div class="ls-field__label-row">
								<span class="ls-label"><?php esc_html_e('Post Types', 'lightshare-social-sharing'); ?></span>
								<button type="button" class="ls-help" data-tooltip="<?php esc_attr_e('Select which post types should display the inline share buttons.', 'lightshare-social-sharing'); ?>" aria-label="<?php esc_attr_e('Help', 'lightshare-social-sharing'); ?>">?</button>
							</div>
							<p class="ls-field__hint"><?php esc_html_e('Only public post types are listed.', 'lightshare-social-sharing'); ?></p>
						</div>
						<div class="ls-field__control">
							<div class="ls-chip-group lightshare-checkbox-group">
								<?php foreach ($lightshare_post_types as $post_type) :
									$lightshare_post_type_name = $post_type->name;
									$lightshare_is_checked = in_array($lightshare_post_type_name, LS_Options::get_option('inline.post_types', array('post')), true);
									?>
									<label class="ls-chip lightshare-checkbox">
										<input type="checkbox"
											name="lightshare_options[inline][post_types][]"
											value="<?php echo esc_attr($lightshare_post_type_name); ?>"
											<?php checked($lightshare_is_checked); ?>>
										<?php echo esc_html($post_type->labels->singular_name); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>
