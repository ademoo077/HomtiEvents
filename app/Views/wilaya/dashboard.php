<?php
/** @var array $kpis @var array $parStatut @var array $parMois @var array $prochains
 *  @var array $recentActivity @var array $recentPhotos @var int $tauxComplet
 *  @var array $latestRequests @var int $agingPending @var array $notifFeed @var int $unreadNotifs */
use App\Helpers\I18n;

$title = __('common.dashboard');
$page  = 'wilaya.dashboard';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
$trendEvents = $trendEvents ?? null;

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

$dotColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'background:var(--wh-amber);--dot-bg:#fff3cd',
        'modification_demandee' => 'background:var(--wh-amber);--dot-bg:#fff3cd',
        'valide'                => 'background:var(--wh-blue);--dot-bg:var(--wh-blue-soft)',
        'programme'             => 'background:#22d3ee;--dot-bg:#cff4fc',
        'qr_genere'             => 'background:#8B5CF6;--dot-bg:#ede9fe',
        'en_cours'              => 'background:var(--wh-blue);--dot-bg:var(--wh-blue-soft)',
        'termine'               => 'background:var(--wh-green);--dot-bg:var(--wh-green-soft)',
        'refuse'                => 'background:var(--wh-red);--dot-bg:#f8d7da',
        default                 => 'background:var(--wh-gray);--dot-bg:var(--wh-gray-soft)',
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

$actionColor = static function (string $action): array {
    return match (true) {
        str_contains($action, 'create')    => ['bg' => 'var(--wh-green-soft)', 'fg' => 'var(--wh-green)'],
        str_contains($action, 'delete')    => ['bg' => '#f8d7da', 'fg' => 'var(--wh-red)'],
        str_contains($action, 'publish')   => ['bg' => 'var(--wh-blue-soft)', 'fg' => 'var(--wh-blue)'],
        str_contains($action, 'upload')    => ['bg' => '#cff4fc', 'fg' => 'var(--wh-cyan)'],
        str_contains($action, 'login')     => ['bg' => 'var(--wh-gray-soft)', 'fg' => 'var(--wh-gray)'],
        default                            => ['bg' => 'var(--wh-gray-soft)', 'fg' => 'var(--wh-gray)'],
    };
};

$statutLabels = [];
foreach (\App\Helpers\EvenementService::STATUTS as $s) {
    $statutLabels[$s] = statut_label($s);
}

$greeting = match (true) {
    (int) date('H') < 12 => $isAr ? 'صباح الخير' : 'Bonjour',
    (int) date('H') < 18 => $isAr ? 'مساء الخير' : 'Bon après-midi',
    default               => $isAr ? 'مساء الخير' : 'Bonsoir',
};
$userName = trim(current_user()['prenom'] ?? '');

