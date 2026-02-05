=== Lightshare - Lightweight Social Sharing ===
Contributors: nazim848
Donate link: https://buymeacoffee.com/nazim848
Tags: social share, social media, share buttons, facebook share, twitter share
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight, high-performance social media sharing plugin for WordPress that won't slow down your site.

== Description ==

Lightshare is a lightweight social sharing plugin built with performance in mind. It provides essential social sharing functionality without the bloat commonly found in other sharing plugins.

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
