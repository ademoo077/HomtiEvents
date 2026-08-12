<?php
/** @var array $kpis @var array $parStatut @var array $parMois @var array $prochains
 *  @var array $recentActivity @var array $recentPhotos @var int $tauxComplet
 *  @var array $latestRequests @var int $agingPending @var array $notifFeed @var int $unreadNotifs */
use App\Helpers\I18n;

$title = __('common.dashboard');
$page  = 'wilaya.dashboard';
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

$actionIcon = static function (string $action): string {
    return match (true) {
        str_contains($action, 'create')    => 'mdi-plus-circle',
        str_contains($action, 'update')    => 'mdi-pencil',
        str_contains($action, 'delete')    => 'mdi-delete',
        str_contains($action, 'publish')   => 'mdi-eye',
        str_contains($action, 'upload')    => 'mdi-upload',
        str_contains($action, 'login')     => 'mdi-login',
        str_contains($action, 'statut')    => 'mdi-source-branch',
        default                            => 'mdi-circle-outline',
    };
};

$actionColor = static function (string $action): string {
    return match (true) {
        str_contains($action, 'create')    => 'text-success',
        str_contains($action, 'delete')    => 'text-danger',
        str_contains($action, 'publish')   => 'text-primary',
        str_contains($action, 'upload')    => 'text-info',
        str_contains($action, 'login')     => 'text-muted',
        default                            => 'text-secondary',
    };
};

