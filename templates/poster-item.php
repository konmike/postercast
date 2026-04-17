<?php
/**
 * Single poster item template.
 *
 * @var WP_Post $poster       The poster post object.
 * @var string  $poster_size  Image size slug.
 * @var string  $orientation  "portrait" or "landscape".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$thumbnail_id = get_post_thumbnail_id( $poster->ID );
$image_meta   = wp_get_attachment_metadata( $thumbnail_id );
$img_width    = ! empty( $image_meta['width'] ) ? $image_meta['width'] : '';
$img_height   = ! empty( $image_meta['height'] ) ? $image_meta['height'] : '';
?>
<?php $extra_style = isset( $extra_style ) ? $extra_style : ''; ?>
<div class="pcast-poster pcast-poster--<?php echo esc_attr( $orientation ); ?>" data-poster-id="<?php echo esc_attr( $poster->ID ); ?>"<?php echo $extra_style ? ' style="' . esc_attr( $extra_style ) . '"' : ''; ?>>
    <button type="button"
            class="pcast-poster__trigger"
            aria-label="<?php
            /* translators: %s: poster title */
            echo esc_attr( sprintf( __( 'Open %s in lightbox', 'postercast' ), get_the_title( $poster ) ) );
            ?>">
        <?php
        echo wp_get_attachment_image(
            $thumbnail_id,
            $poster_size,
            false,
            [
                'class'   => 'pcast-poster__image',
                'width'   => $img_width,
                'height'  => $img_height,
                'loading' => 'lazy',
            ]
        );
        ?>
    </button>
</div>
