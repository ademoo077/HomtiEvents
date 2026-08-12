<?php
/** @var array $evenements @var array $filters @var int $page @var int $lastPage @var int $total
 *  @var array $communes @var array $associations @var array $epics @var array $anomalies
 */
use App\Helpers\I18n;

$title = __('evenements.title');
$page  = 'wilaya.evenements.index';
$dir   = I18n::direction();

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'               => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};

$keep = static function (string $key, mixed $value = ''): string {
    if ($value === '') {
        $value = $_GET[$key] ?? '';
    }
    return $value !== '' && $value !== null ? '&' . $key . '=' . urlencode((string) $value) : '';
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('evenements.title')) ?></h1>
            <p class="wh-page-sub"><?= e($total) ?> <?= e(__('evenements.participants_count')) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= url('wilaya/evenements/export') ?>">
                <i class="mdi mdi-download me-1"></i><?= e(__('common.export')) ?>
            </a>
            <a class="btn btn-primary" href="<?= url('wilaya/evenements/create') ?>">
                <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
            </a>
        </div>
    </div>

    <form method="get" action="<?= url('wilaya/evenements') ?>" class="wh-filters mb-3">
        <div class="row g-2">
            <div class="col-12 col-md-3">
                <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>"
                       value="<?= e((string) ($filters['q'] ?? '')) ?>">
            </div>
            <div class="col-6 col-md-2">
                <select name="statut" class="form-select">
                    <option value=""><?= e(__('common.status')) ?> : <?= e(__('common.all')) ?></option>
                    <?php foreach (\App\Helpers\EvenementService::STATUTS as $s): ?>
                        <option value="<?= e($s) ?>" <?= (($filters['statut'] ?? '') === $s) ? 'selected' : '' ?>><?= e(statut_label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="commune_id" class="form-select">
                    <option value=""><?= e(__('common.commune')) ?></option>
                    <?php foreach ($communes as $c): ?>
                        <option value="<?= e((string) $c['id']) ?>" <?= (string) ($filters['commune_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="association_id" class="form-select">
                    <option value=""><?= e(__('common.association')) ?></option>
                    <?php foreach ($associations as $a): ?>
                        <option value="<?= e((string) $a['id']) ?>" <?= (string) ($filters['association_id'] ?? '') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="epic_id" class="form-select">
                    <option value=""><?= e(__('common.epic')) ?></option>
                    <?php foreach ($epics as $ep): ?>
                        <option value="<?= e((string) $ep['id']) ?>" <?= (string) ($filters['epic_id'] ?? '') === (string) $ep['id'] ? 'selected' : '' ?>><?= e($ep['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="anomalie_id" class="form-select">
                    <option value=""><?= e(__('common.anomalies')) ?></option>
                    <?php foreach ($anomalies as $an): ?>
                        <option value="<?= e((string) $an['id']) ?>" <?= (string) ($filters['anomalie_id'] ?? '') === (string) $an['id'] ? 'selected' : '' ?>><?= e($an['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="du" class="form-control" value="<?= e((string) ($filters['du'] ?? '')) ?>">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="au" class="form-control" value="<?= e((string) ($filters['au'] ?? '')) ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="mdi mdi-filter-variant me-1"></i><?= e(__('common.filters')) ?>
                </button>
                <a href="<?= url('wilaya/evenements') ?>" class="btn btn-outline-secondary">
                    <i class="mdi mdi-refresh"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="card wh-card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:36px"><input type="checkbox" class="form-check-input" data-check-all></th>
                    <th>ID</th>
                    <th><?= e(__('common.adresse')) ?></th>
                    <th><?= e(__('common.commune')) ?></th>
                    <th><?= e(__('common.association')) ?></th>
                    <th><?= e(__('common.status')) ?></th>
                    <th><?= e(__('common.date')) ?></th>
                    <th><?= e(__('common.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($evenements as $e): ?>
                    <tr>
                        <td><input type="checkbox" class="form-check-input" data-bulk-id value="<?= e((string) $e['id']) ?>"></td>
                        <td><span class="wh-badge-pill text-muted">#<?= (int) $e['id'] ?></span></td>
                        <td>
                            <a href="<?= url('wilaya/evenements/' . $e['id']) ?>" class="text-decoration-none fw-semibold"><?= e($e['adresse']) ?></a>
                            <?php if ((int) ($e['nb_anomalies'] ?? 0) > 0): ?>
                                <span class="wh-badge badge-red ms-1"><i class="mdi mdi-alert-octagon"></i> <?= (int) $e['nb_anomalies'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= e($e['commune_nom'] ?? '-') ?></td>
                        <td class="text-muted" title="<?= e(($e['association_email'] ?? '') . ' | ' . ($e['association_telephone'] ?? '')) ?>">
                        <?php if (!empty($e['association_id'])): ?>
                            <a href="<?= url('association/' . (int) $e['association_id']) ?>" class="text-decoration-none">
                                <?= e($e['association_nom'] ?? '-') ?>
                            </a>
                        <?php else: ?>
                            <?= e($e['association_nom'] ?? '-') ?>
                        <?php endif; ?>
                    </td>
                        <td><span class="wh-badge <?= $badgeColor($e['statut']) ?>"><?= e(statut_label($e['statut'])) ?></span></td>
                        <td class="text-muted"><?= $e['date_evenement'] ? e(date('d/m/Y', strtotime((string) $e['date_evenement']))) : '—' ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('wilaya/evenements/' . $e['id']) ?>" title="<?= e(__('common.view')) ?>">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('wilaya/evenements/' . $e['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                    <i class="mdi mdi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('wilaya/evenements/' . $e['id'] . '/archiver') ?>" data-confirm="<?= e(__('common.archive')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.archive')) ?>">
                                        <i class="mdi mdi-archive"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($evenements === []): ?>
                    <tr>
                        <td colspan="8">
                            <div class="wh-empty">
                                <i class="mdi mdi-calendar-remove"></i>
                                <p><?= e(__('common.no_data')) ?></p>
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
            <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('wilaya/evenements?page=' . $i . $keep('statut') . $keep('commune_id') . $keep('association_id') . $keep('epic_id') . $keep('anomalie_id') . $keep('du') . $keep('au') . $keep('q')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
