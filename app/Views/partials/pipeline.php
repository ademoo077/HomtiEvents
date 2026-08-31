<?php
/** @var string $statut */
use App\Helpers\I18n;

$dir = I18n::direction();
$isAr = $dir === 'rtl';

$steps = [
    'EN_ATTENTE'       => $isAr ? 'قيد الانتظار' : 'En attente',
    'VALIDÉ'           => $isAr ? 'تمت الموافقة' : 'Validé',
    'PROGRAMME'        => $isAr ? 'مبرمج' : 'Programmé',
    'QR_GENERE'        => $isAr ? 'QR مولّد' : 'QR généré',
    'EN_COURS'         => $isAr ? 'جاري' : 'En cours',
    'TERMINE'          => $isAr ? 'منجز' : 'Terminé',
];

$skipStates = ['MODIFICATION_DEMANDEE', 'REFUSE'];
$statut = strtoupper($statut);

if (in_array($statut, $skipStates, true)) {
    $displaySteps = $steps;
    $currentKey = $statut === 'REFUSE' ? null : null;
} else {
    $displaySteps = $steps;
    $currentKey = $statut;
}

$keys = array_keys($displaySteps);
$currentIndex = $currentKey !== null ? array_search($currentKey, $keys, true) : -1;
?>

<div class="wh-pipeline">
    <?php foreach ($displaySteps as $key => $label): ?>
        <?php
            $stepIndex = array_search($key, $keys, true);
            $state = 'pending';
            if ($currentIndex >= 0 && $stepIndex < $currentIndex) {
                $state = 'done';
            } elseif ($stepIndex === $currentIndex) {
                $state = 'current';
            }
            $icon = match ($key) {
                'EN_ATTENTE' => 'mdi-clock-outline',
                'VALIDÉ'     => 'mdi-check',
                'PROGRAMME'  => 'mdi-calendar',
                'QR_GENERE'  => 'mdi-qrcode',
                'EN_COURS'   => 'mdi-play',
                'TERMINE'    => 'mdi-check-all',
                default      => 'mdi-circle',
            };
        ?>
        <div class="wh-pipeline-step <?= $state === 'done' ? 'wh-done' : ($state === 'current' ? 'wh-current' : '') ?>" data-statut="<?= e($key) ?>" title="<?= e($label) ?> — <?= $state==='done'?'Fait':($state==='current'?'Actuel':'À venir') ?>" style="cursor:<?= $state!=='pending'?'pointer':'' ?>" onclick="if(this.dataset.statut!=='EN_ATTENTE') document.querySelector('#tab-historique')?.click?.() || document.querySelector('[data-bs-target=\'#tab-historique\']')?.click()">
            <div class="wh-pipeline-dot">
                <i class="mdi <?= $icon ?>"></i>
            </div>
            <div class="wh-pipeline-label"><?= e($label) ?></div>
        </div>
    <?php endforeach; ?>
</div>
