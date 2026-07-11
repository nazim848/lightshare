'use strict';

const initLightsharePublic = () => {
	if (window.__lightsharePublicInitialized) {
		return;
	}
	window.__lightsharePublicInitialized = true;

	// How long the toast stays visible (ms).
	const TOAST_DURATION = 2000;

	// Parse a scroll offset string (e.g. '200', '50%', '100px') into { amount, unit }.
	function parseScrollOffset(offsetValue) {
		if (!offsetValue) {
			return null;
		}

		const value = String(offsetValue).trim().toLowerCase();
		const match = value.match(/^(\d+(?:\.\d+)?)(?:\s*(px|%))?$/);
		if (!match) {
			return null;
		}

		return {
			amount: Number.parseFloat(match[1]),
			unit: match[2] || 'px'
		};
	}

	// Send a POST request with URL-encoded data and return parsed JSON.
	function postAjax(url, data) {
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: new URLSearchParams(data)
		}).then(response => response.json());
	}

	// Legacy clipboard fallback using a hidden textarea and execCommand.
	function fallbackCopyText(text) {
		const textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.setAttribute('readonly', '');
		textarea.style.position = 'fixed';
		textarea.style.top = '-9999px';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.focus();
		textarea.select();

		let copied = false;
		try {
			copied = document.execCommand('copy');
		} catch (error) {
			copied = false;
		}

		document.body.removeChild(textarea);
		return copied;
	}

	// Copy text to clipboard using Clipboard API with execCommand fallback.
	function copyText(text) {
		if (
			navigator.clipboard &&
			typeof navigator.clipboard.writeText === 'function' &&
			window.isSecureContext
		) {
			return navigator.clipboard.writeText(text);
		}

		return new Promise((resolve, reject) => {
			if (fallbackCopyText(text)) {
				resolve();
				return;
			}
			reject(new Error('Copy command failed'));
		});
	}

	// Initialize scroll-based visibility toggling for floating share buttons.
	function initFloatingScrollVisibility() {
		const floatingElements = document.querySelectorAll('.lightshare-floating');
		floatingElements.forEach(floating => {
			const buttonGroup = floating.querySelector('.lightshare-buttons');
			const offsetValue =
				(buttonGroup ? buttonGroup.getAttribute('data-scroll-offset') : '') ||
				floating.getAttribute('data-scroll-offset') ||
				'';
			const parsedOffset = parseScrollOffset(offsetValue);

			if (!parsedOffset) {
				floating.classList.remove('lightshare-floating-scroll-gated', 'lightshare-floating-hidden');
				return;
			}

			floating.classList.remove('lightshare-floating-scroll-gated');
			floating.style.removeProperty('display');
			floating.classList.add('lightshare-floating-hidden');

			let ticking = false;

			// Calculate scroll threshold and toggle visibility class.
			const updateVisibility = () => {
				const documentHeight = Math.max(
					document.body.scrollHeight,
					document.documentElement.scrollHeight,
					document.body.offsetHeight,
					document.documentElement.offsetHeight,
					document.body.clientHeight,
					document.documentElement.clientHeight
				);
				const maxScroll = Math.max(0, documentHeight - window.innerHeight);
				const threshold = parsedOffset.unit === '%'
					? maxScroll * (parsedOffset.amount / 100)
					: parsedOffset.amount;
				const currentScroll = window.scrollY || 0;

				floating.classList.toggle('lightshare-floating-hidden', currentScroll < threshold);
			};

			// Throttled scroll/resize handler gated by requestAnimationFrame.
			const onScrollOrResize = () => {
				if (ticking) {
					return;
				}
				ticking = true;
				requestAnimationFrame(() => {
					updateVisibility();
					ticking = false;
				});
			};

			updateVisibility();
			window.addEventListener('scroll', onScrollOrResize, { passive: true });
			window.addEventListener('resize', onScrollOrResize);
		});
	}

	// Display a brief toast notification at the bottom of the screen.
	function showToast(message) {
		let toast = document.getElementById('lightshare-toast');
		if (!toast) {
			toast = document.createElement('div');
			toast.id = 'lightshare-toast';
			toast.className = 'lightshare-toast';
			document.body.appendChild(toast);
		}

		toast.textContent = message;
		toast.classList.add('is-visible');
		setTimeout(() => {
			toast.classList.remove('is-visible');
		}, TOAST_DURATION);
	}

	function getString(key, fallback) {
		return window.lightshare_ajax?.strings?.[key] || fallback;
	}

	function normalizeMastodonInstance(value) {
		let candidate = String(value || '').trim();
		if (!candidate) {
			return '';
		}
		if (!/^https?:\/\//i.test(candidate)) {
			candidate = `https://${candidate}`;
		}
		try {
			const url = new URL(candidate);
			if (
				url.protocol !== 'https:' ||
				!url.hostname ||
				url.username ||
				url.password ||
				url.port
			) {
				return '';
			}
			return url.hostname.toLowerCase();
		} catch (error) {
			return '';
		}
	}

	function getSavedMastodonInstance() {
		try {
			return localStorage.getItem('lightshare_mastodon_instance') || '';
		} catch (error) {
			return '';
		}
	}

	function saveMastodonInstance(instance) {
		try {
			localStorage.setItem('lightshare_mastodon_instance', instance);
		} catch (error) {
			// Sharing still works when browser storage is unavailable.
		}
	}

	function openMastodonDialog(shareText, trigger) {
		const backdrop = document.createElement('div');
		backdrop.className = 'lightshare-dialog-backdrop';
		backdrop.innerHTML = `
			<div class="lightshare-dialog" role="dialog" aria-modal="true" aria-labelledby="lightshare-mastodon-title">
				<h2 id="lightshare-mastodon-title">${getString('mastodon_title', 'Share on Mastodon')}</h2>
				<p>${getString('mastodon_help', 'Enter your Mastodon server, for example mastodon.social.')}</p>
				<form class="lightshare-mastodon-form">
					<label for="lightshare-mastodon-instance">${getString('mastodon_label', 'Mastodon server')}</label>
					<input id="lightshare-mastodon-instance" type="text" inputmode="url" autocomplete="url" placeholder="mastodon.social" required>
					<p class="lightshare-dialog-error" role="alert" hidden></p>
					<div class="lightshare-dialog-actions">
						<button type="button" class="lightshare-dialog-cancel">${getString('cancel', 'Cancel')}</button>
						<button type="submit">${getString('mastodon_share', 'Continue to Mastodon')}</button>
					</div>
				</form>
			</div>`;

		const dialog = backdrop.querySelector('.lightshare-dialog');
		const form = backdrop.querySelector('form');
		const input = backdrop.querySelector('input');
		const error = backdrop.querySelector('.lightshare-dialog-error');
		const cancel = backdrop.querySelector('.lightshare-dialog-cancel');
		const focusable = () => Array.from(dialog.querySelectorAll('button, input'));

		const close = () => {
			document.removeEventListener('keydown', onKeydown);
			backdrop.remove();
			trigger?.focus();
		};
		const onKeydown = event => {
			if (event.key === 'Escape') {
				close();
				return;
			}
			if (event.key === 'Tab') {
				const items = focusable();
				const first = items[0];
				const last = items[items.length - 1];
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			}
		};

		input.value = getSavedMastodonInstance();
		cancel.addEventListener('click', close);
		backdrop.addEventListener('click', event => {
			if (event.target === backdrop) {
				close();
			}
		});
		form.addEventListener('submit', event => {
			event.preventDefault();
			const instance = normalizeMastodonInstance(input.value);
			if (!instance) {
				error.textContent = getString('invalid_instance', 'Enter a valid Mastodon server using HTTPS.');
				error.hidden = false;
				input.setAttribute('aria-invalid', 'true');
				input.focus();
				return;
			}
			saveMastodonInstance(instance);
			window.open(`https://${instance}/share?text=${encodeURIComponent(shareText)}`, '_blank', 'noopener,noreferrer');
			close();
		});

		document.body.appendChild(backdrop);
		document.addEventListener('keydown', onKeydown);
		input.focus();
	}

	// Extract the network slug from a share button's class list (e.g. 'lightshare-twitter' -> 'twitter').
	function getNetworkFromButton(button) {
		if (button.dataset.network) {
			return button.dataset.network;
		}
		for (const className of button.classList) {
			if (
				className.startsWith('lightshare-') &&
				className !== 'lightshare-button' &&
				className !== 'lightshare-copy'
			) {
				return className.slice('lightshare-'.length);
			}
		}
		return '';
	}

	// Resolve AJAX URL and nonce from container data attributes or global config.
	function getAjaxConfig(container) {
		const config = window.lightshare_ajax;
		return {
			ajaxUrl: container.getAttribute('data-ajax-url') || config?.ajax_url || '',
			nonce: container.getAttribute('data-nonce') || config?.nonce || ''
		};
	}

	// Delegated click handler for all share button interactions.
	document.addEventListener('click', event => {
		const button = event.target.closest('.lightshare-buttons a');
		if (!button) {
			return;
		}

		const container = button.closest('.lightshare-buttons');
		if (!container) {
			return;
		}

		const action = button.dataset.lightshareAction || (button.classList.contains('lightshare-copy') ? 'copy' : 'link');
		if (action === 'copy') {
			event.preventDefault();
			const copyValue = button.dataset.copyText || button.dataset.url || '';
			if (copyValue) {
				copyText(copyValue)
					.then(() => showToast(getString('link_copied', 'Link copied')))
					.catch(() => showToast(getString('copy_failed', 'Failed to copy')));
			}
		} else if (action === 'copy-and-open') {
			event.preventDefault();
			window.open(button.href, '_blank', 'noopener,noreferrer');
			copyText(button.dataset.copyText || '')
				.then(() => showToast(getString('prompt_copied', 'Prompt copied — paste it into Claude')))
				.catch(() => showToast(getString('copy_failed', 'Failed to copy')));
		} else if (action === 'mastodon-instance') {
			event.preventDefault();
			openMastodonDialog(button.dataset.copyText || '', button);
		}

		const postId = container.dataset.postId;
		const network = getNetworkFromButton(button);
		const { ajaxUrl, nonce } = getAjaxConfig(container);

		if (!ajaxUrl || !nonce || !postId || !network) {
			return;
		}

		postAjax(ajaxUrl, {
			action: 'lightshare_track_click',
			nonce,
			post_id: postId,
			network
		}).then(response => {
			if (!response?.success) {
				return;
			}

			const previous = container.previousElementSibling;
			const countSpan = previous?.matches('.lightshare-label')
				? previous.querySelector('.lightshare-total-count')
				: null;

			if (countSpan && response.data && typeof response.data.count !== 'undefined') {
				countSpan.textContent = `(${response.data.count})`;
			}
		}).catch(() => {
			// Silent fail for click tracking.
		});
	});

	initFloatingScrollVisibility();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initLightsharePublic);
} else {
	initLightsharePublic();
}
