<?php
/** @var array $items */
use App\Helpers\I18n;

$title = __('landing.admin.before_after');
$page  = 'admin.landing.before_after';
$dir   = I18n::direction();
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('landing.admin.before_after')) ?></h1>
            <p class="wh-page-sub"><?= count($items) ?> <?= e(__('landing.admin.before_after')) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= url('admin/landing') ?>"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
            <a class="btn btn-primary" href="<?= url('admin/landing/before-after/create') ?>"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></a>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="d-flex">
                        <div class="ratio ratio-1x1 flex-grow-1 overflow-hidden" style="background:#f1f5f9">
                            <?php if ($item['image_before']): ?><img src="<?= e($item['image_before']) ?>" alt="Avant" loading="lazy" class="w-100 h-100 object-fit-cover"><?php endif; ?>
                        </div>
                        <div class="ratio ratio-1x1 flex-grow-1 overflow-hidden" style="background:#f1f5f9">
                            <?php if ($item['image_after']): ?><img src="<?= e($item['image_after']) ?>" alt="Après" loading="lazy" class="w-100 h-100 object-fit-cover"><?php endif; ?>
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