$suggestColorMap = [
    'primary' => ['bg' => 'var(--wh-blue-soft)', 'fg' => 'var(--wh-blue)'],
    'blue'    => ['bg' => 'var(--wh-blue-soft)', 'fg' => 'var(--wh-blue)'],
    'green'   => ['bg' => 'var(--wh-green-soft)', 'fg' => 'var(--wh-green)'],
    'amber'   => ['bg' => '#fff3cd', 'fg' => '#f59e0b'],
    'red'     => ['bg' => '#f8d7da', 'fg' => 'var(--wh-red)'],
    'violet'  => ['bg' => '#ede9fe', 'fg' => '#8b5cf6'],
    'cyan'    => ['bg' => '#cff4fc', 'fg' => '#22d3ee'],
    'info'    => ['bg' => 'var(--wh-blue-soft)', 'fg' => 'var(--wh-blue)'],
    'purple'  => ['bg' => '#ede9fe', 'fg' => '#8b5cf6'],
    'gray'    => ['bg' => 'var(--wh-gray-soft)', 'fg' => 'var(--wh-gray)'],
];
?>
<div class="wh-page">

    <!-- ═══ HERO ═══ -->
    <style>
        .wh-dash-hero { --hero-a: #084298; --hero-b: #0f8a70; }
        .wh-dash-hero .hero-title { font-size: 1.75rem; margin-bottom: .35rem; letter-spacing: -.3px; }
        .wh-hero-panel .hero-stat-value { font-size: 1.7rem; font-weight: 800; font-family: var(--wh-font-heading); }
        .wh-hero-panel .hero-stat-label { font-size: .78rem; opacity: .88; }
        @media (max-width: 767.98px) { .wh-dash-hero .hero-title { font-size: 1.25rem; } }
    </style>
    <div class="wh-hero-panel wh-dash-hero mb-4" style="color:#fff">
        <div class="row align-items-center" style="position:relative;z-index:1">
            <div class="col-lg-7 col-md-8">
                <div class="hero-greeting"><?= e($greeting) ?><?= $userName !== '' ? ', <strong>' . e($userName) . '</strong>' : '' ?></div>
                <h1 class="hero-title">
                    <i class="mdi mdi-view-dashboard me-2"></i><?= e(__('common.dashboard')) ?>
                </h1>
                <p class="hero-subtitle">
                    <i class="mdi mdi-map-marker me-1"></i><?= e(__('common.wilaya')) ?> · <?= e(date('d/m/Y')) ?>
                </p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-value"><?= (int) $kpis['total'] ?></span>
                        <span class="hero-stat-label"><?= $isAr ? 'حدث' : 'événements' ?></span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value"><?= (int) $kpis['associations'] ?></span>
                        <span class="hero-stat-label"><?= $isAr ? 'جمعية' : 'associations' ?></span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value"><?= $tauxComplet ?>%</span>
                        <span class="hero-stat-label"><?= $isAr ? 'إنجاز' : 'complétion' ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 col-md-4 text-md-end mt-3 mt-md-0">
                <div class="hero-actions">
                    <a class="btn btn-warning btn-lg" href="<?= url('wilaya/evenements/create') ?>">
                        <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
                    </a>
                    <a class="btn btn-light btn-lg" href="<?= url('wilaya/gallery') ?>">
                        <i class="mdi mdi-image-multiple me-1"></i><?= e(__('common.gallery')) ?>
                    </a>
                    <button type="button" class="btn btn-light btn-lg" id="btnPdf" title="Rapport PDF">
                        <i class="mdi mdi-file-pdf-box me-1"></i>PDF
                    </button>
                </div>
                <form id="pdfForm" method="post" action="<?= url('wilaya/dashboard/export-pdf') ?>" style="display:none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="chart_statuts" id="pdfChartStatuts">
                    <input type="hidden" name="chart_mois" id="pdfChartMois">
                    <input type="hidden" name="chart_org" id="pdfChartOrg">
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ SUGGESTIONS ═══ -->
    <?php if (! empty($suggestions ?? [])): ?>
    <div class="wh-dash-section">
        <div class="wh-dash-section-header">
            <i class="mdi mdi-lightbulb-on-outline"></i>
            <h2><?= $isAr ? 'أفكار وتوصيات' : 'Idées & conseils' ?></h2>
            <?php if ($unreadNotifs > 0): ?>
                <a class="badge bg-primary text-decoration-none" href="<?= url('wilaya/notifications') ?>">
                    <i class="mdi mdi-bell-outline me-1"></i><?= (int) $unreadNotifs ?> <?= $isAr ? 'جديد' : 'nouveau(x)' ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="row g-2">
            <?php foreach ($suggestions as $i => $s): ?>
                <div class="col-md-6 col-lg-3">
                    <?php if (! empty($s['lien'])): ?>
                        <a href="<?= e($s['lien']) ?>" class="wh-dash-suggest wh-dash-animate">
                    <?php else: ?>
                        <div class="wh-dash-suggest wh-dash-animate">
                    <?php endif; ?>
                        <?php $sc = $suggestColorMap[$s['color'] ?? 'primary'] ?? $suggestColorMap['primary']; ?>
                        <div class="wh-dash-suggest-icon" style="background:<?= e($sc['bg']) ?>;color:<?= e($sc['fg']) ?>">
                            <i class="mdi <?= e($s['icon']) ?>"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="fw-semibold small text-truncate"><?= e($s['titre']) ?></div>
                            <span class="text-muted" style="font-size:.76rem;line-height:1.3;display:block"><?= e($s['texte']) ?></span>
                        </div>
                    <?php if (! empty($s['lien'])): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ KPIs — Événements ═══ -->
    <div class="wh-dash-section">
        <div class="wh-dash-section-header">
            <i class="mdi mdi-calendar-star"></i>
            <h2><?= $isAr ? 'الأحداث' : 'Événements' ?></h2>
        </div>
        <div class="row g-3">
            <div class="col-6 col-lg-3 wh-dash-animate">
                <div class="wh-kpi-card" style="--kpi-accent:var(--wh-blue);--kpi-bg:var(--wh-blue-soft)">
                    <div class="wh-kpi-icon"><i class="mdi mdi-calendar-star"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['total'] ?></div>
                        <div class="wh-kpi-label"><?= e(__('common.total')) ?></div>
                    </div>
                    <?php if ($trendEvents !== null && $trendEvents !== 0.0): ?>
                        <?php $trendDir = $trendEvents > 0 ? 'up' : 'down'; $trendArrow = $trendEvents > 0 ? 'mdi-trending-up' : 'mdi-trending-down'; ?>
                        <span class="wh-kpi-trend <?= $trendDir ?>" title="<?= $isAr ? 'مقارنة بالشهر الماضي' : 'vs mois dernier' ?>">
                            <i class="mdi <?= $trendArrow ?>"></i><?= number_format(abs($trendEvents), 0) ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6 col-lg-3 wh-dash-animate">
                <a href="<?= url('wilaya/evenements?statut=en_attente') ?>" class="wh-kpi-card" style="--kpi-accent:var(--wh-amber);--kpi-bg:#fff3cd">
                    <div class="wh-kpi-icon"><i class="mdi mdi-clock-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['en_attente'] ?></div>
                        <div class="wh-kpi-label"><?= e(__('evenements.statut_en_attente')) ?></div>
                    </div>
                    <i class="mdi mdi-arrow-right wh-kpi-arrow"></i>
                </a>
            </div>
            <div class="col-6 col-lg-3 wh-dash-animate">
                <div class="wh-kpi-card" style="--kpi-accent:var(--wh-green);--kpi-bg:var(--wh-green-soft)">
                    <div class="wh-kpi-icon"><i class="mdi mdi-check-circle-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['termines'] ?></div>
                        <div class="wh-kpi-label"><?= e(__('evenements.statut_termine')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3 wh-dash-animate">
                <div class="wh-kpi-card" style="--kpi-accent:var(--wh-gray);--kpi-bg:var(--wh-gray-soft)">
                    <div class="wh-kpi-icon"><i class="mdi mdi-account-group"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['associations'] ?></div>
                        <div class="wh-kpi-label"><?= e(__('common.associations')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ KPIs — Réseau & Progression ═══ -->
    <div class="wh-dash-section">
        <div class="wh-dash-section-header">
            <i class="mdi mdi-network"></i>
            <h2><?= $isAr ? 'الشبكة' : 'Réseau' ?></h2>
        </div>
        <div class="row g-3">
            <div class="col-6 col-lg-2 wh-dash-animate">
                <div class="wh-kpi-card" style="--kpi-accent:var(--wh-blue);--kpi-bg:var(--wh-blue-soft)">
                    <div class="wh-kpi-icon"><i class="mdi mdi-satellite-variant"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['epics'] ?></div>
                        <div class="wh-kpi-label"><?= e(__('common.epic')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2 wh-dash-animate">
                <div class="wh-kpi-card" style="--kpi-accent:var(--wh-gray);--kpi-bg:var(--wh-gray-soft)">
                    <div class="wh-kpi-icon"><i class="mdi mdi-home-city-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['communes'] ?></div>
                        <div class="wh-kpi-label"><?= e(__('common.commune')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 wh-dash-animate">
                <div class="wh-kpi wh-kpi-highlight" style="border-radius:var(--wh-radius)">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold" style="font-size:.88rem"><?= $isAr ? 'نسبة الإنجاز' : 'Taux de complétion' ?></span>
                            <span class="fw-bold" style="color:var(--wh-blue);font-size:1.1rem;font-family:var(--wh-font-heading)"><?= $tauxComplet ?>%</span>
                        </div>
                        <div class="progress" style="height:10px;border-radius:10px">
                            <div class="progress-bar" role="progressbar" style="width:<?= $tauxComplet ?>%;background:linear-gradient(90deg,var(--wh-blue),var(--wh-green))"></div>
                        </div>
                        <small class="text-muted mt-1 d-block" style="font-size:.78rem">
                            <?= (int) $kpis['termines'] ?>/<?= (int) $kpis['total'] ?> <?= $isAr ? 'فعاليات منجزة' : 'événements terminés' ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Charts row: Statuts + Événements ═══ -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box blue"><i class="mdi mdi-chart-bar"></i></div>
                        <span class="fw-bold"><?= e(__('common.statistiques')) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartStatuts"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box green"><i class="mdi mdi-calendar-star"></i></div>
                        <span class="fw-bold"><?= e(__('common.evenements')) ?></span>
                    </div>
                    <a href="<?= url('wilaya/evenements') ?>" class="btn btn-sm btn-outline-primary">
                        <?= e(__('common.all')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($prochains as $p): ?>
                        <div class="wh-dash-event">
                            <div class="wh-dash-event-dot" style="<?= e($dotColor((string) $p['statut'])) ?>"></div>
                            <div class="wh-dash-event-body">
                                <a href="<?= url('wilaya/evenements/' . (int) $p['id']) ?>" class="wh-dash-event-title"><?= e($p['adresse']) ?></a>
                                <div class="wh-dash-event-meta">
                                    <i class="mdi mdi-map-marker-outline"></i>
                                    <span><?= e($p['commune_nom'] ?? '-') ?></span>
                                    <?php if ($p['date_evenement']): ?>
                                        <span>·</span>
                                        <span><?= e(date('d/m', strtotime((string) $p['date_evenement']))) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="wh-badge <?= $badgeColor((string) $p['statut']) ?>"><?= e(statut_label((string) $p['statut'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($prochains === []): ?>
                        <div class="wh-empty p-3"><p class="mb-0"><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Charts row: Répartition + Alertes ═══ -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box green"><i class="mdi mdi-folder-account-outline"></i></div>
                        <span class="fw-bold"><?= $isAr ? 'الأحداث حسب المؤسسة' : 'Répartition par organisation' ?></span>
                    </div>
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
        <div class="col-lg-6 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box amber"><i class="mdi mdi-alert-octagon-outline"></i></div>
                        <span class="fw-bold"><?= $isAr ? 'التنبيهات غير المعالجة' : 'Alertes de routage' ?></span>
                    </div>
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

    <!-- ═══ KPIs — Demandes ═══ -->
    <div class="wh-dash-section">
        <div class="wh-dash-section-header">
            <i class="mdi mdi-account-plus-outline"></i>
            <h2><?= $isAr ? 'طلبات الانضمام' : 'Demandes d\'inscription' ?></h2>
            <?php if ($agingPending > 0): ?>
                <span class="badge bg-danger"><i class="mdi mdi-alert-outline me-1"></i><?= $agingPending ?> <?= $isAr ? 'معلّقة' : 'en attente' ?></span>
            <?php endif; ?>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3 wh-dash-animate">
                <a href="<?= url('admin/association-requests') ?>" class="wh-kpi-card" style="--kpi-accent:var(--wh-blue);--kpi-bg:var(--wh-blue-soft)">
                    <div class="wh-kpi-icon"><i class="mdi mdi-account-plus-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['total_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'إجمالي الطلبات' : 'Total' ?></div>
                    </div>
                    <i class="mdi mdi-arrow-right wh-kpi-arrow"></i>
                </a>
            </div>
            <div class="col-6 col-lg-3 wh-dash-animate">
                <a href="<?= url('admin/association-requests?status=pending') ?>" class="wh-kpi-card" style="--kpi-accent:var(--wh-amber);--kpi-bg:#fff3cd">
                    <div class="wh-kpi-icon"><i class="mdi mdi-clock-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['pending_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'قيد الانتظار' : 'En attente' ?></div>
                    </div>
                    <i class="mdi mdi-arrow-right wh-kpi-arrow"></i>
                </a>
            </div>
            <div class="col-6 col-lg-3 wh-dash-animate">
                <a href="<?= url('admin/association-requests?status=approved') ?>" class="wh-kpi-card" style="--kpi-accent:var(--wh-green);--kpi-bg:var(--wh-green-soft)">
                    <div class="wh-kpi-icon"><i class="mdi mdi-check-circle-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['approved_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'مقبولة' : 'Approuvées' ?></div>
                    </div>
                    <i class="mdi mdi-arrow-right wh-kpi-arrow"></i>
                </a>
            </div>
            <div class="col-6 col-lg-3 wh-dash-animate">
                <a href="<?= url('admin/association-requests?status=rejected') ?>" class="wh-kpi-card" style="--kpi-accent:var(--wh-red);--kpi-bg:#f8d7da">
                    <div class="wh-kpi-icon"><i class="mdi mdi-close-circle-outline"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpis['rejected_requests'] ?></div>
                        <div class="wh-kpi-label"><?= $isAr ? 'مرفوضة' : 'Refusées' ?></div>
                    </div>
                    <i class="mdi mdi-arrow-right wh-kpi-arrow"></i>
                </a>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-6 wh-dash-animate">
                <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box green"><i class="mdi mdi-account-check-outline"></i></div>
                            <span class="fw-bold"><?= $isAr ? 'طلبات الجمعيات' : 'Demandes des associations' ?></span>
                        </div>
                        <a href="<?= url('admin/association-requests') ?>" class="btn btn-sm btn-outline-primary">
                            <?= e(__('common.all')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php $reqTotal = max(1, (int) $kpis['total_requests']); ?>
                        <div class="mb-3">
                            <div class="progress wh-funnel" style="height:10px;border-radius:10px">
                                <div class="progress-bar bg-warning" style="width:<?= round(((int) $kpis['pending_requests'] / $reqTotal) * 100, 1) ?>%"></div>
                                <div class="progress-bar bg-success" style="width:<?= round(((int) $kpis['approved_requests'] / $reqTotal) * 100, 1) ?>%"></div>
                                <div class="progress-bar bg-danger" style="width:<?= round(((int) $kpis['rejected_requests'] / $reqTotal) * 100, 1) ?>%"></div>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <a href="<?= url('admin/association-requests?status=pending') ?>" class="wh-dash-req">
                                <div class="wh-dash-req-icon" style="background:#fff3cd;color:var(--wh-amber)"><i class="mdi mdi-clock-outline"></i></div>
                                <span class="wh-dash-req-label"><?= $isAr ? 'قيد الانتظار' : 'En attente' ?></span>
                                <span class="wh-dash-req-count" style="color:var(--wh-amber)"><?= (int) $kpis['pending_requests'] ?></span>
                            </a>
                            <a href="<?= url('admin/association-requests?status=approved') ?>" class="wh-dash-req">
                                <div class="wh-dash-req-icon" style="background:var(--wh-green-soft);color:var(--wh-green)"><i class="mdi mdi-check-circle-outline"></i></div>
                                <span class="wh-dash-req-label"><?= $isAr ? 'مقبولة' : 'Approuvées' ?></span>
                                <span class="wh-dash-req-count" style="color:var(--wh-green)"><?= (int) $kpis['approved_requests'] ?></span>
                            </a>
                            <a href="<?= url('admin/association-requests?status=rejected') ?>" class="wh-dash-req">
                                <div class="wh-dash-req-icon" style="background:#f8d7da;color:var(--wh-red)"><i class="mdi mdi-close-circle-outline"></i></div>
                                <span class="wh-dash-req-label"><?= $isAr ? 'مرفوضة' : 'Refusées' ?></span>
                                <span class="wh-dash-req-count" style="color:var(--wh-red)"><?= (int) $kpis['rejected_requests'] ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 wh-dash-animate">
                <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box blue"><i class="mdi mdi-chart-line"></i></div>
                            <span class="fw-bold"><?= $isAr ? 'التطور الشهري' : 'Évolution mensuelle' ?></span>
                        </div>
                        <span class="text-muted" style="font-size:.75rem">6 <?= $isAr ? 'أشهر' : 'derniers mois' ?></span>
                    </div>
                    <div class="card-body">
                        <div class="wh-chart"><canvas id="chartMois"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Bottom row: Demandes + Notifications + Activity + Photos ═══ -->
    <div class="row g-4 mb-4">
        <!-- Dernières demandes -->
        <div class="col-lg-6 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box blue"><i class="mdi mdi-account-plus-outline"></i></div>
                        <span class="fw-bold"><?= $isAr ? 'أحدث الطلبات' : 'Dernières demandes' ?></span>
                    </div>
                    <a href="<?= url('admin/association-requests') ?>" class="btn btn-sm btn-outline-primary">
                        <?= e(__('common.all')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($latestRequests as $rq): ?>
                        <?php
                            $rqStatus = (string) ($rq['status'] ?? 'pending');
                            $rqBadge = match ($rqStatus) { 'approved' => 'badge-green', 'rejected' => 'badge-red', default => 'badge-amber' };
                            $rqLabel = match ($rqStatus) {
                                'approved' => $isAr ? 'مقبول' : 'Approuvée',
                                'rejected' => $isAr ? 'مرفوضة' : 'Refusée',
                                default    => $isAr ? 'قيد الانتظار' : 'En attente',
                            };
                            $rqOld = ($rqStatus === 'pending') && (int) ($rq['age_jours'] ?? 0) >= 7;
                            $rqIconBg = match ($rqStatus) { 'approved' => 'var(--wh-green-soft)', 'rejected' => '#f8d7da', default => '#fff3cd' };
                            $rqIconFg = match ($rqStatus) { 'approved' => 'var(--wh-green)', 'rejected' => 'var(--wh-red)', default => 'var(--wh-amber)' };
                        ?>
                        <div class="wh-dash-list-item">
                            <div class="wh-dash-list-icon" style="background:<?= $rqIconBg ?>;color:<?= $rqIconFg ?>">
                                <i class="mdi mdi-account-outline"></i>
                            </div>
                            <div class="wh-dash-list-body">
                                <a href="<?= url('admin/association-requests/' . (int) $rq['id']) ?>" class="text-decoration-none fw-semibold wh-dash-list-title text-truncate d-block">
                                    <?= e($rq['association_name']) ?>
                                </a>
                                <div class="wh-dash-list-sub">
                                    <i class="mdi mdi-calendar-outline me-1"></i><?= e(date('d/m/Y', strtotime((string) $rq['created_at']))) ?>
                                    <?php if (! empty($rq['approval_file'])): ?>
                                        <i class="mdi mdi-file-document-outline text-primary ms-2"></i>
                                    <?php endif; ?>
                                    <?php if ($rqOld): ?>
                                        <span class="wh-badge badge-red ms-1"><i class="mdi mdi-alert-outline me-1"></i><?= $isAr ? '+7 أيام' : '+7j' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="wh-badge <?= $rqBadge ?>"><?= $rqLabel ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($latestRequests === []): ?>
                        <div class="wh-empty p-3"><p class="mb-0"><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Notifications -->
        <div class="col-lg-6 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box green"><i class="mdi mdi-bell-outline"></i></div>
                        <span class="fw-bold"><?= $isAr ? 'التنبيهات' : 'Notifications' ?></span>
                    </div>
                    <?php if ((int) $unreadNotifs > 0): ?>
                        <span class="badge bg-danger" style="font-size:.68rem"><?= (int) $unreadNotifs ?> <?= $isAr ? 'جديد' : 'nouveau(x)' ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($notifFeed as $nf): ?>
                        <div class="wh-dash-list-item">
                            <div class="wh-dash-list-icon" style="background:<?= (int) ($nf['lu'] ?? 0) ? 'var(--wh-gray-soft)' : 'var(--wh-blue-soft)' ?>;color:<?= (int) ($nf['lu'] ?? 0) ? 'var(--wh-gray)' : 'var(--wh-blue)' ?>">
                                <i class="mdi <?= (int) ($nf['lu'] ?? 0) ? 'mdi-bell-outline' : 'mdi-bell-ring' ?>"></i>
                            </div>
                            <div class="wh-dash-list-body">
                                <div class="wh-dash-list-title"><?= e($nf['titre']) ?></div>
                                <div class="wh-dash-list-sub text-truncate"><?= e($nf['message_notif']) ?></div>
                            </div>
                            <span class="wh-dash-list-time">
                                <?= e(date('d/m H:i', strtotime((string) $nf['date_creation']))) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($notifFeed === []): ?>
                        <div class="wh-empty p-3"><p class="mb-0"><?= $isAr ? 'لا توجد تنبيهات' : 'Aucune notification' ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Bottom row: Activity + Photos ═══ -->
    <div class="row g-4">
        <!-- Activité récente -->
        <div class="col-lg-6 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box blue"><i class="mdi mdi-history"></i></div>
                        <span class="fw-bold"><?= $isAr ? 'النشاط الأخير' : 'Activité récente' ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($recentActivity as $act): ?>
                        <?php $ac = $actionColor($act['action']); ?>
                        <div class="wh-dash-list-item">
                            <div class="wh-dash-list-icon" style="background:<?= $ac['bg'] ?>;color:<?= $ac['fg'] ?>">
                                <i class="mdi <?= $actionIcon($act['action']) ?>"></i>
                            </div>
                            <div class="wh-dash-list-body">
                                <div class="wh-dash-list-title"><?= e($act['action']) ?></div>
                                <div class="wh-dash-list-sub">
                                    <?= e(trim(($act['nom'] ?? '') . ' ' . ($act['prenom'] ?? ''))) ?>
                                    · <?= e($act['modele'] ?? '') ?>
                                </div>
                            </div>
                            <span class="wh-dash-list-time">
                                <?= e(date('d/m H:i', strtotime((string) $act['created_at']))) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($recentActivity === []): ?>
                        <div class="wh-empty p-3"><p class="mb-0"><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Photos récentes -->
        <div class="col-lg-6 wh-dash-animate">
            <div class="card wh-dash-card rounded-2xl transition-shadow hover:shadow-wh-xl h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box purple"><i class="mdi mdi-camera"></i></div>
                        <span class="fw-bold"><?= $isAr ? 'آخر الصور' : 'Photos récentes' ?></span>
                    </div>
                    <a href="<?= url('wilaya/gallery') ?>" class="btn btn-sm btn-outline-primary">
                        <?= e(__('common.gallery')) ?> <i class="mdi mdi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (! empty($recentPhotos)): ?>
                        <div class="row g-2">
                            <?php foreach ($recentPhotos as $rp): ?>
                                <div class="col-4 col-md-3">
                                    <div class="wh-dash-photo" style="aspect-ratio:1">
                                        <img src="<?= e($rp['image']) ?>" alt="" loading="lazy">
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
            animation: { duration: 800, easing: 'easeOutQuart' },
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
                    ticks: { color: ticks, font: { weight: '500', family: "'Space Grotesk', sans-serif" } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: grid },
                    ticks: { color: ticks, precision: 0, font: { family: "'Space Grotesk', sans-serif" } }
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
    // — IA prediction 3 mois (régression linéaire simple)
    var pred=[], predLabels=[];
    if(nbs.length>=2){
        var sumX=0,sumY=0,sumXY=0,sumXX=0; for(var i=0;i<nbs.length;i++){ sumX+=i; sumY+=nbs[i]; sumXY+=i*nbs[i]; sumXX+=i*i; }
        var slope=(nbs.length*sumXY - sumX*sumY)/(nbs.length*sumXX - sumX*sumX || 1);
        var intercept=(sumY - slope*sumX)/nbs.length;
        for(var p=1;p<=3;p++){ var idx=nbs.length-1+p; pred.push(Math.max(0, Math.round(slope*idx+intercept))); predLabels.push('P'+p); }
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: mois.concat(predLabels),
            datasets: [{
                label: <?= json_encode(__('common.total')) ?>,
                data: nbs.concat(new Array(pred.length).fill(null)),
                borderColor: '#0B5ED7',
                backgroundColor: function(ctx) {
                    var chart = ctx.chart;
                    var area = chart.ctx.createLinearGradient(0, 0, 0, chart.height);
                    area.addColorStop(0, 'rgba(11,94,215,.18)');
                    area.addColorStop(1, 'rgba(11,94,215,.02)');
                    return area;
                },
                fill: true,
                tension: .4,
                pointRadius: 5,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0B5ED7',
                pointBorderWidth: 2.5,
                pointHoverRadius: 7,
                borderWidth: 2.5
            },{
                label: 'Prévision IA',
                data: new Array(nbs.length).fill(null).concat(pred),
                borderColor: '#f59e0b',
                borderDash: [6,4],
                pointRadius: 4,
                pointStyle: 'triangle',
                pointBackgroundColor: '#fff',
                pointBorderColor: '#f59e0b',
                fill: false,
                tension: .4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 800, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: true, labels:{ color:ticks, boxWidth:12, usePointStyle:true } },
                tooltip: {
                    backgroundColor: dark ? '#1a2332' : '#fff',
                    titleColor: dark ? '#e6ebf2' : '#212b36',
                    bodyColor: dark ? '#b8c7dc' : '#697586',
                    borderColor: dark ? '#2b3648' : '#dee2e6',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    displayColors: true,
                    titleFont: { weight: '600' }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: ticks, font: { weight: '500', family: "'Space Grotesk', sans-serif" } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: grid },
                    ticks: { color: ticks, precision: 0, font: { family: "'Space Grotesk', sans-serif" } }
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
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            animation: { animateRotate: true, duration: 800 },
            plugins: {
                legend: { position: 'right', labels: { color: ticks, boxWidth: 14, padding: 12, font: { family: "'Space Grotesk', sans-serif" } } },
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

document.getElementById('btnPdf')?.addEventListener('click', function(){
    try{
        var c1=document.getElementById('chartStatuts'); if(c1) document.getElementById('pdfChartStatuts').value=c1.toDataURL('image/png');
        var c2=document.getElementById('chartMois'); if(c2) document.getElementById('pdfChartMois').value=c2.toDataURL('image/png');
        var c3=document.getElementById('chartRepartitionOrg'); if(c3) document.getElementById('pdfChartOrg').value=c3.toDataURL('image/png');
    }catch(e){}
    document.getElementById('pdfForm').submit();
});
</script>
