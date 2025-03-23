class LightshareAdmin {
	constructor($) {
		this.$ = $;
		this.init();
	}

	init() {
		this.initializeTabs();
		this.setupEventHandlers();
		this.setupResetSettings();
	}

	setupResetSettings() {
		this.$("#lightshare-reset-settings").on(
			"click",
			this.handleResetSettings.bind(this)
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

		this.$.ajax({
			url: lightshare_admin.ajax_url,
			type: "POST",
			data: {
				action: "lightshare_reset_settings",
				nonce: lightshare_admin.nonce
			},
			success: response => {
				if (response.success) {
					alert("Settings reset successfully. The page will now reload.");
					location.reload();
				} else {
					alert("Failed to reset settings. Please try again.");
				}
			},
			error: () => alert("An error occurred. Please try again.")
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
				const message = response.success
					? response.data
					: response.data || "Failed to save settings. Please try again.";
				this.showNotice(message, response.success ? "success" : "error");
			},
			error: () => {
				this.hideLoadingIndicator();
				this.showNotice("An error occurred. Please try again.", "error");
			}
		});
	}

	showNotice(message, type) {
		this.$(".lightshare-inline-notice").remove();
		const notice = this.$(`
			<span class="lightshare-inline-notice notice-${
				type === "success" ? "success" : "error"
			}">
				${message}
			</span>
		`);
		this.$("#submit").after(notice);
		setTimeout(() => notice.fadeOut(300, () => notice.remove()), 3000);
	}

	showLoadingIndicator() {
		this.$("#submit").val("Saving...");
	}

	hideLoadingIndicator() {
		this.$("#submit").val("Save Changes");
	}

	initializeTabs() {
		const urlParams = new URLSearchParams(window.location.search);
		this.setActiveTab(urlParams.get("tab") || "general");
	}

	setActiveTab(tab) {
		const { $ } = this;
		$(".nav-tab-wrapper a").removeClass("nav-tab-active");
		$(`.nav-tab-wrapper a[href="#${tab}"]`).addClass("nav-tab-active");
		$(".tab-content > div").hide();
		$(`#${tab}`).show();
		$("#lightshare_active_tab").val(tab);
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
