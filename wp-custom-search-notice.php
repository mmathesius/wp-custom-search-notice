<?php
/**
 * Plugin Name:       WordPress Custom Search Notice
 * Plugin URI:        https://example.com
 * Description:       Adds a highly customizable text box above the WordPress search results page.
 * Version:           1.0.0
 * Author:            Your Name
 * License:           GPLv2 or later
 * Text Domain:       wp-custom-search-notice
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Injects a custom styled text box immediately before the search results loop starts.
 *
 * @param WP_Query $query The current WP_Query instance.
 */
function wpcsn_inject_search_textbox( $query ) {
    // Ensure this runs only on the main frontend search query
    if ( is_search() && is_main_query() && did_action( 'loop_start' ) === 1 ) {
        
        // Define your custom notice text here
        $custom_text = __( '<strong>Notice:</strong> Looking for something specific? If you can\'t find what you need, feel free to contact our support team!', 'wp-custom-search-notice' );
        
        // Output the HTML text box with inline styles
        echo '<div class="wpcsn-custom-search-box" style="
            background-color: #f0f7ff;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            color: #333;
            font-size: 16px;
            line-height: 1.5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        ">';
        echo wp_kses_post( $custom_text );
        echo '</div>';
    }
}
add_action( 'loop_start', 'wpcsn_inject_search_textbox' );
