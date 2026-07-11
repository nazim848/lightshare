<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Verify user capabilities
if (!current_user_can('manage_options')) {
	wp_die(
		esc_html__('You do not have sufficient permissions to access this page.', 'lightshare-social-sharing'),
		'',
		array('response' => 403)
	);
}

// Define tabs
$tabs = array(
	'share-button'    => array(
		'label' => __('Share Button', 'lightshare-social-sharing'),
		'icon'  => 'dashicons-share',
		'desc'  => __('Networks & appearance', 'lightshare-social-sharing'),
	),
	'floating-button' => array(
		'label' => __('Floating Button', 'lightshare-social-sharing'),
		'icon'  => 'dashicons-share-alt',
		'desc'  => __('Sticky sidebar shares', 'lightshare-social-sharing'),
	),
	'inline-button'   => array(
		'label' => __('Inline Button', 'lightshare-social-sharing'),
		'icon'  => 'dashicons-align-left',
		'desc'  => __('Before / after content', 'lightshare-social-sharing'),
	),
	'tools'           => array(
		'label' => __('Tools', 'lightshare-social-sharing'),
		'icon'  => 'dashicons-admin-tools',
		'desc'  => __('Reset & data cleanup', 'lightshare-social-sharing'),
	),
);

// Get current tab
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab switching is handled client-side via JavaScript
$lightshare_active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'share-button';

// Ensure the active tab is valid
if (!array_key_exists($lightshare_active_tab, $tabs)) {
	$lightshare_active_tab = 'share-button';
}

$lightshare_active_label = $tabs[$lightshare_active_tab]['label'];
?>

<div class="wrap ls-admin">
	<div class="ls-dashboard">
		<aside class="ls-sidebar">
			<div class="ls-sidebar__brand">
				<div class="ls-logo" aria-hidden="true"></div>
				<span class="ls-version">v<?php echo esc_html(LIGHTSHARE_VERSION); ?></span>
			</div>

			<p class="ls-sidebar__label"><?php esc_html_e('Settings', 'lightshare-social-sharing'); ?></p>

			<div class="ls-nav-scroll">
				<nav class="ls-nav nav-tab-wrapper" role="tablist" aria-label="<?php esc_attr_e('Lightshare settings sections', 'lightshare-social-sharing'); ?>">
					<?php foreach ($tabs as $lightshare_tab_id => $lightshare_tab) :
						$lightshare_is_active = ($lightshare_tab_id === $lightshare_active_tab);
						$lightshare_class = $lightshare_is_active ? ' is-active nav-tab-active' : '';
					?>
						<a
							href="#<?php echo esc_attr($lightshare_tab_id); ?>"
							class="ls-nav__tab nav-tab<?php echo esc_attr($lightshare_class); ?>"
							role="tab"
							aria-selected="<?php echo $lightshare_is_active ? 'true' : 'false'; ?>"
							data-tab="<?php echo esc_attr($lightshare_tab_id); ?>">
							<span class="ls-nav__icon">
								<span class="dashicons <?php echo esc_attr($lightshare_tab['icon']); ?>" aria-hidden="true"></span>
							</span>
							<span class="ls-nav__text">
								<span class="ls-nav__title"><?php echo esc_html($lightshare_tab['label']); ?></span>
								<span class="ls-nav__desc"><?php echo esc_html($lightshare_tab['desc']); ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
		</aside>

		<div class="ls-main">
			<header class="ls-header">
				<div class="ls-header__meta">
					<h1 id="ls-page-title"><?php echo esc_html($lightshare_active_label); ?></h1>
				</div>
				<div class="ls-header__actions">
					<span id="ls-save-status" class="ls-save-status" role="status" aria-live="polite"></span>
					<button type="submit" form="lightshare-settings-form" id="submit" name="submit" class="ls-btn ls-btn--primary" disabled data-ls-save-label="<?php esc_attr_e('Save Changes', 'lightshare-social-sharing'); ?>" data-ls-saving-label="<?php esc_attr_e('Saving…', 'lightshare-social-sharing'); ?>" data-ls-saved-label="<?php esc_attr_e('Saved', 'lightshare-social-sharing'); ?>">
						<?php esc_html_e('Save Changes', 'lightshare-social-sharing'); ?>
					</button>
				</div>
			</header>

			<form id="lightshare-settings-form" method="post">
				<?php settings_fields('lightshare_options'); ?>
				<?php do_settings_sections('lightshare_options'); ?>
				<?php wp_nonce_field('lightshare_options_verify', 'lightshare_nonce'); ?>
				<input type="hidden" id="lightshare_active_tab" name="lightshare_active_tab" value="<?php echo esc_attr($lightshare_active_tab); ?>">

				<div class="tab-content">
					<?php
					foreach ($tabs as $lightshare_tab_id => $lightshare_tab) {
						$lightshare_tab_file = plugin_dir_path(__FILE__) . 'tabs/' . $lightshare_tab_id . '.php';
						if (file_exists($lightshare_tab_file)) {
							include $lightshare_tab_file;
						} else {
							echo '<p>' . esc_html__('Tab content not found.', 'lightshare-social-sharing') . '</p>';
						}
					}
					?>
				</div>
			</form>
		</div>
	</div>

	<div class="ls-mobile-save">
		<button type="submit" form="lightshare-settings-form" class="ls-btn ls-btn--primary ls-mobile-save-btn" disabled data-ls-save-label="<?php esc_attr_e('Save Changes', 'lightshare-social-sharing'); ?>" data-ls-saving-label="<?php esc_attr_e('Saving…', 'lightshare-social-sharing'); ?>" data-ls-saved-label="<?php esc_attr_e('Saved', 'lightshare-social-sharing'); ?>">
			<?php esc_html_e('Save Changes', 'lightshare-social-sharing'); ?>
		</button>
	</div>

	<div id="ls-toast" class="ls-toast" role="status" aria-live="polite" hidden></div>

	<div id="ls-modal" class="ls-modal" hidden aria-hidden="true">
		<div class="ls-modal__backdrop" data-ls-modal-close></div>
		<div class="ls-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ls-modal-title">
			<h2 id="ls-modal-title" class="ls-modal__title"></h2>
			<p id="ls-modal-body" class="ls-modal__body"></p>
			<div class="ls-modal__actions">
				<button type="button" class="ls-btn ls-btn--secondary" data-ls-modal-close>
					<?php esc_html_e('Cancel', 'lightshare-social-sharing'); ?>
				</button>
				<button type="button" id="ls-modal-confirm" class="ls-btn ls-btn--danger-solid">
					<?php esc_html_e('Confirm', 'lightshare-social-sharing'); ?>
				</button>
			</div>
		</div>
	</div>
</div>