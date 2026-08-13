<?php
/** @var array $modules @var array $regles @var array $securite @var array $statistiques */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<!-- KPIs -->
<div class="futur-grid mb-4">
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= (int) $statistiques['utilisateurs'] ?></div>
        <div class="futur-kpi-label"><?= e(__('common.users')) ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= (int) $statistiques['suspendus'] ?></div>
        <div class="futur-kpi-label"><?= e(__('control.suspendus')) ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= (int) $statistiques['associations'] ?></div>
        <div class="futur-kpi-label"><?= e(__('common.associations')) ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= (int) $statistiques['evenements'] ?></div>
        <div class="futur-kpi-label"><?= e(__('common.evenements')) ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= (int) $statistiques['audit'] ?></div>
        <div class="futur-kpi-label"><?= e(__('common.audit')) ?></div>
    </div>
</div>

<div class="futur-grid mb-4">
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= count($modules) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'الوحدات' : 'Modules' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= count($regles) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'قواعد العمل' : 'Règles métier' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= (int) Database::value('SELECT COUNT(*) FROM commune') ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'البلديات' : 'Communes' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= (int) Database::value('SELECT COUNT(*) FROM epic') ?></div>
        <div class="futur-kpi-label">EPICs</div>
    </div>
</div>

<!-- Modules -->
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
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($modules)): ?>
                        <tr><td colspan="3" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Business Rules -->
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
                    <?php endforeach; ?>
                    <?php if (empty($regles)): ?>
                        <tr><td colspan="4" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Security Events -->
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

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