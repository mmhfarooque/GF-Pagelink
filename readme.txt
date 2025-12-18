=== Jezweb GF Pagelink ===
Contributors: jezweb, mmhfarooque
Tags: gravity forms, form locations, page builder, elementor, divi
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 2.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display all active Gravity Forms with page links where they are used. Supports all major page builders.

== Description ==

**Jezweb GF Pagelink** is a powerful WordPress plugin that helps you track where your Gravity Forms are being used across your website. It scans all pages, posts, custom post types, widgets, and theme builder templates to find form locations.

= Key Features =

* **Comprehensive Form Detection** - Finds Gravity Forms in content, shortcodes, blocks, widgets, and page builders
* **Page Builder Support** - Works with Elementor, Divi, Beaver Builder, Oxygen, Bricks, Fusion (Avada), WPBakery, and SiteOrigin
* **Theme Builder Support** - Detects forms in Elementor Theme Builder, Divi Theme Builder, and Beaver Themer templates
* **Reusable Blocks** - Scans WordPress reusable blocks for form usage
* **Widget Detection** - Finds forms in sidebar widgets
* **Debug Mode** - Built-in logging to help troubleshoot form detection issues
* **Auto Updates** - Automatic updates from GitHub releases

= Supported Page Builders =

* Elementor (including Theme Builder)
* Divi Builder (including Theme Builder)
* Beaver Builder (including Beaver Themer)
* Oxygen Builder
* Bricks Builder
* Fusion Builder (Avada)
* WPBakery (Visual Composer)
* SiteOrigin Page Builder

= Requirements =

* WordPress 5.0 or higher
* PHP 7.2 or higher
* Gravity Forms plugin (active)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/gravity-page-link-view/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to 'GF Pagelink' in the WordPress admin menu to view form locations.

== Frequently Asked Questions ==

= Does this plugin require Gravity Forms? =

Yes, Gravity Forms must be installed and activated for this plugin to work.

= What page builders are supported? =

The plugin supports Elementor, Divi, Beaver Builder, Oxygen, Bricks, Fusion Builder (Avada), WPBakery, and SiteOrigin Page Builder.

= How do I enable debug mode? =

Go to GF Pagelink > Debug Logs tab and enable the debug mode checkbox to start logging detection attempts.

= Does this plugin support auto-updates? =

Yes! The plugin checks for updates from GitHub releases and can be auto-updated using WordPress's built-in auto-update feature.

== Screenshots ==

1. Main interface showing all active Gravity Forms and their usage locations
2. Debug logs interface for troubleshooting

== Changelog ==

= 2.2.0 =
* SECURITY: Added capability checks to export and clear log functions
* SECURITY: Added security headers for file downloads (X-Content-Type-Options, X-Frame-Options)
* SECURITY: Added sensitive data redaction in debug log exports
* SECURITY: Added form_id validation to prevent ReDoS attacks
* SECURITY: Added API response caching to prevent rate limiting
* SECURITY: Added whitelist validation for tab parameters
* SECURITY: Improved nonce verification with proper error handling
* SECURITY: Added proper output escaping throughout (esc_html_e, esc_url, esc_js)
* SECURITY: Added SSL verification for GitHub API requests
* SECURITY: Use wp_safe_redirect instead of wp_redirect
* IMPROVED: Added User-Agent header for GitHub API requests
* IMPROVED: Better error handling for security failures

= 2.1.0 =
* NEW: Added GitHub-based auto-update functionality
* NEW: WordPress native auto-update support
* NEW: Plugin action links on plugins page
* NEW: GitHub and Support links in plugin row meta
* IMPROVED: Plugin renamed to "Jezweb GF Pagelink"
* IMPROVED: Better plugin organization and code structure

= 2.0.1 =
* FIXED: Elementor WordPress Form Widget detection
* IMPROVED: Enhanced Elementor detection for wp-widget-* widgets
* IMPROVED: Better form_id matching with improved regex patterns
* IMPROVED: Support for nested widget data structures

= 2.0.0 =
* NEW: Universal page builder support
* NEW: Detection method tracking shows how forms were found
* NEW: Debug mode with detailed logging
* NEW: Support for Elementor, Divi, Beaver Builder, Oxygen, Bricks, Fusion, WPBakery, SiteOrigin
* NEW: Theme builder template scanning
* NEW: Reusable block detection
* NEW: Widget and sidebar scanning
* IMPROVED: Complete UI redesign with two-column layout
* IMPROVED: Better performance with optimized queries

= 1.0.0 =
* Initial release
* Basic Gravity Forms detection in pages and posts

== Upgrade Notice ==

= 2.2.0 =
SECURITY RELEASE: This update includes important security hardening. All users should upgrade immediately.

= 2.1.0 =
This update adds auto-update capability from GitHub releases and WordPress native auto-update support.

= 2.0.1 =
Bug fix for Elementor WordPress Form Widget detection.

= 2.0.0 =
Major update with universal page builder support and new UI. Recommended for all users.
