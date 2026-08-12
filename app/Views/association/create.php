<?php
/** @var array|null $association @var array $communes @var array $anomalies @var array $epics @var array $errors @var array $old */
/** @var array|null $event */
use App\Helpers\I18n;

$title = __('evenements.create');
$page  = 'association.create';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="wh-page-title-icon blue"><i class="mdi mdi-calendar-plus"></i></div>
            <div>
                <h1 class="wh-page-title"><?= e(__('evenements.create')) ?></h1>
                <p class="wh-page-sub"><?= $isAr ? 'قم بوصف النشاط الذي ترغب في تنظيمه' : 'Décrivez l\'activité que vous souhaitez organiser' ?></p>
            </div>
        </div>
        <a class="btn btn-outline-secondary" href="<?= url('association') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
        </a>
    </div>

    <?php if ($association): ?>
    <div class="alert alert-light border d-flex flex-wrap align-items-center gap-3 mb-4 shadow-sm">
        <div class="wh-page-title-icon green"><i class="mdi mdi-hand-heart-outline"></i></div>
        <div class="flex-grow-1">
            <div class="fw-bold"><?= e((string) $association['nom']) ?></div>
            <div class="small text-muted d-flex gap-3 flex-wrap">
                <?php if (! empty($association['numero_agrement'])): ?>
                    <span><i class="mdi mdi-certificate-outline me-1"></i><?= e((string) $association['numero_agrement']) ?></span>
                <?php endif; ?>
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
        <?= association_badge($association) ?>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= url('association') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label wh-label-icon" for="commune_id">
                        <i class="mdi mdi-map-marker-outline"></i><?= e(__('common.commune')) ?>
                    </label>
                    <div class="wh-input-icon-wrap">
                        <i class="mdi mdi-earth"></i>
                        <select class="form-select" id="commune_id" name="commune_id" data-route-refresh>
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
                                        <option value="<?= (int) $c['id'] ?>" <?= (($old['commune_id'] ?? ($event['commune_id'] ?? '')) == $c['id']) ? 'selected' : '' ?>>
                                            <?= e($c['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($errors['commune_id'])): ?><div class="text-danger small mt-1"><?= e($errors['commune_id']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label wh-label-icon" for="adresse">
                        <i class="mdi mdi-road-variant"></i><?= e(__('common.adresse')) ?>
                    </label>
                    <div class="wh-input-icon-wrap">
                        <i class="mdi mdi-map-marker"></i>
                        <input type="text" class="form-control" id="adresse" name="adresse" value="<?= e($old['adresse'] ?? ($event['adresse'] ?? '')) ?>" required>
                    </div>
                    <?php if (isset($errors['adresse'])): ?><div class="text-danger small mt-1"><?= e($errors['adresse']) ?></div><?php endif; ?>
                </div>

                <div class="alert alert-light border small mb-3">
                    <i class="mdi mdi-calendar-clock me-1"></i>
                    <?= e(__('associations.date_wilaya_hint')) ?>
                </div>

                <div class="mb-3">
                    <label class="form-label wh-label-icon" for="description">
                        <i class="mdi mdi-text-box-outline"></i><?= e(__('common.description')) ?>
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?= e($old['description'] ?? ($event['description'] ?? '')) ?></textarea>
                    <?php if (isset($errors['description'])): ?><div class="text-danger small mt-1"><?= e($errors['description']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label wh-label-icon" for="informations">
                        <i class="mdi mdi-information-outline"></i><?= e(__('evenements.complementaires')) ?>
                    </label>
                    <textarea class="form-control" id="informations" name="informations" rows="3"><?= e($old['informations'] ?? ($event['informations_complementaires'] ?? '')) ?></textarea>
                </div>

                <div class="mb-3">
                    <?= view('partials.anomalies_checkbox', [
                        'anomaliesParEpic' => $anomaliesParEpic,
                        'selectedIds'      => (array) ($old['anomalies'] ?? []),
                        'isAr'             => $isAr,
                        'errors'           => $errors,
                    ]) ?>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-send me-1"></i> <?= e(__('common.save')) ?>
                    </button>
                    <a href="<?= url('association') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Aperçu routage en direct -->
    <div class="card border-0 shadow-sm mt-4" id="routePreviewCard" style="display:none">
        <div class="card-header bg-light d-flex align-items-center gap-2">
            <i class="mdi mdi-route text-primary"></i>
            <h3 class="h6 mb-0"><?= $isAr ? 'الجهة المكلفة بالمعالجة' : 'Organisme en charge du traitement' ?></h3>
        </div>
        <div class="card-body d-flex align-items-center gap-3">
            <div class="wh-page-title-icon purple" id="routePreviewIcon"><i class="mdi mdi-satellite-variant"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold" id="routePreviewName">—</div>
                <div class="small text-muted" id="routePreviewDetail"></div>
            </div>
            <span class="spinner-border spinner-border-sm text-primary" id="routePreviewSpinner" style="display:none" role="status"></span>
        </div>
    </div>
</div>

<script>
(function () {
    var card    = document.getElementById('routePreviewCard');
    var nameEl  = document.getElementById('routePreviewName');
    var detail  = document.getElementById('routePreviewDetail');
    var spinner = document.getElementById('routePreviewSpinner');
    var timer   = null;

    function collect() {
        var commune = document.getElementById('commune_id');
        var checked = document.querySelectorAll('input[name="anomalies[]"]:checked');
        var anomalies = [];
        for (var i = 0; i < checked.length; i++) {
            anomalies.push(checked[i].value);
        }
        if (!commune || commune.value === '' || anomalies.length === 0) {
            card.style.display = 'none';
            return;
        }
        card.style.display = 'block';
        spinner.style.display = 'inline-block';
        nameEl.textContent = '…';
        detail.textContent = '';

        var body = new URLSearchParams();
        body.append('commune_id', commune.value);
        anomalies.forEach(function (id) { body.append('anomalies[]', id); });

        fetch('<?= url('association/routing-preview') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body + '&_token=' + encodeURIComponent('<?= csrf_token() ?>')
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            spinner.style.display = 'none';
            if (data && data.success && data.epic) {
                nameEl.textContent = data.epic.nom;
                detail.textContent = data.detail;
            } else {
                nameEl.textContent = '<?= $isAr ? 'لم يتم تحديد جهة بعد' : 'Aucun organisme déterminé' ?>';
                detail.textContent = (data && data.detail) ? data.detail : '';
            }
        })
        .catch(function () {
            spinner.style.display = 'none';
            nameEl.textContent = '—';
        });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(collect, 250);
    }

    var commune = document.getElementById('commune_id');
    if (commune) { commune.addEventListener('change', schedule); }
    document.querySelectorAll('input[name="anomalies[]"]').forEach(function (cb) {
        cb.addEventListener('change', schedule);
    });

    if (commune && commune.value !== '' &&
        document.querySelectorAll('input[name="anomalies[]"]:checked').length > 0) {
        collect();
    }
})();
</script>
