<?php
/** @var array $users @var string $q @var string $role @var int $page @var int $lastPage @var int $total @var array $errors */
$title = __('common.users');
$viewPage  = 'admin.users.index';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';

$roles = [
    'citoyen'     => $isAr ? 'المواطنون' : 'Citoyens',
    'association' => $isAr ? 'رؤساء الجمعيات' : 'Présidents d\'associations',
    'epic'        => 'EPIC',
    'wilaya'      => $isAr ? 'الولاية' : 'Wilaya',
];

$roleIcons = [
    'citoyen'     => 'mdi-account-outline',
    'association' => 'mdi-handshake',
    'epic'        => 'mdi-satellite-variant',
    'wilaya'      => 'mdi-shield-star',
];

$roleBadgeColors = [
    'wilaya'      => 'violet',
    'association' => 'blue',
    'epic'        => 'cyan',
    'membre'      => 'gray',
    'citoyen'     => 'gray',
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

$roleCounts = ['citoyen' => 0, 'association' => 0, 'epic' => 0, 'wilaya' => 0];
foreach ($users as $u) {
    $rk = $u['role_user'] ?? 'citoyen';
    if (isset($roleCounts[$rk])) $roleCounts[$rk]++;
}
?>

<style>
.wh-role-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
    gap: .75rem;
}
.wh-role-stat {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: 1rem 1.1rem;
    border-radius: var(--wh-radius);
    background: var(--wh-white);
    border: 1px solid var(--wh-border);
    box-shadow: var(--wh-shadow);
    text-decoration: none;
    color: inherit;
    transition: transform .15s, box-shadow .15s;
}
.wh-role-stat:hover { transform: translateY(-2px); box-shadow: var(--wh-shadow-lg); color: inherit; }
.wh-role-stat.active { border-color: var(--wh-blue); box-shadow: 0 0 0 3px rgba(11,94,215,.12); }
.wh-role-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: .7rem;
    display: grid;
    place-items: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.wh-users-table-card {
    background: var(--wh-white);
    border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius);
    box-shadow: var(--wh-shadow);
    overflow: hidden;
}
.wh-users-table-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .85rem 1.1rem;
    border-bottom: 1px solid var(--wh-border);
    background: var(--wh-gray-soft);
}

.wh-user-row { transition: background .12s; }
.wh-user-row:hover { background: var(--wh-gray-soft) !important; }

.wh-user-link {
    color: var(--wh-text);
    text-decoration: none;
    font-weight: 600;
    transition: color .15s;
}
.wh-user-row:hover .wh-user-link { color: var(--wh-blue); }

.wh-user-avatar {
    width: 38px;
    height: 38px;
    border-radius: .6rem;
    display: inline-grid;
    place-items: center;
    font-weight: 700;
    font-size: .82rem;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--wh-blue), #4f83d8);
}

.wh-user-actions { opacity: .4; transition: opacity .15s; }
.wh-user-row:hover .wh-user-actions { opacity: 1; }

@media (max-width: 767.98px) {
    .wh-role-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 575.98px) {
    .wh-role-stats { grid-template-columns: 1fr; }
}
</style>

