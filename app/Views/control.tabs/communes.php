<?php
/** @var array $communes */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <a class="btn btn-primary" href="<?= url('control/communes/create') ?>">
        <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
    </a>
</div>

<div class="futur-card">
    <div class="futur-card-body p-0">
        <div class="table-responsive">
            <table class="futur-table mb-0">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                        <th><?= $isAr ? 'الولاية' : 'Wilaya' ?></th>
                        <th><?= $isAr ? 'الدائرة' : 'Daira' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($communes as $c): ?>
                        <tr>
                            <td><strong><?= e($c['nom']) ?></strong></td>
                            <td><?= e($c['wilaya'] ?? '—') ?></td>
                            <td><?= e($c['daira'] ?? '—') ?></td>
                            <td>
                                <span class="futur-chip chip-<?= (int) ($c['actif'] ?? 1) ? 'success' : 'gray' ?>">
                                    <?= (int) ($c['actif'] ?? 1) ? ($isAr ? 'نشط' : 'Actif') : ($isAr ? 'معطل' : 'Inactif') ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="<?= url('control/communes/' . (int) $c['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <form method="post" action="<?= url('control/communes/' . (int) $c['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="<?= e(__('common.delete')) ?>">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($communes)): ?>
                        <tr><td colspan="5" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
