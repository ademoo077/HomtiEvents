<?php
/**
 * Suivi en direct — Carte des événements EN_COURS.
 *
 * @var string $page_title
 */
use App\Helpers\I18n;

$title = 'Suivi en direct';
$page  = 'wilaya.suivi';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="wh-page">
    <div class="wh-hero-panel wh-suivi-hero mb-4">
        <i class="mdi mdi-map-marker-radius wh-hero-watermark" aria-hidden="true"></i>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3" style="position:relative;z-index:1">
            <div>
                <h1 class="d-flex align-items-center gap-2">
                    <i class="mdi mdi-map-marker-radius"></i>
                    <?= e($title) ?>
                </h1>
                <p>
                    <?= e($isAr ? 'متابعة مباشرة للفعاليات الجارية' : 'Suivi temps réel des événements en cours') ?>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2" style="position:relative;z-index:1">
                <span class="wh-hero-tag" id="suiviLastUpdate"></span>
                <button type="button" class="btn btn-light" id="suiviRefresh" title="<?= e($isAr ? 'تحديث' : 'Actualiser') ?>">
                    <i class="mdi mdi-refresh"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ KPI ═══ -->
    <div class="grid grid-cols-2 gap-3 mb-4 lg:grid-cols-4">
        <div class="wh-kpi-card bg-white shadow-sm rounded-xl border border-gray-200 p-3 flex items-center gap-3">
            <div class="wh-kpi-icon bg-primary bg-opacity-10 text-primary"><i class="mdi mdi-calendar-clock"></i></div>
            <div class="min-w-0">
                <div class="wh-kpi-value" id="kpiEnCours">0</div>
                <div class="wh-kpi-label"><?= e($isAr ? 'فعالية جارية' : 'Événements en cours') ?></div>
            </div>
        </div>
        <div class="wh-kpi-card bg-white shadow-sm rounded-xl border border-gray-200 p-3 flex items-center gap-3">
            <div class="wh-kpi-icon bg-success bg-opacity-10 text-success"><i class="mdi mdi-account-group"></i></div>
            <div class="min-w-0">
                <div class="wh-kpi-value" id="kpiParticipants">0</div>
                <div class="wh-kpi-label"><?= e($isAr ? 'مشارك' : 'Participants cumulés') ?></div>
            </div>
        </div>
        <div class="wh-kpi-card bg-white shadow-sm rounded-xl border border-gray-200 p-3 flex items-center gap-3">
            <div class="wh-kpi-icon bg-warning bg-opacity-10 text-warning"><i class="mdi mdi-scan-helper"></i></div>
            <div class="min-w-0">
                <div class="wh-kpi-value" id="kpiScansHeure">0</div>
                <div class="wh-kpi-label"><?= e($isAr ? 'مسح (آخر ساعة)' : 'Scans (1 h)') ?></div>
            </div>
        </div>
        <div class="wh-kpi-card bg-white shadow-sm rounded-xl border border-gray-200 p-3 flex items-center gap-3">
            <div class="wh-kpi-icon bg-info bg-opacity-10 text-info"><i class="mdi mdi-gauge"></i></div>
            <div class="min-w-0">
                <div class="wh-kpi-value" id="kpiTaux">—</div>
                <div class="wh-kpi-label"><?= e($isAr ? 'متوسط الامتلاء' : 'Taux moyen remplissage') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 rounded-xl shadow-sm overflow-hidden">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 bg-gray-50 border-bottom">
                    <span class="fw-bold"><i class="mdi mdi-map-marker me-2"></i><?= e($isAr ? 'الخريطة' : 'Carte') ?></span>
                    <div class="d-flex align-items-center flex-wrap gap-3 small">
                        <span class="wh-legend"><i class="wh-dot" style="background:#16a34a"></i><?= e($isAr ? 'ضمن الحدود' : 'Sous capacité') ?></span>
                        <span class="wh-legend"><i class="wh-dot" style="background:#d4af37"></i><?= e($isAr ? 'قريب من الامتلاء' : 'Presque plein') ?></span>
                        <span class="wh-legend"><i class="wh-dot" style="background:#dc2626"></i><?= e($isAr ? 'ممتلئ' : 'Complet') ?></span>
                        <span class="wh-legend"><span class="suivi-anomaly-dot"><i class="mdi mdi-alert"></i></span><?= e($isAr ? 'خلل' : 'Anomalie') ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="suiviMap"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 rounded-xl shadow-sm overflow-hidden mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-gray-50 fw-bold">
                    <span><i class="mdi mdi-format-list-bulleted me-2"></i><?= e($isAr ? 'الفعاليات الجارية' : 'Événements en cours') ?></span>
                    <span class="badge rounded-pill bg-primary-subtle text-primary ms-1" id="suiviEventCount" style="display:none">0</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush wh-event-list" id="suiviList" style="max-height: 320px; overflow-y:auto;"></ul>
                </div>
            </div>
            <div class="card border-0 rounded-xl shadow-sm overflow-hidden">
                <div class="card-header bg-gray-50 fw-bold"><i class="mdi mdi-scan-helper me-2"></i><?= e($isAr ? 'آخر عمليات المسح' : 'Derniers scans') ?></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush wh-scan-list" id="suiviScans" style="max-height: 260px; overflow-y:auto;"></ul>
                </div>
            </div>
        </div>
    </div>

    <style>
