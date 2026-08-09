(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ════════════ THÈME (clair / sombre) ════════════ */
    function theme() {
        var root = document.documentElement;
        var toggle = document.querySelector('[data-theme-toggle]');
        var icon = document.querySelector('[data-theme-icon]');

        function apply(themeName) {
            root.setAttribute('data-theme', themeName);
            try { localStorage.setItem('wh-theme', themeName); } catch (e) { /* stockage indisponible */ }
            if (icon) {
                icon.className = 'mdi ' + (themeName === 'dark' ? 'mdi-weather-sunny' : 'mdi-weather-night');
            }
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                apply(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
            });
        }

        // Suit les changements de préférence système tant qu'aucun choix explicite n'est stocké.
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                var stored; try { stored = localStorage.getItem('wh-theme'); } catch (err) {}
                if (!stored) { apply(e.matches ? 'dark' : 'light'); }
            });
        }

        apply(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
    }

    document.addEventListener('DOMContentLoaded', function () {
        theme();

        var eventSearch = document.getElementById('eventSearch');
        var upcomingList = document.getElementById('upcomingList');
        var btnNearby = document.getElementById('btnNearby');
        var nearbyList = document.getElementById('nearbyList');
        var nearbyEmpty = document.getElementById('nearbyEmpty');

        if (eventSearch && upcomingList) {
            eventSearch.addEventListener('input', function () {
                var q = this.value.toLowerCase().trim();
                var cards = upcomingList.querySelectorAll('.citoyen-card');
                cards.forEach(function (card) {
                    var text = card.textContent.toLowerCase();
                    card.style.display = text.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        }

        if (btnNearby && nearbyList && nearbyEmpty) {
            btnNearby.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    nearbyEmpty.textContent = window.WH_I18N && window.WH_I18N['citoyen.nearby_unavailable'] || 'Géolocalisation non disponible.';
                    nearbyEmpty.style.display = '';
                    return;
                }

                btnNearby.disabled = true;
                btnNearby.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> ' + (window.WH_I18N && window.WH_I18N['citoyen.locating'] || 'Localisation…');

                navigator.geolocation.getCurrentPosition(function (pos) {
                    var lat = pos.coords.latitude;
                    var lon = pos.coords.longitude;
                    var radius = 20;

                    fetch('/api/evenements/nearby?lat=' + lat + '&lon=' + lon + '&rayon=' + radius)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            btnNearby.disabled = false;
                            btnNearby.innerHTML = '<i class="mdi mdi-crosshairs-gps"></i> ' + (window.WH_I18N && window.WH_I18N['citoyen.find_nearby'] || 'À proximité');

                            if (!data.success || !data.data || data.data.length === 0) {
                                nearbyEmpty.textContent = window.WH_I18N && window.WH_I18N['citoyen.no_nearby'] || 'Aucun événement à proximité.';
                                nearbyEmpty.style.display = '';
                                return;
                            }

                            nearbyEmpty.style.display = 'none';
                            nearbyList.innerHTML = '';
                            data.data.forEach(function (ev) {
                                var card = document.createElement('a');
                                card.className = 'citoyen-card';
                                card.href = '/citoyen/evenement/' + (ev.id || '');
                                card.innerHTML = '<div class="citoyen-card-date">' +
                                    '<span class="citoyen-card-day">' + new Date(ev.date_evenement).getDate() + '</span>' +
                                    '<span class="citoyen-card-month">' + new Date(ev.date_evenement).toLocaleDateString('fr-FR', {month:'short'}) + '</span>' +
                                    '</div>' +
                                    '<div class="citoyen-card-body">' +
                                    '<h3 class="citoyen-card-title">' + (ev.adresse || '') + '</h3>' +
                                    '<p class="citoyen-card-meta"><i class="mdi mdi-map-marker-outline"></i> ' + (ev.commune_nom || '') + ' · ' + (ev.distance_km ? ev.distance_km.toFixed(1) + ' km' : '') + '</p>' +
                                    '</div>';
                                nearbyList.appendChild(card);
                            });
                        })
                        .catch(function () {
                            btnNearby.disabled = false;
                            btnNearby.innerHTML = '<i class="mdi mdi-crosshairs-gps"></i> ' + (window.WH_I18N && window.WH_I18N['citoyen.find_nearby'] || 'À proximité');
                            nearbyEmpty.textContent = window.WH_I18N && window.WH_I18N['citoyen.nearby_error'] || 'Erreur de localisation.';
                            nearbyEmpty.style.display = '';
                        });
                }, function () {
                    btnNearby.disabled = false;
                    btnNearby.innerHTML = '<i class="mdi mdi-crosshairs-gps"></i> ' + (window.WH_I18N && window.WH_I18N['citoyen.find_nearby'] || 'À proximité');
                    nearbyEmpty.textContent = window.WH_I18N && window.WH_I18N['citoyen.location_denied'] || 'Accès à la localisation refusé.';
                    nearbyEmpty.style.display = '';
                });
            });
        }

        /* ── Révélation au scroll (harmonisation avec la landing) ── */
        var revealEls = document.querySelectorAll('[data-reveal]:not(.revealed)');
        if (revealEls.length > 0 && !prefersReduced && 'IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            revealEls.forEach(function (el) { io.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('revealed'); });
        }

        var onboard = document.getElementById('citoyenOnboard');
        if (onboard) {
            setTimeout(function () {
                onboard.style.opacity = '0';
                onboard.style.transition = 'opacity .3s';
                setTimeout(function () { onboard.remove(); }, 300);
            }, 4000);
        }
    });
})();
