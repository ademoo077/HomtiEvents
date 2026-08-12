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

$withFilters = static function (string $key, string $value) use ($filters): string {
    $params = array_filter([
        'mois'       => $value,
        'du'         => $filters['du'] ?? '',
        'au'         => $filters['au'] ?? '',
        'commune_id' => (int) ($filters['commune_id'] ?? 0),
    ], static fn ($v) => $v !== '' && $v !== 0);

    return url('epic/dashboard') . ($params === [] ? '' : '?' . http_build_query($params));
};

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
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-satellite-variant me-2"></i><?= e(($epic['nom'] ?? __('common.epic'))) ?>
            </h1>
            <p class="wh-page-sub"><?= $isRtl ? 'لوحة قيادة الأحداث الموكلة ومراقبة الحالات' : 'Événements attribués, calendrier et anomalies de votre zone d\'intervention' ?></p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="<?= url('epic') ?>">
                <i class="mdi mdi-clipboard-text-outline me-1"></i><?= $isRtl ? 'التدخلات' : 'Interventions' ?>
            </a>
            <a class="btn btn-primary" href="<?= url('epic/dashboard/export') ?>">
                <i class="mdi mdi-file-delimited-outline me-1"></i><?= $isRtl ? 'تصدير CSV' : 'Exporter CSV' ?>
            </a>
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
                <div class="row g-3">
                    <?php foreach ($suggestions as $s): ?>
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
            <div class="col-md-2 col-6">
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
                            <?= $isRtl
                                ? '<span class="d-inline-block" style="min-width:130px">' . date('F Y', mktime(0, 0, 0, $moisNum, 1, $annee)) . '</span>'
                                : date('F Y', mktime(0, 0, 0, $moisNum, 1, $annee)) ?>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e($withFilters('mois', $moisPrec)) ?>" title="<?= $isRtl ? 'السابق' : 'Mois précédent' ?>">
                                <i class="mdi <?= $isRtl ? 'mdi-chevron-right' : 'mdi-chevron-left' ?>"></i>
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('epic/dashboard') ?>" title="<?= $isRtl ? 'اليوم' : 'Aujourd\'hui' ?>">
                                <?= $isRtl ? 'اليوم' : 'Aujourd\'hui' ?>
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e($withFilters('mois', $moisSuiv)) ?>" title="<?= $isRtl ? 'التالي' : 'Mois suivant' ?>">
                                <i class="mdi <?= $isRtl ? 'mdi-chevron-left' : 'mdi-chevron-right' ?>"></i>
                            </a>
                        </div>
                    </div>

                    <div class="wh-cal">
                        <div class="wh-cal-head">
                            <?php foreach ($joursSemaine as $j): ?>
                                <div class="wh-cal-head-cell"><?= e($j) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="wh-cal-grid">
                            <?php for ($i = 0; $i < $decalage; $i++): ?>
                                <div class="wh-cal-cell is-muted"></div>
                            <?php endfor; ?>

                            <?php for ($jour = 1; $jour <= $nbJours; $jour++): ?>
                                <?php
                                $date = sprintf('%04d-%02d-%02d', $annee, $moisNum, $jour);
                                $evs = $parJour[$date] ?? [];
                                $estAujourdhui = $date === $aujourdhui;
                                ?>
                                <div class="wh-cal-cell <?= $evs !== [] ? 'has-events' : '' ?> <?= $estAujourdhui ? 'is-today' : '' ?>"
                                     <?php if ($evs !== []): ?>data-events-date="<?= $date ?>" data-events-count="<?= count($evs) ?>"<?php endif; ?>>
                                    <span class="wh-cal-day"><?= $jour ?></span>
<?php if ($evs !== []): ?>
                                        <div class="wh-cal-events" title="<?= count($evs) . ' ' . ($isRtl ? 'حدث' : 'événement(s)') ?>">
                                            <?php foreach (array_slice($evs, 0, 3) as $ev): ?>
                                                <span class="wh-cal-dot" style="background:<?= $statutColor((string) $ev['statut']) ?>"></span>
                                                <?php if (! empty($ev['token_qr'])): ?>
                                                    <span class="wh-cal-qr" title="QR disponible"><i class="mdi mdi-qrcode"></i></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <?php if (count($evs) > 3): ?>
                                                <small class="wh-cal-more">+<?= count($evs) - 3 ?></small>
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
            html += '<li class="list-group-item px-0">' +
                '<div class="d-flex align-items-start gap-2">' +
                '<div class="flex-grow-1 min-w-0">' +
                '<div class="fw-semibold">' + ev.adresse + '</div>' +
                '<div class="small text-muted">' +
                (ev.association ? ev.association + ' — ' : '') + ev.commune +
                (ev.heure ? ' · ' + ev.heure.slice(0, 5) : '') +
                '</div>' +
                (ev.motif ? '<div class="small text-danger mt-1"><i class="mdi mdi-information-outline"></i> ' + ev.motif + '</div>' : '') +
                '</div>' +
                '<span class="badge badge-soft ms-1">' + ev.statut_lib + '</span>' +
                 '<a class="btn btn-sm btn-outline-primary ms-2" href="' + (ev.url_epic || ev.url_admin) + '" title="Gérer"><i class="mdi mdi-open-in-new"></i></a>' +
                '</div></li>';
        });
        html += '</ul>';
        dayBody.innerHTML = html;
    }

    document.querySelectorAll('[data-events-date]').forEach(function (cell) {
        cell.addEventListener('click', function () {
            var date = cell.getAttribute('data-events-date');
            dayTitle.textContent = isRtl ? frDate(date) : frDate(date);
            dayBody.innerHTML = '<div class="text-center text-muted py-4"><i class="mdi mdi-loading mdi-spin mdi-24px"></i></div>';
            if (modalEl) modalEl.show();
            fetch(apiUrl + '?date=' + date, { headers: { 'X-Requested-With': 'fetch' } })
                .then(function (r) { return r.json(); })
                .then(renderEvents)
                .catch(function () {
                    dayBody.innerHTML = '<div class="alert alert-danger mb-0">Erreur réseau.</div>';
                });
        });
    });

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
