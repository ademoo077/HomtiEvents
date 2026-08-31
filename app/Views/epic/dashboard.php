<?php
/**
 * Tableau de bord EPIC — événements attribués, calendrier, anomalies.
 *
 * @var array  $epic
 * @var int    $epicId
 * @var array  $kpis
 * @var string $mois
 * @var array  $parJour
 * @var array  $avenir
 * @var array  $anomalies
 * @var int    $badgeAnomalies
 * @var array  $communes
 * @var array  $filters
 * @var bool   $isRtl
 */
use App\Helpers\I18n;

$title = ($epic['nom'] ?? __('common.epic')) . ' — ' . ($isRtl ? 'لوحة القيادة' : 'Tableau de bord');
$page  = 'epic.dashboard';
$dir   = I18n::direction();

$statutBadge = static function (string $statut): string {
    return match ($statut) {
        'VALIDÉ'                => 'badge-info',
        'PROGRAMME', 'QR_GENERE' => 'badge-primary',
        'EN_COURS'              => 'badge-warning',
        'TERMINE'               => 'badge-success',
        'REFUSE'                => 'badge-danger',
        'MODIFICATION_DEMANDEE' => 'badge-danger',
        default                 => 'badge-gray',
    };
};
$statutColor = static function (string $statut): string {
    return match ($statut) {
        'PROGRAMME' => '#6366f1',
        'QR_GENERE' => '#a78bfa',
        'EN_COURS'  => '#06b6d4',
        default     => '#94a3b8',
    };
};

// ── Calendrier : construction de la grille mensuelle (lundi → dimanche) ──
$annee   = (int) substr($mois, 0, 4);
$moisNum = (int) substr($mois, 5, 2);
$nbJours = (int) date('t', mktime(0, 0, 0, $moisNum, 1, $annee));
$nSemaine = (int) date('N', mktime(0, 0, 0, $moisNum, 1, $annee)); // lun=1 … dim=7
// Grille lundi→dimanche (LTR) ou samedi→dimanche (RTL, inversion CSS grid).
$decalage = $isRtl ? ($nSemaine + 1) % 7 : ($nSemaine - 1) % 7;
$moisPrec = date('Y-m', mktime(0, 0, 0, $moisNum - 1, 1, $annee));
$moisSuiv = date('Y-m', mktime(0, 0, 0, $moisNum + 1, 1, $annee));
$aujourdhui = date('Y-m-d');

