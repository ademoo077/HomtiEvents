<?php
/** @var array $item */
use App\Helpers\I18n;

$editing = ! empty($item['id']);
$title = $editing ? __('common.edit') : __('common.create');
$page  = $editing ? 'admin.landing.gallery.edit' : 'admin.landing.gallery.create';
$dir   = I18n::direction();

$val = static function (string $k) use ($item): string {
    return (string) (old($k) ?? ($item[$k] ?? ''));
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('landing.admin.gallery')) ?> — <?= e($title) ?></h1>
            <p class="wh-page-sub"><?= e(__('common.informations')) ?></p>
        </div>
        <a href="<?= url('admin/landing/gallery') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
    </div>

    <form method="post" action="<?= $editing ? url('admin/landing/gallery/' . (int) $item['id'] . '/update') : url('admin/landing/gallery') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header"><span><i class="mdi mdi-image-multiple me-2"></i><?= e(__('landing.admin.gallery')) ?></span></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="titre_fr"><?= e(__('landing.admin.titre')) ?> (FR) *</label>
                        <input type="text" class="form-control" id="titre_fr" name="titre_fr" value="<?= e($val('titre_fr')) ?>" required maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="titre_ar"><?= e(__('landing.admin.titre')) ?> (AR)</label>
                        <input type="text" class="form-control" id="titre_ar" name="titre_ar" value="<?= e($val('titre_ar')) ?>" maxlength="255">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="image">Image (URL ou chemin) *</label>
                        <input type="text" class="form-control" id="image" name="image" value="<?= e($val('image')) ?>" required maxlength="255" placeholder="/assets/img/gallery/photo.jpg">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="lien">Lien</label>
                        <input type="url" class="form-control" id="lien" name="lien" value="<?= e($val('lien')) ?>" maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="type">Type</label>
                        <select class="form-select" id="type" name="type">
                            <?php foreach (['album', 'evenement', 'actualite'] as $t): ?>
                                <option value="<?= e($t) ?>" <?= $val('type') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="sort_order"><?= e(__('common.details')) ?></label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= (int) $val('sort_order') ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="actif" id="actif" <?= $editing && ! (int) $item['actif'] ? '' : 'checked' ?>>
                            <label class="form-check-label" for="actif"><?= e(__('landing.admin.active')) ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('admin/landing/gallery') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
</div>
