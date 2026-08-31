<?php
/** @var array $modules @var array $regles @var array $securite @var array $statistiques
 *  @var array $tendances @var array $chartData @var array $actionsRequises */
use App\Helpers\Database;
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';

$kpiConfig = [
    [
        'key'   => 'utilisateurs',
        'label' => __('common.users'),
        'icon'  => 'mdi-account-multiple',
        'color' => 'blue',
        'link'  => url('control?tab=users'),
        'invertTrend' => false,
    ],
    [
        'key'   => 'suspendus',
        'label' => __('control.suspendus'),
        'icon'  => 'mdi-account-off',
        'color' => 'red',
        'link'  => url('control?tab=users'),
        'invertTrend' => true,
    ],
    [
        'key'   => 'associations',
        'label' => __('common.associations'),
        'icon'  => 'mdi-account-group',
        'color' => 'green',
        'link'  => url('control?tab=associations'),
        'invertTrend' => false,
    ],
    [
        'key'   => 'evenements',
        'label' => __('common.evenements'),
        'icon'  => 'mdi-calendar-star',
        'color' => 'amber',
        'link'  => url('control?tab=dashboard'),
        'invertTrend' => false,
    ],
    [
        'key'   => 'audit',
        'label' => __('common.audit'),
        'icon'  => 'mdi-file-document-outline',
        'color' => 'gray',
        'link'  => url('control?tab=audit'),
        'invertTrend' => false,
    ],
];

$sysKpiConfig = [
    [
        'value' => count($modules),
        'label' => $isAr ? 'الوحدات' : 'Modules',
        'icon'  => 'mdi-cog-outline',
        'color' => 'blue',
    ],
    [
        'value' => count($regles),
        'label' => $isAr ? 'قواعد العمل' : 'Règles métier',
        'icon'  => 'mdi-scale-balance',
        'color' => 'green',
    ],
    [
        'value' => (int) Database::value('SELECT COUNT(*) FROM commune'),
        'label' => $isAr ? 'البلديات' : 'Communes',
        'icon'  => 'mdi-city',
        'color' => 'amber',
    ],
    [
        'value' => (int) Database::value('SELECT COUNT(*) FROM epic'),
        'label' => 'EPICs',
        'icon'  => 'mdi-satellite-variant',
        'color' => 'purple',
    ],
];

function trendPct(array $kpi, array $tendances, bool $invert): string {
    $t = $tendances[$kpi['key']] ?? null;
    if (!$t || $t['previous'] == 0) return '';
    $pct = $t['pct'];
    if ($invert) $pct = -$pct;
    $sign = $pct > 0 ? '+' : '';
    return $sign . $pct . '%';
}

function trendClass(array $kpi, array $tendances, bool $invert): string {
    $t = $tendances[$kpi['key']] ?? null;
    if (!$t || $t['previous'] == 0) return 'trend-flat';
    $dir = $t['direction'];
    if ($invert) {
        $dir = $dir === 'up' ? 'down' : ($dir === 'down' ? 'up' : 'flat');
    }
    return 'trend-' . $dir;
}
?>

<div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-view-dashboard-outline me-2"></i><?= $isAr ? 'لوحة القيادة' : 'Tableau de bord' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'نظرة عامة على المنصة' : 'Vue d\'ensemble de la plateforme' ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($actionsRequises)): ?>
<div class="futur-actions-bar mb-4">
    <div class="futur-actions-bar-title">
        <i class="mdi mdi-alert-circle-outline"></i>
        <?= $isAr ? 'إجراءات مطلوبة' : 'Actions requises' ?>
    </div>
    <div class="futur-actions-bar-items">
        <?php foreach ($actionsRequises as $a): ?>
            <a href="<?= $a['link'] ?>" class="futur-action-chip futur-action-chip--<?= e($a['color']) ?>">
                <i class="mdi <?= e($a['icon']) ?>"></i>
                <span class="futur-action-chip-count"><?= (int) $a['count'] ?></span>
                <span class="futur-action-chip-label"><?= e($a['label']) ?></span>
                <i class="mdi mdi-chevron-right futur-action-chip-arrow"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="futur-grid mb-4">
    <?php foreach ($kpiConfig as $kpi):
        $t = $tendances[$kpi['key']] ?? ['current' => 0, 'pct' => 0, 'direction' => 'flat'];
        $pctText = trendPct($kpi, $tendances, $kpi['invertTrend']);
        $pctClass = trendClass($kpi, $tendances, $kpi['invertTrend']);
    ?>
        <a href="<?= $kpi['link'] ?>" class="futur-kpi futur-kpi--linked">
            <div class="futur-kpi-head">
                <div class="futur-kpi-icon <?= e($kpi['color']) ?>">
                    <i class="mdi <?= e($kpi['icon']) ?>"></i>
                </div>
                <?php if ($pctText !== ''): ?>
                    <span class="futur-kpi-trend <?= $pctClass ?>">
                        <?php if ($t['direction'] === 'up'): ?>
                            <i class="mdi mdi-trending-up"></i>
                        <?php elseif ($t['direction'] === 'down'): ?>
                            <i class="mdi mdi-trending-down"></i>
                        <?php else: ?>
                            <i class="mdi mdi-minus"></i>
                        <?php endif ?>
                        <?= $pctText ?>
                    </span>
                <?php endif ?>
            </div>
            <div class="futur-kpi-value"><?= (int) $statistiques[$kpi['key']] ?></div>
            <div class="futur-kpi-label"><?= e($kpi['label']) ?></div>
        </a>
    <?php endforeach ?>
