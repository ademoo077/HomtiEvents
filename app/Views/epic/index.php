<?php
/** @var array $epic @var array $interventions @var array $filters @var int $page @var int $lastPage @var int $total */
use App\Helpers\I18n;

$title = __('common.epic');
$page  = 'epic.index';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$badgeColor = static function (string $statut): string {
    return match (strtolower($statut)) {
        'en_attente' => 'badge-gray',
        'valide', 'programme', 'qr_genere' => 'badge-info',
        'en_cours' => 'badge-warning',
        'termine' => 'badge-success',
        'refuse', 'modification_demandee' => 'badge-danger',
        default => 'badge-gray',
    };
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(($epic['nom'] ?? __('common.epic'))) ?></h1>
            <p class="wh-page-sub"><?= e(mb_substr((string) ($epic['description'] ?? ''), 0, 100)) ?></p>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-clipboard-sync"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $total ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'إجمالي التدخلات' : 'Interventions totales' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon warning"><i class="mdi mdi-progress-wrench"></i></div>
                <div>
                    <div class="wh-kpi-value">
                        <?php
                        echo count(array_filter($interventions, fn($v) => strtolower((string) ($v['statut'] ?? '')) === 'en_cours'));
                        ?>
                    </div>
                    <div class="wh-kpi-label"><?= $isAr ? 'قيد التنفيذ' : 'En cours' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon green"><i class="mdi mdi-check-circle"></i></div>
                <div>
                    <div class="wh-kpi-value">
                        <?php
                        echo count(array_filter($interventions, fn($v) => strtolower((string) ($v['statut'] ?? '')) === 'termine'));
                        ?>
                    </div>
                    <div class="wh-kpi-label"><?= $isAr ? 'منتهي' : 'Terminées' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon red"><i class="mdi mdi-alert-octagon"></i></div>
                <div>
                    <div class="wh-kpi-value">
                        <?php
                        echo count(array_filter($interventions, fn($v) => strtolower((string) ($v['statut'] ?? '')) === 'anomalie'));
                        ?>
                    </div>
                    <div class="wh-kpi-label"><?= $isAr ? 'بحالت طارئة' : 'Anomalies' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= url('epic') ?>" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="<?= $isAr ? 'بحث...' : 'Rechercher...' ?>" value="<?= e($filters['q'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="statut" class="form-select">
                <option value=""><?= $isAr ? 'جميع الحالات' : 'Tous les statuts' ?></option>
                <option value="AFFECTE" <?= (($filters['statut'] ?? '') === 'AFFECTE') ? 'selected' : '' ?>><?= $isAr ? 'معين' : 'Affecté' ?></option>
                <option value="EN_COURS" <?= (($filters['statut'] ?? '') === 'EN_COURS') ? 'selected' : '' ?>><?= $isAr ? 'قيد التنفيذ' : 'En cours' ?></option>
                <option value="TERMINE" <?= (($filters['statut'] ?? '') === 'TERMINE') ? 'selected' : '' ?>><?= $isAr ? 'منتهي' : 'Terminé' ?></option>
                <option value="ANOMALIE" <?= (($filters['statut'] ?? '') === 'ANOMALIE') ? 'selected' : '' ?>><?= $isAr ? 'طارئ' : 'Anomalie' ?></option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="mdi mdi-filter-variant"></i>
            </button>
        </div>
    </form>

    <!-- Interventions list -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $isAr ? 'الحدث' : 'Événement' ?></th>
                        <th><?= $isAr ? 'العنوان' : 'Adresse' ?></th>
                        <th><?= $isAr ? 'الوضعية' : 'Statut' ?></th>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                        <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($interventions as $iv): ?>
                        <tr>
                            <td>
                                <?php
                                echo (int) ($iv['id'] ?? 0);
                                ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e(($iv['association_nom'] ?? '-')) ?></div>
                                <small class="text-muted"><?= e(mb_substr((string) ($iv['evenement_adresse'] ?? ''), 0, 40)) ?></small>
                            </td>
                            <td><?= e($iv['evenement_adresse'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $badgeColor((string) ($iv['evenement_statut'] ?? 'EN_ATTENTE')) ?>">
                                    <?= e(statut_label((string) ($iv['evenement_statut'] ?? 'EN_ATTENTE'))) ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <?php if (!empty($iv['date_affectation'])): ?>
                                    <?= e(date('d/m/Y H:i', strtotime((string) $iv['date_affectation']))) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('epic/' . (int) $iv['evenement_id']) ?>" title="<?= $isAr ? 'عرض' : 'Voir' ?>">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($interventions)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="mdi mdi-clipboard-remove-outline mdi-24px"></i>
                                <p class="text-muted mb-0"><?= $isAr ? 'ليس لديك تدخلات بعد.' : 'Aucune intervention pour le moment.' ?></p>
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
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
