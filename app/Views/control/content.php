<?php
/** @var array $items @var array $statuts @var array $modeles @var string $currentStatut @var string $currentModele */
use App\Helpers\I18n;

$title = __('common.content');
$page  = 'control.content';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-file-document-check me-2"></i><?= e(__('common.content')) ?></h1>
                    <p class="wh-hero-sub"><?= $isAr ? 'مراجعة محتوى العلنة — موافقة، رفض، نشر' : 'Validation des contenus publics — approbation, rejet, publication' ?></p>
                </div>
            </div>
        </div>
    </div>

    <form method="get" action="<?= e(url('control/content')) ?>" class="futur-row mb-3">
        <div class="futur-col">
            <select name="statut" class="form-select">
                <option value="tous"><?= $isAr ? 'جميع الحالات' : 'Tous les statuts' ?></option>
                <?php foreach ($statuts as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($currentStatut ?? '') === $s ? 'selected' : '' ?>>
                        <?= e(__('evenements.statut_' . statut_key($s)) ?? $s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="futur-col">
            <select name="modele" class="form-select">
                <option value=""><?= $isAr ? 'جميع النماذج' : 'Tous les modèles' ?></option>
                <?php foreach ($modeles as $m): ?>
                    <option value="<?= e($m) ?>" <?= ($currentModele ?? '') === $m ? 'selected' : '' ?>>
                        <?= e(ucfirst($m)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="futur-col">
            <button type="submit" class="futur-btn futur-btn-sm futur-btn-outline">
                <i class="mdi mdi-filter-variant"></i> <?= e(__('common.filters')) ?>
            </button>
        </div>
    </form>

    <div class="futur-card">
        <div class="futur-card-body">
            <div class="table-responsive">
                <table class="futur-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $isAr ? 'النموذج' : 'Modèle' ?></th>
                            <th><?= $isAr ? 'العنوان' : 'Titre' ?></th>
                            <th><?= $isAr ? 'المقترح من' : 'Proposé par' ?></th>
                            <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                            <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                            <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i): ?>
                            <tr>
                                <td><?= (int) $i['id'] ?></td>
                                <td><?= e(ucfirst($i['modele'] ?? '-')) ?></td>
                                <td><?= e($i['modele_nom'] ?? $i['modele'] ?? '-') ?></td>
                                <td><?= e(($i['propose_par_nom'] ?? '-') . ' ' . ($i['propose_par_prenom'] ?? '')) ?></td>
                                <td>
                                    <span class="futur-chip chip-<?= match ($i['statut'] ?? 'EN_ATTENTE') {
                                        'PUBLIE' => 'success',
                                        'REJETE' => 'danger',
                                        'EN_ATTENTE' => 'warning',
                                        'BROUILLON' => 'gray',
                                        default => 'gray',
                                    } ?>">
                                        <?= e($i['statut'] ?? 'EN_ATTENTE') ?>
                                    </span>
                                </td>
                                <td><?= e($i['created_at'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php if (($i['statut'] ?? '') === 'EN_ATTENTE'): ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="contentAction(<?= (int) $i['id'] ?>, 'approve')">
                                            <i class="mdi mdi-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="contentAction(<?= (int) $i['id'] ?>, 'reject')">
                                            <i class="mdi mdi-close"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (($i['statut'] ?? '') !== 'PUBLIE'): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="contentAction(<?= (int) $i['id'] ?>, 'publish')">
                                            <i class="mdi mdi-publish"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="7" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>

<script>
function contentAction(id, action) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', <?= json_encode(url('control/content/' . $action)) ?>);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= e(csrf_token()) ?>');
    xhr.onload = function() {
        if (xhr.status === 200) { location.reload(); }
    };
    xhr.send('id=' + id);
}
</script>
