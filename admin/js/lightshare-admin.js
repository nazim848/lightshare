class LightshareAdmin {
	constructor($) {
		this.$ = $;
		this.init();
	}

	init() {
		this.initializeTabs();
		this.setupEventHandlers();
		this.setupResetSettings();
		this.setupResetCounts();
		this.setupPreview();
		this.initializeSortable();
	}

	setupResetSettings() {
		this.$("#lightshare-reset-settings").on(
			"click",
			this.handleResetSettings.bind(this)
		);
	}

	setupResetCounts() {
		this.$("#lightshare-reset-counts").on(
			"click",
			this.handleResetCounts.bind(this)
		);
	}

	handleResetSettings(e) {
		e.preventDefault();
		if (
			!confirm(
				"Are you sure you want to reset all Lightshare settings? This action cannot be undone."
			)
		) {
			return;
		}

		this.showLoadingIndicator();
		this.$.ajax({
			url: lightshare_admin.ajax_url,
			type: "POST",
			data: {
				action: "lightshare_reset_settings",
				nonce: lightshare_admin.nonce
			},
			success: response => {
				this.hideLoadingIndicator();
				if (response.success) {
					this.showNotice(
						"Settings reset successfully. The page will now reload.",
						"success"
					);
					setTimeout(() => location.reload(), 1500);
				} else {
					this.showNotice(
						response.data?.message ||
							"Failed to reset settings. Please try again.",
						"error"
					);
				}
			},
			error: () => {
				this.hideLoadingIndicator();
				this.showNotice("An error occurred. Please try again.", "error");
			}
		});
	}

	updateQueryStringParameter(uri, key, value) {
		const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
		const separator = uri.indexOf("?") !== -1 ? "&" : "?";
		return uri.match(re)
			? uri.replace(re, "$1" + key + "=" + value + "$2")
			: uri + separator + key + "=" + value;
	}

	setupEventHandlers() {
		this.$(".nav-tab-wrapper a").on("click", this.handleTabClick.bind(this));
		this.$("form").on("submit", this.handleFormSubmit.bind(this));
		this.$(".floating-button-toggle").on(
			"change",
			this.handleFloatingButtonToggle.bind(this)
		);
		this.$(".inline-button-toggle").on(
			"change",
			this.handleInlineButtonToggle.bind(this)
		);
		this.$("select[name='lightshare_options[share][style]']").on(
			"change",
			this.updatePreview.bind(this)
		);
		this.$("select[name='lightshare_options[share][color_theme]']").on(
			"change",
			this.updatePreview.bind(this)
		);
		this.$("input[name='lightshare_options[share][show_label]']").on(
			"change",
			this.updatePreview.bind(this)
		);
		this.$("input[name='lightshare_options[share][label_text]']").on(
			"input",
			this.updatePreview.bind(this)
		);
	}

	handleFloatingButtonToggle(e) {
		const isChecked = e.target.checked;
		this.$(".floating-button-settings").slideToggle(
			isChecked ? "fast" : "fast"
		);
	}

	handleInlineButtonToggle(e) {
		const isChecked = e.target.checked;
		this.$(".inline-button-settings").slideToggle(
			isChecked ? "fast" : "fast"
		);
	}

	handleResetCounts(e) {
		e.preventDefault();
		if (
			!confirm(
				"Are you sure you want to reset all share counts? This action cannot be undone."
			)
		) {
			return;
		}

		this.showLoadingIndicator();
		this.$.ajax({
			url: lightshare_admin.ajax_url,
			type: "POST",
			data: {
				action: "lightshare_reset_counts",
				nonce: lightshare_admin.nonce
			},
			success: response => {
				this.hideLoadingIndicator();
				if (response.success) {
					this.showNotice(
						response.data?.message ||
							"Share counts reset successfully.",
						"success"
					);
				} else {
					this.showNotice(
						response.data?.message ||
							"Failed to reset counts. Please try again.",
						"error"
					);
				}
			},
			error: () => {
				this.hideLoadingIndicator();
				this.showNotice("An error occurred. Please try again.", "error");
			}
		});
	}

	handleTabClick(e) {
		e.preventDefault();
		const target = this.$(e.currentTarget).attr("href").substr(1);
		this.setActiveTab(target);
		history.pushState(
			null,
			null,
			this.updateQueryStringParameter(window.location.href, "tab", target)
		);
	}

	handleFormSubmit(e) {
		e.preventDefault();
		const form = this.$(e.currentTarget);
		const formData = new FormData(form[0]);

		// Add unchecked checkboxes
		form.find("input[type=checkbox]:not(:checked)").each(function () {
			formData.append(this.name, "0");
		});

		formData.append("action", "lightshare_save_settings");
		formData.append("lightshare_nonce", lightshare_admin.nonce);

		this.showLoadingIndicator();

		this.$.ajax({
			url: lightshare_admin.ajax_url,
			type: "POST",
			data: formData,
			processData: false,
			contentType: false,
			success: response => {
				this.hideLoadingIndicator();
				if (response.success) {
					this.showNotice(response.data || "Settings saved.", "success");
				} else {
					this.showNotice(
						response.data?.message ||
							"Failed to save settings. Please try again.",
						"error"
					);
				}
			},
			error: () => {
				this.hideLoadingIndicator();
				this.showNotice(
					"An error occurred while saving. Please try again.",
					"error"
				);
			}
		});
	}

	showNotice(message, type) {
		this.$(".lightshare-inline-notice").remove();
		const notice = this.$(`
			<div class="lightshare-inline-notice notice-${type}">
				${message}
			</div>
		`);
		this.$("#submit").after(notice);

		// Auto-dismiss after 3 seconds
		setTimeout(() => {
			notice.fadeOut(300, () => notice.remove());
		}, 3000);

		// Handle manual dismiss
		notice.find(".notice-dismiss").on("click", () => {
			notice.fadeOut(300, () => notice.remove());
		});
	}

	showLoadingIndicator() {
		this.$("#submit").val("Saving...");
	}

	hideLoadingIndicator() {
		this.$("#submit").val("Save Changes");
	}

	initializeTabs() {
		const urlParams = new URLSearchParams(window.location.search);
		this.setActiveTab(urlParams.get("tab") || "share-button");
	}

	setActiveTab(tab) {
		const { $ } = this;
		$(".nav-tab-wrapper a").removeClass("nav-tab-active");
		$(`.nav-tab-wrapper a[href="#${tab}"]`).addClass("nav-tab-active");
		$(".tab-content > div").hide();
		$(`#${tab}`).show();
		$("#lightshare_active_tab").val(tab);
	}

	initializeSortable() {
		const { $ } = this;
		$(".lightshare-social-networks").sortable({
			items: "li", // Allow sorting of all items
			opacity: 0.6,
			cursor: "move",
			update: (event, ui) => {
				// Update the hidden input with the new order of all buttons
				const networks = [];
				$(".lightshare-social-networks li").each((index, element) => {
					networks.push($(element).data("network"));
				});
				$("#lightshare_social_networks_order").val(
					JSON.stringify(networks)
				);
				this.updatePreview();
			}
		});

		// Handle checkbox changes
		$('.lightshare-social-networks input[type="checkbox"]').on(
			"change",
			e => {
				const $li = $(e.currentTarget).closest("li");
				const $label = $(e.currentTarget).closest("label");

				if (e.currentTarget.checked) {
					$li.addClass("active");
					$label.addClass("active");
				} else {
					$li.removeClass("active");
					$label.removeClass("active");
				}

				// Update the order after checkbox change
				const networks = [];
				$(".lightshare-social-networks li").each((index, element) => {
					networks.push($(element).data("network"));
				});
				$("#lightshare_social_networks_order").val(
					JSON.stringify(networks)
				);
				this.updatePreview();
			}
		);
	}

	setupPreview() {
		if (!this.$("#lightshare-preview").length) {
			return;
		}
		this.updatePreview();
	}

	updatePreview() {
		const $preview = this.$("#lightshare-preview");
		if (!$preview.length) {
			return;
		}

		const activeNetworks = [];
		this.$(".lightshare-social-networks li").each((index, element) => {
			const $li = this.$(element);
			const isChecked = $li.find("input[type=checkbox]").is(":checked");
			if (isChecked) {
				activeNetworks.push($li.data("network"));
			}
		});

		const style = this.$(
			"select[name='lightshare_options[share][style]']"
		).val();
		const showLabel = this.$(
			"input[name='lightshare_options[share][show_label]']"
		).is(":checked");
		const labelText = this.$(
			"input[name='lightshare_options[share][label_text]']"
		).val();
		const colorTheme = this.$(
			"select[name='lightshare_options[share][color_theme]']"
		).val();

		this.$.ajax({
			url: lightshare_admin.ajax_url,
			type: "POST",
			data: {
				action: "lightshare_preview_buttons",
				nonce: lightshare_admin.nonce,
				networks: activeNetworks.join(","),
				style: style || "",
				color_theme: colorTheme || "",
				show_label: showLabel ? 1 : 0,
				label_text: labelText || ""
			},
			success: response => {
				if (response.success && response.data?.html) {
					if (response.data?.css) {
						const styleId = "lightshare-preview-theme";
						let styleTag = document.getElementById(styleId);
						if (!styleTag) {
							styleTag = document.createElement("style");
							styleTag.id = styleId;
							document.head.appendChild(styleTag);
						}
						styleTag.textContent = response.data.css;
					}
					$preview.html(response.data.html);
				}
			}
		});
	}
}

// Initialize the admin functionality
jQuery($ => {
	const lightshareAdmin = new LightshareAdmin($);
	window.setActiveTab = tab => lightshareAdmin.setActiveTab(tab);

	// Show submit button with a slight delay to prevent flash
	setTimeout(() => {
		const submitButton = document.getElementById("submit-button");
		if (submitButton) submitButton.style.display = "block";
	}, 50);
});
