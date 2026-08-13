<?php
/** @var array $item */
use App\Helpers\I18n;

$editing = ! empty($item['id']);
$title = $editing ? __('common.edit') : __('common.create');
$page  = $editing ? 'admin.landing.before_after.edit' : 'admin.landing.before_after.create';
$dir   = I18n::direction();

$val = static function (string $k) use ($item): string {
    return (string) (old($k) ?? ($item[$k] ?? ''));
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('landing.admin.before_after')) ?> — <?= e($title) ?></h1>
            <p class="wh-page-sub"><?= e(__('common.informations')) ?></p>
        </div>
        <a href="<?= url('admin/landing/before-after') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
    </div>

    <form method="post" action="<?= $editing ? url('admin/landing/before-after/' . (int) $item['id'] . '/update') : url('admin/landing/before-after') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header"><span><i class="mdi mdi-compare-horizontal me-2"></i><?= e(__('landing.admin.before_after')) ?></span></div>
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
                    <div class="col-md-6">
                        <label class="form-label" for="image_before">Image « avant » *</label>
                        <input type="text" class="form-control" id="image_before" name="image_before" value="<?= e($val('image_before')) ?>" required maxlength="255" placeholder="/assets/img/avant.jpg">
                        <div class="mt-2 ba-thumb" id="thumb_before">
                            <?php if ($val('image_before') !== ''): ?>
                                <img src="<?= e(asset($val('image_before'))) ?>" alt="Avant" loading="lazy">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="image_after">Image « après » *</label>
                        <input type="text" class="form-control" id="image_after" name="image_after" value="<?= e($val('image_after')) ?>" required maxlength="255" placeholder="/assets/img/apres.jpg">
                        <div class="mt-2 ba-thumb" id="thumb_after">
                            <?php if ($val('image_after') !== ''): ?>
                                <img src="<?= e(asset($val('image_after'))) ?>" alt="Après" loading="lazy">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description_fr">Description (FR)</label>
                        <textarea class="form-control" id="description_fr" name="description_fr" rows="3" maxlength="1000"><?= e($val('description_fr')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description_ar">Description (AR)</label>
                        <textarea class="form-control" id="description_ar" name="description_ar" rows="3" maxlength="1000"><?= e($val('description_ar')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="statut">Statut</label>
                        <select class="form-select" id="statut" name="statut">
                            <?php foreach (['brouillon', 'publie'] as $s): ?>
                                <option value="<?= e($s) ?>" <?= $val('statut') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
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
            <a href="<?= url('admin/landing/before-after') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
</div>

<style>
.ba-thumb {
    width: 100%;
    height: 120px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}
.ba-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.ba-thumb:empty {
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function bindThumb(inputId, thumbId) {
        var input = document.getElementById(inputId);
        var thumb = document.getElementById(thumbId);
        if (!input || !thumb) return;
        var render = function () {
            var v = input.value.trim();
            if (v === '') {
                thumb.innerHTML = '';
                return;
            }
            var abs = (v.charAt(0) === '/' || /^(https?:)?\/\//.test(v)) ? v : '/' + v;
            thumb.innerHTML = '<img src="' + abs.replace(/"/g, '&quot;') + '" alt="" loading="lazy" onerror="this.parentElement.innerHTML=\'\'">';
        };
        input.addEventListener('input', render);
    }
    bindThumb('image_before', 'thumb_before');
    bindThumb('image_after', 'thumb_after');
});
</script>
