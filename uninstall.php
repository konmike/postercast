<?php
/**
 * PosterCast — Uninstall.
 *
 * Removes all plugin data when the plugin is deleted through the WordPress admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Delete all poster posts and their meta (both old and new post type names).
$post_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('pcast_poster', 'poster')" // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
);

if ( ! empty( $post_ids ) ) {
    $ids_placeholder = implode( ',', array_map( 'intval', $post_ids ) );

    // Delete post meta.
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$ids_placeholder})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

    // Delete term relationships.
    $wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ({$ids_placeholder})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

    // Delete posts.
    $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ('pcast_poster', 'poster')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
}

// 2. Delete taxonomy terms (both old and new taxonomy names).
foreach ( [ 'pcast_gallery', 'poster_gallery' ] as $taxonomy ) {
    $terms = get_terms( [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ] );

    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }
    }

    // Clean up taxonomy from the database.
    $wpdb->delete( $wpdb->term_taxonomy, [ 'taxonomy' => $taxonomy ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
}

// 3. Delete options.
delete_option( 'pcast_version' );
delete_option( 'pg_version' );

// 4. Flush rewrite rules.
flush_rewrite_rules();
