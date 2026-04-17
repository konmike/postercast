/**
 * Poster Gallery — Lightbox.
 *
 * Full-screen image viewer with keyboard/swipe navigation, image preloading,
 * focus trap, and a thumbnail strip for poster navigation.
 *
 * Poster data: window.pcastGalleryData[ galleryId ]
 * Each entry: { id, title, imageUrl, orientation, url }
 */
( function () {
    'use strict';

    var el           = {};
    var currentData  = [];
    var currentIndex = 0;
    var currentGalleryId = null;
    var isOpen       = false;
    var touchStartX  = 0;
    var touchStartY  = 0;
    var focusableEls = [];
    var lastFocused  = null;

    /* ------------------------------------------------------------------
       DOM
       ------------------------------------------------------------------ */

    function ensureDOM() {
        if ( el.root ) return;

        var existing = document.querySelector( '.pcast-lightbox' );

        if ( existing ) {
            el.root       = existing;
            el.backdrop   = existing.querySelector( '.pcast-lightbox__backdrop' );
            el.content    = existing.querySelector( '.pcast-lightbox__content' );
            el.close      = existing.querySelector( '.pcast-lightbox__close' );
            el.prev       = existing.querySelector( '.pcast-lightbox__prev' );
            el.next       = existing.querySelector( '.pcast-lightbox__next' );
            el.imgWrapper = existing.querySelector( '.pcast-lightbox__image-wrapper' );
            el.image      = existing.querySelector( '.pcast-lightbox__image' );
            el.footer     = existing.querySelector( '.pcast-lightbox__footer' );
            el.title      = existing.querySelector( '.pcast-lightbox__title' );
            el.link       = existing.querySelector( '.pcast-lightbox__link' );
            el.counter    = existing.querySelector( '.pcast-lightbox__counter' );
        } else {
            el.root = mk( 'div', 'pcast-lightbox', { role: 'dialog', 'aria-modal': 'true', 'aria-label': 'Image lightbox', hidden: '' } );
            el.backdrop   = mk( 'div', 'pcast-lightbox__backdrop' );
            el.content    = mk( 'div', 'pcast-lightbox__content' );
            el.close      = mk( 'button', 'pcast-lightbox__close', { type: 'button', 'aria-label': 'Close lightbox' } );
            el.close.innerHTML = '<span aria-hidden="true">&times;</span>';
            el.prev       = mk( 'button', 'pcast-lightbox__prev', { type: 'button', 'aria-label': 'Previous image' } );
            el.prev.innerHTML = '<span aria-hidden="true">&#8249;</span>';
            el.next       = mk( 'button', 'pcast-lightbox__next', { type: 'button', 'aria-label': 'Next image' } );
            el.next.innerHTML = '<span aria-hidden="true">&#8250;</span>';
            el.imgWrapper = mk( 'div', 'pcast-lightbox__image-wrapper' );
            el.image      = mk( 'img', 'pcast-lightbox__image', { src: '', alt: '' } );
            el.imgWrapper.appendChild( el.image );
            el.footer     = mk( 'div', 'pcast-lightbox__footer' );
            el.title      = mk( 'span', 'pcast-lightbox__title' );
            el.link       = mk( 'a', 'pcast-lightbox__link', { href: '#', target: '_blank', rel: 'noopener noreferrer' } );
            el.link.textContent = 'Visit link \u2192';
            el.footer.appendChild( el.title );
            el.footer.appendChild( el.link );
            el.counter    = mk( 'div', 'pcast-lightbox__counter' );

            el.content.appendChild( el.close );
            el.content.appendChild( el.prev );
            el.content.appendChild( el.imgWrapper );
            el.content.appendChild( el.next );
            el.content.appendChild( el.footer );
            el.content.appendChild( el.counter );
            el.root.appendChild( el.backdrop );
            el.root.appendChild( el.content );
            document.body.appendChild( el.root );
        }

        // Create thumbnail strip container (always dynamic).
        el.thumbStrip = mk( 'div', 'pcast-lightbox__thumbs' );
        el.counter.parentNode.insertBefore( el.thumbStrip, el.counter );

        bindEvents();
    }

    function mk( tag, cls, attrs ) {
        var node = document.createElement( tag );
        node.className = cls;
        if ( attrs ) {
            for ( var k in attrs ) {
                if ( attrs.hasOwnProperty( k ) ) node.setAttribute( k, attrs[ k ] );
            }
        }
        return node;
    }

    /* ------------------------------------------------------------------
       Events
       ------------------------------------------------------------------ */

    function bindEvents() {
        el.close.addEventListener( 'click', close );
        el.backdrop.addEventListener( 'click', close );
        el.prev.addEventListener( 'click', function () { navigate( -1 ); } );
        el.next.addEventListener( 'click', function () { navigate( 1 ); } );
        document.addEventListener( 'keydown', onKeyDown );
        el.root.addEventListener( 'touchstart', onTouchStart, { passive: true } );
        el.root.addEventListener( 'touchend', onTouchEnd );
    }

    function onKeyDown( e ) {
        if ( ! isOpen ) return;
        switch ( e.key ) {
            case 'Escape':    close(); break;
            case 'ArrowLeft': navigate( -1 ); break;
            case 'ArrowRight':navigate( 1 ); break;
            case 'Tab':       trapFocus( e ); break;
        }
    }

    function onTouchStart( e ) {
        if ( e.touches.length === 1 ) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }
    }

    function onTouchEnd( e ) {
        if ( ! isOpen || e.changedTouches.length !== 1 ) return;
        var dx = e.changedTouches[0].clientX - touchStartX;
        var dy = e.changedTouches[0].clientY - touchStartY;
        if ( Math.abs( dx ) > 50 && Math.abs( dx ) > Math.abs( dy ) ) {
            navigate( dx < 0 ? 1 : -1 );
        }
    }

    function trapFocus( e ) {
        focusableEls = [].slice.call(
            el.root.querySelectorAll( 'button, a[href], [tabindex]:not([tabindex="-1"])' )
        ).filter( function ( n ) { return ! n.hasAttribute( 'hidden' ) && n.offsetParent !== null; } );

        if ( ! focusableEls.length ) { e.preventDefault(); return; }
        var first = focusableEls[0], last = focusableEls[ focusableEls.length - 1 ];
        if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
        else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
    }

    /* ------------------------------------------------------------------
       Public API
       ------------------------------------------------------------------ */

    function open( galleryId, index ) {
        ensureDOM();
        var data = ( window.pcastGalleryData && window.pcastGalleryData[ galleryId ] ) || [];
        if ( ! data.length ) return;

        currentData      = data;
        currentGalleryId = galleryId;
        currentIndex = ( typeof index === 'number' && index >= 0 && index < data.length ) ? index : 0;
        lastFocused  = document.activeElement;

        buildThumbs();

        el.root.removeAttribute( 'hidden' );
        void el.root.offsetHeight;
        el.root.classList.add( 'pcast-lightbox--open' );
        document.body.style.overflow = 'hidden';
        isOpen = true;

        showPoster( currentIndex );
        el.close.focus();
    }

    function close() {
        if ( ! isOpen ) return;
        el.root.classList.remove( 'pcast-lightbox--open' );
        document.body.style.overflow = '';
        isOpen = false;
        setTimeout( function () { el.root.setAttribute( 'hidden', '' ); }, 300 );
        if ( lastFocused ) { lastFocused.focus(); lastFocused = null; }
    }

    function navigate( dir ) {
        if ( ! currentData.length ) return;
        var i = currentIndex + dir;
        if ( i < 0 ) i = currentData.length - 1;
        else if ( i >= currentData.length ) i = 0;
        showPoster( i );
    }

    function showPoster( index ) {
        if ( index < 0 || index >= currentData.length ) return;
        currentIndex = index;
        var poster = currentData[ index ];

        el.image.classList.remove( 'pcast-lightbox__image--loaded' );

        var img = new Image();
        img.onload = img.onerror = function () {
            el.image.src = poster.imageUrl;
            el.image.alt = poster.title || '';
            el.image.classList.add( 'pcast-lightbox__image--loaded' );
        };
        img.src = poster.imageUrl;

        el.title.textContent = poster.title || '';

        var galleryConfig = window.pcastGalleryConfig && window.pcastGalleryConfig[ currentGalleryId ];
        var linkText = ( galleryConfig && galleryConfig.linkText ) || '';

        if ( poster.url ) {
            el.link.href = poster.url;
            el.link.textContent = ( linkText || 'Visit link' ) + ' \u2192';
            el.link.removeAttribute( 'hidden' );
        } else {
            el.link.setAttribute( 'hidden', '' );
        }

        // Hide counter text, we use thumbs now.
        el.counter.style.display = 'none';

        if ( currentData.length <= 1 ) {
            el.prev.setAttribute( 'hidden', '' );
            el.next.setAttribute( 'hidden', '' );
        } else {
            el.prev.removeAttribute( 'hidden' );
            el.next.removeAttribute( 'hidden' );
        }

        updateThumbs( index );
        preload( index - 1 );
        preload( index + 1 );
    }

    function preload( index ) {
        if ( currentData.length <= 1 ) return;
        if ( index < 0 ) index = currentData.length - 1;
        else if ( index >= currentData.length ) index = 0;
        if ( currentData[ index ].imageUrl ) { var i = new Image(); i.src = currentData[ index ].imageUrl; }
    }

    /* ------------------------------------------------------------------
       Thumbnail strip
       ------------------------------------------------------------------ */

    function buildThumbs() {
        el.thumbStrip.innerHTML = '';

        for ( var i = 0; i < currentData.length; i++ ) {
            var thumb = document.createElement( 'button' );
            thumb.className = 'pcast-lightbox__thumb';
            thumb.type = 'button';
            thumb.setAttribute( 'aria-label', currentData[ i ].title || ( 'Poster ' + ( i + 1 ) ) );
            thumb.setAttribute( 'data-index', i );

            var img = document.createElement( 'img' );
            img.src = currentData[ i ].imageUrl;
            img.alt = '';
            img.draggable = false;

            thumb.appendChild( img );
            el.thumbStrip.appendChild( thumb );

            ( function ( idx ) {
                thumb.addEventListener( 'click', function ( e ) {
                    e.stopPropagation();
                    showPoster( idx );
                } );
            } )( i );
        }
    }

    function updateThumbs( activeIndex ) {
        var thumbs = el.thumbStrip.querySelectorAll( '.pcast-lightbox__thumb' );
        for ( var i = 0; i < thumbs.length; i++ ) {
            if ( i === activeIndex ) {
                thumbs[ i ].classList.add( 'pcast-lightbox__thumb--active' );
            } else {
                thumbs[ i ].classList.remove( 'pcast-lightbox__thumb--active' );
            }
        }

        // Scroll active thumb into view.
        if ( thumbs[ activeIndex ] ) {
            thumbs[ activeIndex ].scrollIntoView( { inline: 'center', block: 'nearest', behavior: 'smooth' } );
        }
    }

    /* ------------------------------------------------------------------
       Expose
       ------------------------------------------------------------------ */

    window.PcastLightbox = {
        open: open,
        close: close,
        navigate: navigate,
        showPoster: showPoster
    };

} )();
