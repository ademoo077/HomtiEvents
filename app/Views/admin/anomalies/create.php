<?php
/** @var array $errors */
$title = __('common.create');
$page  = 'admin.anomalies.create';

$error = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<div class="form-error">' . e((string) $errors[$key]) . '</div>' : '';
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('common.create')) ?> — <?= e(__('common.anomalies')) ?></h1>
            <p class="wh-page-sub"><?= e(__('common.informations')) ?></p>
        </div>
        <a href="<?= url('admin/anomalies') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
    </div>

    <form method="post" action="<?= url('admin/anomalies') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header"><span><i class="mdi mdi-alert-octagon me-2"></i><?= e(__('common.anomalies')) ?></span></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nom"><?= e(__('common.nom')) ?> *</label>
                        <input type="text" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= e(old('nom')) ?>" required maxlength="100">
                        <?= $error('nom') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="icone"><?= e(__('common.details')) ?></label>
                        <input type="text" class="form-control" id="icone" name="icone" value="<?= e(old('icone')) ?>" placeholder="mdi-alert-octagon" maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="couleur"><?= e(__('common.details')) ?></label>
                        <input type="color" class="form-control form-control-color" id="couleur" name="couleur" value="<?= e(old('couleur', '#dc3545')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description"><?= e(__('common.description')) ?></label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= e(old('description')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('admin/anomalies') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
</div>
