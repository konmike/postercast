<?php
/**
 * Lightbox shell template.
 *
 * Inserted once per page. The actual content is populated dynamically by JS.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="pcast-lightbox" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Image lightbox', 'postercast' ); ?>" hidden>
    <div class="pcast-lightbox__backdrop"></div>

    <div class="pcast-lightbox__content">
        <button type="button" class="pcast-lightbox__close" aria-label="<?php esc_attr_e( 'Close lightbox', 'postercast' ); ?>">
            <span aria-hidden="true">&times;</span>
        </button>

        <button type="button" class="pcast-lightbox__prev" aria-label="<?php esc_attr_e( 'Previous image', 'postercast' ); ?>">
            <span aria-hidden="true">&#8249;</span>
        </button>

        <div class="pcast-lightbox__image-wrapper">
            <img class="pcast-lightbox__image" src="" alt="" />
        </div>

        <button type="button" class="pcast-lightbox__next" aria-label="<?php esc_attr_e( 'Next image', 'postercast' ); ?>">
            <span aria-hidden="true">&#8250;</span>
        </button>

        <div class="pcast-lightbox__footer">
            <span class="pcast-lightbox__title"></span>
            <a class="pcast-lightbox__link" href="#" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Visit link', 'postercast' ); ?> &rarr;
            </a>
        </div>

        <div class="pcast-lightbox__counter"></div>
    </div>
</div>
