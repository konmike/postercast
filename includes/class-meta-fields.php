<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Meta_Fields {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_pcast_poster', [ $this, 'save_meta' ], 10, 2 );
    }

    /**
     * Register the Poster Details meta box.
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'pcast_poster_details',
            __( 'Poster Details', 'postercast' ),
            [ $this, 'render_meta_box' ],
            Post_Type_Poster::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Render the Poster Details meta box.
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'pcast_poster_details', 'pcast_poster_details_nonce' );

        $description      = get_post_meta( $post->ID, '_pcast_description', true );
        $url              = get_post_meta( $post->ID, '_pcast_url', true );
        $orientation_mode = get_post_meta( $post->ID, '_pcast_orientation_mode', true ) ?: 'auto';
        $orientation      = get_post_meta( $post->ID, '_pcast_orientation', true ) ?: 'portrait';
        // Detect orientation from featured image for the auto note.
        $detected = 'portrait';
        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        if ( $thumbnail_id ) {
            $detected = Image_Orientation::detect( (int) $thumbnail_id );
        }
        ?>
        <div class="pcast-meta-fields">
            <p class="pcast-meta-field">
                <label for="pcast_description">
                    <?php esc_html_e( 'Description', 'postercast' ); ?>
                </label>
                <textarea id="pcast_description" name="pcast_description" rows="4" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
                <span class="description"><?php esc_html_e( 'Admin-only note. Not displayed on the frontend.', 'postercast' ); ?></span>
            </p>

            <p class="pcast-meta-field">
                <label for="pcast_url">
                    <?php esc_html_e( 'URL', 'postercast' ); ?>
                </label>
                <input type="url" id="pcast_url" name="pcast_url" value="<?php echo esc_url( $url ); ?>" class="large-text" placeholder="https://" />
            </p>

            <p class="pcast-meta-field">
                <label for="pcast_orientation_mode">
                    <?php esc_html_e( 'Orientation Mode', 'postercast' ); ?>
                </label>
                <select id="pcast_orientation_mode" name="pcast_orientation_mode">
                    <option value="auto" <?php selected( $orientation_mode, 'auto' ); ?>>
                        <?php esc_html_e( 'Auto', 'postercast' ); ?>
                    </option>
                    <option value="portrait" <?php selected( $orientation_mode, 'portrait' ); ?>>
                        <?php esc_html_e( 'Portrait', 'postercast' ); ?>
                    </option>
                    <option value="landscape" <?php selected( $orientation_mode, 'landscape' ); ?>>
                        <?php esc_html_e( 'Landscape', 'postercast' ); ?>
                    </option>
                </select>
                <?php if ( 'auto' === $orientation_mode ) : ?>
                    <span class="description">
                        <?php
                        printf(
                            /* translators: %s: detected orientation value */
                            esc_html__( 'Detected: %s', 'postercast' ),
                            esc_html( ucfirst( $detected ) )
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </p>

            <?php
            /**
             * Fires after the basic poster meta fields (description, URL, orientation).
             * Used by PRO add-on to inject alignment and date scheduling fields.
             *
             * @param \WP_Post $post Current post object.
             */
            do_action( 'pcast_meta_box_after_basic_fields', $post );
            ?>
        </div>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     */
    public function save_meta( int $post_id, \WP_Post $post ): void {
        // Verify nonce.
        if (
            ! isset( $_POST['pcast_poster_details_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pcast_poster_details_nonce'] ) ), 'pcast_poster_details' )
        ) {
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize and save each field.
        $description = isset( $_POST['pcast_description'] )
            ? sanitize_textarea_field( wp_unslash( $_POST['pcast_description'] ) )
            : '';
        update_post_meta( $post_id, '_pcast_description', $description );

        $url = isset( $_POST['pcast_url'] )
            ? esc_url_raw( wp_unslash( $_POST['pcast_url'] ) )
            : '';
        update_post_meta( $post_id, '_pcast_url', $url );

        $orientation_mode = isset( $_POST['pcast_orientation_mode'] )
            ? Post_Type_Poster::sanitize_orientation_mode( sanitize_text_field( wp_unslash( $_POST['pcast_orientation_mode'] ) ) )
            : 'auto';
        update_post_meta( $post_id, '_pcast_orientation_mode', $orientation_mode );

        // Determine and save the effective orientation.
        if ( 'auto' === $orientation_mode ) {
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            $orientation  = $thumbnail_id ? Image_Orientation::detect( (int) $thumbnail_id ) : 'portrait';
        } else {
            $orientation = $orientation_mode;
        }
        update_post_meta( $post_id, '_pcast_orientation', $orientation );

        $show_from = isset( $_POST['pcast_show_from'] )
            ? sanitize_text_field( wp_unslash( $_POST['pcast_show_from'] ) )
            : '';
        update_post_meta( $post_id, '_pcast_show_from', $show_from );

        $show_until = isset( $_POST['pcast_show_until'] )
            ? sanitize_text_field( wp_unslash( $_POST['pcast_show_until'] ) )
            : '';
        update_post_meta( $post_id, '_pcast_show_until', $show_until );

        $align = isset( $_POST['pcast_align'] )
            ? sanitize_text_field( wp_unslash( $_POST['pcast_align'] ) )
            : 'stretch';
        update_post_meta( $post_id, '_pcast_align', $align );

        $valign = isset( $_POST['pcast_valign'] )
            ? sanitize_text_field( wp_unslash( $_POST['pcast_valign'] ) )
            : 'stretch';
        update_post_meta( $post_id, '_pcast_valign', $valign );
    }
}
