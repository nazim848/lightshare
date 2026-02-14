class LightshareAdmin {
	// Animation timing constants.
	static ANIMATION_DURATION = 150;
	static ANIMATION_CLEANUP_DELAY = 160;
	static NOTICE_DISPLAY_DURATION = 3000;
	static NOTICE_FADE_DURATION = 300;
	static PREVIEW_DEBOUNCE_DELAY = 400;
	static RELOAD_DELAY = 1500;

	constructor() {
		this.draggedItem = null;
		this._dragRafPending = false;
		this.cacheElements();
		this.init();
	}

	// Cache frequently accessed DOM elements.
	cacheElements() {
		this.submitButton = document.getElementById("submit");
		this.previewContainer = document.getElementById("lightshare-preview");
		this.styleSelect = document.querySelector(
			"select[name='lightshare_options[share][style]']"
		);
		this.colorThemeSelect = document.querySelector(
			"select[name='lightshare_options[share][color_theme]']"
		);
		this.showLabelInput = document.querySelector(
			"input[name='lightshare_options[share][show_label]']"
		);
		this.labelTextInput = document.querySelector(
			"input[name='lightshare_options[share][label_text]']"
		);
		this.nudgeTextInput = document.querySelector(
			"input[name='lightshare_options[share][nudge_text]']"
		);
		this.showCountsInput = document.querySelector(
			"input[name='lightshare_options[share][show_counts]']"
		);
		this.utmEnabledInput = document.querySelector(
			"input[name='lightshare_options[share][utm_enabled]']"
		);
	}

	// Bootstrap all admin UI features.
	init() {
		this.initializeTabs();
		this.setupEventHandlers();
		this.setupResetSettings();
		this.setupResetCounts();
		this.setupPreview();
		this.syncConditionalFields({ animate: false });
		this.initializeSortable();
	}

	// Create a debounced version of a function.
	debounce(fn, delay = LightshareAdmin.PREVIEW_DEBOUNCE_DELAY) {
		let timer;
		return (...args) => {
			clearTimeout(timer);
			timer = setTimeout(() => fn.apply(this, args), delay);
		};
	}

	// Send a POST request with URL-encoded data.
	postAjax(data) {
		return fetch(lightshare_admin.ajax_url, {
			method: "POST",
			credentials: "same-origin",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
			},
			body: new URLSearchParams(data)
		}).then(response => response.json());
	}

	// Send a POST request with FormData (multipart).
	postFormData(formData) {
		return fetch(lightshare_admin.ajax_url, {
			method: "POST",
			credentials: "same-origin",
			body: formData
		}).then(response => response.json());
	}

	// Bind click handler for the "Reset Settings" button.
	setupResetSettings() {
		const button = document.getElementById("lightshare-reset-settings");
		if (button) {
			button.addEventListener("click", this.handleResetSettings.bind(this));
		}
	}

	// Bind click handler for the "Reset Counts" button.
	setupResetCounts() {
		const button = document.getElementById("lightshare-reset-counts");
		if (button) {
			button.addEventListener("click", this.handleResetCounts.bind(this));
		}
	}

	// Generic handler for reset-type AJAX actions with confirmation.
	handleResetAction(e, action, confirmMessage, successMessage, onSuccess) {
		e.preventDefault();

		if (!confirm(confirmMessage)) {
			return;
		}

		const button = e.currentTarget;
		button.disabled = true;

		this.showLoadingIndicator();
		this.postAjax({
			action,
			nonce: lightshare_admin.nonce
		})
			.then(response => {
				this.hideLoadingIndicator();
				button.disabled = false;

				if (response.success) {
					this.showNotice(
						(response.data && response.data.message) || successMessage,
						"success"
					);
					if (onSuccess) {
						onSuccess(response);
					}
				} else {
					this.showNotice(
						(response.data && response.data.message) ||
							"Operation failed. Please try again.",
						"error"
					);
				}
			})
			.catch(() => {
				this.hideLoadingIndicator();
				button.disabled = false;
				this.showNotice("An error occurred. Please try again.", "error");
			});
	}

	// Handle "Reset Settings" button click.
	handleResetSettings(e) {
		this.handleResetAction(
			e,
			"lightshare_reset_settings",
			"Are you sure you want to reset all Lightshare settings? This action cannot be undone.",
			"Settings reset successfully. The page will now reload.",
			() => {
				setTimeout(() => location.reload(), LightshareAdmin.RELOAD_DELAY);
			}
		);
	}

	// Handle "Reset Counts" button click.
	handleResetCounts(e) {
		this.handleResetAction(
			e,
			"lightshare_reset_counts",
			"Are you sure you want to reset all share counts? This action cannot be undone.",
			"Share counts reset successfully."
		);
	}

	// Update a query string parameter in a URI using the URL API.
	updateQueryStringParameter(uri, key, value) {
		const url = new URL(uri, window.location.origin);
		url.searchParams.set(key, value);
		return url.toString();
	}

	// Set up all event handlers for the admin settings page.
	setupEventHandlers() {
		document.querySelectorAll(".nav-tab-wrapper a").forEach(tab => {
			tab.addEventListener("click", this.handleTabClick.bind(this));
		});

		document.querySelectorAll("form").forEach(form => {
			form.addEventListener("submit", this.handleFormSubmit.bind(this));
		});

		document.querySelectorAll(".floating-button-toggle").forEach(input => {
			input.addEventListener("change", this.handleFloatingButtonToggle.bind(this));
		});

		document.querySelectorAll(".inline-button-toggle").forEach(input => {
			input.addEventListener("change", this.handleInlineButtonToggle.bind(this));
		});

		const boundUpdatePreview = this.updatePreview.bind(this);
		const debouncedPreview = this.debounce(() => this.updatePreview());
		const boundSyncFields = this.syncConditionalFields.bind(this);

		// Data-driven bindings for preview and conditional field controls.
		const fieldBindings = [
			{ el: this.styleSelect, event: "change", handler: boundUpdatePreview },
			{ el: this.colorThemeSelect, event: "change", handler: boundUpdatePreview },
			{ el: this.showLabelInput, event: "change", handler: boundUpdatePreview },
			{ el: this.labelTextInput, event: "input", handler: debouncedPreview },
			{ el: this.nudgeTextInput, event: "input", handler: debouncedPreview },
			{ el: this.showCountsInput, event: "change", handler: boundSyncFields },
			{ el: this.utmEnabledInput, event: "change", handler: boundSyncFields },
			{ el: this.showLabelInput, event: "change", handler: boundSyncFields }
		];

		fieldBindings.forEach(({ el, event, handler }) => {
			el?.addEventListener(event, handler);
		});
	}

	// Toggle visibility of a settings section with slide animation.
	toggleSection(selector, isVisible) {
		document.querySelectorAll(selector).forEach(element => {
			if (isVisible) {
				this.slideDown(element);
			} else {
				this.slideUp(element);
			}
		});
	}

	// Handle floating button toggle change.
	handleFloatingButtonToggle(e) {
		this.toggleSection(".floating-button-settings", e.target.checked);
	}

	// Handle inline button toggle change.
	handleInlineButtonToggle(e) {
		this.toggleSection(".inline-button-settings", e.target.checked);
	}

	// Handle tab navigation click.
	handleTabClick(e) {
		e.preventDefault();
		const href = e.currentTarget.getAttribute("href") || "";
		const target = href.charAt(0) === "#" ? href.substring(1) : href;
		this.setActiveTab(target);
		history.pushState(
			null,
			null,
			this.updateQueryStringParameter(window.location.href, "tab", target)
		);
	}

	// Handle settings form submission via AJAX.
	handleFormSubmit(e) {
		e.preventDefault();
		const form = e.currentTarget;
		const formData = new FormData(form);

		form
			.querySelectorAll("input[type=checkbox]:not(:checked)")
			.forEach(checkbox => {
				if (checkbox.name) {
					formData.append(checkbox.name, "0");
				}
			});

		formData.append("action", "lightshare_save_settings");
		formData.append("lightshare_nonce", lightshare_admin.nonce);

		this.showLoadingIndicator();
		this.postFormData(formData)
			.then(response => {
				this.hideLoadingIndicator();
				if (response.success) {
					this.showNotice(response.data || "Settings saved.", "success");
				} else {
					this.showNotice(
						(response.data && response.data.message) ||
							"Failed to save settings. Please try again.",
						"error"
					);
				}
			})
			.catch(() => {
				this.hideLoadingIndicator();
				this.showNotice(
					"An error occurred while saving. Please try again.",
					"error"
				);
			});
	}

	// Display an inline notice message below the submit button.
	showNotice(message, type) {
		document.querySelectorAll(".lightshare-inline-notice").forEach(notice => {
			notice.remove();
		});

		const notice = document.createElement("div");
		notice.className = `lightshare-inline-notice notice-${type}`;
		notice.textContent = message;

		if (this.submitButton && this.submitButton.parentNode) {
			this.submitButton.parentNode.insertBefore(
				notice,
				this.submitButton.nextSibling
			);
		} else {
			document.body.appendChild(notice);
		}

		setTimeout(() => {
			notice.style.transition = `opacity ${LightshareAdmin.NOTICE_FADE_DURATION}ms ease`;
			notice.style.opacity = "0";
			setTimeout(() => notice.remove(), LightshareAdmin.NOTICE_FADE_DURATION);
		}, LightshareAdmin.NOTICE_DISPLAY_DURATION);
	}

	// Show a loading state on the submit button and disable it.
	showLoadingIndicator() {
		if (this.submitButton) {
			this.submitButton.value = "Saving...";
			this.submitButton.disabled = true;
		}
	}

	// Restore the submit button to its default state.
	hideLoadingIndicator() {
		if (this.submitButton) {
			this.submitButton.value = "Save Changes";
			this.submitButton.disabled = false;
		}
	}

	// Animate an element open with a height transition.
	slideDown(element) {
		if (!element) {
			return;
		}

		element.style.removeProperty("display");
		if (window.getComputedStyle(element).display === "none") {
			element.style.display = "block";
		}

		const targetHeight = element.scrollHeight;
		element.style.overflow = "hidden";
		element.style.height = "0px";
		element.offsetHeight; // Force reflow.
		element.style.transition = `height ${LightshareAdmin.ANIMATION_DURATION}ms ease`;
		element.style.height = `${targetHeight}px`;

		setTimeout(() => {
			element.style.removeProperty("height");
			element.style.removeProperty("overflow");
			element.style.removeProperty("transition");
		}, LightshareAdmin.ANIMATION_CLEANUP_DELAY);
	}

	// Animate an element closed with a height transition.
	slideUp(element) {
		if (!element || window.getComputedStyle(element).display === "none") {
			return;
		}

		element.style.height = `${element.scrollHeight}px`;
		element.style.overflow = "hidden";
		element.offsetHeight; // Force reflow.
		element.style.transition = `height ${LightshareAdmin.ANIMATION_DURATION}ms ease`;
		element.style.height = "0px";

		setTimeout(() => {
			element.style.display = "none";
			element.style.removeProperty("height");
			element.style.removeProperty("overflow");
			element.style.removeProperty("transition");
		}, LightshareAdmin.ANIMATION_CLEANUP_DELAY);
	}

	// Fade a table row into view.
	fadeRowIn(row) {
		if (!row) {
			return;
		}

		row.style.removeProperty("display");
		if (window.getComputedStyle(row).display === "none") {
			row.style.display = "table-row";
		}

		row.style.opacity = "0";
		row.style.transition = `opacity ${LightshareAdmin.ANIMATION_DURATION}ms ease`;
		requestAnimationFrame(() => {
			row.style.opacity = "1";
		});

		setTimeout(() => {
			row.style.removeProperty("opacity");
			row.style.removeProperty("transition");
		}, LightshareAdmin.ANIMATION_CLEANUP_DELAY);
	}

	// Fade a table row out of view.
	fadeRowOut(row) {
		if (!row || window.getComputedStyle(row).display === "none") {
			return;
		}

		row.style.opacity = "1";
		row.style.transition = `opacity ${LightshareAdmin.ANIMATION_DURATION}ms ease`;
		requestAnimationFrame(() => {
			row.style.opacity = "0";
		});

		setTimeout(() => {
			row.style.display = "none";
			row.style.removeProperty("opacity");
			row.style.removeProperty("transition");
		}, LightshareAdmin.ANIMATION_CLEANUP_DELAY);
	}

	// Show or hide conditional fields based on their toggle checkbox state.
	syncConditionalFields(options = {}) {
		const animate = options.animate !== false;

		document.querySelectorAll("[data-toggle-target]").forEach(input => {
			const targetSelector = input.dataset.toggleTarget;
			if (!targetSelector) {
				return;
			}

			document.querySelectorAll(targetSelector).forEach(target => {
				const isRow =
					input.dataset.toggleMode === "row" ||
					target.matches("tr") ||
					target.hasAttribute("data-toggle-row");
				const isVisible =
					window.getComputedStyle(target).display !== "none";

				// Show target when checked and hidden.
				if (input.checked && !isVisible) {
					if (animate) {
						isRow ? this.fadeRowIn(target) : this.slideDown(target);
					} else {
						target.style.display = isRow ? "" : "";
						target.style.removeProperty("display");
					}
					return;
				}

				// Hide target when unchecked and visible.
				if (!input.checked && isVisible) {
					if (animate) {
						isRow ? this.fadeRowOut(target) : this.slideUp(target);
					} else {
						target.style.display = "none";
					}
				}
			});
		});
	}

	// Read the active tab from the URL and activate it.
	initializeTabs() {
		const urlParams = new URLSearchParams(window.location.search);
		this.setActiveTab(urlParams.get("tab") || "share-button");
	}

	// Activate a specific tab by its ID.
	setActiveTab(tab) {
		document.querySelectorAll(".nav-tab-wrapper a").forEach(link => {
			link.classList.remove("nav-tab-active");
		});

		const activeTabLink = document.querySelector(
			`.nav-tab-wrapper a[href="#${tab}"]`
		);
		if (activeTabLink) {
			activeTabLink.classList.add("nav-tab-active");
		}

		document.querySelectorAll(".tab-content > div").forEach(content => {
			content.style.display = "none";
		});

		const activeTab = document.getElementById(tab);
		if (activeTab) {
			activeTab.style.display = "block";
		}

		const activeTabInput = document.getElementById("lightshare_active_tab");
		if (activeTabInput) {
			activeTabInput.value = tab;
		}
	}

	// Serialize the current network order into the hidden input field.
	updateNetworksOrder() {
		const orderInput = document.getElementById("lightshare_social_networks_order");
		if (!orderInput) {
			return;
		}

		const networks = [];
		document
			.querySelectorAll(".lightshare-social-networks li")
			.forEach(element => {
				if (element.dataset.network) {
					networks.push(element.dataset.network);
				}
			});
		orderInput.value = JSON.stringify(networks);
	}

	// Initialize drag-and-drop sorting for the social networks list.
	initializeSortable() {
		const list = document.querySelector(".lightshare-social-networks");
		if (!list) {
			return;
		}

		list.querySelectorAll("li").forEach(item => {
			item.setAttribute("draggable", "true");

			item.addEventListener("dragstart", event => {
				this.draggedItem = item;
				item.classList.add("is-dragging");
				if (event.dataTransfer) {
					event.dataTransfer.effectAllowed = "move";
				}
			});

			item.addEventListener("dragend", () => {
				item.classList.remove("is-dragging");
				this.draggedItem = null;
				this.updateNetworksOrder();
				this.updatePreview();
			});
		});

		// Throttled dragover handler using requestAnimationFrame.
		list.addEventListener("dragover", event => {
			event.preventDefault();

			if (event.dataTransfer) {
				event.dataTransfer.dropEffect = "move";
			}

			if (this._dragRafPending || !this.draggedItem) {
				return;
			}

			const hoveredItem = event.target.closest("li");
			if (!hoveredItem || hoveredItem === this.draggedItem) {
				return;
			}

			// Capture coordinates before the rAF callback.
			const clientX = event.clientX;
			const clientY = event.clientY;

			this._dragRafPending = true;
			requestAnimationFrame(() => {
				this._dragRafPending = false;

				const rect = hoveredItem.getBoundingClientRect();
				const pointerIsInsideRowBand =
					clientY >= rect.top && clientY <= rect.bottom;
				const insertBefore = pointerIsInsideRowBand
					? clientX < rect.left + rect.width / 2
					: clientY < rect.top + rect.height / 2;

				if (insertBefore) {
					list.insertBefore(this.draggedItem, hoveredItem);
				} else {
					list.insertBefore(this.draggedItem, hoveredItem.nextSibling);
				}
			});
		});

		list
			.querySelectorAll('input[type="checkbox"]')
			.forEach(checkbox => {
				const li = checkbox.closest("li");
				const label = checkbox.closest("label");

				if (li) {
					li.classList.toggle("active", checkbox.checked);
				}
				if (label) {
					label.classList.toggle("active", checkbox.checked);
				}

				checkbox.addEventListener("change", event => {
					const currentLi = event.currentTarget.closest("li");
					const currentLabel = event.currentTarget.closest("label");
					if (currentLi) {
						currentLi.classList.toggle("active", event.currentTarget.checked);
					}
					if (currentLabel) {
						currentLabel.classList.toggle("active", event.currentTarget.checked);
					}

					this.updateNetworksOrder();
					this.updatePreview();
				});
			});

		this.updateNetworksOrder();
	}

	// Initialize the live preview if the preview container exists.
	setupPreview() {
		if (!this.previewContainer) {
			return;
		}
		this.updatePreview();
	}

	// Fetch and render a live preview of the share buttons via AJAX.
	// Note: innerHTML is safe here — endpoint requires manage_options + nonce, output is escaped server-side.
	updatePreview() {
		if (!this.previewContainer) {
			return;
		}

		const activeNetworks = [];
		document.querySelectorAll(".lightshare-social-networks li").forEach(li => {
			const checkbox = li.querySelector("input[type=checkbox]");
			if (checkbox && checkbox.checked && li.dataset.network) {
				activeNetworks.push(li.dataset.network);
			}
		});

		const style = this.styleSelect?.value;
		const showLabel = this.showLabelInput?.checked;
		const labelText = this.labelTextInput?.value;
		const nudgeText = this.nudgeTextInput?.value;
		const colorTheme = this.colorThemeSelect?.value;

		this.postAjax({
			action: "lightshare_preview_buttons",
			nonce: lightshare_admin.nonce,
			networks: activeNetworks.join(","),
			style: style || "",
			color_theme: colorTheme || "",
			show_label: showLabel ? 1 : 0,
			label_text: labelText || "",
			nudge_text: typeof nudgeText === "string" ? nudgeText : ""
		}).then(response => {
			if (!(response && response.success && response.data && response.data.html)) {
				return;
			}

			if (response.data.css) {
				const styleId = "lightshare-preview-theme";
				let styleTag = document.getElementById(styleId);
				if (!styleTag) {
					styleTag = document.createElement("style");
					styleTag.id = styleId;
					document.head.appendChild(styleTag);
				}
				styleTag.textContent = response.data.css;
			}

			this.previewContainer.innerHTML = response.data.html;
		});
	}
}

window.addEventListener("DOMContentLoaded", () => {
	const lightshareAdmin = new LightshareAdmin();
	window.setActiveTab = tab => lightshareAdmin.setActiveTab(tab);

	setTimeout(() => {
		const submitButton = document.getElementById("submit-button");
		if (submitButton) {
			submitButton.style.display = "block";
		}
	}, 50);
});
