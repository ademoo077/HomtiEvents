<?php
/** @var array $interventions @var int $year @var int $month @var int $dim @var int $nbJours @var array $prev @var array $next @var bool $isCurrent */
use App\Helpers\I18n;

$title = __('epic.agenda_link');
$page  = 'epic.agenda';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$statutCls = static function (?string $statut): string {
    return match (strtolower((string) $statut)) {
        'en_cours' => 'bg-warning text-dark',
        'termine'  => 'bg-success',
        'anomalie' => 'bg-danger',
        default    => 'bg-secondary',
    };
};
$moisNoms = [
    1 => ['fr' => 'Janvier', 'ar' => 'جانفي'],
    2 => ['fr' => 'Février', 'ar' => 'فيفري'],
    3 => ['fr' => 'Mars', 'ar' => 'مارس'],
    4 => ['fr' => 'Avril', 'ar' => 'أفريل'],
    5 => ['fr' => 'Mai', 'ar' => 'ماي'],
    6 => ['fr' => 'Juin', 'ar' => 'جوان'],
    7 => ['fr' => 'Juillet', 'ar' => 'جويلية'],
    8 => ['fr' => 'Août', 'ar' => 'أوت'],
    9 => ['fr' => 'Septembre', 'ar' => 'سبتمبر'],
    10 => ['fr' => 'Octobre', 'ar' => 'أكتوبر'],
    11 => ['fr' => 'Novembre', 'ar' => 'نوفمبر'],
    12 => ['fr' => 'Décembre', 'ar' => 'ديسمبر'],
];
$titreMois = $isAr ? ($moisNoms[$month]['ar'] ?? (string) $month) : ($moisNoms[$month]['fr'] ?? (string) $month);
?>
<div class="wh-page">
    <div class="wh-hero wh-hero-epic" style="background: linear-gradient(135deg, #0891B2 0%, #0B5ED7 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title">
                        <i class="mdi mdi-calendar-month me-2"></i><?= $isAr ? 'أجندة التدخلات' : 'Agenda des interventions' ?>
                    </h1>
                    <p class="wh-hero-sub"><?= e($titreMois) ?> <?= (int) $year ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-outline-light" href="<?= url('epic') ?>">
                        <i class="mdi mdi-clipboard-text-outline me-1"></i><?= $isAr ? 'قائمة التدخلات' : 'Liste des interventions' ?>
                    </a>
                    <a class="btn btn-outline-light" href="<?= url('epic/dashboard') ?>">
                        <i class="mdi mdi-view-dashboard-outline me-1"></i><?= $isAr ? 'لوحة القيادة' : 'Tableau de bord' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('epic/agenda?year=' . (int) $prev['y'] . '&month=' . (int) $prev['m']) ?>" title="<?= e(__('epic.agenda_prev')) ?>">
                        <i class="mdi <?= $isAr ? 'mdi-chevron-right' : 'mdi-chevron-left' ?>"></i>
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('epic/agenda?year=' . (int) $next['y'] . '&month=' . (int) $next['m']) ?>" title="<?= e(__('epic.agenda_next')) ?>">
                        <i class="mdi <?= $isAr ? 'mdi-chevron-left' : 'mdi-chevron-right' ?>"></i>
                    </a>
                </div>
                <h5 class="mb-0 fw-semibold text-uppercase"><?= $isAr ? '' : $titreMois ?> <?= $isAr ? (string) $year : (string) $year ?></h5>
                <?php if (! $isCurrent): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('epic/agenda') ?>"><?= e(__('epic.agenda_today')) ?></a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0 wh-calendar">
                    <thead class="table-light">
                        <tr>
                            <?php for ($w = 1; $w <= 7; $w++): ?>
                                <th class="wh-cal-head"><?= e(__('epic.agenda_week' . $w)) ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $today = (int) date('j');
                        $isCurrentMonth = $isCurrent;
                        $cell = 1 - ($dim - 1);
                        $weeks = (int) ceil(($dim + $cell - 1) / 7);
                        for ($r = 0; $r < $weeks; $r++):
                            ?>
                            <tr>
                                <?php for ($c = 0; $c < 7; $c++): ?>
                                    <?php
                                    $day = $cell;
                                    $inMonth = $day >= 1 && $day <= $nbJours;
                                    $cls = '';
                                    if ($inMonth && $isCurrentMonth && $day === $today) {
                                        $cls = 'wh-cal-today';
                                    } elseif (! $inMonth) {
                                        $cls = 'wh-cal-outside';
                                    }
                                    $evts = $inMonth ? ($interventions[$day] ?? []) : [];
                                    ?>
                                    <td class="wh-cal-cell <?= $cls ?>">
                                        <div class="wh-cal-day"><?= $inMonth ? (int) $day : '' ?></div>
                                        <?php foreach ($evts as $ev): ?>
                                            <a class="d-block text-start small mb-1 p-1 rounded wh-cal-ev badge <?= $statutCls($ev['intervention_statut'] ?? null) ?>"
                                               href="<?= url('epic/' . (int) $ev['evenement_id']) ?>" title="<?= e($ev['adresse'] ?? '') ?>">
                                                <i class="mdi mdi-clock-outline"></i> <?= $isAr ? (string) ($ev['heure'] ?? '') . ' ' : (string) ($ev['heure'] ?? '') . ' ' ?><?= e(mb_substr((string) ($ev['adresse'] ?? ''), 0, 22)) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </td>
                                    <?php $cell++; ?>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($interventions) === 0): ?>
                <div class="text-center text-muted py-4">
                    <i class="mdi mdi-calendar-blank-outline mdi-36px"></i>
                    <p class="mb-0 mt-2"><?= e(__('epic.agenda_empty')) ?></p>
                </div>
            <?php endif; ?>

            <div class="small text-muted mt-3">
                <span class="badge bg-secondary me-1"><?= e(__('epic.statut_affecte')) ?></span>
                <span class="badge bg-warning text-dark me-1"><?= e(__('epic.statut_en_cours')) ?></span>
                <span class="badge bg-success me-1"><?= e(__('epic.statut_termine')) ?></span>
                <span class="badge bg-danger me-1"><?= e(__('epic.statut_anomalie')) ?></span>
            </div>
        </div>
    </div>

    <style>
        .wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}
        .wh-hero-inner{max-width:1200px;margin:0 auto}
        .wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
        .wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}
        .wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}
        .wh-hero-actions{display:flex;align-items:center;gap:.5rem}
        .wh-cal-cell{height:96px;vertical-align:top;padding:.35rem;position:relative}
        .wh-cal-day{font-weight:700;color:#0B5ED7}
        .wh-cal-today{background:#e8f1ff}
        .wh-cal-outside{background:#f7f8fa}
        .wh-cal-ev{color:#fff;text-decoration:none}
        .wh-cal-ev:hover{opacity:.85;color:#fff}
        .wh-cal-head{font-size:.8rem;text-transform:uppercase;color:#6c757d}
    </style>
</div>
