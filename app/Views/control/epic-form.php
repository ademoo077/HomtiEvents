<?php
/**
 * Formulaire création / édition d'un EPIC.
 *
 * @var string $mode      'create' | 'edit'
 * @var array|null $epic
 * @var array $errors
 */
use App\Helpers\I18n;

$isAr   = I18n::direction() === 'rtl';
$editing = $mode === 'edit';
$old     = $old ?? [];
$oldVal  = static fn (string $key, mixed $default = '') => (string) ($old[$key] ?? $default);
$val     = static fn (string $key, mixed $default = '') => (string) ($epic[$key] ?? $default);
$action  = $editing
    ? url('control/epic/' . (int) $epic['id'] . '/update')
    : url('control/epic');
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title">
                        <i class="mdi mdi-satellite-variant me-2"></i>
                        <?= $editing ? ($isAr ? 'تعديل EPIC' : 'Modifier l\'EPIC') : ($isAr ? 'EPIC جديد' : 'Nouvel EPIC') ?>
                    </h1>
                    <p class="wh-hero-sub">
                        <?= $isAr ? 'إنشاء أو تعديل منظمة EPIC' : 'Création ou édition d\'une organisation EPIC' ?>
                    </p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-sm btn-outline-light" href="<?= url('control?tab=epics') ?>">
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
                        <label class="form-label" for="daira"><?= $isAr ? 'الدائرة' : 'Daira' ?></label>
                        <input type="text" class="form-control" id="daira" name="daira" value="<?= e($editing ? $val('daira') : $oldVal('daira')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="description"><?= $isAr ? 'الوصف' : 'Description' ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= e($editing ? $val('description') : $oldVal('description')) ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i><?= $isAr ? 'حفظ' : 'Enregistrer' ?>
                    </button>
                    <a href="<?= url('control?tab=epics') ?>" class="btn btn-outline-secondary"><?= $isAr ? 'إلغاء' : 'Annuler' ?></a>
                </div>
            </form>
        </div>
    </div>
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>
