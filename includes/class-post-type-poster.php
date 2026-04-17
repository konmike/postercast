<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Post_Type_Poster {

    public const POST_TYPE = 'pcast_poster';

    public static function register(): void {
        register_post_type( self::POST_TYPE, [
            'labels'              => self::get_labels(),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'rest_base'           => 'posters',
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-format-gallery',
            'supports'            => [ 'title', 'thumbnail', 'page-attributes', 'custom-fields' ],
            'has_archive'         => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
        ] );

        self::register_meta();
    }

    private static function get_labels(): array {
        return [
            'name'                  => __( 'Posters', 'postercast' ),
            'singular_name'         => __( 'Poster', 'postercast' ),
            'menu_name'             => __( 'PosterCast', 'postercast' ),
            'add_new'               => __( 'Add Poster', 'postercast' ),
            'add_new_item'          => __( 'Add New Poster', 'postercast' ),
            'edit_item'             => __( 'Edit Poster', 'postercast' ),
            'new_item'              => __( 'New Poster', 'postercast' ),
            'view_item'             => __( 'View Poster', 'postercast' ),
            'search_items'          => __( 'Search Posters', 'postercast' ),
            'not_found'             => __( 'No posters found', 'postercast' ),
            'not_found_in_trash'    => __( 'No posters found in Trash', 'postercast' ),
            'all_items'             => __( 'All Posters', 'postercast' ),
            'featured_image'        => __( 'Poster Image', 'postercast' ),
            'set_featured_image'    => __( 'Set poster image', 'postercast' ),
            'remove_featured_image' => __( 'Remove poster image', 'postercast' ),
            'use_featured_image'    => __( 'Use as poster image', 'postercast' ),
        ];
    }

    private static function register_meta(): void {
        $meta_fields = [
            '_pcast_description' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default'           => '',
            ],
            '_pcast_url' => [
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default'           => '',
            ],
            '_pcast_orientation_mode' => [
                'type'              => 'string',
                'sanitize_callback' => [ self::class, 'sanitize_orientation_mode' ],
                'default'           => 'auto',
            ],
            '_pcast_orientation' => [
                'type'              => 'string',
                'sanitize_callback' => [ self::class, 'sanitize_orientation' ],
                'default'           => 'portrait',
            ],
            '_pcast_align' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'stretch',
            ],
            '_pcast_valign' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'stretch',
            ],
            '_pcast_show_from' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
            '_pcast_show_until' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
        ];

        foreach ( $meta_fields as $key => $args ) {
            register_post_meta( self::POST_TYPE, $key, [
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => $args['type'],
                'sanitize_callback' => $args['sanitize_callback'],
                'default'           => $args['default'],
                'auth_callback'     => function () {
                    return current_user_can( 'edit_posts' );
                },
            ] );
        }
    }

    public static function sanitize_orientation_mode( string $value ): string {
        return in_array( $value, [ 'auto', 'portrait', 'landscape' ], true ) ? $value : 'auto';
    }

    public static function sanitize_orientation( string $value ): string {
        return in_array( $value, [ 'portrait', 'landscape' ], true ) ? $value : 'portrait';
    }
}
