<?php
/** @var array $association @var array $stats @var array $historique @var array $evaluations */
use App\Helpers\I18n;

$title = __('common.dashboard');
$page  = 'association.dashboard';
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
                <?= e(($association['nom'] ?? 'Association')) ?> — <?= $isAr ? 'مراقبة نشاطات الجمعية' : 'Suivi de l\'activité de votre association' ?>
            </p>
            <div class="mt-1"><?= association_badge($association) ?></div>
        </div>
        <a class="btn btn-primary" href="<?= url('association/create') ?>">
            <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
        </a>
    </div>

    <!-- Alerte : action requise (modifications demandées) -->
    <?php if (! empty($attention)): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center gap-2 mb-4" role="alert">
            <i class="mdi mdi-alert-octagon-outline fs-4"></i>
            <div class="flex-grow-1">
                <strong><?= $isAr ? 'إجراء مطلوب' : 'Action requise' ?></strong>
                — <?= count($attention) ?> <?= $isAr ? 'نشاط بانتظار تعديلكم' : (count($attention) > 1 ? 'événements en attente de votre modification' : 'événement en attente de votre modification') ?>
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach (array_slice($attention, 0, 3) as $item): ?>
                        <li>
                            <a href="<?= url('association/' . (int) $item['id']) ?>"><?= e(mb_substr((string) ($item['adresse'] ?? ''), 0, 60)) ?></a>
                            <?php if (! empty($item['motif_refus'])): ?>
                                <span class="text-muted small">— <?= e(mb_substr((string) $item['motif_refus'], 0, 80)) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a class="btn btn-sm btn-outline-warning" href="<?= url('association?statut=MODIFICATION_DEMANDEE') ?>">
                <?= $isAr ? 'عرض الكل' : 'Voir tout' ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- KPIs : compteurs de statuts cliquables -->
    <?php $sc = $statutsCounts ?? []; ?>
    <?php $total = array_sum($sc); ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association') ?>">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-star"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= $total ?></div>
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
                <div class="wh-kpi-icon gray"><i class="mdi mdi-calendar-clock"></i></div>
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
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link wh-kpi-action wh-kpi-attention" href="<?= url('association?statut=MODIFICATION_DEMANDEE') ?>">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-alert-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['MODIFICATION_DEMANDEE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'تعديل مطلوب' : 'Modification demandée' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=REFUSE') ?>">
                <div class="wh-kpi-icon red"><i class="mdi mdi-close-circle-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['REFUSE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مرفوضة' : 'Refusés' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon green"><i class="mdi mdi-shield-check-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($stats['validated'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'المنجزة' : 'Validés' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon purple"><i class="mdi mdi-account-group"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($stats['participants'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'المشاركون' : 'Participants' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des actions -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h3 class="h6 mb-0"><?= $isAr ? 'النشاط الأخير' : 'Historique récent' ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($historique)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= $isAr ? 'الإجراء' : 'Action' ?></th>
                                <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                                <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique as $h): ?>
                                <tr>
                                    <td><?= e($h['action'] ?? '') ?></td>
                                    <td>
                                        <span class="badge <?= $badgeColor((string) ($h['nouveau_statut'] ?? 'EN_ATTENTE')) ?>">
                                            <?= e(statut_label((string) ($h['nouveau_statut'] ?? 'EN_ATTENTE'))) ?>
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        <?= e(date('d/m/Y H:i', strtotime((string) ($h['created_at'] ?? 'now')))) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted"><?= $isAr ? 'لا توجد أنشطة حديثة.' : 'Aucune activité récente.' ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Évaluations -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h3 class="h6 mb-0"><?= $isAr ? 'التقييمات الأخيرة' : 'Évaluations récentes' ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($evaluations)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= $isAr ? 'الحدث' : 'Événement' ?></th>
                                <th><?= $isAr ? 'المقيّم' : 'Évalué par' ?></th>
                                <th class="text-center"><?= $isAr ? 'التقييم' : 'Note' ?></th>
                                <th><?= $isAr ? 'التعليقات' : 'Commentaires' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $ev): ?>
                                <tr>
                                    <td><?= e(mb_substr((string) ($ev['adresse'] ?? ''), 0, 40)) ?></td>
                                    <td>
                                        <?= e(($ev['evaluateur_prenom'] ?? '') . ' ' . ($ev['evaluateur_nom'] ?? '')) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">
                                            <?= str_repeat('★', (int) ($ev['note'] ?? 0)) ?><?= str_repeat('☆', 5 - (int) ($ev['note'] ?? 0)) ?>
                                        </span>
                                    </td>
                                    <td><?= e(mb_substr((string) ($ev['description'] ?? ''), 0, 60)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted"><?= $isAr ? 'لا توجد تقييمات حتى الآن.' : 'Aucune évaluation pour le moment.' ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.wh-kpi.purple .wh-kpi-icon {
    background: rgba(168, 85, 247, 0.2);
    color: #a855f7;
}
</style>