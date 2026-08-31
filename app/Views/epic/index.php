<?php
/** @var array $epic @var array $interventions @var array $filters @var int $page @var int $lastPage @var int $total @var array $kpis */
use App\Helpers\I18n;

$title = __('common.epic');
$page  = 'epic.index';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
$total = (int) $total;

// Statut d'intervention (champ evenement_epic.statut)
$badgeInter = static function (string $statut): string {
    return match (strtolower($statut)) {
        'affecte'  => 'bg-secondary',
        'en_cours' => 'bg-warning',
        'termine'  => 'bg-success',
        'anomalie' => 'bg-danger',
        default    => 'bg-secondary',
    };
};
$badgeInterLabel = static function (string $statut) use ($isAr): string {
    return match (strtolower($statut)) {
        'affecte'  => $isAr ? 'معين' : 'Affecté',
        'en_cours' => $isAr ? 'قيد التنفيذ' : 'En cours',
        'termine'  => $isAr ? 'منتهي' : 'Terminé',
        'anomalie' => $isAr ? 'طارئ' : 'Anomalie',
        default    => e($statut),
    };
};
?>
<div class="wh-page">
    <div class="wh-hero wh-hero-epic" style="background: linear-gradient(135deg, #0891B2 0%, #0B5ED7 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title">
                        <i class="mdi mdi-clipboard-text-outline me-2"></i><?= e(($epic['nom'] ?? __('common.epic'))) ?>
                    </h1>
                    <p class="wh-hero-sub"><?= e(mb_substr((string) ($epic['description'] ?? ''), 0, 110)) ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-outline-light" href="<?= url('epic/agenda') ?>">
                        <i class="mdi mdi-calendar-month me-1"></i><?= $isAr ? 'الأجندة' : 'Agenda' ?>
                    </a>
                    <a class="btn btn-outline-light" href="<?= url('epic/dashboard') ?>">
                        <i class="mdi mdi-view-dashboard-outline me-1"></i><?= $isAr ? 'لوحة القيادة' : 'Tableau de bord' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs (EPIC-wide) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-sm-4 col-lg">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-clipboard-sync"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($kpis['total'] ?? $total) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'إجمالي التدخلات' : 'Interventions' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon gray"><i class="mdi mdi-clipboard-arrow-down"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($kpis['affecte'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'معين' : 'Affectés' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon purple"><i class="mdi mdi-clock-alert-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($kpis['en_attente'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'بانتظار القبول' : 'À accepter' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-progress-wrench"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($kpis['en_cours'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'قيد التنفيذ' : 'En cours' ?></div>
                </div>
            </div>
        </div>        <div class="col-6 col-sm-4 col-lg">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon green"><i class="mdi mdi-check-circle"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($kpis['termine'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'منتهي' : 'Terminés' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon red"><i class="mdi mdi-alert-octagon"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($kpis['anomalie'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'طارئ' : 'Anomalies' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon info"><i class="mdi mdi-clock-outline"></i></div>
                <div>
                    <div class="wh-kpi-value">
                        <?php if (($kpis['temps_moyen_epic'] ?? null) !== null): ?>
                            <?= (float) $kpis['temps_moyen_epic'] ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </div>
                    <div class="wh-kpi-label"><?= $isAr ? 'متوسط المدة (أيام)' : 'Temps moyen (j)' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= url('epic') ?>" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1"><?= $isAr ? 'بحث' : 'Rechercher' ?></label>
                    <input type="text" name="q" class="form-control" placeholder="<?= $isAr ? 'بحث بالعنوان أو الوصف...' : 'Rechercher par adresse ou description...' ?>" value="<?= e($filters['q'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1"><?= $isAr ? 'الحالة' : 'Statut' ?></label>
                    <select name="statut" class="form-select">
                        <option value=""><?= $isAr ? 'جميع الحالات' : 'Tous les statuts' ?></option>
                        <option value="AFFECTE" <?= (($filters['statut'] ?? '') === 'AFFECTE') ? 'selected' : '' ?>><?= $isAr ? 'معين' : 'Affecté' ?></option>
                        <option value="EN_COURS" <?= (($filters['statut'] ?? '') === 'EN_COURS') ? 'selected' : '' ?>><?= $isAr ? 'قيد التنفيذ' : 'En cours' ?></option>
                        <option value="TERMINE" <?= (($filters['statut'] ?? '') === 'TERMINE') ? 'selected' : '' ?>><?= $isAr ? 'منتهي' : 'Terminé' ?></option>
                        <option value="ANOMALIE" <?= (($filters['statut'] ?? '') === 'ANOMALIE') ? 'selected' : '' ?>><?= $isAr ? 'طارئ' : 'Anomalie' ?></option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="mdi mdi-filter-variant me-1"></i><?= $isAr ? 'تصفية' : 'Filtrer' ?>
                    </button>
                    <a href="<?= url('epic') ?>" class="btn btn-outline-secondary" title="<?= $isAr ? 'إعادة' : 'Réinitialiser' ?>"><i class="mdi mdi-refresh"></i></a>
                </div>
            </div>
        </div>
    </form>

    <!-- Interventions list -->
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="mdi mdi-clipboard-list-outline me-2"></i><?= $isAr ? 'قائمة التدخلات' : 'Liste des interventions' ?></span>
            <span class="wh-badge badge-soft"><?= (int) $total ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $isAr ? 'الحدث' : 'Événement' ?></th>
                        <th><?= $isAr ? 'العنوان' : 'Adresse' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                        <th><?= $isAr ? 'تاريخ الإسناد' : 'Affectation' ?></th>
                        <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($interventions as $iv): ?>
                        <tr>
                            <td class="text-muted"><?= (int) ($iv['intervention_id'] ?? 0) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e(($iv['association_nom'] ?? '-')) ?></div>
                                <small class="text-muted"><?= e(mb_substr((string) ($iv['evenement_adresse'] ?? ''), 0, 40)) ?></small>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width:220px"><?= e($iv['evenement_adresse'] ?? '-') ?></div>
                                <small class="text-muted"><?= e($iv['commune_nom'] ?? '') ?></small>
                            </td>
                            <td>
                                <span class="wh-statut-pill <?= strtolower((string) ($iv['intervention_statut'] ?? 'AFFECTE')) === 'en_cours' ? 'text-warning' : (strtolower((string) ($iv['intervention_statut'] ?? '')) === 'termine' ? 'text-success' : (strtolower((string) ($iv['intervention_statut'] ?? '')) === 'anomalie' ? 'text-danger' : 'text-secondary')) ?>">
                                    <?= $badgeInterLabel((string) ($iv['intervention_statut'] ?? 'AFFECTE')) ?>
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
                            <td colspan="6" class="text-center py-5">
                                <i class="mdi mdi-clipboard-remove-outline mdi-36px text-muted"></i>
                                <p class="text-muted mb-0 mt-2"><?= $isAr ? 'ليس لديك تدخلات بعد.' : 'Aucune intervention pour le moment.' ?></p>
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
            <ul class="pagination pagination-sm">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= max(1, $page - 1) ?>"><i class="mdi mdi-chevron-left"></i></a>
                </li>
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= min($lastPage, $page + 1) ?>"><i class="mdi mdi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem}</style>
</div>
