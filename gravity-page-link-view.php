<?php
/**
 * Plugin Name: Jezweb GF Pagelink
 * Plugin URI: https://jezweb.com.au/gravity-page-link-view
 * Description: Display all active Gravity Forms with page links where they are used. Supports all major page builders: Elementor, Divi, Beaver Builder, Oxygen, Bricks, Fusion (Avada), WPBakery, SiteOrigin, and more!
 * Version: 2.1.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Jezweb
 * Author URI: https://jezweb.com.au
 * Developer: Mahmud Farooque
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gravity-page-link-view
 * Domain Path: /languages
 * Update URI: https://github.com/mmhfarooque/GF-Pagelink
 * GitHub Plugin URI: mmhfarooque/GF-Pagelink
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'GPLV_VERSION', '2.2.0' );
define( 'GPLV_MIN_WP_VERSION', '5.0' );
define( 'GPLV_MIN_PHP_VERSION', '7.2' );
define( 'GPLV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GPLV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GPLV_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'GPLV_GITHUB_REPO', 'mmhfarooque/GF-Pagelink' );
define( 'GPLV_CACHE_EXPIRATION', 6 * HOUR_IN_SECONDS );

/**
 * GitHub Plugin Updater Class
 *
 * Handles automatic updates from GitHub releases
 */
class GPLV_GitHub_Updater {

    /**
     * Plugin slug
     */
    private $slug;

    /**
     * Plugin data
     */
    private $plugin_data;

    /**
     * GitHub username
     */
    private $github_username;

    /**
     * GitHub repository name
     */
    private $github_repo;

    /**
     * Plugin file path
     */
    private $plugin_file;

    /**
     * GitHub API response
     */
    private $github_response;

    /**
     * Constructor
     *
     * @param string $plugin_file Path to the main plugin file
     */
    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;

        // Parse GitHub repo
        $repo_parts = explode( '/', GPLV_GITHUB_REPO );
        $this->github_username = $repo_parts[0];
        $this->github_repo = $repo_parts[1];

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );

        // Enable auto-updates for this plugin
        add_filter( 'auto_update_plugin', array( $this, 'auto_update' ), 10, 2 );
        add_filter( 'plugin_auto_update_setting_html', array( $this, 'auto_update_setting_html' ), 10, 3 );
    }

    /**
     * Get plugin data
     */
    private function init_plugin_data() {
        $this->slug = plugin_basename( $this->plugin_file );
        $this->plugin_data = get_plugin_data( $this->plugin_file );
    }

    /**
     * Get GitHub release info with caching
     */
    private function get_github_release_info() {
        if ( ! empty( $this->github_response ) ) {
            return;
        }

        // Check transient cache first
        $cache_key = 'gplv_github_release_' . md5( GPLV_GITHUB_REPO );
        $cached_response = get_transient( $cache_key );

        if ( false !== $cached_response ) {
            $this->github_response = $cached_response;
            return;
        }

        // Build API URL for latest release
        $api_url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            esc_attr( $this->github_username ),
            esc_attr( $this->github_repo )
        );

        // Make API request with proper user agent
        $response = wp_remote_get( $api_url, array(
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
            ),
            'timeout' => 15,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return;
        }

        $body = wp_remote_retrieve_body( $response );
        $this->github_response = json_decode( $body );

        // Cache the response
        if ( ! empty( $this->github_response ) ) {
            set_transient( $cache_key, $this->github_response, GPLV_CACHE_EXPIRATION );
        }
    }

    /**
     * Check for plugin updates
     *
     * @param object $transient Update transient
     * @return object Modified transient
     */
    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $this->init_plugin_data();
        $this->get_github_release_info();

        if ( empty( $this->github_response ) ) {
            return $transient;
        }

        // Get version from GitHub (remove 'v' prefix if present)
        $github_version = ltrim( $this->github_response->tag_name, 'v' );
        $current_version = $this->plugin_data['Version'];

        // Compare versions
        if ( version_compare( $github_version, $current_version, '>' ) ) {
            // Find the zip file in assets or use zipball
            $download_url = $this->get_download_url();

            $plugin = array(
                'slug'        => dirname( $this->slug ),
                'plugin'      => $this->slug,
                'new_version' => $github_version,
                'url'         => $this->plugin_data['PluginURI'],
                'package'     => $download_url,
                'icons'       => array(),
                'banners'     => array(),
                'tested'      => '',
                'requires_php' => GPLV_MIN_PHP_VERSION,
                'compatibility' => new stdClass(),
            );

            $transient->response[ $this->slug ] = (object) $plugin;
        }

        return $transient;
    }

    /**
     * Get download URL from GitHub release
     */
    private function get_download_url() {
        // First, check for a zip file in release assets
        if ( ! empty( $this->github_response->assets ) ) {
            foreach ( $this->github_response->assets as $asset ) {
                if ( substr( $asset->name, -4 ) === '.zip' ) {
                    return $asset->browser_download_url;
                }
            }
        }

        // Fall back to zipball URL
        return $this->github_response->zipball_url;
    }

    /**
     * Plugin information for the update details popup
     *
     * @param false|object|array $result Result
     * @param string $action API action
     * @param object $args Arguments
     * @return false|object
     */
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }

        $this->init_plugin_data();

        if ( ! isset( $args->slug ) || $args->slug !== dirname( $this->slug ) ) {
            return $result;
        }

        $this->get_github_release_info();

        if ( empty( $this->github_response ) ) {
            return $result;
        }

        $github_version = ltrim( $this->github_response->tag_name, 'v' );

        $plugin_info = array(
            'name'              => $this->plugin_data['Name'],
            'slug'              => dirname( $this->slug ),
            'version'           => $github_version,
            'author'            => $this->plugin_data['Author'],
            'author_profile'    => $this->plugin_data['AuthorURI'],
            'homepage'          => $this->plugin_data['PluginURI'],
            'requires'          => GPLV_MIN_WP_VERSION,
            'tested'            => get_bloginfo( 'version' ),
            'requires_php'      => GPLV_MIN_PHP_VERSION,
            'downloaded'        => 0,
            'last_updated'      => $this->github_response->published_at,
            'sections'          => array(
                'description'   => $this->plugin_data['Description'],
                'changelog'     => $this->parse_changelog( $this->github_response->body ),
            ),
            'download_link'     => $this->get_download_url(),
        );

        return (object) $plugin_info;
    }

    /**
     * Parse changelog from GitHub release body
     *
     * @param string $body Release body/notes
     * @return string Formatted changelog
     */
    private function parse_changelog( $body ) {
        if ( empty( $body ) ) {
            return '<p>See the <a href="' . esc_url( 'https://github.com/' . GPLV_GITHUB_REPO . '/releases' ) . '" target="_blank" rel="noopener noreferrer">GitHub releases page</a> for the full changelog.</p>';
        }

        // Sanitize and convert markdown to basic HTML
        $changelog = wp_kses_post( nl2br( esc_html( $body ) ) );

        return '<div class="changelog">' . $changelog . '</div>';
    }

    /**
     * After install, rename folder to match expected plugin directory
     *
     * @param bool  $response   Installation response
     * @param array $hook_extra Extra arguments
     * @param array $result     Installation result
     * @return array Modified result
     */
    public function after_install( $response, $hook_extra, $result ) {
        global $wp_filesystem;

        $this->init_plugin_data();

        // Check if this is our plugin
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->slug ) {
            return $result;
        }

        // Get the expected plugin directory name
        $plugin_folder = dirname( $this->slug );
        $proper_destination = WP_PLUGIN_DIR . '/' . $plugin_folder;

        // Move to proper location if needed
        if ( $result['destination'] !== $proper_destination ) {
            $wp_filesystem->move( $result['destination'], $proper_destination );
            $result['destination'] = $proper_destination;
        }

        // Re-activate the plugin if it was active
        if ( is_plugin_active( $this->slug ) ) {
            activate_plugin( $this->slug );
        }

        return $result;
    }

    /**
     * Enable auto-updates for this plugin
     *
     * @param bool   $update Whether to auto-update
     * @param object $item   Plugin update data
     * @return bool
     */
    public function auto_update( $update, $item ) {
        if ( isset( $item->slug ) && dirname( $this->slug ) === $item->slug ) {
            // Allow users to control this via WordPress auto-update settings
            $auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
            return in_array( $this->slug, $auto_updates, true );
        }
        return $update;
    }

    /**
     * Customize auto-update setting HTML
     *
     * @param string $html   HTML output
     * @param string $plugin Plugin file
     * @param array  $plugin_data Plugin data
     * @return string
     */
    public function auto_update_setting_html( $html, $plugin, $plugin_data ) {
        if ( $plugin === $this->slug ) {
            // Add note about GitHub updates
            $html .= '<br><small style="color: #666;">' .
                     esc_html__( 'Updates from GitHub', 'gravity-page-link-view' ) .
                     '</small>';
        }
        return $html;
    }
}

