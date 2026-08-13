/*!
 * Wilaya Harmonia — Landing Page
 * Réveil au scroll, compteurs animés, bascule de thème, navigation mobile, header sticky.
 * Chargé uniquement sur la page publique (layouts/landing.php).
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ════════════ RÉVÉLATION AU SCROLL ════════════ */
    function reveal() {
        var elements = document.querySelectorAll('[data-reveal]:not(.revealed)');
        if (elements.length === 0) {
            return;
        }

        if (prefersReduced || !('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(elements, function (el) {
                el.classList.add('revealed');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var el = entry.target;
                var delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10) || 0;
                if (delay > 0) {
                    setTimeout(function () { el.classList.add('revealed'); }, Math.min(delay, 600));
                } else {
                    el.classList.add('revealed');
                }
                observer.unobserve(el);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        Array.prototype.forEach.call(elements, function (el) { observer.observe(el); });
    }

    /* ════════════ COMPTEURS ANIMÉS (hero-stats) ════════════ */
    function counters() {
        var targets = document.querySelectorAll('[data-count]');
        if (targets.length === 0) {
            return;
        }

        function animate(el) {
            var end = parseInt(el.getAttribute('data-count') || '0', 10) || 0;
            if (prefersReduced) {
                el.textContent = end.toLocaleString();
                return;
            }
            var start = 0;
            var duration = 1200;
            var t0 = null;
            function step(ts) {
                if (t0 === null) { t0 = ts; }
                var p = Math.min((ts - t0) / duration, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(start + (end - start) * eased).toLocaleString();
                if (p < 1) { window.requestAnimationFrame(step); }
            }
            window.requestAnimationFrame(step);
        }

        if (!('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(targets, animate);
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });
        Array.prototype.forEach.call(targets, function (el) { io.observe(el); });
    }

    /* ════════════ THÈME (light only) ════════════ */
    function theme() {
        var root = document.documentElement;
        root.setAttribute('data-theme', 'light');
        try { localStorage.setItem('wh-theme', 'light'); } catch (e) { /* stockage indisponible */ }
    }

    /* ════════════ NAVIGATION MOBILE ════════════ */
    function mobileNav() {
        var toggle = document.getElementById('navToggle');
        var nav = document.getElementById('siteNav');
        if (!toggle || !nav) {
            return;
        }

        function close() {
            document.body.classList.remove('nav-open');
            toggle.setAttribute('aria-expanded', 'false');
            // Close all dropdowns
            Array.prototype.forEach.call(nav.querySelectorAll('.site-menu-item.open'), function (item) {
                item.classList.remove('open');
            });
        }

        toggle.addEventListener('click', function () {
            var open = document.body.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // Dropdown toggles on mobile
        Array.prototype.forEach.call(nav.querySelectorAll('.dropdown-toggle'), function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var item = btn.closest('.site-menu-item');
                if (item) {
                    item.classList.toggle('open');
                    btn.setAttribute('aria-expanded', item.classList.contains('open') ? 'true' : 'false');
                }
            });
        });

        // Ferme le menu après un clic sur un lien (sauf lang / thème).
        Array.prototype.forEach.call(nav.querySelectorAll('a[href^="#"], a[href*="/auth/"]'), function (link) {
            link.addEventListener('click', close);
        });

        // Touche Échap pour fermer.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                close();
            }
        });
    }

    /* ════════════ HEADER STICKY — classe "scrolled" ════════════ */
    function stickyHeader() {
        var header = document.getElementById('siteHeader');
        if (!header) {
            return;
        }
        var onScroll = function () {
            header.classList.toggle('scrolled', window.scrollY > 10);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ════════════ ANCRES LISSES (hors prefers-reduced-motion) ════════════ */
    function smoothAnchors() {
        if (prefersReduced) {
            return;
        }
        document.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var id = link.getAttribute('href');
                if (!id || id === '#') {
                    return;
                }
                var target = document.querySelector(id);
                if (!target) {
                    return;
                }
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Close mobile nav and dropdowns
                document.body.classList.remove('nav-open');
                var toggle = document.getElementById('navToggle');
                if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
                Array.prototype.forEach.call(document.querySelectorAll('.site-menu-item.open'), function (item) {
                    item.classList.remove('open');
                    var btn = item.querySelector('.dropdown-toggle');
                    if (btn) { btn.setAttribute('aria-expanded', 'false'); }
                });
            });
        });
    }

    /* ════════════ PARALLAX SOBRE — orbes & chips ════════════ */
    function parallaxOrbs() {
        var orbs = document.querySelectorAll('.hero .orb, .float-chip');
        if (orbs.length === 0 || prefersReduced) {
            return;
        }

        var onScroll = function () {
            var y = window.scrollY;
            if (y < 300) {
                orbs.forEach(function (e, i) {
                    var depth = (i % 3) + 1;
                    var ty = y * 0.1 * depth;
                    e.style.transform = 'translateY(' + ty + 'px)';
                });
            }
        };

        window.addEventListener('scroll', function () {
            if (window.scrollY < 300) { onScroll(); }
        }, { passive: true });
    }

    /* ════════════ SECTION ACTIVE NAV ════════════ */
    function sectionNav() {
        var sections = document.querySelectorAll('section[id]');
        var links = document.querySelectorAll('.site-menu a[href^="#"], .dropdown-item[href^="#"]');
        if (!sections.length || !links.length || prefersReduced) {
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var id = entry.target.getAttribute('id');
                var link = document.querySelector('.site-menu a[href="#' + id + '"], .dropdown-item[href="#' + id + '"]');
                if (!link) { return; }
                if (entry.isIntersecting) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }, { threshold: 0.4 });

        sections.forEach(function (s) { observer.observe(s); });
    }

    /* ════════════ FILTRES ACTUALITÉS & ÉVÉNEMENTS ════════════ */
    function newsFilters() {
        var grid = document.getElementById('newsGrid');
        if (!grid) return;
        var buttons = Array.prototype.slice.call(document.querySelectorAll('.news-filter-btn'));
        if (!buttons.length) return;
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.news-card'));

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                buttons.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                var filter = btn.getAttribute('data-filter');
                var visible = 0;
                cards.forEach(function (card) {
                    var show = filter === 'all' || card.getAttribute('data-type') === filter;
                    card.hidden = !show;
                    if (show) visible += 1;
                });
                var empty = grid.querySelector('.news-empty');
                if (empty) { empty.hidden = visible > 0; }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        theme();
        mobileNav();
        stickyHeader();
        smoothAnchors();
        parallaxOrbs();
        sectionNav();
        reveal();
        counters();
        lightbox();
        compareSlider();
        newsFilters();

        /* ════════════ Lightbox (galerie) ════════════ */
    function lightbox() {
        var modal = document.getElementById('landingLightbox');
        if (!modal) return;
        var img = modal.querySelector('.lb-img');
        var title = modal.querySelector('.lb-title');
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox="landing"]'));

        if (items.length === 0) return;

        var openAt = function (i) {
            if (i == null) i = 0;
            var item = items[i];
            var src = item.getAttribute('data-full') || item.href;
            var cap = item.getAttribute('data-title') || '';
            img.src = src;
            img.alt = cap;
            title.textContent = cap;
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('lb-open');
        };

        modal.setAttribute('aria-hidden', 'true');

        items.forEach(function (item, i) {
            item.addEventListener('click', function (e) {
                if (item.getAttribute('href') === '#') {
                    e.preventDefault();
                    openAt(i);
                }
            });
        });

        var close = function () {
            modal.classList.remove('lb-open');
        };

        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target === modal.querySelector('.lb-close')) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (!modal.classList.contains('lb-open')) return;
            if (e.key === 'Escape') { close(); }
        });
    }

    /* ════════════ Comparateur Avant / Après ════════════ */
    function compareSlider() {
        var roots = document.querySelectorAll('.ba-photos');
        if (roots.length === 0 || !window.PointerEvent) return;

        roots.forEach(function (root) {
            if (root.classList.contains('ba-compare')) return;

            var before = root.querySelector('.ba-photo:first-child img');
            var after = root.querySelector('.ba-photo:last-child img');
            if (!before || !after) return;

            root.classList.add('ba-compare');
            root.style.position = 'relative';

            var photos = root.querySelectorAll('.ba-photo');
            photos[0].style.position = 'absolute';
            photos[0].style.inset = '0';
            photos[0].style.width = '50%';
            photos[1].style.position = 'absolute';
            photos[1].style.inset = '0';
            photos[1].style.width = '100%';

            var handle = document.createElement('div');
            handle.className = 'ba-compare-handle';
            var line = document.createElement('span');
            line.className = 'ba-handle-line';
            handle.appendChild(line);
            root.appendChild(handle);

            var pct = 50;
            var isRtl = getComputedStyle(root).direction === 'rtl';

            var update = function (p) {
                pct = p;
                photos[0].style.width = pct + '%';
                handle.style.left = 'calc(' + pct + '% - 12px)';
            };

            handle.style.cursor = 'ew-resize';
            handle.addEventListener('pointerdown', function (e) {
                e.preventDefault();
                var onMove = function (ev) {
                    var rect = root.getBoundingClientRect();
                    var clientX = ev.clientX || (ev.touches && ev.touches[0] && ev.touches[0].clientX);
                    var raw = (clientX - rect.left) / rect.width;
                    raw = Math.max(0, Math.min(1, raw));
                    if (isRtl) { raw = 1 - raw; }
                    update(Math.round(raw * 100));
                };
                var onUp = function () {
                    document.removeEventListener('pointermove', onMove);
                    document.removeEventListener('pointerup', onUp);
                };
                document.addEventListener('pointermove', onMove);
                document.addEventListener('pointerup', onUp);
            });
        });
    }

    // Close dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.site-menu-item.has-dropdown')) {
                Array.prototype.forEach.call(document.querySelectorAll('.site-menu-item.open'), function (item) {
                    item.classList.remove('open');
                    var btn = item.querySelector('.dropdown-toggle');
                    if (btn) { btn.setAttribute('aria-expanded', 'false'); }
                });
            }
        });
    });
})();
