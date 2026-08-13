<?php
/** @var array $users */
use App\Helpers\I18n;
use App\Helpers\Rbac;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <a class="btn btn-primary" href="<?= url('control/utilisateurs/create') ?>">
        <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
    </a>
</div>

<div class="futur-card">
    <div class="futur-card-body p-0">
        <div class="table-responsive">
            <table class="futur-table mb-0">
                <thead>
                    <tr>
                        <th><?= e(__('common.name')) ?></th>
                        <th><?= e(__('common.email')) ?></th>
                        <th><?= e(__('common.role')) ?></th>
                        <th><?= $isAr ? 'الجمعية' : 'Association' ?></th>
                        <th><?= $isAr ? 'EPIC' : 'EPIC' ?></th>
                        <th><?= e(__('common.status')) ?></th>
                        <th><?= e(__('common.created')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['prenom'] . ' ' . $u['nom']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= Rbac::color($u['role_user']) ?>">
                                    <?= e(Rbac::label($u['role_user'])) ?>
                                </span>
                            </td>
                            <td><?= e($u['association_nom'] ?? '—') ?></td>
                            <td><?= e($u['epic_nom'] ?? '—') ?></td>
                            <td>
                                <span class="badge bg-<?= (int) $u['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                    <?= (int) $u['is_active'] === 1 ? ($isAr ? 'نشط' : 'Actif') : ($isAr ? 'غير نشط' : 'Inactif') ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime((string) $u['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="<?= url('control/utilisateurs/' . (int) $u['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <form method="post" action="<?= url('control/utilisateurs/' . (int) $u['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="<?= e(__('common.delete')) ?>">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>