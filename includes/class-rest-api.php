<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rest_Api {

    private const NAMESPACE = 'pcast/v1';

    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/posters', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_posters' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'gallery_id'      => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ( $value ) {
                        return is_numeric( $value ) && (int) $value > 0;
                    },
                ],
                'include_expired' => [
                    'required'          => false,
                    'type'              => 'boolean',
                    'default'           => false,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/order', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'update_order' ],
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
            'args'                => [
                'gallery_id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'order'      => [
                    'required'          => true,
                    'type'              => 'array',
                    'items'             => [
                        'type' => 'integer',
                    ],
                    'sanitize_callback' => function ( $value ) {
                        return array_map( 'absint', (array) $value );
                    },
                ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/galleries', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_galleries' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function get_posters( \WP_REST_Request $request ): \WP_REST_Response {
        $gallery_id      = $request->get_param( 'gallery_id' );
        $include_expired = $request->get_param( 'include_expired' );

        $query_args = [
            'post_type'      => Post_Type_Poster::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                [
                    'taxonomy' => Taxonomy_Gallery::TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => $gallery_id,
                ],
            ],
        ];

        if ( ! $include_expired ) {
            $meta_query = apply_filters( 'pcast_poster_meta_query', [] );
            if ( ! empty( $meta_query ) ) {
                $query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            }
        }

        $query   = new \WP_Query( $query_args );
        $posters = [];

        foreach ( $query->posts as $post ) {
            $thumbnail_id = get_post_thumbnail_id( $post->ID );
            $image_full   = '';
            $image_thumb  = '';

            if ( $thumbnail_id ) {
                $full  = wp_get_attachment_image_src( $thumbnail_id, 'full' );
                $thumb = wp_get_attachment_image_src( $thumbnail_id, 'medium_large' );

                $image_full  = $full ? $full[0] : '';
                $image_thumb = $thumb ? $thumb[0] : '';
            }

            $orientation_mode = get_post_meta( $post->ID, '_pcast_orientation_mode', true );
            $orientation      = get_post_meta( $post->ID, '_pcast_orientation', true );

            if ( 'auto' === $orientation_mode && $thumbnail_id ) {
                $metadata = wp_get_attachment_metadata( $thumbnail_id );
                if ( $metadata && isset( $metadata['width'], $metadata['height'] ) ) {
                    $orientation = $metadata['width'] >= $metadata['height'] ? 'landscape' : 'portrait';
                }
            } elseif ( 'auto' !== $orientation_mode && $orientation_mode ) {
                $orientation = $orientation_mode;
            }

            $posters[] = [
                'id'          => $post->ID,
                'title'       => get_the_title( $post->ID ),
                'image_full'  => $image_full,
                'image_thumb' => $image_thumb,
                'orientation' => $orientation ?: 'portrait',
                'url'         => get_post_meta( $post->ID, '_pcast_url', true ),
                'menu_order'  => $post->menu_order,
            ];
        }

        return new \WP_REST_Response( $posters, 200 );
    }

    public function update_order( \WP_REST_Request $request ): \WP_REST_Response {
        $gallery_id = $request->get_param( 'gallery_id' );
        $order      = $request->get_param( 'order' );

        // Verify the term exists.
        $term = get_term( $gallery_id, Taxonomy_Gallery::TAXONOMY );

        if ( is_wp_error( $term ) || ! $term ) {
            return new \WP_REST_Response(
                [ 'message' => __( 'Gallery not found.', 'postercast' ) ],
                404
            );
        }

        foreach ( $order as $index => $post_id ) {
            wp_update_post( [
                'ID'         => $post_id,
                'menu_order' => $index,
            ] );
        }

        return new \WP_REST_Response(
            [ 'message' => __( 'Order updated successfully.', 'postercast' ) ],
            200
        );
    }

    public function get_galleries( \WP_REST_Request $request ): \WP_REST_Response {
        $terms = get_terms( [
            'taxonomy'   => Taxonomy_Gallery::TAXONOMY,
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) ) {
            return new \WP_REST_Response( [], 200 );
        }

        $galleries = [];

        foreach ( $terms as $term ) {
            $galleries[] = [
                'id'    => $term->term_id,
                'name'  => $term->name,
                'slug'  => $term->slug,
                'count' => $term->count,
            ];
        }

        return new \WP_REST_Response( $galleries, 200 );
    }
}
