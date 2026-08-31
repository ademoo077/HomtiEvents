<?php
/**
 * Carte KPI réutilisable.
 *
 * Usage :
 *   <?= view('partials.kpi_card', [
 *       'value' => $kpis['en_attente'],
 *       'label' => __('evenements.statut_en_attente'),
 *       'icon'  => 'mdi-clock-outline',
 *       'accent' => 'var(--wh-amber)',
 *       'bg'     => '#fff3cd',
 *       'link'   => url('wilaya/evenements?statut=en_attente'),
 *       'arrow'  => true,
 *   ]) ?>
 *
 * @var mixed  $value  Valeur affichée
 * @var string $label  Libellé
 * @var string $icon   Classe MDI (ex: mdi-clock-outline)
 * @var string $accent Couleur d'accent (variable CSS ou hexa)
 * @var string $bg     Couleur de fond soft
 * @var string $link   Lien optionnel (rend la carte cliquable)
 * @var bool   $arrow  Afficher la flèche (défaut false)
 * @var float  $trend  Évolution en % (ex: +12.5 / -3.2) — affiche une pastille positive/négative
 * @var string $trendLabel Libellé de l'évolution (ex: "vs mois dernier")
 * @var string $class  Classes additionnelles sur la carte
 */

$value  = $value ?? 0;
$label  = $label ?? '';
$icon   = $icon ?? 'mdi-circle-outline';
$accent = $accent ?? 'var(--wh-blue)';
$bg     = $bg ?? 'var(--wh-blue-soft)';
$link   = $link ?? '';
$showArrow = ! empty($arrow);
$extra  = $class ?? '';
$trend  = $trend ?? null;
$trendLabel = $trendLabel ?? '';
$trendChip = '';
if ($trend !== null && $trend !== '' && is_numeric($trend)) {
    $trendVal = (float) $trend;
    $trendDir = $trendVal >= 0 ? 'up' : 'down';
    $trendAbs = number_format(abs($trendVal), 0);
    $trendArrow = $trendVal >= 0 ? 'mdi-trending-up' : 'mdi-trending-down';
    $caption = $trendLabel !== '' ? e($trendLabel) : '';
    $trendChip = '<span class="wh-kpi-trend ' . $trendDir . '" title="' . $caption . '"><i class="mdi ' . $trendArrow . '"></i>' . $trendAbs . '%</span>';
}
$inner = '<div class="wh-kpi-icon"><i class="mdi ' . e($icon) . '"></i></div>'
    . '<div>'
    . '<div class="wh-kpi-value">' . $value . '</div>'
    . '<div class="wh-kpi-label">' . e($label) . '</div>'
    . '</div>'
    . ($showArrow ? '<i class="mdi mdi-arrow-right wh-kpi-arrow"></i>' : '')
    . $trendChip;
?>
<?php if ($link !== ''): ?>
<a href="<?= e($link) ?>" class="wh-kpi-card wh-dash-animate <?= e($extra) ?>" style="--kpi-accent:<?= e($accent) ?>;--kpi-bg:<?= e($bg) ?>"><?= $inner ?></a>
<?php else: ?>
<div class="wh-kpi-card wh-dash-animate <?= e($extra) ?>" style="--kpi-accent:<?= e($accent) ?>;--kpi-bg:<?= e($bg) ?>"><?= $inner ?></div>
<?php endif; ?>
