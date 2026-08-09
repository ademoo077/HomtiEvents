<?php
/** @var array $intervention @var array $participants */
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;

$title = e(mb_substr((string) ($intervention['evenement_adresse'] ?? ''), 0, 60));
$page  = 'epic.show';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$checkinUrl = url('checkin/' . $intervention['token_qr']);
?>
<div class="wh-page">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="wh-page-title"><?= e($intervention['evenement_adresse'] ?? 'Intervention') ?></h1>
            <p class="wh-page-sub">
                <?= e($intervention['association_nom'] ?? '-') ?> •
                <?= e($intervention['commune_nom'] ?? '-') ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= url('epic') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?= e(__('common.details')) ?></h5>
                    <div class="mb-2">
                        <strong><?= e(__('common.description')) ?>:</strong>
                        <p class="mb-0"><?= nl2br(e($intervention['description'] ?? '-')) ?></p>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.date')) ?>:</strong>
                        <?= ($intervention['date_evenement'] ?? '') ? e(date('d/m/Y', strtotime((string) $intervention['date_evenement']))) : '—' ?>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.heure')) ?>:</strong> <?= e($intervention['heure'] ?? '-') ?>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.status')) ?>:</strong>
                        <span class="badge <?= match (strtolower((string) $intervention['statut'] ?? '')) {
                            'affacte' => 'bg-info',
                            'en_cours' => 'bg-warning',
                            'termine' => 'bg-success',
                            'anomalie' => 'bg-danger',
                            default => 'bg-secondary',
                        } ?>">
                            <?= e(ucfirst(strtolower((string) ($intervention['statut'] ?? 'AFFECTE')))) ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold"><?= e(__('common.status')) ?> <?= e(__('common.edit')) ?>:</label>
                        <form method="post" action="<?= url('epic/' . (int) $intervention['id'] . '/statut') ?>" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <select name="statut" class="form-select">
                                <option value="AFFECTE" <?= ($intervention['statut'] ?? '') === 'AFFECTE' ? 'selected' : '' ?>>Affecté</option>
                                <option value="EN_COURS" <?= ($intervention['statut'] ?? '') === 'EN_COURS' ? 'selected' : '' ?>>En cours</option>
                                <option value="TERMINE" <?= ($intervention['statut'] ?? '') === 'TERMINE' ? 'selected' : '' ?>>Terminé</option>
                                <option value="ANOMALIE" <?= ($intervention['statut'] ?? '') === 'ANOMALIE' ? 'selected' : '' ?>>Anomalie</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="mdi mdi-update"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (! empty($intervention['token_qr'])): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <img src="<?= e(QrCodeGenerator::pngDataUri($checkinUrl, 240)) ?>" alt="QR code" class="img-fluid" style="max-width:240px">
                        <p class="text-muted small mt-2"><?= e($checkinUrl) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <span><i class="mdi mdi-account-group"></i> <?= e(__('common.participants')) ?></span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($participants as $p): ?>
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold"><?= e(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? '')) ?></div>
                                    <small class="text-muted">
                                        <?= ($p['heure_scan'] ?? '') ? e(date('d/m/Y H:i', strtotime((string) $p['heure_scan']))) : '—' ?>
                                    </small>
                                </div>
                                <span class="text-muted small">
                                    <i class="mdi mdi-plus text-success"></i> +50 pts
                                </span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($participants)): ?>
                            <li class="list-group-item text-center text-muted py-4">
                                <?= $isAr ? 'لا يوجد مشاركون بعد.' : 'Aucun participant pour le moment.' ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
