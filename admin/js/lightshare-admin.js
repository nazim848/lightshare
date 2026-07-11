class LightshareAdmin {
	// Animation timing constants.
	static ANIMATION_DURATION = 150;
	static ANIMATION_CLEANUP_DELAY = 160;
	static NOTICE_DISPLAY_DURATION = 3200;
	static NOTICE_FADE_DURATION = 280;
	static PREVIEW_DEBOUNCE_DELAY = 400;
	static RELOAD_DELAY = 1500;

	constructor() {
		this.draggedItem = null;
		this._dragRafPending = false;
		this._modalResolve = null;
		this.cacheElements();
		this.init();
	}

	// Cache frequently accessed DOM elements.
	cacheElements() {
		this.submitButton = document.getElementById("submit");
		this.mobileSaveButtons = document.querySelectorAll(".ls-mobile-save-btn");
		this.previewContainer = document.getElementById("lightshare-preview");
		this.styleInputs = document.querySelectorAll(
			"input[name='lightshare_options[share][style]']"
		);
		this.colorThemeInputs = document.querySelectorAll(
			"input[name='lightshare_options[share][color_theme]']"
		);
		// Legacy select support (if present).
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
		this.toastEl = document.getElementById("ls-toast");
		this.modalEl = document.getElementById("ls-modal");
		this.modalTitle = document.getElementById("ls-modal-title");
		this.modalBody = document.getElementById("ls-modal-body");
		this.modalConfirm = document.getElementById("ls-modal-confirm");
		this.saveStatus = document.getElementById("ls-save-status");
		this.hideOnMobileInput = document.getElementById("lightshare-hide-on-mobile");
	}

	// Bootstrap all admin UI features.
	init() {
		this.initializeTabs();
		this.setupEventHandlers();
		this.setupModal();
		this.setupResetSettings();
		this.setupResetCounts();
		this.setupPreview();
		this.setupChoiceCards();
		this.syncConditionalFields({ animate: false });
		this.initializeSortable();
		this.syncFloatingMobilePosition();
		this.setSaveState("clean");
	}

	// Localized string helper with fallbacks.
	i18n(key, fallback) {
		if (
			typeof lightshare_admin !== "undefined" &&
			lightshare_admin.i18n &&
			lightshare_admin.i18n[key]
		) {
			return lightshare_admin.i18n[key];
		}
		return fallback;
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

	// Read selected radio value (or select fallback).
	getChoiceValue(inputs, selectEl) {
		if (inputs && inputs.length) {
			const checked = Array.from(inputs).find(input => input.checked);
			if (checked) {
				return checked.value;
			}
		}
		return selectEl?.value || "";
	}

	// Keep visual selected state on choice cards.
	setupChoiceCards() {
		document.querySelectorAll(".ls-choice").forEach(card => {
			const input = card.querySelector("input[type=radio]");
			if (!input) {
				return;
			}

			const sync = () => {
				const name = input.name;
				document
					.querySelectorAll(`.ls-choice input[name="${CSS.escape(name)}"]`)
					.forEach(radio => {
						const parent = radio.closest(".ls-choice");
						if (parent) {
							parent.classList.toggle("is-selected", radio.checked);
						}
					});
			};

			input.addEventListener("change", () => {
				sync();
				this.updatePreview();
			});
			sync();
		});
	}

	// Modal plumbing.
	setupModal() {
		if (!this.modalEl) {
			return;
		}

		this.modalEl.querySelectorAll("[data-ls-modal-close]").forEach(el => {
			el.addEventListener("click", () => this.closeModal(false));
		});

		if (this.modalConfirm) {
			this.modalConfirm.addEventListener("click", () => this.closeModal(true));
		}

		document.addEventListener("keydown", e => {
			if (e.key === "Escape" && this.modalEl.classList.contains("is-open")) {
				this.closeModal(false);
			}
		});
	}

	openModal({ title, body, confirmLabel }) {
		return new Promise(resolve => {
			if (!this.modalEl) {
				resolve(window.confirm(body || title));
				return;
			}

			this._modalResolve = resolve;
			if (this.modalTitle) {
				this.modalTitle.textContent = title || "";
			}
			if (this.modalBody) {
				this.modalBody.textContent = body || "";
			}
			if (this.modalConfirm && confirmLabel) {
				this.modalConfirm.textContent = confirmLabel;
			}

			this.modalEl.hidden = false;
			this.modalEl.setAttribute("aria-hidden", "false");
			// Force reflow so transition runs.
			this.modalEl.offsetHeight;
			this.modalEl.classList.add("is-open");
			this.modalConfirm?.focus();
		});
	}

	closeModal(confirmed) {
		if (!this.modalEl) {
			return;
		}

		this.modalEl.classList.remove("is-open");
		this.modalEl.setAttribute("aria-hidden", "true");

		setTimeout(() => {
			this.modalEl.hidden = true;
		}, LightshareAdmin.ANIMATION_CLEANUP_DELAY);

		if (this._modalResolve) {
			const resolve = this._modalResolve;
			this._modalResolve = null;
			resolve(Boolean(confirmed));
		}
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
	async handleResetAction(e, action, confirmTitle, confirmMessage, successMessage, onSuccess) {
		e.preventDefault();

		const confirmed = await this.openModal({
			title: confirmTitle,
			body: confirmMessage,
			confirmLabel: this.i18n("confirm", "Confirm")
		});

		if (!confirmed) {
			return;
		}

		const button = e.currentTarget;
		button.disabled = true;
		button.classList.add("is-loading");

		this.postAjax({
			action,
			nonce: lightshare_admin.nonce
		})
			.then(response => {
				button.disabled = false;
				button.classList.remove("is-loading");

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
							this.i18n("operationFailed", "Operation failed. Please try again."),
						"error"
					);
				}
			})
			.catch(() => {
				button.disabled = false;
				button.classList.remove("is-loading");
				this.showNotice(
					this.i18n("errorOccurred", "An error occurred. Please try again."),
					"error"
				);
			});
	}

	// Handle "Reset Settings" button click.
	handleResetSettings(e) {
		this.handleResetAction(
			e,
			"lightshare_reset_settings",
			this.i18n("resetSettingsTitle", "Reset all settings?"),
			this.i18n(
				"resetSettingsConfirm",
				"Are you sure you want to reset all Lightshare settings? This action cannot be undone."
			),
			this.i18n(
				"resetSettingsSuccess",
				"Settings reset successfully. The page will now reload."
			),
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
			this.i18n("resetCountsTitle", "Reset share counts?"),
			this.i18n(
				"resetCountsConfirm",
				"Are you sure you want to reset all share counts? This action cannot be undone."
			),
			this.i18n("resetCountsSuccess", "Share counts reset successfully.")
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
		document.querySelectorAll(".nav-tab-wrapper a, .ls-nav__tab").forEach(tab => {
			tab.addEventListener("click", this.handleTabClick.bind(this));
		});

		const form = document.getElementById("lightshare-settings-form");
		if (form) {
			form.addEventListener("submit", this.handleFormSubmit.bind(this));
			form.addEventListener("input", this.markDirty.bind(this));
			form.addEventListener("change", this.markDirty.bind(this));
		}

		document.querySelectorAll(".floating-button-toggle").forEach(input => {
			input.addEventListener("change", this.handleFloatingButtonToggle.bind(this));
		});

		this.hideOnMobileInput?.addEventListener("change", () => {
			this.syncFloatingMobilePosition();
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

		// Radio style/theme inputs.
		this.styleInputs.forEach(input => {
			input.addEventListener("change", boundUpdatePreview);
		});
		this.colorThemeInputs.forEach(input => {
			input.addEventListener("change", boundUpdatePreview);
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

	// Keep mobile-only controls meaningful when the floating button is hidden.
	syncFloatingMobilePosition() {
		if (!this.hideOnMobileInput) {
			return;
		}

		const isHiddenOnMobile = this.hideOnMobileInput.checked;
		const row = document.querySelector(".ls-mobile-position-row");
		const controls = document.querySelector("[data-mobile-position-controls]");
		const preservedValue = document.querySelector(".ls-mobile-position-preserve");
		const note = document.querySelector(".ls-mobile-position-note");

		if (row) {
			row.classList.toggle("is-disabled", isHiddenOnMobile);
		}
		if (controls) {
			controls.disabled = isHiddenOnMobile;
		}
		if (preservedValue) {
			const selectedPosition = controls?.querySelector("input:checked");
			if (selectedPosition) {
				preservedValue.value = selectedPosition.value;
			}
			preservedValue.disabled = !isHiddenOnMobile;
		}
		if (note) {
			note.hidden = !isHiddenOnMobile;
		}
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

		this.setSaveState("saving");
		this.postFormData(formData)
			.then(response => {
				if (response.success) {
					this.showNotice(
						response.data || this.i18n("settingsSaved", "Settings saved."),
						"success"
					);
					this.setSaveState("saved");
				} else {
					this.showNotice(
						(response.data && response.data.message) ||
							this.i18n(
								"saveFailed",
								"Failed to save settings. Please try again."
							),
						"error"
					);
					this.setSaveState("dirty");
				}
			})
			.catch(() => {
				this.showNotice(
					this.i18n(
						"saveError",
						"An error occurred while saving. Please try again."
					),
					"error"
				);
				this.setSaveState("dirty");
			});
	}

	markDirty() {
		if (this.saveState !== "saving") {
			this.setSaveState("dirty");
		}
	}

	setSaveState(state) {
		this.saveState = state;
		const statusMessages = {
			clean: "",
			dirty: this.i18n("unsavedChanges", "Unsaved changes"),
			saving: this.i18n("saving", "Saving..."),
			saved: this.i18n("settingsSaved", "Settings saved.")
		};

		if (this.saveStatus) {
			this.saveStatus.textContent = statusMessages[state] || "";
		}

		[this.submitButton, ...this.mobileSaveButtons].filter(Boolean).forEach(button => {
			const label = button.dataset.lsSaveLabel || this.i18n("saveChanges", "Save Changes");
			const savingLabel = button.dataset.lsSavingLabel || this.i18n("saving", "Saving...");
			const savedLabel = button.dataset.lsSavedLabel || this.i18n("saved", "Saved");
			button.disabled = state !== "dirty";
			button.classList.toggle("is-loading", state === "saving");
			button.classList.toggle("is-saved", state === "saved");
			button.toggleAttribute("aria-busy", state === "saving");
			button.textContent = state === "saving" ? savingLabel : state === "saved" ? savedLabel : label;
		});

		if (state === "saved") {
			clearTimeout(this._savedStateTimer);
			this._savedStateTimer = setTimeout(() => {
				if (this.saveState === "saved") {
					this.setSaveState("clean");
				}
			}, 1800);
		}
	}

	// Display a toast notice.
	showNotice(message, type) {
		const toast =
			this.toastEl ||
			(() => {
				const el = document.createElement("div");
				el.id = "ls-toast";
				el.className = "ls-toast";
				el.setAttribute("role", "status");
				el.setAttribute("aria-live", "polite");
				document.body.appendChild(el);
				this.toastEl = el;
				return el;
			})();

		toast.className = `ls-toast ls-toast--${type}`;
		toast.textContent = message;
		toast.hidden = false;
		// Force reflow.
		toast.offsetHeight;
		toast.classList.add("is-visible");

		clearTimeout(this._toastTimer);
		this._toastTimer = setTimeout(() => {
			toast.classList.remove("is-visible");
			setTimeout(() => {
				toast.hidden = true;
			}, LightshareAdmin.NOTICE_FADE_DURATION);
		}, LightshareAdmin.NOTICE_DISPLAY_DURATION);
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

	// Fade a field/row into view.
	fadeRowIn(row) {
		if (!row) {
			return;
		}

		row.style.removeProperty("display");
		if (window.getComputedStyle(row).display === "none") {
			row.style.display = row.classList.contains("ls-field") ? "grid" : "block";
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

	// Fade a field/row out of view.
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
					target.classList.contains("ls-field") ||
					target.hasAttribute("data-toggle-row");
				const isVisible =
					window.getComputedStyle(target).display !== "none";

				// Show target when checked and hidden.
				if (input.checked && !isVisible) {
					if (animate) {
						isRow ? this.fadeRowIn(target) : this.slideDown(target);
					} else {
						target.style.removeProperty("display");
						if (target.classList.contains("ls-field")) {
							target.style.display = "grid";
						}
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
		document.querySelectorAll(".nav-tab-wrapper a, .ls-nav__tab").forEach(link => {
			link.classList.remove("nav-tab-active", "is-active");
			link.setAttribute("aria-selected", "false");
		});

		const activeTabLink =
			document.querySelector(`.nav-tab-wrapper a[href="#${tab}"]`) ||
			document.querySelector(`.ls-nav__tab[href="#${tab}"]`);
		if (activeTabLink) {
			activeTabLink.classList.add("nav-tab-active", "is-active");
			activeTabLink.setAttribute("aria-selected", "true");

			const pageTitle = document.getElementById("ls-page-title");
			const titleEl = activeTabLink.querySelector(".ls-nav__title");
			if (pageTitle && titleEl) {
				pageTitle.textContent = titleEl.textContent.trim();
			}
		}

		document.querySelectorAll(".tab-content > .tab-pane, .tab-content > div").forEach(content => {
			content.style.display = "none";
			content.classList.remove("active", "is-active");
		});

		const activeTab = document.getElementById(tab);
		if (activeTab) {
			activeTab.style.display = "block";
			activeTab.classList.add("active", "is-active");
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
				this.markDirty();
			});

			item.addEventListener("keydown", event => {
				if (!event.altKey || !["ArrowLeft", "ArrowRight"].includes(event.key)) {
					return;
				}

				const items = Array.from(list.querySelectorAll("li"));
				const index = items.indexOf(item);
				const targetIndex = event.key === "ArrowLeft" ? index - 1 : index + 1;
				const target = items[targetIndex];
				if (!target) {
					return;
				}

				event.preventDefault();
				list.insertBefore(item, event.key === "ArrowLeft" ? target : target.nextSibling);
				this.updateNetworksOrder();
				this.updatePreview();
				this.markDirty();
				item.focus();
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

		list.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
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

		const style = this.getChoiceValue(this.styleInputs, this.styleSelect);
		const colorTheme = this.getChoiceValue(
			this.colorThemeInputs,
			this.colorThemeSelect
		);
		const showLabel = this.showLabelInput?.checked;
		const labelText = this.labelTextInput?.value;
		const nudgeText = this.nudgeTextInput?.value;

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
});
