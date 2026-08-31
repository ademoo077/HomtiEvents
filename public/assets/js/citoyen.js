(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isArPage = document.documentElement.getAttribute('dir') === 'rtl';

    /* ═══ Haptics ═══ */
    function haptic(pattern) {
        try { if ('vibrate' in navigator) navigator.vibrate(pattern || 10); } catch (e) {}
    }

    /* ═══ Toast notification ═══ */
    function showToast(msg, type, duration) {
        var existing = document.querySelector('.wh-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.className = 'wh-toast' + (type === 'error' ? ' wh-toast-error' : type === 'success' ? ' wh-toast-success' : '');
        toast.innerHTML = '<span class="wh-toast-msg">' + msg + '</span>';
        document.body.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('show'); });
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 400);
        }, duration || 3000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        /* ═══ Service Worker ═══ */
        if ('serviceWorker' in navigator) {
            var swReg;
            navigator.serviceWorker.register('/sw.js').then(function (reg) {
                swReg = reg;
                reg.addEventListener('updatefound', function () {
                    var newWorker = reg.installing;
                    if (!newWorker) return;
                    newWorker.addEventListener('statechange', function () {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            var i18n = window.WH_I18N || {};
                            var msg = i18n['pwa_update_available'] || (isArPage ? 'تحديث جديد متاح. أعد التحميل.' : 'Mise à jour disponible. Rechargez la page.');
                            var reload = i18n['pwa_reload'] || (isArPage ? 'تحديث' : 'Recharger');
                            showToast(msg + ' <button onclick="location.reload()" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:6px;padding:3px 10px;margin-left:8px;cursor:pointer;font-weight:600">' + reload + '</button>', 'success', 8000);
                            haptic([50, 30, 50]);
                        }
                    });
                });
            }).catch(function () {});
        }

        /* ═══ PWA — Installation ═══ */
        var deferredPrompt = null;
        var pwaSheet = null;
        var installBanner = document.getElementById('pwaInstallBanner');
        var isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
            || window.navigator.standalone === true;
        var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent)
            || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var COOLDOWN_1 = 24 * 3600 * 1000;       /* rappel après 24 h */
        var COOLDOWN_MAX = 7 * 24 * 3600 * 1000; /* ensuite : 7 jours */

        /* Popup affichée une seule fois par session (sessionStorage), sans
           dépasser le délai de rappel mémorisé (localStorage). */
        function inviteAllowed() {
            if (isStandalone) return false;
            try {
                if (sessionStorage.getItem('wh_pwa_seen') === '1') return false;
                return Date.now() > parseInt(localStorage.getItem('wh_pwa_cooldown') || '0', 10);
            } catch (e) { return true; }
        }
        function markSeen() {
            try { sessionStorage.setItem('wh_pwa_seen', '1'); } catch (e) {}
        }
        function snoozeInvite(ms) {
            try { localStorage.setItem('wh_pwa_cooldown', String(Date.now() + ms)); } catch (e) {}
        }
        function hideBanner() {
            if (!installBanner) return;
            installBanner.classList.remove('show');
        }

        function showInstallBanner() {
            /* Popup centrée — s'affiche sur toutes les pages, y compris les
               formulaires (profil), sans gêner les champs. */
            if (isStandalone || !inviteAllowed() || document.hidden) return;
            markSeen();
            haptic(8);
            openInstallSheet();
        }

        /* Fenêtre d'installation unique (guide iOS pas-à-pas OU QR pour Android
           sans prompt natif) — une seule expérience cohérente. */
        function openInstallSheet() {
            var cardBody =
                '<div class="citoyen-pwa-sheet-card" role="dialog" aria-modal="true">' +
                    '<button type="button" class="citoyen-pwa-pop-close" aria-label="' + (isArPage ? 'إغلاق' : 'Fermer') + '"><i class="mdi mdi-close"></i></button>' +
                    '<div class="wh-pwa-sheet-app">' +
                        '<img src="/assets/img/icon-192.png" alt="" class="wh-pwa-sheet-app-icon">' +
                        '<div><strong>' + (isArPage ? 'تطبيق الولاية' : 'L\u0027application Wilaya') + '</strong>' +
                        '<span>' + (isArPage ? 'أضِفه إلى شاشتك الرئيسية' : 'Ajoutez-la à votre écran d\u2019accueil') + '</span></div>' +
                    '</div>';
            var btnId = 'pwaSheetPrimary';
            if (isIOS) {
                cardBody +=
                    '<div class="citoyen-install-steps">' +
                        '<div class="citoyen-install-step">' +
                            '<span class="citoyen-install-step-icon"><i class="mdi mdi-share-variant"></i></span>' +
                            '<span class="citoyen-install-step-text"><strong>' + (isArPage ? 'اضغط أيقونة المشاركة' : 'Touchez le bouton Partager') + '</strong>' + (isArPage ? 'في أسفل شريط سفاري' : 'en bas de la barre Safari') + '</span>' +
                        '</div>' +
                        '<div class="citoyen-install-step">' +
                            '<span class="citoyen-install-step-icon"><i class="mdi mdi-plus-box-outline"></i></span>' +
                            '<span class="citoyen-install-step-text"><strong>' + (isArPage ? 'اختر « إلى الشاشة الرئيسية »' : 'Choisissez « Sur l\u2019écran d\u2019accueil »') + '</strong>' + (isArPage ? 'ثم « إضافة » للتثبيت' : 'puis « Ajouter » pour confirmer') + '</span>' +
                        '</div>' +
                        '<div class="citoyen-install-step">' +
                            '<span class="citoyen-install-step-icon"><i class="mdi mdi-offline"></i></span>' +
                            '<span class="citoyen-install-step-text"><strong>' + (isArPage ? 'افتحه كتطبيق مستقل' : 'Ouvrez-la comme une vraie app') + '</strong>' + (isArPage ? 'بدون إنترنت ومع إشعارات' : 'plein écran, avec notifications') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="citoyen-btn citoyen-btn-primary citoyen-btn-block" id="' + btnId + '"><i class="mdi mdi-check"></i> ' + (isArPage ? 'فهمت، شكراً' : 'J\u2019ai compris') + '</button>';
            } else {
                cardBody +=
                    '<p class="wh-pwa-sheet-qr-hint">' + (isArPage ? 'امسح الكود لفتح التطبيق على هاتف آخر' : 'Scannez ce code pour ouvrir l\u2019app sur un autre téléphone') + '</p>' +
                    '<div class="citoyen-pwa-qr wh-pwa-sheet-qr" id="pwaQrBox" style="background:#fff">' +
                        '<i class="mdi mdi-qrcode" style="font-size:3rem;color:var(--cit-primary)"></i>' +
                    '</div>' +
                    '<div style="display:flex;gap:8px">' +
                        '<button id="' + btnId + '" class="citoyen-btn citoyen-btn-primary" style="flex:1"><i class="mdi mdi-download"></i> ' + (isArPage ? 'تثبيت' : 'Installer') + '</button>' +
                        '<button id="pwaSheetClose" class="citoyen-btn citoyen-btn-outline" style="flex:1">' + (isArPage ? 'إغلاق' : 'Fermer') + '</button>' +
                    '</div>';
            }
            cardBody += '</div>';

            if (pwaSheet) { pwaSheet.innerHTML = cardBody; }
            else {
                pwaSheet = document.createElement('div');
                pwaSheet.className = 'citoyen-pwa-sheet';
                pwaSheet.innerHTML = cardBody;
                document.body.appendChild(pwaSheet);
            }
            requestAnimationFrame(function () { pwaSheet.classList.add('show'); haptic([10, 30, 10]); });

            function closeSheet() {
                pwaSheet.classList.remove('show');
            }
            pwaSheet.onclick = function (e) {
                if (e.target === pwaSheet) { closeSheet(); markSeen(); }
            };
            var popClose = pwaSheet.querySelector('.citoyen-pwa-pop-close');
            if (popClose) {
                popClose.addEventListener('click', function () { closeSheet(); markSeen(); });
            }
            var closeBtn = pwaSheet.querySelector('#pwaSheetClose');
            if (closeBtn) closeBtn.addEventListener('click', closeSheet);
            var primary = pwaSheet.querySelector('#' + btnId);
            if (primary) {
                primary.addEventListener('click', function () {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then(function (r) {
                            if (r.outcome === 'accepted') {
                                var i18n = window.WH_I18N || {};
                                showToast(i18n['pwa_installed'] || (isArPage ? 'تم تثبيت التطبيق!' : 'Application installée !'), 'success');
                                closeSheet();
                            } else {
                                snoozeInvite(COOLDOWN_1);
                            }
                            deferredPrompt = null;
                        });
                    } else if (!isIOS) {
                        /* Android sans prompt natif : QR à scanner / rien d'autre à faire */
                        closeSheet();
                        snoozeInvite(COOLDOWN_1);
                    } else {
                        closeSheet();
                    }
                    markSeen();
                });
            }
            if (!isIOS) {
                var qrBox = pwaSheet.querySelector('#pwaQrBox');
                if (qrBox) {
                    var u = location.origin + '/citoyen';
                    qrBox.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' + encodeURIComponent(u) + '" alt="QR" style="width:140px;height:140px;border-radius:12px">';
                }
            }
            return pwaSheet;
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            showInstallBanner();
        });

        /* Safari/iOS & navigateurs sans beforeinstallprompt → invitation différée.
           Affichage à un moment opportun : après un court délai ou au premier
           défilement réellement utile. */
        function armInstallBanner() {
            if (isStandalone || !inviteAllowed()) return;
            var shown = false;
            function tryShow() {
                if (shown || !inviteAllowed()) return;
                shown = true;
                showInstallBanner();
            }
            setTimeout(function () {
                if (!shown) tryShow();
            }, 6000);
            var idle = null;
            window.addEventListener('scroll', function onScroll() {
                if (window.scrollY < 120) return;
                tryShow();
                if (idle) clearTimeout(idle);
                idle = setTimeout(function () {
                    window.removeEventListener('scroll', onScroll);
                }, 4000);
            }, { passive: true });
        }
        if (!isStandalone) armInstallBanner();
        /* Après un beforeinstallprompt non traité, la bannière a déjà été appelée. */

        window.addEventListener('appinstalled', function () {
            hideBanner();
            if (pwaSheet) pwaSheet.classList.remove('show');
            snoozeInvite(COOLDOWN_MAX);
            markSeen();
            deferredPrompt = null;
        });

        /* Appui long sur le bouton scan (nav du bas) → fenêtre d'installation */
        var scanFab = document.querySelector('.citoyen-scan-fab');
        if (scanFab) {
            var pressTimer;
            scanFab.addEventListener('touchstart', function () { pressTimer = setTimeout(function () { openInstallSheet(); }, 600); }, { passive: true });
            scanFab.addEventListener('touchend', function () { clearTimeout(pressTimer); });
        }

        /* ═══ Online/Offline banner ═══ */
        var offlineBanner = document.getElementById('pwaOfflineBanner');
        function updateOnlineStatus() {
            if (!offlineBanner) return;
            if (navigator.onLine) {
                offlineBanner.style.display = 'none';
                var i18n = window.WH_I18N || {};
                showToast(i18n['back_online'] || (isArPage ? 'عاد الاتصال' : 'De retour en ligne'), 'success', 2500);
                haptic([10, 30, 10]);
            } else {
                offlineBanner.style.display = 'flex';
                haptic([50, 50, 50]);
            }
        }
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        if (!navigator.onLine && offlineBanner) offlineBanner.style.display = 'flex';

        /* ═══ Pull-to-refresh ═══ */
        var pullStartY = 0;
        var pullIndicator = null;
        var isPulling = false;
        function createPullIndicator() {
            if (pullIndicator) return;
            pullIndicator = document.createElement('div');
            pullIndicator.className = 'wh-pull-indicator';
            pullIndicator.innerHTML = '<div class="wh-pull-spinner"></div>';
            document.body.appendChild(pullIndicator);
        }

        document.addEventListener('touchstart', function (e) {
            if (window.scrollY > 0) return;
            pullStartY = e.touches[0].clientY;
            isPulling = false;
        }, { passive: true });

        document.addEventListener('touchmove', function (e) {
            if (pullStartY === 0 || window.scrollY > 0) return;
            var dy = e.touches[0].clientY - pullStartY;
            if (dy > 60 && !isPulling) {
                isPulling = true;
                createPullIndicator();
                haptic(15);
                if (pullIndicator) pullIndicator.classList.add('ready');
            }
        }, { passive: true });

        document.addEventListener('touchend', function () {
            if (isPulling) {
                isPulling = false;
                if (pullIndicator) {
                    pullIndicator.classList.add('refreshing');
                    setTimeout(function () { location.reload(); }, 600);
                }
            }
            pullStartY = 0;
        }, { passive: true });

        /* ═══ Event search ═══ */
        var eventSearch = document.getElementById('eventSearch');
        var upcomingList = document.getElementById('upcomingList');

        function esc(value) {
            var d = document.createElement('div');
            d.textContent = value == null ? '' : String(value);
            return d.innerHTML;
        }

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

        function monthShort(dateStr) {
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return '';
            return d.toLocaleDateString(isArPage ? 'ar' : 'fr-FR', { month: 'short' });
        }

        /* ═══ Nearby events ═══ */
        var btnNearby = document.getElementById('btnNearby');
        var nearbyList = document.getElementById('nearbyList');
        var nearbyEmpty = document.getElementById('nearbyEmpty');

        if (btnNearby && nearbyList && nearbyEmpty) {
            btnNearby.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    nearbyEmpty.textContent = window.WH_I18N && window.WH_I18N['citoyen.nearby_unavailable'] || 'Géolocalisation non disponible.';
                    nearbyEmpty.style.display = '';
                    return;
                }

                btnNearby.disabled = true;
                btnNearby.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> ' + (window.WH_I18N && window.WH_I18N['citoyen.locating'] || 'Localisation…');
                haptic(10);

                navigator.geolocation.getCurrentPosition(function (pos) {
                    var lat = pos.coords.latitude;
                    var lon = pos.coords.longitude;

                    fetch('/api/evenements/nearby?lat=' + lat + '&lon=' + lon + '&rayon=20')
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            btnNearby.disabled = false;
                            btnNearby.innerHTML = '<i class="mdi mdi-crosshairs-gps"></i> ' + (window.WH_I18N && window.WH_I18N['citoyen.find_nearby'] || 'À proximité');

                            if (!data.success || !data.data || data.data.length === 0) {
                                nearbyEmpty.textContent = window.WH_I18N && window.WH_I18N['citoyen.no_nearby'] || 'Aucun événement à proximité.';
                                nearbyEmpty.style.display = '';
                                return;
                            }

                            haptic([10, 20, 10]);
                            nearbyEmpty.style.display = 'none';
                            nearbyList.innerHTML = '';
                            data.data.forEach(function (ev) {
                                var card = document.createElement('a');
                                card.className = 'citoyen-card';
                                card.href = '/citoyen/evenement/' + (ev.id || '');
                                var day = new Date(ev.date_evenement).getDate();
                                var km = ev.distance_km ? Number(ev.distance_km).toFixed(1) + ' km' : '';
                                card.innerHTML =
                                    '<div class="citoyen-card-date">' +
                                        '<span class="citoyen-card-day">' + day + '</span>' +
                                        '<span class="citoyen-card-month">' + esc(monthShort(ev.date_evenement)) + '</span>' +
                                    '</div>' +
                                    '<div class="citoyen-card-body">' +
                                        '<h3 class="citoyen-card-title">' + esc(ev.adresse || '') + '</h3>' +
                                        '<p class="citoyen-card-meta"><i class="mdi mdi-map-marker-outline"></i> ' + esc(ev.commune_nom || '') + ' · ' + esc(km) + '</p>' +
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

        /* ═══ Scroll reveal ═══ */
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

        /* ═══ data-confirm ═══ */
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.matches || !form.matches('[data-confirm]')) return;
            var msg = form.getAttribute('data-confirm') || 'Confirmer cette action ?';
            if (!window.confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        /* ═══ Notification preference toggles (AJAX) ═══ */
        document.querySelectorAll('.wh-toggle input[data-pref], select[data-pref]').forEach(function (el) {
            el.addEventListener('change', function () {
                var key   = this.getAttribute('data-pref');
                var url   = this.getAttribute('data-url');
                var value = this.type === 'checkbox' ? (this.checked ? 1 : 0) : this.value;
                var token = document.querySelector('meta[name="csrf-token"]');
                var csrf  = token ? token.getAttribute('content') : '';
                haptic(10);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: encodeURIComponent(key) + '=' + encodeURIComponent(value) + '&_token=' + encodeURIComponent(csrf)
                }).then(function (r) { return r.json(); })
                  .then(function (d) {
                      if (d.ok && key === 'langue' && d.langue !== undefined && d.langue !== '') {
                          window.location.reload();
                      }
                  }).catch(function () {});
            });
        });

        /* ═══ Card tap feedback ═══ */
        document.addEventListener('touchstart', function (e) {
            var card = e.target.closest('.citoyen-card, .citoyen-album-card, .citoyen-nav-item, .citoyen-scan-fab');
            if (card) haptic(5);
        }, { passive: true });

        /* ═══ Notification badge (citoyen) ═══ */
        (function(){
            var bell = document.querySelector('a[href*="citoyen/notifications"]');
            if(!bell) return;
            function updateBadge(){
                fetch('/api/notifications/unread', {headers:{'Accept':'application/json'}}).then(function(r){return r.json()}).then(function(d){
                    var c = d && d.count ? parseInt(d.count,10) : 0;
                    var existing = bell.querySelector('.badge-dot');
                    if(c>0){
                        if(!existing){ var dot=document.createElement('span'); dot.className='badge-dot'; bell.style.position='relative'; bell.appendChild(dot); }
                        bell.setAttribute('aria-label', bell.getAttribute('aria-label') + ' ('+c+')');
                    } else if(existing){ existing.remove(); }
                }).catch(function(){});
            }
            updateBadge();
            setInterval(updateBadge, 30000);
            document.addEventListener('visibilitychange', function(){ if(!document.hidden) updateBadge(); });
        })();

        /* ═══ Smooth page transitions ═══ */
        if (!prefersReduced && document.startViewTransition) {
            document.querySelectorAll('a[href]').forEach(function (a) {
                if (a.target || a.hasAttribute('download') || a.href.startsWith('javascript:') || a.href.includes('#')) return;
                a.addEventListener('click', function (e) {
                    if (e.defaultPrevented || e.metaKey || e.ctrlKey) return;
                    var href = this.getAttribute('href');
                    if (!href || href.startsWith('http') && href.indexOf(location.hostname) === -1) return;
                    e.preventDefault();
                    document.startViewTransition(function () {
                        window.location.href = href;
                    });
                });
            });
        }
    });
})();
