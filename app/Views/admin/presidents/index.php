<?php
/** @var array $presidents @var string $q @var int $page @var int $lastPage @var int $total @var array $errors */
$title = $isAr ? 'الرئيسون' : 'Présidents';
$page  = 'admin.presidents.index';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= $isAr ? 'رئيسو الجمعيات' : 'Présidents d\'associations' ?></h1>
            <p class="wh-page-sub"><?= e($total) ?> <?= $isAr ? 'رئيس' : 'présidents' ?></p>
        </div>
    </div>

    <form method="get" action="<?= url('admin/presidents') ?>" class="wh-filters mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" value="<?= e($q) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-variant me-1"></i><?= e(__('common.filters')) ?></button>
                <a href="<?= url('admin/presidents') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-refresh"></i></a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                    <th><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></th>
                    <th><?= $isAr ? 'الهاتف' : 'Téléphone' ?></th>
                    <th><?= $isAr ? 'الجمعية' : 'Association' ?></th>
                    <th><?= $isAr ? 'حالة الجمعية' : 'Statut association' ?></th>
                    <th><?= $isAr ? 'حالة الحساب' : 'Statut compte' ?></th>
                    <th><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($presidents as $p): ?>
                    <tr>
                        <td>
                            <a href="<?= url('admin/presidents/' . $p['id']) ?>" class="text-decoration-none fw-semibold"><?= e($p['prenom'] . ' ' . $p['nom']) ?></a>
                        </td>
                        <td class="wh-text-muted"><?= e($p['email']) ?></td>
                        <td class="wh-text-muted"><?= e($p['telephone'] ?? '-') ?></td>
                        <td class="fw-semibold"><?= e($p['association_nom']) ?></td>
                        <td>
                            <?php if ((int) $p['association_valide'] === 1): ?>
                                <span class="wh-badge badge-green"><?= $isAr ? 'موثقة' : 'Validée' ?></span>
                            <?php else: ?>
                                <span class="wh-badge badge-yellow"><?= $isAr ? 'قيد المراجعة' : 'En attente' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $p['is_active'] === 1): ?>
                                <span class="wh-badge badge-green"><?= $isAr ? 'نشط' : 'Actif' ?></span>
                            <?php else: ?>
                                <span class="wh-badge badge-red"><?= $isAr ? 'غير نشط' : 'Inactif' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/presidents/' . $p['id']) ?>" title="<?= e(__('common.view')) ?>"><i class="mdi mdi-eye"></i></a>
                                <form method="post" action="<?= url('admin/presidents/' . $p['id'] . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?= (int) $p['is_active'] === 1 ? 'danger' : 'success' ?>" title="<?= $isAr ? 'تغيير الحالة' : 'Changer statut' ?>">
                                        <i class="mdi mdi-<?= (int) $p['is_active'] === 1 ? 'account-off' : 'account-check' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($presidents === []): ?>
                    <tr><td colspan="7"><div class="wh-empty"><i class="mdi mdi-account-tie"></i><p><?= e(__('common.no_data')) ?></p></div></td></tr>
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
                    <a class="page-link" href="<?= url('admin/presidents?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
