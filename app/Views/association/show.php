<?php
/** @var array $event @var array $participants @var array|null $album @var array|null $evaluation @var array $historique */
use App\Helpers\I18n;

$title = e($event['adresse'] ?? 'Détails');
$page  = 'association.show';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
$statut = (string) ($event['statut'] ?? 'EN_ATTENTE');
$editable = in_array($statut, ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE'], true);
$termine = $statut === 'TERMINE';
$countParticipants = count($participants);
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <h1 class="wh-page-title"><?= e(mb_substr((string) ($event['adresse'] ?? ''), 0, 60)) ?></h1>
        <a class="btn btn-outline-secondary" href="<?= url('association') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?= e(__('common.details')) ?></h5>
                    <div class="mb-2">
                        <strong><?= e(__('common.adresse')) ?>:</strong> <?= e($event['adresse'] ?? '-') ?>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.commune')) ?>:</strong>
                        <?= e($event['commune_nom'] ?? '—') ?>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.description')) ?>:</strong>
                        <p class="mb-0"><?= nl2br(e($event['description'] ?? '-')) ?></p>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.date')) ?>:</strong>
                        <?= ($event['date_evenement'] ?? '') ? e(date('d/m/Y', strtotime((string) $event['date_evenement']))) : '—' ?>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.heure')) ?>:</strong> <?= e($event['heure'] ?? '—') ?>
                    </div>
                    <?php if (! empty($event['motif_refus'])): ?>
                        <div class="alert alert-danger py-2 small mb-0">
                            <strong><?= e(__('common.motif_refus')) ?>:</strong> <?= e($event['motif_refus']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php $statutQr = (string) ($statut ?? $event['statut'] ?? ''); ?>
            <?php if (! empty($qrStreamUrl) && in_array($statutQr, ['VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE'], true)): ?>
                <div class="card border-0 shadow-sm mb-3 text-center">
                    <div class="card-header">
                        <i class="mdi mdi-qrcode me-1"></i><?= e(__('evenements.qr_code')) ?>
                    </div>
                    <div class="card-body">
                        <img src="<?= $qrStreamUrl ?>" alt="QR" class="img-fluid mb-3" style="max-width:200px">
                        <div class="wh-qr-summary small wh-text-muted mb-3">
                            <div class="fw-medium text-dark"><?= e(substr((string) ($event['description'] ?? ''), 0, 50) ?: 'Événement') ?></div>
                            <div><i class="mdi mdi-map-marker me-1"></i><?= e($event['adresse'] ?? '-') ?></div>
                            <div><i class="mdi mdi-calendar me-1"></i>
                                <?= e($event['date_evenement'] ? date('d/m/Y H:i', strtotime((string) $event['date_evenement'] . ' ' . ((string) ($event['heure'] ?? '00:00:00')))) : '-') ?>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <?php if (! empty($qrDownloadUrl)): ?>
                                <a href="<?= $qrDownloadUrl ?>" class="btn btn-sm btn-outline-primary" download>
                                    <i class="mdi mdi-download me-1"></i><?= e(__('evenements.qr_download')) ?>
                                </a>
                            <?php endif; ?>
                            <?php if (! empty($qrShareUrl)): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="navigator.clipboard.writeText('<?= e($qrShareUrl) ?>').then(function(){ alert('Lien copié dans le presse-papiers'); })">
                                    <i class="mdi mdi-link-box me-1"></i><?= e(__('evenements.qr_share')) ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (! empty($qrShareUrl)): ?>
                            <div class="wh-text-muted small mt-2 text-break">
                                <?= e($qrShareUrl) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="mdi mdi-account-group me-1"></i><?= e(__('evenements.participants')) ?>
                        <span class="badge bg-secondary"><?= $countParticipants ?></span>
                    </h5>
                    <?php if ($participants !== []): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th><?= e(__('common.nom')) ?></th>
                                        <th><?= e(__('common.email')) ?></th>
                                        <th><?= e(__('common.telephone')) ?></th>
                                        <th><?= e(__('evenements.scan_time')) ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participants as $p): ?>
                                        <tr>
                                            <td><?= e($p['prenom'] . ' ' . $p['nom']) ?></td>
                                            <td><?= e($p['email']) ?></td>
                                            <td><?= e($p['telephone'] ?? '—') ?></td>
                                            <td><?= e(date('d/m/Y H:i', strtotime((string) $p['heure_scan']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?= e(__('evenements.no_participants')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($album !== null): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">
                                <i class="mdi mdi-image-multiple me-1"></i><?= e($album['titre'] ?? '') ?>
                                <span class="badge bg-secondary"><?= (int) ($album['nb_photos'] ?? 0) ?></span>
                            </h5>
                            <span class="badge <?= ($album['statut'] ?? 'brouillon') === 'publie' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= e(__('gallery.status_' . (($album['statut'] ?? 'brouillon') === 'publie' ? 'published' : 'draft'))) ?>
                            </span>
                        </div>
                        <?php if (! empty($album['recit'])): ?>
                            <p class="mt-3 mb-0"><?= nl2br(e($album['recit'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= e(__('evenements.workflow_history')) ?></h5>
                    <?php if ($historique !== []): ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($historique as $h): ?>
                                <li class="d-flex gap-2 mb-2">
                                    <span class="badge <?= match (statut_key((string) $h['statut_apres'])) {
                                        'en_attente', 'modification_demandee' => 'bg-warning',
                                        'valide', 'programme', 'qr_genere' => 'bg-info',
                                        'en_cours' => 'bg-primary',
                                        'termine' => 'bg-success',
                                        'refuse' => 'bg-danger',
                                        default => 'bg-secondary',
                                    } ?>">
                                        <?= e(statut_label((string) $h['statut_apres'])) ?>
                                    </span>
                                    <span class="text-muted small">
                                        <?= e(date('d/m/Y H:i', strtotime((string) $h['created_at']))) ?>
                                        <?php if (! empty($h['motif'])): ?>
                                            — <?= e($h['motif']) ?>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?= e(__('evenements.no_history')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body text-center">
                    <span class="badge <?= match (statut_key($statut)) {
                        'en_attente', 'modification_demandee' => 'bg-warning',
                        'valide', 'programme', 'qr_genere' => 'bg-info',
                        'en_cours' => 'bg-primary',
                        'termine' => 'bg-success',
                        'refuse' => 'bg-danger',
                        default => 'bg-secondary',
                    } ?>">
                        <?= e(statut_label($statut)) ?>
                    </span>
                </div>
            </div>

            <?php if ($editable): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <a class="btn btn-outline-secondary w-100" href="<?= url('association/' . (int) $event['id'] . '/edit') ?>">
                            <i class="mdi mdi-pencil"></i> <?= e(__('common.edit')) ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($termine): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="mdi mdi-star-outline me-1"></i><?= e(__('evenements.evaluation')) ?></h5>
                        <?php if ($evaluation !== null): ?>
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="mdi <?= $i <= (int) $evaluation['note'] ? 'mdi-star' : 'mdi-star-outline' ?> text-warning"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if (! empty($evaluation['description'])): ?>
                                <p class="mb-0"><?= nl2br(e($evaluation['description'])) ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="post" action="<?= url('association/' . (int) $event['id'] . '/evaluate') ?>">
                                <?= csrf_field() ?>
                                <div class="mb-2">
                                    <label class="form-label small fw-medium"><?= e(__('evenements.evaluation_note')) ?></label>
                                    <div class="d-flex gap-1" id="ratingStars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning rating-star" data-value="<?= $i ?>">
                                                <i class="mdi mdi-star-outline"></i>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="note" id="note" value="" required>
                                    <?php if (isset($errors['note'])): ?><div class="text-danger small"><?= e($errors['note']) ?></div><?php endif; ?>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-medium"><?= e(__('evenements.evaluation_comment')) ?></label>
                                    <textarea class="form-control" name="description" rows="3"><?= e($old['description'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="mdi mdi-send me-1"></i><?= e(__('evenements.evaluation_submit')) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const stars = document.querySelectorAll('#ratingStars .rating-star');
    const noteInput = document.getElementById('note');
    if (!stars.length || !noteInput) return;
    stars.forEach((btn) => {
        btn.addEventListener('click', () => {
            const value = parseInt(btn.dataset.value, 10);
            noteInput.value = value;
            stars.forEach((s, i) => {
                const on = i < value;
                s.classList.toggle('btn-warning', on);
                s.classList.toggle('btn-outline-warning', !on);
                s.innerHTML = on
                    ? '<i class="mdi mdi-star"></i>'
                    : '<i class="mdi mdi-star-outline"></i>';
            });
        });
    });
})();
</script>
