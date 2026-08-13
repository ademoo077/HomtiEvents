<?php
/** @var array $associationsList */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <a class="btn btn-primary" href="<?= url('control/associations/create') ?>">
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
                        <th><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></th>
                        <th><?= $isAr ? 'الهاتف' : 'Téléphone' ?></th>
                        <th><?= $isAr ? 'الولاية' : 'Wilaya' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($associationsList as $a): ?>
                        <tr>
                            <td><strong><?= e($a['nom']) ?></strong></td>
                            <td><?= e($a['email'] ?? '—') ?></td>
                            <td><?= e($a['telephone'] ?? '—') ?></td>
                            <td><?= e($a['wilaya'] ?? '—') ?></td>
                            <td>
                                <span class="futur-chip chip-<?= (int) ($a['valide'] ?? 0) ? 'success' : 'warning' ?>">
                                    <?= (int) ($a['valide'] ?? 0) ? ($isAr ? 'موثق' : 'Validée') : ($isAr ? 'قيد المراجعة' : 'En attente') ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="<?= url('control/associations/' . (int) $a['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <form method="post" action="<?= url('control/associations/' . (int) $a['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="<?= e(__('common.delete')) ?>">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($associationsList)): ?>
                        <tr><td colspan="6" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
