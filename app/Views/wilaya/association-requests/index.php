<?php
/**
 * Demandes d'inscription association — Liste (admin Wilaya).
 *
 * @var array $requests
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $status
 * @var string $q
 */
use App\Helpers\I18n;

$title = 'Demandes d\'inscription';
$page  = 'wilaya.association-requests.index';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$statusLabels = [
    'pending'               => ['badge-amber', 'mdi-clock-outline', 'En attente', 'قيد الانتظار'],
    'approved'              => ['badge-green', 'mdi-check-circle-outline', 'Approuvée', 'موافق عليها'],
    'rejected'              => ['badge-red',   'mdi-close-circle-outline', 'Refusée', 'مرفوضة'],
    'modification_requested' => ['badge-orange', 'mdi-file-document-edit', 'En attente de modifications', 'في انتظار التعديلات'],
];

$statusFilter = [
    ''                       => ['Toutes', 'الكل', 'mdi-view-list'],
    'pending'                => ['En attente', 'قيد الانتظار', 'mdi-clock-outline'],
    'approved'               => ['Approuvées', 'موافق عليها', 'mdi-check-circle-outline'],
    'rejected'               => ['Refusées', 'مرفوضة', 'mdi-close-circle-outline'],
    'modification_requested' => ['Modifications', 'تعديلات', 'mdi-file-document-edit'],
];

$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$modificationCount = 0;
foreach ($requests as $r) {
    match ($r['status'] ?? '') {
        'pending'               => $pendingCount++,
        'approved'              => $approvedCount++,
        'rejected'              => $rejectedCount++,
        'modification_requested' => $modificationCount++,
        default                 => null,
    };
}
?>

<style>
.wh-req-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: .75rem;
}
.wh-req-stat {
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
.wh-req-stat:hover { transform: translateY(-2px); box-shadow: var(--wh-shadow-lg); color: inherit; }
.wh-req-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: .7rem;
    display: grid;
    place-items: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.wh-req-filters {
    background: var(--wh-white);
    border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius);
    padding: .75rem 1rem;
    box-shadow: var(--wh-shadow);
}

.wh-req-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .4rem .85rem;
    border-radius: 999px;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid var(--wh-border);
    color: var(--wh-text-muted);
    background: var(--wh-white);
    transition: all .15s;
}
.wh-req-chip:hover { border-color: var(--wh-blue); color: var(--wh-blue); }
.wh-req-chip.active { background: var(--wh-blue-soft); color: var(--wh-blue); border-color: var(--wh-blue); }
.wh-req-chip .mdi { font-size: 1rem; }
.wh-req-chip-count {
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--wh-gray-soft);
    font-size: .68rem;
    font-weight: 700;
    padding: 0 5px;
}
.wh-req-chip.active .wh-req-chip-count { background: rgba(11,94,215,.15); }

.wh-req-table-card {
    background: var(--wh-white);
    border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius);
    box-shadow: var(--wh-shadow);
    overflow: hidden;
}
.wh-req-table-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .85rem 1.1rem;
    border-bottom: 1px solid var(--wh-border);
    background: var(--wh-gray-soft);
}

.wh-req-row {
    transition: background .12s;
}
.wh-req-row:hover { background: var(--wh-gray-soft) !important; }

.wh-req-avatar {
    width: 38px;
    height: 38px;
    border-radius: .6rem;
    display: inline-grid;
    place-items: center;
    font-weight: 700;
    font-size: .85rem;
    color: #fff;
    flex-shrink: 0;
}

.wh-req-empty {
    text-align: center;
    padding: 3.5rem 1.5rem;
}
.wh-req-empty .mdi { font-size: 3.5rem; color: var(--wh-gray-light); margin-bottom: .75rem; }
.wh-req-empty p { color: var(--wh-text-muted); }

@media (max-width: 767.98px) {
    .wh-req-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 575.98px) {
    .wh-req-stats { grid-template-columns: 1fr; }
}
</style>

