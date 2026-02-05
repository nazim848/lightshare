(function( $ ) {
	'use strict';

	$(document).ready(function() {
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
		$('.lightshare-buttons a').on('click', function(e) {
			// Don't prevent default unless it's copy (already handled)
			// We want the link to open.
			
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

			if (postId && network) {
				$.ajax({
					url: lightshare_ajax.ajax_url,
					type: 'POST',
					data: {
						action: 'lightshare_track_click',
						nonce: lightshare_ajax.nonce,
						post_id: postId,
						network: network
					},
					success: function(response) {
						if (response.success) {
							// Update count if visible
							var $countSpan = $container.prev('.lightshare-label').find('.lightshare-total-count');
							if ($countSpan.length) {
								$countSpan.text('(' + response.data.count + ')');
							} else {
								// If count wasn't visible (e.g. was 0), and we want to show it now?
								// Only if the setting is on. But we can't check the PHP setting easily here.
								// We assume if the span is missing, either setting is off or count was 0.
								// If count was 0, it might not be rendered.
								// We can try to append it if we know setting is on, but cleaner to just update if exists.
							}
						}
					}
				});
			}
		});
	});

})( jQuery );
