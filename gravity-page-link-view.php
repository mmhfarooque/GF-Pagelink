<?php
/**
 * Plugin Name: Gravity Page Link View
 * Plugin URI: https://jezweb.com
 * Description: Display all active Gravity Forms with page links where they are used
 * Version: 1.0.0
 * Author: Mahmud Farooque
 * Author URI: https://jezweb.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gravity-page-link-view
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'GPLV_VERSION', '1.0.0' );
define( 'GPLV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GPLV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

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
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Gravity Page Link View', 'gravity-page-link-view' ),
            __( 'GF Page Links', 'gravity-page-link-view' ),
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
     */
    public function find_form_usage( $form_id ) {
        $usage_locations = array();

        // Query all posts and pages
        $args = array(
            'post_type'      => array( 'post', 'page' ),
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
                $post_type    = get_post_type();

                $found = false;

                // Check for Gravity Forms shortcode [gravityform id="X"]
                if ( preg_match( '/\[gravityform[^\]]*id=["\']?' . $form_id . '["\']?[^\]]*\]/i', $post_content ) ) {
                    $found = true;
                }

                // Check for Gravity Forms block (Gutenberg)
                if ( has_blocks( $post_content ) ) {
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
                        'post_id'    => $post_id,
                        'post_title' => $post_title,
                        'post_type'  => $post_type,
                        'edit_link'  => get_edit_post_link( $post_id ),
                        'view_link'  => get_permalink( $post_id ),
                    );
                }
            }
            wp_reset_postdata();
        }

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
                    <p><?php _e( 'Gravity Forms is not active. Please install and activate Gravity Forms to use this plugin.', 'gravity-page-link-view' ); ?></p>
                </div>
            </div>
            <?php
            return;
        }

        $forms = $this->get_all_forms();
        $form_usage_data = array();

        // Get usage data for all forms
        foreach ( $forms as $form ) {
            $form_id = $form['id'];
            $form_usage_data[ $form_id ] = $this->find_form_usage( $form_id );
        }

        ?>
        <div class="wrap gplv-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p class="description">
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
        </div>
        <?php
    }
}

/**
 * Initialize the plugin
 */
function gplv_init() {
    return Gravity_Page_Link_View::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'gplv_init' );
