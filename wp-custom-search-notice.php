<?php
/**
 * Plugin Name:       WordPress Custom Search Notice
 * Plugin URI:        https://example.com
 * Description:       Adds a rich text custom notice box above the WordPress search results page (theme-proof) and via shortcode.
 * Version:           1.2.0
 * Author:            Your Name
 * License:           GPLv2 or later
 * Text Domain:       wp-custom-search-notice
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * =========================================================================
 * 1. FRONTEND: Inject the Rich Text Custom Notice Box via Output Buffering
 * =========================================================================
 */

// Core rendering engine for the notice layout box HTML
function wpcsn_get_search_box_html() {
    $custom_text = get_option( 'wpcsn_search_notice_text', '' );
    
    if ( ! empty( $custom_text ) ) {
        return '<div class="wpcsn-custom-search-box" style="
            background-color: #f0f7ff;
            border-left: 4px solid #0073aa;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
            color: #333;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            clear: both;
            width: 100%;
            box-sizing: border-box;
        ">' . wp_kses_post( $custom_text ) . '</div>';
    }
    return '';
}

/**
 * Intercepts the search template rendering, buffers the HTML, 
 * and injects our notice exactly at the start of the primary content grid.
 */
function wpcsn_buffer_search_page() {
    // Only execute on the actual frontend search results page
    if ( is_search() && is_main_query() ) {
        ob_start( 'wpcsn_inject_into_buffer' );
    }
}
add_action( 'template_redirect', 'wpcsn_buffer_search_page' );

/**
 * Buffer parsing callback
 */
function wpcsn_inject_into_buffer( $html ) {
    $notice_html = wpcsn_get_search_box_html();
    
    if ( empty( $notice_html ) ) {
        return $html;
    }

    // Target the main container opening structure common to modern themes like Elegant Recipe
    $targets = array(
        '<div id="content" class="site-content">',
        '<main id="primary" class="site-main">',
        '<main id="main" class="site-main">',
        '<div class="site-content">'
    );

    foreach ( $targets as $target ) {
        if ( strpos( $html, $target ) !== false ) {
            return str_replace( $target, $target . $notice_html, $html );
        }
    }

    // Fallback: If no wrappers match, inject right after the closing </header> block
    return str_replace( '</header>', '</header>' . $notice_html, $html );
}

/**
 * =========================================================================
 * 2. SHORTCODE: Allow [search_notice] to be used anywhere on the site
 * =========================================================================
 */
function wpcsn_register_search_notice_shortcode() {
    return wpcsn_get_search_box_html();
}
add_shortcode( 'search_notice', 'wpcsn_register_search_notice_shortcode' );

/**
 * =========================================================================
 * 3. BACKEND: Create the Admin Settings Page
 * =========================================================================
 */
function wpcsn_add_admin_menu() {
    add_options_page(
        __( 'Search Notice Settings', 'wp-custom-search-notice' ), 
        __( 'Search Notice', 'wp-custom-search-notice' ),          
        'manage_options',                                         
        'wp-custom-search-notice',                                
        'wpcsn_render_settings_page'                              
    );
}
add_action( 'admin_menu', 'wpcsn_add_admin_menu' );

/**
 * Register and Sanitize the Option
 */
function wpcsn_register_settings() {
    register_setting(
        'wpcsn_settings_group',         
        'wpcsn_search_notice_text',     
        array(
            'type'              => 'string',
            'sanitize_callback' => 'wp_kses_post', 
            'default'           => '',
        )
    );
}
add_action( 'admin_init', 'wpcsn_register_settings' );

/**
 * Render the Settings Page UI with Documentation Tabs
 */
