<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Image_Orientation {

    /**
     * Detect orientation of an attachment image.
     *
     * @param int $attachment_id Attachment post ID.
     * @return string 'landscape' if width > height, otherwise 'portrait'.
     */
    public static function detect( int $attachment_id ): string {
        $metadata = wp_get_attachment_metadata( $attachment_id );

        if ( ! is_array( $metadata ) || empty( $metadata['width'] ) || empty( $metadata['height'] ) ) {
            return 'portrait';
        }

        return ( (int) $metadata['width'] > (int) $metadata['height'] ) ? 'landscape' : 'portrait';
    }

    /**
     * Initialize hooks for auto-detection on thumbnail change.
     */
    public static function init(): void {
        add_action( 'updated_post_meta', [ self::class, 'on_thumbnail_change' ], 10, 4 );
        add_action( 'added_post_meta', [ self::class, 'on_thumbnail_change' ], 10, 4 );
    }

    /**
     * When _thumbnail_id is set on a poster and orientation mode is 'auto',
     * auto-update the _pcast_orientation meta field.
     *
     * @param int    $meta_id    Meta ID.
     * @param int    $object_id  Post ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     */
    public static function on_thumbnail_change( int $meta_id, int $object_id, string $meta_key, mixed $meta_value ): void {
        if ( '_thumbnail_id' !== $meta_key ) {
            return;
        }

        if ( get_post_type( $object_id ) !== Post_Type_Poster::POST_TYPE ) {
            return;
        }

        $orientation_mode = get_post_meta( $object_id, '_pcast_orientation_mode', true );

        if ( 'auto' !== $orientation_mode && '' !== $orientation_mode ) {
            return;
        }

        $orientation = self::detect( (int) $meta_value );
        update_post_meta( $object_id, '_pcast_orientation', $orientation );
    }
}
