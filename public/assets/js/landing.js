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
        }

        toggle.addEventListener('click', function () {
            var open = document.body.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
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
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        theme();
        mobileNav();
        stickyHeader();
        smoothAnchors();
        reveal();
        counters();
    });
})();
