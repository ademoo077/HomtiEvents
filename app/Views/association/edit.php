<?php
/** @var array $event @var array $communes @var array $anomalies @var array $epics @var array $selectedAnomalies @var array $assignedEpics @var array $errors @var array $old */
/** @var array|null $event */
use App\Helpers\I18n;

$title = __('common.edit') . ' — ' . e(mb_substr((string) ($event['adresse'] ?? ''), 0, 40));
$page  = 'association.edit';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="wh-page">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="wh-page-title"><?= e(__('common.edit')) ?></h1>
        <a class="btn btn-outline-secondary" href="<?= url('association') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= url('association/' . (int) $event['id'] . '/update') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="commune_id"><?= e(__('common.commune')) ?></label>
                    <select class="form-select" id="commune_id" name="commune_id">
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

                <div class="mb-3">
                    <label class="form-label" for="adresse"><?= e(__('common.adresse')) ?></label>
                    <input type="text" class="form-control" id="adresse" name="adresse" value="<?= e($old['adresse'] ?? ($event['adresse'] ?? '')) ?>" required>
                </div>

                <div class="alert alert-light border small mb-3">
                    <i class="mdi mdi-calendar-clock me-1"></i>
                    <?= e(__('associations.date_wilaya_hint')) ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description"><?= e(__('common.description')) ?></label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?= e($old['description'] ?? ($event['description'] ?? '')) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="informations"><?= e(__('evenements.complementaires')) ?></label>
                    <textarea class="form-control" id="informations" name="informations" rows="3"><?= e($old['informations'] ?? ($event['informations_complementaires'] ?? '')) ?></textarea>
                </div>

                <div class="mb-3">
                    <?= view('partials.anomalies_checkbox', [
                        'anomaliesParEpic' => $anomaliesParEpic,
                        'selectedIds'      => (array) ($old['anomalies'] ?? ($selectedAnomalies ?? [])),
                        'isAr'             => $isAr,
                        'errors'           => $errors,
                    ]) ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-content-save"></i> <?= e(__('common.save')) ?>
                </button>
            </form>
        </div>
    </div>
</div>