<div class="wh-page">
    <!-- Hero -->
    <div class="wh-hero-panel mb-4" style="--hero-a:#0B5ED7;--hero-b:#6d28d9">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-account-plus"></i>
                    <?= $isAr ? 'طلبات انضمام الجمعيات' : 'Demandes d\'inscription' ?>
                </h1>
                <p class="mt-1 mb-0"><?= (int) $total ?> <?= $isAr ? 'طلب' : 'demande(s)' ?> <?= $isAr ? 'في النظام' : 'au total' ?></p>
            </div>
        </div>
    </div>

    <!-- KPI Stats -->
    <div class="wh-req-stats mb-4">
        <a href="<?= url('admin/association-requests') ?>" class="wh-req-stat <?= $status === '' ? 'border-primary shadow' : '' ?>">
            <div class="wh-req-stat-icon" style="background:var(--wh-gray-soft);color:var(--wh-gray)">
                <i class="mdi mdi-view-list"></i>
            </div>
            <div>
                <div class="wh-stat-val"><?= (int) $total ?></div>
                <div class="wh-stat-label"><?= $isAr ? 'المجموع' : 'Total' ?></div>
            </div>
        </a>
        <a href="<?= url('admin/association-requests?status=pending') ?>" class="wh-req-stat <?= $status === 'pending' ? 'border-warning shadow' : '' ?>">
            <div class="wh-req-stat-icon" style="background:#fff3cd;color:#b45309">
                <i class="mdi mdi-clock-outline"></i>
            </div>
            <div>
                <div class="wh-stat-val"><?= $pendingCount ?></div>
                <div class="wh-stat-label"><?= $isAr ? 'قيد الانتظار' : 'En attente' ?></div>
            </div>
        </a>
        <a href="<?= url('admin/association-requests?status=approved') ?>" class="wh-req-stat <?= $status === 'approved' ? 'border-success shadow' : '' ?>">
            <div class="wh-req-stat-icon" style="background:var(--wh-green-soft);color:var(--wh-green)">
                <i class="mdi mdi-check-circle-outline"></i>
            </div>
            <div>
                <div class="wh-stat-val"><?= $approvedCount ?></div>
                <div class="wh-stat-label"><?= $isAr ? 'موافق عليها' : 'Approuvées' ?></div>
            </div>
        </a>
        <a href="<?= url('admin/association-requests?status=rejected') ?>" class="wh-req-stat <?= $status === 'rejected' ? 'border-danger shadow' : '' ?>">
            <div class="wh-req-stat-icon" style="background:#f8d7da;color:#b02a37">
                <i class="mdi mdi-close-circle-outline"></i>
            </div>
            <div>
                <div class="wh-stat-val"><?= $rejectedCount ?></div>
                <div class="wh-stat-label"><?= $isAr ? 'مرفوضة' : 'Refusées' ?></div>
            </div>
        </a>
        <a href="<?= url('admin/association-requests?status=modification_requested') ?>" class="wh-req-stat <?= $status === 'modification_requested' ? 'border-orange shadow' : '' ?>">
            <div class="wh-req-stat-icon" style="background:#fff3cd;color:#b45309">
                <i class="mdi mdi-file-document-edit"></i>
            </div>
            <div>
                <div class="wh-stat-val"><?= $modificationCount ?></div>
                <div class="wh-stat-label"><?= $isAr ? 'تعديلات' : 'Modifications' ?></div>
            </div>
        </a>
    </div>

    <!-- Filters -->
    <div class="wh-req-filters mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="get" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <div class="wh-input-icon-wrap flex-grow-1" style="max-width:280px">
                    <i class="mdi mdi-magnify"></i>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="<?= $isAr ? 'بحث...' : 'Rechercher...' ?>" value="<?= e($q) ?>">
                </div>
                <input type="hidden" name="status" value="<?= e($status) ?>">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="mdi mdi-magnify me-1"></i><?= $isAr ? 'بحث' : 'Chercher' ?>
                </button>
            </form>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($statusFilter as $val => $info): ?>
                    <a href="<?= url('admin/association-requests' . ($val !== '' ? '?status=' . $val : '') . ($q !== '' ? ($val !== '' ? '&' : '?') . 'q=' . urlencode($q) : '')) ?>"
                       class="wh-req-chip <?= $status === $val ? 'active' : '' ?>">
                        <i class="mdi <?= $info[2] ?>"></i>
                        <?= e($isAr ? $info[1] : $info[0]) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="wh-req-table-card">
        <div class="wh-req-table-top">
            <h3 class="d-flex align-items-center gap-2 mb-0" style="font-size:.9rem;font-weight:700">
                <i class="mdi mdi-format-list-bulleted" style="color:var(--wh-blue)"></i>
                <?= $isAr ? 'قائمة الطلبات' : 'Liste des demandes' ?>
                <span class="wh-badge badge-blue" style="font-size:.7rem"><?= e($total) ?></span>
            </h3>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th><?= $isAr ? 'الجمعية' : 'Association' ?></th>
                    <th><?= $isAr ? 'الرئيس' : 'Président' ?></th>
                    <th><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></th>
                    <th><?= $isAr ? 'الهاتف' : 'Téléphone' ?></th>
                    <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                    <th><?= $isAr ? 'بتاريخ' : 'Soumise le' ?></th>
                    <th style="width:100px"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <?php
                    $st = $statusLabels[$r['status']] ?? ['badge-gray', 'mdi-help-circle', $r['status'], $r['status']];
                    $initials = mb_substr($r['president_firstname'] ?? '', 0, 1) . mb_substr($r['president_lastname'] ?? '', 0, 1);
                    $avatarBg = match($r['status'] ?? '') {
                        'approved' => 'background:var(--wh-green-soft);color:var(--wh-green)',
                        'rejected' => 'background:#f8d7da;color:#b02a37',
                        default    => 'background:#fff3cd;color:#b45309',
                    };
                    ?>
                    <tr class="wh-req-row">
                        <td><span class="wh-id-badge"><?= (int) $r['id'] ?></span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="wh-req-avatar" style="<?= $avatarBg ?>"><?= e(mb_substr($r['association_name'] ?? '', 0, 1)) ?></span>
                                <div>
                                    <a href="<?= url('admin/association-requests/' . (int) $r['id']) ?>" class="fw-semibold text-decoration-none wh-event-link" style="font-size:.88rem">
                                        <?= e($r['association_name']) ?>
                                    </a>
                                    <?php if (!empty($r['approval_number'])): ?>
                                        <div style="font-size:.72rem;color:var(--wh-text-muted)">N° <?= e($r['approval_number']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="wh-req-avatar" style="background:var(--wh-blue-soft);color:var(--wh-blue);width:32px;height:32px;font-size:.75rem">
                                    <?= e($initials) ?>
                                </span>
                                <span style="font-size:.85rem"><?= e($r['president_firstname'] . ' ' . $r['president_lastname']) ?></span>
                            </div>
                        </td>
                        <td><span style="font-size:.82rem;color:var(--wh-text-muted)"><?= e($r['email']) ?></span></td>
                        <td><span style="font-size:.82rem;color:var(--wh-text-muted)"><?= e($r['phone']) ?></span></td>
                        <td>
                            <span class="wh-status-pill badge <?= $st[0] ?>">
                                <i class="mdi <?= $st[1] ?>"></i>
                                <?= e($st[2]) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size:.82rem;color:var(--wh-text-muted)">
                                <i class="mdi mdi-calendar-outline" style="font-size:.9rem"></i>
                                <?= e($r['created_at']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-end gap-1" style="opacity:.5;transition:opacity .15s">
                                <a href="<?= url('admin/association-requests/' . (int) $r['id']) ?>"
                                   class="btn btn-sm btn-outline-primary" title="<?= $isAr ? 'عرض' : 'Voir' ?>">
                                    <i class="mdi mdi-eye-outline"></i>
                                </a>
                                <a href="<?= url('admin/association-requests/' . (int) $r['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary" title="<?= $isAr ? 'تعديل' : 'Modifier' ?>">
                                    <i class="mdi mdi-pencil-outline"></i>
                                </a>
                                <form method="post" action="<?= url('admin/association-requests/' . (int) $r['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Supprimer définitivement la demande de « <?= e($r['association_name']) ?> » ?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= $isAr ? 'حذف' : 'Supprimer' ?>">
                                        <i class="mdi mdi-trash-can-outline"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($requests === []): ?>
                    <tr>
                        <td colspan="8">
                            <div class="wh-req-empty">
                                <i class="mdi mdi-account-plus-outline d-block"></i>
                                <p class="mb-1 fw-semibold"><?= $isAr ? 'لا توجد طلبات' : 'Aucune demande d\'inscription' ?></p>
                                <p style="font-size:.8rem;color:var(--wh-text-muted)">
                                    <?= $isAr ? 'لم يتم العثور على طلبات مطابقة' : 'Aucune demande ne correspond à vos critères' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($lastPage > 1): ?>
    <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination pagination-sm">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= e($status) ?>&q=<?= e($q) ?>">
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= e($status) ?>&q=<?= e($q) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $lastPage): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= e($status) ?>&q=<?= e($q) ?>">
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
