<?php
/** @var array $association @var array $evenements @var array $filters @var int $page @var int $lastPage @var int $total */
/** @var array $communes @var array $anomalies @var array $epics */
use App\Helpers\I18n;

$title = __('common.dashboard');
$page  = 'association.index';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'                => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('common.dashboard')) ?></h1>
            <p class="wh-page-sub">
                <?= e(($association['nom'] ?? 'Association')) ?> — <?= $isAr ? 'مراقبة طلبات الأحداث' : 'Suivi de vos demandes d\'événements' ?>
            </p>
            <div class="mt-1"><?= association_badge($association) ?></div>
        </div>
        <a class="btn btn-primary" href="<?= url('association/create') ?>">
            <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
        </a>
    </div>

    <!-- KPIs (compteurs globaux, cliquables) -->
    <?php $sc = $statutsCounts ?? []; ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association') ?>">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-star"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) $total ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'الإجمالي' : 'Total' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=EN_ATTENTE') ?>">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-clock-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['EN_ATTENTE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'قيد الانتظار' : 'En attente' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=PROGRAMME') ?>">
                <div class="wh-kpi-icon cyan"><i class="mdi mdi-calendar-check"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['PROGRAMME'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مبرمجة' : 'Programmés' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=TERMINE') ?>">
                <div class="wh-kpi-icon green"><i class="mdi mdi-check-circle"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['TERMINE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'منجزة' : 'Terminés' ?></div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= url('association') ?>" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" value="<?= e($filters['q'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="statut" class="form-select">
                <option value=""><?= $isAr ? 'جميع الحالات' : 'Tous les statuts' ?></option>
                <?php foreach (['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE', 'REFUSE'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($filters['statut'] ?? '') === $s ? 'selected' : '' ?>>
                        <?= e(statut_label($s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="mdi mdi-filter-variant"></i>
            </button>
        </div>
    </form>

    <!-- Events list -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $isAr ? 'العنوان' : 'Adresse' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                        <th class="text-center"><?= $isAr ? 'المشاركون' : 'Participants' ?></th>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                        <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evenements as $e): ?>
                        <?php $aRequire = ($e['statut'] ?? '') === 'MODIFICATION_DEMANDEE'; ?>
                        <tr class="<?= $aRequire ? 'table-warning' : '' ?>">
                            <td><?= (int) $e['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= e(mb_substr((string) ($e['adresse'] ?? ''), 0, 40)) ?></div>
                                <small class="text-muted"><?= e(mb_substr((string) ($e['description'] ?? ''), 0, 50)) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $badgeColor((string) ($e['statut'] ?? 'EN_ATTENTE')) ?>">
                                    <?= e(statut_label((string) ($e['statut'] ?? 'EN_ATTENTE'))) ?>
                                </span>
                                <?php if ($aRequire): ?>
                                    <span class="badge text-bg-warning d-inline-flex align-items-center gap-1 mt-1">
                                        <i class="mdi mdi-alert-circle-outline"></i><?= $isAr ? 'إجراء مطلوب' : 'Action requise' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark"><?= (int) ($e['participants'] ?? 0) ?></span>
                            </td>
                            <td class="text-nowrap">
                                <?= ($e['date_evenement'] ?? '') ? e(date('d/m/Y', strtotime((string) $e['date_evenement']))) : '—' ?>
                            </td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('association/' . (int) $e['id']) ?>" title="<?= $isAr ? 'عرض' : 'Voir' ?>">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                                <?php if (in_array(($e['statut'] ?? ''), ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE'], true)): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('association/' . (int) $e['id'] . '/edit') ?>" title="<?= $isAr ? 'تعديل' : 'Modifier' ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($evenements)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="mdi mdi-calendar-remove-outline mdi-24px"></i>
                                <p class="text-muted mb-0"><?= $isAr ? 'ليس لديك أحداث بعد.' : 'Aucun événement pour le moment.' ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($lastPage > 1): ?>
        <nav class="mt-3">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&statut=<?= e($filters['statut'] ?? '') ?>&q=<?= e($filters['q'] ?? '') ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
