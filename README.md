# Gravity Page Link View

A WordPress plugin that displays all active Gravity Forms with page links showing where they are used on your website.

## Description

Gravity Page Link View provides a clean dashboard interface that shows:
- All active Gravity Forms on the left sidebar
- Page/post locations where each form is used on the right content area
- Direct links to view or edit pages containing the forms

This plugin is perfect for WordPress administrators who need to quickly find where specific Gravity Forms are being used across their website.

## Author

**Mahmud Farooque**

## Features

- **Two-Column Dashboard Layout**: Easy-to-navigate interface with forms on the left and usage details on the right
- **Active Forms Only**: Displays only active Gravity Forms
- **Comprehensive Form Detection**: Detects forms in:
  - Classic Editor shortcodes `[gravityform id="X"]`
  - Gutenberg blocks (Gravity Forms blocks)
- **Usage Statistics**: Shows how many locations each form is used in
- **Direct Navigation**: Quick links to view or edit pages containing forms
- **Responsive Design**: Works well on desktop and mobile devices
- **Keyboard Navigation**: Use arrow keys to navigate between forms
- **Smooth Interactions**: Click on any form to see its usage details instantly

## Requirements

- WordPress 5.0 or higher
- Gravity Forms plugin (active)
- PHP 7.0 or higher

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

Currently, the plugin scans only 'post' and 'page' post types. Custom post type support can be added if needed.

### Does it affect my site's performance?

The plugin only runs in the WordPress admin area and does not affect your frontend performance. The scanning happens only when you view the dashboard page.

### Can I export this data?

The current version displays the data in a dashboard. Export functionality can be added in future versions.

## Changelog

### Version 1.0.0
- Initial release
- Display all active Gravity Forms
- Show page locations where forms are used
- Support for shortcodes and Gutenberg blocks
- Responsive dashboard design
- Keyboard navigation support

## Development

### File Structure
```
gravity-page-link-view/
├── gravity-page-link-view.php    # Main plugin file
├── assets/
│   ├── css/
│   │   └── admin-style.css       # Admin dashboard styles
│   └── js/
│       └── admin-script.js       # Admin dashboard JavaScript
└── README.md                      # Documentation
```

### Hooks and Filters

The plugin uses standard WordPress hooks:
- `admin_menu` - Adds the admin menu page
- `admin_enqueue_scripts` - Enqueues CSS and JavaScript

## Future Enhancements

Potential features for future versions:
- Custom post type support
- Export to CSV functionality
- Widget/template detection
- Form usage analytics
- Bulk operations

## Credits

Developed by **Mahmud Farooque**

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

## Support

For issues, questions, or contributions, please contact the developer.