$kpiTiles = [
    ['val' => (int) $kpis['total'],      'label' => $isRtl ? 'المجموع' : 'Total attribué',       'icon' => 'mdi-calendar-star',     'teinte' => 'blue'],
    ['val' => (int) $kpis['VALIDÉ'],     'label' => $isRtl ? 'مقبول' : 'Validés',                'icon' => 'mdi-check-decagram',    'teinte' => 'green'],
    ['val' => (int) $kpis['PROGRAMME'],  'label' => $isRtl ? 'مبرمج' : 'Programmés',             'icon' => 'mdi-calendar-check',    'teinte' => 'purple'],
    ['val' => (int) $kpis['EN_COURS'],   'label' => $isRtl ? 'جاري' : 'En cours',                'icon' => 'mdi-progress-wrench',   'teinte' => 'amber'],
    ['val' => (int) $kpis['TERMINE'],    'label' => $isRtl ? 'منتهي' : 'Terminés',               'icon' => 'mdi-check-circle',      'teinte' => 'green'],
    ['val' => (int) $kpis['REFUSE'],     'label' => $isRtl ? 'مرفوض' : 'Refusés',                'icon' => 'mdi-alert-octagon',     'teinte' => 'red'],
];
$joursSemaine = $isRtl
    ? ['السبت', 'الجمعة', 'الخميس', 'الأربعاء', 'الثلاثاء', 'الاثنين', 'الأحد']
    : ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$moisNoms = $isRtl
    ? ['جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أوت', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
    : ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$moisLabel = ($moisNoms[$moisNum - 1] ?? '') . ' ' . $annee;
?>
<div class="wh-page">
    <div class="wh-hero" style="background: linear-gradient(135deg, #0891B2 0%, #0B5ED7 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-satellite-variant me-2"></i><?= e(($epic['nom'] ?? __('common.epic'))) ?></h1>
                    <p class="wh-hero-sub"><?= $isRtl ? 'لوحة قيادة الأحداث الموكلة ومراقبة الحالات' : 'Événements attribués, calendrier et anomalies de votre zone d\'intervention' ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-outline-light" href="<?= url('epic') ?>">
                        <i class="mdi mdi-clipboard-text-outline me-1"></i><?= $isRtl ? 'التدخلات' : 'Interventions' ?>
                    </a>
                    <a class="btn btn-light" href="<?= url('epic/dashboard/export') ?>">
                        <i class="mdi mdi-file-delimited-outline me-1"></i><?= $isRtl ? 'تصدير CSV' : 'Exporter CSV' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($badgeAnomalies > 3): ?>
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" data-autohide role="alert">
            <i class="mdi mdi-alert-decagram"></i>
            <div class="flex-grow-1">
                <?= $isRtl
                    ? 'تنبيه : ' . (int) $badgeAnomalies . ' حالة شاذة غير معالجة هذا الأسبوع.'
                    : 'Alerte : ' . (int) $badgeAnomalies . ' anomalies non traitées cette semaine.' ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Idées & conseils : actions recommandées selon le contexte -->
    <?php if (! empty($suggestions)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light d-flex align-items-center gap-2">
                <i class="mdi mdi-lightbulb-on-outline text-warning"></i>
                <h3 class="h6 mb-0"><?= $isRtl ? 'أفكار وتوصيات' : 'Idées & conseils' ?></h3>
            </div>
            <div class="card-body">
                <div class="wh-ideas">
                    <?php foreach ($suggestions as $s): ?>
                        <div class="wh-idea">
                            <span class="wh-idea-icon <?= e($s['color'] ?? 'primary') ?>">
                                <i class="mdi <?= e($s['icon']) ?>"></i>
                            </span>
                            <div class="wh-idea-body">
                                <?php if (! empty($s['titre'])): ?>
                                    <div class="wh-idea-title"><?= e($s['titre']) ?></div>
                                <?php endif; ?>
                                <div class="wh-idea-text"><?= e($s['texte']) ?></div>
                                <?php if (! empty($s['lien'])): ?>
                                    <a class="wh-idea-link" href="<?= e($s['lien']) ?>">
                                        <?= e($s['cta'] ?? ($isRtl ? 'عرض التفاصيل' : 'Voir les détails')) ?>
                                        <i class="mdi <?= $isRtl ? 'mdi-arrow-left' : 'mdi-arrow-right' ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtres (KPIs + anomalies) -->
    <form method="get" action="<?= url('epic/dashboard') ?>" class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1"><?= $isRtl ? 'من تاريخ' : 'Du' ?></label>
                    <input type="date" name="du" class="form-control" value="<?= e($filters['du'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1"><?= $isRtl ? 'إلى تاريخ' : 'Au' ?></label>
                    <input type="date" name="au" class="form-control" value="<?= e($filters['au'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1"><?= $isRtl ? 'البلدية' : 'Commune' ?></label>
                    <select name="commune_id" class="form-select">
                        <option value="0"><?= $isRtl ? 'جميع البلديات' : 'Toutes les communes' ?></option>
                        <?php foreach ($communes as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= ((int) ($filters['commune_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary flex-fill">
                        <i class="mdi mdi-filter-variant me-1"></i><?= $isRtl ? 'تصفية' : 'Filtrer' ?>
                    </button>
                    <a href="<?= url('epic/dashboard') ?>" class="btn btn-link text-decoration-none"><?= $isRtl ? 'إعادة' : 'Réinitialiser' ?></a>
                </div>
            </div>
        </div>
    </form>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <?php foreach ($kpiTiles as $kpi): ?>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="wh-kpi wh-kpi-hover">
                    <div class="wh-kpi-icon <?= e($kpi['teinte']) ?>"><i class="mdi <?= e($kpi['icon']) ?>"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= (int) $kpi['val'] ?></div>
                        <div class="wh-kpi-label"><?= e($kpi['label']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4 mb-4">
        <!-- Calendrier mensuel -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h6 class="fw-bold mb-0">
                            <i class="mdi mdi-calendar-month me-2"></i>
                            <span id="whCalLabel" class="d-inline-block" style="min-width:130px"><?= e($moisLabel) ?></span>
                            <i id="whCalSpinner" class="mdi mdi-loading mdi-spin ms-1" style="display:none"></i>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="whCalPrev" title="<?= $isRtl ? 'السابق' : 'Mois précédent' ?>" data-mois="<?= e($moisPrec) ?>">
                                <i class="mdi <?= $isRtl ? 'mdi-chevron-right' : 'mdi-chevron-left' ?>"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="whCalToday" title="<?= $isRtl ? 'اليوم' : 'Aujourd\'hui' ?>">
                                <?= $isRtl ? 'اليوم' : 'Aujourd\'hui' ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="whCalNext" title="<?= $isRtl ? 'التالي' : 'Mois suivant' ?>" data-mois="<?= e($moisSuiv) ?>">
                                <i class="mdi <?= $isRtl ? 'mdi-chevron-left' : 'mdi-chevron-right' ?>"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Résumé du mois affiché (dynamique) -->
                    <div id="whCalSummary" class="d-flex flex-wrap gap-2 mb-3">
                        <span class="wh-cal-sum bg-soft-blue"><i class="mdi mdi-calendar-star me-1"></i><b><?= (int) $kpis['PROGRAMME'] ?></b> <?= $isRtl ? 'مبرمج' : 'programmés' ?></span>
                        <span class="wh-cal-sum bg-soft-purple"><i class="mdi mdi-qrcode-scan me-1"></i><b>0</b> <?= $isRtl ? 'رمز QR' : 'QR' ?></span>
                        <span class="wh-cal-sum bg-soft-cyan"><i class="mdi mdi-progress-wrench me-1"></i><b>0</b> <?= $isRtl ? 'جارية' : 'en cours' ?></span>
                    </div>

                    <div class="wh-cal">
                        <div class="wh-cal-head" id="whCalHead">
                            <?php foreach ($joursSemaine as $j): ?>
                                <div class="wh-cal-head-cell"><?= e($j) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="wh-cal-grid" id="whCalGrid">
                            <?php for ($i = 0; $i < $decalage; $i++): ?>
                                <div class="wh-cal-cell is-muted"></div>
                            <?php endfor; ?>

                            <?php for ($jour = 1; $jour <= $nbJours; $jour++): ?>
                                <?php
                                $date = sprintf('%04d-%02d-%02d', $annee, $moisNum, $jour);
                                $evs = $parJour[$date] ?? [];
                                $estAujourdhui = $date === $aujourdhui;
                                $jourSemaine   = (int) date('w', mktime(0, 0, 0, $moisNum, $jour, $annee));
                                $estWeekend    = (int) $jourSemaine === 0 || (int) $jourSemaine === 6;
                                ?>
                                <div class="wh-cal-cell <?= $evs !== [] ? 'has-events' : '' ?> <?= $estAujourdhui ? 'is-today' : '' ?> <?= $estWeekend ? 'is-weekend' : '' ?>" data-date="<?= e($date) ?>" data-jour="<?= $jour ?>">
                                    <span class="wh-cal-day">
                                        <?= $jour ?>
                                        <?php if ($evs !== []): ?>
                                            <span class="wh-cal-count"><?= count($evs) ?></span>
                                        <?php endif; ?>
                                    </span>
<?php if ($evs !== []): ?>
                                        <div class="wh-cal-events">
                                            <?php foreach (array_slice($evs, 0, 2) as $ev): ?>
                                                <span class="wh-cal-ev" title="<?= e($ev['adresse'] ?? '') ?>">
                                                    <?php if (! empty($ev['heure'])): ?><i class="wh-cal-h"><?= e(date('H:i', strtotime((string) $ev['heure']))) ?></i><?php endif; ?>
                                                    <i class="wh-cal-dot" style="background:<?= $statutColor((string) $ev['statut']) ?>"></i>
                                                    <span class="wh-cal-ev-label"><?= e(mb_strimwidth((string) ($ev['adresse'] ?? ''), 0, 26, '…')) ?></span>
                                                    <?php if (! empty($ev['token_qr'])): ?>
                                                        <i class="mdi mdi-qrcode wh-cal-qr" title="QR disponible"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($evs) > 2): ?>
                                                <span class="wh-cal-more">+<?= count($evs) - 2 ?> <?= $isRtl ? 'أخرى' : 'autres' ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 mt-3 small text-muted">
                        <span class="d-inline-flex align-items-center gap-1"><span class="wh-cal-dot" style="background:#6366f1"></span><?= $isRtl ? 'مبرمج' : 'Programmé' ?></span>
                        <span class="d-inline-flex align-items-center gap-1"><span class="wh-cal-dot" style="background:#a78bfa"></span><?= $isRtl ? 'تم توليد الرمز' : 'QR généré' ?></span>
                        <span class="d-inline-flex align-items-center gap-1"><span class="wh-cal-dot" style="background:#06b6d4"></span><?= $isRtl ? 'جاري' : 'En cours' ?></span>
                        <span class="ms-auto"><?= $isRtl ? 'انقر على يوم لعرض الأحداث' : 'Cliquez sur un jour pour afficher ses événements' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- À venir -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="mdi mdi-calendar-clock me-2"></i><?= $isRtl ? 'الأحداث القادمة (3 jours)' : 'À venir (3 jours)' ?>
                    </h6>
                    <?php if ($avenir === []): ?>
                        <div class="wh-empty text-center text-muted py-4">
                            <i class="mdi mdi-calendar-blank mdi-24px"></i>
                            <p class="mb-0 small"><?= $isRtl ? 'لا توجد أحداث خلال الأيام الثلاثة القادمة.' : 'Aucun événement dans les 3 prochains jours.' ?></p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($avenir as $ev): ?>
                                <li class="list-group-item px-0">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="wh-cal-chip">
                                            <strong><?= e(date('d', strtotime((string) $ev['date_evenement']))) ?></strong>
                                            <small><?= e(date('M', strtotime((string) $ev['date_evenement']))) ?></small>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold small text-truncate"><?= e($ev['adresse'] ?? '-') ?></div>
                                            <div class="small text-muted">
                                                <?= e($ev['commune_nom'] ?? '-') ?>
                                                <?php if (! empty($ev['heure'])): ?> · <?= e(date('H:i', strtotime((string) $ev['heure']))) ?><?php endif; ?>
                                                <?php if (! empty($ev['token_qr'])): ?>
                                                    <span class="ms-2 text-primary" title="QR disponible"><i class="mdi mdi-qrcode"></i></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge <?= $statutBadge((string) $ev['statut']) ?> mt-1"><?= e(statut_label((string) $ev['statut'])) ?></span>
                                        </div>
                                         <a class="btn btn-sm btn-outline-primary flex-shrink-0" href="<?= url('epic/' . (int) $ev['id']) ?>" title="<?= $isRtl ? 'إدارة' : 'Gérer' ?>">
                                             <i class="mdi mdi-open-in-new"></i>
                                         </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Nouveaux événements routés (48h) -->
    <?php if (! empty($nouveauxRoutages)): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="mdi mdi-router-wireless-outline me-2"></i><?= $isRtl ? 'أحداث موكلة حديثًا (48 ساعة)' : 'Nouveaux événements routés (48 h)' ?></span>
                    <span class="wh-badge badge-violet"><?= count($nouveauxRoutages) ?></span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($nouveauxRoutages as $r): ?>
                            <li class="list-group-item d-flex align-items-center justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate"><?= e($r['evenement_adresse'] ?? $r['adresse'] ?? '-') ?></div>
                                    <div class="small text-muted">
                                        <?= e(date('d/m/Y H:i', strtotime((string) ($r['created_at'] ?? $r['date_creation'] ?? 'now')))) ?> ·
                                        <?= e($r['commune_nom'] ?? '-') ?> ·
                                        <span class="text-capitalize"><?= e(strtolower((string) ($r['rule_matched'] ?? ''))) ?></span>
                                    </div>
                                </div>
                                 <a class="btn btn-sm btn-outline-primary flex-shrink-0" href="<?= url('epic/' . (int) $r['evenement_id']) ?>" title="<?= $isRtl ? 'إدارة' : 'Gérer' ?>">
                                     <i class="mdi mdi-open-in-new"></i>
                                 </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Anomalies -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="mdi mdi-alert-octagon me-2"></i><?= $isRtl ? 'توزيع الحالات الشاذة حسب السبب' : 'Anomalies par motif' ?>
                    </h6>
                    <?php if ($anomalies === []): ?>
                        <div class="wh-empty text-center text-muted py-4">
                            <i class="mdi mdi-check-circle-outline mdi-24px text-success"></i>
                            <p class="mb-0 small"><?= $isRtl ? 'لا توجد حالات شاذة.' : 'Aucune anomalie sur vos événements.' ?></p>
                        </div>
                    <?php else: ?>
                        <div class="wh-chart"><canvas id="chartAnomalies" style="height:260px"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="mdi mdi-clipboard-alert me-2"></i><?= $isRtl ? 'تفاصيل الحالات الشاذة' : 'Détail des anomalies' ?>
                    </h6>
                    <?php if ($anomalies === []): ?>
                        <div class="wh-empty text-center text-muted py-4">
                            <i class="mdi mdi-check mdi-24px text-success"></i>
                            <p class="mb-0 small"><?= $isRtl ? 'كل شيء تحت السيطرة.' : 'Aucune anomalie à signaler.' ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-muted small fw-semibold"><?= $isRtl ? 'السبب' : 'Motif' ?></th>
                                    <th class="text-end text-muted small fw-semibold"><?= $isRtl ? 'العدد' : 'Nombre' ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($anomalies as $a): ?>
                                    <tr>
                                        <td>
                                            <i class="mdi mdi-information-outline text-danger me-1"></i><?= e($a['motif']) ?>
                                        </td>
                                        <td class="text-end fw-bold"><?= (int) $a['nb'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span><?= $isRtl ? 'الحالات الشاذة هذا الأسبوع' : 'Anomalies de la semaine' ?></span>
                                <span class="fw-bold"><?= (int) $badgeAnomalies ?></span>
                            </div>
                            <div class="progress" style="height:8px;border-radius:8px">
                                <div class="progress-bar bg-danger" role="progressbar"
                                     style="width:<?= min(100, (int) $badgeAnomalies * 25) ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem}</style>
</div>

<!-- Modal : événements du jour -->
<div class="modal fade" id="epicDayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-calendar-day me-2"></i><span id="epicDayTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="epicDayBody">
                <div class="text-center text-muted py-4"><i class="mdi mdi-loading mdi-spin mdi-24px"></i></div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var isRtl = <?= $isRtl ? 'true' : 'false' ?>;

    // ── Calendrier dynamique : mois précédent / suivant / aujourd'hui ──
    var calApi  = '<?= url('api/epic/calendar') ?>';
    var calGrid = document.getElementById('whCalGrid');
    var calLabel = document.getElementById('whCalLabel');
    var calSpinner = document.getElementById('whCalSpinner');
    var calSummary = document.getElementById('whCalSummary');
    var currentMois = '<?= e($mois) ?>';
    var communeId = parseInt('<?= (int) ($filters['commune_id'] ?? 0) ?>', 10) || 0;

    var moisNoms = <?= json_encode($moisNoms, JSON_UNESCAPED_UNICODE) ?>;
    var statutColors = {
        'PROGRAMME': '#6366f1',
        'QR_GENERE': '#a78bfa',
        'EN_COURS':  '#06b6d4'
    };

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function shiftMois(m, diff) {
        var p = m.split('-');
        var y = parseInt(p[0], 10), mo = parseInt(p[1], 10);
        var d = new Date(y, mo - 1 + diff, 1);
        return d.getFullYear() + '-' + pad(d.getMonth() + 1);
    }

    function buildSummary(data) {
        if (!calSummary) return;
        var total = data.total || 0;
        var ps = data.parStatut || {};
        var prog = (ps['PROGRAMME'] || 0) + (ps['QR_GENERE'] || 0);
        var qr = ps['QR_GENERE'] || 0;
        var encours = ps['EN_COURS'] || 0;
        calSummary.innerHTML =
            '<span class="wh-cal-sum bg-soft-blue"><i class="mdi mdi-calendar-star me-1"></i><b>' + total + '</b> ' +
                (isRtl ? 'حدثًا' : 'événements') + '</span>' +
            '<span class="wh-cal-sum bg-soft-purple"><i class="mdi mdi-calendar-check me-1"></i><b>' + prog + '</b> ' +
                (isRtl ? 'مبرمج' : 'programmés') + '</span>' +
            '<span class="wh-cal-sum bg-soft-cyan"><i class="mdi mdi-qrcode-scan me-1"></i><b>' + qr + '</b> ' +
                (isRtl ? 'رمز QR' : 'QR générés') + '</span>' +
            '<span class="wh-cal-sum bg-soft-blue low"><i class="mdi mdi-progress-wrench me-1"></i><b>' + encours + '</b> ' +
                (isRtl ? 'جارية' : 'en cours') + '</span>';
    }

    function updateCalendarLinks(data) {
        var prevBtn = document.getElementById('whCalPrev');
        var nextBtn = document.getElementById('whCalNext');
        if (prevBtn) prevBtn.setAttribute('data-mois', shiftMois(data.mois, -1));
        if (nextBtn) nextBtn.setAttribute('data-mois', shiftMois(data.mois, 1));
    }

    function buildGrid(data) {
        if (!calGrid) return;
        var y = data.annee, mo = data.moisNum, nb = data.nbJours;
        var today = new Date().getFullYear() + '-' + pad((new Date().getMonth() + 1)) + '-' + pad(new Date().getDate());
        var jours = data.jours || {};
        var html = '';
        for (var i = 0; i < data.decalage; i++) html += '<div class="wh-cal-cell is-muted"></div>';
        for (var d = 1; d <= nb; d++) {
            var date = y + '-' + pad(mo) + '-' + pad(d);
            var evs = jours[date] || [];
            var dow = new Date(y, mo - 1, d).getDay();
            var weekend = (dow === 0 || dow === 6) ? ' is-weekend' : '';
            var todayCls = (date === today) ? ' is-today' : '';
            html += '<div class="wh-cal-cell' + (evs.length ? ' has-events' : '') + todayCls + weekend + '" data-date="' + date + '">' +
                '<span class="wh-cal-day">' + d + (evs.length ? '<span class="wh-cal-count">' + evs.length + '</span>' : '') + '</span>';
            if (evs.length) {
                html += '<div class="wh-cal-events">';
                evs.slice(0, 2).forEach(function (ev) {
                    html += '<span class="wh-cal-ev" title="' + (ev.adresse || '') + '">';
                    if (ev.heure) html += '<i class="wh-cal-h">' + ev.heure.slice(0, 5) + '</i>';
                    html += '<i class="wh-cal-dot" style="background:' + (statutColors[ev.statut] || '#94a3b8') + '"></i>';
                    html += '<span class="wh-cal-ev-label">' + trunc(ev.adresse || '', 26) + '</span>';
                    if (ev.token_qr) html += '<i class="mdi mdi-qrcode wh-cal-qr"></i>';
                    html += '</span>';
                });
                if (evs.length > 2) html += '<span class="wh-cal-more">+' + (evs.length - 2) + ' ' + (isRtl ? 'أخرى' : 'autres') + '</span>';
                html += '</div>';
            }
            html += '</div>';
        }
        calGrid.innerHTML = html;
        bindCells();
        updateCalendarLinks(data);
    }

    function trunc(s, n) {
        if (!s) return '';
        if (s.length > n) return s.substring(0, n) + '…';
        return s;
    }

    function loadMonth(mois) {
        if (calSpinner) calSpinner.style.display = 'inline-block';
        fetch(calApi + '?mois=' + mois + (communeId ? '&commune_id=' + communeId : ''), { headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (calSpinner) calSpinner.style.display = 'none';
                if (!data || !data.success) { location.reload(); return; }
                var nom = moisNoms[data.moisNum - 1] || '';
                if (calLabel) calLabel.textContent = (nom || '') + ' ' + data.annee;
                currentMois = data.mois;
                buildSummary(data);
                buildGrid(data);
            })
            .catch(function () { if (calSpinner) calSpinner.style.display = 'none'; location.reload(); });
    }

    document.getElementById('whCalPrev').addEventListener('click', function (e) {
        e.preventDefault();
        loadMonth(this.getAttribute('data-mois') || shiftMois(currentMois, -1));
    });
    document.getElementById('whCalNext').addEventListener('click', function (e) {
        e.preventDefault();
        loadMonth(this.getAttribute('data-mois') || shiftMois(currentMois, 1));
    });
    document.getElementById('whCalToday').addEventListener('click', function (e) {
        e.preventDefault();
        loadMonth(new Date().getFullYear() + '-' + pad(new Date().getMonth() + 1));
    });

    // ── Calendrier : clic sur un jour → modal (API) ──
    var apiUrl = '<?= url('api/epic/events') ?>';
    var dayModal = document.getElementById('epicDayModal');
    var dayTitle = document.getElementById('epicDayTitle');
    var dayBody = document.getElementById('epicDayBody');
    var modalEl = dayModal ? new bootstrap.Modal(dayModal) : null;

    function frDate(d) {
        var parts = d.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function renderEvents(resp) {
        if (!resp || !resp.success) {
            dayBody.innerHTML = '<div class="alert alert-danger mb-0">' +
                (resp && resp.error ? resp.error : 'Erreur de chargement.') + '</div>';
            return;
        }
        if (!resp.count || !resp.events.length) {
            dayBody.innerHTML = '<div class="text-center text-muted py-4"><i class="mdi mdi-calendar-blank mdi-24px"></i>' +
                '<p class="mb-0 mt-2"><?= $isRtl ? 'لا توجد أحداث في هذا اليوم.' : 'Aucun événement ce jour.' ?></p></div>';
            return;
        }
        var html = '<ul class="list-group list-group-flush">';
        resp.events.forEach(function (ev) {
            var chip = ev.heure
                ? '<span class="badge rounded-pill text-bg-primary flex-shrink-0 align-self-start mt-1"><i class="mdi mdi-clock-outline me-1"></i>' + ev.heure.slice(0, 5) + '</span>'
                : '';
            html += '<li class="list-group-item px-0"><div class="d-flex align-items-start gap-2">' +
                chip +
                '<div class="flex-grow-1 min-w-0">' +
                '<div class="fw-semibold small">' + (ev.adresse || '-') + '</div>' +
                '<div class="small text-muted">' +
                (ev.association ? ev.association + ' — ' : '') + ev.commune +
                '</div>' +
                (ev.motif ? '<div class="small text-danger mt-1"><i class="mdi mdi-information-outline"></i> ' + ev.motif + '</div>' : '') +
                '</div>' +
                '<div class="d-flex align-items-center gap-1 flex-shrink-0 align-self-start">' +
                '<span class="badge badge-soft ms-1">' + ev.statut_lib + '</span>' +
                (ev.token_qr
                    ? '<a class="btn btn-sm btn-outline-secondary" href="' + (ev.url_qr || '') + '" title="QR Code"><i class="mdi mdi-qrcode"></i></a>'
                    : '') +
                '<a class="btn btn-sm btn-outline-primary" href="' + (ev.url_epic || ev.url_admin) + '" title="<?= $isRtl ? 'إدارة' : 'Gérer' ?>"><i class="mdi mdi-open-in-new"></i></a>' +
                '</div></div></li>';
        });
        html += '</ul>';
        dayBody.innerHTML = html;
    }

    function openDay(date) {
        if (!date) return;
        dayTitle.textContent = frDate(date);
        dayBody.innerHTML = '<div class="text-center text-muted py-4"><i class="mdi mdi-loading mdi-spin mdi-24px"></i></div>';
        if (modalEl) modalEl.show();
        fetch(apiUrl + '?date=' + date, { headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(renderEvents)
            .catch(function () {
                dayBody.innerHTML = '<div class="alert alert-danger mb-0">Erreur réseau.</div>';
            });
    }

    function bindCells() {
        calGrid.querySelectorAll('[data-date]').forEach(function (cell) {
            cell.addEventListener('click', function () {
                if (cell.classList.contains('has-events')) openDay(cell.getAttribute('data-date'));
            });
            cell.addEventListener('keydown', function (e) {
                if ((e.key === 'Enter' || e.key === ' ') && cell.classList.contains('has-events')) {
                    e.preventDefault();
                    openDay(cell.getAttribute('data-date'));
                }
            });
        });
    }
    bindCells();

    // ── Chart.js : répartition des anomalies ──
    var c = document.getElementById('chartAnomalies');
    if (c && typeof Chart !== 'undefined') {
        var labels = [], counts = [];
        <?php foreach ($anomalies as $a): ?>
            labels.push(<?= json_encode($a['motif'], JSON_UNESCAPED_UNICODE) ?>);
            counts.push(<?= (int) $a['nb'] ?>);
        <?php endforeach; ?>
        var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        var ticks = dark ? '#9aa3b2' : '#697586';
        new Chart(c, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: ['#dc3545', '#f59e0b', '#8b5cf6', '#0b5ed7', '#64748b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: isRtl ? 'left' : 'right', labels: { color: ticks, boxWidth: 12, padding: 12 } },
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
    }
})();
</script>
