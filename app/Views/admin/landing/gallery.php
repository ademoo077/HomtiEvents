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
    <div style="background:linear-gradient(135deg,#0B5ED7 0%,#198754 60%,#6610f2 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-image-multiple"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('landing.admin.gallery')) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= count($items) ?> <?= e(__('landing.admin.gallery')) ?></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light" href="<?= url('admin/landing') ?>"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
                <a class="btn btn-warning fw-bold" href="<?= url('admin/landing/gallery/create') ?>"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100" style="border-radius:var(--wh-radius);overflow:hidden;">
                    <div class="overflow-hidden" style="height:160px;background:#f1f5f9;">
                        <?php if ($item['image']): ?>
                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['titre_fr']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                <i class="mdi mdi-image-off" style="font-size:2.2rem;opacity:.4;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="card-title mb-0 text-truncate"><?= e($item['titre_fr']) ?></h6>
                            <span class="wh-badge badge-<?= $item['type'] === 'evenement' ? 'cyan' : ($item['type'] === 'actualite' ? 'violet' : 'blue') ?>"><?= e($typeLabel((string) $item['type'])) ?></span>
                        </div>
                        <small class="wh-text-muted"><?= e($item['titre_ar'] ?? '') ?></small>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2" style="background:var(--wh-gray-soft,#f8fafc);border-top:1px solid var(--wh-border,#e2e8f0);">
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
