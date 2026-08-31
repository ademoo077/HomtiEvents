<?php
/** @var array $anomalie @var array $errors */
$title = __('common.edit');
$page  = 'admin.anomalies.edit';

$error = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<div class="form-error">' . e((string) $errors[$key]) . '</div>' : '';
};
?>
<style>
.wh-anomalies-hero{background:linear-gradient(135deg,#dc3545 0%,#F59E0B 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden}
.wh-anomalies-hero::before{content:"";position:absolute;top:-40%;right:-5%;width:300px;height:300px;background:rgba(255,255,255,.07);border-radius:50%}
.wh-anomalies-hero h1{position:relative;z-index:1;margin:0}
.wh-anomalies-hero p{position:relative;z-index:1;opacity:.85}
.wh-anomalies-hero .btn{position:relative;z-index:1}
@media(max-width:767.98px){.wh-anomalies-hero{padding:1.25rem 1rem}.wh-anomalies-hero h1{font-size:1.2rem}}
</style>

<div class="wh-page">
    <div class="wh-anomalies-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-pencil-outline"></i>
                    <?= e(__('common.edit')) ?> — <?= e($anomalie['nom']) ?>
                </h1>
                <p class="mt-1 mb-0"><?= e(__('common.anomalies')) ?></p>
            </div>
            <a href="<?= url('admin/anomalies') ?>" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
        </div>
    </div>

    <form method="post" action="<?= url('admin/anomalies/' . (int) $anomalie['id'] . '/update') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background:var(--wh-red-soft);border-bottom:2px solid #dc3545"><h6 class="mb-0 fw-bold" style="color:#dc3545"><i class="mdi mdi-alert-octagon me-2"></i><?= e(__('common.anomalies')) ?></h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nom"><?= e(__('common.nom')) ?> *</label>
                        <input type="text" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= e($anomalie['nom']) ?>" required maxlength="100">
                        <?= $error('nom') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="icone"><?= e(__('common.details')) ?></label>
                        <input type="text" class="form-control" id="icone" name="icone" value="<?= e($anomalie['icone']) ?>" placeholder="mdi-alert-octagon" maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="couleur"><?= e(__('common.details')) ?></label>
                        <input type="color" class="form-control form-control-color" id="couleur" name="couleur" value="<?= e($anomalie['couleur'] ?: '#dc3545') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description"><?= e(__('common.description')) ?></label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= e($anomalie['description']) ?></textarea>
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
