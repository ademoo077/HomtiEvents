<?php
/** @var array|null $association @var array $communes @var array $anomalies @var array $epics @var array $errors @var array $old */
/** @var array|null $event */
use App\Helpers\I18n;

$title = __('evenements.create');
$page  = 'association.create';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

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
#assocMap { height: 300px; z-index: 1; }
.assoc-map-pin { position: absolute; bottom: .75rem; right: .75rem; z-index: 10; }
.anomaly-card { border: 1px solid #e2e8f0; border-radius: .625rem; padding: .65rem .85rem; margin-bottom: .5rem; background: #fff; transition: all .2s; cursor: pointer; }
.anomaly-card:hover { border-color: var(--wh-purple, #7c3aed); box-shadow: 0 0 0 1px var(--wh-purple, #7c3aed); }
.anomaly-card.selected { border-color: var(--wh-purple, #7c3aed); background: #f5f3ff; }
.anomaly-card .anomaly-head { display: flex; align-items: center; gap: .5rem; }
.anomaly-card .anomaly-head input[type="checkbox"] { width: 1.1em; height: 1.1em; accent-color: var(--wh-purple, #7c3aed); }
.anomaly-card .anomaly-gps { display: none; margin-top: .5rem; }
.anomaly-card.selected .anomaly-gps { display: block; }
.gps-row { display: flex; gap: .5rem; align-items: end; }
.gps-row input { flex: 1; font-size: .8rem; }
.btn-pin { font-size: .7rem; padding: .25rem .5rem; white-space: nowrap; }
.routing-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: .5rem; padding: .65rem .85rem; margin-top: .5rem; font-size: .85rem; }
.routing-box.no-match { background: #fef2f2; border-color: #fca5a5; }
.epic-tag { display: inline-flex; align-items: center; gap: .3rem; background: #dbeafe; color: #1e40af; border-radius: 9999px; padding: .2rem .55rem; font-size: .75rem; font-weight: 500; margin: .15rem; }
.epic-tag.unrouted { background: #fee2e2; color: #991b1b; }
</style>

<div class="wh-page">
    <div style="background:linear-gradient(135deg,#198754 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-calendar-plus"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('evenements.create')) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= e($association['nom'] ?? '') ?> — <?= $isAr ? 'قم بوصف النشاط الذي ترغب في تنظيمه' : 'Décrivez l\'activité que vous souhaitez organiser' ?></p>
                </div>
            </div>
            <a class="btn btn-light btn-sm" href="<?= url('association') ?>">
                <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
            </a>
        </div>
    </div>

    <?php if ($association): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius);overflow:hidden;">
        <div style="padding:.65rem 1.25rem;background:var(--wh-green-soft);border-bottom:1px solid #b7e4c7;display:flex;align-items:center;gap:.5rem;">
            <span style="width:28px;height:28px;border-radius:7px;background:rgba(25,135,84,.15);display:grid;place-items:center;color:var(--wh-green);font-size:.85rem;"><i class="mdi mdi-hand-heart-outline"></i></span>
            <span class="fw-bold" style="font-size:.88rem;"><?= e((string) $association['nom']) ?></span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <?php if (! empty($association['numero_agrement'])): ?>
                    <span class="wh-badge badge-blue"><i class="mdi mdi-certificate-outline me-1"></i><?= e((string) $association['numero_agrement']) ?></span>
                <?php endif; ?>
                <?= association_badge($association) ?>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1">
                    <div class="small text-muted d-flex gap-3 flex-wrap">
                        <?php if (! empty($association['nom_prenom_president'])): ?>
                            <span><i class="mdi mdi-account-tie-outline me-1"></i><?= e((string) $association['nom_prenom_president']) ?></span>
                        <?php endif; ?>
                        <?php if (! empty($association['email'])): ?>
                            <span><i class="mdi mdi-email-outline me-1"></i><?= e((string) $association['email']) ?></span>
                        <?php endif; ?>
                        <?php if (! empty($association['telephone'])): ?>
                            <span><i class="mdi mdi-phone-outline me-1"></i><?= e((string) $association['telephone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ ASSISTANT DE PRÉPARATION (qualité du dossier en direct) ═══ -->
    <?php if ($association): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius);overflow:hidden;">
        <div style="padding:.65rem 1.25rem;background:linear-gradient(90deg,#0B5ED7,#7c3aed);display:flex;align-items:center;gap:.5rem;color:#fff;">
            <span style="width:28px;height:28px;border-radius:7px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:.9rem;"><i class="mdi mdi-robot-happy-outline"></i></span>
            <span class="fw-bold" style="font-size:.9rem;"><?= $isAr ? 'مساعد الإعداد' : 'Assistant de préparation' ?></span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="badge bg-white text-dark" id="prepScoreBadge" style="min-width:56px">0%</span>
            </div>
        </div>
        <div class="card-body">
            <div class="progress mb-2" style="height:8px;background:#e9ecef;">
                <div class="progress-bar" id="prepBar" style="width:0%;background:var(--wh-red);transition:width .25s ease"></div>
            </div>
            <div class="small text-muted mb-2" id="prepHint">
                <i class="mdi mdi-information-outline me-1"></i><?= $isAr ? 'املأ الملف للحصول على ...' : 'Renseignez le dossier pour voir la qualité de préparation et les éléments manquants.' ?>
            </div>
            <div id="prepItems" class="d-flex flex-wrap gap-1"></div>
            <div id="prepRouting" class="mt-2 small"></div>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= url('association') ?>" id="create-event-form" novalidate>
        <?= csrf_field() ?>

        <!-- Carte + Adresse -->
        <div class="futur-card mb-4">
            <div class="futur-card-header d-flex justify-content-between align-items-center" style="padding:.65rem 1.25rem;background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;">
                <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);font-size:.85rem;"><i class="mdi mdi-map-marker-radius"></i></span> <?= e(__('common.adresse')) ?></span>
                <div class="d-flex align-items-center gap-2">
                    <input type="hidden" id="latitude" name="latitude" value="<?= e($old['latitude'] ?? '') ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?= e($old['longitude'] ?? '') ?>">
                </div>
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
                        <div class="futur-search" style="max-width: 100%;">
                            <i class="mdi mdi-magnify"></i>
                            <select class="futur-form-control <?= isset($errors['commune_id']) ? 'is-invalid' : '' ?>" id="commune_id" name="commune_id" required data-route-refresh style="padding-inline-start: 40px;">
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
                        <?php if (isset($errors['commune_id'])): ?><div class="futur-form-error"><?= e($errors['commune_id']) ?></div><?php endif; ?>
                    </div>

                    <div class="futur-form-group">
                        <label class="futur-form-label" for="adresse"><?= e(__('common.adresse')) ?> <span class="required">*</span></label>
                        <input type="text" class="futur-form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" id="adresse" name="adresse" value="<?= e($old['adresse'] ?? ($event['adresse'] ?? '')) ?>" required minlength="5" placeholder="<?= $isAr ? 'عنوان النشاط بالتفصيل' : 'Adresse détaillée de l\'activité' ?>">
                        <?php if (isset($errors['adresse'])): ?><div class="futur-form-error"><?= e($errors['adresse']) ?></div><?php endif; ?>
                    </div>
                </div>
                <small class="text-muted"><?= $isAr ? 'انقر على الخريطة لتحديد الموقع' : 'Cliquez sur la carte pour placer un marqueur' ?></small>
            </div>
        </div>

        <!-- Informations générales -->
        <div class="futur-card mb-4">
            <div class="futur-card-header" style="padding:.65rem 1.25rem;background:#ede9fe;border-bottom:1px solid #ddd6fe;">
                <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(124,58,237,.15);display:grid;place-items:center;color:#7c3aed;font-size:.85rem;"><i class="mdi mdi-file-document-outline"></i></span> <?= e(__('common.informations')) ?></span>
            </div>
            <div class="futur-card-body">
                <div class="futur-form-row">
                    <div class="futur-form-group">
                        <label class="futur-form-label" for="capacite"><?= $isAr ? 'السعة القصوى (مشارك)' : 'Capacité maximale (participants)' ?></label>
                        <input type="number" class="futur-form-control <?= isset($errors['capacite']) ? 'is-invalid' : '' ?>" id="capacite" name="capacite" min="1" step="1" value="<?= e($old['capacite'] ?? ($event['capacite'] ?? '')) ?>" placeholder="200">
                        <div class="futur-form-hint"><?= $isAr ? 'اختياري' : 'Optionnel — quota de passages via QR' ?></div>
                        <?php if (isset($errors['capacite'])): ?><div class="futur-form-error"><?= e($errors['capacite']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="futur-form-group">
                    <label class="futur-form-label" for="description"><?= e(__('common.description')) ?> <span class="required">*</span></label>
                    <textarea class="futur-form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="4" required minlength="10" placeholder="<?= $isAr ? 'وصف مفصل للنشاط...' : 'Description détaillée de l\'activité...' ?>"><?= e($old['description'] ?? ($event['description'] ?? '')) ?></textarea>
                    <?php if (isset($errors['description'])): ?><div class="futur-form-error"><?= e($errors['description']) ?></div><?php endif; ?>
                </div>

                <div class="futur-form-group">
                    <label class="futur-form-label" for="informations"><?= e(__('evenements.complementaires')) ?></label>
                    <textarea class="futur-form-control" id="informations" name="informations" rows="3" placeholder="<?= $isAr ? 'معلومات إضافية...' : 'Informations complémentaires...' ?>"><?= e($old['informations'] ?? ($event['informations_complementaires'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Anomalies avec GPS par anomalie -->
        <div class="futur-card mb-4">
            <div class="futur-card-header d-flex justify-content-between align-items-center" style="padding:.65rem 1.25rem;background:#fef3c7;border-bottom:1px solid #fde68a;">
                <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(245,158,11,.15);display:grid;place-items:center;color:var(--wh-amber);font-size:.85rem;"><i class="mdi mdi-alert-octagon"></i></span> <?= e(__('evenements.anomalies')) ?> <span class="required">*</span></span>
                <small class="text-muted"><?= $isAr ? 'حدد الكائنات' : 'Sélectionnez et géolocalisez chaque anomalie' ?></small>
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
                            $isChecked = in_array($aId, (array) ($old['anomalies'] ?? []), true);
                        ?>
                            <div class="anomaly-card <?= $isChecked ? 'selected' : '' ?>" data-a="<?= $aId ?>">
                                <div class="anomaly-head">
                                    <input class="form-check-input anomaly-toggle" type="checkbox" name="anomalies[]" value="<?= $aId ?>" <?= $isChecked ? 'checked' : '' ?>>
                                    <?php $icon = !empty($a['icone']) ? $a['icone'] : 'alert-circle-outline'; ?>
                                    <?php $color = !empty($a['couleur']) ? $a['couleur'] : 'var(--wh-blue)'; ?>
                                    <i class="mdi mdi-<?= e($icon) ?>" style="color: <?= e($color) ?>"></i>
                                    <span class="fw-semibold"><?= e($a['nom']) ?></span>
                                </div>
                                <div class="anomaly-gps">
                                    <div class="gps-row">
                                        <input type="number" step="any" class="futur-form-control form-control-sm" name="anomaly_lat[<?= $aId ?>]" placeholder="Latitude">
                                        <input type="number" step="any" class="futur-form-control form-control-sm" name="anomaly_lng[<?= $aId ?>]" placeholder="Longitude">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-pin" data-a="<?= $aId ?>" title="Pointer sur la carte">
                                            <i class="mdi mdi-map-marker-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['anomalies'])): ?><div class="futur-form-error"><?= e($errors['anomalies']) ?></div><?php endif; ?>
                <div id="routingPreview" style="display:none;"></div>
            </div>
        </div>

        <!-- ═══ MODÈLES DE DEMANDE (réutilisables) ═══ -->
        <div class="futur-card mb-4">
            <div class="futur-card-header d-flex justify-content-between align-items-center" style="padding:.65rem 1.25rem;background:#e0f2fe;border-bottom:1px solid #bae6fd;">
                <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(14,165,233,.15);display:grid;place-items:center;color:#0284c7;font-size:.85rem;"><i class="mdi mdi-content-save-outline"></i></span> <?= $isAr ? 'قوالب الطلبات' : 'Modèles de demande' ?></span>
                <small class="text-muted"><?= $isAr ? 'احفظ نموذجًا لإعادة استخدامه' : 'Enregistrez un modèle pour réutiliser une demande type' ?></small>
            </div>
            <div class="futur-card-body">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="futur-search" style="flex:1;min-width:220px;">
                        <i class="mdi mdi-folder-outline"></i>
                        <select class="futur-form-control" id="modeleSelect" style="padding-inline-start:40px;">
                            <option value=""><?= $isAr ? '…اختر نموذجًا' : 'Choisir un modèle…' ?></option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnModeleApply" disabled>
                        <i class="mdi mdi-backup-restore me-1"></i><?= $isAr ? 'تطبيق' : 'Appliquer' ?>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnModeleDelete" disabled>
                        <i class="mdi mdi-trash-can-outline me-1"></i><?= $isAr ? 'حذف' : 'Supprimer' ?>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnModeleSave">
                        <i class="mdi mdi-content-save me-1"></i><?= $isAr ? 'حفظ الحالية كنموذج' : 'Enregistrer comme modèle' ?>
                    </button>
                </div>
                <div class="small mt-2" style="color:var(--wh-text-muted)" id="modeleMsg"></div>
            </div>
        </div>

        <div class="futur-card mb-4">
            <div class="futur-card-body">
                <div class="futur-form-actions">
                    <a href="<?= url('association') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="mdi mdi-send me-1"></i> <?= e(__('common.save')) ?>
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

    // Set pin from main marker
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
                    el.innerHTML = '<div class="routing-box no-match"><i class="mdi mdi-alert-circle me-1"></i><?= $isAr ? 'لم يتم تحديد جهة' : 'Aucune règle de routage ne correspond' ?></div>';
                }
            }).catch(function(){ el.style.display='none'; });
        }, 300);
    }
    document.getElementById('commune_id').addEventListener('change', function(){ fetchPreview(); computePrep(); });

    // ── Assistant de préparation : score & checklist en direct ──
    var prepItems = [];
    function computePrep(){
        var g = function(id){ var el=document.getElementById(id); return el?el.value.trim():''; };
        var commune = g('commune_id')!=='';
        var adresse = g('adresse').length>=5;
        var descr   = g('description').length>=10;
        var capa    = document.getElementById('capacite') ? document.getElementById('capacite').value!=='' : false;
        var nAno    = document.querySelectorAll('.anomaly-toggle:checked').length;
        var gps     = g('latitude')!=='' && g('longitude')!=='';

        var items = [
            {k:'commune', ok:commune, w:20, lbl:'<?= $isAr ? "البلدية" : "Commune" ?>'},
            {k:'adresse', ok:adresse, w:20, lbl:'<?= $isAr ? "العنوان" : "Adresse" ?>'},
            {k:'description', ok:descr, w:20, lbl:'<?= $isAr ? "الوصف" : "Description" ?>'},
            {k:'anomalies', ok:nAno>=1, w:25, lbl:'<?= $isAr ? "الكائنات (≥1)" : "Anomalies (≥1)" ?>'},
            {k:'gps', ok:gps, w:15, lbl:'<?= $isAr ? "الموقع (GPS)" : "Localisation (GPS)" ?>'}
        ];
        var score = 0;
        items.forEach(function(it){ if(it.ok) score += it.w; });
        var miss = items.filter(function(it){ return !it.ok; });

        var bar = document.getElementById('prepBar');
        var badge = document.getElementById('prepScoreBadge');
        var hint = document.getElementById('prepHint');
        var box = document.getElementById('prepItems');
        if(bar) bar.style.width = score+'%';
        if(bar) bar.style.background = score>=90 ? 'var(--wh-green)' : (score>=60 ? 'var(--wh-amber)' : 'var(--wh-red)');
        if(badge) badge.textContent = score+'%';
        if(box){
            box.innerHTML = miss.map(function(it){
                return '<span class="badge bg-secondary-subtle text-secondary"><i class="mdi mdi-close-circle me-1"></i>'+it.lbl+'</span>';
            }).join('') || '<span class="badge bg-success-subtle text-success"><i class="mdi mdi-check-circle me-1"></i><?= $isAr ? "اكتمل الملف" : "Dossier complet" ?></span>';
        }
        if(hint){
            if(score>=90){ hint.innerHTML = '<i class="mdi mdi-check-decagram me-1"></i><?= $isAr ? "الملف جاهز للإرسال" : "Votre demande est prête à être soumise." ?>'; }
            else if(miss.length){ hint.innerHTML = '<i class="mdi mdi-information-outline me-1"></i><?= $isAr ? "عناصر ناقصة" : "Éléments manquants" ?>: '+miss.map(function(m){return m.lbl;}).join(', '); }
            else { hint.innerHTML = '<i class="mdi mdi-information-outline me-1"></i><?= $isAr ? "املأ الملف للحصول على تقييم" : "Renseignez le dossier pour voir la qualité." ?>'; }
        }
        prepItems = miss;
    }

    ['adresse','description','capacite','latitude','longitude'].forEach(function(id){
        var el=document.getElementById(id); if(el) el.addEventListener('input', computePrep);
    });
    document.querySelectorAll('.anomaly-toggle').forEach(function(cb){ cb.addEventListener('change', computePrep); });
    computePrep();

    // ── Modèles de demande ──
    var modeleData = [];
    var modeleSel = document.getElementById('modeleSelect');
    var btnApply = document.getElementById('btnModeleApply');
    var btnDel = document.getElementById('btnModeleDelete');
    var modeleMsg = document.getElementById('modeleMsg');

    function refreshModelesUI(){
        var has = modeleData.length > 0 && modeleData.some(function(t){ return String(t.id) === modeleSel.value; });
        btnApply.disabled = !has;
        btnDel.disabled = !has;
    }

    function loadModeles(){
        fetch('<?= url("association/modeles") ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){ return r.json(); })
            .then(function(data){
                if(!data || !data.success) return;
                modeleData = data.templates;
                modeleSel.innerHTML = '<option value=""><?= $isAr ? "…اختر نموذجًا" : "Choisir un modèle…" ?></option>'
                    + modeleData.map(function(t){
                        var name = t.nom + (t.commune_nom ? ' — ' + t.commune_nom : '');
                        return '<option value="'+t.id+'">'+name+'</option>';
                    }).join('');
                refreshModelesUI();
            }).catch(function(){});
    }

    function setField(id, value){
        var el = document.getElementById(id);
        if(el) el.value = (value === null || value === undefined) ? '' : value;
    }

    btnApply.addEventListener('click', function(){
        var t = modeleData.find(function(x){ return String(x.id) === modeleSel.value; });
        if(!t) return;
        setField('commune_id', t.commune_id);
        setField('adresse', t.adresse);
        setField('capacite', t.capacite);
        setField('description', t.description);
        setField('informations', t.informations);
        document.querySelectorAll('.anomaly-toggle').forEach(function(cb){
            cb.checked = (t.anomalies||[]).map(String).indexOf(cb.value) > -1;
            cb.closest('.anomaly-card').classList.toggle('selected', cb.checked);
            cb.dispatchEvent(new Event('change'));
        });
        var ev = document.getElementById('commune_id');
        if(ev) ev.dispatchEvent(new Event('change'));
        computePrep(); fetchPreview();
        modeleMsg.innerHTML = '<i class="mdi mdi-check-circle text-success me-1"></i><?= $isAr ? "تم تطبيق النموذج" : "Modèle appliqué." ?>';
    });

    btnDel.addEventListener('click', function(){
        var t = modeleData.find(function(x){ return String(x.id) === modeleSel.value; });
        if(!t) return;
        if(!confirm('<?= $isAr ? "حذف النموذج؟" : "Supprimer ce modèle ?" ?>')) return;
        fetch('<?= url("association/modeles") ?>/'+t.id+'/delete', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body:'_token='+encodeURIComponent(csrfToken)
        }).then(function(r){ return r.json(); }).then(function(data){
            if(data && data.success){ modeleMsg.innerHTML = '<i class="mdi mdi-check-circle me-1"></i><?= $isAr ? "تم الحذف" : "Modèle supprimé." ?>'; loadModeles(); }
        });
    });

    btnModeleSave.addEventListener('click', function(){
        var nom = prompt('<?= $isAr ? "اسم النموذج" : "Nom du modèle" ?>');
        if(nom === null) return;
        var description = document.getElementById('description').value;
        var informations = document.getElementById('informations').value;
        var ids = [];
        document.querySelectorAll('.anomaly-toggle:checked').forEach(function(cb){ ids.push(cb.value); });
        var body = '_token='+encodeURIComponent(csrfToken)
            +'&nom='+encodeURIComponent(nom)
            +'&commune_id=' + encodeURIComponent(document.getElementById('commune_id').value)
            +'&adresse=' + encodeURIComponent(document.getElementById('adresse').value)
            +'&capacite=' + encodeURIComponent(document.getElementById('capacite').value)
            +'&description=' + encodeURIComponent(description)
            +'&informations=' + encodeURIComponent(informations)
            +'&anomalies[]=' + ids.join('&anomalies[]=');
        fetch('<?= url("association/modeles") ?>', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
            body: body
        }).then(function(r){ return r.json(); }).then(function(data){
            if(data && data.success){
                modeleMsg.innerHTML = '<i class="mdi mdi-check-circle text-success me-1"></i><?= $isAr ? "تم حفظ النموذج" : "Modèle enregistré." ?>';
                loadModeles();
            }
        });
    });

    modeleSel.addEventListener('change', refreshModelesUI);
    loadModeles();
})();
</script>
