<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles front-end rendering of the poster gallery — both as a Gutenberg
 * block render_callback and as a [pcast_gallery] shortcode.
 */
class Frontend_Renderer {

    /**
     * Whether the lightbox shell has already been output on this page load.
     *
     * @var bool
     */
    private static bool $lightbox_rendered = false;

    /**
     * Boot shortcode registration. Called once from Plugin.
     */
    public static function init(): void {
        add_shortcode( 'pcast_gallery', [ self::class, 'render_shortcode' ] );
        // Backward compatibility: keep old shortcode working for existing content.
        add_shortcode( 'pg_gallery', [ self::class, 'render_shortcode' ] );
    }

    /**
     * Shortcode handler — normalises attributes and delegates to render().
     *
     * @param array|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render_shortcode( $atts ): string {
        $atts = shortcode_atts(
            [
                'gallery_id'    => 0,
                'limit'         => 6,
                'show_all_link' => 'true',
                'poster_size'   => 'medium_large',
                'columns'           => 2,
                'landscape_span'    => 2,
                'portrait_span'     => 1,
                'poster_align'      => 'center',
                'max_height'        => 400,
                'max_width'         => 0,
                'gallery_align'     => 'center',
                'gap'               => 16,
                'poster_background' => '#ffffff',
                'show_shadow'       => 'true',
                'lightbox_link_text' => '',
            ],
            $atts,
            'pcast_gallery'
        );

        $attributes = [
            'galleryId'        => absint( $atts['gallery_id'] ),
            'limit'            => absint( $atts['limit'] ),
            'showAllLink'      => filter_var( $atts['show_all_link'], FILTER_VALIDATE_BOOLEAN ),
            'posterSize'       => sanitize_text_field( $atts['poster_size'] ),
            'columns'          => max( 1, absint( $atts['columns'] ) ),
            'landscapeSpan'    => max( 1, absint( $atts['landscape_span'] ) ),
            'portraitSpan'     => max( 1, absint( $atts['portrait_span'] ) ),
            'posterAlign'      => sanitize_text_field( $atts['poster_align'] ),
            'maxHeight'        => absint( $atts['max_height'] ),
            'maxWidth'         => absint( $atts['max_width'] ),
            'galleryAlign'     => sanitize_text_field( $atts['gallery_align'] ),
            'gap'              => absint( $atts['gap'] ),
            'posterBackground' => sanitize_hex_color( $atts['poster_background'] ) ?: '#ffffff',
            'showShadow'       => filter_var( $atts['show_shadow'], FILTER_VALIDATE_BOOLEAN ),
            'lightboxLinkText' => sanitize_text_field( $atts['lightbox_link_text'] ),
        ];

        return self::render( $attributes );
    }

    /**
     * Gutenberg block render_callback.
     *
     * @param array $attributes Block attributes.
     * @return string HTML output.
     */
    public static function render_block( array $attributes, string $content = '', $block = null ): string {
        $attributes = wp_parse_args( $attributes, [
            'galleryId'        => 0,
            'limit'            => 6,
            'showAllLink'      => true,
            'posterSize'       => 'medium_large',
            'columns'          => 2,
            'landscapeSpan'    => 2,
            'portraitSpan'     => 1,
            'posterAlign'      => 'center',
            'maxHeight'        => 400,
            'maxWidth'         => 0,
            'galleryAlign'     => 'center',
            'gap'              => 16,
            'posterBackground' => '#ffffff',
            'showShadow'       => true,
            'lightboxLinkText' => '',
        ] );

        return self::render( $attributes, true );
    }

