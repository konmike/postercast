<?php

namespace PosterCast;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    private static ?Plugin $instance = null;

    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_action( 'init', [ $this, 'register_types' ] );
        add_action( 'init', [ $this, 'ensure_default_gallery' ], 20 );
        add_action( 'init', [ $this, 'register_block' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // Image orientation auto-detection (works for both admin and REST).
        Image_Orientation::init();

        // Frontend shortcode registration.
        Frontend_Renderer::init();

        // Admin components.
        if ( is_admin() ) {
            new Meta_Fields();
            new Admin_Columns();
            new Admin_Order();
        }
    }

    public function register_types(): void {
        Post_Type_Poster::register();
        Taxonomy_Gallery::register();
    }

    public function register_block(): void {
        $block_path = PCAST_PLUGIN_DIR . 'build/blocks/gallery-block';

        if ( file_exists( $block_path . '/index.js' ) || file_exists( $block_path . '/block.json' ) ) {
            register_block_type( $block_path, [
                'render_callback' => [ Frontend_Renderer::class, 'render_block' ],
            ] );

            wp_set_script_translations(
                'pcast-gallery-editor-script',
                'postercast',
                PCAST_PLUGIN_DIR . 'languages'
            );

            // Pass plugin config to the block editor.
            $default_gallery_id = 0;
            if ( ! apply_filters( 'pcast_enable_multiple_galleries', false ) ) {
                $terms = get_terms( [
                    'taxonomy' => Taxonomy_Gallery::TAXONOMY,
                    'hide_empty' => false,
                    'number'     => 1,
                    'fields'     => 'ids',
                ] );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    $default_gallery_id = (int) $terms[0];
                }
            }

            wp_add_inline_script( 'pcast-gallery-editor-script', sprintf(
                'window.pcastConfig = %s;',
                wp_json_encode( [
                    'isPro'             => defined( 'PCAST_PRO' ),
                    'multipleGalleries' => apply_filters( 'pcast_enable_multiple_galleries', false ),
                    'defaultGalleryId'  => $default_gallery_id,
                ] )
            ), 'before' );
        }

        /**
         * Fires after the main gallery block is registered.
         * Used by PRO add-on to register additional blocks (e.g. poster-link-format).
         */
        do_action( 'pcast_register_blocks' );
    }

    /**
     * Ensure a default gallery exists for the free version.
     */
    public function ensure_default_gallery(): void {
        if ( apply_filters( 'pcast_enable_multiple_galleries', false ) ) {
            return;
        }

        $terms = get_terms( [
            'taxonomy'   => Taxonomy_Gallery::TAXONOMY,
            'hide_empty' => false,
            'number'     => 1,
        ] );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            wp_insert_term(
                __( 'Default Gallery', 'postercast' ),
                Taxonomy_Gallery::TAXONOMY
            );
        }
    }

    public function enqueue_admin_assets( string $hook ): void {
        $screen = get_current_screen();

        if ( ! $screen ) {
            return;
        }

        // Enqueue on poster edit screens and reorder page.
        if ( $screen->post_type === Post_Type_Poster::POST_TYPE || $hook === 'pcast_poster_page_pcast-reorder' ) {
            wp_enqueue_style(
                'pcast-admin',
                PCAST_PLUGIN_URL . 'admin/css/admin-style.css',
                [],
                PCAST_VERSION
            );
        }

        // Drag & drop ordering page.
        if ( $hook === 'pcast_poster_page_pcast-reorder' ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script(
                'pcast-admin-order',
                PCAST_PLUGIN_URL . 'admin/js/admin-order.js',
                [ 'jquery', 'jquery-ui-sortable' ],
                PCAST_VERSION,
                true
            );
            wp_localize_script( 'pcast-admin-order', 'pcastAdmin', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'pcast_reorder' ),
            ] );
        }
    }

    public function register_frontend_assets(): void {
        wp_register_style(
            'pcast-gallery',
            PCAST_PLUGIN_URL . 'public/css/gallery-style.css',
            [],
            PCAST_VERSION
        );

        wp_register_script(
            'pcast-gallery',
            PCAST_PLUGIN_URL . 'public/js/gallery.js',
            [ 'pcast-lightbox' ],
            PCAST_VERSION,
            true
        );

        wp_register_script(
            'pcast-lightbox',
            PCAST_PLUGIN_URL . 'public/js/lightbox.js',
            [],
            PCAST_VERSION,
            true
        );
    }

    public function register_rest_routes(): void {
        $rest_api = new Rest_Api();
        $rest_api->register_routes();
    }
}
