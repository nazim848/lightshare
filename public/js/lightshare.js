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

	// Extract the network slug from a share button's class list (e.g. 'lightshare-twitter' -> 'twitter').
	function getNetworkFromButton(button) {
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

		const isCopy = button.classList.contains('lightshare-copy');
		if (isCopy) {
			event.preventDefault();
			const url = button.dataset.url || '';
			if (url) {
				copyText(url)
					.then(() => showToast('Link copied'))
					.catch(() => showToast('Failed to copy link'));
			}
		}

		const postId = container.dataset.postId;
		const network = isCopy ? 'copy' : getNetworkFromButton(button);
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
