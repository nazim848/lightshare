(function( $ ) {
	'use strict';

	$(document).ready(function() {
		function parseScrollOffset(offsetValue) {
			if (!offsetValue) {
				return null;
			}
			var value = String(offsetValue).trim().toLowerCase();
			var match = value.match(/^(\d+(?:\.\d+)?)(px|%)$/);
			if (!match) {
				return null;
			}
			return {
				amount: parseFloat(match[1]),
				unit: match[2]
			};
		}

		function initFloatingScrollVisibility() {
			$('.lightshare-floating').each(function() {
				var $floating = $(this);
				var $buttonGroup = $floating.find('.lightshare-buttons').first();
				var parsedOffset = parseScrollOffset($buttonGroup.attr('data-scroll-offset'));

				if (!parsedOffset) {
					$floating.removeClass('lightshare-floating-scroll-gated lightshare-floating-hidden');
					return;
				}
				$floating.removeClass('lightshare-floating-scroll-gated');
				$floating.addClass('lightshare-floating-hidden');

				var ticking = false;
				var updateVisibility = function() {
					var maxScroll = Math.max(0, $(document).height() - $(window).height());
					var threshold = parsedOffset.unit === '%'
						? maxScroll * (parsedOffset.amount / 100)
						: parsedOffset.amount;
					var currentScroll = $(window).scrollTop() || 0;
					if (currentScroll >= threshold) {
						$floating.removeClass('lightshare-floating-hidden');
					} else {
						$floating.addClass('lightshare-floating-hidden');
					}
				};

				var onScrollOrResize = function() {
					if (ticking) {
						return;
					}
					ticking = true;
					if (window.requestAnimationFrame) {
						window.requestAnimationFrame(function() {
							updateVisibility();
							ticking = false;
						});
					} else {
						setTimeout(function() {
							updateVisibility();
							ticking = false;
						}, 16);
					}
				};

				updateVisibility();
				$(window).on('scroll resize', onScrollOrResize);
			});
		}

		function showToast(message) {
			var $toast = $('#lightshare-toast');
			if (!$toast.length) {
				$toast = $('<div id="lightshare-toast" class="lightshare-toast"></div>');
				$('body').append($toast);
			}
			$toast.text(message).addClass('is-visible');
			setTimeout(function() {
				$toast.removeClass('is-visible');
			}, 2000);
		}

		// Handle Copy Link button
		$('.lightshare-copy').on('click', function(e) {
			e.preventDefault();

			var url = $(this).data('url');

			if (!url) {
				return;
			}

			navigator.clipboard.writeText(url).then(function() {
				showToast('Link copied');
			}).catch(function(err) {
				console.error('Could not copy text: ', err);
			});
		});

		// Track Clicks
		$('.lightshare-buttons a').on('click', function() {
			var $container = $(this).closest('.lightshare-buttons');
			var postId = $container.data('post-id');
			var network = '';

			// Get network from class lightshare-{network}
			var classes = $(this).attr('class').split(' ');
			for (var i = 0; i < classes.length; i++) {
				if (classes[i].indexOf('lightshare-') === 0 && classes[i] !== 'lightshare-button' && classes[i] !== 'lightshare-copy') {
					network = classes[i].replace('lightshare-', '');
					break;
				}
			}

			// Special case for copy since it doesn't open a link
			if ($(this).hasClass('lightshare-copy')) {
				network = 'copy';
			}

			var ajaxUrl = $container.attr('data-ajax-url');
			var nonce = $container.attr('data-nonce');
			if (!ajaxUrl || !nonce) {
				var ajaxConfig = window.lightshare_ajax || null;
				ajaxUrl = ajaxConfig && ajaxConfig.ajax_url ? ajaxConfig.ajax_url : '';
				nonce = ajaxConfig && ajaxConfig.nonce ? ajaxConfig.nonce : '';
			}

			if (!ajaxUrl || !nonce) {
				return;
			}

			if (postId && network) {
				$.ajax({
					url: ajaxUrl,
					type: 'POST',
					data: {
						action: 'lightshare_track_click',
						nonce: nonce,
						post_id: postId,
						network: network
					},
					success: function(response) {
						if (response.success) {
							var $countSpan = $container.prev('.lightshare-label').find('.lightshare-total-count');
							if ($countSpan.length) {
								$countSpan.text('(' + response.data.count + ')');
							}
						}
					}
				});
			}
		});

		initFloatingScrollVisibility();
	});

})( jQuery );
