<?php
/**
 * Gravity Page Link View Uninstall
 *
 * Fired when the plugin is uninstalled.
 *
 * @package   Gravity_Page_Link_View
 * @author    Jezweb
 * @developer Mahmud Farooque
 * @license   GPL-2.0+
 * @link      https://jezweb.com.au
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
function gplv_uninstall_cleanup() {
    // Delete plugin options
    delete_option( 'gplv_debug_mode' );
    delete_option( 'gplv_debug_logs' );
    delete_option( 'gplv_version' );

    // Delete settings
    delete_option( 'gplv_settings' );

    // Clean up any transients (if we add them in future versions)
    delete_transient( 'gplv_cache' );

    // Optional: Clean up for multisite
    if ( is_multisite() ) {
        global $wpdb;

        // Get all blog IDs
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );

            // Delete options for each site
            delete_option( 'gplv_debug_mode' );
            delete_option( 'gplv_debug_logs' );
            delete_option( 'gplv_version' );
            delete_option( 'gplv_settings' );

            restore_current_blog();
        }
    }
}

// Run cleanup
gplv_uninstall_cleanup();
