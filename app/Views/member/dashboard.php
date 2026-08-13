<?php
/** @var array|null $association @var array $events */
use App\Helpers\I18n;

$title = __('members.dashboard_title');
$page  = 'member.dashboard';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$statutBadge = static function (string $statut): string {
    return match (strtolower($statut)) {
        'en_attente' => 'badge bg-secondary',
        'valide', 'programme', 'qr_genere' => 'badge bg-info',
        'en_cours' => 'badge bg-warning',
        'termine' => 'badge bg-success',
        'refuse', 'modification_demandee', 'annule' => 'badge bg-danger',
        default => 'badge bg-secondary',
    };
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('members.dashboard_title')) ?></h1>
            <p class="wh-page-sub">
                <?= e($association['nom'] ?? '') ?> —
                <?= $isAr ? 'فعاليات الجمعية' : 'Événements de votre association' ?>
            </p>
        </div>
    </div>

    <div class="row g-3">
        <?php if ($events === []): ?>
            <div class="col-12">
                <div class="wh-empty py-5 text-center text-muted">
                    <i class="mdi mdi-calendar-blank-outline" style="font-size:2.5rem"></i>
                    <p class="mb-0 mt-2"><?= $isAr ? 'لا توجد فعاليات بعد.' : 'Aucun événement pour le moment.' ?></p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($events as $ev): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="<?= $statutBadge((string) $ev['statut']) ?>">
                                    <?= e(statut_label((string) $ev['statut'])) ?>
                                </span>
                            </div>
                            <h5 class="card-title"><?= e($ev['adresse'] ?? '') ?></h5>
                            <p class="card-text text-muted small">
                                <?= e(mb_substr((string) ($ev['description'] ?? ''), 0, 120)) ?>
                            </p>
                            <div class="small text-muted">
                                <div><i class="mdi mdi-map-marker-outline me-1"></i><?= e($ev['commune_nom'] ?? '-') ?></div>
                                <div><i class="mdi mdi-calendar me-1"></i><?= e(date('d/m/Y', strtotime((string) $ev['date_evenement']))) ?></div>
                                <div><i class="mdi mdi-account-group-outline me-1"></i>
                                    <?= (int) ($ev['participants'] ?? 0) ?> <?= $isAr ? 'مشارك' : 'participant(s)' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
