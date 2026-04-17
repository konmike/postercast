<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Columns {

    public function __construct() {
        add_filter( 'manage_pcast_poster_posts_columns', [ $this, 'register_columns' ] );
        add_action( 'manage_pcast_poster_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
        add_filter( 'manage_edit-pcast_poster_sortable_columns', [ $this, 'sortable_columns' ] );
        add_action( 'pre_get_posts', [ $this, 'sort_by_order' ] );
    }

    /**
     * Register custom columns for the poster list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function register_columns( array $columns ): array {
        $new_columns = [];

        // Insert checkbox first.
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
            unset( $columns['cb'] );
        }

        $new_columns['pcast_thumbnail'] = __( 'Thumbnail', 'postercast' );

        // Re-insert title.
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
            unset( $columns['title'] );
        }

        $new_columns['pcast_orientation'] = __( 'Orientation', 'postercast' );
        if ( apply_filters( 'pcast_show_date_column', false ) ) {
            $new_columns['pcast_date_range'] = __( 'Date Range', 'postercast' );
        }
        if ( apply_filters( 'pcast_show_order_column', false ) ) {
            $new_columns['pcast_order'] = __( 'Order', 'postercast' );
        }

        // Append remaining columns (taxonomy, date, etc.).
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
        }

        return $new_columns;
    }

    /**
     * Render custom column content.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_column( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'pcast_thumbnail':
                $thumbnail_id = get_post_thumbnail_id( $post_id );
                if ( $thumbnail_id ) {
                    echo wp_get_attachment_image( $thumbnail_id, [ 60, 80 ], false, [
                        'style' => 'width:60px;height:80px;object-fit:cover;border-radius:4px;',
                    ] );
                } else {
                    echo '<span class="pcast-no-image" aria-label="' . esc_attr__( 'No image', 'postercast' ) . '">&mdash;</span>';
                }
                break;

            case 'pcast_orientation':
                $orientation = get_post_meta( $post_id, '_pcast_orientation', true );
                $mode        = get_post_meta( $post_id, '_pcast_orientation_mode', true );
                echo esc_html( ucfirst( $orientation ?: 'portrait' ) );
                if ( 'auto' === $mode || '' === $mode ) {
                    echo ' <span class="description">(' . esc_html__( 'auto', 'postercast' ) . ')</span>';
                }
                break;

            case 'pcast_date_range':
                $this->render_date_range_column( $post_id );
                break;

            case 'pcast_order':
                $post = get_post( $post_id );
                echo esc_html( $post->menu_order );
                break;
        }
    }

    /**
     * Render the date range column with status badges.
     *
     * @param int $post_id Post ID.
     */
    private function render_date_range_column( int $post_id ): void {
        $show_from  = get_post_meta( $post_id, '_pcast_show_from', true );
        $show_until = get_post_meta( $post_id, '_pcast_show_until', true );
        $today      = current_time( 'Y-m-d' );

        if ( $show_from && $show_until ) {
            printf(
                '%s &ndash; %s',
                esc_html( date_i18n( get_option( 'date_format' ), strtotime( $show_from ) ) ),
                esc_html( date_i18n( get_option( 'date_format' ), strtotime( $show_until ) ) )
            );
        } elseif ( $show_from ) {
            printf(
                /* translators: %s: start date */
                esc_html__( 'From %s', 'postercast' ),
                esc_html( date_i18n( get_option( 'date_format' ), strtotime( $show_from ) ) )
            );
        } elseif ( $show_until ) {
            printf(
                /* translators: %s: end date */
                esc_html__( 'Until %s', 'postercast' ),
                esc_html( date_i18n( get_option( 'date_format' ), strtotime( $show_until ) ) )
            );
        } else {
            echo '<span class="description">' . esc_html__( 'Always', 'postercast' ) . '</span>';
        }

        // Status badges.
        if ( $show_until && $show_until < $today ) {
            echo ' <span class="pcast-badge pcast-badge--expired">' . esc_html__( 'Expired', 'postercast' ) . '</span>';
        } elseif ( $show_from && $show_from > $today ) {
            echo ' <span class="pcast-badge pcast-badge--scheduled">' . esc_html__( 'Scheduled', 'postercast' ) . '</span>';
        }
    }

    /**
     * Register sortable columns.
     *
     * @param array $columns Sortable columns.
     * @return array Modified sortable columns.
     */
    public function sortable_columns( array $columns ): array {
        $columns['pcast_order'] = 'menu_order';
        return $columns;
    }

    /**
     * Handle sorting by menu_order in admin.
     *
     * @param \WP_Query $query The query object.
     */
    public function sort_by_order( \WP_Query $query ): void {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'post_type' ) !== Post_Type_Poster::POST_TYPE ) {
            return;
        }

        if ( 'menu_order' === $query->get( 'orderby' ) ) {
            $query->set( 'orderby', 'menu_order' );
            $query->set( 'order', $query->get( 'order' ) ?: 'ASC' );
        }
    }
}
