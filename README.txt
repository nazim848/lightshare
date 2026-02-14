=== Lightshare - Lightweight Social Sharing ===
Contributors: nazim848
Donate link: https://buymeacoffee.com/nazim848
Tags: social share, social media, share buttons, facebook share, twitter share
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight, high-performance social media sharing plugin for WordPress that won't slow down your site.

== Description ==

Lightshare is a very lightweight social sharing plugin built with performance in mind. It provides essential social sharing functionality without the bloat commonly found in other sharing plugins.

== Key Features ==

* Lightweight and fast - minimal impact on page load times
* Support for major social networks
* Clean, modern design with multiple style options
* Share count display (where available)
* Customizable button placement
* Mobile-friendly and responsive
* No third-party scripts loaded by default
* Support for custom post types
* Shortcode support for manual placement

== Performance First ==

Lightshare is built with performance as a top priority:

* Minimal CSS/JS footprint
* SVG icons instead of icon fonts
* Internal click tracking for counts (no API calls)
* Assets are only enqueued when needed
* No third-party tracking scripts

== Shortcode ==

You can manually place sharing buttons anywhere using the shortcode:

`[lightshare]`

With custom options:

`[lightshare networks="facebook,twitter,linkedin" style="rounded"]`

== Block ==

Lightshare includes a block for the block editor:

* Block name: "Lightshare Buttons"
* Allows selecting networks (comma-separated), label visibility, and label text
* Rendered server-side for accurate output

== Developer Friendly ==

* Well-documented code following WordPress coding standards
* Extensible through filters and actions
* Clean, semantic HTML output
* Customizable through CSS classes

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/lightshare` directory, or install directly through WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Lightshare to configure the plugin

== Frequently Asked Questions ==

= Which social networks are supported? =

Lightshare supports Facebook, Twitter/X, LinkedIn, Pinterest, Reddit, WhatsApp, BlueSky, ChatGPT, Grok, Perplexity, Google AI, and Email sharing.

= Will this plugin slow down my site? =

No, Lightshare is built with performance in mind. The total size of CSS and JavaScript is less than 30KB, and assets are only loaded when needed.

= How do I display share counts? =

Share counts can be enabled in the plugin settings under the "Share Button" tab. Lightshare uses a lightweight internal click-tracking mechanism to ensure performance and privacy, so no external API keys are required.

= Can I customize the button styles? =

Yes, Lightshare comes with multiple pre-built button styles and colors.

= Can I use the sharing buttons anywhere on my site? =

Yes, you can use the `[lightshare]` shortcode or block to display sharing buttons anywhere in your content.

= Does it work with custom post types? =

Yes, you can enable sharing buttons for any public post type in the plugin settings.

== External services ==

Lightshare does not call third-party APIs in the background. External requests only happen when a visitor clicks a share button. When a share button is clicked, the destination service receives the share data in the URL query string (for example: current page URL, page title, and for Pinterest also an image URL when available).

The plugin can connect to these third-party services:

* Facebook (`facebook.com`) for sharing links.
  Data sent on click: page URL.
  Terms: https://www.facebook.com/terms.php
  Privacy: https://www.facebook.com/privacy/policy/

* X / Twitter (`twitter.com`) for sharing links/text.
  Data sent on click: page title and page URL.
  Terms: https://x.com/en/tos
  Privacy: https://x.com/en/privacy

* LinkedIn (`linkedin.com`) for sharing links.
  Data sent on click: page URL.
  Terms: https://www.linkedin.com/legal/user-agreement
  Privacy: https://www.linkedin.com/legal/privacy-policy

* WhatsApp (`api.whatsapp.com`) for sharing links/text.
  Data sent on click: page title and page URL.
  Terms: https://www.whatsapp.com/legal/terms-of-service
  Privacy: https://www.whatsapp.com/legal/privacy-policy

* Pinterest (`pinterest.com`) for creating pins from your page.
  Data sent on click: page URL, page title, and featured image URL (if available).
  Terms: https://policy.pinterest.com/en/terms-of-service
  Privacy: https://policy.pinterest.com/en/privacy-policy

* Reddit (`reddit.com`) for sharing posts.
  Data sent on click: page URL and page title.
  Terms: https://www.redditinc.com/policies/user-agreement
  Privacy: https://www.reddit.com/policies/privacy-policy

* Bluesky (`bsky.app`) for sharing links/text.
  Data sent on click: page title and page URL.
  Terms: https://bsky.social/about/support/tos
  Privacy: https://bsky.social/about/support/privacy-policy

* OpenAI ChatGPT (`chat.openai.com`) for opening a prefilled prompt.
  Data sent on click: generated prompt text that includes the page title and page URL.
  Terms: https://openai.com/policies/terms-of-use/
  Privacy: https://openai.com/policies/privacy-policy/

* Google Search AI mode (`google.com`) for opening a prefilled query.
  Data sent on click: generated prompt text that includes the page title and page URL.
  Terms: https://policies.google.com/terms
  Privacy: https://policies.google.com/privacy

* Perplexity (`perplexity.ai`) for opening a prefilled query.
  Data sent on click: generated prompt text that includes the page title and page URL.
  Terms: https://www.perplexity.ai/hub/legal/terms-of-service
  Privacy: https://www.perplexity.ai/hub/legal/privacy-policy

* xAI Grok (`x.com`) for opening a prefilled query.
  Data sent on click: generated prompt text that includes the page title and page URL.
  Terms: https://x.com/en/tos
  Privacy: https://x.com/en/privacy

* Email client (`mailto:`) for composing an email draft.
  Data sent on click: page title (as subject) and page URL (as body) passed to the visitor's local email client.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Lightshare

== Support ==

For support, feature requests, or bug reports, please visit the [plugin support forum](https://wordpress.org/support/plugin/lightshare/).

== License ==
This plugin is free software, released under the GPLv2 or later.

== Privacy Policy ==

Lightshare does not collect personal data. When visitors click a share button, they are sent to the selected third-party sharing service (for example, Facebook, X, LinkedIn, Pinterest, Reddit, WhatsApp, BlueSky, or the AI services listed above). No third-party scripts are loaded by the plugin itself.
