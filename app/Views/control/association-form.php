<?php
/**
 * Formulaire création / édition d'une association.
 *
 * @var string $mode         'create' | 'edit'
 * @var array|null $association
 * @var array $errors
 */
use App\Helpers\I18n;

$isAr   = I18n::direction() === 'rtl';
$editing = $mode === 'edit';
$old     = $old ?? [];
$oldVal  = static fn (string $key, mixed $default = '') => (string) ($old[$key] ?? $default);
$val     = static fn (string $key, mixed $default = '') => (string) ($association[$key] ?? $default);
$action  = $editing
    ? url('control/associations/' . (int) $association['id'] . '/update')
    : url('control/associations');
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title">
                        <i class="mdi mdi-account-group me-2"></i>
                        <?= $editing ? ($isAr ? 'تعديل الجمعية' : 'Modifier l\'association') : ($isAr ? 'جمعية جديدة' : 'Nouvelle association') ?>
                    </h1>
                    <p class="wh-hero-sub">
                        <?= $isAr ? 'إنشاء أو تعديل جمعية' : 'Création ou édition d\'une association' ?>
                    </p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-sm btn-outline-light" href="<?= url('control?tab=associations') ?>">
                        <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= $action ?>">
                <?= csrf_field() ?>

                <?php if (! empty($errors)): ?>
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
                        <label class="form-label" for="nom"><?= $isAr ? 'الاسم' : 'Nom' ?> *</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?= e($editing ? $val('nom') : $oldVal('nom')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="wilaya"><?= $isAr ? 'الولاية' : 'Wilaya' ?></label>
                        <input type="text" class="form-control" id="wilaya" name="wilaya" value="<?= e($editing ? $val('wilaya') : $oldVal('wilaya')) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="email"><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?> *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($editing ? $val('email') : $oldVal('email')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="telephone"><?= $isAr ? 'الهاتف' : 'Téléphone' ?></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= e($editing ? $val('telephone') : $oldVal('telephone')) ?>" placeholder="+213 ...">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="adresse"><?= $isAr ? 'العنوان' : 'Adresse' ?></label>
                    <input type="text" class="form-control" id="adresse" name="adresse" value="<?= e($editing ? $val('adresse') : $oldVal('adresse')) ?>">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i><?= $isAr ? 'حفظ' : 'Enregistrer' ?>
                    </button>
                    <a href="<?= url('control?tab=associations') ?>" class="btn btn-outline-secondary"><?= $isAr ? 'إلغاء' : 'Annuler' ?></a>
                </div>
            </form>
        </div>
    </div>
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>
