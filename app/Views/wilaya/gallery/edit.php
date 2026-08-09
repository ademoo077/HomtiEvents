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

$val = static function (string $k) use ($photo): string {
    return (string) (old($k) ?? ($photo[$k] ?? ''));
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-pencil me-2"></i><?= e(__('common.edit')) ?> — Photo
            </h1>
            <p class="wh-page-sub">
                <?= e($event['adresse'] ?? '') ?>
                <?php if (! empty($event['date_evenement'])): ?>
                    · <?= e($event['date_evenement']) ?>
                <?php endif; ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <form method="post" action="<?= url('wilaya/photos/' . (int) $photo['id'] . '/update') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <span><i class="mdi mdi-image me-2"></i><?= e(__('gallery.photo_details')) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="legende"><?= e(__('gallery.photo_caption')) ?></label>
                                <input type="text" class="form-control" id="legende" name="legende"
                                       value="<?= e($val('legende')) ?>" maxlength="255">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="btn btn-outline-secondary">
                        <?= e(__('common.cancel')) ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <span><i class="mdi mdi-image me-2"></i><?= e(__('gallery.preview')) ?></span>
                </div>
                <div class="card-body text-center">
                    <?php if (! empty($photo['image'])): ?>
                        <img src="<?= e($photo['image']) ?>" alt="Photo"
                             class="img-fluid rounded" style="max-height:300px;object-fit:contain">
                    <?php else: ?>
                        <div class="text-muted py-5">
                            <i class="mdi mdi-image-off" style="font-size:3rem"></i>
                        </div>
                    <?php endif; ?>
                    <small class="text-muted d-block mt-2">
                        <?= e(__('gallery.uploaded_at')) ?>: <?= e($photo['uploaded_at']) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