function wpcsn_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Determine current active tab
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general_settings';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Search Notice Configuration', 'wp-custom-search-notice' ); ?></h1>
        
        <!-- Tab Navigation Bars -->
        <nav class="nav-tab-wrapper">
            <a href="?page=wp-custom-search-notice&tab=general_settings" class="nav-tab <?php echo $active_tab === 'general_settings' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'General Settings', 'wp-custom-search-notice' ); ?>
            </a >
            <a href="?page=wp-custom-search-notice&tab=documentation" class="nav-tab <?php echo $active_tab === 'documentation' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Documentation & Help', 'wp-custom-search-notice' ); ?>
            </a >
        </nav>

        <div class="tab-content" style="padding-top: 20px;">
            <?php if ( $active_tab === 'general_settings' ) : ?>
                <!-- Tab 1: Configuration Form -->
                <p><?php esc_html_e( 'Enter the rich text message you want displayed at the top of the search results screen.', 'wp-custom-search-notice' ); ?></p>
                
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'wpcsn_settings_group' );
                    $saved_text = get_option( 'wpcsn_search_notice_text', '' );
                    
                    $editor_settings = array(
                        'textarea_name' => 'wpcsn_search_notice_text',
                        'media_buttons' => true,                       
                        'textarea_rows' => 12,
                        'teeny'         => false,                      
                        'quicktags'     => true,                       
                    );
                    
                    wp_editor( $saved_text, 'wpcsn_search_notice_editor', $editor_settings );
                    submit_button();
                    ?>
                </form>

            <?php else : ?>
                <!-- Tab 2: Native Documentation Screen -->
                <div class="card" style="max-width: 800px; padding: 20px; margin-top: 0;">
                    <h2>📖 Plugin Manual & Usage Instructions</h2>
                    <p>Welcome to your custom Search Notice system. This utility seamlessly injects beautifully styled global alerts across your target layouts.</p>
                    
                    <hr />

                    <h3>🔍 Automated Search Results Placement</h3>
                    <p>The plugin is hardwired with an advanced <strong>output buffering mechanism</strong>. It automatically intercepts and scans the template files of your recipe and article search directories, appending your saved alerts right below your identity blocks and above your recipe entries. No manual code insertion is required.</p>

                    <h3>🧱 Using the Global Shortcode</h3>
                    <p>Want to repurpose this identical layout or text inside individual posts, footer widgets, recipe directories, or separate static landing pages?</p>
                    <p>Insert the following shortcode string directly inside any WordPress block text field:</p>
                    <p><code>[search_notice]</code></p>

                    <h3>🎨 Custom Styling Notice</h3>
                    <p>The display box ships with default inline padding, structural shadows, and a clean alert border configuration. To apply site-wide style adjustments, you can override its native container classes using the following layout hook inside your theme's <em>Additional CSS</em> panel:</p>
                    <pre style="background: #f4f4f4; padding: 10px; border-left: 3px solid #ccc; font-family: monospace;">
.wpcsn-custom-search-box {
    /* Insert custom colors, fonts, or margins here */
}</pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * =========================================================================
 * 4. DASHBOARD INTEGRATION: Inject Action Links onto Plugins Overview Screen
 * =========================================================================
 */

/**
 * Adds an immediate "Settings" actionable hyper-link directly alongside "Deactivate"
 */
function wpcsn_add_plugin_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=wp-custom-search-notice' ) . '">' . __( 'Settings', 'wp-custom-search-notice' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpcsn_add_plugin_action_links' );

/**
 * Adds a secondary "Documentation" lookup link across the metadata details grid row
 */
function wpcsn_add_plugin_row_meta( $links, $file ) {
    if ( $file === plugin_basename( __FILE__ ) ) {
        $doc_link = '<a href="' . admin_url( 'options-general.php?page=wp-custom-search-notice&tab=documentation' ) . '" style="font-weight: bold; color: #135e96;">' . __( 'Documentation', 'wp-custom-search-notice' ) . '</a>';
        $links[] = $doc_link;
    }
    return $links;
}
add_filter( 'plugin_row_meta', 'wpcsn_add_plugin_row_meta', 10, 2 );