$statutLabels = [];
foreach (\App\Helpers\EvenementService::STATUTS as $s) {
    $statutLabels[$s] = statut_label($s);
}
?>
<div class="wh-page">

    <!-- Hero Header -->
    <div class="wh-dashboard-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="wh-page-title mb-1">
                    <?= e(__('common.dashboard')) ?>
                </h1>
                <p class="wh-page-sub mb-0">
                    <?= e(__('common.wilaya')) ?> · <?= e(date('d/m/Y')) ?>
                </p>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <a class="btn btn-primary btn-lg" href="<?= url('wilaya/evenements/create') ?>">
                    <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
                </a>
                <a class="btn btn-outline-secondary btn-lg ms-2" href="<?= url('wilaya/gallery') ?>">
                    <i class="mdi mdi-image-multiple me-1"></i><?= e(__('common.gallery')) ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Idées & conseils : actions recommandées selon le contexte -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center gap-2">
            <i class="mdi mdi-lightbulb-on-outline text-warning"></i>
            <h3 class="h6 mb-0"><?= $isAr ? 'أفكار وتوصيات' : 'Idées & conseils' ?></h3>
            <?php if ($unreadNotifs > 0): ?>
                <a class="ms-auto btn btn-sm btn-outline-primary" href="<?= url('wilaya/notifications') ?>">
                    <i class="mdi mdi-bell-outline me-1"></i><?= (int) $unreadNotifs ?> <?= $isAr ? 'تنبيهات جديدة' : 'notification(s) nouvelle(s)' ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($suggestions ?? [] as $s): ?>
                    <div class="col-md-6">
                        <?php if (! empty($s['lien'])): ?>
                            <a href="<?= e($s['lien']) ?>" class="text-decoration-none d-block">
                        <?php endif; ?>
                        <div class="d-flex gap-2 align-items-start p-2 rounded-3 bg-light wh-kpi-hover h-100">
                            <i class="mdi <?= e($s['icon']) ?> text-<?= e($s['color']) ?>" style="font-size:1.35rem"></i>
                            <div>
                                <div class="fw-semibold small"><?= e($s['titre']) ?></div>
                                <span class="small text-muted"><?= e($s['texte']) ?></span>
                            </div>
                        </div>
                        <?php if (! empty($s['lien'])): ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- KPI Row 1: Événements -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-star"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $kpis['total'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.total')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-clock-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $kpis['en_attente'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('evenements.statut_en_attente')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon green"><i class="mdi mdi-check-circle-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $kpis['termines'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('evenements.statut_termine')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon gray"><i class="mdi mdi-account-group"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $kpis['associations'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.associations')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Row 2: Demandes d'inscription (liées vers le module) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <a class="text-decoration-none d-block" href="<?= url('admin/association-requests') ?>">
                <div class="wh-kpi wh-kpi-hover">
                    <div class="wh-kpi-icon blue"><i class="mdi mdi-account-plus-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['total_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'إجمالي الطلبات' : 'Demandes totales' ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="text-decoration-none d-block" href="<?= url('admin/association-requests?status=pending') ?>">
                <div class="wh-kpi wh-kpi-hover">
                    <div class="wh-kpi-icon amber"><i class="mdi mdi-clock-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['pending_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'طلبات قيد الانتظار' : 'Demandes en attente' ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="text-decoration-none d-block" href="<?= url('admin/association-requests?status=approved') ?>">
                <div class="wh-kpi wh-kpi-hover">
                    <div class="wh-kpi-icon green"><i class="mdi mdi-check-circle-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['approved_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'طلبات مقبولة' : 'Demandes approuvées' ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="text-decoration-none d-block" href="<?= url('admin/association-requests?status=rejected') ?>">
                <div class="wh-kpi wh-kpi-hover">
                    <div class="wh-kpi-icon red"><i class="mdi mdi-close-circle-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['rejected_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'طلبات مرفوضة' : 'Demandes refusées' ?></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- KPI Row 3: Réseau + Progression -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-satellite-variant"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $kpis['epics'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.epic')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon gray"><i class="mdi mdi-home-city-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $kpis['communes'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.commune')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="wh-kpi wh-kpi-highlight">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold"><?= $isAr ? 'نسبة الإنجاز' : 'Taux de complétion' ?></span>
                        <span class="fw-bold" style="color:var(--wh-blue)"><?= $tauxComplet ?>%</span>
                    </div>
                    <div class="progress" style="height:10px;border-radius:10px">
                        <div class="progress-bar" role="progressbar" style="width:<?= $tauxComplet ?>%;background:linear-gradient(90deg,var(--wh-blue),var(--wh-green))"></div>
                    </div>
                    <small class="text-muted mt-1 d-block">
                        <?= (int) $kpis['termines'] ?>/<?= (int) $kpis['total'] ?> <?= $isAr ? 'فعاليات منجزة' : 'événements terminés' ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart + Events List -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <span><i class="mdi mdi-chart-bar me-2"></i><?= e(__('common.statistiques')) ?></span>
                </div>
                <div class="card-body">
                    <div class="wh-chart">
                        <canvas id="chartStatuts"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-calendar-star me-2"></i><?= e(__('common.evenements')) ?></span>
                    <a href="<?= url('wilaya/evenements') ?>" class="btn btn-sm btn-outline-primary">
                        <?= e(__('common.all')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($prochains as $p): ?>
                            <li class="list-group-item wh-event-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="wh-event-dot <?= statut_key((string) $p['statut']) ?>"></div>
                                    <div class="flex-grow-1 min-w-0">
                                        <a href="<?= url('wilaya/evenements/' . (int) $p['id']) ?>" class="text-decoration-none fw-semibold text-truncate d-block">
                                            <?= e($p['adresse']) ?>
                                        </a>
                                        <small class="wh-text-muted">
                                            <i class="mdi mdi-map-marker-outline me-1"></i><?= e($p['commune_nom'] ?? '-') ?>
                                            <?php if ($p['date_evenement']): ?>
                                                · <?= e(date('d/m', strtotime((string) $p['date_evenement']))) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="wh-badge <?= $badgeColor((string) $p['statut']) ?>"><?= e(statut_label((string) $p['statut'])) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($prochains === []): ?>
                            <li class="list-group-item">
                                <div class="wh-empty p-3"><p class="mb-0"><?= e(__('common.no_data')) ?></p></div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Répartition des événements par organisation -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-folder-account-outline me-2"></i>
                        <?= $isAr ? 'الأحداث حسب المؤسسة' : 'Répartition des événements par organisation' ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (($repartitionOrg ?? []) === []): ?>
                        <div class="wh-empty text-center py-4 text-muted">
                            <i class="mdi mdi-folder-account-outline mdi-32px"></i>
                            <p class="mb-0 small"><?= $isAr ? 'لا توجد أحداث.' : 'Aucun événement pour le moment.' ?></p>
                        </div>
                    <?php else: ?>
                        <div class="wh-chart"><canvas id="chartRepartitionOrg" style="height:260px"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-information-skeleton-text-outline me-2"></i>
                        <?= $isAr ? 'التنبيهات غير المعالجة' : 'Alertes de routage non traitées' ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php $alertes = $routing_alertes_non_traitees ?? []; ?>
                    <?php if (empty($alertes)): ?>
                        <div class="wh-empty text-center py-4 text-muted">
                            <i class="mdi mdi-check-circle-outline mdi-32px text-success"></i>
                            <p class="mb-0 small"><?= $isAr ? 'لا توجد تنبيهات.' : 'Aucune alerte.' ?></p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($alertes as $a): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate"><?= e(mb_strimwidth((string) ($a['adresse'] ?? ''), 0, 60, '…')) ?></div>
                                        <div class="small text-muted"><?= e(mb_strimwidth((string) ($a['motif'] ?? ''), 0, 70, '…')) ?></div>
                                    </div>
                                    <span class="wh-badge badge-red"><?= e($a['commune_nom'] ?? '-') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Évolution mensuelle + Demandes -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-chart-line me-2"></i><?= $isAr ? 'التطور الشهري' : 'Évolution mensuelle' ?></span>
                    <span class="wh-text-muted" style="font-size:.78rem">6 <?= $isAr ? 'أشهر' : 'derniers mois' ?></span>
                </div>
                <div class="card-body">
                    <div class="wh-chart">
                        <canvas id="chartMois"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-account-check-outline me-2"></i><?= $isAr ? 'طلبات الجمعيات' : 'Demandes des associations' ?></span>
                    <a href="<?= url('admin/association-requests') ?>" class="btn btn-sm btn-outline-primary">
                        <?= e(__('common.all')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php $reqTotal = max(1, (int) $kpis['total_requests']); ?>
                    <div class="mb-3">
                        <div class="progress wh-funnel" style="height:10px;border-radius:10px">
                            <div class="progress-bar bg-warning" style="width:<?= round(((int) $kpis['pending_requests'] / $reqTotal) * 100, 1) ?>%" title="En attente"></div>
                            <div class="progress-bar bg-success" style="width:<?= round(((int) $kpis['approved_requests'] / $reqTotal) * 100, 1) ?>%" title="Approuvées"></div>
                            <div class="progress-bar bg-danger" style="width:<?= round(((int) $kpis['rejected_requests'] / $reqTotal) * 100, 1) ?>%" title="Refusées"></div>
                        </div>
                        <?php if ($agingPending > 0): ?>
                            <small class="d-block mt-2 text-danger" style="font-size:.75rem">
                                <i class="mdi mdi-alert-circle-outline me-1"></i>
                                <?= $isAr ? "{$agingPending} طلبات معلّقة منذ أكثر من 7 أيام" : "{$agingPending} demande(s) en attente depuis +7 jours" ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <a href="<?= url('admin/association-requests?status=pending') ?>" class="text-decoration-none d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 wh-kpi-hover">
                            <span class="d-flex align-items-center gap-2 text-muted"><i class="mdi mdi-clock-outline text-warning"></i> <?= $isAr ? 'قيد الانتظار' : 'En attente' ?></span>
                            <span class="fw-bold"><?= (int) $kpis['pending_requests'] ?></span>
                        </a>
                        <a href="<?= url('admin/association-requests?status=approved') ?>" class="text-decoration-none d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 wh-kpi-hover">
                            <span class="d-flex align-items-center gap-2 text-muted"><i class="mdi mdi-check-circle-outline text-success"></i> <?= $isAr ? 'مقبولة' : 'Approuvées' ?></span>
                            <span class="fw-bold"><?= (int) $kpis['approved_requests'] ?></span>
                        </a>
                        <a href="<?= url('admin/association-requests?status=rejected') ?>" class="text-decoration-none d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 wh-kpi-hover">
                            <span class="d-flex align-items-center gap-2 text-muted"><i class="mdi mdi-close-circle-outline text-danger"></i> <?= $isAr ? 'مرفوضة' : 'Refusées' ?></span>
                            <span class="fw-bold"><?= (int) $kpis['rejected_requests'] ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demandes récentes + Notifications -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-account-plus-outline me-2"></i><?= $isAr ? 'أحدث طلبات التسجيل' : 'Dernières demandes d\'inscription' ?></span>
                    <a href="<?= url('admin/association-requests') ?>" class="btn btn-sm btn-outline-primary">
                        <?= e(__('common.all')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($latestRequests as $rq): ?>
                            <?php
                                $rqStatus = (string) ($rq['status'] ?? 'pending');
                                $rqBadge = match ($rqStatus) {
                                    'approved' => 'badge-green',
                                    'rejected' => 'badge-red',
                                    default    => 'badge-amber',
                                };
                                $rqLabel = match ($rqStatus) {
                                    'approved' => $isAr ? 'مقبول' : 'Approuvée',
                                    'rejected' => $isAr ? 'مرفوضة' : 'Refusée',
                                    default    => $isAr ? 'قيد الانتظار' : 'En attente',
                                };
                                $rqOld = ($rqStatus === 'pending') && (int) ($rq['age_jours'] ?? 0) >= 7;
                            ?>
                            <li class="list-group-item wh-event-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="flex-grow-1 min-w-0">
                                        <a href="<?= url('admin/association-requests/' . (int) $rq['id']) ?>" class="text-decoration-none fw-semibold text-truncate d-block">
                                            <?= e($rq['association_name']) ?>
                                        </a>
                                        <small class="wh-text-muted d-flex align-items-center gap-2 flex-wrap">
                                            <span><i class="mdi mdi-calendar-outline me-1"></i><?= e(date('d/m/Y', strtotime((string) $rq['created_at']))) ?></span>
                                            <?php if (! empty($rq['approval_file'])): ?>
                                                <span title="Document joint"><i class="mdi mdi-file-document-outline text-primary"></i></span>
                                            <?php endif; ?>
                                            <?php if ($rqOld): ?>
                                                <span class="wh-badge badge-red">
                                                    <i class="mdi mdi-alert-outline me-1"></i><?= $isAr ? '+7 أيام' : '+7 jours' ?>
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="wh-badge <?= $rqBadge ?>"><?= $rqLabel ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($latestRequests === []): ?>
                            <li class="list-group-item">
                                <div class="wh-empty p-3"><p class="mb-0"><?= e(__('common.no_data')) ?></p></div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-bell-outline me-2"></i><?= $isAr ? 'التنبيهات' : 'Notifications' ?></span>
                    <?php if ((int) $unreadNotifs > 0): ?>
                        <span class="wh-badge badge-red"><?= (int) $unreadNotifs ?> <?= $isAr ? 'جديد' : 'nouveau(x)' ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($notifFeed as $nf): ?>
                            <li class="list-group-item d-flex align-items-start gap-2 py-3">
                                <i class="mdi <?= (int) ($nf['lu'] ?? 0) ? 'mdi-bell-outline' : 'mdi-bell-ring' ?> text-primary" style="font-size:1.15rem"></i>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small"><?= e($nf['titre']) ?></div>
                                    <small class="text-muted d-block text-truncate"><?= e($nf['message_notif']) ?></small>
                                </div>
                                <small class="text-muted text-nowrap" style="font-size:.72rem">
                                    <?= e(date('d/m H:i', strtotime((string) $nf['date_creation']))) ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($notifFeed === []): ?>
                            <li class="list-group-item">
                                <div class="wh-empty p-3"><p class="mb-0"><?= $isAr ? 'لا توجد تنبيهات' : 'Aucune notification' ?></p></div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity + Photos -->
    <div class="row g-4">
        <!-- Activité récente -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-history me-2"></i><?= $isAr ? 'النشاط الأخير' : 'Activité récente' ?></span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentActivity as $act): ?>
                            <li class="list-group-item d-flex align-items-center gap-3 py-3">
                                <div class="<?= $actionColor($act['action']) ?>">
                                    <i class="mdi <?= $actionIcon($act['action']) ?>" style="font-size:1.3rem"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small"><?= e($act['action']) ?></div>
                                    <small class="text-muted">
                                        <?= e(trim(($act['nom'] ?? '') . ' ' . ($act['prenom'] ?? ''))) ?>
                                        · <?= e($act['modele'] ?? '') ?>
                                    </small>
                                </div>
                                <small class="text-muted text-nowrap">
                                    <?= e(date('d/m H:i', strtotime((string) $act['created_at']))) ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($recentActivity === []): ?>
                            <li class="list-group-item">
                                <div class="wh-empty p-3"><p class="mb-0"><?= e(__('common.no_data')) ?></p></div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Photos récentes -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <span><i class="mdi mdi-camera me-2"></i><?= $isAr ? 'آخر الصور' : 'Photos récentes' ?></span>
                    <a href="<?= url('wilaya/gallery') ?>" class="btn btn-sm btn-outline-primary">
                        <?= e(__('common.gallery')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (! empty($recentPhotos)): ?>
                        <div class="row g-2">
                            <?php foreach ($recentPhotos as $rp): ?>
                                <div class="col-4 col-md-2">
                                    <div class="ratio ratio-1x1 rounded overflow-hidden wh-photo-thumb" style="background:#f1f5f9">
                                        <img src="<?= e($rp['image']) ?>" alt="" loading="lazy" style="object-fit:cover">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="wh-empty p-3"><p class="mb-0"><?= e(__('gallery.no_photos')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var canvas = document.getElementById('chartStatuts');
    if (!canvas || typeof Chart === 'undefined') return;
    var labels = <?= json_encode(array_values($statutLabels), JSON_UNESCAPED_UNICODE) ?>;
    var counts = new Array(labels.length).fill(0);
    <?php foreach ($parStatut as $ps): ?>
        var idx = labels.indexOf(<?= json_encode(statut_label((string) $ps['statut'])) ?>);
        if (idx >= 0) counts[idx] = <?= (int) $ps['nb'] ?>;
    <?php endforeach; ?>

    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.04)';
    var ticks = dark ? '#9aa3b2' : '#697586';

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: <?= json_encode(__('common.total')) ?>,
                data: counts,
                backgroundColor: ['#FBBF24', '#F59E0B', '#0B5ED7', '#22d3ee', '#8B5CF6', '#0B5ED7', '#198754', '#dc3545'],
                borderRadius: 8,
                maxBarThickness: 44,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: dark ? '#1a2332' : '#fff',
                    titleColor: dark ? '#e6ebf2' : '#212b36',
                    bodyColor: dark ? '#b8c7dc' : '#697586',
                    borderColor: dark ? '#2b3648' : '#dee2e6',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    displayColors: false,
                    titleFont: { weight: '600' }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: ticks, font: { weight: '500' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: grid },
                    ticks: { color: ticks, precision: 0 }
                }
            }
        }
    });
})();

