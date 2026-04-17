<?php
/**
 * Plugin Name: PosterCast
 * Plugin URI:  https://poster-gallery.konecnymichal.cz
 * Description: Beautiful poster and flyer galleries with responsive grid, full-screen lightbox, and Gutenberg block.
 * Version:     1.0.0
 * Author:      Michal Konecny
 * Author URI:  https://konecnymichal.cz
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: postercast
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PCAST_VERSION', '1.0.0' );
define( 'PCAST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCAST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PCAST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 */
spl_autoload_register( function ( string $class ) {
    $prefix    = 'PosterCast\\';
    $base_dir  = PCAST_PLUGIN_DIR . 'includes/';

    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, strlen( $prefix ) );
    $file = $base_dir . 'class-' . strtolower( str_replace( [ '\\', '_' ], [ '/', '-' ], $relative_class ) ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Plugin activation.
 */
function pcast_activate(): void {
    \PosterCast\Post_Type_Poster::register();
    \PosterCast\Taxonomy_Gallery::register();
    flush_rewrite_rules();
    pcast_migrate_data();
    update_option( 'pcast_version', PCAST_VERSION );
}
register_activation_hook( __FILE__, 'pcast_activate' );

/**
 * Plugin deactivation.
 */
function pcast_deactivate(): void {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pcast_deactivate' );

/**
 * Migrate data from older plugin versions (wpcg → pg → pcast).
 */
function pcast_migrate_data(): void {
    global $wpdb;

    // 1. Migrate post type: poster → pcast_poster.
    $wpdb->update( $wpdb->posts, [ 'post_type' => 'pcast_poster' ], [ 'post_type' => 'poster' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

    // 2. Migrate taxonomy: poster_gallery → pcast_gallery.
    $wpdb->update( $wpdb->term_taxonomy, [ 'taxonomy' => 'pcast_gallery' ], [ 'taxonomy' => 'poster_gallery' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

    // 3. Migrate meta keys from all previous prefixes.
    $meta_keys = [ 'description', 'url', 'orientation_mode', 'orientation', 'align', 'valign', 'show_from', 'show_until' ];

    foreach ( $meta_keys as $key ) {
        // wpcg → pcast (original old format).
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s", "_pcast_{$key}", "_wpcg_{$key}" ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        // pg → pcast (v1 format).
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s", "_pcast_{$key}", "_pg_{$key}" ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    // 4. Migrate block names in post_content.
    $wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'wp:wpcg/gallery', 'wp:pcast/gallery') WHERE post_content LIKE '%wp:wpcg/gallery%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'wp:pg/gallery', 'wp:pcast/gallery') WHERE post_content LIKE '%wp:pg/gallery%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

    // 5. Migrate option.
    $old_version = get_option( 'pg_version' );
    if ( false !== $old_version ) {
        delete_option( 'pg_version' );
    }
}

/**
 * Run upgrade routines when plugin version changes.
 */
function pcast_maybe_upgrade(): void {
    $stored_version = get_option( 'pcast_version', '0' );
    if ( version_compare( $stored_version, PCAST_VERSION, '<' ) ) {
        pcast_migrate_data();
        update_option( 'pcast_version', PCAST_VERSION );
    }
}

/**
 * Add "Go PRO" link to plugin action links.
 */
function pcast_plugin_action_links( array $links ): array {
    if ( ! defined( 'PCAST_PRO' ) ) {
        $links['go_pro'] = sprintf(
            '<a href="%s" target="_blank" rel="noopener" style="color:#d63638;font-weight:600;">%s</a>',
            'https://michalkoneczny.gumroad.com/l/postercast',
            __( 'Go PRO', 'postercast' )
        );
    }
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pcast_plugin_action_links' );

/**
 * Initialize the plugin.
 */
function pcast_init(): void {
    pcast_maybe_upgrade();
    \PosterCast\Plugin::get_instance();

    /**
     * Fires after PosterCast is fully loaded.
     * Used by PRO add-on to hook into the free plugin.
     */
    do_action( 'pcast_loaded' );
}
add_action( 'plugins_loaded', 'pcast_init' );
