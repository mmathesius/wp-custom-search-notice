<?php
/**
 * Plugin Name:       WordPress Custom Search Notice
 * Plugin URI:        https://example.com
 * Description:       Adds a rich text custom notice box above the WordPress search results page.
 * Version:           1.1.3
 * Author:            Your Name
 * License:           GPLv2 or later
 * Text Domain:       wp-custom-search-notice
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. FRONTEND: Inject the Rich Text Custom Notice Box
 */

// Core rendering engine for the notice layout box
function wpcsn_render_search_box_html() {
    static $has_rendered = false;
    if ( $has_rendered ) {
        return '';
    }

    $custom_text = get_option( 'wpcsn_search_notice_text', '' );
    
    if ( ! empty( $custom_text ) ) {
        $has_rendered = true;
        
        return '<div class="wpcsn-custom-search-box" style="
            background-color: #f0f7ff;
            border-left: 4px solid #0073aa;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
            color: #333;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            clear: both;
        ">' . wp_kses_post( $custom_text ) . '</div>';
    }
    return '';
}

/**
 * Fix for SUCCESSFUL searches: Prepend to the very first search result item's content.
 * This guarantees it prints inside the main content column, right above the first result.
 */
function wpcsn_prepend_to_first_result( $content ) {
    if ( is_search() && is_main_query() && in_the_loop() ) {
        $notice = wpcsn_render_search_box_html();
        if ( ! empty( $notice ) ) {
            return $notice . $content;
        }
    }
    return $content;
}
add_filter( 'the_content', 'wpcsn_prepend_to_first_result', 1 );
add_filter( 'the_excerpt', 'wpcsn_prepend_to_first_result', 1 ); // Themes using snippets instead of full text

/**
 * Fix for ZERO MATCH searches: Fallback to the standard no-results hook wrapper.
 */
function wpcsn_handle_no_results() {
    if ( is_search() && is_main_query() ) {
        echo wpcsn_render_search_box_html();
    }
}
add_action( 'loop_no_results', 'wpcsn_handle_no_results' );


/**
 * 2. BACKEND: Create the Admin Settings Page
 */
function wpcsn_add_admin_menu() {
    add_options_page(
        __( 'Search Notice Settings', 'wp-custom-search-notice' ), // Page Title
        __( 'Search Notice', 'wp-custom-search-notice' ),          // Menu Title
        'manage_options',                                         // Capability Required
        'wp-custom-search-notice',                                // Menu Slug
        'wpcsn_render_settings_page'                              // Callback Function
    );
}
add_action( 'admin_menu', 'wpcsn_add_admin_menu' );


/**
 * 3. BACKEND: Register and Sanitize the Option
 */
function wpcsn_register_settings() {
    register_setting(
        'wpcsn_settings_group',         // Option Group Name
        'wpcsn_search_notice_text',     // Option Name in Database
        array(
            'type'              => 'string',
            'sanitize_callback' => 'wp_kses_post', // Securely cleans rich text before database save
            'default'           => '',
        )
    );
}
add_action( 'admin_init', 'wpcsn_register_settings' );


/**
 * 4. BACKEND: Render the Settings Page UI with wp_editor
 */
function wpcsn_render_settings_page() {
    // Check user permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Search Notice Configuration', 'wp-custom-search-notice' ); ?></h1>
        <p><?php esc_html_e( 'Enter the rich text message you want displayed at the top of the search results screen.', 'wp-custom-search-notice' ); ?></p>
        
        <form action="options.php" method="post">
            <?php
            // Output security fields and settings blocks
            settings_fields( 'wpcsn_settings_group' );
            
            // Retrieve current saved value
            $saved_text = get_option( 'wpcsn_search_notice_text', '' );
            
            // Editor configuration arguments
            $editor_settings = array(
                'textarea_name' => 'wpcsn_search_notice_text', // Corresponds to option name
                'media_buttons' => true,                       // Allows adding images
                'textarea_rows' => 10,
                'teeny'         => false,                      // Show full toolbars
                'quicktags'     => true,                       // Show the HTML/Text tab
            );
            
            // Render the Native WordPress Visual/Rich Text Editor
            wp_editor( $saved_text, 'wpcsn_search_notice_editor', $editor_settings );
            
            // Output standard save changes button
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
