/**
 * Poster Gallery — Gallery grid interaction.
 */
( function () {
    'use strict';

    document.addEventListener( 'click', function ( e ) {
        var trigger = e.target.closest( '.pcast-poster__trigger' );

        if ( trigger ) {
            e.preventDefault();
            var posterEl = trigger.closest( '.pcast-poster' );
            var gallery  = trigger.closest( '.pcast-gallery' );

            if ( ! posterEl || ! gallery || ! window.PcastLightbox ) return;

            var galleryId = gallery.getAttribute( 'data-gallery-id' );
            var posterId  = posterEl.getAttribute( 'data-poster-id' );
            var data      = ( window.pcastGalleryData && window.pcastGalleryData[ galleryId ] ) || [];
            var index     = 0;

            for ( var i = 0; i < data.length; i++ ) {
                if ( String( data[ i ].id ) === String( posterId ) ) {
                    index = i;
                    break;
                }
            }

            window.PcastLightbox.open( galleryId, index );
            return;
        }

        var pgOpen = e.target.closest( '[data-pcast-open]' );

        if ( pgOpen ) {
            e.preventDefault();
            var pid = parseInt( pgOpen.getAttribute( 'data-pcast-open' ), 10 );
            if ( ! pid || ! window.pcastGalleryData || ! window.PcastLightbox ) return;
            for ( var gid in window.pcastGalleryData ) {
                if ( ! window.pcastGalleryData.hasOwnProperty( gid ) ) continue;
                var items = window.pcastGalleryData[ gid ];
                for ( var j = 0; j < items.length; j++ ) {
                    if ( items[ j ].id === pid ) {
                        window.PcastLightbox.open( parseInt( gid, 10 ), j );
                        return;
                    }
                }
            }
            return;
        }

        var showAllBtn = e.target.closest( '.pcast-gallery__show-all' );

        if ( showAllBtn ) {
            e.preventDefault();
            var galleryEl = showAllBtn.closest( '.pcast-gallery' );
            if ( ! galleryEl || ! window.PcastLightbox ) return;
            window.PcastLightbox.open( galleryEl.getAttribute( 'data-gallery-id' ), 0 );
        }
    } );
} )();