.wh-suivi-hero{--hero-a:#0B5ED7;--hero-b:#6C63FF}
.wh-hero-watermark{position:absolute;inset-inline-end:-1rem;bottom:-2rem;font-size:9rem;opacity:.14;pointer-events:none}
.wh-hero-tag{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);backdrop-filter:blur(4px);color:#fff;font-size:.76rem;font-weight:600;padding:.3rem .7rem;border-radius:999px;white-space:nowrap}

/* ── Marqueurs d'anomalies ─────────────────────────── */
.suivi-anomaly-dot{
    width:18px;height:18px;border-radius:50%;
    background:linear-gradient(135deg,#dc2626,#f87171);
    color:#fff;display:inline-flex;align-items:center;justify-content:center;
    font-size:.72rem;flex-shrink:0;
}
.suivi-anomaly-dot .mdi{font-size:.8rem}
.suivi-anomaly-pill{
    display:inline-flex;align-items:center;gap:.35rem;
    padding:.25rem .65rem;border-radius:999px;
    font-weight:700;font-size:.72rem;line-height:1.2;color:#fff;
    box-shadow:0 4px 14px rgba(220,38,38,.4);
}
.suivi-anomaly-pill .mdi{font-size:.85rem}
.suivi-anomaly-pill.prio-critique{background:linear-gradient(135deg,#b91c1c,#ef4444);animation:wh-anom-pulse 1.8s ease-out infinite}
.suivi-anomaly-pill.prio-haute{background:linear-gradient(135deg,#ea580c,#f97316)}
.suivi-anomaly-pill.prio-moyenne{background:linear-gradient(135deg,#d97706,#f59e0b)}
.suivi-anomaly-pill.prio-basse{background:linear-gradient(135deg,#2563eb,#3b82f6)}
@keyframes wh-anom-pulse{0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.5)}50%{box-shadow:0 0 0 8px rgba(220,38,38,0)}}
.suivi-amarker{
    width:34px;height:34px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;color:#fff;
    border:3px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,.35);
    position:relative;cursor:pointer;text-decoration:none;
    transition:transform .18s var(--wh-ease, ease);
}
.suivi-amarker:hover{transform:scale(1.12)}
.suivi-amarker .mdi{font-size:1rem}
.suivi-amarker.pulse::after{
    content:'';position:absolute;inset:-6px;border-radius:50%;
    border:2px solid currentColor;animation:wh-anom-ping 1.8s ease-out infinite;opacity:0;
}
@keyframes wh-anom-ping{0%{transform:scale(.7);opacity:.9}100%{transform:scale(1.7);opacity:0}}
.suivi-popup .suivi-anom-tag{font-size:.78rem;color:#b91c1c;font-weight:600;margin-top:.35rem}
    </style>
</div>

<link rel="stylesheet" href="<?= asset('/assets/vendor/leaflet/css/leaflet.css') ?>">
<script src="<?= asset('/assets/vendor/leaflet/js/leaflet.js') ?>"></script>
<style>
#suiviMap { z-index: 0; height: 560px; }
.wh-kpi-card { transition: transform .18s var(--wh-ease), box-shadow .18s var(--wh-ease); }
.wh-kpi-card:hover { transform: translateY(-2px); box-shadow: var(--wh-shadow-lg) !important; }
.wh-kpi-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.wh-kpi-value { font-size: 1.35rem; font-weight: 800; line-height: 1.1; color: var(--wh-text, #1f2937); }
.wh-kpi-label { font-size: .78rem; color: #6b7280; }
.wh-legend { display: inline-flex; align-items: center; gap: .35rem; color: #6b7280; }
.wh-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.suivi-marker {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; font-weight: 800;
    border: 3px solid #fff; box-shadow: 0 3px 10px rgba(0,0,0,.35);
}
.suivi-marker.pulse::after {
    content: ''; position: absolute; inset: -6px; border-radius: 50%;
    border: 2px solid currentColor; animation: wh-ping 1.8s ease-out infinite; opacity: 0;
}
@keyframes wh-ping { 0% { transform: scale(.7); opacity: .8; } 100% { transform: scale(1.6); opacity: 0; } }
.suivi-popup { min-width: 220px; }
.suivi-popup h6 { margin: 0 0 .15rem; font-weight: 700; }
.suivi-popup .suivi-meta { color: #6b7280; font-size: .8rem; }
.suivi-popup .progress { height: 8px; background: #e5e7eb; }
.wh-event-list .list-group-item { padding: .65rem 1rem; }
.wh-event-bar { font-size: .72rem; font-weight: 700; color: #4b5563; display: flex; justify-content: space-between; }
.wh-scan-list .list-group-item { padding: .5rem .9rem; }
.wh-scan-name { font-weight: 600; font-size: .85rem; }
.wh-scan-time { font-size: .72rem; color: #9ca3af; }
.wh-pulse-row { animation: wh-flash 1.2s ease-in-out; }
@keyframes wh-flash { 0%,100% { background: transparent; } 50% { background: rgba(212,175,55,.18); } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('suiviMap');
    const listEl = document.getElementById('suiviList');
    const scansEl = document.getElementById('suiviScans');
    const lastEl = document.getElementById('suiviLastUpdate');
    if (!mapEl) { return; }

    const isAr = document.documentElement.dir === 'rtl';
    const api = '<?= url('api/evenements/suivi') ?>';

    let map = L.map('suiviMap', { scrollWheelZoom: false, center: [36.7538, 3.0588], zoom: 9 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    let markers = {};
    let seenEvents = {};
    // Couche dédiée aux marqueurs d'anomalies (rafraîchie à chaque tick)
    let anomaliesLayer = L.layerGroup().addTo(map);

    function esc(s) { return String(s == null ? '' : s).replace(/[<>&"]/g, function (c) { return { '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;' }[c]; }); }

    // Palette de priorité d'anomalie → classe + libellé
    function anomalyPrio(a) {
        const p = String(a.priorite || 'moyenne').toLowerCase();
        if (p === 'critique') return { cls: 'prio-critique', label: isAr ? 'حاسم' : 'Critique' };
        if (p === 'haute')    return { cls: 'prio-haute',  label: isAr ? 'عالية' : 'Haute' };
        if (p === 'basse')    return { cls: 'prio-basse',  label: isAr ? 'منخفضة' : 'Basse' };
        return { cls: 'prio-moyenne', label: isAr ? 'متوسطة' : 'Moyenne' };
    }

    function renderAnomalies(anomalies) {
        anomaliesLayer.clearLayers();
        (anomalies || []).forEach(function (a) {
            const lat = parseFloat(a.latitude), lng = parseFloat(a.longitude);
            if (!lat || !lng) return;
            const prio = anomalyPrio(a);
            const icon = L.divIcon({
                className: 'suivi-amarker-wrap',
                html: '<div class="suivi-amarker pulse ' + prio.cls +
                      '" role="img" aria-label="' + esc(prio.label) + '"><i class="mdi mdi-alert"></i></div>',
                iconSize: [34, 34],
                iconAnchor: [17, 17]
            });
            const evUrl = '<?= url('wilaya/evenements') ?>/' + (a.evenement_id_suivi || a.evenement_id);
            L.marker([lat, lng], { icon: icon })
                .addTo(anomaliesLayer)
                .bindPopup(
                    '<div class="suivi-popup">' +
                    '<h6>' + esc(a.titre || a.anomalie_nom || 'Anomalie') + '</h6>' +
                    '<div class="suivi-meta">' + esc(a.commune || '') +
                        (a.evenement_adresse ? ' · ' + esc(a.evenement_adresse) : '') + '</div>' +
                    '<span class="suivi-anomaly-pill ' + prio.cls + '"><i class="mdi mdi-alert"></i>' + prio.label + '</span>' +
                    '<div class="suivi-meta mt-1">' + esc(a.statut || 'DETECTEE') + '</div>' +
                    '<a href="' + evUrl + '" class="btn btn-sm btn-outline-primary mt-2"><i class="mdi mdi-eye me-1"></i>' +
                        (isAr ? 'عرض الحدث' : 'Voir l\'événement') + '</a>' +
                    '</div>'
                );
        });
    }

    function fmtDate(d) { try { return new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }); } catch (e) { return d; } }

    function tauxColor(ev) {
        const t = ev.taux_remplissage;
        if (t == null) return '#1A4D3E';
        if (t >= 100) return '#dc2626';
        if (t >= 85) return '#ea580c';
        if (t >= 60) return '#d4af37';
        return '#16a34a';
    }

    function render(events, kpis, scans, anomalies) {
        lastEl.textContent = isAr ? 'آخر تحديث: ' + new Date().toLocaleTimeString() : 'Mis à jour à ' + new Date().toLocaleTimeString();

        document.getElementById('kpiEnCours').textContent = kpis ? kpis.en_cours : events.length;
        document.getElementById('kpiParticipants').textContent = kpis ? kpis.participants : 0;
        document.getElementById('kpiScansHeure').textContent = kpis ? kpis.scans_heure : 0;
        document.getElementById('kpiTaux').textContent = kpis && kpis.taux_moyen != null ? kpis.taux_moyen + '%' : '—';

        // Compteur d'événements dans le panneau latéral
        const evCountEl = document.getElementById('suiviEventCount');
        if (evCountEl) { evCountEl.textContent = events.length; evCountEl.style.display = events.length ? '' : 'none'; }

        // Marqueurs d'anomalies (couche dédiée)
        renderAnomalies(anomalies);

        const now = Date.now();
        const seen = {};
        events.forEach(function (ev) {
            seen[ev.id] = true;

            // Détection d'un nouvel événement : flash + centrage
            if (!seenEvents[ev.id]) {
                seenEvents[ev.id] = true;
                setTimeout(function () {
                    const row = listEl.querySelector('[data-id="' + ev.id + '"]');
                    if (row) { row.classList.add('wh-pulse-row'); setTimeout(function () { row.classList.remove('wh-pulse-row'); }, 1200); }
                }, 50);
            }

            if (!ev.latitude || !ev.longitude) { return; }
            const key = 'e' + ev.id;
            const color = tauxColor(ev);
            if (markers[key]) { map.removeLayer(markers[key]); }

            const places = ev.places_restantes != null
                ? (ev.places_restantes + ' ' + (isAr ? 'متبقية' : 'places'))
                : (isAr ? 'غير محدود' : 'illimité');
            const bar = ev.taux_remplissage != null
                ? '<div class="progress mt-1"><div class="progress-bar" style="width:' + ev.taux_remplissage + '%;background:' + color + '"></div></div>' +
                  '<div class="wh-event-bar"><span>' + ev.participants + '/' + ev.capacite + '</span><span>' + ev.taux_remplissage + '%</span></div>'
                : '<div class="wh-event-bar"><span>' + ev.participants + ' ' + (isAr ? 'مشارك' : 'participants') + '</span></div>';

            const icon = L.divIcon({
                className: 'suivi-marker-wrap',
                html: '<div class="suivi-marker pulse" style="background:' + color + '">' + ev.participants + '</div>',
                iconSize: [34, 34],
                iconAnchor: [17, 17]
            });

            var detailUrl='<?= url('wilaya/evenements') ?>/'+ev.id;
            var quick='';
            if(ev.statut==='EN_ATTENTE') quick='<a href="'+detailUrl+'" class="btn btn-sm btn-warning mt-2"><i class="mdi mdi-check me-1"></i>'+(isAr?'تحقق':'Valider')+'</a>';
            else if(ev.statut==='VALIDÉ') quick='<a href="'+detailUrl+'" class="btn btn-sm btn-primary mt-2"><i class="mdi mdi-calendar-check me-1"></i>'+(isAr?'برمجة':'Programmer')+'</a>';
            else quick='<a href="'+detailUrl+'" class="btn btn-sm btn-outline-primary mt-2"><i class="mdi mdi-eye me-1"></i>'+(isAr?'عرض':'Voir')+'</a>';
            markers[key] = L.marker([parseFloat(ev.latitude), parseFloat(ev.longitude)], { icon: icon })
                .addTo(map)
                .bindPopup(
                    '<div class="suivi-popup">' +
                    '<h6>' + esc(ev.adresse) + '</h6>' +
                    '<div class="suivi-meta">' + esc(ev.commune || '') + (ev.association ? ' · ' + esc(ev.association) : '') + ' · ' + esc(ev.statut||'') + '</div>' +
                    '<div class="suivi-meta">' + (ev.heure ? String(ev.heure).slice(0, 5) : '') + '</div>' +
                    '<div class="mt-2">' + bar + '</div>' +
                    '<div class="suivi-meta mt-1">' + places + '</div>' +
                    quick +
                    '</div>'
                );
        });

        Object.keys(markers).forEach(function (k) {
            if (!seen[k.slice(1)]) { map.removeLayer(markers[k]); delete markers[k]; }
        });

        // Liste latérale
        if (events.length === 0) {
            listEl.innerHTML = '<li class="list-group-item text-center text-muted py-4">' + (isAr ? 'لا توجد فعاليات جارية' : 'Aucun événement en cours') + '</li>';
        } else {
            listEl.innerHTML = events.map(function (ev) {
                const color = tauxColor(ev);
                const t = ev.taux_remplissage;
                const bar = t != null
                    ? '<div class="progress mt-1" style="height:6px;background:#e5e7eb"><div class="progress-bar" style="width:' + t + '%;background:' + color + '"></div></div>' +
                      '<div class="wh-event-bar mt-1"><span>' + ev.participants + '/' + ev.capacite + '</span><span>' + t + '%</span></div>'
                    : '<div class="wh-event-bar mt-1"><span>' + ev.participants + ' ' + (isAr ? 'مشارك' : 'participants') + '</span><span>' + (isAr ? 'غير محدود' : 'illimité') + '</span></div>';
                return '<li class="list-group-item" data-id="' + ev.id + '">' +
                    '<div class="d-flex justify-content-between align-items-start gap-2">' +
                    '<div class="min-w-0">' +
                    '<div class="text-truncate fw-semibold">' + esc(ev.adresse) + '</div>' +
                    '<div class="small text-muted">' + esc(ev.commune || '') + (ev.association ? ' · ' + esc(ev.association) : '') + (ev.heure ? ' · ' + String(ev.heure).slice(0, 5) : '') + '</div>' +
                    '</div>' +
                    '<span class="suivi-dot" style="width:10px;height:10px;border-radius:50%;background:' + color + ';flex-shrink:0;margin-top:.3rem"></span>' +
                    '</div>' + bar + '</li>';
            }).join('');
        }

        // Fil des scans
        if (!scans || scans.length === 0) {
            scansEl.innerHTML = '<li class="list-group-item text-center text-muted py-4">' + (isAr ? 'لا توجد عمليات مسح' : 'Aucun scan récent') + '</li>';
        } else {
            scansEl.innerHTML = scans.slice(0, 8).map(function (s) {
                const name = (s.nom || s.prenom) ? esc((s.prenom || '') + ' ' + (s.nom || '')).trim() : (isAr ? 'مواطن' : 'Citoyen');
                return '<li class="list-group-item d-flex justify-content-between align-items-center gap-2">' +
                    '<div class="min-w-0">' +
                    '<div class="wh-scan-name text-truncate">' + name + '</div>' +
                    '<div class="text-truncate small text-muted">' + esc(s.adresse) + '</div>' +
                    '</div>' +
                    '<span class="wh-scan-time text-nowrap">' + fmtDate(s.heure_scan) + '</span>' +
                    '</li>';
            }).join('');
        }

        // Cadrer sur les événements à coordonnées
        const pts = events.filter(function (ev) { return ev.latitude && ev.longitude; })
            .map(function (ev) { return [parseFloat(ev.latitude), parseFloat(ev.longitude)]; });
        if (pts.length > 0) { map.fitBounds(L.latLngBounds(pts), { padding: [40, 40], maxZoom: 13 }); }
    }

    async function refresh() {
        try {
            const res = await fetch(api, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data && data.success) { render(data.events || [], data.kpis || null, data.recent_scans || [], data.anomalies || []); }
        } catch (e) { /* réseau : on réessaiera au prochain tick */ }
    }

    refresh();
    setInterval(refresh, 10000);
    document.getElementById('suiviRefresh').addEventListener('click', refresh);
});
</script>
