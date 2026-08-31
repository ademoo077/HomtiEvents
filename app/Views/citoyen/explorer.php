<?php
/** @var array $events @var array $communes @var array $anomalies @var array $filters */
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;

$isAr = I18n::direction() === 'rtl';
$filters = $filters ?? [];
$selectedCommune = (string) ($filters['commune'] ?? '');
$selectedAnomalie = (string) ($filters['anomalie'] ?? '');
$selectedDate = (string) ($filters['date'] ?? 'all');
$searchQ = (string) ($filters['q'] ?? '');

$dateOptions = [
    'all'     => $isAr ? 'الكل' : 'Tout',
    'today'   => $isAr ? 'اليوم' : "Aujourd'hui",
    'week'    => $isAr ? 'هذا الأسبوع' : 'Cette semaine',
    'upcoming'=> $isAr ? 'القادمة' : 'À venir',
    'past'    => $isAr ? 'المنقضاة' : 'Passées',
];
?>
<div class="citoyen-section">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-compass-outline" aria-hidden="true"></i> <?= e(__('citoyen.explorer_title')) ?></h2>
        <div class="citoyen-search">
            <i class="mdi mdi-magnify"></i>
            <input type="search" id="explorerSearch" value="<?= e($searchQ) ?>"
                   placeholder="<?= e(__('citoyen.search_placeholder')) ?>"
                   aria-label="<?= e(__('citoyen.search_placeholder')) ?>">
        </div>
    </div>

    <div class="explorer-filters" data-reveal>
        <div class="filter-row">
            <label class="filter-label" for="filterDate"><?= $isAr ? 'التاريخ' : 'Date' ?></label>
            <select class="filter-select" id="filterDate">
                <?php foreach ($dateOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $selectedDate === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-row">
            <label class="filter-label" for="filterCommune"><?= $isAr ? 'البلدية' : 'Commune' ?></label>
            <select class="filter-select" id="filterCommune">
                <option value=""><?= $isAr ? 'جميع البلديات' : 'Toutes les communes' ?></option>
                <?php foreach ($communes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $selectedCommune === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-row">
            <label class="filter-label" for="filterAnomalie"><?= $isAr ? 'النوع' : 'Type' ?></label>
            <select class="filter-select" id="filterAnomalie">
                <option value=""><?= $isAr ? 'جميع الأنواع' : 'Tous les types' ?></option>
                <?php foreach ($anomalies as $an): ?>
                    <option value="<?= (int) $an['id'] ?>" <?= $selectedAnomalie === (string) $an['id'] ? 'selected' : '' ?>>
                        <?= $an['icone'] ? e($an['icone']) . ' ' : '' ?><?= e($an['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="filter-btn reset-btn" id="resetFilters" type="button">
            <i class="mdi mdi-refresh"></i> <?= $isAr ? 'إعادة تعيين' : 'Réinitialiser' ?>
        </button>

        <button class="filter-btn near-btn" id="btnNearbyExplore" type="button">
            <i class="mdi mdi-crosshairs-gps"></i> <?= $isAr ? 'القريب مني' : 'À proximité' ?>
        </button>
    </div>

    <div class="explorer-view-toggle" data-reveal>
        <button class="view-btn active" data-view="list" id="viewList" type="button" aria-label="<?= $isAr ? 'قائمة' : 'Liste' ?>" aria-pressed="true">
            <i class="mdi mdi-view-list"></i> <?= $isAr ? 'قائمة' : 'Liste' ?>
        </button>
        <button class="view-btn" data-view="map" id="viewMap" type="button" aria-label="<?= $isAr ? 'خريطة' : 'Carte' ?>" aria-pressed="false">
            <i class="mdi mdi-map-variant"></i> <?= $isAr ? 'خريطة' : 'Carte' ?>
        </button>
        <span class="explorer-results" id="explorerResults">
            <i class="mdi mdi-tag-outline" aria-hidden="true"></i>
            <strong id="explorerCount" aria-live="polite"><?= (int) count($events ?? []) ?></strong>
        </span>
    </div>

    <div class="explorer-content">
        <!-- ── Shimmer de chargement ── -->
        <div class="explorer-list shimmer-list" id="explorerShimmer" style="display:none;">
            <div class="shimmer-card"></div>
            <div class="shimmer-card"></div>
            <div class="shimmer-card"></div>
            <div class="shimmer-card"></div>
        </div>

        <div class="explorer-list" id="explorerList"></div>
        <div class="explorer-empty" id="explorerEmpty" style="display:none;" role="status">
            <i class="mdi mdi-calendar-remove-outline"></i>
            <p><?= $isAr ? 'لا توجد أحداث مطابقة' : 'Aucun événement ne correspond à vos critères' ?></p>
        </div>

        <div class="explorer-map-container" id="explorerMapContainer" style="display:none;">
            <div id="explorerMap" style="width:100%;height:100%;border-radius:16px;"></div>
        </div>
    </div>
</div>

<style>
.citoyen-card-wrap{position:relative;display:flex;align-items:center;margin-bottom:.6rem;background:var(--cit-card-bg,#fff);border-radius:var(--cit-radius-sm,12px);box-shadow:var(--cit-shadow,0 1px 3px rgba(0,0,0,.08));border:1px solid transparent;transition:transform .15s,box-shadow .15s}
.citoyen-card-wrap:hover{transform:translateY(-1px);box-shadow:var(--cit-shadow-lg,0 6px 20px rgba(0,0,0,.1));border-color:var(--cit-primary-light,#1A4D3E)}
.citoyen-card-wrap .citoyen-card{flex:1;min-width:0;display:flex;gap:.85rem;align-items:center;border:none;box-shadow:none;background:transparent;border-radius:0;transform:none}
.citoyen-card-wrap .citoyen-card:hover{transform:none;box-shadow:none}
.citoyen-fav-btn{flex:0 0 auto;align-self:center;width:38px;height:38px;margin-inline-end:1rem;border-radius:50%;border:1px solid #EDE7DA;background:#fff;color:#9CA3AF;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s;font-size:1.1rem}
.citoyen-fav-btn:hover{border-color:#D4AF37;color:#D4AF37}
.citoyen-fav-btn.active{background:#D4AF37;color:#fff;border-color:#D4AF37}
</style>

<script>
(function () {
    'use strict';

    var isAr = <?= $isAr ? 'true' : 'false' ?>;
    var events = <?= json_encode($events ?? []) ?>;
    var currentFilters = {
        date: <?= json_encode($selectedDate) ?>,
        commune: <?= json_encode($selectedCommune) ?>,
        anomalie: <?= json_encode($selectedAnomalie) ?>,
        q: <?= json_encode($searchQ) ?>,
        lat: null,
        lon: null
    };

    var listEl = document.getElementById('explorerList');
    var emptyEl = document.getElementById('explorerEmpty');
    var shimmerEl = document.getElementById('explorerShimmer');
    var viewListBtn = document.getElementById('viewList');
    var viewMapBtn = document.getElementById('viewMap');
    var mapContainer = document.getElementById('explorerMapContainer');
    var explorerMap = null;
    var markerLayer = null;
    var nearbyActive = false;
    var currentView = 'list';

    var i18n = {
        participants: isAr ? 'مشارك' : 'participants',
        commune: isAr ? 'بلدية' : 'Commune',
        participants_count: isAr ? 'مشارك' : 'participants',
        see_event: isAr ? 'عرض الحدث' : "Voir l'événement",
        scan: isAr ? 'امسح' : 'Scanner',
        geolocating: isAr ? 'جارٍ التحديد…' : 'Localisation…',
        location_denied: isAr ? 'تم رفض الوصول إلى الموقع.' : 'Accès à la localisation refusé.',
        load_error: isAr ? 'حدث خطأ أثناء التحميل.' : 'Erreur lors du chargement.',
        near_me: isAr ? 'القريب مني' : 'À proximité',
        statut: {
            'EN_ATTENTE': isAr ? 'قيد الانتظار' : 'En attente',
            'VALIDÉ': isAr ? 'مقبول' : 'Validé',
            'PROGRAMME': isAr ? 'مبرمج' : 'Programmé',
            'QR_GENERE': isAr ? 'تم توليد الرمز' : 'QR généré',
            'EN_COURS': isAr ? 'جاري' : 'En cours',
            'TERMINE': isAr ? 'منتهي' : 'Terminé',
            'REFUSE': isAr ? 'مرفوض' : 'Refusé',
            'ANNULE': isAr ? 'ملغى' : 'Annulé'
        }
    };

    function badgeClass(statut) {
        /* Sans accents, cohérent avec statut_badge_class() côté PHP */
        var s = String(statut || '')
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase().replace(/_/g, '-');
        return 'badge badge-' + s;
    }

    function renderCard(ev) {
        var d = new Date(ev.date_evenement);
        var day = String(d.getDate()).padStart(2, '0');
        var month = d.toLocaleDateString(isAr ? 'ar' : 'fr-FR', { month: 'short' });

        var anomalie = ev.anomalies
            ? '<span class="badge badge-anomalie">' + esc(ev.anomalies.split(',')[0]) + '</span> '
            : '';

        var favActive = ev.is_favori ? ' active' : '';
        var favIcon = ev.is_favori ? 'mdi-heart' : 'mdi-heart-outline';
        var favBtn = '<button type="button" class="citoyen-fav-btn' + favActive + '" data-fav-id="' + ev.id + '" ' +
            'data-active="' + (ev.is_favori ? 'true' : 'false') + '" aria-pressed="' + (ev.is_favori ? 'true' : 'false') + '" ' +
            'title="' + (isAr ? 'المفضلة' : 'Favori') + '">' +
            '<i class="mdi ' + favIcon + '"></i></button>';

        var link = '<a class="citoyen-card" href="/citoyen/evenement/' + ev.id + '">' +
            '<div class="citoyen-card-date">' +
                '<span class="citoyen-card-day">' + day + '</span>' +
                '<span class="citoyen-card-month">' + esc(month) + '</span>' +
            '</div>' +
            '<div class="citoyen-card-body">' +
                '<h3 class="citoyen-card-title">' + esc(ev.adresse || '') + '</h3>' +
                '<p class="citoyen-card-meta">' +
                    '<i class="mdi mdi-map-marker-outline"></i> ' + esc(ev.commune_nom || '') +
                    '<span class="participants-count"><i class="mdi mdi-account-group"></i> ' + (ev.participants | 0) + ' ' + i18n.participants + '</span>' +
                '</p>' +
                '<div class="citoyen-card-badges">' +
                    anomalie +
                    '<span class="' + badgeClass(ev.statut) + '">' + esc(i18n.statut[ev.statut] || ev.statut) + '</span>' +
                '</div>' +
            '</div>' +
        '</a>';

        return '<div class="citoyen-card-wrap">' + link + favBtn + '</div>';
    }

    function esc(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

    function renderList(data) {
        listEl.innerHTML = '';
        var countEl = document.getElementById('explorerCount');
        if (countEl) { countEl.textContent = (data ? data.length : 0); }
        if (!data || data.length === 0) {
            listEl.style.display = 'none';
            emptyEl.style.display = 'block';
            return;
        }
        emptyEl.style.display = 'none';
        /* Respecter le mode d'affichage courant (liste ou carte) */
        listEl.style.display = currentView === 'list' ? '' : 'none';
        data.forEach(function (ev) {
            var wrap = document.createElement('div');
            wrap.innerHTML = renderCard(ev);
            listEl.appendChild(wrap.firstChild);
        });
    }

    /* ── Fetch AJAX ── */
    var searchTimer = null;
    function fetchEvents() {
        shimmerEl.style.display = '';
        listEl.style.display = 'none';
        emptyEl.style.display = 'none';

        var qs = '?ajax=1';
        if (currentFilters.date) qs += '&date=' + encodeURIComponent(currentFilters.date);
        if (currentFilters.commune) qs += '&commune=' + encodeURIComponent(currentFilters.commune);
        if (currentFilters.anomalie) qs += '&anomalie=' + encodeURIComponent(currentFilters.anomalie);
        if (currentFilters.q) qs += '&q=' + encodeURIComponent(currentFilters.q);
        if (currentFilters.lat && currentFilters.lon) {
            qs += '&lat=' + currentFilters.lat + '&lon=' + currentFilters.lon;
        }

        fetch('/citoyen/explorer' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                shimmerEl.style.display = 'none';
                renderList(data.events || []);
                events = data.events || [];
                if (explorerMap) { updateMap(); }
            })
            .catch(function () {
                shimmerEl.style.display = 'none';
                listEl.style.display = 'none';
                mapContainer.style.display = 'none';
                emptyEl.style.display = 'block';
                emptyEl.innerHTML = '<i class="mdi mdi-wifi-off"></i><p>' + esc(i18n.load_error) + '</p>';
            });
    }

    /* ── Carte ── */
    function initMap() {
        explorerMap = L.map('explorerMap').setView([36.7559, 3.0588], 7);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        }).addTo(explorerMap);
        markerLayer = L.layerGroup().addTo(explorerMap);
        updateMap();
    }

    function updateMap() {
        markerLayer.clearLayers();
        var markers = [];
        events.forEach(function (ev) {
            if (!ev.latitude || !ev.longitude) return;
            var lat = parseFloat(ev.latitude);
            var lon = parseFloat(ev.longitude);
            if (isNaN(lat) || isNaN(lon)) return;
            var marker = L.marker([lat, lon]).addTo(markerLayer)
                .bindPopup('<strong>' + esc(ev.adresse || '') + '</strong><br><a href="/citoyen/evenement/' + ev.id + '">' + i18n.see_event + '</a>');
            markers.push({ lat: lat, lon: lon });
        });
        if (markers.length > 0 && nearbyActive && currentFilters.lat && currentFilters.lon) {
            explorerMap.setView([parseFloat(currentFilters.lat), parseFloat(currentFilters.lon)], 12);
        } else if (markers.length > 0) {
            explorerMap.fitBounds(markers.map(function (m) { return [m.lat, m.lon]; }), { padding: [30, 30] });
        }
    }

    var viewToggle = {
        list: function () {
            currentView = 'list';
            listEl.style.display = '';
            emptyEl.style.display = listEl.children.length === 0 ? 'block' : 'none';
            mapContainer.style.display = 'none';
            viewListBtn.classList.add('active');
            viewListBtn.setAttribute('aria-pressed', 'true');
            viewMapBtn.classList.remove('active');
            viewMapBtn.setAttribute('aria-pressed', 'false');
            if (explorerMap) { explorerMap.remove(); explorerMap = null; markerLayer = null; }
        },
        map: function () {
            currentView = 'map';
            listEl.style.display = 'none';
            emptyEl.style.display = 'none';
            mapContainer.style.display = '';
            viewMapBtn.classList.add('active');
            viewMapBtn.setAttribute('aria-pressed', 'true');
            viewListBtn.classList.remove('active');
            viewListBtn.setAttribute('aria-pressed', 'false');
            if (!explorerMap) { initMap(); }
        }
    };

    viewListBtn.addEventListener('click', viewToggle.list);
    viewMapBtn.addEventListener('click', viewToggle.map);

    /* ── Recherche (debounce) ── */
    var searchInput = document.getElementById('explorerSearch');
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        currentFilters.q = this.value.trim();
        searchTimer = setTimeout(fetchEvents, 300);
    });

    /* ── Filtres ── */
    document.getElementById('filterDate').addEventListener('change', function () {
        currentFilters.date = this.value;
        fetchEvents();
    });
    document.getElementById('filterCommune').addEventListener('change', function () {
        currentFilters.commune = this.value;
        fetchEvents();
    });
    document.getElementById('filterAnomalie').addEventListener('change', function () {
        currentFilters.anomalie = this.value;
        fetchEvents();
    });

    document.getElementById('resetFilters').addEventListener('click', function () {
        currentFilters = { date: 'all', commune: '', anomalie: '', q: '', lat: null, lon: null };
        document.getElementById('filterDate').value = 'all';
        document.getElementById('filterCommune').value = '';
        document.getElementById('filterAnomalie').value = '';
        searchInput.value = '';
        nearbyActive = false;
        fetchEvents();
    });

    /* ── Géolocalisation ── */
    document.getElementById('btnNearbyExplore').addEventListener('click', function () {
        if (!navigator.geolocation) {
            emptyEl.innerHTML = '<i class="mdi mdi-map-marker-off-outline"></i><p>' + esc(isAr ? 'الموقع غير متاح على جهازك.' : 'Géolocalisation non disponible sur votre appareil.') + '</p>';
            emptyEl.style.display = 'block';
            return;
        }
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> ' + i18n.geolocating;
        navigator.geolocation.getCurrentPosition(function (pos) {
            currentFilters.lat = pos.coords.latitude.toFixed(5);
            currentFilters.lon = pos.coords.longitude.toFixed(5);
            nearbyActive = true;
            btn.disabled = false;
            btn.innerHTML = '<i class="mdi mdi-crosshairs-gps"></i> ' + i18n.near_me;
            viewToggle.list();
            fetchEvents();
        }, function (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="mdi mdi-crosshairs-gps"></i> ' + i18n.near_me;
            var msg = err && err.code === err.PERMISSION_DENIED ? i18n.location_denied : i18n.load_error;
            emptyEl.innerHTML = '<i class="mdi mdi-map-marker-off-outline"></i><p>' + esc(msg) + '</p>';
            emptyEl.style.display = 'block';
        });
    });

    /* ── Favoris (délégation sur la liste) ── */
    listEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.citoyen-fav-btn');
        if (!btn || !window.WH_CSRF) return;
        e.preventDefault();
        e.stopPropagation();
        var id = btn.getAttribute('data-fav-id');
        if (!id) return;
        btn.disabled = true;
        fetch('/citoyen/favoris/' + id + '/toggle?ajax=1', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.WH_CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); })
          .then(function (res) {
            if (res && res.success) {
                var icon = btn.querySelector('.mdi');
                if (res.saved) {
                    btn.classList.add('active');
                    btn.setAttribute('data-active', 'true');
                    btn.setAttribute('aria-pressed', 'true');
                    if (icon) { icon.classList.add('mdi-heart'); icon.classList.remove('mdi-heart-outline'); }
                } else {
                    btn.classList.remove('active');
                    btn.setAttribute('data-active', 'false');
                    btn.setAttribute('aria-pressed', 'false');
                    if (icon) { icon.classList.remove('mdi-heart'); icon.classList.add('mdi-heart-outline'); }
                }
            }
          })
          .catch(function () {})
          .finally(function () { btn.disabled = false; });
    });

    renderList(events);
})();
</script>