</div>

<div class="futur-grid futur-grid--4 mb-4">
    <?php foreach ($sysKpiConfig as $kpi): ?>
        <div class="futur-kpi">
            <div class="futur-kpi-head">
                <div class="futur-kpi-icon <?= e($kpi['color']) ?>">
                    <i class="mdi <?= e($kpi['icon']) ?>"></i>
                </div>
            </div>
            <div class="futur-kpi-value"><?= $kpi['value'] ?></div>
            <div class="futur-kpi-label"><?= e($kpi['label']) ?></div>
        </div>
    <?php endforeach ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="futur-card h-100">
            <div class="futur-card-header">
                <span><i class="mdi mdi-chart-line"></i> <?= $isAr ? 'التطور الشهري' : 'Évolution mensuelle' ?></span>
            </div>
            <div class="futur-card-body">
                <div class="futur-chart"><canvas id="ccChartEvolution"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="futur-card h-100">
            <div class="futur-card-header">
                <span><i class="mdi mdi-chart-donut"></i> <?= $isAr ? 'الأحداث حسب الحالة' : 'Événements par statut' ?></span>
            </div>
            <div class="futur-card-body">
                <div class="futur-chart"><canvas id="ccChartStatuts"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="futur-card mb-4">
    <div class="futur-card-header">
        <span><i class="mdi mdi-cog-outline"></i> <?= e(__('control.modules')) ?></span>
    </div>
    <div class="futur-card-body">
        <div class="table-responsive">
            <table class="futur-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'الوحدة' : 'Module' ?></th>
                        <th><?= $isAr ? 'الوصف' : 'Description' ?></th>
                        <th class="text-center"><?= $isAr ? 'الحالة' : 'État' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $m): ?>
                        <tr>
                            <td><strong><?= e($m['nom']) ?></strong></td>
                            <td><?= e($m['description'] ?? '') ?></td>
                            <td class="text-center">
                                <span class="futur-chip chip-<?= (int) $m['actif'] ? 'success' : 'gray' ?>">
                                    <?= (int) $m['actif'] ? ($isAr ? 'نشط' : 'Actif') : ($isAr ? 'معطل' : 'Inactif') ?>
                                </span>
                                <?php if ((int) ($m['verrouille'] ?? 0) === 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2"
                                            onclick="toggleModule('<?= e($m['cle']) ?>', this)">
                                        <?= (int) $m['actif'] ? ($isAr ? 'إيقاف' : 'Désactiver') : ($isAr ? 'تفعيل' : 'Activer') ?>
                                    </button>
                                <?php else: ?>
                                    <span class="futur-chip chip-gray"><?= $isAr ? 'مقفل' : 'Verrouillé' ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($modules)): ?>
                        <tr><td colspan="3" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="futur-card mb-4">
    <div class="futur-card-header">
        <span><i class="mdi mdi-scale-balance"></i> <?= e(__('common.rules')) ?></span>
    </div>
    <div class="futur-card-body">
        <div class="table-responsive">
            <table class="futur-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'القاعدة' : 'Règle' ?></th>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'الوصف' : 'Description' ?></th>
                        <th class="text-center"><?= $isAr ? 'الحالة' : 'État' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regles as $r): ?>
                        <tr>
                            <td><strong><?= e($r['cle']) ?></strong></td>
                            <td><?= e($r['activite']) ?></td>
                            <td><?= e($r['description'] ?? '') ?></td>
                            <td class="text-center">
                                <span class="futur-chip chip-<?= (int) $r['actif'] ? 'success' : 'gray' ?>">
                                    <?= (int) $r['actif'] ? ($isAr ? 'نشط' : 'Active') : ($isAr ? 'معطل' : 'Inactive') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($regles)): ?>
                        <tr><td colspan="4" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (! empty($securite['sessions'])): ?>
