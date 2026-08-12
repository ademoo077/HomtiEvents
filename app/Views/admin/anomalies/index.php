<?php
/** @var array $anomalies @var string $q @var int $page @var int $lastPage @var int $total @var array $errors */
$title = __('common.anomalies');
$page  = 'admin.anomalies.index';
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('common.anomalies')) ?></h1>
            <p class="wh-page-sub"><?= e($total) ?> <?= e(__('common.anomalies')) ?></p>
        </div>
        <a class="btn btn-primary" href="<?= url('admin/anomalies/create') ?>">
            <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
        </a>
    </div>

    <form method="get" action="<?= url('admin/anomalies') ?>" class="wh-filters mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" value="<?= e($q) ?>">
            </div>
            <div class="col-md-3">
                <select name="epic_id" class="form-select">
                    <option value="0">— <?= e(__('common.epic')) ?> —</option>
                    <?php foreach (($epics ?? []) as $ep): ?>
                        <option value="<?= (int) $ep['id'] ?>" <?= ((int) ($epic_id ?? 0) === (int) $ep['id']) ? 'selected' : '' ?>>
                            <?= e($ep['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-variant me-1"></i><?= e(__('common.filters')) ?></button>
                <a href="<?= url('admin/anomalies') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-refresh"></i></a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="wh-page-sub mb-0"><?= e((int) $total) ?> <?= e(__('common.anomalies')) ?></p>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/routing') ?>">
            <i class="mdi mdi-router-network-outline me-1"></i>Gérer le routage
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th><?= e(__('common.nom')) ?></th>
                    <th><?= e(__('common.description')) ?></th>
                <th><?= e(__('common.epic')) ?></th>
                <th><?= e(__('common.evenements')) ?></th>
                <th>Routes</th>
                <th><?= e(__('common.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($anomalies as $a): ?>
                    <tr>
                        <td>
                            <span class="wh-dot d-inline-block me-2" style="background:<?= e($a['couleur'] ?: 'var(--wh-red)') ?>"></span>
                            <?php if ($a['icone']): ?><i class="mdi <?= e($a['icone']) ?> me-1"></i><?php endif; ?>
                            <span class="fw-semibold"><?= e($a['nom']) ?></span>
                        </td>
                        <td class="wh-text-muted"><?= e(mb_strimwidth((string) ($a['description'] ?? ''), 0, 60, '…')) ?></td>
                        <td>
                            <?php if (! empty($a['epics_competents'])): ?>
                                <?php foreach (explode(',', (string) $a['epics_competents']) as $nom): ?>
                                    <span class="wh-badge badge-blue me-1"><?= e(trim($nom)) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="wh-text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="wh-badge badge-red"><?= (int) $a['signalements'] ?></span></td>
                        <td>
                            <?php if (! empty($a['regles_routage'])): ?>
                                <a class="wh-badge badge-purple"
                                   href="<?= e(url('admin/routing?q=' . urlencode((string) $a['nom']))) ?>"
                                   title="Voir les règles"><?= (int) $a['regles_routage'] ?> règle(s)</a>
                            <?php else: ?>
                                <a class="wh-badge badge-gray"
                                   href="<?= e(url('admin/routing?q=' . urlencode((string) $a['nom']))) ?>"
                                   title="Aucune règle active">0</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/anomalies/' . $a['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>"><i class="mdi mdi-pencil"></i></a>
                                <form method="post" action="<?= url('admin/anomalies/' . $a['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.delete')) ?>"><i class="mdi mdi-delete"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($anomalies === []): ?>
                    <tr><td colspan="7"><div class="wh-empty"><i class="mdi mdi-alert-octagon"></i><p><?= e(__('common.no_data')) ?></p></div></td></tr>
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
                    <a class="page-link" href="<?= url('admin/anomalies?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '') . ((int) ($epic_id ?? 0) > 0 ? '&epic_id=' . (int) $epic_id : '')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
