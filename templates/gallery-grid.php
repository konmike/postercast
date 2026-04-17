<?php
/**
 * Gallery grid template.
 *
 * @var int       $gallery_id    Term ID of the poster_gallery taxonomy.
 * @var WP_Post[] $posters       Array of poster posts (limited).
 * @var int       $total_count   Total number of matching posters.
 * @var bool      $has_more      Whether there are more posters beyond the limit.
 * @var bool      $show_all_link Whether to show the "Show all" link.
 * @var string    $poster_size   Image size slug for grid thumbnails.
 * @var array     $attributes    Block/shortcode attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<script>
window.pcastGalleryData = window.pcastGalleryData || {};
window.pcastGalleryData[<?php echo (int) $gallery_id; ?>] = <?php echo wp_json_encode( $lightbox_items ); ?>;
window.pcastGalleryConfig = window.pcastGalleryConfig || {};
window.pcastGalleryConfig[<?php echo (int) $gallery_id; ?>] = <?php echo wp_json_encode([ 'linkText' => $lightbox_link_text ?? '' ]); ?>;
</script>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="pcast-gallery__grid">
        <?php
        // Pre-calculate orientations and spans to figure out last-row centering.
        $poster_data = [];
        foreach ( $posters as $poster ) {
            $thumbnail_id = get_post_thumbnail_id( $poster->ID );
            if ( ! $thumbnail_id ) {
                continue;
            }
            $orientation = \PosterCast\Frontend_Renderer::get_poster_orientation( $poster->ID, $thumbnail_id );
            $span        = ( 'landscape' === $orientation ) ? $landscape_span : $portrait_span;
            $align       = get_post_meta( $poster->ID, '_pcast_align', true ) ?: 'center';
            $valign      = get_post_meta( $poster->ID, '_pcast_valign', true ) ?: 'center';
            $poster_data[] = [
                'poster'       => $poster,
                'thumbnail_id' => $thumbnail_id,
                'orientation'  => $orientation,
                'span'         => $span,
                'align'        => $align,
                'valign'       => $valign,
            ];
        }

        // Group items into rows and calculate explicit grid positions.
        $rows            = [];
        $current_row     = [];
        $current_row_cols = 0;

        foreach ( $poster_data as $item ) {
            if ( $current_row_cols + $item['span'] > $columns ) {
                $rows[]           = $current_row;
                $current_row      = [];
                $current_row_cols = 0;
            }
            $current_row[]     = $item;
            $current_row_cols += $item['span'];
        }
        if ( ! empty( $current_row ) ) {
            $rows[] = $current_row;
        }

        // Render rows with explicit grid positions.
        $grid_row = 1;
        foreach ( $rows as $row ) {
            $row_cols  = array_sum( array_column( $row, 'span' ) );
            $col_start = ( $row_cols < $columns )
                ? (int) floor( ( $columns - $row_cols ) / 2 ) + 1
                : 1;

            foreach ( $row as $item ) {
                $poster           = $item['poster'];
                $thumbnail_id     = $item['thumbnail_id'];
                $orientation      = $item['orientation'];
                $poster_size_used = $poster_size;
                $align            = $item['align'];
                $valign           = $item['valign'];

                $extra_style = sprintf(
                    'grid-column: %d / span %d; grid-row: %d; justify-self: %s; align-self: %s;',
                    $col_start,
                    $item['span'],
                    $grid_row,
                    esc_attr( $align ),
                    esc_attr( $valign )
                );
                $col_start += $item['span'];

                include PCAST_PLUGIN_DIR . 'templates/poster-item.php';
            }
            $grid_row++;
        }
        ?>
    </div>

    <?php if ( $has_more && $show_all_link ) : ?>
        <div class="pcast-gallery__footer">
            <button type="button" class="pcast-gallery__show-all">
                <?php
                $btn_label = ! empty( $show_all_text ) ? $show_all_text : __( 'Show all', 'postercast' );
                if ( $show_all_show_count ) {
                    echo esc_html( $btn_label ) . ' (' . (int) $total_count . ')';
                } else {
                    echo esc_html( $btn_label );
                }
                ?>
            </button>
        </div>
    <?php endif; ?>
</div>
