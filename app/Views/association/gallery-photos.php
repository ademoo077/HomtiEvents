<?php
/**
 * Galerie associative — Photos d'un événement + soumission.
 *
 * @var array $event
 * @var array|null $album
 * @var array $photos
 */
use App\Helpers\I18n;

$title = __('common.gallery') . ' — ' . ($event['adresse'] ?? '');
$page  = 'association.gallery';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$maxUpload = (int) config('security.upload_max', 5242880);
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-image-multiple me-2"></i><?= e(__('common.gallery')) ?>
            </h1>
            <p class="wh-page-sub">
                <?= e($event['adresse'] ?? '') ?>
                <?php if (! empty($event['date_evenement'])): ?>
                    · <?= e($event['date_evenement']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= url('association/gallery') ?>">
                <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
            </a>
        </div>
    </div>

    <?php if (! empty($event['motif_refus'])): ?>
        <div class="alert alert-warning">
            <i class="mdi mdi-alert-outline me-1"></i><?= e($event['motif_refus']) ?>
        </div>
    <?php endif; ?>

    <!-- ═══ FORMULAIRE DE SOUMISSION ═══ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-2"><i class="mdi mdi-upload me-1"></i><?= e($isAr ? 'إرسال صور' : 'Soumettre des photos') ?></h5>
            <p class="text-muted small mb-3">
                <?= e($isAr
                    ? 'ستُرسل الصور إلى الولاية للتحقق قبل النشر.'
                    : 'Les photos soumises seront validées par la Wilaya avant publication.') ?>
            </p>
            <form method="post" action="<?= url('association/evenements/' . (int) $event['id'] . '/photos') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="file" name="photos[]" class="form-control mb-2" accept="image/*" multiple required>
                <small class="text-muted d-block mb-2">
                    <i class="mdi mdi-information-outline me-1"></i>JPG, PNG, WebP — max <?= number_format($maxUpload / 1048576, 1) ?> Mo / image
                </small>
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-upload me-1"></i><?= e($isAr ? 'إرسال الصور' : 'Soumettre') ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ═══ PHOTOS ═══ -->
    <?php if ($photos === []): ?>
        <div class="wh-empty card shadow-sm py-5">
            <div class="card-body text-center">
                <i class="mdi mdi-image-multiple text-muted" style="font-size: 2rem"></i>
                <p class="mb-0 mt-2"><?= e($isAr ? 'لا توجد صور بعد' : 'Aucune photo pour le moment.') ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($photos as $photo): ?>
                <?php
                    $statut = (string) ($photo['status'] ?? 'active');
                    $badge  = match ($statut) {
                        'pending'  => 'bg-warning text-dark',
                        'rejected' => 'bg-danger',
                        'active'   => 'bg-success',
                        default    => 'bg-secondary',
                    };
                    $label  = match ($statut) {
                        'pending'  => $isAr ? 'قيد الانتظار' : 'En attente de validation',
                        'rejected' => $isAr ? 'مرفوضة' : 'Rejetée',
                        'active'   => $isAr ? 'منشورة' : 'Publiée',
                        default    => $statut,
                    };
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm overflow-hidden h-100">
                        <?php if (! empty($photo['image'])): ?>
                            <img src="<?= e($photo['image']) ?>" alt="<?= e($photo['legende'] ?? '') ?>" class="w-100" style="aspect-ratio: 1/1; object-fit: cover;" loading="lazy">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="aspect-ratio: 1/1;">
                                <i class="mdi mdi-image-off" style="font-size: 1.8rem"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <span class="badge <?= $badge ?>"><?= e($label) ?></span>
                            <?php if ($statut === 'rejected' && ! empty($photo['motif_rejet'])): ?>
                                <p class="small text-muted mt-1 mb-1"><?= e($photo['motif_rejet']) ?></p>
                            <?php endif; ?>
                            <?php if (in_array($statut, ['pending', 'rejected'], true)): ?>
                                <form method="post" action="<?= url('association/photos/' . (int) $photo['id'] . '/delete') ?>" class="d-inline"
                                      data-confirm="<?= e($isAr ? 'حذف هذه الصورة؟' : 'Supprimer cette photo ?') ?>"
                                      onsubmit="return confirm('<?= e($isAr ? 'حذف هذه الصورة؟' : 'Supprimer cette photo ?') ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 mt-1">
                                        <i class="mdi mdi-delete me-1"></i><?= e($isAr ? 'حذف' : 'Supprimer') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
