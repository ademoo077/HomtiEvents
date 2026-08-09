<?php
/** @var array $citoyens @var string $q @var int $page @var int $lastPage @var int $total @var array $errors */
$title = __('common.citoyens');
$page  = 'admin.citoyens.index';
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('common.citoyens')) ?></h1>
            <p class="wh-page-sub"><?= e($total) ?> <?= e(__('common.citoyens')) ?></p>
        </div>
    </div>

    <form method="get" action="<?= url('admin/citoyens') ?>" class="wh-filters mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" value="<?= e($q) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-variant me-1"></i><?= e(__('common.filters')) ?></button>
                <a href="<?= url('admin/citoyens') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-refresh"></i></a>
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
                    <th><?= e(__('common.telephone')) ?></th>
                    <th><?= e(__('common.participants')) ?></th>
                    <th><?= e(__('common.points')) ?></th>
                    <th><?= e(__('common.status')) ?></th>
                    <th><?= e(__('common.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($citoyens as $c): ?>
                    <tr>
                        <td>
                            <a href="<?= url('admin/citoyens/' . $c['id']) ?>" class="text-decoration-none fw-semibold"><?= e($c['prenom'] . ' ' . $c['nom']) ?></a>
                        </td>
                        <td class="wh-text-muted"><?= e($c['email']) ?></td>
                        <td class="wh-text-muted"><?= e($c['telephone'] ?? '-') ?></td>
                        <td><span class="wh-badge badge-blue"><?= (int) $c['participations'] ?></span></td>
                        <td><span class="wh-badge badge-violet"><?= (int) $c['points'] ?> pts</span></td>
                        <td>
                            <?php if ((int) $c['is_active'] === 1): ?>
                                <span class="wh-badge badge-green"><?= e(__('common.validate')) ?></span>
                            <?php else: ?>
                                <span class="wh-badge badge-red"><?= e(__('common.reject')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/citoyens/' . $c['id']) ?>" title="<?= e(__('common.view')) ?>"><i class="mdi mdi-eye"></i></a>
                                <form method="post" action="<?= url('admin/citoyens/' . $c['id'] . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-<?= (int) $c['is_active'] === 1 ? 'danger' : 'success' ?>">
                                        <i class="mdi mdi-<?= (int) $c['is_active'] === 1 ? 'account-off' : 'account-check' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($citoyens === []): ?>
                    <tr><td colspan="7"><div class="wh-empty"><i class="mdi mdi-account-group"></i><p><?= e(__('common.no_data')) ?></p></div></td></tr>
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
                    <a class="page-link" href="<?= url('admin/citoyens?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