/**
 * Initialize GitHub Updater
 */
function gplv_init_updater() {
    if ( is_admin() ) {
        new GPLV_GitHub_Updater( __FILE__ );
    }
}
add_action( 'init', 'gplv_init_updater' );

/**
 * Check plugin requirements
 */
function gplv_check_requirements() {
    global $wp_version;
    $errors = array();

    // Check WordPress version
    if ( version_compare( $wp_version, GPLV_MIN_WP_VERSION, '<' ) ) {
        $errors[] = sprintf(
            __( 'Gravity Page Link View requires WordPress version %s or higher. You are running version %s.', 'gravity-page-link-view' ),
            GPLV_MIN_WP_VERSION,
            $wp_version
        );
    }

    // Check PHP version
    if ( version_compare( PHP_VERSION, GPLV_MIN_PHP_VERSION, '<' ) ) {
        $errors[] = sprintf(
            __( 'Gravity Page Link View requires PHP version %s or higher. You are running version %s.', 'gravity-page-link-view' ),
            GPLV_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }

    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function gplv_requirements_notice() {
    $errors = gplv_check_requirements();

    if ( ! empty( $errors ) ) {
        ?>
        <div class="notice notice-error">
            <p><strong><?php esc_html_e( 'Jezweb GF Pagelink - Requirements Not Met', 'gravity-page-link-view' ); ?></strong></p>
            <ul style="list-style: disc; padding-left: 20px;">
                <?php foreach ( $errors as $error ) : ?>
                    <li><?php echo esc_html( $error ); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><?php esc_html_e( 'Please update your WordPress and PHP versions to use this plugin.', 'gravity-page-link-view' ); ?></p>
        </div>
        <?php

        // Deactivate the plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

// Check requirements before loading plugin
$requirement_errors = gplv_check_requirements();
if ( ! empty( $requirement_errors ) ) {
    add_action( 'admin_notices', 'gplv_requirements_notice' );
    return; // Stop plugin execution
}

/**
 * Main Plugin Class
 */
class Gravity_Page_Link_View {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_gplv_export_logs', array( $this, 'export_debug_logs' ) );
        add_action( 'admin_post_gplv_clear_logs', array( $this, 'clear_debug_logs' ) );

        // Add plugin action links
        add_filter( 'plugin_action_links_' . GPLV_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
    }

    /**
     * Add action links to plugins page
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=gravity-page-link-view' ) . '">' .
            __( 'View Forms', 'gravity-page-link-view' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }

    /**
     * Add row meta links to plugins page
     *
     * @param array  $links Existing links
     * @param string $file  Plugin file
     * @return array Modified links
     */
    public function plugin_row_meta( $links, $file ) {
        if ( GPLV_PLUGIN_BASENAME === $file ) {
            $row_meta = array(
                'github' => '<a href="https://github.com/' . GPLV_GITHUB_REPO . '" target="_blank">' .
                           __( 'GitHub', 'gravity-page-link-view' ) . '</a>',
                'support' => '<a href="https://github.com/' . GPLV_GITHUB_REPO . '/issues" target="_blank">' .
                            __( 'Support', 'gravity-page-link-view' ) . '</a>',
            );
            return array_merge( $links, $row_meta );
        }
        return $links;
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'gplv_settings', 'gplv_debug_mode' );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Jezweb GF Pagelink', 'gravity-page-link-view' ),
            __( 'GF Pagelink', 'gravity-page-link-view' ),
            'manage_options',
            'gravity-page-link-view',
            array( $this, 'render_admin_page' ),
            'dashicons-list-view',
            30
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_gravity-page-link-view' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'gplv-admin-style',
            GPLV_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            GPLV_VERSION
        );

        wp_enqueue_script(
            'gplv-admin-script',
            GPLV_PLUGIN_URL . 'assets/js/admin-script.js',
            array( 'jquery' ),
            GPLV_VERSION,
            true
        );
    }

    /**
     * Check if Gravity Forms is active
     */
    public function is_gravity_forms_active() {
        return class_exists( 'GFForms' );
    }

    /**
     * Check if debug mode is enabled
     */
    private function is_debug_mode() {
        return get_option( 'gplv_debug_mode', false );
    }

    /**
     * Log debug message
     */
    private function log_debug( $message, $context = array() ) {
        if ( ! $this->is_debug_mode() ) {
            return;
        }

        $logs = get_option( 'gplv_debug_logs', array() );

        // Limit to last 500 entries to prevent database bloat
        if ( count( $logs ) >= 500 ) {
            array_shift( $logs );
        }

        $log_entry = array(
            'timestamp' => current_time( 'mysql' ),
            'message'   => $message,
            'context'   => $context,
        );

        $logs[] = $log_entry;
        update_option( 'gplv_debug_logs', $logs );
    }

    /**
     * Get debug logs
     */
    private function get_debug_logs() {
        return get_option( 'gplv_debug_logs', array() );
    }

    /**
     * Clear debug logs
     */
    public function clear_debug_logs() {
        // Security: Verify nonce and capability
        if ( ! check_admin_referer( 'gplv_clear_logs', '_wpnonce', false ) ) {
            wp_die( esc_html__( 'Security check failed.', 'gravity-page-link-view' ), 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'gravity-page-link-view' ), 403 );
        }

        delete_option( 'gplv_debug_logs' );

        wp_safe_redirect( add_query_arg( array(
            'page'          => 'gravity-page-link-view',
            'tab'           => 'debug',
            'logs_cleared'  => '1',
        ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Export debug logs
     */
    public function export_debug_logs() {
        // Security: Verify nonce and capability
        if ( ! check_admin_referer( 'gplv_export_logs', '_wpnonce', false ) ) {
            wp_die( esc_html__( 'Security check failed.', 'gravity-page-link-view' ), 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'gravity-page-link-view' ), 403 );
        }

        $logs = $this->get_debug_logs();

        // Generate safe filename
        $filename = 'gplv-debug-logs-' . gmdate( 'Y-m-d-His' ) . '.log';
        $filename = sanitize_file_name( $filename );

        // Set security headers for download
        nocache_headers();
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: DENY' );

        echo "Jezweb GF Pagelink - Debug Logs\n";
        echo "Generated: " . esc_html( current_time( 'mysql' ) ) . "\n";
        echo "Plugin Version: " . esc_html( GPLV_VERSION ) . "\n";
        echo str_repeat( '=', 80 ) . "\n\n";

        if ( empty( $logs ) ) {
            echo "No debug logs found.\n";
        } else {
            foreach ( $logs as $log ) {
                // Sanitize log output
                $timestamp = isset( $log['timestamp'] ) ? esc_html( $log['timestamp'] ) : 'Unknown';
                $message = isset( $log['message'] ) ? esc_html( $log['message'] ) : '';

                echo "[{$timestamp}] {$message}\n";

                if ( ! empty( $log['context'] ) && is_array( $log['context'] ) ) {
                    // Remove any potentially sensitive data from context
                    $safe_context = $this->sanitize_log_context( $log['context'] );
                    echo "Context: " . wp_json_encode( $safe_context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
                }
                echo str_repeat( '-', 80 ) . "\n";
            }
        }

        exit;
    }

    /**
     * Sanitize log context to remove sensitive data
     *
     * @param array $context The context array
     * @return array Sanitized context
     */
    private function sanitize_log_context( $context ) {
        $sensitive_keys = array( 'password', 'secret', 'token', 'key', 'api_key', 'auth', 'credential' );
        $sanitized = array();

        foreach ( $context as $key => $value ) {
            // Check if key contains sensitive terms
            $is_sensitive = false;
            foreach ( $sensitive_keys as $sensitive ) {
                if ( stripos( $key, $sensitive ) !== false ) {
                    $is_sensitive = true;
                    break;
                }
            }

            if ( $is_sensitive ) {
                $sanitized[ $key ] = '[REDACTED]';
            } elseif ( is_array( $value ) ) {
                $sanitized[ $key ] = $this->sanitize_log_context( $value );
            } else {
                $sanitized[ $key ] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get all active Gravity Forms
     */
    public function get_all_forms() {
        if ( ! $this->is_gravity_forms_active() ) {
            return array();
        }

        if ( class_exists( 'GFAPI' ) ) {
            $forms = GFAPI::get_forms();

            // Filter only active forms
            $active_forms = array_filter( $forms, function( $form ) {
                return isset( $form['is_active'] ) && $form['is_active'];
            });

            return $active_forms;
        }

        return array();
    }

    /**
     * Find pages where a specific form is used
     *
     * @param int $form_id The Gravity Form ID
     * @return array Array of usage locations
     */
    public function find_form_usage( $form_id ) {
        // Security: Validate form_id is a positive integer
        $form_id = absint( $form_id );
        if ( $form_id <= 0 ) {
            return array();
        }

        $this->log_debug( "Starting form usage detection for Form ID: {$form_id}" );

        $usage_locations = array();

        // Get all public post types (including custom post types)
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        $this->log_debug( "Scanning post types", array( 'post_types' => $post_types ) );

        // Query all posts and pages
        $args = array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new WP_Query( $args );
        $this->log_debug( "Found {$query->post_count} posts/pages to scan" );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id      = get_the_ID();
                $post_content = get_post_field( 'post_content', $post_id );
                $post_title   = get_the_title();
                $post_type    = get_post_type();

                $found = false;
                $detection_method = '';

                // Check for Gravity Forms shortcode variations
                // [gravityform id="X"], [gravityforms id="X"], [gravityform id=X]
                if ( preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $post_content ) ) {
                    $found = true;
                    $detection_method = 'Shortcode in content';
                    $this->log_debug( "Found form via shortcode", array(
                        'post_id'    => $post_id,
                        'post_title' => $post_title,
                        'method'     => $detection_method,
                    ) );
                }

                // Check for Gravity Forms block (Gutenberg)
                if ( !$found && has_blocks( $post_content ) ) {
                    $blocks = parse_blocks( $post_content );
                    foreach ( $blocks as $block ) {
                        if ( $this->check_block_for_form( $block, $form_id ) ) {
                            $found = true;
                            $detection_method = 'Gutenberg block';
                            $this->log_debug( "Found form via Gutenberg block", array(
                                'post_id'    => $post_id,
                                'post_title' => $post_title,
                                'method'     => $detection_method,
                            ) );
                            break;
                        }
                    }
                }

                // Check for Elementor
                if ( !$found && $this->is_elementor_active() ) {
                    if ( $this->check_elementor_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'Elementor widget';
                        $this->log_debug( "Found form via Elementor", array(
                            'post_id'    => $post_id,
                            'post_title' => $post_title,
                        ) );
                    }
                }

                // Check for Divi Builder
                if ( !$found && $this->is_divi_active() ) {
                    if ( $this->check_divi_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'Divi Builder';
                    }
                }

                // Check for Beaver Builder
                if ( !$found && $this->is_beaver_builder_active() ) {
                    if ( $this->check_beaver_builder_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'Beaver Builder';
                    }
                }

                // Check for Oxygen Builder
                if ( !$found && $this->is_oxygen_active() ) {
                    if ( $this->check_oxygen_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'Oxygen Builder';
                    }
                }

                // Check for Bricks Builder
                if ( !$found && $this->is_bricks_active() ) {
                    if ( $this->check_bricks_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'Bricks Builder';
                    }
                }

                // Check for Fusion Builder (Avada)
                if ( !$found && $this->is_fusion_active() ) {
                    if ( $this->check_fusion_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'Fusion Builder (Avada)';
                    }
                }

                // Check for SiteOrigin Page Builder
                if ( !$found && $this->is_siteorigin_active() ) {
                    if ( $this->check_siteorigin_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'SiteOrigin Page Builder';
                    }
                }

                // Check for WPBakery (Visual Composer)
                if ( !$found && $this->is_wpbakery_active() ) {
                    if ( $this->check_wpbakery_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'WPBakery element';
                    }
                }

                // Check post meta for ACF or custom fields
                if ( !$found ) {
                    if ( $this->check_post_meta_for_form( $post_id, $form_id ) ) {
                        $found = true;
                        $detection_method = 'Custom field/ACF';
                    }
                }

                if ( $found ) {
                    $usage_locations[] = array(
                        'post_id'         => $post_id,
                        'post_title'      => $post_title,
                        'post_type'       => $post_type,
                        'edit_link'       => get_edit_post_link( $post_id ),
                        'view_link'       => get_permalink( $post_id ),
                        'detection_method' => $detection_method,
                    );
                }
            }
            wp_reset_postdata();
        }

        // Check widgets and sidebars
        $this->log_debug( "Checking widgets and sidebars" );
        $widget_locations = $this->check_widgets_for_form( $form_id );
        $usage_locations = array_merge( $usage_locations, $widget_locations );
        $this->log_debug( "Found " . count( $widget_locations ) . " widget locations" );

        // Check reusable blocks
        $this->log_debug( "Checking reusable blocks" );
        $reusable_blocks = $this->check_reusable_blocks_for_form( $form_id );
        $usage_locations = array_merge( $usage_locations, $reusable_blocks );
        $this->log_debug( "Found " . count( $reusable_blocks ) . " reusable blocks" );

        // Check theme builder locations (Elementor Pro, Divi Theme Builder, etc.)
        $this->log_debug( "Checking theme builder locations" );
        $theme_locations = $this->check_theme_builder_locations( $form_id );
        $usage_locations = array_merge( $usage_locations, $theme_locations );
        $this->log_debug( "Found " . count( $theme_locations ) . " theme builder locations" );

        $this->log_debug( "Completed form usage detection. Total locations found: " . count( $usage_locations ), array(
            'form_id'         => $form_id,
            'total_locations' => count( $usage_locations ),
        ) );

        return $usage_locations;
    }

    /**
     * Recursively check blocks for form ID
     */
    private function check_block_for_form( $block, $form_id ) {
        // Check if it's a Gravity Forms block
        if ( isset( $block['blockName'] ) && $block['blockName'] === 'gravityforms/form' ) {
            if ( isset( $block['attrs']['formId'] ) && $block['attrs']['formId'] == $form_id ) {
                return true;
            }
        }

        // Check inner blocks recursively
        if ( ! empty( $block['innerBlocks'] ) ) {
            foreach ( $block['innerBlocks'] as $inner_block ) {
                if ( $this->check_block_for_form( $inner_block, $form_id ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if Elementor is active
     */
    private function is_elementor_active() {
        return class_exists( '\Elementor\Plugin' );
    }

    /**
     * Check Elementor data for form usage
     */
    private function check_elementor_for_form( $post_id, $form_id ) {
        $elementor_data = get_post_meta( $post_id, '_elementor_data', true );

        if ( empty( $elementor_data ) ) {
            return false;
        }

        // Elementor data is stored as JSON
        $data = json_decode( $elementor_data, true );
        if ( ! is_array( $data ) ) {
            return false;
        }

        return $this->search_elementor_elements( $data, $form_id );
    }

    /**
     * Recursively search Elementor elements for form ID
     */
    private function search_elementor_elements( $elements, $form_id ) {
        foreach ( $elements as $element ) {
            // Check if this is a Gravity Forms widget (native)
            if ( isset( $element['widgetType'] ) &&
                 ( $element['widgetType'] === 'gravity_forms' || $element['widgetType'] === 'gravityforms' ) ) {
                if ( isset( $element['settings']['form_id'] ) && $element['settings']['form_id'] == $form_id ) {
                    return true;
                }
            }

            // Check for WordPress Form widget (wp-widget-form)
            if ( isset( $element['widgetType'] ) &&
                 ( stripos( $element['widgetType'], 'form' ) !== false ||
                   stripos( $element['widgetType'], 'wp-widget' ) !== false ) ) {

                // Check settings for form_id or formId
                if ( isset( $element['settings'] ) ) {
                    // Direct form_id check
                    if ( ( isset( $element['settings']['form_id'] ) && $element['settings']['form_id'] == $form_id ) ||
                         ( isset( $element['settings']['formId'] ) && $element['settings']['formId'] == $form_id ) ) {
                        return true;
                    }

                    // Check for widget settings (WordPress widgets store data differently)
                    if ( isset( $element['settings']['wp'] ) ) {
                        $wp_settings = $element['settings']['wp'];
                        if ( is_array( $wp_settings ) ) {
                            // Check for Gravity Forms widget data
                            if ( isset( $wp_settings['form_id'] ) && $wp_settings['form_id'] == $form_id ) {
                                return true;
                            }
                            // Check in widget instance data
                            if ( isset( $wp_settings['widget_instance'] ) && is_array( $wp_settings['widget_instance'] ) ) {
                                if ( isset( $wp_settings['widget_instance']['form_id'] ) &&
                                     $wp_settings['widget_instance']['form_id'] == $form_id ) {
                                    return true;
                                }
                            }
                        }
                    }

                    // Check entire settings as JSON string for form references
                    $settings_string = json_encode( $element['settings'] );

                    // Look for form_id in various formats
                    if ( preg_match( '/"form_id["\']?\s*[:=]\s*["\']?' . $form_id . '["\']?/i', $settings_string ) ||
                         preg_match( '/"formId["\']?\s*[:=]\s*["\']?' . $form_id . '["\']?/i', $settings_string ) ) {
                        return true;
                    }
                }
            }

            // Check settings for shortcodes (catch-all for any widget with shortcode)
            if ( isset( $element['settings'] ) ) {
                $settings_string = json_encode( $element['settings'] );
                if ( preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $settings_string ) ) {
                    return true;
                }
            }

            // Check inner elements recursively
            if ( ! empty( $element['elements'] ) ) {
                if ( $this->search_elementor_elements( $element['elements'], $form_id ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if WPBakery is active
     */
    private function is_wpbakery_active() {
        return class_exists( 'Vc_Manager' );
    }

    /**
     * Check WPBakery content for form usage
     */
    private function check_wpbakery_for_form( $post_id, $form_id ) {
        $post_content = get_post_field( 'post_content', $post_id );

        // WPBakery stores shortcodes in post content
        // Look for [vc_gravityform] or embedded [gravityform] in vc_ shortcodes
        if ( preg_match( '/\[vc_[^\]]*\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $post_content ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check post meta fields for form usage
     */
    private function check_post_meta_for_form( $post_id, $form_id ) {
        $all_meta = get_post_meta( $post_id );

        if ( empty( $all_meta ) ) {
            return false;
        }

        foreach ( $all_meta as $meta_key => $meta_values ) {
            foreach ( $meta_values as $meta_value ) {
                // Skip Elementor data (already checked separately)
                if ( $meta_key === '_elementor_data' ) {
                    continue;
                }

                // Convert to string if it's an array or object
                if ( is_array( $meta_value ) || is_object( $meta_value ) ) {
                    $meta_value = maybe_serialize( $meta_value );
                }

                // Check for form ID in meta value
                if ( is_string( $meta_value ) &&
                     preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $meta_value ) ) {
                    return true;
                }

                // Check for direct form ID references in serialized data
                if ( is_string( $meta_value ) &&
                     ( strpos( $meta_value, '"form_id";i:' . $form_id ) !== false ||
                       strpos( $meta_value, '"form_id";s:' . strlen( $form_id ) . ':"' . $form_id . '"' ) !== false ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if Divi is active
     */
    private function is_divi_active() {
        return defined( 'ET_BUILDER_VERSION' ) || function_exists( 'et_setup_theme' );
    }

    /**
     * Check Divi Builder for form usage
     */
    private function check_divi_for_form( $post_id, $form_id ) {
        // Divi stores builder data in post content with shortcodes
        $post_content = get_post_field( 'post_content', $post_id );

        // Check for Gravity Forms in Divi modules
        if ( preg_match( '/\[et_pb_[^\]]*\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $post_content ) ) {
            return true;
        }

        // Check for gravity form module
        if ( preg_match( '/\[et_pb_gravityform[^\]]*form_id=["\']?' . $form_id . '["\']?[^\]]*\]/i', $post_content ) ) {
            return true;
        }

        // Check Divi Library (saved modules)
        $divi_library = get_post_meta( $post_id, '_et_pb_use_builder', true );
        if ( $divi_library === 'on' ) {
            // Check all meta fields for form references
            $all_meta = get_post_meta( $post_id );
            foreach ( $all_meta as $meta_value_array ) {
                foreach ( $meta_value_array as $meta_value ) {
                    if ( is_string( $meta_value ) &&
                         ( strpos( $meta_value, 'form_id="' . $form_id . '"' ) !== false ||
                           strpos( $meta_value, 'form_id=' . $form_id ) !== false ) ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if Beaver Builder is active
     */
    private function is_beaver_builder_active() {
        return class_exists( 'FLBuilder' );
    }

    /**
     * Check Beaver Builder for form usage
     */
    private function check_beaver_builder_for_form( $post_id, $form_id ) {
        $builder_data = get_post_meta( $post_id, '_fl_builder_data', true );

        if ( empty( $builder_data ) || ! is_array( $builder_data ) ) {
            return false;
        }

        // Beaver Builder stores modules as objects in an array
        foreach ( $builder_data as $node ) {
            if ( ! is_object( $node ) ) {
                continue;
            }

            // Check for Gravity Forms module
            if ( isset( $node->type ) && $node->type === 'module' ) {
                if ( isset( $node->settings->type ) &&
                     ( $node->settings->type === 'gravity-form' || $node->settings->type === 'gravityforms' ) ) {
                    if ( isset( $node->settings->form_id ) && $node->settings->form_id == $form_id ) {
                        return true;
                    }
                }

                // Check for shortcode modules containing gravity forms
                if ( isset( $node->settings->type ) && $node->settings->type === 'shortcode' ) {
                    if ( isset( $node->settings->shortcode ) &&
                         preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i',
                                    $node->settings->shortcode ) ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if Oxygen is active
     */
    private function is_oxygen_active() {
        return defined( 'CT_VERSION' ) || class_exists( 'CT_Component' );
    }

    /**
     * Check Oxygen Builder for form usage
     */
    private function check_oxygen_for_form( $post_id, $form_id ) {
        $oxygen_data = get_post_meta( $post_id, 'ct_builder_shortcodes', true );

        if ( empty( $oxygen_data ) ) {
            return false;
        }

        // Oxygen stores data as serialized shortcodes
        if ( is_string( $oxygen_data ) ) {
            // Check for gravity form component or shortcode
            if ( preg_match( '/gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?/i', $oxygen_data ) ) {
                return true;
            }

            // Check for form_id in oxygen data
            if ( strpos( $oxygen_data, '"form_id":"' . $form_id . '"' ) !== false ||
                 strpos( $oxygen_data, '"form_id":' . $form_id ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if Bricks is active
     */
    private function is_bricks_active() {
        return defined( 'BRICKS_VERSION' );
    }

    /**
     * Check Bricks Builder for form usage
     */
    private function check_bricks_for_form( $post_id, $form_id ) {
        $bricks_data = get_post_meta( $post_id, '_bricks_page_content_2', true );

        if ( empty( $bricks_data ) || ! is_array( $bricks_data ) ) {
            return false;
        }

        return $this->search_bricks_elements( $bricks_data, $form_id );
    }

    /**
     * Recursively search Bricks elements
     */
    private function search_bricks_elements( $elements, $form_id ) {
        foreach ( $elements as $element ) {
            if ( ! is_array( $element ) ) {
                continue;
            }

            // Check for Gravity Forms element
            if ( isset( $element['name'] ) &&
                 ( $element['name'] === 'gravity-forms' || $element['name'] === 'gravityforms' ) ) {
                if ( isset( $element['settings']['form_id'] ) && $element['settings']['form_id'] == $form_id ) {
                    return true;
                }
            }

            // Check for shortcode element
            if ( isset( $element['name'] ) && $element['name'] === 'shortcode' ) {
                if ( isset( $element['settings']['shortcode'] ) &&
                     preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i',
                                $element['settings']['shortcode'] ) ) {
                    return true;
                }
            }

            // Check children recursively
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                if ( $this->search_bricks_elements( $element['children'], $form_id ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if Fusion Builder (Avada) is active
     */
    private function is_fusion_active() {
        return class_exists( 'FusionBuilder' ) || class_exists( 'Avada' );
    }

    /**
     * Check Fusion Builder for form usage
     */
    private function check_fusion_for_form( $post_id, $form_id ) {
        // Fusion stores data in post content as shortcodes
        $post_content = get_post_field( 'post_content', $post_id );

        // Check for Gravity Forms in Fusion elements
        if ( preg_match( '/\[fusion_[^\]]*\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $post_content ) ) {
            return true;
        }

        // Check for fusion_builder shortcodes containing gravity forms
        if ( preg_match( '/\[fusion_builder_[^\]]*gravity.*?' . $form_id . '/i', $post_content ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check if SiteOrigin is active
     */
    private function is_siteorigin_active() {
        return class_exists( 'SiteOrigin_Panels' );
    }

    /**
     * Check SiteOrigin Page Builder for form usage
     */
    private function check_siteorigin_for_form( $post_id, $form_id ) {
        $panels_data = get_post_meta( $post_id, 'panels_data', true );

        if ( empty( $panels_data ) || ! is_array( $panels_data ) ) {
            return false;
        }

        // Check widgets in panels
        if ( isset( $panels_data['widgets'] ) && is_array( $panels_data['widgets'] ) ) {
            foreach ( $panels_data['widgets'] as $widget ) {
                if ( ! is_array( $widget ) ) {
                    continue;
                }

                // Check for Gravity Forms widget
                if ( isset( $widget['info']['class'] ) &&
                     strpos( $widget['info']['class'], 'GravityForms' ) !== false ) {
                    if ( isset( $widget['form_id'] ) && $widget['form_id'] == $form_id ) {
                        return true;
                    }
                }

                // Check for text widgets with shortcodes
                $widget_string = json_encode( $widget );
                if ( preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $widget_string ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check widgets and sidebars for form usage
     */
    private function check_widgets_for_form( $form_id ) {
        $usage_locations = array();
        $sidebars_widgets = get_option( 'sidebars_widgets', array() );

        foreach ( $sidebars_widgets as $sidebar_id => $widget_ids ) {
            if ( $sidebar_id === 'wp_inactive_widgets' || empty( $widget_ids ) || ! is_array( $widget_ids ) ) {
                continue;
            }

            foreach ( $widget_ids as $widget_id ) {
                // Check for Gravity Forms widget
                if ( strpos( $widget_id, 'gravityforms' ) !== false ) {
                    $widget_number = str_replace( 'gravityforms-', '', $widget_id );
                    $widget_options = get_option( 'widget_gravityforms' );

                    if ( isset( $widget_options[ $widget_number ]['form_id'] ) &&
                         $widget_options[ $widget_number ]['form_id'] == $form_id ) {
                        $usage_locations[] = array(
                            'post_id'         => 0,
                            'post_title'      => 'Widget: ' . $sidebar_id,
                            'post_type'       => 'widget',
                            'edit_link'       => admin_url( 'widgets.php' ),
                            'view_link'       => home_url(),
                            'detection_method' => 'Widget',
                        );
                    }
                }

                // Check text widgets for shortcodes
                if ( strpos( $widget_id, 'text-' ) !== false ) {
                    $widget_number = str_replace( 'text-', '', $widget_id );
                    $widget_options = get_option( 'widget_text' );

                    if ( isset( $widget_options[ $widget_number ]['text'] ) ) {
                        if ( preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i',
                                        $widget_options[ $widget_number ]['text'] ) ) {
                            $usage_locations[] = array(
                                'post_id'         => 0,
                                'post_title'      => 'Text Widget: ' . $sidebar_id,
                                'post_type'       => 'widget',
                                'edit_link'       => admin_url( 'widgets.php' ),
                                'view_link'       => home_url(),
                                'detection_method' => 'Text widget',
                            );
                        }
                    }
                }
            }
        }

        return $usage_locations;
    }

    /**
     * Check reusable blocks for form usage
     */
    private function check_reusable_blocks_for_form( $form_id ) {
        $usage_locations = array();

        // Query reusable blocks (wp_block post type)
        $args = array(
            'post_type'      => 'wp_block',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id      = get_the_ID();
                $post_content = get_post_field( 'post_content', $post_id );
                $post_title   = get_the_title();

                $found = false;

                // Check for shortcodes in reusable block
                if ( preg_match( '/\[gravity_?forms?[^\]]*(?:id|form_id)=["\']?' . $form_id . '["\']?[^\]]*\]/i', $post_content ) ) {
                    $found = true;
                }

                // Check for Gravity Forms block
                if ( !$found && has_blocks( $post_content ) ) {
                    $blocks = parse_blocks( $post_content );
                    foreach ( $blocks as $block ) {
                        if ( $this->check_block_for_form( $block, $form_id ) ) {
                            $found = true;
                            break;
                        }
                    }
                }

                if ( $found ) {
                    $usage_locations[] = array(
                        'post_id'         => $post_id,
                        'post_title'      => 'Reusable Block: ' . $post_title,
                        'post_type'       => 'reusable_block',
                        'edit_link'       => get_edit_post_link( $post_id ),
                        'view_link'       => admin_url( 'edit.php?post_type=wp_block' ),
                        'detection_method' => 'Reusable Block',
                    );
                }
            }
            wp_reset_postdata();
        }

        return $usage_locations;
    }

    /**
     * Check theme builder locations for form usage
     */
    private function check_theme_builder_locations( $form_id ) {
        $usage_locations = array();

        // Check Elementor Theme Builder templates
        if ( $this->is_elementor_active() ) {
            $elementor_templates = $this->check_elementor_theme_builder( $form_id );
            $usage_locations = array_merge( $usage_locations, $elementor_templates );
        }

        // Check Divi Theme Builder
        if ( $this->is_divi_active() ) {
            $divi_templates = $this->check_divi_theme_builder( $form_id );
            $usage_locations = array_merge( $usage_locations, $divi_templates );
        }

        // Check Beaver Themer
        if ( $this->is_beaver_builder_active() ) {
            $beaver_templates = $this->check_beaver_themer( $form_id );
            $usage_locations = array_merge( $usage_locations, $beaver_templates );
        }

        return $usage_locations;
    }

    /**
     * Check Elementor Theme Builder templates
     */
    private function check_elementor_theme_builder( $form_id ) {
        $usage_locations = array();

        $args = array(
            'post_type'      => 'elementor_library',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();

                if ( $this->check_elementor_for_form( $post_id, $form_id ) ) {
                    $template_type = get_post_meta( $post_id, '_elementor_template_type', true );
                    $usage_locations[] = array(
                        'post_id'         => $post_id,
                        'post_title'      => get_the_title() . ' (Elementor ' . ucfirst( $template_type ) . ')',
                        'post_type'       => 'elementor_library',
                        'edit_link'       => get_edit_post_link( $post_id ),
                        'view_link'       => admin_url( 'edit.php?post_type=elementor_library' ),
                        'detection_method' => 'Elementor Template',
                    );
                }
            }
            wp_reset_postdata();
        }

        return $usage_locations;
    }

    /**
     * Check Divi Theme Builder templates
     */
    private function check_divi_theme_builder( $form_id ) {
        $usage_locations = array();

        $args = array(
            'post_type'      => 'et_theme_builder',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();

                if ( $this->check_divi_for_form( $post_id, $form_id ) ) {
                    $usage_locations[] = array(
                        'post_id'         => $post_id,
                        'post_title'      => get_the_title() . ' (Divi Theme Builder)',
                        'post_type'       => 'et_theme_builder',
                        'edit_link'       => get_edit_post_link( $post_id ),
                        'view_link'       => admin_url( 'edit.php?post_type=et_theme_builder' ),
                        'detection_method' => 'Divi Theme Builder',
                    );
                }
            }
            wp_reset_postdata();
        }

        return $usage_locations;
    }

    /**
     * Check Beaver Themer templates
     */
    private function check_beaver_themer( $form_id ) {
        $usage_locations = array();

        $args = array(
            'post_type'      => 'fl-theme-layout',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();

                if ( $this->check_beaver_builder_for_form( $post_id, $form_id ) ) {
                    $usage_locations[] = array(
                        'post_id'         => $post_id,
                        'post_title'      => get_the_title() . ' (Beaver Themer)',
                        'post_type'       => 'fl-theme-layout',
                        'edit_link'       => get_edit_post_link( $post_id ),
                        'view_link'       => admin_url( 'edit.php?post_type=fl-theme-layout' ),
                        'detection_method' => 'Beaver Themer',
                    );
                }
            }
            wp_reset_postdata();
        }

        return $usage_locations;
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'gravity-page-link-view' ) );
        }

        if ( ! $this->is_gravity_forms_active() ) {
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <div class="notice notice-error">
                    <p><?php esc_html_e( 'Gravity Forms is not active. Please install and activate Gravity Forms to use this plugin.', 'gravity-page-link-view' ); ?></p>
                </div>
            </div>
            <?php
            return;
        }

        // Get current tab with whitelist validation
        $allowed_tabs = array( 'forms', 'debug' );
        $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'forms';
        if ( ! in_array( $current_tab, $allowed_tabs, true ) ) {
            $current_tab = 'forms';
        }

        // Handle settings save
        if ( isset( $_POST['gplv_save_settings'] ) ) {
            if ( ! check_admin_referer( 'gplv_settings', '_wpnonce', false ) ) {
                wp_die( esc_html__( 'Security check failed.', 'gravity-page-link-view' ), 403 );
            }
            $debug_mode = isset( $_POST['gplv_debug_mode'] ) ? 1 : 0;
            update_option( 'gplv_debug_mode', $debug_mode );
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Settings saved.', 'gravity-page-link-view' ); ?></p>
            </div>
            <?php
        }

        ?>
        <div class="wrap gplv-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <!-- Tabs -->
            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gravity-page-link-view&tab=forms' ) ); ?>" class="nav-tab <?php echo 'forms' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Form Locations', 'gravity-page-link-view' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gravity-page-link-view&tab=debug' ) ); ?>" class="nav-tab <?php echo 'debug' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Debug Logs', 'gravity-page-link-view' ); ?>
                </a>
            </nav>

            <?php if ( $current_tab === 'forms' ) : ?>
                <?php
                $forms = $this->get_all_forms();
                $form_usage_data = array();

                // Get usage data for all forms
                foreach ( $forms as $form ) {
                    $form_id = $form['id'];
                    $form_usage_data[ $form_id ] = $this->find_form_usage( $form_id );
                }
                ?>

                <p class="description" style="margin-top: 15px;">
                    <?php _e( 'View all active Gravity Forms and the pages where they are used.', 'gravity-page-link-view' ); ?>
                </p>

                <div class="gplv-container">
                <div class="gplv-sidebar">
                    <h2><?php _e( 'Active Gravity Forms', 'gravity-page-link-view' ); ?></h2>

                    <?php if ( empty( $forms ) ) : ?>
                        <p class="gplv-no-forms"><?php _e( 'No active forms found.', 'gravity-page-link-view' ); ?></p>
                    <?php else : ?>
                        <ul class="gplv-form-list">
                            <?php foreach ( $forms as $form ) : ?>
                                <li class="gplv-form-item" data-form-id="<?php echo esc_attr( $form['id'] ); ?>">
                                    <div class="gplv-form-info">
                                        <span class="gplv-form-id">#<?php echo esc_html( $form['id'] ); ?></span>
                                        <span class="gplv-form-title"><?php echo esc_html( $form['title'] ); ?></span>
                                    </div>
                                    <div class="gplv-form-meta">
                                        <span class="gplv-usage-count">
                                            <?php
                                            $usage_count = count( $form_usage_data[ $form['id'] ] );
                                            printf(
                                                _n( 'Used in %d location', 'Used in %d locations', $usage_count, 'gravity-page-link-view' ),
                                                $usage_count
                                            );
                                            ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="gplv-content">
                    <h2><?php _e( 'Form Usage Locations', 'gravity-page-link-view' ); ?></h2>

                    <?php if ( empty( $forms ) ) : ?>
                        <p><?php _e( 'No forms to display.', 'gravity-page-link-view' ); ?></p>
                    <?php else : ?>
                        <?php foreach ( $forms as $form ) : ?>
                            <div class="gplv-usage-section" data-form-id="<?php echo esc_attr( $form['id'] ); ?>">
                                <h3>
                                    <?php echo esc_html( $form['title'] ); ?>
                                    <span class="gplv-form-id-badge">#<?php echo esc_html( $form['id'] ); ?></span>
                                </h3>

                                <?php if ( empty( $form_usage_data[ $form['id'] ] ) ) : ?>
                                    <p class="gplv-no-usage">
                                        <?php _e( 'This form is not currently used on any published pages or posts.', 'gravity-page-link-view' ); ?>
                                    </p>
                                <?php else : ?>
                                    <table class="gplv-usage-table widefat striped">
                                        <thead>
                                            <tr>
                                                <th><?php _e( 'Title', 'gravity-page-link-view' ); ?></th>
                                                <th><?php _e( 'Type', 'gravity-page-link-view' ); ?></th>
                                                <th><?php _e( 'Detection Method', 'gravity-page-link-view' ); ?></th>
                                                <th><?php _e( 'Actions', 'gravity-page-link-view' ); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $form_usage_data[ $form['id'] ] as $location ) : ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo esc_html( $location['post_title'] ); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="gplv-post-type-badge">
                                                            <?php echo esc_html( ucfirst( $location['post_type'] ) ); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="gplv-detection-badge">
                                                            <?php echo esc_html( $location['detection_method'] ); ?>
                                                        </span>
                                                    </td>
                                                    <td class="gplv-actions">
                                                        <a href="<?php echo esc_url( $location['view_link'] ); ?>"
                                                           class="button button-small"
                                                           target="_blank">
                                                            <?php _e( 'View Page', 'gravity-page-link-view' ); ?>
                                                        </a>
                                                        <a href="<?php echo esc_url( $location['edit_link'] ); ?>"
                                                           class="button button-small button-primary">
                                                            <?php _e( 'Edit Page', 'gravity-page-link-view' ); ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ( $current_tab === 'debug' ) : ?>
                <div class="gplv-debug-tab" style="margin-top: 20px;">
                    <!-- Settings Section -->
                    <div class="gplv-debug-settings" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                        <h2><?php _e( 'Debug Settings', 'gravity-page-link-view' ); ?></h2>
                        <form method="post" action="">
                            <?php wp_nonce_field( 'gplv_settings' ); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="gplv_debug_mode"><?php _e( 'Enable Debug Mode', 'gravity-page-link-view' ); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="gplv_debug_mode" name="gplv_debug_mode" value="1" <?php checked( $this->is_debug_mode(), true ); ?>>
                                            <?php _e( 'Enable detailed logging of form detection attempts', 'gravity-page-link-view' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e( 'When enabled, the plugin will log all detection attempts, helping you troubleshoot why forms may not be detected.', 'gravity-page-link-view' ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button( __( 'Save Settings', 'gravity-page-link-view' ), 'primary', 'gplv_save_settings' ); ?>
                        </form>
                    </div>

                    <!-- Debug Logs Section -->
                    <div class="gplv-debug-logs" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0;"><?php _e( 'Debug Logs', 'gravity-page-link-view' ); ?></h2>
                            <div>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
                                    <?php wp_nonce_field( 'gplv_export_logs' ); ?>
                                    <input type="hidden" name="action" value="gplv_export_logs">
                                    <button type="submit" class="button">
                                        <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                                        <?php _e( 'Export Logs', 'gravity-page-link-view' ); ?>
                                    </button>
                                </form>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline; margin-left: 10px;">
                                    <?php wp_nonce_field( 'gplv_clear_logs' ); ?>
                                    <input type="hidden" name="action" value="gplv_clear_logs">
                                    <button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all debug logs?', 'gravity-page-link-view' ) ); ?>')">
                                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Clear Logs', 'gravity-page-link-view' ); ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if ( isset( $_GET['logs_cleared'] ) && '1' === $_GET['logs_cleared'] ) : ?>
                            <div class="notice notice-success is-dismissible">
                                <p><?php esc_html_e( 'Debug logs cleared successfully.', 'gravity-page-link-view' ); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php
                        $logs = $this->get_debug_logs();
                        if ( ! $this->is_debug_mode() ) :
                        ?>
                            <div class="notice notice-info inline">
                                <p><?php esc_html_e( 'Debug mode is currently disabled. Enable it above to start logging.', 'gravity-page-link-view' ); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ( empty( $logs ) ) : ?>
                            <p style="color: #646970; font-style: italic;">
                                <?php esc_html_e( 'No debug logs recorded yet. Enable debug mode and scan for forms to see logs here.', 'gravity-page-link-view' ); ?>
                            </p>
                        <?php else : ?>
                            <div class="gplv-log-entries" style="background: #f6f7f7; padding: 15px; max-height: 600px; overflow-y: auto; font-family: monospace; font-size: 13px; border: 1px solid #dcdcde;">
                                <?php
                                // Reverse to show newest first
                                $logs = array_reverse( $logs );
                                foreach ( $logs as $log ) :
                                ?>
                                    <div class="gplv-log-entry" style="margin-bottom: 15px; padding: 10px; background: #fff; border-left: 3px solid #2271b1;">
                                        <div style="color: #646970; font-size: 11px; margin-bottom: 5px;">
                                            <?php echo esc_html( $log['timestamp'] ); ?>
                                        </div>
                                        <div style="color: #1d2327; margin-bottom: 5px;">
                                            <?php echo esc_html( $log['message'] ); ?>
                                        </div>
                                        <?php if ( ! empty( $log['context'] ) ) : ?>
                                            <details style="margin-top: 5px;">
                                                <summary style="cursor: pointer; color: #2271b1; font-size: 12px;">
                                                    <?php _e( 'View Context', 'gravity-page-link-view' ); ?>
                                                </summary>
                                                <pre style="background: #f0f0f1; padding: 10px; margin-top: 5px; overflow-x: auto; font-size: 11px;"><?php echo esc_html( json_encode( $log['context'], JSON_PRETTY_PRINT ) ); ?></pre>
                                            </details>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p style="margin-top: 10px; color: #646970; font-size: 12px;">
                                <?php printf( __( 'Showing %d log entries (max 500)', 'gravity-page-link-view' ), count( $logs ) ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}

/**
 * Plugin Activation Hook
 */
function gplv_activate() {
    // Set default options if they don't exist
    add_option( 'gplv_debug_mode', 0 );
    add_option( 'gplv_version', GPLV_VERSION );

    // Log activation
    if ( get_option( 'gplv_debug_mode' ) ) {
        $logs = get_option( 'gplv_debug_logs', array() );
        $logs[] = array(
            'timestamp' => current_time( 'mysql' ),
            'message'   => 'Plugin activated - Version ' . GPLV_VERSION,
            'context'   => array( 'version' => GPLV_VERSION ),
        );
        update_option( 'gplv_debug_logs', $logs );
    }
}
register_activation_hook( __FILE__, 'gplv_activate' );

/**
 * Check and handle plugin upgrades
 */
function gplv_check_version() {
    $installed_version = get_option( 'gplv_version', '0.0.0' );

    // If version is different, run upgrade
    if ( version_compare( $installed_version, GPLV_VERSION, '<' ) ) {
        gplv_upgrade( $installed_version );
        update_option( 'gplv_version', GPLV_VERSION );

        // Log upgrade
        if ( get_option( 'gplv_debug_mode' ) ) {
            $logs = get_option( 'gplv_debug_logs', array() );
            $logs[] = array(
                'timestamp' => current_time( 'mysql' ),
                'message'   => "Plugin upgraded from version {$installed_version} to " . GPLV_VERSION,
                'context'   => array(
                    'from_version' => $installed_version,
                    'to_version'   => GPLV_VERSION,
                ),
            );
            update_option( 'gplv_debug_logs', $logs );
        }
    }
}
add_action( 'plugins_loaded', 'gplv_check_version', 5 );

/**
 * Handle version-specific upgrades
 *
 * @param string $from_version The version being upgraded from
 */
function gplv_upgrade( $from_version ) {
    // Future version-specific upgrade steps go here

    // Example for future versions:
    /*
    if ( version_compare( $from_version, '2.1.0', '<' ) ) {
        // Upgrade steps for 2.1.0
        // e.g., add new options, migrate data structures, etc.
    }

    if ( version_compare( $from_version, '3.0.0', '<' ) ) {
        // Upgrade steps for 3.0.0
    }
    */

    // For now, just ensure all required options exist
    add_option( 'gplv_debug_mode', 0 );
    add_option( 'gplv_debug_logs', array() );
}

/**
 * Initialize the plugin
 */
function gplv_init() {
    return Gravity_Page_Link_View::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'gplv_init', 10 );