    /**
     * Core render logic shared by both block and shortcode.
     *
     * @param array $attributes Normalised attributes.
     * @param bool $is_block Whether rendering as a Gutenberg block (enables spacing supports).
     * @return string HTML output.
     */
    private static function render( array $attributes, bool $is_block = false ): string {
        $gallery_id = absint( $attributes['galleryId'] );

        if ( ! $gallery_id ) {
            return '';
        }

        // Query ALL matching posters (needed for lightbox data).
        $query_args = [
            'post_type'      => Post_Type_Poster::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_query'     => apply_filters( 'pcast_poster_meta_query', [] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'tax_query'      => [                               // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                [
                    'taxonomy' => Taxonomy_Gallery::TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => $gallery_id,
                ],
            ],
        ];

        $all_posters = get_posts( $query_args );

        if ( empty( $all_posters ) ) {
            return '';
        }

        // Enqueue assets (registered earlier in Plugin::register_frontend_assets).
        wp_enqueue_style( 'pcast-gallery' );
        wp_enqueue_script( 'pcast-lightbox' );
        wp_enqueue_script( 'pcast-gallery' );

        // Build lightbox JSON data from ALL posters.
        $lightbox_items = self::build_lightbox_data( $all_posters );

        // Data is output directly as a script tag in the HTML below,
        // because wp_add_inline_script can be unreliable with dynamic blocks.

        // Only display up to the limit in the grid.
        $limit         = max( 1, absint( $attributes['limit'] ) );
        $total_count   = count( $all_posters );
        $posters       = array_slice( $all_posters, 0, $limit );
        $has_more      = $total_count > $limit;
        $show_all_link = (bool) $attributes['showAllLink'];
        $poster_size   = $attributes['posterSize'];
        $max_height        = absint( $attributes['maxHeight'] );
        $max_width         = $attributes['maxWidth'] ?? 0;
        $gallery_align     = sanitize_text_field( $attributes['galleryAlign'] ?? 'center' );
        $gap               = absint( $attributes['gap'] );
        $columns           = max( 1, absint( $attributes['columns'] ?? 2 ) );
        $landscape_span    = max( 1, absint( $attributes['landscapeSpan'] ?? 2 ) );
        $portrait_span     = max( 1, absint( $attributes['portraitSpan'] ?? 1 ) );
        $poster_align      = sanitize_text_field( $attributes['posterAlign'] ?? 'center' );
        $poster_background = sanitize_hex_color( $attributes['posterBackground'] ?? '' ) ?: '#ffffff';
        $show_shadow       = (bool) ( $attributes['showShadow'] ?? true );

        // "Show All" button attributes.
        $show_all_text        = sanitize_text_field( $attributes['showAllText'] ?? 'Show all' );
        $show_all_show_count  = (bool) ( $attributes['showAllShowCount'] ?? true );
        $show_all_bg          = sanitize_text_field( $attributes['showAllBg'] ?? 'transparent' );
        $show_all_color       = sanitize_hex_color( $attributes['showAllColor'] ?? '' ) ?: '#333333';
        $show_all_border_color = sanitize_hex_color( $attributes['showAllBorderColor'] ?? '' ) ?: '#c0c0c0';
        $show_all_border_width = absint( $attributes['showAllBorderWidth'] ?? 1 );
        $show_all_border_radius = absint( $attributes['showAllBorderRadius'] ?? 24 );

        // Lightbox attributes.
        $lightbox_link_text = ! empty( $attributes['lightboxLinkText'] ) 
            ? sanitize_text_field( $attributes['lightboxLinkText'] ) 
            : '';

        // Build wrapper attributes — merge Gutenberg spacing styles with our custom ones.
        $gallery_class = 'pcast-gallery' . ( $show_shadow ? '' : ' pcast-gallery--no-shadow' );
        $max_width_css = ( 100 === $max_width ) ? '100%' : ( $max_width > 0 ? $max_width . 'px' : 'fit-content' );
        $margin_value  = 'center' === $gallery_align ? '0 auto' : ( 'left' === $gallery_align ? '0 auto 0 0 !important' : '0 0 0 auto !important' );
        $custom_style  = '--pcast-columns: ' . $columns
            . '; --pcast-landscape-span: ' . $landscape_span
            . '; --pcast-portrait-span: ' . $portrait_span
            . '; --pcast-poster-align: ' . $poster_align
            . '; --pcast-max-height: ' . $max_height . 'px'
            . '; --pcast-max-width: ' . $max_width_css
            . '; --pcast-gap: ' . $gap . 'px'
            . '; margin: ' . $margin_value
            . '; --pcast-poster-bg: ' . $poster_background
            . '; --pcast-show-all-bg: ' . $show_all_bg
            . '; --pcast-show-all-color: ' . $show_all_color
            . '; --pcast-show-all-border-color: ' . $show_all_border_color
            . '; --pcast-show-all-border-width: ' . $show_all_border_width . 'px'
            . '; --pcast-show-all-border-radius: ' . $show_all_border_radius . 'px;';
        $wrapper_extra = [
            'class'           => $gallery_class,
            'data-gallery-id' => $gallery_id,
            'style'           => $custom_style,
        ];

        if ( $is_block && function_exists( 'get_block_wrapper_attributes' ) ) {
            $wrapper_attrs = get_block_wrapper_attributes( $wrapper_extra );
        } else {
            $wrapper_attrs = 'class="pcast-gallery" data-gallery-id="' . esc_attr( $gallery_id ) . '" style="' . esc_attr( $custom_style ) . '"';
        }

        // Render the grid template.
        ob_start();
        include PCAST_PLUGIN_DIR . 'templates/gallery-grid.php';
        $output = ob_get_clean();

        // Append lightbox shell once per page.
        if ( ! self::$lightbox_rendered ) {
            ob_start();
            include PCAST_PLUGIN_DIR . 'templates/lightbox.php';
            $output .= ob_get_clean();
            self::$lightbox_rendered = true;
        }

        return $output;
    }

    /**
     * Build an array of poster data for the lightbox JS.
     *
     * @param \WP_Post[] $posters Array of poster post objects.
     * @return array Lightbox-ready data.
     */
    private static function build_lightbox_data( array $posters ): array {
        $items = [];

        foreach ( $posters as $poster ) {
            $thumbnail_id = get_post_thumbnail_id( $poster->ID );

            if ( ! $thumbnail_id ) {
                continue;
            }

            $full_src = wp_get_attachment_image_url( $thumbnail_id, 'full' );

            if ( ! $full_src ) {
                continue;
            }

            $orientation = self::get_poster_orientation( $poster->ID, $thumbnail_id );
            $url         = get_post_meta( $poster->ID, '_pcast_url', true );

            $items[] = [
                'id'          => $poster->ID,
                'title'       => get_the_title( $poster ),
                'imageUrl'    => $full_src,
                'orientation' => $orientation,
                'url'         => $url ? esc_url( $url ) : '',
            ];
        }

        return $items;
    }

    /**
     * Determine the effective orientation for a poster.
     *
     * If orientation_mode is "auto", calculate from the image dimensions.
     * Otherwise use the manual override.
     *
     * @param int $post_id      Poster post ID.
     * @param int $thumbnail_id Attachment ID.
     * @return string "portrait" or "landscape".
     */
    public static function get_poster_orientation( int $post_id, int $thumbnail_id ): string {
        $mode = get_post_meta( $post_id, '_pcast_orientation_mode', true );

        if ( 'auto' === $mode || '' === $mode ) {
            $meta = wp_get_attachment_metadata( $thumbnail_id );

            if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
                return $meta['width'] > $meta['height'] ? 'landscape' : 'portrait';
            }

            return 'portrait';
        }

        // Manual mode — the stored orientation_mode IS the orientation value.
        return in_array( $mode, [ 'portrait', 'landscape' ], true ) ? $mode : 'portrait';
    }
}
