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

    /* ── Sidebar repliée : tooltips natifs sur les liens ──────── */
    (function () {
        var app = document.querySelector('.wh-app');
        var sidebar = document.getElementById('whSidebar');
        if (!app || !sidebar) return;

        function syncTooltips() {
            var collapsed = app.classList.contains('has-collapsed');
            var desktop = window.innerWidth >= 992;
            sidebar.querySelectorAll('a.nav-link').forEach(function (a) {
                var label = a.querySelector('span');
                if (collapsed && desktop) {
                    // Ne surcharge pas un title existant (badges etc.).
                    if (!a.hasAttribute('data-wh-title')) {
                        a.setAttribute('data-wh-title', label ? label.textContent.trim() : '');
                    }
                    a.title = a.getAttribute('data-wh-title') || '';
                    // Rendu accessible pour basse résolution.
                    a.setAttribute('aria-label', a.getAttribute('data-wh-title') || '');
                } else {
                    a.title = '';
                    a.removeAttribute('aria-label');
                }
            });
        }

        // Écoute le basculement déclenché par le bouton de collapse (main.php).
        var observer = new MutationObserver(syncTooltips);
        observer.observe(app, {
            attributes: true,
            attributeFilter: ['class']
        });
        window.addEventListener('resize', syncTooltips);
        syncTooltips();
    })();

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

    // Fermeture du sidebar mobile au clic sur un lien du menu
    // (après navigation, le menu ne doit pas rester ouvert).
    document.addEventListener('click', function (e) {
        var link = e.target.closest('#whSidebar a[href]');
        if (!link) return;
        var sidebar = document.getElementById('whSidebar');
        var backdrop = document.getElementById('whSidebarBackdrop');
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    });

    // Fermeture du sidebar mobile avec la touche Échap
    document.addEventListener('keyup', function (e) {
        if (e.key !== 'Escape') return;
        var sidebar = document.getElementById('whSidebar');
        var backdrop = document.getElementById('whSidebarBackdrop');
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    });

    /* ── Indicateur de défilement horizontal des tableaux ─────── */
    function initTableScrollHints() {
        document.querySelectorAll('.table-responsive').forEach(function (wrap) {
            var check = function () {
                var scrollable = wrap.scrollWidth > wrap.clientWidth + 2;
                wrap.classList.toggle('wh-table-scroll', scrollable);
                var hint = wrap.querySelector(':scope > .wh-table-hint');
                if (scrollable) {
                    if (!hint) {
                        hint = document.createElement('span');
                        hint.className = 'wh-table-hint';
                        hint.setAttribute('aria-hidden', 'true');
                        hint.innerHTML = '<i class="mdi mdi-swipe-horizontal"></i>'
                            + t('common.table_scroll', 'Faire défiler');
                        wrap.appendChild(hint);
                    }
                } else if (hint) {
                    hint.remove();
                }
            };
            check();
            window.addEventListener('resize', check);
        });
    }

    /* ── Filtres : accordéon sur mobile ────────────────────────── */
    function initMobileFilters() {
        document.querySelectorAll('form.wh-filters').forEach(function (form) {
            var row = form.querySelector(':scope > .row, :scope > .card-body > .row');
            if (!row) return;
            var container = row.parentNode;
            row.classList.add('wh-filters-body');
            var btn = form.querySelector(':scope > .wh-filters-toggle, :scope > .card-body > .wh-filters-toggle');
            if (!btn) {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'wh-filters-toggle';
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '<i class="mdi mdi-tune-variant" aria-hidden="true"></i>'
                    + t('common.filters', 'Filtres')
                    + '<i class="mdi mdi-chevron-down" aria-hidden="true"></i>';
                container.insertBefore(btn, row);
            }
            btn.addEventListener('click', function () {
                var open = form.classList.toggle('wh-filters-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTableScrollHints();
        initMobileFilters();
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

    /* ── Confirmations de suppression / actions (modale) ───────── */
    function confirmModal(message, onConfirm) {
        var wrap = document.querySelector('.wh-confirm-modal');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'modal fade wh-confirm-modal';
            wrap.tabIndex = -1;
            wrap.setAttribute('aria-hidden', 'true');
            wrap.innerHTML =
                '<div class="modal-dialog modal-dialog-centered">'
                + '<div class="modal-content">'
                + '<div class="modal-header">'
                + '<h6 class="modal-title"><i class="mdi mdi-alert-outline me-1 text-danger"></i>'
                + t('common.confirm_title', 'Confirmation') + '</h6>'
                + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' + t('common.close', 'Fermer') + '"></button>'
                + '</div>'
                + '<div class="modal-body"></div>'
                + '<div class="modal-footer">'
                + '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">' + t('common.cancel', 'Annuler') + '</button>'
                + '<button type="button" class="btn btn-danger wh-confirm-ok"><i class="mdi mdi-check me-1"></i>'
                + t('common.confirm', 'Confirmer') + '</button>'
                + '</div>'
                + '</div>'
                + '</div>';
            document.body.appendChild(wrap);
        }
        wrap.querySelector('.modal-body').textContent = message;
        var ok = wrap.querySelector('.wh-confirm-ok');
        var instance = bootstrap.Modal.getOrCreateInstance(wrap);
        ok.onclick = function () {
            instance.hide();
            onConfirm();
        };
        instance.show();
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('[data-confirm]')) return;
        e.preventDefault();
        var message = form.getAttribute('data-confirm')
            || t('common.confirm_delete', 'Confirmer cette action ?');
        confirmModal(message, function () { form.submit(); });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm-click]');
        if (!btn) return;
        e.preventDefault();
        var message = btn.getAttribute('data-confirm-click')
            || t('common.confirm_action', 'Confirmer cette action ?');
        confirmModal(message, function () {
            if (btn.tagName === 'FORM') { btn.submit(); return; }
            var form = btn.form;
            if (form) {
                var index = Array.prototype.indexOf.call(form.elements, btn);
                form.submit();
                if (index >= 0 && form.elements[index] === btn) btn.disabled = true;
            }
        });
    });

    /* ── Toast d'événements (Futur Design) ───────────────────────── */
    function showToast(message, type, title) {
        var wrap = document.querySelector('.futur-toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'futur-toast-wrap';
            document.body.appendChild(wrap);
        }
        type = type || 'success';
        var iconMap = {
            success: 'mdi-check-circle',
            error: 'mdi-alert-circle',
            danger: 'mdi-alert-circle',
            warning: 'mdi-alert',
            info: 'mdi-information'
        };
        var icons = {
            success: 'mdi-check',
            error: 'mdi-close',
            danger: 'mdi-close',
            warning: 'mdi-alert',
            info: 'mdi-information'
        };
        var el = document.createElement('div');
        el.className = 'futur-toast ' + type;
        el.setAttribute('role', 'alert');
        el.innerHTML =
            '<div class="futur-toast-icon"><i class="mdi ' + iconMap[type] + '"></i></div>' +
            '<div class="futur-toast-content">' +
            (title ? '<div class="futur-toast-title">' + title + '</div>' : '') +
            '<div class="futur-toast-message">' + (message || '') + '</div>' +
            '</div>' +
            '<button type="button" class="futur-toast-close" aria-label="Fermer"><i class="mdi mdi-close"></i></button>' +
            '<div class="futur-toast-progress"></div>';
        wrap.appendChild(el);
        var duration = 5000;
        var progress = el.querySelector('.futur-toast-progress');
        progress.style.animationDuration = duration + 'ms';
        var closeBtn = el.querySelector('.futur-toast-close');
        closeBtn.addEventListener('click', function () { removeToast(el); });
        var timer = window.setTimeout(function () { removeToast(el); }, duration);
        function removeToast(toast) {
            clearTimeout(timer);
            toast.classList.add('removing');
            toast.addEventListener('animationend', function () { toast.remove(); });
        }
    }

    window.showToast = showToast;
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

    /* ── Validation progressive & scroll-to-error (Phase 5 §2) ─── */
    function scrollToFirstInvalid(form) {
        var invalid = form ? form.querySelector('.is-invalid, .form-error, .invalid-feedback, [aria-invalid="true"]') : null;
        if (!invalid) return;
        var field = invalid.closest('input, select, textarea') || invalid;
        if (field && typeof field.focus === 'function') {
            try { field.focus({ preventScroll: true }); } catch (e) { field.focus(); }
        }
        if (invalid.scrollIntoView) invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Après une redirection avec erreurs serveur : scroll + focus sur le premier champ invalide.
        document.querySelectorAll('form').forEach(function (form) {
            if (form.querySelector('.is-invalid')) scrollToFirstInvalid(form);
        });
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM' || form.noValidate) return;

        var firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) {
            e.preventDefault();
            var control = firstInvalid.closest('.form-control, .form-select, input, select, textarea') || firstInvalid;
            control.classList.add('is-invalid');
            scrollToFirstInvalid(form);
            return;
        }

        // Nettoyage des états d'erreur à la soumission valide.
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    });

    document.addEventListener('input', function (e) {
        var field = e.target.closest('input, select, textarea');
        if (!field) return;
        field.classList.toggle('is-invalid', !field.checkValidity() && field.value !== '');
    });

    document.addEventListener('blur', function (e) {
        var field = e.target.closest('input, select, textarea');
        if (!field) return;
        if (!field.checkValidity()) {
            field.classList.add('is-invalid');
            scrollToFirstInvalid(field.form);
        } else {
            field.classList.remove('is-invalid');
        }
    }, true);

    /* ── Submit loading state (disabled + spinner) ───────────────── */
    function initFormSubmitLoading() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            // Ignore forms with data-no-loading or already submitting
            if (form.hasAttribute('data-no-loading') || form.dataset.submitting === 'true') return;
            // Ignore GET forms (search, filters)
            if (form.method.toLowerCase() === 'get') return;

            var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!submitBtn) return;

            // Prevent double submit
            if (submitBtn.disabled) { e.preventDefault(); return; }

            // Disable + add spinner
            submitBtn.disabled = true;
            form.dataset.submitting = 'true';
            var originalHtml = submitBtn.innerHTML;
            submitBtn.dataset.originalHtml = originalHtml;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
                (submitBtn.hasAttribute('data-loading-text') ? submitBtn.getAttribute('data-loading-text') : 'Enregistrement...');

            // Re-enable on page unload (back button, etc.)
            window.addEventListener('pageshow', function onPageshow(event) {
                if (event.persisted) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                    delete form.dataset.submitting;
                    window.removeEventListener('pageshow', onPageshow);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initFormSubmitLoading);
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy]');
        if (!btn) return;
        var targetId = btn.getAttribute('data-copy-target');
        var source = targetId ? document.getElementById(targetId) : btn.previousElementSibling;
        if (!source) return;
        try {
            source.select();
            document.execCommand('copy');
            whToast(t('common.copied', 'Copié !'), 'success');
        } catch (err) { /* silencieux */ }
    });

    /* ── PWA: Install prompt banner ── */
    var deferredPrompt = null;
    var installBanner = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        showInstallBanner();
    });

    function showInstallBanner() {
        if (document.getElementById('pwa-install-banner')) return;
        if (localStorage.getItem('pwa_install_dismissed') === '1') return;

        var banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.innerHTML =
            '<div style="position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:12px 16px;background:linear-gradient(135deg,#0F2B22,#1A4D3E);border-top:2px solid #D4AF37;display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-family:system-ui,sans-serif;box-shadow:0 -4px 20px rgba(0,0,0,.3)">' +
            '<div style="flex:1;min-width:200px;color:#F6EFDD">' +
            '<div style="font-weight:700;font-size:.9rem">📱 Installer حومتي ايفانت</div>' +
            '<div style="font-size:.78rem;color:#C9D6CE;margin-top:2px">Accès rapide • Notifications • Mode hors ligne</div>' +
            '</div>' +
            '<button id="pwa-install-btn" style="padding:8px 18px;border-radius:8px;border:1px solid rgba(212,175,55,.6);background:#D4AF37;color:#0F2B22;font-weight:700;cursor:pointer;font-size:.85rem;white-space:nowrap">Installer</button>' +
            '<button id="pwa-install-dismiss" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:transparent;color:#C9D6CE;cursor:pointer;font-size:.85rem">✕</button>' +
            '</div>';
        document.body.appendChild(banner);

        document.getElementById('pwa-install-btn').addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (res) {
                if (res.outcome === 'accepted') {
                    banner.remove();
                }
                deferredPrompt = null;
            });
        });
        document.getElementById('pwa-install-dismiss').addEventListener('click', function () {
            banner.remove();
            localStorage.setItem('pwa_install_dismissed', '1');
        });
    }

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        var b = document.getElementById('pwa-install-banner');
        if (b) b.remove();
    });

    /* ── PWA: Update available banner ── */
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            showUpdateBanner();
        });
    }

    function showUpdateBanner() {
        if (document.getElementById('pwa-update-banner')) return;
        var banner = document.createElement('div');
        banner.id = 'pwa-update-banner';
        banner.innerHTML =
            '<div style="position:fixed;top:0;left:0;right:0;z-index:9999;padding:10px 16px;background:#D4AF37;color:#0F2B22;display:flex;align-items:center;gap:10px;font-family:system-ui,sans-serif;font-weight:600;font-size:.85rem;box-shadow:0 4px 12px rgba(0,0,0,.2)">' +
            '<span style="flex:1">🔄 Une mise à jour est disponible</span>' +
            '<button onclick="location.reload()" style="padding:6px 14px;border-radius:6px;border:1px solid #0F2B22;background:#0F2B22;color:#fff;cursor:pointer;font-weight:700;font-size:.82rem">Actualiser</button>' +
            '<button onclick="this.parentElement.remove()" style="padding:6px 10px;border:none;background:transparent;cursor:pointer;font-size:1rem">✕</button>' +
            '</div>';
        document.body.appendChild(banner);
    }

    /* ── PWA: Handle background sync messages from SW ── */
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', function (event) {
            if (event.data && event.data.type === 'SYNC_SCANS') {
                syncOfflineScans();
            }
        });
    }

    function syncOfflineScans() {
        try {
            var queue = JSON.parse(localStorage.getItem('wh_scan_queue') || '[]');
            if (!queue.length) return;
            var sent = 0;
            queue.forEach(function (item) {
                fetch(item.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(item.data)
                }).then(function () { sent++; })
                  .catch(function () {});
            });
            localStorage.setItem('wh_scan_queue', '[]');
            if (sent > 0) {
                var ev = new CustomEvent('wh:synced', { detail: { count: sent } });
                window.dispatchEvent(ev);
            }
        } catch (e) { /* silencieux */ }
    }

    /* ── PWA: Sync offline comments ── */
    function syncOfflineComments() {
        try {
            var queue = JSON.parse(localStorage.getItem('wh_comment_queue') || '[]');
            if (!queue.length) return;
            var sent = 0;
            queue.forEach(function (item) {
                fetch(item.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: item.body
                }).then(function () { sent++; })
                  .catch(function () {});
            });
            localStorage.setItem('wh_comment_queue', '[]');
            if (sent > 0) {
                window.whToast && window.whToast(t('common.comments_synced', 'Commentaires synchronisés'), 'success');
            }
        } catch (e) { /* silencieux */ }
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', function (event) {
            if (event.data && event.data.type === 'SYNC_COMMENTS') {
                syncOfflineComments();
            }
        });
    }

    /* ── PWA: Periodic background sync registration ── */
    if ('serviceWorker' in navigator && 'periodicSync' in (navigator.serviceWorker.controller ? {} : {})) {
        navigator.serviceWorker.ready.then(function (reg) {
            if ('periodicSync' in reg) {
                reg.periodicSync.register('sync-scans', { minInterval: 60 * 60 * 1000 }).catch(function () {});
            }
        });
    }

    /* ── Scroll-to-top button ─────────────────────────────────── */
    (function () {
        var btn = document.getElementById('scrollTopBtn');
        if (!btn) return;
        var ticking = false;
        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    btn.classList.toggle('visible', window.scrollY > 300);
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();

    /* ── Sidebar swipe gesture (mobile) ───────────────────────── */
    (function () {
        var sidebar = document.getElementById('whSidebar');
        var backdrop = document.getElementById('whSidebarBackdrop');
        if (!sidebar || !backdrop) return;
        var startX = 0;
        var currentX = 0;
        var swiping = false;
        var isRTL = document.documentElement.dir === 'rtl';

        sidebar.addEventListener('touchstart', function (e) {
            if (!sidebar.classList.contains('show')) return;
            startX = e.touches[0].clientX;
            swiping = true;
        }, { passive: true });

        sidebar.addEventListener('touchmove', function (e) {
            if (!swiping) return;
            currentX = e.touches[0].clientX;
            var diff = currentX - startX;
            if (isRTL) diff = -diff;
            if (diff < 0) {
                sidebar.style.transform = 'translateX(' + Math.max(diff, -268) + 'px)';
            }
        }, { passive: true });

        sidebar.addEventListener('touchend', function () {
            if (!swiping) return;
            swiping = false;
            var diff = currentX - startX;
            if (isRTL) diff = -diff;
            sidebar.style.transform = '';
            if (diff < -60) {
                sidebar.classList.remove('show');
                if (backdrop) backdrop.classList.remove('show');
            }
        }, { passive: true });
    })();

    /* ── Active nav link smooth scroll into view ──────────────── */
    (function () {
        var active = document.querySelector('.wh-nav .nav-link.active');
        if (active) {
            active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    })();

    /* ── Toast auto-dismiss ───────────────────────────────────── */
    window.showToast = window.showToast || function (message, type) {
        var wrap = document.querySelector('.wh-toast-wrap');
        if (!wrap) return;
        var icon = type === 'success' ? 'mdi-check-circle' : type === 'error' ? 'mdi-alert-circle' : 'mdi-information';
        var bg = type === 'success' ? '#198754' : type === 'error' ? '#dc3545' : '#0B5ED7';
        var toast = document.createElement('div');
        toast.className = 'wh-toast';
        toast.style.cssText = 'display:flex;align-items:center;gap:.65rem;padding:.75rem 1.1rem;background:' + bg + ';color:#fff;border-radius:var(--wh-radius);box-shadow:0 6px 20px rgba(0,0,0,.2);font-size:.88rem;font-weight:500;min-width:260px;max-width:400px;cursor:pointer;margin-top:.5rem;';
        toast.innerHTML = '<i class="mdi ' + icon + '" style="font-size:1.2rem"></i><span>' + message + '</span>';
        toast.addEventListener('click', function () { toast.remove(); });
        wrap.appendChild(toast);
        setTimeout(function () {
            toast.style.animation = 'whToastOut .3s ease forwards';
            setTimeout(function () { toast.remove(); }, 300);
        }, 4000);
    };

    /* ── IA MAX — CountUp + Skeletons + Keyboard ───────────────────── */
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.wh-kpi-value, .wh-kpi-card .wh-kpi-value, .wh-stat-val').forEach(function(el){
            var raw=(el.textContent||'').replace(/[^\d]/g,''); var target=parseInt(raw,10);
            if(isNaN(target) || target===0) return;
            var cur=0, step=Math.max(1, Math.ceil(target/30));
            el.textContent='0';
            var iv=setInterval(function(){ cur+=step; if(cur>=target){ cur=target; clearInterval(iv);} el.textContent=cur.toLocaleString('fr-DZ'); }, 20);
        });
        document.addEventListener('keydown', function(e){
            if(e.target && /INPUT|TEXTAREA|SELECT/.test(e.target.tagName)) return;
            if(e.key==='?' ){ e.preventDefault(); showToast('Raccourcis: Ctrl+K palette · n nouveau · / recherche · ? aide','info'); }
            if(e.key==='n' ){ var a=document.querySelector('a[href*="evenements/create"]'); if(a){ e.preventDefault(); location.href=a.href; } }
            if(e.key==='/' ){ var s=document.querySelector('.wh-search input'); if(s){ e.preventDefault(); s.focus(); } }
        });
    });
})();
