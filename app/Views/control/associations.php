<?php
/** @var array $associations */
use App\Helpers\I18n;

$title = __('common.associations');
$page  = 'control.associations';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-handshake me-2"></i><?= e(__('common.associations')) ?></h1>
                    <p class="wh-hero-sub"><?= $isAr ? 'إدارة الجمعيات — التفعيل، الإيقاف، الأحداث' : 'Gestion des associations — activation, suspension, événements' ?></p>
                </div>
            </div>
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
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
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