(function () {
    var canvas = document.getElementById('chartMois');
    if (!canvas || typeof Chart === 'undefined') return;

    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.04)';
    var ticks = dark ? '#9aa3b2' : '#697586';

    var mois = <?= json_encode(array_column($parMois, 'mois'), JSON_UNESCAPED_UNICODE) ?>;
    var nbs  = <?= json_encode(array_map('intval', array_column($parMois, 'nb'))) ?>;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: mois,
            datasets: [{
                label: <?= json_encode(__('common.total')) ?>,
                data: nbs,
                borderColor: '#0B5ED7',
                backgroundColor: 'rgba(11,94,215,.12)',
                fill: true,
                tension: .35,
                pointRadius: 4,
                pointBackgroundColor: '#0B5ED7',
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: dark ? '#1a2332' : '#fff',
                    titleColor: dark ? '#e6ebf2' : '#212b36',
                    bodyColor: dark ? '#b8c7dc' : '#697586',
                    borderColor: dark ? '#2b3648' : '#dee2e6',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    displayColors: false,
                    titleFont: { weight: '600' }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: ticks, font: { weight: '500' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: grid },
                    ticks: { color: ticks, precision: 0 }
                }
            }
        }
    });
})();

(function () {
    var canvas = document.getElementById('chartRepartitionOrg');
    if (!canvas || typeof Chart === 'undefined') return;
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var ticks = dark ? '#9aa3c2' : '#697586';
    var data = <?= json_encode($repartitionOrg ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var labels = data.map(function (d) { return d.org; });
    var nbs    = data.map(function (d) { return d.nb; });
    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: nbs,
                backgroundColor: ['#0B5ED7', '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#64748b', '#8b5cf6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'right', labels: { color: ticks, boxWidth: 14, padding: 12 } },
                tooltip: {
                    backgroundColor: dark ? '#1a2332' : '#fff',
                    titleColor: dark ? '#e6ebf2' : '#212b36',
                    bodyColor: dark ? '#b8c7dc' : '#697586',
                    borderColor: dark ? '#2b3648' : '#dee2e6',
                    borderWidth: 1, cornerRadius: 8, padding: 12
                }
            }
        }
    });
})();
</script>
