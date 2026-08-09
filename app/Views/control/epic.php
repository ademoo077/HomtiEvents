<?php
/** @var array $epics @var array $interventions */
use App\Helpers\I18n;

$title = __('common.epic');
$page  = 'control.epic';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="futur-control-header">
        <div>
            <h2 class="futur-control-title"><i class="mdi mdi-satellite-variant"></i> <?= e(__('common.epic')) ?></h2>
            <p class="futur-control-sub"><?= $isAr ? 'مراجعة مدخلات EPIC — التعيين، التوثيق، المراقبة' : 'Revue des interventions EPIC — affectation, validation, supervision' ?></p>
        </div>
    </div>

    <!-- EPIC List -->
    <div class="futur-card mb-4">
        <div class="futur-card-header">
            <span><i class="mdi mdi-satellite"></i> <?= $isAr ? 'قائمة EPIC' : 'EPIC enregistrés' ?></span>
        </div>
        <div class="futur-card-body">
            <div class="table-responsive">
                <table class="futur-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                            <th><?= $isAr ? 'الوصف' : 'Description' ?></th>
                            <th class="text-center"><?= $isAr ? 'التدخلات' : 'Interventions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($epics as $e): ?>
                            <tr>
                                <td><?= (int) $e['id'] ?></td>
                                <td><strong><?= e($e['nom'] ?? '-') ?></strong></td>
                                <td><?= e(mb_substr((string) ($e['description'] ?? ''), 0, 60)) ?></td>
                                <td class="text-center">
                                    <?php
                                    $count = 0;
                                    foreach ($interventions as $iv) {
                                        if (($iv['epic_id'] ?? null) === $e['id']) $count++;
                                    }
                                    echo (int) $count;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($epics)): ?>
                            <tr><td colspan="4" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Interventions -->
    <div class="futur-card">
        <div class="futur-card-header">
            <span><i class="mdi mdi-clipboard-check-outline"></i> <?= $isAr ? 'التدخلات الأخيرة' : 'Dernières interventions' ?></span>
        </div>
        <div class="futur-card-body">
            <div class="table-responsive">
                <table class="futur-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $isAr ? 'الحدث' : 'Événement' ?></th>
                            <th><?= $isAr ? 'EPIC' : 'EPIC' ?></th>
                            <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                            <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                            <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interventions as $iv): ?>
                            <tr>
                                <td><?= (int) $iv['id'] ?></td>
                                <td><?= e($iv['evenement_adresse'] ?? '-') ?></td>
                                <td><?= e($iv['epic_nom'] ?? '-') ?></td>
                                <td>
                                    <span class="futur-chip chip-<?= match ($iv['statut'] ?? 'AFFECTE') {
                                        'AFFECTE' => 'info',
                                        'EN_COURS' => 'warning',
                                        'TERMINE' => 'success',
                                        'ANOMALIE' => 'danger',
                                        default => 'gray',
                                    } ?>">
                                        <?= e($iv['statut'] ?? 'AFFECTE') ?>
                                    </span>
                                </td>
                                <td><?= e($iv['date_affectation'] ?? '-') ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="validateEpic(<?= (int) $iv['id'] ?>, 'EN_COURS')">
                                        <i class="mdi mdi-progress-wrench"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($interventions)): ?>
                            <tr><td colspan="6" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function validateEpic(id, statut) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', <?= json_encode(url('control/epic/validate')) ?>);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= e(csrf_token()) ?>');
    xhr.onload = function() {
        if (xhr.status === 200) { location.reload(); }
    };
    xhr.send('id=' + id + '&statut=' + statut);
}
</script>
