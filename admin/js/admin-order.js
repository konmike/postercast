/**
 * Poster Gallery — Reorder Posters admin page.
 *
 * Depends on jQuery and jQuery UI Sortable.
 * Localized data available via pcastAdmin (ajaxUrl, nonce).
 */
(function ($) {
    'use strict';

    var $gallerySelect = $('#pcast-gallery-select');
    var $posterList    = $('#pcast-poster-list');
    var $feedback      = $('#pcast-reorder-feedback');

    /**
     * Show a feedback message.
     *
     * @param {string} message  Text to display.
     * @param {string} type     'success' or 'error'.
     */
    function showFeedback(message, type) {
        $feedback
            .removeClass('pcast-feedback--success pcast-feedback--error')
            .addClass('pcast-feedback--' + type)
            .text(message)
            .fadeIn(200);

        if (type === 'success') {
            setTimeout(function () {
                $feedback.fadeOut(300);
            }, 3000);
        }
    }

    /**
     * Initialize jQuery UI Sortable on the poster list.
     */
    function initSortable() {
        $posterList.sortable({
            items: '.pcast-poster-card',
            handle: '.pcast-sortable-handle',
            placeholder: 'pcast-sortable-placeholder',
            cursor: 'grabbing',
            opacity: 0.8,
            tolerance: 'pointer',
            update: function () {
                saveOrder();
            }
        });
    }

    /**
     * Load posters for the selected gallery via AJAX.
     */
    function loadPosters() {
        var galleryId = $gallerySelect.val();

        if (!galleryId) {
            $posterList.html('<p class="pcast-empty-message">' +
                'Select a gallery to load posters.</p>');
            return;
        }

        $posterList.addClass('pcast-loading');

        $.post(pcastAdmin.ajaxUrl, {
            action: 'pcast_load_posters',
            nonce: pcastAdmin.nonce,
            gallery_id: galleryId
        }, function (response) {
            $posterList.removeClass('pcast-loading');

            if (response.success) {
                $posterList.html(response.data);
                initSortable();
            } else {
                $posterList.html('<p class="pcast-empty-message">' +
                    (response.data || 'Error loading posters.') + '</p>');
            }
        }).fail(function () {
            $posterList.removeClass('pcast-loading');
            showFeedback('Failed to load posters. Please try again.', 'error');
        });
    }

    /**
     * Save the current poster order via AJAX.
     */
    function saveOrder() {
        var galleryId = $gallerySelect.val();
        var order     = [];

        $posterList.find('.pcast-poster-card').each(function () {
            order.push($(this).data('post-id'));
        });

        if (!galleryId || !order.length) {
            return;
        }

        $.post(pcastAdmin.ajaxUrl, {
            action: 'pcast_update_order',
            nonce: pcastAdmin.nonce,
            gallery_id: galleryId,
            order: order
        }, function (response) {
            if (response.success) {
                showFeedback(response.data, 'success');
            } else {
                showFeedback(response.data || 'Error saving order.', 'error');
            }
        }).fail(function () {
            showFeedback('Failed to save order. Please try again.', 'error');
        });
    }

    // Bind events.
    $gallerySelect.on('change', function () {
        $feedback.hide();
        loadPosters();
    });

})(jQuery);
