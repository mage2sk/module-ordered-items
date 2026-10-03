(function () {
    'use strict';

    var FOCUSABLE = 'a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
    var lastTrigger = null;

    function getModal(backdrop) {
        return backdrop ? backdrop.querySelector('.panth-oi-modal') : null;
    }

    function paginate(modalId, action) {
        var backdrop = document.getElementById(modalId);
        var modal = getModal(backdrop);
        if (!modal) {
            return;
        }
        var items = modal.querySelectorAll('.panth-oi-modal-item');
        var total = items.length;
        var perPageRaw = modal.getAttribute('data-per-page') || '20';
        var showAll = perPageRaw === 'all';
        var perPage = showAll ? Math.max(total, 1) : (parseInt(perPageRaw, 10) || 20);
        var currentPage = parseInt(modal.getAttribute('data-current-page') || '1', 10);

        if (action === 'next') {
            currentPage++;
        } else if (action === 'prev') {
            currentPage--;
        } else if (!isNaN(parseInt(action, 10))) {
            currentPage = parseInt(action, 10);
        }

        var totalPages = showAll ? 1 : Math.max(1, Math.ceil(total / perPage));
        currentPage = Math.min(Math.max(currentPage, 1), totalPages);
        modal.setAttribute('data-current-page', String(currentPage));

        var start = (currentPage - 1) * perPage;
        var end = showAll ? total : start + perPage;
        for (var i = 0; i < total; i++) {
            items[i].hidden = !(i >= start && i < end);
        }

        var body = modal.querySelector('.panth-oi-modal-body');
        if (body) {
            body.scrollTop = 0;
        }

        var pageInfo = modal.querySelector('[data-pageinfo]');
        if (pageInfo) {
            var textAll = pageInfo.getAttribute('data-text-all') || 'Showing all %1 items';
            var textRange = pageInfo.getAttribute('data-text-range') || 'Showing %1-%2 of %3 items';
            pageInfo.textContent = showAll
                ? textAll.replace('%1', total)
                : textRange.replace('%1', total ? start + 1 : 0).replace('%2', Math.min(end, total))
                    .replace('%3', total);
        }

        var prevBtn = modal.querySelector('[data-prev]');
        var nextBtn = modal.querySelector('[data-next]');
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
        }
    }

    function openModal(modalId, trigger) {
        var backdrop = document.getElementById(modalId);
        if (!backdrop) {
            return;
        }
        lastTrigger = trigger || null;
        backdrop.hidden = false;
        document.documentElement.classList.add('panth-oi-modal-open');
        paginate(modalId, 1);
        var closeBtn = backdrop.querySelector('[data-panth-oi-close]');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeModal(backdrop) {
        if (!backdrop || backdrop.hidden) {
            return;
        }
        backdrop.hidden = true;
        if (!document.querySelector('.panth-oi-modal-backdrop:not([hidden])')) {
            document.documentElement.classList.remove('panth-oi-modal-open');
        }
        if (lastTrigger && document.body.contains(lastTrigger)) {
            lastTrigger.focus();
        }
        lastTrigger = null;
    }

    function toggleInline(button) {
        var wrap = document.getElementById(button.getAttribute('data-panth-oi-toggle'));
        if (!wrap) {
            return;
        }
        var expand = button.getAttribute('aria-expanded') !== 'true';
        wrap.querySelectorAll('[data-panth-oi-hidden]').forEach(function (el) {
            el.hidden = !expand;
        });
        button.setAttribute('aria-expanded', expand ? 'true' : 'false');
        button.textContent = button.getAttribute(expand ? 'data-label-less' : 'data-label-more');
    }

    window.panthOiPaginate = paginate;

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target || !target.closest || !target.closest('.panth-oi-wrap')) {
            return;
        }
        e.stopPropagation();

        if (target.classList.contains('panth-oi-modal-backdrop')) {
            closeModal(target);
            return;
        }
        var control = target.closest('button');
        if (!control) {
            return;
        }
        var backdrop = control.closest('.panth-oi-modal-backdrop');
        if (control.hasAttribute('data-panth-oi-open')) {
            e.preventDefault();
            openModal(control.getAttribute('data-panth-oi-open'), control);
        } else if (control.hasAttribute('data-panth-oi-toggle')) {
            e.preventDefault();
            toggleInline(control);
        } else if (control.hasAttribute('data-panth-oi-close')) {
            e.preventDefault();
            closeModal(backdrop);
        } else if (control.hasAttribute('data-prev') && backdrop) {
            e.preventDefault();
            paginate(backdrop.id, 'prev');
        } else if (control.hasAttribute('data-next') && backdrop) {
            e.preventDefault();
            paginate(backdrop.id, 'next');
        }
    }, true);

    document.addEventListener('change', function (e) {
        var select = e.target;
        if (!select || !select.hasAttribute || !select.hasAttribute('data-panth-oi-perpage')) {
            return;
        }
        var backdrop = select.closest('.panth-oi-modal-backdrop');
        var modal = getModal(backdrop);
        if (modal) {
            modal.setAttribute('data-per-page', select.value);
            paginate(backdrop.id, 1);
        }
    });

    document.addEventListener('keydown', function (e) {
        var open = document.querySelector('.panth-oi-modal-backdrop:not([hidden])');
        if (!open) {
            return;
        }
        if (e.key === 'Escape' || e.key === 'Esc') {
            e.preventDefault();
            e.stopPropagation();
            closeModal(open);
            return;
        }
        if (e.key !== 'Tab') {
            return;
        }
        var focusable = Array.prototype.filter.call(
            open.querySelectorAll(FOCUSABLE),
            function (el) {
                return el.offsetParent !== null;
            }
        );
        if (!focusable.length) {
            return;
        }
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (!open.contains(document.activeElement)) {
            e.preventDefault();
            first.focus();
        } else if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }, true);
})();
