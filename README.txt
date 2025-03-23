=== LightShare - Lightweight Social Sharing ===
Contributors: nazim848
Donate link: https://buymeacoffee.com/nazim848
Tags: social share, social media, share buttons, facebook share, twitter share
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight, high-performance social media sharing plugin for WordPress that won't slow down your site.

== Description ==

LightShare is a lightweight social sharing plugin built with performance in mind. It provides essential social sharing functionality without the bloat commonly found in other sharing plugins.

== Key Features ==

* Lightweight and fast - minimal impact on page load times
* Support for major social networks: Facebook, Twitter/X, LinkedIn, Pinterest, Reddit, and Email
* Clean, modern design with multiple style options
* Share count display (where available)
* Customizable button placement
* Mobile-friendly and responsive
* No third-party scripts loaded by default
* Support for custom post types
* Shortcode support for manual placement
* RTL language support

== Performance First ==

LightShare is built with performance as a top priority:

* Minimal CSS/JS footprint (< 30KB combined)
* SVG icons instead of icon fonts
* Lazy-loaded share counts
* Local caching of share counts
* No render-blocking resources
* No third-party tracking scripts

== Shortcode ==

You can manually place sharing buttons anywhere using the shortcode:

`[lightshare]`

With custom options:

`[lightshare networks="facebook,twitter,linkedin" style="rounded" counts="false"]`

== Developer Friendly ==

* Well-documented code following WordPress coding standards
* Extensible through filters and actions
* Clean, semantic HTML output
* Customizable through CSS classes

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/lightshare` directory, or install directly through WordPress
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > LightShare to configure the plugin

== Frequently Asked Questions ==

= Which social networks are supported? =

LightShare supports Facebook, Twitter/X, LinkedIn, Pinterest, Reddit, and Email sharing by default.

= Will this plugin slow down my site? =

No, LightShare is built with performance in mind. The total size of CSS and JavaScript is less than 30KB, and assets are only loaded when needed.

= How do I display share counts? =

Share counts can be enabled in the plugin settings. For Facebook share counts, you'll need to provide a Facebook API access token.

= Can I customize the button styles? =

Yes, LightShare comes with multiple pre-built styles (Default, Rounded, Circle, Minimal) and can be further customized through CSS.

= Can I use the sharing buttons anywhere on my site? =

Yes, you can use the `[lightshare]` shortcode to display sharing buttons anywhere in your content.

= Does it work with custom post types? =

Yes, you can enable sharing buttons for any public post type in the plugin settings.

== Screenshots ==

1. Social sharing buttons with default style
2. Settings page - General tab
3. Settings page - Style tab
4. Settings page - API tab
5. Mobile view of sharing buttons

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of LightShare

== Support ==

For support, feature requests, or bug reports, please visit the [plugin support forum](https://wordpress.org/support/plugin/lightshare/).

== Privacy Policy ==

Lightshare does not collect any personal data from your visitors or your WordPress site. The plugin operates entirely within your WordPress installation and does not communicate with external services unless explicitly configured to do so.
