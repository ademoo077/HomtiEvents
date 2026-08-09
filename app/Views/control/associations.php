<?php
/** @var array $associations */
use App\Helpers\I18n;

$title = __('common.associations');
$page  = 'control.associations';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="futur-control-header">
        <div>
            <h2 class="futur-control-title"><i class="mdi mdi-handshake"></i> <?= e(__('common.associations')) ?></h2>
            <p class="futur-control-sub"><?= $isAr ? 'إدارة الجمعيات — التفعيل، الإيقاف، الأحداث' : 'Gestion des associations — activation, suspension, événements' ?></p>
        </div>
    </div>

    <div class="futur-card">
        <div class="futur-card-body">
            <div class="table-responsive">
                <table class="futur-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                            <th><?= $isAr ? 'الرئيس' : 'Président' ?></th>
                            <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                            <th class="text-center"><?= $isAr ? 'الفعاليات' : 'Évènements' ?></th>
                            <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($associations as $a): ?>
                            <tr>
                                <td><?= (int) $a['id'] ?></td>
                                <td>
                                    <strong><?= e($a['nom'] ?? '-') ?></strong>
                                    <?php if (($a['email'] ?? '') !== ''): ?>
                                        <div class="text-muted small"><?= e($a['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($a['president'] ?? '-') ?></td>
                                <td>
                                    <span class="futur-chip chip-<?= (int) ($a['valide'] ?? 0) ? 'success' : 'danger' ?>">
                                        <?= (int) ($a['valide'] ?? 0) ? ($isAr ? 'نشط' : 'Active') : ($isAr ? 'معلقة' : 'Suspendue') ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= (int) ($a['nb_evenements'] ?? 0) ?></td>
                                <td class="text-center">
                                    <?php if ((int) ($a['valide'] ?? 0)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                onclick="associationAction(<?= (int) $a['id'] ?>, 'suspendre')">
                                            <i class="mdi mdi-pause-circle"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-success"
                                                onclick="associationAction(<?= (int) $a['id'] ?>, 'restaurer')">
                                            <i class="mdi mdi-play-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($associations)): ?>
                            <tr><td colspan="6" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function associationAction(id, action) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', <?= json_encode(url('control/associations/action')) ?>);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= e(csrf_token()) ?>');
    xhr.onload = function() {
        if (xhr.status === 200) { location.reload(); }
    };
    xhr.send('id=' + id + '&action=' + action);
}
</script>
