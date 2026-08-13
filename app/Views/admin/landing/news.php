<?php
/** @var array $items @var array $errors */
$title = 'Actualités & événements';
$page  = 'admin.landing.news';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= $isAr ? 'الأخبار والأحداث القادمة' : 'Actualités & événements à venir' ?></h1>
            <p class="wh-page-sub"><?= count($items) ?> <?= $isAr ? 'عنصر' : 'éléments' ?></p>
        </div>
        <a class="btn btn-primary" href="<?= url('admin/landing/news/create') ?>">
            <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                    <th><?= $isAr ? 'العنوان' : 'Titre' ?></th>
                    <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                    <th><?= $isAr ? 'المكان' : 'Lieu' ?></th>
                    <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                    <th><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <span class="wh-badge badge-<?= $item['type'] === 'evenement' ? 'blue' : 'green' ?>">
                                <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?> me-1"></i>
                                <?= $item['type'] === 'evenement' ? ($isAr ? 'حدث' : 'Événement') : ($isAr ? 'خبر' : 'Actualité') ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= e($item['titre_fr']) ?></div>
                            <?php if ($item['titre_ar']): ?>
                                <small class="wh-text-muted"><?= e($item['titre_ar']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="wh-text-muted">
                            <?= $item['date_event'] ? e(date('d/m/Y', strtotime((string) $item['date_event']))) : '-' ?>
                        </td>
                        <td class="wh-text-muted"><?= e($item['lieu'] ?? '-') ?></td>
                        <td>
                            <?php if ((int) $item['actif'] === 1): ?>
                                <span class="wh-badge badge-green"><?= $isAr ? 'نشط' : 'Actif' ?></span>
                            <?php else: ?>
                                <span class="wh-badge badge-gray"><?= $isAr ? 'غير نشط' : 'Inactif' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/landing/news/' . $item['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                    <i class="mdi mdi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('admin/landing/news/' . $item['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.delete')) ?>">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <tr><td colspan="6"><div class="wh-empty"><i class="mdi mdi-newspaper"></i><p><?= e(__('common.no_data')) ?></p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
