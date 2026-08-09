/* ═══════════════════════════════════════════════════════════════════
   WILAYA HARMONIA — Scripts d'administration
   Design system institutionnel — Bootstrap 5.3 · MDI · i18n
   ═══════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    var I18N = window.WH_I18N || {};

    function t(key, fallback) {
        return I18N[key] || fallback || key;
    }

    /* ── Thème clair / sombre ──────────────────────────────────── */
    function applyTheme(theme, persist) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        var icon = document.querySelector('[data-theme-icon]');
        if (icon) {
            icon.className = theme === 'dark' ? 'mdi mdi-weather-sunny' : 'mdi mdi-weather-night';
        }
        if (persist !== false) {
            try { localStorage.setItem('wh-theme', theme); } catch (e) { /* noop */ }
        }
    }

    function initTheme() {
        var stored = null;
        try { stored = localStorage.getItem('wh-theme'); } catch (e) { /* noop */ }
        var dark = stored === 'dark'
            || (!stored && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        applyTheme(dark ? 'dark' : 'light', false);
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-theme-toggle]');
        if (toggle) {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next, true);
        }
    });

    /* ── Sidebar mobile (offcanvas) ────────────────────────────── */
    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-sidebar-open]');
        if (!opener) return;
        var sidebar = document.getElementById('whSidebar');
        if (sidebar) sidebar.classList.add('show');
        var backdrop = document.getElementById('whSidebarBackdrop');
        if (backdrop) backdrop.classList.add('show');
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-sidebar-close]') || e.target.id === 'whSidebarBackdrop') {
            var sidebar = document.getElementById('whSidebar');
            var backdrop = document.getElementById('whSidebarBackdrop');
            if (sidebar) sidebar.classList.remove('show');
            if (backdrop) backdrop.classList.remove('show');
        }
    });

    /* ── Recherche instantanée dans les tableaux ───────────────── */
    document.addEventListener('keyup', function (e) {
        var input = e.target.closest('[data-table-search]');
        if (!input) return;
        var target = document.getElementById(input.getAttribute('data-table-search'));
        if (!target) return;
        var q = input.value.trim().toLowerCase();
        var rows = target.querySelectorAll('tbody tr');
        var noResult = document.getElementById('whNoResult');
        var visible = 0;
        rows.forEach(function (row) {
            var match = !q || row.textContent.toLowerCase().indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (noResult) noResult.style.display = visible === 0 ? '' : 'none';
    });

    /* ── Confirmations de suppression / actions ────────────────── */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('[data-confirm]')) return;
        var message = form.getAttribute('data-confirm')
            || t('common.confirm_delete', 'Confirmer cette action ?');
        if (!window.confirm(message)) e.preventDefault();
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm-click]');
        if (!btn) return;
        var message = btn.getAttribute('data-confirm-click')
            || t('common.confirm_action', 'Confirmer cette action ?');
        if (!window.confirm(message)) e.preventDefault();
    });

    /* ── Toast d'événements ────────────────────────────────────── */
    function showToast(message, type) {
        var wrap = document.querySelector('.wh-toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'wh-toast-wrap';
            document.body.appendChild(wrap);
        }
        type = type || 'success';
        var icon = type === 'success' ? 'mdi-check-circle'
            : type === 'danger' ? 'mdi-alert-circle'
            : type === 'warning' ? 'mdi-alert' : 'mdi-information';
        var el = document.createElement('div');
        el.className = 'toast align-items-center border-0 show text-bg-' + type;
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="mdi ' + icon + ' me-1"></i>'
            + (message || '') + '</div>'
            + '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        wrap.appendChild(el);
        window.setTimeout(function () {
            el.classList.remove('show');
            window.setTimeout(function () { el.remove(); }, 300);
        }, 5000);
    }

    window.whToast = showToast;

    /* ── Alerts auto-dismiss ───────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        var alerts = document.querySelectorAll('.alert[data-autohide]');
        alerts.forEach(function (a) {
            var delay = parseInt(a.getAttribute('data-autohide'), 10) || 5000;
            window.setTimeout(function () {
                a.classList.add('fade');
                a.classList.remove('show');
                window.setTimeout(function () { a.remove(); }, 250);
            }, delay);
        });
    });

    /* ── Tooltips & poppers ────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        if (window.bootstrap && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        }
    });

    /* ── Checkbox "tout sélectionner" ──────────────────────────── */
    document.addEventListener('change', function (e) {
        var master = e.target.closest('[data-check-all]');
        if (!master) return;
        var group = document.querySelectorAll(master.getAttribute('data-check-all'));
        group.forEach(function (cb) { cb.checked = master.checked; });
    });

    /* ── Sélecteur de langue dans l'en-tête ────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        var select = document.querySelector('[data-locale-switch]');
        if (select) {
            select.addEventListener('change', function () {
                window.location.href = select.value;
            });
        }
    });

    /* ── Notifications in-app ──────────────────────────────────── */
    var CSRF = window.WH_CSRF || '';

    function notifFetch(url, opts) {
        opts = opts || {};
        opts.method = opts.method || 'POST';
        opts.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF
        }, opts.headers || {});
        return fetch(url, opts).catch(function () { /* silencieux */ });
    }

    function notifUpdateBadge() {
        var badge = document.querySelector('[data-notif-badge]');
        if (!badge) return;
        fetch('/api/notifications/unread', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var count = res && typeof res.count === 'number' ? res.count : 0;
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.display = count > 0 ? '' : 'none';
        })
        .catch(function () { /* silencieux */ });
    }

    document.addEventListener('click', function (e) {
        var item = e.target.closest('[data-notif-id]');
        if (item) {
            var id = item.getAttribute('data-notif-id');
            notifFetch('/notifications/' + id + '/read');
            item.classList.add('read');
            var dot = item.querySelector('.wh-notif-dot');
            if (dot) dot.remove();
            if (item.hasAttribute('data-notif-nolink')) {
                e.preventDefault();
                notifUpdateBadge();
            }
            return;
        }

        var readAll = e.target.closest('[data-notif-read-all]');
        if (readAll) {
            e.preventDefault();
            notifFetch('/notifications/read-all');
            document.querySelectorAll('[data-notif-id]').forEach(function (el) {
                el.classList.add('read');
                var dot = el.querySelector('.wh-notif-dot');
                if (dot) dot.remove();
            });
            notifUpdateBadge();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('[data-notif-badge]')) {
            window.setInterval(notifUpdateBadge, 30000);
        }
    });

    /* ── Compteur de sélection d'anomalies ─────────────────────── */
    function initAnomaliesCounters() {
        document.querySelectorAll('[data-anomalies-picker]').forEach(function (picker) {
            var counter = picker.querySelector('[data-anomalies-counter]');
            if (!counter) return;

            var inputs = picker.querySelectorAll('input[name="anomalies[]"]');
            var countEl = counter.querySelector('[data-count]');
            var singular = counter.querySelector('[data-singular]');
            var plural = counter.querySelector('[data-plural]');

            function update() {
                var n = 0;
                inputs.forEach(function (cb) { if (cb.checked) n += 1; });
                if (countEl) countEl.textContent = n;
                if (singular && plural) {
                    singular.hidden = n > 1;
                    plural.hidden = n <= 1;
                }
            }

            inputs.forEach(function (cb) { cb.addEventListener('change', update); });
            update();
        });
    }

    document.addEventListener('DOMContentLoaded', initAnomaliesCounters);

    initTheme();
})();
