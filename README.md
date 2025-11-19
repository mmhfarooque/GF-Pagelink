# Gravity Page Link View

A WordPress plugin that displays all active Gravity Forms with page links showing where they are used on your website.

## Description

Gravity Page Link View provides a clean dashboard interface that shows:
- All active Gravity Forms on the left sidebar
- Page/post locations where each form is used on the right content area
- Direct links to view or edit pages containing the forms

This plugin is perfect for WordPress administrators who need to quickly find where specific Gravity Forms are being used across their website.

## Author

**Jezweb**
Website: [https://jezweb.com.au](https://jezweb.com.au)
Developer: Mahmud Farooque

## Features

### Form Detection
- **Universal Page Builder Support**: Detects forms in ALL major page builders:
  - Elementor (including Theme Builder templates)
  - Divi Builder (including Theme Builder)
  - Beaver Builder (including Themer)
  - Oxygen Builder
  - Bricks Builder
  - Fusion Builder (Avada theme)
  - WPBakery (Visual Composer)
  - SiteOrigin Page Builder
- **Content Editor Support**:
  - Gutenberg blocks (native Gravity Forms blocks)
  - Classic Editor shortcodes `[gravityform]`, `[gravityforms]`
  - Reusable blocks and patterns
- **Advanced Detection**:
  - Widgets and sidebars
  - Custom fields (ACF compatible)
  - All custom post types
  - Theme builder locations (headers, footers, templates)

### Dashboard Interface
- **Two-Column Layout**: Forms list on left, usage details on right
- **Detection Method Tracking**: Shows HOW each form was detected
- **Tabbed Interface**: Separate tabs for Form Locations and Debug Logs
- **Usage Statistics**: Real-time count of where each form is used
- **Direct Navigation**: Quick links to view or edit pages
- **Responsive Design**: Works on desktop, tablet, and mobile

### Debug & Troubleshooting
- **Debug Logging System**: Enable detailed logging of detection attempts
- **Log Viewer**: View all detection logs directly in dashboard
- **Log Export**: Download logs as .log file for troubleshooting
- **Clear Logs**: One-click log cleanup
- **Upgrade Handling**: Automatic version tracking and upgrade management

## Requirements

**Minimum Requirements:**
- **WordPress:** 5.0 or higher
- **PHP:** 7.2 or higher
- **Gravity Forms:** Latest version (plugin must be active)

**Recommended:**
- WordPress: 6.0 or higher
- PHP: 8.0 or higher

**Note:** The plugin will automatically check requirements on activation and display an error message if your environment doesn't meet the minimum requirements.

## Installation

1. Upload the `gravity-page-link-view` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure Gravity Forms is installed and activated
4. Go to **GF Page Links** in the WordPress admin menu

## Usage

1. Navigate to **GF Page Links** in your WordPress admin menu
2. You'll see all active Gravity Forms listed on the left sidebar
3. Each form shows:
   - Form ID number
   - Form title
   - Number of locations where it's used
4. Click on any form to view its usage details
5. The right panel shows:
   - Page/post titles where the form appears
   - Post type (Page or Post)
   - Action buttons to view or edit each page

## Screenshots

The dashboard displays:
- Left Sidebar: List of all active Gravity Forms with usage counts
- Right Content: Detailed table showing where each form is used
- Action buttons for quick navigation to pages

## How It Works

The plugin scans all published posts and pages for:
1. Gravity Forms shortcodes in the content
2. Gravity Forms Gutenberg blocks
3. Nested blocks within other blocks

It then displays this information in an organized, easy-to-read format.

## Support for Different Form Implementations

This plugin detects Gravity Forms in:
- **Shortcode format**: `[gravityform id="1" title="false" description="false"]`
- **Gutenberg blocks**: Native Gravity Forms blocks
- **Nested blocks**: Forms inside columns, groups, or other container blocks

## Frequently Asked Questions

### Does this plugin work with Gravity Forms version X?

This plugin is compatible with all modern versions of Gravity Forms that support the GFAPI class.

### Will it detect forms in custom post types?

Yes! Version 2.0.0 and above automatically scans ALL public post types, including custom post types.

### Does it affect my site's performance?

The plugin only runs in the WordPress admin area and does not affect your frontend performance. The scanning happens only when you view the dashboard page.

### Can I export debug logs?

Yes! Go to the Debug Logs tab and click "Export Logs" to download a .log file with all detection attempts and results.

### What if my form isn't being detected?

1. Go to the "Debug Logs" tab
2. Enable "Debug Mode" and save
3. Go back to "Form Locations" tab (this triggers a scan)
4. Return to "Debug Logs" to see exactly what was checked and why the form wasn't found

## Changelog

### Version 2.0.1 (Current)
**Bug Fix Release**

**Requirements:**
- WordPress 5.0+
- PHP 7.2+
- Gravity Forms (active)

#### Bug Fixes
- 🐛 **Fixed Elementor WordPress Form Widget Detection**: Now properly detects Gravity Forms added via the WordPress "Form" widget in Elementor
- 🐛 **Enhanced Elementor Detection**: Added support for wp-widget-* widgets and any widget containing "form" in the name
- 🐛 **Improved Form ID Matching**: Better regex patterns for form_id and formId in various formats
- 🔧 **Better Widget Settings Parsing**: Now checks wp.widget_instance and other nested widget data structures

#### What This Fixes
If you were using the WordPress Widgets → Form widget in Elementor (not the native Gravity Forms Elementor widget), the plugin wasn't detecting your forms. This release fixes that detection.

### Version 2.0.0
**Major Update - Universal Page Builder Support**

**Requirements:**
- WordPress 5.0+
- PHP 7.2+
- Gravity Forms (active)

#### New Features
- ✨ **Universal Page Builder Detection**: Added support for 9 major page builders
  - Elementor & Elementor Pro Theme Builder
  - Divi Builder & Divi Theme Builder
  - Beaver Builder & Beaver Themer
  - Oxygen Builder
  - Bricks Builder
  - Fusion Builder (Avada)
  - WPBakery (Visual Composer)
  - SiteOrigin Page Builder
- ✨ **Reusable Blocks**: Detects forms in WordPress reusable blocks
- ✨ **Theme Builder Templates**: Finds forms in headers, footers, and templates
- ✨ **Widget Support**: Detects forms in sidebars and widget areas
- ✨ **Custom Post Types**: Automatically scans all public post types
- ✨ **Detection Method Tracking**: Shows exactly HOW each form was detected
- ✨ **Debug Logging System**: Complete troubleshooting and logging system
- ✨ **Log Viewer**: View detection logs directly in dashboard
- ✨ **Log Export**: Download logs as .log file
- ✨ **Tabbed Interface**: Separate tabs for Form Locations and Debug Logs

#### Improvements
- 🔧 **Enhanced Shortcode Detection**: Better regex patterns for all variations
- 🔧 **Custom Field Support**: Scans ACF and all post meta fields
- 🔧 **Upgrade System**: Automatic version tracking and upgrade handling
- 🔧 **Activation Hook**: Proper initialization on plugin activation
- 🔧 **Uninstall Cleanup**: Clean removal of all plugin data

#### Developer
- 📦 **Proper Versioning**: Database version tracking for upgrades
- 📦 **Version Requirements**: Automatic WordPress 5.0+ and PHP 7.2+ checking
- 📦 **Uninstall Script**: Complete cleanup on plugin deletion
- 📦 **Multisite Support**: Works on WordPress multisite networks
- 📦 **WordPress Standards**: Follows WordPress coding standards

### Version 1.0.0
**Initial Release**
- Basic form detection
- Display all active Gravity Forms
- Show page locations where forms are used
- Support for shortcodes and Gutenberg blocks
- Responsive dashboard design
- Keyboard navigation support

## Development

### File Structure
```
gravity-page-link-view/
├── gravity-page-link-view.php    # Main plugin file (1300+ lines)
├── uninstall.php                 # Uninstall cleanup script
├── assets/
│   ├── css/
│   │   ├── admin-style.css       # Admin dashboard styles
│   │   └── index.php             # Security file
│   ├── js/
│   │   ├── admin-script.js       # Admin dashboard JavaScript
│   │   └── index.php             # Security file
│   └── index.php                 # Security file
├── index.php                     # Security file
└── README.md                      # Documentation
```

### Hooks and Filters

The plugin uses standard WordPress hooks:
- `admin_menu` - Adds the admin menu page
- `admin_enqueue_scripts` - Enqueues CSS and JavaScript

## Future Enhancements

Potential features for future versions:
- CSV export of form usage data
- Form usage analytics and statistics
- Bulk operations on forms
- Email notifications when forms are added/removed
- Performance optimization for large sites
- Integration with popular SEO plugins

## Credits

**Author:** Jezweb
**Developer:** Mahmud Farooque
**Website:** [https://jezweb.com.au](https://jezweb.com.au)

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Version Management

### For Developers: How to Update Plugin Version

When releasing a new version, follow these steps:

1. **Update Version Numbers:**
   - Line 6: Plugin header `Version: X.X.X`
   - Line 21: `define( 'GPLV_VERSION', 'X.X.X' );`

2. **Add Changelog Entry:**
   - Update README.md with new version changes

3. **Add Upgrade Steps (if needed):**
   - Add version-specific code in `gplv_upgrade()` function
   ```php
   if ( version_compare( $from_version, 'X.X.X', '<' ) ) {
       // Your upgrade steps here
   }
   ```

4. **Test Upgrade:**
   - Install over previous version
   - Verify settings are preserved
   - Check new features work correctly

5. **Create Release Zip:**
   - Package plugin directory
   - Test installation on clean WordPress

### Versioning Scheme

- **Major (X.0.0)**: Breaking changes, major new features
- **Minor (2.X.0)**: New features, enhancements, no breaking changes
- **Patch (2.0.X)**: Bug fixes, small improvements

## Support

For issues, questions, or contributions, please visit [https://jezweb.com.au](https://jezweb.com.au)
