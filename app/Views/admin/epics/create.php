<?php
/** @var array $anomalies @var array $errors */
$title = __('common.create');
$page  = 'admin.epics.create';

$error = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<div class="form-error">' . e((string) $errors[$key]) . '</div>' : '';
};
?>
<div class="wh-page">
    <div class="wh-hero-panel mb-4" style="--hero-a:#0891b2;--hero-b:#0B5ED7">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-plus-circle-outline"></i>
                    <?= e(__('common.create')) ?> — <?= e(__('common.epic')) ?>
                </h1>
                <p class="mt-1 mb-0"><?= e(__('common.informations')) ?></p>
            </div>
            <a href="<?= url('admin/epics') ?>" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
        </div>
    </div>

    <form method="post" action="<?= url('admin/epics') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background:var(--wh-cyan-soft);border-bottom:2px solid #0891b2"><h6 class="mb-0 fw-bold" style="color:#0891b2"><i class="mdi mdi-satellite-variant me-2"></i><?= e(__("common.epic")) ?></h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="nom"><?= e(__('common.nom')) ?> *</label>
                        <input type="text" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= e(old('nom')) ?>" required maxlength="100">
                        <?= $error('nom') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="couleur"><?= e(__('common.details')) ?></label>
                        <input type="color" class="form-control form-control-color" id="couleur" name="couleur" value="<?= e(old('couleur', '#0B5ED7')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description"><?= e(__('common.description')) ?></label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= e(old('description')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= e(__('evenements.anomalies')) ?></label>
                        <div class="row g-2">
                            <?php foreach ($anomalies as $an): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="anomalies[]" id="anomalie-<?= (int) $an['id'] ?>" value="<?= (int) $an['id'] ?>">
                                        <label class="form-check-label" for="anomalie-<?= (int) $an['id'] ?>"><?= e($an['nom']) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('admin/epics') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
</div>
