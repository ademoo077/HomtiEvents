<?php
/** @var array|null $item @var array $errors */
$editing = $item !== null && !empty($item['id']);
$title = $editing ? 'Modifier l\'élément' : 'Nouvel élément';
$page  = 'admin.landing.news_form';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';
$action = $editing
    ? url('admin/landing/news/' . (int) $item['id'] . '/update')
    : url('admin/landing/news');
$val = static fn(string $key, mixed $default = '') => (string) ($item[$key] ?? $default);
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= $editing ? ($isAr ? 'تعديل العنصر' : 'Modifier l\'élément') : ($isAr ? 'عنصر جديد' : 'Nouvel élément') ?></h1>
            <p class="wh-page-sub"><?= $isAr ? 'أخبار أو حدث قادم' : 'Actualité ou événement à venir' ?></p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= url('admin/landing/news') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger small">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e(is_string($err) ? $err : 'Erreur de saisie.') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label" for="titre_fr"><?= $isAr ? 'العنوان (FR)' : 'Titre (FR)' ?> *</label>
                        <input type="text" class="form-control" id="titre_fr" name="titre_fr" value="<?= e($val('titre_fr')) ?>" required maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="type"><?= $isAr ? 'النوع' : 'Type' ?> *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="actualite" <?= $val('type') === 'actualite' ? 'selected' : '' ?>><?= $isAr ? 'خبر' : 'Actualité' ?></option>
                            <option value="evenement" <?= $val('type') === 'evenement' ? 'selected' : '' ?>><?= $isAr ? 'حدث' : 'Événement' ?></option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label" for="titre_ar"><?= $isAr ? 'العنوان (AR)' : 'Titre (AR)' ?></label>
                        <input type="text" class="form-control" id="titre_ar" name="titre_ar" value="<?= e($val('titre_ar')) ?>" maxlength="255" dir="rtl">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="date_event"><?= $isAr ? 'التاريخ' : 'Date' ?></label>
                        <input type="date" class="form-control" id="date_event" name="date_event" value="<?= e($val('date_event')) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="lieu"><?= $isAr ? 'المكان' : 'Lieu' ?></label>
                        <input type="text" class="form-control" id="lieu" name="lieu" value="<?= e($val('lieu')) ?>" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="lieu_ar"><?= $isAr ? 'المكان (AR)' : 'Lieu (AR)' ?></label>
                        <input type="text" class="form-control" id="lieu_ar" name="lieu_ar" value="<?= e($val('lieu_ar')) ?>" maxlength="255" dir="rtl">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="description_fr"><?= $isAr ? 'الوصف (FR)' : 'Description (FR)' ?></label>
                        <textarea class="form-control" id="description_fr" name="description_fr" rows="4"><?= e($val('description_fr')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="description_ar"><?= $isAr ? 'الوصف (AR)' : 'Description (AR)' ?></label>
                        <textarea class="form-control" id="description_ar" name="description_ar" rows="4" dir="rtl"><?= e($val('description_ar')) ?></textarea>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="image_file"><?= $isAr ? 'الصورة' : 'Image' ?></label>
                        <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                        <?php if ($editing && $val('image')): ?>
                            <div class="mt-2">
                                <small class="text-muted"><?= $isAr ? 'الصورة الحالية' : 'Image actuelle' ?>: <?= e($val('image')) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="url_externe"><?= $isAr ? 'رابط خارجي' : 'URL externe' ?></label>
                        <input type="url" class="form-control" id="url_externe" name="url_externe" value="<?= e($val('url_externe')) ?>" maxlength="500" placeholder="https://...">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="sort_order"><?= $isAr ? 'الترتيب' : 'Ordre' ?></label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= e($val('sort_order', '0')) ?>" min="0">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="actif" id="actif" <?= (int) $val('actif', '1') === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="actif"><?= $isAr ? 'نشط' : 'Actif' ?></label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i><?= $isAr ? 'حفظ' : 'Enregistrer' ?>
                    </button>
                    <a href="<?= url('admin/landing/news') ?>" class="btn btn-outline-secondary"><?= $isAr ? 'إلغاء' : 'Annuler' ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
