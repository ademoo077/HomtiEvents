<?php
/**
 * Galerie photos — Formulaire de modification d'une photo.
 *
 * @var array $photo
 * @var array $album
 * @var array $event
 */
use App\Helpers\I18n;

$title = __('common.edit') . ' — Photo';
$page  = 'wilaya.gallery.edit';
$dir   = I18n::direction();
$isAr  = I18n::locale() === 'ar';

$val = static function (string $k) use ($photo): string {
    return (string) (old($k) ?? ($photo[$k] ?? ''));
};
?>
<div class="wh-page">
    <!-- Gradient Hero -->
    <div class="mb-4" style="background:linear-gradient(135deg, #6610f2 0%, #0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden">
        <div style="position:absolute;top:-40%;right:-8%;width:320px;height:320px;background:rgba(255,255,255,.08);border-radius:50%"></div>
        <div style="position:absolute;bottom:-30%;left:5%;width:200px;height:200px;background:rgba(255,255,255,.05);border-radius:50%"></div>
        <div class="row align-items-center" style="position:relative;z-index:1">
            <div class="col-lg-7">
                <h1 class="mb-1" style="font-size:1.5rem;font-weight:800">
                    <i class="mdi mdi-pencil me-2"></i><?= e(__('common.edit')) ?> — <?= $isAr ? 'صورة' : 'Photo' ?>
                </h1>
                <p class="mb-0" style="opacity:.85;font-size:.9rem">
                    <?= e($event['adresse'] ?? '') ?>
                    <?php if (! empty($event['date_evenement'])): ?>
                        · <?= e($event['date_evenement']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <a class="btn btn-light btn-lg" href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>">
                    <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <form method="post" action="<?= url('wilaya/photos/' . (int) $photo['id'] . '/update') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius)">
                    <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:1px solid rgba(11,94,215,.12)">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(11,94,215,.1);display:grid;place-items:center">
                                <i class="mdi mdi-image" style="color:var(--wh-blue);font-size:1rem"></i>
                            </div>
                            <span class="fw-bold"><?= e(__('gallery.photo_details')) ?></span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-medium" for="legende" style="font-size:.85rem;color:#475569"><?= e(__('gallery.photo_caption')) ?></label>
                                <input type="text" class="form-control" id="legende" name="legende"
                                       value="<?= e($val('legende')) ?>" maxlength="255" style="border-radius:.55rem" placeholder="<?= $isAr ? 'أضف تعليقاً...' : 'Ajoutez une légende...' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="btn btn-outline-secondary" style="border-radius:.55rem">
                        <?= e(__('common.cancel')) ?>
                    </a>
                    <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem">
                        <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius:var(--wh-radius)">
                <div class="card-header" style="background:var(--wh-purple-soft);border-bottom:1px solid rgba(102,16,242,.12)">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(102,16,242,.1);display:grid;place-items:center">
                            <i class="mdi mdi-image" style="color:var(--wh-purple);font-size:1rem"></i>
                        </div>
                        <span class="fw-bold"><?= e(__('gallery.preview')) ?></span>
                    </div>
                </div>
                <div class="card-body text-center p-4">
                    <?php if (! empty($photo['image'])): ?>
                        <img src="<?= e(photo_src($photo)) ?>" alt="Photo" loading="lazy"
                             class="img-fluid" style="max-height:300px;object-fit:contain;border-radius:.75rem;box-shadow:0 4px 16px rgba(0,0,0,.08)">
                    <?php else: ?>
                        <div style="width:80px;height:80px;margin:2rem auto;background:#f1f5f9;border-radius:50%;display:grid;place-items:center">
                            <i class="mdi mdi-image-off" style="font-size:2rem;color:#94a3b8"></i>
                        </div>
                    <?php endif; ?>
                    <small class="text-muted d-block mt-3" style="font-size:.8rem">
                        <i class="mdi mdi-clock-outline me-1"></i><?= e(__('gallery.uploaded_at')) ?>: <?= e($photo['uploaded_at']) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
