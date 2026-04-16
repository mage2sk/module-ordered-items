/**
 * Panth OrderedItems — Modal pagination for order items popup.
 * Pure vanilla JS, no dependencies.
 */
(function () {
    'use strict';

    window.panthOiPaginate = function (modalId, action) {
        var backdrop = document.getElementById(modalId);
        if (!backdrop) return;

        var modal = backdrop.querySelector('.panth-oi-modal');
        var body = modal.querySelector('.panth-oi-modal-body');
        var items = body.querySelectorAll('.panth-oi-modal-item');
        var total = items.length;

        // Get per-page from modal dataset or select
        var perPageRaw = modal.dataset.perPage || '20';
        var showAll = perPageRaw === 'all';
        var perPage = showAll ? total : parseInt(perPageRaw, 10);

        // Get current page
        var currentPage = parseInt(modal.dataset.currentPage || '1', 10);

        if (action === 'next') {
            currentPage++;
        } else if (action === 'prev') {
            currentPage--;
        } else if (typeof action === 'number' || (typeof action === 'string' && !isNaN(action))) {
            currentPage = parseInt(action, 10);
        }

        var totalPages = showAll ? 1 : Math.ceil(total / perPage);
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;

        modal.dataset.currentPage = currentPage;

        // Show/hide items
        var start = (currentPage - 1) * perPage;
        var end = showAll ? total : start + perPage;

        for (var i = 0; i < total; i++) {
            items[i].style.display = (i >= start && i < end) ? 'flex' : 'none';
        }

        // Scroll to top
        body.scrollTop = 0;

        // Update page info
        var pageInfo = modal.querySelector('[data-pageinfo]');
        if (pageInfo) {
            var showStart = start + 1;
            var showEnd = Math.min(end, total);
            if (showAll) {
                pageInfo.textContent = 'Showing all ' + total + ' items';
            } else {
                pageInfo.textContent = 'Showing ' + showStart + '–' + showEnd + ' of ' + total + ' items';
            }
        }

        // Update nav buttons
        var prevBtn = modal.querySelector('[data-prev]');
        var nextBtn = modal.querySelector('[data-next]');
        if (prevBtn) prevBtn.disabled = (currentPage <= 1 || showAll);
        if (nextBtn) nextBtn.disabled = (currentPage >= totalPages || showAll);
    };

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var modals = document.querySelectorAll('.panth-oi-modal-backdrop');
            modals.forEach(function (m) {
                if (m.style.display === 'flex') {
                    m.style.display = 'none';
                }
            });
        }
    });
})();
