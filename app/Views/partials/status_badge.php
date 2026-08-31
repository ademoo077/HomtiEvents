<?php
/**
 * Badge de statut réutilisable (avec icône optionnelle).
 *
 * Usage :
 *   <?= view('partials.status_badge', ['statut' => $ev['statut']]) ?>
 *   <?= view('partials.status_badge', ['statut' => $ev['statut'], 'icon' => true, 'class' => 'ms-1']) ?>
 *
 * @var string $statut   Statut brut (ex: EN_ATTENTE, VALIDÉ, ...)
 * @var bool   $icon     Afficher une icône MDI adaptée (défaut false)
 * @var string $class    Classes additionnelles
 * @var string $label    Surcharge du libellé (défaut : statut_label())
 */

$statut = (string) ($statut ?? '');
$withIcon = ! empty($icon);
$extra = $class ?? '';

$badgeClass = match (statut_key($statut)) {
    'en_attente', 'modification_demandee' => 'badge-amber',
    'valide'                               => 'badge-blue',
    'programme'                            => 'badge-cyan',
    'qr_genere'                            => 'badge-violet',
    'en_cours'                             => 'badge-blue',
    'termine'                              => 'badge-green',
    'refuse'                               => 'badge-red',
    default                                => 'badge-gray',
};

$iconClass = match (statut_key($statut)) {
    'en_attente'            => 'mdi-clock-outline',
    'modification_demandee' => 'mdi-pencil-clock',
    'valide'                => 'mdi-check-circle-outline',
    'programme'             => 'mdi-calendar-check',
    'qr_genere'             => 'mdi-qrcode',
    'en_cours'              => 'mdi-play-circle-outline',
    'termine'               => 'mdi-check-all',
    'refuse'                => 'mdi-close-circle-outline',
    default                 => 'mdi-help-circle-outline',
};

$text = $label ?? statut_label($statut);
?>
<span class="wh-badge <?= e($badgeClass) ?> <?= e($extra) ?>"><?php if ($withIcon): ?><i class="mdi <?= e($iconClass) ?>"></i><?php endif; ?><?= e($text) ?></span>