<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-shield-check-outline"></i> <?= e(__('control.securite')) ?></span>
    </div>
    <div class="futur-card-body">
        <div class="table-responsive">
            <table class="futur-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'المستخدم' : 'Utilisateur' ?></th>
                        <th><?= $isAr ? 'الرسالة' : 'Message' ?></th>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($securite['sessions'], 0, 10) as $s): ?>
                        <tr>
                            <td><?= e($s['type'] ?? '-') ?></td>
                            <td><?= e($s['user_id'] ?? '-') ?></td>
                            <td><?= e(mb_substr((string) ($s['message'] ?? ''), 0, 60)) ?></td>
                            <td><?= e($s['created_at'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif ?>

<style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
</style>

<script src="<?= asset('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.04)';
    var ticks = dark ? '#9aa3b2' : '#697586';
    var legend = { labels: { color: ticks, boxWidth: 12, padding: 14, usePointStyle: true } };
    var tooltip = {
        backgroundColor: dark ? '#1a2332' : '#fff',
        titleColor: dark ? '#e6ebf2' : '#212b36',
        bodyColor: dark ? '#b8c7dc' : '#697586',
        borderColor: dark ? '#2b3648' : '#dee2e6',
        borderWidth: 1, cornerRadius: 8, padding: 12, displayColors: true
    };
    var font = { weight: '500' };

    var ce = document.getElementById('ccChartEvolution');
    if (ce) {
        var mois = <?= json_encode(array_column($chartData['evolution'], 'mois')) ?>;
        var ev = <?= json_encode(array_map('intval', array_column($chartData['evolution'], 'evenements'))) ?>;
        new Chart(ce, {
            type: 'line',
            data: {
                labels: mois,
                datasets: [{
                    label: <?= json_encode($isAr ? 'الأحداث' : 'Événements') ?>,
                    data: ev,
                    borderColor: '#0B5ED7',
                    backgroundColor: 'rgba(11,94,215,.10)',
                    fill: true,
                    tension: .35,
                    pointRadius: 4,
                    pointBackgroundColor: '#0B5ED7',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: tooltip },
                scales: {
                    x: { grid: { display: false }, ticks: { color: ticks, font: font } },
                    y: { beginAtZero: true, grid: { color: grid }, ticks: { color: ticks, font: font, precision: 0 } }
                }
            }
        });
    }

    var cs = document.getElementById('ccChartStatuts');
    if (cs) {
        var statutLabels = <?= json_encode(array_map(fn($s) => statut_label((string) $s['statut']), $chartData['parStatut']), JSON_UNESCAPED_UNICODE) ?>;
        var statutCounts = <?= json_encode(array_map('intval', array_column($chartData['parStatut'], 'nb'))) ?>;
        new Chart(cs, {
            type: 'doughnut',
            data: {
                labels: statutLabels,
                datasets: [{
                    data: statutCounts,
                    backgroundColor: ['#FBBF24', '#F59E0B', '#0B5ED7', '#22d3ee', '#8B5CF6', '#198754', '#dc3545', '#64748b'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: { legend: { position: 'bottom', labels: legend.labels }, tooltip: tooltip }
            }
        });
    }
})();
</script>

<script>
function toggleModule(cle, btn) {
    var actif = btn.textContent.toLowerCase().includes('<?= $isAr ? "تفعيل" : "Activer" ?>') ? 1 : 0;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', <?= json_encode(url('control/modules/toggle')) ?>);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= e(csrf_token()) ?>');
    xhr.onload = function() {
        if (xhr.status === 200) { location.reload(); }
    };
    xhr.send('cle=' + encodeURIComponent(cle) + '&actif=' + actif);
}
</script>
