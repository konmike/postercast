<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Taxonomy_Gallery {

    public const TAXONOMY = 'pcast_gallery';

    public static function register(): void {
        register_taxonomy( self::TAXONOMY, Post_Type_Poster::POST_TYPE, [
            'labels'            => self::get_labels(),
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'rest_base'         => 'pcast_gallery',
            'show_admin_column' => true,
            'rewrite'           => false,
        ] );
    }

    private static function get_labels(): array {
        return [
            'name'              => __( 'Galleries', 'postercast' ),
            'singular_name'     => __( 'Gallery', 'postercast' ),
            'menu_name'         => __( 'Galleries', 'postercast' ),
            'all_items'         => __( 'All Galleries', 'postercast' ),
            'edit_item'         => __( 'Edit Gallery', 'postercast' ),
            'view_item'         => __( 'View Gallery', 'postercast' ),
            'update_item'       => __( 'Update Gallery', 'postercast' ),
            'add_new_item'      => __( 'Add New Gallery', 'postercast' ),
            'new_item_name'     => __( 'New Gallery Name', 'postercast' ),
            'search_items'      => __( 'Search Galleries', 'postercast' ),
            'not_found'         => __( 'No galleries found', 'postercast' ),
        ];
    }
}
