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

$statusLabels = [
    'pending'  => ['badge-warning', 'En attente'],
    'approved' => ['badge-success', 'Approuvée'],
    'rejected' => ['badge-danger',  'Refusée'],
];

$statusFilter = [
    ''          => 'Toutes',
    'pending'   => 'En attente',
    'approved'  => 'Approuvées',
    'rejected'  => 'Refusées',
];
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-account-plus me-2"></i><?= e(__('common.associations')) ?>
            </h1>
            <p class="wh-page-sub"><?= (int) $total ?> demande<?= $total !== 1 ? 's' : '' ?></p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Rechercher..." value="<?= e($q) ?>" style="min-width:200px">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($statusFilter as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-filter me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Association</th>
                        <th>Président</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th>Soumise le</th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <?php $st = $statusLabels[$r['status']] ?? ['badge-secondary', $r['status']]; ?>
                        <tr>
                            <td class="text-muted fw-semibold"><?= (int) $r['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($r['association_name']) ?></div>
                                <?php if (! empty($r['approval_number'])): ?>
                                    <small class="text-muted">N° <?= e($r['approval_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($r['president_firstname'] . ' ' . $r['president_lastname']) ?></td>
                            <td><small><?= e($r['email']) ?></small></td>
                            <td><small><?= e($r['phone']) ?></small></td>
                            <td>
                                <span class="wh-badge <?= e($st[0]) ?>">
                                    <?= e($st[1]) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= e($r['created_at']) ?></small></td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url('admin/association-requests/' . (int) $r['id']) ?>"
                                       class="btn btn-sm btn-outline-primary" title="Voir">
                                        <i class="mdi mdi-eye-outline"></i>
                                    </a>
                                    <a href="<?= url('admin/association-requests/' . (int) $r['id'] . '/edit') ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Modifier">
                                        <i class="mdi mdi-pencil-outline"></i>
                                    </a>
                                    <form method="post" action="<?= url('admin/association-requests/' . (int) $r['id'] . '/delete') ?>"
                                          class="d-inline" onsubmit="return confirm('Supprimer définitivement la demande de « <?= e($r['association_name']) ?> » ?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($requests === []): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="mdi mdi-account-plus-outline" style="font-size:2.5rem;color:#d1d5db"></i>
                                <p class="text-muted mt-2 mb-0">Aucune demande d'inscription</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($lastPage > 1): ?>
        <nav class="mt-3 d-flex justify-content-center">
            <ul class="pagination pagination-sm">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&status=<?= e($status) ?>&q=<?= e($q) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