<div class="wh-page">
    <!-- Hero -->
    <div class="wh-hero-panel mb-4" style="--hero-a:#6d28d9;--hero-b:#0B5ED7">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-account-group"></i>
                    <?= $isAr ? 'إدارة المستخدمين' : 'Gestion des utilisateurs' ?>
                </h1>
                <p class="mt-1 mb-0"><?= e($total) ?> <?= $isAr ? 'مستخدم' : 'utilisateur(s)' ?> <?= $isAr ? 'في النظام' : 'enregistré(s)' ?></p>
            </div>
            <a href="<?= url('admin/users/create') ?>" class="btn btn-light" style="position:relative;z-index:1">
                <i class="mdi mdi-account-plus-outline me-1"></i><?= $isAr ? 'إضافة مستخدم' : 'Créer un utilisateur' ?>
            </a>
        </div>
    </div>

    <!-- Role Stats -->
    <div class="wh-role-stats mb-4">
        <a href="<?= url('admin/users') ?>" class="wh-role-stat <?= $role === '' ? 'active' : '' ?>">
            <div class="wh-role-stat-icon" style="background:var(--wh-gray-soft);color:var(--wh-gray)">
                <i class="mdi mdi-account-group"></i>
            </div>
            <div>
                <div class="wh-stat-val"><?= e($total) ?></div>
                <div class="wh-stat-label"><?= $isAr ? 'المجموع' : 'Tous' ?></div>
            </div>
        </a>
        <?php foreach ($roles as $val => $label): ?>
            <a href="<?= url('admin/users?role=' . $val) ?>" class="wh-role-stat <?= $role === $val ? 'active' : '' ?>">
                <div class="wh-role-stat-icon" style="background:var(--wh-<?= match($val) {
                    'wilaya' => 'blue-soft',
                    'association' => 'green-soft',
                    'epic' => 'gray-soft',
                    default => 'gray-soft',
                } ?>);color:var(--wh-<?= match($val) {
                    'wilaya' => 'blue',
                    'association' => 'green',
                    'epic' => 'gray',
                    default => 'gray',
                } ?>)">
                    <i class="mdi <?= $roleIcons[$val] ?>"></i>
                </div>
                <div>
                    <div class="wh-stat-val"><?= $roleCounts[$val] ?? 0 ?></div>
                    <div class="wh-stat-label"><?= e($label) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filter Bar -->
    <form method="get" action="<?= url('admin/users') ?>" class="wh-filter-bar mb-4">
        <div class="d-flex flex-wrap align-items-end gap-2">
            <div class="flex-grow-1" style="max-width:320px">
                <div class="wh-input-icon-wrap">
                    <i class="mdi mdi-magnify"></i>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="<?= $isAr ? 'بحث بالاسم أو البريد...' : 'Rechercher par nom, email...' ?>" value="<?= e($q) ?>">
                </div>
            </div>
            <div style="max-width:200px">
                <label class="form-label"><i class="mdi mdi-shield-account me-1"></i><?= $isAr ? 'الدور' : 'Rôle' ?></label>
                <select name="role" class="form-select form-select-sm">
                    <option value=""><?= $isAr ? 'جميع الأدوار' : 'Tous les rôles' ?></option>
                    <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $role === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="mdi mdi-magnify me-1"></i><?= $isAr ? 'بحث' : 'Filtrer' ?>
                </button>
                <?php if ($q !== '' || $role !== ''): ?>
                    <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-close"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="wh-users-table-card">
        <div class="wh-users-table-top">
            <h3 class="d-flex align-items-center gap-2 mb-0" style="font-size:.9rem;font-weight:700">
                <i class="mdi mdi-format-list-bulleted" style="color:var(--wh-blue)"></i>
                <?= $isAr ? 'قائمة المستخدمين' : 'Liste des utilisateurs' ?>
                <span class="wh-badge badge-blue" style="font-size:.7rem"><?= e($total) ?></span>
            </h3>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
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
                        <th><?= $isAr ? 'حالة الجمعية' : 'Statut assoc.' ?></th>
                    <?php endif; ?>
                    <?php if ($showEpicCol): ?>
                        <th>EPIC</th>
                    <?php endif; ?>
                    <th><?= e(__('common.status')) ?></th>
                    <th style="width:100px"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <?php
                    $initials = mb_substr($u['prenom'] ?? '', 0, 1) . mb_substr($u['nom'] ?? '', 0, 1);
                    $avatarGrad = match($u['role_user'] ?? '') {
                        'wilaya'      => 'linear-gradient(135deg, #6d28d9, #8b5cf6)',
                        'association' => 'linear-gradient(135deg, var(--wh-blue), #4f83d8)',
                        'epic'        => 'linear-gradient(135deg, #06b6d4, #22d3ee)',
                        default       => 'linear-gradient(135deg, var(--wh-gray), #94a3b8)',
                    };
                    ?>
                    <tr class="wh-user-row">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="wh-user-avatar" style="background:<?= $avatarGrad ?>"><?= e($initials) ?></span>
                                <a href="<?= url('admin/users/' . $u['id']) ?>" class="wh-user-link"><?= e($u['prenom'] . ' ' . $u['nom']) ?></a>
                            </div>
                        </td>
                        <td><span style="font-size:.84rem;color:var(--wh-text-muted)"><?= e($u['email']) ?></span></td>
                        <td>
                            <span class="wh-badge badge-<?= $roleBadgeColors[$u['role_user']] ?? 'gray' ?>">
                                <i class="mdi <?= $roleIcons[$u['role_user']] ?? 'mdi-account' ?>"></i>
                                <?= e(ucfirst($u['role_user'])) ?>
                            </span>
                        </td>
                        <?php if ($showCitoyenCols): ?>
                            <td style="font-size:.84rem;color:var(--wh-text-muted)"><?= e($u['telephone'] ?? '-') ?></td>
                            <td><span class="wh-badge badge-blue"><?= (int) $u['participations'] ?></span></td>
                            <td><span class="wh-badge badge-violet"><?= (int) $u['points'] ?> pts</span></td>
                        <?php endif; ?>
                        <?php if ($showAssocCol): ?>
                            <td style="font-size:.84rem;color:var(--wh-text-muted)"><?= e($u['association_nom'] ?? '-') ?></td>
                        <?php endif; ?>
                        <?php if ($showAssocStatutCol): ?>
                            <td>
                                <?php if ($u['association_nom'] !== null): ?>
                                    <?php if ((int) $u['association_valide'] === 1): ?>
                                        <span class="wh-badge badge-green"><i class="mdi mdi-check-circle-outline"></i> <?= $isAr ? 'موثقة' : 'Validée' ?></span>
                                    <?php else: ?>
                                        <span class="wh-badge badge-amber"><i class="mdi mdi-clock-outline"></i> <?= $isAr ? 'قيد المراجعة' : 'En attente' ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--wh-text-muted)">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if ($showEpicCol): ?>
                            <td style="font-size:.84rem;color:var(--wh-text-muted)"><?= e($u['epic_nom'] ?? '-') ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ((int) $u['is_active'] === 1): ?>
                                <span class="wh-badge badge-green"><i class="mdi mdi-check-circle-outline"></i> <?= $isAr ? 'نشط' : 'Actif' ?></span>
                            <?php else: ?>
                                <span class="wh-badge badge-red"><i class="mdi mdi-close-circle-outline"></i> <?= $isAr ? 'غير نشط' : 'Inactif' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 wh-user-actions">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/users/' . $u['id']) ?>" title="<?= e(__('common.view')) ?>">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                                <form method="post" action="<?= url('admin/users/' . $u['id'] . '/toggle') ?>" data-confirm="<?= $isAr ? 'هل أنت متأكد من تغيير حالة هذا الحساب؟' : 'Changer le statut de ce compte ?' ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?= (int) $u['is_active'] === 1 ? 'danger' : 'success' ?>" title="<?= $isAr ? 'تغيير الحالة' : 'Changer statut' ?>">
                                        <i class="mdi mdi-<?= (int) $u['is_active'] === 1 ? 'account-off' : 'account-check' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="<?= url('admin/users/' . $u['id'] . '/delete') ?>" class="d-inline"
                                      data-confirm="<?= $isAr ? 'هل أنت متأكد من أرشفة هذا الحساب؟' : 'Archiver ce compte ?' ?>">
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
                    <tr>
                        <td colspan="<?= $ncols ?>">
                            <div class="wh-empty-state">
                                <i class="mdi mdi-account-group d-block"></i>
                                <p class="mb-1 fw-semibold"><?= $isAr ? 'لا يوجد مستخدمون' : 'Aucun utilisateur trouvé' ?></p>
                                <p style="font-size:.8rem;color:var(--wh-text-muted)"><?= $isAr ? 'جرّب تغيير معايير البحث' : 'Essayez de modifier vos critères' ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($lastPage > 1): ?>
    <nav class="d-flex justify-content-center mt-4" aria-label="Pagination">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= url('admin/users?page=' . ($page - 1) . ($q !== '' ? '&q=' . urlencode($q) : '') . ($role !== '' ? '&role=' . urlencode($role) : '')) ?>"><i class="mdi mdi-chevron-left"></i></a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('admin/users?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '') . ($role !== '' ? '&role=' . urlencode($role) : '')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $lastPage): ?>
                <li class="page-item"><a class="page-link" href="<?= url('admin/users?page=' . ($page + 1) . ($q !== '' ? '&q=' . urlencode($q) : '') . ($role !== '' ? '&role=' . urlencode($role) : '')) ?>"><i class="mdi mdi-chevron-right"></i></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
