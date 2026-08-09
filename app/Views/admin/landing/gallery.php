<?php
/** @var array $items */
use App\Helpers\I18n;

$title = __('landing.admin.gallery');
$page  = 'admin.landing.gallery';
$dir   = I18n::direction();

$typeLabel = static function (string $t) use ($dir): string {
    return match ($t) {
        'evenement' => $dir === 'rtl' ? 'فعالية' : 'Événement',
        'actualite' => $dir === 'rtl' ? 'خبر' : 'Actualité',
        default     => $dir === 'rtl' ? 'ألبوم' : 'Album',
    };
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('landing.admin.gallery')) ?></h1>
            <p class="wh-page-sub"><?= count($items) ?> <?= e(__('landing.admin.gallery')) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= url('admin/landing') ?>"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
            <a class="btn btn-primary" href="<?= url('admin/landing/gallery/create') ?>"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></a>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-img-top ratio ratio-4x3 overflow-hidden" style="background:#f1f5f9">
                        <?php if ($item['image']): ?>
                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['titre_fr']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center text-muted"><i class="mdi mdi-image-off"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="card-title mb-0 text-truncate"><?= e($item['titre_fr']) ?></h6>
                            <span class="wh-badge badge-<?= $item['type'] === 'evenement' ? 'cyan' : ($item['type'] === 'actualite' ? 'violet' : 'blue') ?>"><?= e($typeLabel((string) $item['type'])) ?></span>
                        </div>
                        <small class="wh-text-muted"><?= e($item['titre_ar'] ?? '') ?></small>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                        <?php if (! (int) $item['actif']): ?><span class="wh-badge badge-gray me-auto">off</span><?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/landing/gallery/' . (int) $item['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>"><i class="mdi mdi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/landing/gallery/' . (int) $item['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.delete')) ?>"><i class="mdi mdi-delete"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <div class="col-12"><div class="wh-empty"><i class="mdi mdi-image-multiple"></i><p><?= e(__('common.no_data')) ?></p></div></div>
        <?php endif; ?>
    </div>
</div>
