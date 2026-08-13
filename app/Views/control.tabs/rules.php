<?php
/** @var array $regles */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <a class="btn btn-primary" href="<?= url('control/regles/create') ?>">
        <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
    </a>
</div>

<div class="futur-card">
    <div class="futur-card-body p-0">
        <div class="table-responsive">
            <table class="futur-table mb-0">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'المفتاح' : 'Clé' ?></th>
                        <th><?= $isAr ? 'النشاط' : 'Activité' ?></th>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'القيمة' : 'Valeur' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'État' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regles as $r): ?>
                        <tr>
                            <td><strong><?= e($r['cle']) ?></strong></td>
                            <td><?= e($r['activite'] ?? '—') ?></td>
                            <td><span class="badge bg-info"><?= e($r['type'] ?? '—') ?></span></td>
                            <td><?= e((string) ($r['valeur'] ?? '')) ?></td>
                            <td>
                                <span class="futur-chip chip-<?= (int) ($r['actif'] ?? 1) ? 'success' : 'gray' ?>">
                                    <?= (int) ($r['actif'] ?? 1) ? ($isAr ? 'نشط' : 'Active') : ($isAr ? 'معطل' : 'Inactive') ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="<?= url('control/regles/' . (int) $r['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <form method="post" action="<?= url('control/regles/' . (int) $r['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="<?= e(__('common.delete')) ?>">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($regles)): ?>
                        <tr><td colspan="6" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
