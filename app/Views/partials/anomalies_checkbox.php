<?php
/**
 * Grille de cases à cocher des anomalies, groupée par EPIC.
 *
 * @var array $anomaliesParEpic  groupes [{epic_nom: ?string, items: [{id:int, nom:string, icone:string, couleur:string}]}]
 * @var array $selectedIds       ids déjà sélectionnés
 * @var bool  $isAr
 * @var array $errors
 */
$selectedIds = array_map('intval', (array) $selectedIds);
$labelNone   = $isAr ? 'لا توجد حالات متاحة' : 'Aucune anomalie disponible';
$counterOn   = $isAr ? 'حالة' : 'anomalie sélectionnée';
$counterOff  = $isAr ? 'حالات' : 'anomalies sélectionnées';
?>
<div class="wh-anomalies-wrap" data-anomalies-picker>
    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
        <label class="form-label mb-0"><?= e(__('evenements.anomalies')) ?></label>
        <span class="badge text-bg-primary wh-anomalies-counter" data-anomalies-counter>
            <i class="mdi mdi-checkbox-multiple-marked-outline me-1"></i>
            <span data-count>0</span>
            <span data-singular><?= e($counterOn) ?></span>
            <span data-plural hidden><?= e($counterOff) ?></span>
        </span>
    </div>

    <?php if (isset($errors['anomalies'])): ?>
        <div class="text-danger small mb-2"><?= e($errors['anomalies']) ?></div>
    <?php endif; ?>

    <?php if ($anomaliesParEpic === []): ?>
        <div class="alert alert-light border small mb-0"><?= e($labelNone) ?></div>
    <?php endif; ?>

    <?php foreach ($anomaliesParEpic as $group): ?>
        <?php if ($group['epic_nom'] !== null): ?>
            <div class="wh-anomalies-epic-title mb-2">
                <i class="mdi mdi-package-variant-closed me-1"></i><?= e($group['epic_nom']) ?>
            </div>
        <?php endif; ?>
        <div class="wh-anomalies-grid mb-3">
            <?php foreach ($group['items'] as $a): ?>
                <?php
                $checked = in_array((int) $a['id'], $selectedIds, true) ? 'checked' : '';
                $icon    = ! empty($a['icone']) ? $a['icone'] : 'alert-circle-outline';
                $color   = ! empty($a['couleur']) ? $a['couleur'] : 'var(--wh-blue)';
                ?>
                <label class="wh-anomaly-chip">
                    <input type="checkbox" name="anomalies[]" value="<?= (int) $a['id'] ?>" <?= $checked ?>>
                    <span class="wh-anomaly-label">
                        <i class="mdi mdi-<?= e($icon) ?>" style="color: <?= e($color) ?>"></i>
                        <?= e($a['nom']) ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
