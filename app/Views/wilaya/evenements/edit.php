<?php
/** @var array $event @var array $communes @var array $dairas @var array $associations
 *  @var array $anomalies @var array $selectedAnomalies @var array $epics
 *  @var array $assignedEpics @var array $errors @var array $anomalyDetails @var array $assignments
 */
use App\Helpers\I18n;

$title = __('common.edit');
$page  = 'wilaya.evenements.edit';
$isAr  = I18n::direction() === 'rtl';

$anomalyDetails = $anomalyDetails ?? [];
$assignments = $assignments ?? [];

$oldVal = static function (string $key, mixed $default = null) use ($event): mixed {
    return $_SESSION['_old'][$key] ?? ($event[$key] ?? $default);
};
$error = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<div class="form-error">' . e(is_array($errors[$key]) ? implode(', ', $errors[$key]) : (string) $errors[$key]) . '</div>' : '';
};

$communeData = [];
foreach ($communes as $c) {
    $communeData[(int) $c['id']] = [
        'lat' => isset($c['latitude']) ? (float) $c['latitude'] : null,
        'lng' => isset($c['longitude']) ? (float) $c['longitude'] : null,
        'nom' => $c['nom'],
    ];
}
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
.event-map-card .card-body { padding: 0; }
#eventMap { height: 280px; border-radius: 0 0 .375rem .375rem; z-index: 1; }
.anomaly-card { border: 1px solid #e2e8f0; border-radius: .5rem; padding: .75rem 1rem; margin-bottom: .5rem; background: #f8fafc; transition: all .2s; }
.anomaly-card:hover { border-color: #6366f1; box-shadow: 0 0 0 1px #6366f1; }
.anomaly-card.anomaly-selected { border-color: #6366f1; background: #eef2ff; }
.anomaly-card .anomaly-header { display: flex; align-items: center; gap: .5rem; }
.anomaly-card .anomaly-header input[type="checkbox"] { width: 1.1em; height: 1.1em; accent-color: #6366f1; }
.anomaly-card .anomaly-gps { display: none; margin-top: .5rem; }
.anomaly-card.anomaly-selected .anomaly-gps { display: block; }
.gps-inputs { display: flex; gap: .5rem; align-items: end; }
.gps-inputs .form-floating { flex: 1; }
.btn-set-pin { font-size: .75rem; padding: .2rem .5rem; }
.routing-preview-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: .5rem; padding: .75rem 1rem; margin-top: .75rem; }
.routing-preview-box.no-match { background: #fef2f2; border-color: #fca5a5; }
.routing-epic-tag { display: inline-flex; align-items: center; gap: .35rem; background: #dbeafe; color: #1e40af; border-radius: 9999px; padding: .2rem .6rem; font-size: .8rem; font-weight: 500; margin: .15rem; }
.routing-epic-tag.unrouted { background: #fee2e2; color: #991b1b; }
.assignment-row { display: flex; align-items: center; gap: .75rem; padding: .5rem .75rem; border: 1px solid #e2e8f0; border-radius: .5rem; margin-bottom: .35rem; background: #fff; }
.assignment-row .badge { font-size: .7rem; }
.duration-badge { display: inline-block; background: #6366f1; color: #fff; border-radius: 9999px; padding: .15rem .5rem; font-size: .7rem; font-weight: 600; margin-left: .5rem; }
.anomaly-status-select { font-size: .75rem; padding: .15rem .4rem; }
</style>
<div class="wh-page">
    <div class="wh-hero" style="background:linear-gradient(135deg,#0B5ED7 0%,#6C63FF 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-pencil-outline me-2"></i><?= e(__('common.edit')) ?> — #<?= (int) $event['id'] ?></h1>
                    <p class="wh-hero-sub"><?= e($event['adresse']) ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a href="<?= url('wilaya/evenements/' . (int) $event['id']) ?>" class="btn btn-sm btn-outline-light">
                        <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/update') ?>" novalidate>
        <?= csrf_field() ?>

        <!-- Carte + Adresse -->
        <div class="card border-0 shadow-sm mb-4 event-map-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="mdi mdi-map-marker-radius me-2"></i><?= e(__('common.adresse')) ?></span>
                <div class="d-flex align-items-center gap-2">
                    <input type="hidden" id="latitude" name="latitude" value="<?= e((string) $oldVal('latitude')) ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?= e((string) $oldVal('longitude')) ?>">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnMyLocation" title="Ma position">
                        <i class="mdi mdi-crosshairs-gps"></i>
                    </button>
                </div>
            </div>
            <div id="eventMap"></div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="commune_id"><?= e(__('common.commune')) ?> *</label>
                        <select class="form-select <?= isset($errors['commune_id']) ? 'is-invalid' : '' ?>" id="commune_id" name="commune_id" required>
                            <option value="">— <?= e(__('common.commune')) ?> —</option>
                            <?php foreach ($communes as $c): ?>
                                <option value="<?= e((string) $c['id']) ?>" data-lat="<?= e((string) ($c['latitude'] ?? '')) ?>" data-lng="<?= e((string) ($c['longitude'] ?? '')) ?>" <?= (string) $oldVal('commune_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $error('commune_id') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="adresse"><?= e(__('common.adresse')) ?> *</label>
                        <input type="text" class="form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" id="adresse" name="adresse"
                               value="<?= e((string) $oldVal('adresse')) ?>" required minlength="5">
                        <?= $error('adresse') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations générales -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-file-document-outline me-2"></i><?= e(__('common.informations')) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="association_id"><?= e(__('common.association')) ?></label>
                        <select class="form-select" id="association_id" name="association_id">
                            <option value="">— <?= e(__('common.all')) ?> —</option>
                            <?php foreach ($associations as $a): ?>
                                <option value="<?= e((string) $a['id']) ?>" <?= (string) $oldVal('association_id') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('common.status')) ?></label>
                        <div class="form-control-plaintext">
                            <span class="wh-badge"><?= e(statut_label((string) $event['statut'])) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="capacite"><?= $isAr ? 'السعة القصوى' : 'Capacité maximale (participants)' ?></label>
                        <input type="number" class="form-control <?= isset($errors['capacite']) ? 'is-invalid' : '' ?>" id="capacite" name="capacite"
                               min="1" step="1" value="<?= e((string) $oldVal('capacite')) ?>" placeholder="200">
                        <?= $error('capacite') ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description"><?= e(__('common.description')) ?> *</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description"
                                  rows="4" required minlength="10"><?= e((string) $oldVal('description')) ?></textarea>
                        <?= $error('description') ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="informations"><?= e(__('evenements.complementaires')) ?></label>
                        <textarea class="form-control" id="informations" name="informations" rows="3"><?= e((string) $oldVal('informations_complementaires')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anomalies avec GPS + statut par anomalie -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="mdi mdi-alert-octagon me-2"></i><?= e(__('evenements.anomalies')) ?> *</span>
                <small class="text-muted">Géolocalisez et suivez le statut de chaque anomalie</small>
            </div>
            <div class="card-body">
                <div id="anomalyList">
                    <?php foreach ($anomalies as $an):
                        $aId = (int) $an['id'];
                        $isChecked = in_array($aId, $selectedAnomalies, true);
                        $detail = null;
                        foreach ($anomalyDetails as $ad) {
                            if ((int) $ad['anomalie_id'] === $aId) { $detail = $ad; break; }
                        }
                        ?>
                        <div class="anomaly-card <?= $isChecked ? 'anomaly-selected' : '' ?>" data-anomaly-id="<?= $aId ?>">
                            <div class="anomaly-header">
                                <input class="form-check-input anomaly-toggle" type="checkbox" name="anomalies[]" id="anomalie-<?= $aId ?>"
                                       value="<?= $aId ?>" <?= $isChecked ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="anomalie-<?= $aId ?>"><?= e($an['nom']) ?></label>
                                <?php if ($detail && $detail['statut']): ?>
                                    <span class="badge bg-secondary ms-auto anomaly-status-badge" data-status="<?= e((string) $detail['statut']) ?>"><?= e((string) $detail['statut']) ?></span>
                                <?php endif; ?>
                                <select class="form-select form-select-sm anomaly-status-select ms-2" data-anomaly-id="<?= $aId ?>" style="width:auto; display:<?= $isChecked ? 'inline-block' : 'none' ?>;">
                                    <option value="DETECTEE" <?= ($detail['statut'] ?? '') === 'DETECTEE' ? 'selected' : '' ?>>Détectée</option>
                                    <option value="EN_COURS" <?= ($detail['statut'] ?? '') === 'EN_COURS' ? 'selected' : '' ?>>En cours</option>
                                    <option value="RESOLUE" <?= ($detail['statut'] ?? '') === 'RESOLUE' ? 'selected' : '' ?>>Résolue</option>
                                    <option value="REJETEE" <?= ($detail['statut'] ?? '') === 'REJETEE' ? 'selected' : '' ?>>Rejetée</option>
                                    <option value="EN_ATTENTE" <?= ($detail['statut'] ?? '') === 'EN_ATTENTE' ? 'selected' : '' ?>>En attente</option>
                                </select>
                            </div>
                            <div class="anomaly-gps">
                                <div class="gps-inputs">
                                    <div class="form-floating">
                                        <input type="number" step="any" class="form-control form-control-sm" name="anomaly_lat[<?= $aId ?>]"
                                               placeholder="Latitude" value="<?= e((string) ($detail['latitude'] ?? '')) ?>">
                                        <label>Latitude</label>
                                    </div>
                                    <div class="form-floating">
                                        <input type="number" step="any" class="form-control form-control-sm" name="anomaly_lng[<?= $aId ?>]"
                                               placeholder="Longitude" value="<?= e((string) ($detail['longitude'] ?? '')) ?>">
                                        <label>Longitude</label>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-set-pin" data-anomaly-id="<?= $aId ?>">
                                        <i class="mdi mdi-map-marker-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= $error('anomalies') ?>

                <!-- Current assignments -->
                <?php if ($assignments !== []): ?>
                <div class="mt-3">
                    <strong><i class="mdi mdi-office-building-outline me-1"></i>Assignations EPIC par anomalie</strong>
                    <?php foreach ($assignments as $as): ?>
                        <div class="assignment-row" data-assignment-id="<?= (int) $as['id'] ?>">
                            <span class="fw-semibold" style="min-width:120px;"><?= e((string) $as['anomalie_nom']) ?></span>
                            <i class="mdi mdi-arrow-right-bold"></i>
                            <span class="badge bg-primary"><?= e((string) $as['epic_nom']) ?></span>
                            <?php if ($as['auto_routed']): ?>
                                <span class="badge bg-success" style="font-size:.65rem;">Auto</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark" style="font-size:.65rem;">Manuel</span>
                            <?php endif; ?>
                            <select class="form-select form-select-sm ms-auto override-select" data-assignment-id="<?= (int) $as['id'] ?>" style="width:160px;">
                                <option value="">Réaffecter…</option>
                                <?php foreach ($epics as $ep): ?>
                                    <option value="<?= (int) $ep['id'] ?>" <?= (int) $ep['id'] === (int) $as['epic_id'] ? 'disabled' : '' ?>><?= e($ep['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Routing preview -->
                <div id="routingPreview" style="display:none;"></div>
            </div>
        </div>

        <!-- Dates + horaires -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-calendar-clock me-2"></i><?= e(__('evenements.program.title')) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="date_evenement"><?= e(__('evenements.program.date')) ?></label>
                        <input type="date" class="form-control" id="date_evenement" name="date_evenement"
                               value="<?= e((string) $oldVal('date_evenement')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="heure"><?= e(__('evenements.program.heure')) ?></label>
                        <input type="time" class="form-control" id="heure" name="heure"
                               value="<?= e((string) $oldVal('heure')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="start_at">Date/heure début</label>
                        <input type="datetime-local" class="form-control" id="start_at" name="start_at"
                               value="<?= e((string) $oldVal('start_at')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="end_at">Date/heure fin</label>
                        <input type="datetime-local" class="form-control" id="end_at" name="end_at"
                               value="<?= e((string) $oldVal('end_at')) ?>">
                        <div id="durationBadge"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="epics"><?= e(__('evenements.epics_assigned')) ?></label>
                        <select class="form-select" id="epics" name="epics[]" multiple size="5">
                            <?php foreach ($epics as $ep): ?>
                                <option value="<?= e((string) $ep['id']) ?>" <?= in_array((int) $ep['id'], $assignedEpics, true) ? 'selected' : '' ?>><?= e($ep['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('wilaya/evenements/' . (int) $event['id']) ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
            <button type="submit" class="btn btn-primary">
                <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
            </button>
        </div>
    </form>
</div>

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    const csrfToken = window.WH_CSRF || '';
    const communeData = <?= json_encode($communeData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    let map, mainMarker;

    const defaultLatLng = [36.7538, 3.0588];
    const initLat = document.getElementById('latitude').value;
    const initLng = document.getElementById('longitude').value;

    map = L.map('eventMap', { zoomControl: true, scrollWheelZoom: true }).setView(
        initLat && initLng ? [parseFloat(initLat), parseFloat(initLng)] : defaultLatLng, initLat ? 13 : 6
    );
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    }).addTo(map);

    if (initLat && initLng) {
        mainMarker = L.marker([parseFloat(initLat), parseFloat(initLng)], {draggable: true}).addTo(map);
        mainMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            document.getElementById('latitude').value = pos.lat.toFixed(7);
            document.getElementById('longitude').value = pos.lng.toFixed(7);
        });
    }

    map.on('click', function(e) {
        const lat = e.latlng.lat, lng = e.latlng.lng;
        document.getElementById('latitude').value = lat.toFixed(7);
        document.getElementById('longitude').value = lng.toFixed(7);
        if (mainMarker) { mainMarker.setLatLng([lat, lng]); }
        else {
            mainMarker = L.marker([lat, lng], {draggable: true}).addTo(map);
            mainMarker.on('dragend', function(ev) {
                const pos = ev.target.getLatLng();
                document.getElementById('latitude').value = pos.lat.toFixed(7);
                document.getElementById('longitude').value = pos.lng.toFixed(7);
            });
        }
    });

    const communeSelect = document.getElementById('commune_id');
    communeSelect.addEventListener('change', function() {
        const c = communeData[parseInt(this.value)];
        if (c && c.lat && c.lng) {
            map.flyTo([c.lat, c.lng], 13, {duration: 0.8});
            if (!mainMarker) {
                mainMarker = L.marker([c.lat, c.lng], {draggable: true}).addTo(map);
                mainMarker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    document.getElementById('latitude').value = pos.lat.toFixed(7);
                    document.getElementById('longitude').value = pos.lng.toFixed(7);
                });
            }
            document.getElementById('latitude').value = c.lat.toFixed(7);
            document.getElementById('longitude').value = c.lng.toFixed(7);
        }
    });

    document.getElementById('btnMyLocation').addEventListener('click', function() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(function(pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            map.flyTo([lat, lng], 15, {duration: 0.8});
            document.getElementById('latitude').value = lat.toFixed(7);
            document.getElementById('longitude').value = lng.toFixed(7);
            if (mainMarker) mainMarker.setLatLng([lat, lng]);
            else {
                mainMarker = L.marker([lat, lng], {draggable: true}).addTo(map);
                mainMarker.on('dragend', function(e) {
                    const p = e.target.getLatLng();
                    document.getElementById('latitude').value = p.lat.toFixed(7);
                    document.getElementById('longitude').value = p.lng.toFixed(7);
                });
            }
        });
    });

    // Anomaly toggle
    document.querySelectorAll('.anomaly-toggle').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const card = this.closest('.anomaly-card');
            card.classList.toggle('anomaly-selected', this.checked);
            const sel = card.querySelector('.anomaly-status-select');
            if (sel) sel.style.display = this.checked ? 'inline-block' : 'none';
            fetchRoutingPreview();
        });
    });

    // Set pin
    document.querySelectorAll('.btn-set-pin').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!mainMarker) return;
            const aId = this.dataset.anomalyId;
            const pos = mainMarker.getLatLng();
            const card = document.querySelector('.anomaly-card[data-anomaly-id="' + aId + '"]');
            if (!card) return;
            card.querySelector('input[name="anomaly_lat[' + aId + ']"]').value = pos.lat.toFixed(7);
            card.querySelector('input[name="anomaly_lng[' + aId + ']"]').value = pos.lng.toFixed(7);
        });
    });

    // Anomaly status change
    document.querySelectorAll('.anomaly-status-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            const aId = this.dataset.anomalyId;
            const evenementId = <?= (int) $event['id'] ?>;
            fetch('<?= url("wilaya/api/anomaly-status") ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
                body: 'evenement_id=' + evenementId + '&anomalie_id=' + aId + '&new_status=' + this.value
            }).then(function(r){return r.json();}).then(function(data) {
                if (data.ok) {
                    const card = document.querySelector('.anomaly-card[data-anomaly-id="' + aId + '"]');
                    const badge = card.querySelector('.anomaly-status-badge');
                    if (badge) { badge.dataset.status = sel.value; badge.textContent = sel.options[sel.selectedIndex].text; }
                }
            });
        });
    });

    // Override assignment
    document.querySelectorAll('.override-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            if (!this.value) return;
            const assignmentId = this.dataset.assignmentId;
            const newEpicId = this.value;
            if (!confirm('Réaffecter cette anomalie ?')) { this.value = ''; return; }
            fetch('<?= url("wilaya/api/override-assignment") ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
                body: 'assignment_id=' + assignmentId + '&new_epic_id=' + newEpicId + '&reason=Modification manuelle'
            }).then(function(r){return r.json();}).then(function(data) {
                if (data.ok) location.reload();
                else alert('Erreur : ' + (data.error || 'inconnue'));
            });
        });
    });

    // Duration badge
    function updateDuration() {
        const s = document.getElementById('start_at').value, e = document.getElementById('end_at').value;
        const badge = document.getElementById('durationBadge');
        if (!s || !e) { badge.innerHTML = ''; return; }
        const diff = new Date(e) - new Date(s);
        if (diff <= 0) { badge.innerHTML = ''; return; }
        const hrs = Math.floor(diff / 3600000), mins = Math.floor((diff % 3600000) / 60000);
        badge.innerHTML = '<span class="duration-badge">' + hrs + 'h' + (mins > 0 ? mins + 'min' : '') + '</span>';
    }
    document.getElementById('start_at').addEventListener('change', updateDuration);
    document.getElementById('end_at').addEventListener('change', updateDuration);
    updateDuration();

    // Routing preview
    let routingTimer;
    function fetchRoutingPreview() {
        clearTimeout(routingTimer);
        routingTimer = setTimeout(function() {
            const communeId = communeSelect.value;
            const anomalyIds = [];
            document.querySelectorAll('.anomaly-toggle:checked').forEach(function(cb) { anomalyIds.push(cb.value); });
            if (!communeId || anomalyIds.length === 0) { document.getElementById('routingPreview').style.display = 'none'; return; }
            fetch('<?= url("wilaya/api/routing-preview") ?>?commune_id=' + communeId + '&anomalies[]=' + anomalyIds.join('&anomalies[]='),
                {headers: {'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest'}}
            ).then(function(r){return r.json();}).then(function(data) {
                if (!data.ok || !data.preview || data.preview.length === 0) { document.getElementById('routingPreview').style.display = 'none'; return; }
                let html = '<div class="mt-2"><strong><i class="mdi mdi-route me-1"></i>Aperçu du routage :</strong></div>';
                const epicMap = {};
                data.preview.forEach(function(p) {
                    const cls = p.epic_id ? '' : ' unrouted';
                    const icon = p.epic_id ? '<i class="mdi mdi-check-circle-outline"></i>' : '<i class="mdi mdi-alert-outline"></i>';
                    html += '<div class="routing-epic-tag' + cls + '">' + icon + ' ' + (p.epic_nom || 'Non routé') + ' <small style="opacity:.7">(' + p.anomalie_nom + ')</small></div>';
                    if (p.epic_id) epicMap[p.epic_id] = p.epic_nom;
                });
                if (Object.keys(epicMap).length > 0) {
                    html += '<div class="routing-preview-box mt-2"><strong>EPIC(s) :</strong> ';
                    Object.values(epicMap).forEach(function(n) { html += '<span class="badge bg-primary me-1">' + n + '</span>'; });
                    html += '</div>';
                } else {
                    html += '<div class="routing-preview-box no-match mt-2"><i class="mdi mdi-alert-circle me-1"></i>Aucune règle ne correspond.</div>';
                }
                const el = document.getElementById('routingPreview');
                el.innerHTML = html;
                el.style.display = 'block';
            }).catch(function(){});
        }, 350);
    }
    communeSelect.addEventListener('change', fetchRoutingPreview);
})();
</script>
