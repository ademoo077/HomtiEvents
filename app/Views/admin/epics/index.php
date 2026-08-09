<?php
/** @var array $epics @var string $q @var int $page @var int $lastPage @var int $total @var array $errors */
$title = __('common.epic');
$page  = 'admin.epics.index';
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('common.epic')) ?></h1>
            <p class="wh-page-sub"><?= e($total) ?> <?= e(__('common.epic')) ?></p>
        </div>
        <a class="btn btn-primary" href="<?= url('admin/epics/create') ?>">
            <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
        </a>
    </div>

    <form method="get" action="<?= url('admin/epics') ?>" class="wh-filters mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" value="<?= e($q) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-variant me-1"></i><?= e(__('common.filters')) ?></button>
                <a href="<?= url('admin/epics') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-refresh"></i></a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th><?= e(__('common.nom')) ?></th>
                    <th><?= e(__('common.description')) ?></th>
                    <th><?= e(__('common.anomalies')) ?></th>
                    <th><?= e(__('common.evenements')) ?></th>
                    <th><?= e(__('common.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($epics as $e): ?>
                    <tr>
                        <td>
                            <span class="wh-dot d-inline-block me-2" style="background:<?= e($e['couleur'] ?: 'var(--wh-gray)') ?>"></span>
                            <span class="fw-semibold"><?= e($e['nom']) ?></span>
                        </td>
                        <td class="wh-text-muted"><?= e(mb_strimwidth((string) ($e['description'] ?? ''), 0, 60, '…')) ?></td>
                        <td><span class="wh-badge badge-blue"><?= (int) $e['competences'] ?></span></td>
                        <td><span class="wh-badge badge-cyan"><?= (int) $e['interventions'] ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/epics/' . $e['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>"><i class="mdi mdi-pencil"></i></a>
                                <form method="post" action="<?= url('admin/epics/' . $e['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.delete')) ?>"><i class="mdi mdi-delete"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($epics === []): ?>
                    <tr><td colspan="5"><div class="wh-empty"><i class="mdi mdi-satellite-variant"></i><p><?= e(__('common.no_data')) ?></p></div></td></tr>
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
                    <a class="page-link" href="<?= url('admin/epics?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
