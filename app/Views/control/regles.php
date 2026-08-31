<?php
/** @var array $regles */
use App\Helpers\I18n;

$title = __('common.rules');
$page  = 'control.regles';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-scale-balance me-2"></i><?= e(__('common.rules')) ?></h1>
                    <p class="wh-hero-sub"><?= $isAr ? 'محرك قواعد الأعمال — قيود وتفعيلات ديناميكية' : 'Moteur de règles métier — règles dynamiques et contrôles' ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-sm btn-light" href="<?= e(url('control/regles')) ?>" data-bs-toggle="modal" data-bs-target="#regleModal">
                        <i class="mdi mdi-plus"></i> <?= $isAr ? 'قاعدة جديدة' : 'Nouvelle règle' ?>
                    </a>
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
                            <th><?= $isAr ? 'المفتاح' : 'Clé' ?></th>
                            <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                            <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                            <th><?= $isAr ? 'الوصف' : 'Description' ?></th>
                            <th><?= $isAr ? 'الوثيقة' : 'Portée' ?></th>
                            <th class="text-center"><?= $isAr ? 'الحالة' : 'État' ?></th>
                            <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($regles as $r): ?>
                            <tr>
                                <td><?= (int) $r['id'] ?></td>
                                <td><code><?= e($r['cle'] ?? '-') ?></code></td>
                                <td><?= e($r['nom'] ?? '-') ?></td>
                                <td><?= e($r['activite'] ?? '-') ?></td>
                                <td><?= e(mb_substr((string) ($r['description'] ?? ''), 0, 50)) ?></td>
                                <td><?= e($r['portee'] ?? '-') ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="toggleRule(<?= (int) $r['id'] ?>, <?= (int) ($r['actif'] ?? 1) ? 0 : 1 ?>)">
                                        <span class="futur-chip chip-<?= (int) ($r['actif'] ?? 1) ? 'success' : 'gray' ?>">
                                            <?= (int) ($r['actif'] ?? 1) ? ($isAr ? 'نشط' : 'Active') : ($isAr ? 'معطل' : 'Inactive') ?>
                                        </span>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline-primary" href="#" onclick="editRule(<?= (int) $r['id'] ?>)">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($regles)): ?>
                            <tr><td colspan="8" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>

<script>
function toggleRule(id, actif) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', <?= json_encode(url('control/regles/toggle')) ?>);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= e(csrf_token()) ?>');
    xhr.onload = function() {
        if (xhr.status === 200) { location.reload(); }
    };
    xhr.send('id=' + id + '&actif=' + actif);
}
</script>
