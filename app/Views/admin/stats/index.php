<?php
/**
 * Statistiques de la plateforme (panel Wilaya).
 *
 * @var array $stats
 */
use App\Helpers\I18n;

$title = __('common.statistiques');
$page  = 'admin.stats';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$k   = $stats['kpis'];
$demandeLabels = ['pending' => 'En attente', 'approved' => 'Approuvée', 'rejected' => 'Refusée', 'modification_requested' => 'En attente de modifications'];
?>
<style>
.wh-stats-section{background:var(--wh-white);border:1px solid var(--wh-border);border-radius:var(--wh-radius);border-left:4px solid var(--wh-blue);padding:1rem 1.25rem;margin-bottom:1rem}
.wh-stats-section h6{margin:0}
</style>

<div class="wh-page">
    <div class="wh-hero-panel mb-4" style="--hero-a:#0B5ED7;--hero-b:#198754">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-chart-box"></i>
                    <?= e(__('common.statistiques')) ?>
                </h1>
                <p class="mt-1 mb-0"><?= $isAr ? 'منصة ولاية الجزائر' : "Vue d'ensemble de l'activité de la plateforme" ?></p>
            </div>
            <a class="btn btn-light" href="<?= url('admin/stats/export') ?>">
                <i class="mdi mdi-file-delimited-outline me-1"></i><?= $isAr ? 'تصدير CSV' : 'Exporter CSV' ?>
            </a>
        </div>
    </div>

    <?php
    $kpis = [
        ['val' => (int) $k['evenements'],        'label' => 'Événements',               'icon' => 'mdi-calendar-star',        'teinte' => 'blue'],
        ['val' => (int) $k['participants'],      'label' => 'Participations',           'icon' => 'mdi-account-check',        'teinte' => 'green'],
        ['val' => (int) $k['citoyens'],          'label' => 'Citoyens inscrits',        'icon' => 'mdi-account-group',        'teinte' => 'gray'],
        ['val' => (int) $k['associations'],      'label' => 'Associations',             'icon' => 'mdi-handshake',            'teinte' => 'amber'],
        ['val' => (int) $k['demandes'],          'label' => __('associations.inscription_request'),     'icon' => 'mdi-account-plus-outline', 'teinte' => 'blue'],
        ['val' => (int) $k['epics'],             'label' => 'EPIC',                     'icon' => 'mdi-satellite-variant',    'teinte' => 'purple'],
        ['val' => (int) $k['photos'],            'label' => 'Photos',                   'icon' => 'mdi-image-multiple',       'teinte' => 'green'],
        ['val' => $k['note_moyenne'] ?? '—',     'label' => 'Note moyenne',             'icon' => 'mdi-star-outline',         'teinte' => 'amber'],
    ];
    ?>
    <div class="row g-3 mb-4">
        <?php foreach ($kpis as $kpi): ?>
            <div class="col-md-3 col-6">
                <div class="wh-kpi wh-kpi-hover">
                    <div class="wh-kpi-icon <?= e($kpi['teinte']) ?>"><i class="mdi <?= e($kpi['icon']) ?>"></i></div>
                    <div>
                        <div class="wh-kpi-value"><?= e((string) $kpi['val']) ?></div>
                        <div class="wh-kpi-label"><?= e($kpi['label']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:2px solid var(--wh-blue)"><h6 class="mb-0 fw-bold" style="color:var(--wh-blue)"><i class="mdi mdi-home-city-outline me-2"></i><?= $isAr ? 'توزيع حسب البلدية' : 'Répartition par commune' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartCommunes" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:var(--wh-red-soft);border-bottom:2px solid #dc3545"><h6 class="mb-0 fw-bold" style="color:#dc3545"><i class="mdi mdi-alert-octagon-outline me-2"></i><?= $isAr ? 'توزيع حسب الإعاقة' : 'Répartition par anomalie' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartAnomalies" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:var(--wh-green-soft);border-bottom:2px solid var(--wh-green)"><h6 class="mb-0 fw-bold" style="color:var(--wh-green)"><i class="mdi mdi-chart-line me-2"></i><?= $isAr ? 'التطور الشهري' : 'Évolution mensuelle (6 mois)' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartMois" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:var(--wh-amber-soft);border-bottom:2px solid #F59E0B"><h6 class="mb-0 fw-bold" style="color:#F59E0B"><i class="mdi mdi-calendar-check me-2"></i><?= $isAr ? 'الفعاليات حسب الحالة' : 'Événements par statut' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartStatuts" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:rgba(8,145,178,.08);border-bottom:2px solid #0891b2"><h6 class="mb-0 fw-bold" style="color:#0891b2"><i class="mdi mdi-check-decagram me-2"></i><?= $isAr ? 'المشاركات اليومية' : 'Participations par jour (14 jours)' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartJours" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:rgba(109,40,217,.08);border-bottom:2px solid #6d28d9"><h6 class="mb-0 fw-bold" style="color:#6d28d9"><i class="mdi mdi-trophy-outline me-2"></i><?= $isAr ? 'أكثر الجمعيات نشاطًا' : 'Top associations' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartAssociations" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:2px solid var(--wh-blue)"><h6 class="mb-0 fw-bold" style="color:var(--wh-blue)"><i class="mdi mdi-account-plus-outline me-2"></i><?= $isAr ? 'طلبات التسجيل' : "Demandes d'inscription" ?></h6></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="wh-kpi wh-kpi-hover flex-fill">
                            <div class="wh-kpi-icon blue"><i class="mdi mdi-account-plus-outline"></i></div>
                            <div>
                                <div class="wh-kpi-value"><?= (int) $k['demandes'] ?></div>
                                <div class="wh-kpi-label"><?= $isAr ? 'المجموع' : 'Total' ?></div>
                            </div>
                        </div>
                        <div class="flex-fill">
                            <div class="text-muted small mb-2"><?= $isAr ? 'معدل المشاركة' : 'Taux de participation' ?></div>
                            <div class="progress" style="height:10px;border-radius:10px">
                                <div class="progress-bar" role="progressbar" style="width:<?= min(100, (float) $stats['tauxParticipation']) ?>%;background:linear-gradient(90deg,var(--wh-blue),var(--wh-green))"></div>
                            </div>
                            <small class="text-muted"><?= e((string) $stats['tauxParticipation']) ?>%</small>
                        </div>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tbody>
                            <?php foreach (['pending' => 'En attente', 'approved' => 'Approuvées', 'rejected' => 'Refusées', 'modification_requested' => 'En attente de modifications'] as $st => $lib): ?>
                                <tr>
                                    <td class="text-muted"><?= e($lib) ?></td>
                                    <td class="text-end fw-bold"><?= (int) ($k['demandes_' . $st] ?? 0) ?></td>
                                    <td class="text-end" style="width:40%">
                                        <div class="progress" style="height:6px;border-radius:6px">
                                            <div class="progress-bar" role="progressbar" style="width:<?= (int) $k['demandes'] > 0 ? round(((int) ($k['demandes_' . $st] ?? 0) / (int) $k['demandes']) * 100) : 0 ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php $scan = $stats['scanStats']; ?>
    <h5 class="fw-bold mt-5 mb-3">
        <i class="mdi mdi-qrcode-scan me-2"></i><?= $isAr ? 'إحصائيات المسح (QR)' : 'Statistiques de scan (QR)' ?>
    </h5>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover"><div class="wh-kpi-icon green"><i class="mdi mdi-qrcode-scan"></i></div><div><div class="wh-kpi-value"><?= (int) $scan['total_scans'] ?></div><div class="wh-kpi-label"><?= $isAr ? 'مجموع المسحات' : 'Scans (total)' ?></div></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover"><div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-today"></i></div><div><div class="wh-kpi-value"><?= (int) $scan['scans_aujourdhui'] ?></div><div class="wh-kpi-label"><?= $isAr ? 'مسحات اليوم' : "Scans aujourd'hui" ?></div></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover"><div class="wh-kpi-icon amber"><i class="mdi mdi-chart-areaspline"></i></div><div><div class="wh-kpi-value"><?= e((string) $scan['moyenne_jour']) ?></div><div class="wh-kpi-label"><?= $isAr ? 'متوسط اليوم' : 'Moyenne / jour (30 j)' ?></div></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi wh-kpi-hover"><div class="wh-kpi-icon gray"><i class="mdi mdi-ticket-percent-outline"></i></div><div><div class="wh-kpi-value"><?= count($scan['taux_par_evenement']) ?></div><div class="wh-kpi-label"><?= $isAr ? 'فعاليات بسعة محددة' : 'Événements à capacité' ?></div></div></div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:var(--wh-green-soft);border-bottom:2px solid var(--wh-green)"><h6 class="mb-0 fw-bold" style="color:var(--wh-green)"><i class="mdi mdi-clock-outline me-2"></i><?= $isAr ? 'ساعات الذروة (30 يومًا)' : 'Heures de pointe (30 jours)' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartScanHeures" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:rgba(109,40,217,.08);border-bottom:2px solid #6d28d9"><h6 class="mb-0 fw-bold" style="color:#6d28d9"><i class="mdi mdi-map-marker-multiple-outline me-2"></i><?= $isAr ? 'المسحات حسب البلدية' : 'Scans par commune' ?></h6></div>
                <div class="card-body">
                    <div class="wh-chart"><canvas id="chartScanCommunes" style="height:240px"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background:var(--wh-amber-soft);border-bottom:2px solid #F59E0B"><h6 class="mb-0 fw-bold" style="color:#F59E0B"><i class="mdi mdi-ticket-percent-outline me-2"></i><?= $isAr ? 'نسبة الامتلاء (فعاليات ذات سعة)' : 'Taux de remplissage (événements à capacité)' ?></h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= $isAr ? 'الفعالية' : 'Événement' ?></th>
                                    <th class="text-end"><?= $isAr ? 'مشاركون' : 'Participants' ?></th>
                                    <th class="text-end"><?= $isAr ? 'السعة' : 'Capacité' ?></th>
                                    <th style="width:30%"><?= $isAr ? 'النسبة' : 'Taux' ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($scan['taux_par_evenement'] === []): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3"><?= $isAr ? 'لا توجد بيانات' : 'Aucune donnée' ?></td></tr>
                                <?php endif; ?>
                                <?php foreach ($scan['taux_par_evenement'] as $r): ?>
                                    <tr>
                                        <td class="text-truncate" style="max-width:260px"><?= e(substr((string) $r['adresse'], 0, 50)) ?></td>
                                        <td class="text-end"><?= (int) $r['participants'] ?></td>
                                        <td class="text-end"><?= (int) $r['capacite'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:8px;border-radius:8px">
                                                    <div class="progress-bar" role="progressbar" style="width:<?= min(100, (int) $r['taux']) ?>%;background:<?= (int) $r['taux'] >= 100 ? '#dc3545' : ((int) $r['taux'] >= 80 ? '#F59E0B' : '#198754') ?>"></div>
                                                </div>
                                                <small class="text-muted"><?= (int) $r['taux'] ?>%</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.04)';
    var ticks = dark ? '#9aa3b2' : '#697586';
    var legend = { labels: { color: ticks, boxWidth: 12, padding: 12 } };
    var tooltip = {
        backgroundColor: dark ? '#1a2332' : '#fff',
        titleColor: dark ? '#e6ebf2' : '#212b36',
        bodyColor: dark ? '#b8c7dc' : '#697586',
        borderColor: dark ? '#2b3648' : '#dee2e6',
        borderWidth: 1, cornerRadius: 8, padding: 12, displayColors: false
    };
    var font = { weight: '500' };

    function baseOptions(extra) {
        return Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: legend, tooltip: tooltip },
            scales: {
                x: { grid: { display: false }, ticks: { color: ticks, font: font } },
                y: { beginAtZero: true, grid: { color: grid }, ticks: { color: ticks, font: font, precision: 0 } }
            }
        }, extra || {});
    }

    var c = document.getElementById('chartStatuts');
    if (c && typeof Chart !== 'undefined') {
        var labels = [], counts = [];
        <?php foreach ($stats['parStatut'] as $ps): ?>
            labels.push(<?= json_encode(statut_label((string) $ps['statut']), JSON_UNESCAPED_UNICODE) ?>);
            counts.push(<?= (int) $ps['nb'] ?>);
        <?php endforeach; ?>
        new Chart(c, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: ['#FBBF24', '#F59E0B', '#0B5ED7', '#22d3ee', '#8B5CF6', '#198754', '#dc3545', '#64748b'],
                    borderRadius: 8, maxBarThickness: 44, borderSkipped: false
                }]
            },
            options: baseOptions({ plugins: Object.assign({}, { legend: { display: false }, tooltip: tooltip }) })
        });
    }

    var cm = document.getElementById('chartMois');
    if (cm && typeof Chart !== 'undefined') {
        var mois = <?= json_encode(array_column($stats['evolutionMensuelle'], 'mois')) ?>;
        var ev = <?= json_encode(array_map('intval', array_column($stats['evolutionMensuelle'], 'evenements'))) ?>;
        var part = <?= json_encode(array_map('intval', array_column($stats['evolutionMensuelle'], 'participants'))) ?>;
        new Chart(cm, {
            type: 'line',
            data: {
                labels: mois,
                datasets: [
                    { label: 'Événements', data: ev, borderColor: '#0B5ED7', backgroundColor: 'rgba(11,94,215,.10)', fill: true, tension: .35, pointRadius: 4 },
                    { label: 'Participations', data: part, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.10)', fill: true, tension: .35, pointRadius: 4 }
                ]
            },
            options: baseOptions({ plugins: { legend: legend, tooltip: tooltip } })
        });
    }

    var cj = document.getElementById('chartJours');
    if (cj && typeof Chart !== 'undefined') {
        var jours = <?= json_encode(array_column($stats['participationsJour'], 'jour')) ?>;
        var nbs = <?= json_encode(array_map('intval', array_column($stats['participationsJour'], 'nb'))) ?>;
        new Chart(cj, {
            type: 'bar',
            data: {
                labels: jours,
                datasets: [{ data: nbs, backgroundColor: '#198754', borderRadius: 6, maxBarThickness: 28 }]
            },
            options: baseOptions({ plugins: Object.assign({}, { legend: { display: false }, tooltip: tooltip }) })
        });
    }

    var ccom = document.getElementById('chartCommunes');
    if (ccom && typeof Chart !== 'undefined') {
        new Chart(ccom, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stats['repartitionCommunes'], 'nom'), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('intval', array_column($stats['repartitionCommunes'], 'nb'))) ?>,
                    backgroundColor: ['#0B5ED7', '#198754', '#F59E0B', '#8B5CF6', '#22d3ee', '#dc3545', '#64748b', '#FBBF24'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'right', labels: legend.labels }, tooltip: tooltip }
            }
        });
    }

    var cano = document.getElementById('chartAnomalies');
    if (cano && typeof Chart !== 'undefined') {
        new Chart(cano, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stats['repartitionAnomalies'], 'nom'), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('intval', array_column($stats['repartitionAnomalies'], 'nb'))) ?>,
                    backgroundColor: ['#dc3545', '#F59E0B', '#8B5CF6', '#22d3ee', '#198754', '#64748b', '#0B5ED7', '#FBBF24'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'right', labels: legend.labels }, tooltip: tooltip }
            }
        });
    }

    var ca = document.getElementById('chartAssociations');
    if (ca && typeof Chart !== 'undefined') {
        var noms = <?= json_encode(array_column($stats['topAssociations'], 'nom'), JSON_UNESCAPED_UNICODE) ?>;
        var nbAssos = <?= json_encode(array_map('intval', array_column($stats['topAssociations'], 'nb'))) ?>;
        new Chart(ca, {
            type: 'bar',
            data: {
                labels: noms,
                datasets: [{ data: nbAssos, backgroundColor: '#0B5ED7', borderRadius: 6, maxBarThickness: 26 }]
            },
            options: baseOptions({
                indexAxis: 'y',
                plugins: Object.assign({}, { legend: { display: false }, tooltip: tooltip })
            })
        });
    }

    var csh = document.getElementById('chartScanHeures');
    if (csh && typeof Chart !== 'undefined') {
        var heures = <?= json_encode(array_column($scan['scans_par_heure'], 'heure')) ?>;
        var nbHeures = <?= json_encode(array_map('intval', array_column($scan['scans_par_heure'], 'nb'))) ?>;
        var maxH = Math.max.apply(null, nbHeures);
        new Chart(csh, {
            type: 'bar',
            data: {
                labels: heures,
                datasets: [{
                    data: nbHeures,
                    backgroundColor: nbHeures.map(function (v) { return v === maxH ? '#D4AF37' : '#198754'; }),
                    borderRadius: 4,
                    maxBarThickness: 16
                }]
            },
            options: baseOptions({ plugins: Object.assign({}, { legend: { display: false }, tooltip: tooltip }) })
        });
    }

    var csc = document.getElementById('chartScanCommunes');
    if (csc && typeof Chart !== 'undefined') {
        new Chart(csc, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($scan['scans_par_commune'], 'nom'), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('intval', array_column($scan['scans_par_commune'], 'nb'))) ?>,
                    backgroundColor: ['#0B5ED7', '#198754', '#F59E0B', '#8B5CF6', '#22d3ee', '#dc3545', '#64748b', '#FBBF24'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'right', labels: legend.labels }, tooltip: tooltip }
            }
        });
    }
})();
</script>