<?php
/** @var array $items */
use App\Helpers\I18n;

$title = __('landing.admin.before_after');
$page  = 'admin.landing.before_after';
$dir   = I18n::direction();
?>
<div class="wh-page">
    <div style="background:linear-gradient(135deg,#0B5ED7 0%,#198754 60%,#6610f2 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-compare-horizontal"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('landing.admin.before_after')) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= count($items) ?> <?= e(__('landing.admin.before_after')) ?></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light" href="<?= url('admin/landing') ?>"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
                <a class="btn btn-warning fw-bold" href="<?= url('admin/landing/before-after/create') ?>"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100" style="border-radius:var(--wh-radius);overflow:hidden;">
                    <div style="padding:.75rem 1.25rem;background:#ede9fe;border-bottom:1px solid #ddd6fe;display:flex;align-items:center;gap:.5rem;">
                        <span style="width:28px;height:28px;border-radius:7px;background:rgba(124,58,237,.15);display:grid;place-items:center;color:#7c3aed;font-size:.85rem;"><i class="mdi mdi-compare-horizontal"></i></span>
                        <span class="fw-bold" style="font-size:.88rem;"><?= e($item['titre_fr']) ?></span>
                    </div>
                    <div class="d-flex" style="position:relative;">
                        <div class="ratio ratio-1x1 flex-grow-1 overflow-hidden" style="background:#f1f5f9;min-height:160px;">
                            <?php if ($item['image_before']): ?><img src="<?= e($item['image_before']) ?>" alt="Avant" loading="lazy" class="w-100 h-100 object-fit-cover"><?php endif; ?>
                            <span style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,.55);color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:6px;letter-spacing:.5px;text-transform:uppercase;">Avant</span>
                        </div>
                        <div style="width:2px;background:#ddd6fe;position:relative;z-index:1;">
                            <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#7c3aed;color:#fff;font-size:.6rem;font-weight:800;width:24px;height:24px;border-radius:50%;display:grid;place-items:center;letter-spacing:0;">VS</span>
                        </div>
                        <div class="ratio ratio-1x1 flex-grow-1 overflow-hidden" style="background:#f1f5f9;min-height:160px;">
                            <?php if ($item['image_after']): ?><img src="<?= e($item['image_after']) ?>" alt="Après" loading="lazy" class="w-100 h-100 object-fit-cover"><?php endif; ?>
                            <span style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.55);color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:6px;letter-spacing:.5px;text-transform:uppercase;">Après</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="card-title mb-0 text-truncate"><?= e($item['titre_fr']) ?></h6>
                            <span class="wh-badge badge-<?= $item['statut'] === 'publie' ? 'green' : 'amber' ?>"><?= e($item['statut']) ?></span>
                        </div>
                        <small class="wh-text-muted"><?= e(mb_strimwidth((string) ($item['description_fr'] ?? ''), 0, 70, '…')) ?></small>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                        <?php if (! (int) $item['actif']): ?><span class="wh-badge badge-gray me-auto">off</span><?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/landing/before-after/' . (int) $item['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>"><i class="mdi mdi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/landing/before-after/' . (int) $item['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.delete')) ?>"><i class="mdi mdi-delete"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <div class="col-12"><div class="wh-empty"><i class="mdi mdi-compare-horizontal"></i><p><?= e(__('common.no_data')) ?></p></div></div>
        <?php endif; ?>
    </div>
</div>
