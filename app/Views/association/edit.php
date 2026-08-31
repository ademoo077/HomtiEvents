<?php
/** @var array $event @var array $communes @var array $anomalies @var array $epics @var array $selectedAnomalies @var array $assignedEpics @var array $errors @var array $old
 *  @var array $anomalyDetails @var array $assignments
 */
use App\Helpers\I18n;

$title = __('common.edit') . ' — ' . e(mb_substr((string) ($event['adresse'] ?? ''), 0, 40));
$page  = 'association.edit';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$anomalyDetails = $anomalyDetails ?? [];
$assignments = $assignments ?? [];

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
.assoc-map-wrap { position: relative; border-radius: .75rem; overflow: hidden; margin-bottom: 1rem; }
#assocMap { height: 280px; z-index: 1; }
.assoc-map-pin { position: absolute; bottom: .75rem; right: .75rem; z-index: 10; }
.anomaly-card { border: 1px solid #e2e8f0; border-radius: .625rem; padding: .65rem .85rem; margin-bottom: .5rem; background: #fff; transition: all .2s; }
.anomaly-card:hover { border-color: var(--wh-purple, #7c3aed); }
.anomaly-card.selected { border-color: var(--wh-purple, #7c3aed); background: #f5f3ff; }
.anomaly-card .anomaly-head { display: flex; align-items: center; gap: .5rem; }
.anomaly-card .anomaly-head input[type="checkbox"] { width: 1.1em; height: 1.1em; accent-color: var(--wh-purple, #7c3aed); }
.anomaly-card .anomaly-gps { display: none; margin-top: .5rem; }
.anomaly-card.selected .anomaly-gps { display: block; }
.gps-row { display: flex; gap: .5rem; align-items: end; }
.gps-row input { flex: 1; font-size: .8rem; }
.btn-pin { font-size: .7rem; padding: .25rem .5rem; white-space: nowrap; }
.assignment-row { display: flex; align-items: center; gap: .6rem; padding: .45rem .7rem; border: 1px solid #e2e8f0; border-radius: .5rem; margin-bottom: .35rem; background: #fafafa; font-size: .85rem; }
.assignment-row .badge { font-size: .65rem; }
.routing-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: .5rem; padding: .65rem .85rem; margin-top: .5rem; font-size: .85rem; }
.routing-box.no-match { background: #fef2f2; border-color: #fca5a5; }
.anomaly-status-badge { font-size: .7rem; padding: .15rem .45rem; border-radius: 9999px; }
</style>

<div class="wh-page">
    <div style="background:linear-gradient(135deg,#198754 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-calendar-edit"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('common.edit')) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= e(mb_substr((string) ($event['adresse'] ?? ''), 0, 40)) ?> — <?= $isAr ? 'تعديل الفعالية' : 'Modification de l\'événement' ?></p>
                </div>
            </div>
            <a class="btn btn-light btn-sm" href="<?= url('association') ?>">
                <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
            </a>
        </div>
    </div>

    <form method="post" action="<?= url('association/' . (int) $event['id'] . '/update') ?>" novalidate>
        <?= csrf_field() ?>

        <!-- Carte + Adresse -->
        <div class="futur-card mb-4">
            <div class="futur-card-header d-flex justify-content-between align-items-center" style="padding:.65rem 1.25rem;background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;">
                <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);font-size:.85rem;"><i class="mdi mdi-map-marker-radius"></i></span> <?= e(__('common.adresse')) ?></span>
                <input type="hidden" id="latitude" name="latitude" value="<?= e((string) ($event['latitude'] ?? '')) ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?= e((string) ($event['longitude'] ?? '')) ?>">
            </div>
            <div class="futur-card-body">
                <div class="assoc-map-wrap">
                    <div id="assocMap"></div>
                    <div class="assoc-map-pin">
                        <button type="button" class="btn btn-sm btn-light shadow" id="btnMyLoc" title="Ma position">
                            <i class="mdi mdi-crosshairs-gps"></i>
                        </button>
                    </div>
                </div>

                <div class="futur-form-row">
                    <div class="futur-form-group">
                        <label class="futur-form-label" for="commune_id"><?= e(__('common.commune')) ?> <span class="required">*</span></label>
                        <select class="futur-form-control" id="commune_id" name="commune_id" required>
                            <option value=""><?= $isAr ? 'اختر' : 'Choisir' ?></option>
                            <?php
                            $grouped = [];
                            foreach ($communes as $c) {
                                $caId = (int) ($c['ca_id'] ?? 0);
                                if (! isset($grouped[$caId])) {
                                    $grouped[$caId] = ['label' => $c['daira_nom'] ?? '', 'options' => []];
                                }
                                $grouped[$caId]['options'][] = $c;
                            }
                            foreach ($grouped as $group): ?>
                                <optgroup label="<?= e($group['label']) ?>">
                                    <?php foreach ($group['options'] as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>" data-lat="<?= e((string) ($c['latitude'] ?? '')) ?>" data-lng="<?= e((string) ($c['longitude'] ?? '')) ?>" <?= (($old['commune_id'] ?? ($event['commune_id'] ?? '')) == $c['id']) ? 'selected' : '' ?>>
                                            <?= e($c['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="futur-form-group">
                        <label class="futur-form-label" for="adresse"><?= e(__('common.adresse')) ?> <span class="required">*</span></label>
                        <input type="text" class="futur-form-control" id="adresse" name="adresse" value="<?= e($old['adresse'] ?? ($event['adresse'] ?? '')) ?>" required minlength="5">
                    </div>
                </div>

                <?php if (!empty($event['latitude']) && !empty($event['longitude'])): ?>
                    <div class="mt-2">
                        <a href="https://www.openstreetmap.org/?mlat=<?= e((string) $event['latitude']) ?>&mlon=<?= e((string) $event['longitude']) ?>#map=15" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="mdi mdi-map me-1"></i>Voir sur OpenStreetMap
                        </a>
                        <small class="text-muted ms-2"><?= e((string) $event['latitude']) ?>, <?= e((string) $event['longitude']) ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informations générales -->
        <div class="futur-card mb-4">
            <div class="futur-card-body">
                <div class="futur-form-group">
                    <label class="futur-form-label" for="description"><?= e(__('common.description')) ?> <span class="required">*</span></label>
                    <textarea class="futur-form-control" id="description" name="description" rows="4" required minlength="10"><?= e($old['description'] ?? ($event['description'] ?? '')) ?></textarea>
                </div>

                <div class="futur-form-group">
                    <label class="futur-form-label" for="informations"><?= e(__('evenements.complementaires')) ?></label>
                    <textarea class="futur-form-control" id="informations" name="informations" rows="3"><?= e($old['informations'] ?? ($event['informations_complementaires'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Anomalies avec GPS + statut -->
        <div class="futur-card mb-4">
            <div class="futur-card-header d-flex justify-content-between align-items-center" style="padding:.65rem 1.25rem;background:#fef3c7;border-bottom:1px solid #fde68a;">
                <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(245,158,11,.15);display:grid;place-items:center;color:var(--wh-amber);font-size:.85rem;"><i class="mdi mdi-alert-octagon"></i></span> <?= e(__('evenements.anomalies')) ?> <span class="required">*</span></span>
                <small class="text-muted"><?= $isAr ? 'حدد الكائنات' : 'Statut et géolocalisation par anomalie' ?></small>
            </div>
            <div class="futur-card-body">
                <div id="anomalyList">
                    <?php foreach ($anomaliesParEpic as $group): ?>
                        <?php if ($group['epic_nom'] !== null): ?>
                            <div class="wh-anomalies-epic-title mb-2 mt-2">
                                <i class="mdi mdi-package-variant-closed me-1"></i><?= e($group['epic_nom']) ?>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($group['items'] as $a):
                            $aId = (int) $a['id'];
                            $isChecked = in_array($aId, $selectedAnomalies, true);
                            $detail = null;
                            foreach ($anomalyDetails as $ad) {
                                if ((int) $ad['anomalie_id'] === $aId) { $detail = $ad; break; }
                            }
                        ?>
                            <div class="anomaly-card <?= $isChecked ? 'selected' : '' ?>" data-a="<?= $aId ?>">
                                <div class="anomaly-head">
                                    <input class="form-check-input anomaly-toggle" type="checkbox" name="anomalies[]" value="<?= $aId ?>" <?= $isChecked ? 'checked' : '' ?>>
                                    <?php $icon = !empty($a['icone']) ? $a['icone'] : 'alert-circle-outline'; ?>
                                    <?php $color = !empty($a['couleur']) ? $a['couleur'] : 'var(--wh-blue)'; ?>
                                    <i class="mdi mdi-<?= e($icon) ?>" style="color: <?= e($color) ?>"></i>
                                    <span class="fw-semibold"><?= e($a['nom']) ?></span>
                                    <?php if ($detail && $detail['statut']): ?>
                                        <?php
                                        $st = (string) $detail['statut'];
                                        $stColor = match($st) { 'DETECTEE' => '#f59e0b', 'EN_COURS' => '#3b82f6', 'RESOLUE' => '#22c55e', 'REJETEE' => '#ef4444', default => '#6b7280' };
                                        ?>
                                        <span class="anomaly-status-badge ms-auto" style="background:<?= $stColor ?>20; color:<?= $stColor ?>;"><?= e($st) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="anomaly-gps">
                                    <div class="gps-row">
                                        <input type="number" step="any" class="futur-form-control form-control-sm" name="anomaly_lat[<?= $aId ?>]" placeholder="Lat" value="<?= e((string) ($detail['latitude'] ?? '')) ?>">
                                        <input type="number" step="any" class="futur-form-control form-control-sm" name="anomaly_lng[<?= $aId ?>]" placeholder="Lng" value="<?= e((string) ($detail['longitude'] ?? '')) ?>">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-pin" data-a="<?= $aId ?>">
                                            <i class="mdi mdi-map-marker-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['anomalies'])): ?><div class="futur-form-error"><?= e($errors['anomalies']) ?></div><?php endif; ?>

                <?php if ($assignments !== []): ?>
                <div class="mt-3">
                    <strong class="small"><i class="mdi mdi-office-building-outline me-1"></i><?= $isAr ? 'الجهات المكلفة' : 'Assignations EPIC par anomalie' ?></strong>
                    <?php foreach ($assignments as $as): ?>
                        <div class="assignment-row">
                            <span class="fw-semibold" style="min-width:110px;"><?= e((string) $as['anomalie_nom']) ?></span>
                            <i class="mdi mdi-arrow-right-bold text-muted"></i>
                            <span class="badge bg-primary"><?= e((string) $as['epic_nom']) ?></span>
                            <span class="badge bg-<?= $as['auto_routed'] ? 'success' : 'warning text-dark' ?>" style="font-size:.6rem;"><?= $as['auto_routed'] ? 'Auto' : 'Manuel' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div id="routingPreview" style="display:none;"></div>
            </div>
        </div>

        <div class="futur-card mb-4">
            <div class="futur-card-body">
                <div class="futur-form-actions">
                    <a href="<?= url('association') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="mdi mdi-content-save me-1"></i> <?= e(__('common.save')) ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    var communeData = <?= json_encode($communeData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    var csrfToken = window.WH_CSRF || '<?= csrf_token() ?>';
    var map, mainPin;

    var initLat = document.getElementById('latitude').value;
    var initLng = document.getElementById('longitude').value;
    var center = initLat && initLng ? [parseFloat(initLat), parseFloat(initLng)] : [36.7538, 3.0588];

    map = L.map('assocMap', {zoomControl:true, scrollWheelZoom:true}).setView(center, initLat ? 13 : 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OSM', maxZoom:19}).addTo(map);

    if (initLat && initLng) {
        mainPin = L.marker(center, {draggable:true}).addTo(map);
        mainPin.on('dragend', function(e){
            var p = e.target.getLatLng();
            document.getElementById('latitude').value = p.lat.toFixed(7);
            document.getElementById('longitude').value = p.lng.toFixed(7);
        });
    }

    map.on('click', function(e){
        var lat = e.latlng.lat, lng = e.latlng.lng;
        document.getElementById('latitude').value = lat.toFixed(7);
        document.getElementById('longitude').value = lng.toFixed(7);
        if (mainPin) { mainPin.setLatLng([lat,lng]); }
        else {
            mainPin = L.marker([lat,lng],{draggable:true}).addTo(map);
            mainPin.on('dragend', function(ev){
                var p = ev.target.getLatLng();
                document.getElementById('latitude').value = p.lat.toFixed(7);
                document.getElementById('longitude').value = p.lng.toFixed(7);
            });
        }
    });

    document.getElementById('commune_id').addEventListener('change', function(){
        var c = communeData[parseInt(this.value)];
        if (c && c.lat && c.lng) {
            map.flyTo([c.lat,c.lng], 13, {duration:0.8});
            document.getElementById('latitude').value = c.lat.toFixed(7);
            document.getElementById('longitude').value = c.lng.toFixed(7);
            if (!mainPin) {
                mainPin = L.marker([c.lat,c.lng],{draggable:true}).addTo(map);
                mainPin.on('dragend', function(e){
                    var p = e.target.getLatLng();
                    document.getElementById('latitude').value = p.lat.toFixed(7);
                    document.getElementById('longitude').value = p.lng.toFixed(7);
                });
            } else { mainPin.setLatLng([c.lat,c.lng]); }
        }
    });

    document.getElementById('btnMyLoc').addEventListener('click', function(){
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(function(pos){
            var lat=pos.coords.latitude, lng=pos.coords.longitude;
            map.flyTo([lat,lng],15,{duration:0.8});
            document.getElementById('latitude').value=lat.toFixed(7);
            document.getElementById('longitude').value=lng.toFixed(7);
            if (mainPin) mainPin.setLatLng([lat,lng]);
            else {
                mainPin=L.marker([lat,lng],{draggable:true}).addTo(map);
                mainPin.on('dragend',function(e){var p=e.target.getLatLng();document.getElementById('latitude').value=p.lat.toFixed(7);document.getElementById('longitude').value=p.lng.toFixed(7);});
            }
        });
    });

    // Anomaly toggle
    document.querySelectorAll('.anomaly-toggle').forEach(function(cb){
        cb.addEventListener('change', function(){
            this.closest('.anomaly-card').classList.toggle('selected', this.checked);
            fetchPreview();
        });
    });

    // Set pin
    document.querySelectorAll('.btn-pin').forEach(function(btn){
        btn.addEventListener('click', function(){
            if (!mainPin) return;
            var aId = this.dataset.a;
            var pos = mainPin.getLatLng();
            var card = document.querySelector('.anomaly-card[data-a="'+aId+'"]');
            if (!card) return;
            card.querySelector('input[name="anomaly_lat['+aId+']"]').value = pos.lat.toFixed(7);
            card.querySelector('input[name="anomaly_lng['+aId+']"]').value = pos.lng.toFixed(7);
        });
    });

    // Routing preview
    var timer;
    function fetchPreview(){
        clearTimeout(timer);
        timer = setTimeout(function(){
            var communeId = document.getElementById('commune_id').value;
            var ids = [];
            document.querySelectorAll('.anomaly-toggle:checked').forEach(function(cb){ ids.push(cb.value); });
            var el = document.getElementById('routingPreview');
            if (!communeId || ids.length===0) { el.style.display='none'; return; }
            el.style.display='block';
            el.innerHTML = '<div class="routing-box"><i class="mdi mdi-loading mdi-spin me-1"></i>Calcul...</div>';

            fetch('<?= url("association/routing-preview") ?>', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
                body:'commune_id='+communeId+'&anomalies[]='+ids.join('&anomalies[]=')+'&_token='+encodeURIComponent(csrfToken)
            }).then(function(r){return r.json();}).then(function(data){
                if (data && data.success && data.epic) {
                    el.innerHTML = '<div class="routing-box"><strong><i class="mdi mdi-check-circle-outline me-1"></i>'+data.epic.nom+'</strong><br><small class="text-muted">'+data.detail+'</small></div>';
                } else {
                    el.innerHTML = '<div class="routing-box no-match"><i class="mdi mdi-alert-circle me-1"></i>Aucune règle de routage ne correspond.</div>';
                }
            }).catch(function(){ el.style.display='none'; });
        }, 300);
    }
    document.getElementById('commune_id').addEventListener('change', fetchPreview);
})();
</script>
