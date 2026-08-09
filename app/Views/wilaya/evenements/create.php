<?php
/** @var array $communes @var array $dairas @var array $associations @var array $anomalies
 *  @var array $epics @var array $selectedAnomalies @var array $assignedEpics
 *  @var ?array $event @var array $errors @var array $old
 */
use App\Helpers\I18n;

$title = __('evenements.create');
$page  = 'wilaya.evenements.create';

$selectedAnomalies = $selectedAnomalies ?? [];
$assignedEpics     = $assignedEpics ?? [];
$oldVal            = static function (string $key, mixed $default = null) use ($old): mixed {
    return $old[$key] ?? $default;
};
$error = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<div class="form-error">' . e(is_array($errors[$key]) ? implode(', ', $errors[$key]) : (string) $errors[$key]) . '</div>' : '';
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('evenements.create')) ?></h1>
            <p class="wh-page-sub"><?= e(__('common.informations')) ?></p>
        </div>
        <a href="<?= url('wilaya/evenements') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
        </a>
    </div>

    <form method="post" action="<?= url('wilaya/evenements') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-map-marker-radius me-2"></i><?= e(__('common.adresse')) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="commune_id"><?= e(__('common.commune')) ?> *</label>
                        <select class="form-select <?= isset($errors['commune_id']) ? 'is-invalid' : '' ?>" id="commune_id" name="commune_id" required>
                            <option value="">— <?= e(__('common.commune')) ?> —</option>
                            <?php foreach ($communes as $c): ?>
                                <option value="<?= e((string) $c['id']) ?>" <?= (string) $oldVal('commune_id', $event['commune_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $error('commune_id') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="adresse"><?= e(__('common.adresse')) ?> *</label>
                        <input type="text" class="form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" id="adresse" name="adresse"
                               value="<?= e((string) $oldVal('adresse', $event['adresse'] ?? '')) ?>" required minlength="5">
                        <?= $error('adresse') ?>
                    </div>
                </div>
            </div>
        </div>

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
                                <option value="<?= e((string) $a['id']) ?>" <?= (string) $oldVal('association_id', $event['association_id'] ?? '') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="statut"><?= e(__('common.status')) ?></label>
                        <select class="form-select" id="statut" name="statut">
                            <?php foreach (['EN_ATTENTE', 'VALIDÉ', 'PROGRAMME'] as $s): ?>
                                <option value="<?= e($s) ?>" <?= (string) $oldVal('statut', $event['statut'] ?? '') === $s ? 'selected' : '' ?>><?= e(statut_label($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="wh-form-hint"><?= e(__('common.details')) ?> : <?= e(__('evenements.create_success')) ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description"><?= e(__('common.description')) ?> *</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description"
                                  rows="4" required minlength="10"><?= e((string) $oldVal('description', $event['description'] ?? '')) ?></textarea>
                        <?= $error('description') ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="informations"><?= e(__('evenements.complementaires')) ?></label>
                        <textarea class="form-control" id="informations" name="informations" rows="3"><?= e((string) $oldVal('informations', $event['informations_complementaires'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-alert-octagon me-2"></i><?= e(__('evenements.anomalies')) ?> *</span>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($anomalies as $an): ?>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="anomalies[]" id="anomalie-<?= (int) $an['id'] ?>"
                                       value="<?= (int) $an['id'] ?>" <?= in_array((int) $an['id'], $selectedAnomalies, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="anomalie-<?= (int) $an['id'] ?>"><?= e($an['nom']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= $error('anomalies') ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-calendar-clock me-2"></i><?= e(__('evenements.program.title')) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="date_evenement"><?= e(__('evenements.program.date')) ?></label>
                        <input type="date" class="form-control" id="date_evenement" name="date_evenement"
                               value="<?= e((string) $oldVal('date_evenement', $event['date_evenement'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="heure"><?= e(__('evenements.program.heure')) ?></label>
                        <input type="time" class="form-control" id="heure" name="heure"
                               value="<?= e((string) $oldVal('heure', $event['heure'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="epics"><?= e(__('evenements.epics_assigned')) ?></label>
                        <select class="form-select" id="epics" name="epics[]" multiple size="5">
                            <?php foreach ($epics as $ep): ?>
                                <option value="<?= e((string) $ep['id']) ?>" <?= in_array((int) $ep['id'], $assignedEpics, true) ? 'selected' : '' ?>><?= e($ep['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="wh-form-hint"><?= e(__('evenements.program.epics')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('wilaya/evenements') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
            <button type="submit" class="btn btn-primary">
                <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
            </button>
        </div>
    </form>
</div>
