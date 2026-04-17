<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Order {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'maybe_add_submenu_page' ] );
        add_action( 'wp_ajax_pcast_update_order', [ $this, 'ajax_update_order' ] );
        add_action( 'wp_ajax_pcast_load_posters', [ $this, 'ajax_load_posters' ] );
    }

    /**
     * Conditionally add the Reorder Posters submenu page (PRO only).
     */
    public function maybe_add_submenu_page(): void {
        if ( ! apply_filters( 'pcast_enable_reorder_page', false ) ) {
            return;
        }
        $this->add_submenu_page();
    }

    /**
     * Add the Reorder Posters submenu page under the Poster menu.
     */
    public function add_submenu_page(): void {
        add_submenu_page(
            'edit.php?post_type=' . Post_Type_Poster::POST_TYPE,
            __( 'Reorder Posters', 'postercast' ),
            __( 'Reorder', 'postercast' ),
            'edit_posts',
            'pcast-reorder',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render the Reorder Posters admin page.
     */
    public function render_page(): void {
        $galleries = get_terms( [
            'taxonomy'   => Taxonomy_Gallery::TAXONOMY,
            'hide_empty' => false,
        ] );
        ?>
        <div class="wrap pcast-reorder-wrap">
            <h1><?php esc_html_e( 'Reorder Posters', 'postercast' ); ?></h1>

            <div class="pcast-reorder-controls">
                <label for="pcast-gallery-select">
                    <?php esc_html_e( 'Select Gallery:', 'postercast' ); ?>
                </label>
                <select id="pcast-gallery-select">
                    <option value=""><?php esc_html_e( '&mdash; Select a gallery &mdash;', 'postercast' ); ?></option>
                    <?php if ( ! is_wp_error( $galleries ) ) : ?>
                        <?php foreach ( $galleries as $gallery ) : ?>
                            <option value="<?php echo esc_attr( $gallery->term_id ); ?>">
                                <?php echo esc_html( $gallery->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div id="pcast-reorder-feedback" class="pcast-feedback" style="display:none;"></div>

            <div id="pcast-poster-list" class="pcast-poster-list">
                <p class="pcast-empty-message">
                    <?php esc_html_e( 'Select a gallery to load posters.', 'postercast' ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler: Load posters for a given gallery.
     */
    public function ajax_load_posters(): void {
        check_ajax_referer( 'pcast_reorder', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'postercast' ) );
        }

        $gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;

        if ( ! $gallery_id ) {
            wp_send_json_error( __( 'Invalid gallery.', 'postercast' ) );
        }

        $posters = get_posts( [
            'post_type'      => Post_Type_Poster::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'any',
            'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                [
                    'taxonomy' => Taxonomy_Gallery::TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => $gallery_id,
                ],
            ],
        ] );

        $html = '';

        if ( empty( $posters ) ) {
            $html = '<p class="pcast-empty-message">' . esc_html__( 'No posters found in this gallery.', 'postercast' ) . '</p>';
        } else {
            foreach ( $posters as $poster ) {
                $thumbnail = get_the_post_thumbnail( $poster->ID, [ 80, 100 ], [
                    'class' => 'pcast-card-thumb',
                ] );

                if ( ! $thumbnail ) {
                    $thumbnail = '<div class="pcast-card-thumb-placeholder"></div>';
                }

                $html .= sprintf(
                    '<div class="pcast-poster-card" data-post-id="%d">
                        <span class="pcast-sortable-handle dashicons dashicons-menu"></span>
                        <div class="pcast-card-thumbnail">%s</div>
                        <div class="pcast-card-title">%s</div>
                    </div>',
                    esc_attr( $poster->ID ),
                    $thumbnail,
                    esc_html( get_the_title( $poster->ID ) )
                );
            }
        }

        wp_send_json_success( $html );
    }

    /**
     * AJAX handler: Update poster order.
     */
    public function ajax_update_order(): void {
        check_ajax_referer( 'pcast_reorder', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'postercast' ) );
        }

        $gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;
        $order      = isset( $_POST['order'] ) && is_array( $_POST['order'] ) ? array_map( 'absint', $_POST['order'] ) : [];

        if ( ! $gallery_id || empty( $order ) ) {
            wp_send_json_error( __( 'Invalid data.', 'postercast' ) );
        }

        global $wpdb;

        foreach ( $order as $position => $post_id ) {
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->posts,
                [ 'menu_order' => $position ],
                [ 'ID' => $post_id ],
                [ '%d' ],
                [ '%d' ]
            );
            clean_post_cache( $post_id );
        }

        wp_send_json_success( __( 'Order updated successfully.', 'postercast' ) );
    }
}
