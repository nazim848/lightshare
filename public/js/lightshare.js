'use strict';

const initLightsharePublic = () => {
	if (window.__lightsharePublicInitialized) {
		return;
	}
	window.__lightsharePublicInitialized = true;

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
				const currentScroll =
					window.scrollY ||
					window.pageYOffset ||
					document.documentElement.scrollTop ||
					document.body.scrollTop ||
					0;

				floating.classList.toggle('lightshare-floating-hidden', currentScroll < threshold);
			};

			const onScrollOrResize = () => {
				if (ticking) {
					return;
				}
				ticking = true;
				if (window.requestAnimationFrame) {
					window.requestAnimationFrame(() => {
						updateVisibility();
						ticking = false;
					});
				} else {
					setTimeout(() => {
						updateVisibility();
						ticking = false;
					}, 16);
				}
			};

			updateVisibility();
			window.addEventListener('scroll', onScrollOrResize, { passive: true });
			window.addEventListener('resize', onScrollOrResize);
		});
	}

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
		}, 2000);
	}

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
				copyText(url).then(() => {
					showToast('Link copied');
				}).catch(error => {
					console.error('Could not copy text: ', error);
				});
			}
		}

		const postId = container.dataset.postId;
		let network = '';

		button.classList.forEach(className => {
			if (
				!network &&
				className.indexOf('lightshare-') === 0 &&
				className !== 'lightshare-button' &&
				className !== 'lightshare-copy'
			) {
				network = className.replace('lightshare-', '');
			}
		});

		if (isCopy) {
			network = 'copy';
		}

		let ajaxUrl = container.getAttribute('data-ajax-url');
		let nonce = container.getAttribute('data-nonce');
		if (!ajaxUrl || !nonce) {
			const ajaxConfig = window.lightshare_ajax || null;
			ajaxUrl = ajaxConfig && ajaxConfig.ajax_url ? ajaxConfig.ajax_url : '';
			nonce = ajaxConfig && ajaxConfig.nonce ? ajaxConfig.nonce : '';
		}

		if (!ajaxUrl || !nonce || !postId || !network) {
			return;
		}

		postAjax(ajaxUrl, {
			action: 'lightshare_track_click',
			nonce: nonce,
			post_id: postId,
			network: network
		}).then(response => {
			if (!response || !response.success) {
				return;
			}

			const previous = container.previousElementSibling;
			const countSpan = previous && previous.matches('.lightshare-label')
				? previous.querySelector('.lightshare-total-count')
				: null;

			if (countSpan && response.data && typeof response.data.count !== 'undefined') {
				countSpan.textContent = `(${response.data.count})`;
			}
		}).catch(() => {
			// Do nothing if click tracking fails.
		});
	});

	initFloatingScrollVisibility();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initLightsharePublic);
} else {
	initLightsharePublic();
}
