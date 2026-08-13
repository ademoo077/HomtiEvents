<?php
/** @var array $users @var string $q @var string $role @var int $page @var int $lastPage @var int $total @var array $errors */
$title = __('common.users');
$page  = 'admin.users.index';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';

$roles = [
    'citoyen'     => $isAr ? 'المواطنون' : 'Citoyens',
    'association' => $isAr ? 'رؤساء الجمعيات' : 'Présidents d\'associations',
    'epic'        => 'EPIC',
    'wilaya'      => $isAr ? 'الولاية' : 'Wilaya',
];

$showCitoyenCols    = $role === 'citoyen';
$showAssocCol       = $role === '' || $role === 'association' || $role === 'wilaya';
$showAssocStatutCol = $role === '' || $role === 'association';
$showEpicCol        = $role === '' || $role === 'epic';

$ncols = 3
    + ($showCitoyenCols ? 3 : 0)
    + ($showAssocCol ? 1 : 0)
    + ($showAssocStatutCol ? 1 : 0)
    + ($showEpicCol ? 1 : 0)
    + 2;
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= $isAr ? 'إدارة المستخدمين' : 'Gestion des utilisateurs' ?></h1>
            <p class="wh-page-sub"><?= e($total) ?> <?= $isAr ? 'مستخدم' : 'utilisateurs' ?></p>
        </div>
    </div>

    <form method="get" action="<?= url('admin/users') ?>" class="wh-filters mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" value="<?= e($q) ?>">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value=""><?= $isAr ? 'جميع الأدوار' : 'Tous les rôles' ?></option>
                    <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $role === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-variant me-1"></i><?= e(__('common.filters')) ?></button>
                <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-refresh"></i></a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th><?= e(__('common.nom')) ?></th>
                    <th><?= e(__('common.email')) ?></th>
                    <th><?= e(__('common.role')) ?></th>
                    <?php if ($showCitoyenCols): ?>
                        <th><?= e(__('common.telephone')) ?></th>
                        <th><?= e(__('common.participants')) ?></th>
                        <th><?= e(__('common.points')) ?></th>
                    <?php endif; ?>
                    <?php if ($showAssocCol): ?>
                        <th><?= $isAr ? 'الجمعية' : 'Association' ?></th>
                    <?php endif; ?>
                    <?php if ($showAssocStatutCol): ?>
                        <th><?= $isAr ? 'حالة الجمعية' : 'Statut association' ?></th>
                    <?php endif; ?>
                    <?php if ($showEpicCol): ?>
                        <th>EPIC</th>
                    <?php endif; ?>
                    <th><?= e(__('common.status')) ?></th>
                    <th><?= e(__('common.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <a href="<?= url('admin/users/' . $u['id']) ?>" class="text-decoration-none fw-semibold"><?= e($u['prenom'] . ' ' . $u['nom']) ?></a>
                        </td>
                        <td class="wh-text-muted"><?= e($u['email']) ?></td>
                        <td>
                            <span class="wh-badge badge-<?= match($u['role_user']) {
                                'wilaya' => 'violet',
                                'association' => 'blue',
                                'epic' => 'cyan',
                                default => 'gray'
                            } ?>"><?= e(ucfirst($u['role_user'])) ?></span>
                        </td>
                        <?php if ($showCitoyenCols): ?>
                            <td class="wh-text-muted"><?= e($u['telephone'] ?? '-') ?></td>
                            <td><span class="wh-badge badge-blue"><?= (int) $u['participations'] ?></span></td>
                            <td><span class="wh-badge badge-violet"><?= (int) $u['points'] ?> pts</span></td>
                        <?php endif; ?>
                        <?php if ($showAssocCol): ?>
                            <td class="wh-text-muted"><?= e($u['association_nom'] ?? '-') ?></td>
                        <?php endif; ?>
                        <?php if ($showAssocStatutCol): ?>
                            <td>
                                <?php if ($u['association_nom'] !== null): ?>
                                    <?php if ((int) $u['association_valide'] === 1): ?>
                                        <span class="wh-badge badge-green"><?= $isAr ? 'موثقة' : 'Validée' ?></span>
                                    <?php else: ?>
                                        <span class="wh-badge badge-yellow"><?= $isAr ? 'قيد المراجعة' : 'En attente' ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="wh-text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if ($showEpicCol): ?>
                            <td class="wh-text-muted"><?= e($u['epic_nom'] ?? '-') ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ((int) $u['is_active'] === 1): ?>
                                <span class="wh-badge badge-green"><?= $isAr ? 'نشط' : 'Actif' ?></span>
                            <?php else: ?>
                                <span class="wh-badge badge-red"><?= $isAr ? 'غير نشط' : 'Inactif' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/users/' . $u['id']) ?>" title="<?= e(__('common.view')) ?>"><i class="mdi mdi-eye"></i></a>
                                <form method="post" action="<?= url('admin/users/' . $u['id'] . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?= (int) $u['is_active'] === 1 ? 'danger' : 'success' ?>" title="<?= $isAr ? 'تغيير الحالة' : 'Changer statut' ?>">
                                        <i class="mdi mdi-<?= (int) $u['is_active'] === 1 ? 'account-off' : 'account-check' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="<?= url('admin/users/' . $u['id'] . '/delete') ?>" class="d-inline"
                                      data-confirm="<?= $isAr ? 'هل أنت متأكد من أرشفة هذا الحساب؟ سيفقد صاحبه إمكانية الدخول.' : 'Archiver ce compte ? Son propriétaire ne pourra plus se connecter.' ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= $isAr ? 'أرشفة' : 'Archiver' ?>">
                                        <i class="mdi mdi-archive-outline"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($users === []): ?>
                    <tr><td colspan="<?= $ncols ?>"><div class="wh-empty"><i class="mdi mdi-account-group"></i><p><?= e(__('common.no_data')) ?></p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($lastPage > 1): ?>
    <nav class="d-flex justify-content-center mt-4" aria-label="Pagination">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('admin/users?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '') . ($role !== '' ? '&role=' . urlencode($role) : '')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
