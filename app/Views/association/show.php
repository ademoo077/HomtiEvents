<?php
/** @var array $event @var array $participants @var array|null $album @var array|null $evaluation @var array $historique */
use App\Helpers\Database;
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
    <div style="background:linear-gradient(135deg,#0B5ED7 0%,#198754 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-calendar-check"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(mb_substr((string) ($event['adresse'] ?? ''), 0, 60)) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= e($event['commune_nom'] ?? '') ?> <?= ($event['date_evenement'] ?? '') ? '· ' . e(date('d/m/Y', strtotime((string) $event['date_evenement']))) : '' ?></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light btn-sm" href="<?= url('association') ?>">
                    <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
                </a>
                <a class="btn btn-light btn-sm" href="<?= url('association/' . (int) $event['id'] . '/ical') ?>" title="Export iCal">
                    <i class="mdi mdi-calendar-export me-1"></i>iCal
                </a>
                <a class="btn btn-light btn-sm" href="<?= url('association/' . (int) $event['id'] . '/clone') ?>" title="Cloner">
                    <i class="mdi mdi-content-copy me-1"></i><?= $isAr ? 'استنساخ' : 'Cloner' ?>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3" style="border-radius:var(--wh-radius);overflow:hidden;">
                <div style="padding:.65rem 1.25rem;background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;display:flex;align-items:center;gap:.5rem;">
                    <span style="width:28px;height:28px;border-radius:7px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);font-size:.85rem;"><i class="mdi mdi-information-outline"></i></span>
                    <span class="fw-bold" style="font-size:.88rem;"><?= e(__('common.details')) ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong><?= e(__('common.adresse')) ?>:</strong> <?= e($event['adresse'] ?? '-') ?>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.commune')) ?>:</strong>
                        <?= e($event['commune_nom'] ?? '—') ?>
                    </div>
                    <?php if (!empty($event['latitude']) && !empty($event['longitude'])): ?>
                        <div class="mb-2">
                            <strong><i class="mdi mdi-map-marker-radius me-1"></i>GPS :</strong>
                            <a href="https://www.openstreetmap.org/?mlat=<?= e((string) $event['latitude']) ?>&mlon=<?= e((string) $event['longitude']) ?>#map=15" target="_blank" class="text-decoration-none">
                                <?= e((string) $event['latitude']) ?>, <?= e((string) $event['longitude']) ?>
                                <i class="mdi mdi-open-in-new ms-1" style="font-size:.7rem;"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <strong><?= e(__('common.description')) ?>:</strong>
                        <p class="mb-0"><?= nl2br(e($event['description'] ?? '-')) ?></p>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.date')) ?>:</strong>
                        <?= ($event['date_evenement'] ?? '') ? e(date('d/m/Y', strtotime((string) $event['date_evenement']))) : '—' ?>
                    </div>
                    <?php if (!empty($event['start_at'])): ?>
                        <div class="mb-2">
                            <strong>Début :</strong> <?= e(date('d/m/Y H:i', strtotime((string) $event['start_at']))) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($event['end_at'])): ?>
                        <div class="mb-2">
                            <strong>Fin :</strong> <?= e(date('d/m/Y H:i', strtotime((string) $event['end_at']))) ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <strong><?= e(__('common.heure')) ?>:</strong> <?= e($event['heure'] ?? '—') ?>
                    </div>

                    <?php
                    $anomalyDetails = \App\Helpers\Database::all(
                        'SELECT ae.*, an.nom AS anomalie_nom FROM anomalies_evenement ae JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = ?',
                        [(int) $event['id']]
                    );
                    $anomalyAssigns = \App\Helpers\Database::all(
                        'SELECT aa.*, an.nom AS anomalie_nom, ep.nom AS epic_nom FROM anomaly_assignments aa JOIN anomalies an ON an.id = aa.anomalie_id JOIN epic ep ON ep.id = aa.epic_id WHERE aa.evenement_id = ?',
                        [(int) $event['id']]
                    );
                    ?>
                    <?php if ($anomalyDetails): ?>
                        <div class="mt-3 mb-2">
                            <strong><i class="mdi mdi-alert-octagon me-1"></i>Anomalies déclarées</strong>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.8rem;">
                                <thead class="table-light">
                                <tr><th>Anomalie</th><th>Statut</th><th>GPS</th><th>EPIC</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($anomalyDetails as $ad): ?>
                                    <?php
                                    $assign = null;
                                    foreach ($anomalyAssigns as $aa) {
                                        if ((int) $aa['anomalie_id'] === (int) $ad['anomalie_id']) { $assign = $aa; break; }
                                    }
                                    $st = (string) ($ad['statut'] ?? 'DETECTEE');
                                    $stColor = match($st) { 'DETECTEE' => '#f59e0b', 'EN_COURS' => '#3b82f6', 'RESOLUE' => '#22c55e', 'REJETEE' => '#ef4444', 'EN_ATTENTE' => '#6b7280', default => '#6b7280' };
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e((string) $ad['anomalie_nom']) ?></td>
                                        <td><span style="background:<?= $stColor ?>20; color:<?= $stColor ?>; padding:.1rem .4rem; border-radius:9999px; font-size:.7rem;"><?= e($st) ?></span></td>
                                        <td>
                                            <?php if ($ad['latitude'] && $ad['longitude']): ?>
                                                <a href="https://www.openstreetmap.org/?mlat=<?= e((string) $ad['latitude']) ?>&mlon=<?= e((string) $ad['longitude']) ?>#map=15" target="_blank" class="text-decoration-none">
                                                    <i class="mdi mdi-map-marker text-danger"></i> <?= e(mb_substr((string) $ad['latitude'], 0, 6)) ?>, <?= e(mb_substr((string) $ad['longitude'], 0, 6)) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $assign ? e((string) $assign['epic_nom']) : '<span class="text-muted">—</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <?php if (! empty($event['motif_refus'])): ?>
                        <div class="alert alert-danger py-2 small mb-0">
                            <strong><?= e(__('common.motif_refus')) ?>:</strong> <?= e($event['motif_refus']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php $statutQr = (string) ($statut ?? $event['statut'] ?? ''); ?>
            <?php if (! empty($qrStreamUrl) && in_array($statutQr, ['VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE'], true)): ?>
                <div class="card border-0 shadow-sm mb-3 text-center" style="border-radius:var(--wh-radius);overflow:hidden;">
                    <div style="padding:.65rem 1.25rem;background:#ede9fe;border-bottom:1px solid #ddd6fe;display:flex;align-items:center;gap:.5rem;">
                        <span style="width:28px;height:28px;border-radius:7px;background:rgba(124,58,237,.15);display:grid;place-items:center;color:#7c3aed;font-size:.85rem;"><i class="mdi mdi-qrcode"></i></span>
                        <span class="fw-bold" style="font-size:.88rem;"><?= e(__('evenements.qr_code')) ?></span>
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

            <div class="card border-0 shadow-sm mb-3" style="border-radius:var(--wh-radius);overflow:hidden;">
                <div style="padding:.65rem 1.25rem;background:var(--wh-green-soft);border-bottom:1px solid #b7e4c7;display:flex;align-items:center;gap:.5rem;">
                    <span style="width:28px;height:28px;border-radius:7px;background:rgba(25,135,84,.15);display:grid;place-items:center;color:var(--wh-green);font-size:.85rem;"><i class="mdi mdi-account-group"></i></span>
                    <span class="fw-bold" style="font-size:.88rem;"><?= e(__('evenements.participants')) ?> <span class="wh-badge badge-green"><?= $countParticipants ?></span></span>
                    <?php if (in_array((string) ($event['statut'] ?? ''), ['PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE'], true)): ?>
                        <a href="<?= url('association/evenements/' . (int) $event['id'] . '/presence') ?>" class="btn btn-sm btn-outline-success" style="margin-inline-start:auto;">
                            <i class="mdi mdi-account-multiple-check me-1"></i><?= e($isAr ? 'الحضور المباشر' : 'Présences en direct') ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h5 class="card-title" style="display:none;"><?= e(__('evenements.participants')) ?></h5>
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
                <div class="card border-0 shadow-sm mb-3" style="border-radius:var(--wh-radius);overflow:hidden;">
                    <div style="padding:.65rem 1.25rem;background:#cff4fc;border-bottom:1px solid #b6effb;display:flex;align-items:center;gap:.5rem;">
                        <span style="width:28px;height:28px;border-radius:7px;background:rgba(13,202,240,.15);display:grid;place-items:center;color:#087990;font-size:.85rem;"><i class="mdi mdi-image-multiple"></i></span>
                        <span class="fw-bold" style="font-size:.88rem;"><?= e($album['titre'] ?? '') ?> <span class="wh-badge badge-cyan"><?= (int) ($album['nb_photos'] ?? 0) ?></span></span>
                        <span class="badge <?= ($album['statut'] ?? 'brouillon') === 'publie' ? 'bg-success' : 'bg-secondary' ?>" style="margin-inline-start:auto;">
                            <?= e(__('gallery.status_' . (($album['statut'] ?? 'brouillon') === 'publie' ? 'published' : 'draft'))) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (! empty($album['recit'])): ?>
                            <p class="mt-3 mb-0"><?= nl2br(e($album['recit'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php $eventId = (int) $event['id']; ?>
            <?php include __DIR__ . '/../partials/event_messages.php'; ?>
            <?php include __DIR__ . '/../partials/event_checklist.php'; ?>

            <div class="card border-0 shadow-sm" style="border-radius:var(--wh-radius);overflow:hidden;">
                <div style="padding:.65rem 1.25rem;background:var(--wh-gray-soft);border-bottom:1px solid var(--wh-border);display:flex;align-items:center;gap:.5rem;">
                    <span style="width:28px;height:28px;border-radius:7px;background:rgba(91,100,114,.12);display:grid;place-items:center;color:var(--wh-gray);font-size:.85rem;"><i class="mdi mdi-history"></i></span>
                    <span class="fw-bold" style="font-size:.88rem;"><?= e(__('evenements.workflow_history')) ?></span>
                </div>
                <div class="card-body">
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
            <div class="card border-0 shadow-sm mb-3" style="border-radius:var(--wh-radius);overflow:hidden;">
                <div style="padding:.65rem 1.25rem;background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;display:flex;align-items:center;gap:.5rem;">
                    <span style="width:28px;height:28px;border-radius:7px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);font-size:.85rem;"><i class="mdi mdi-chart-timeline-variant"></i></span>
                    <span class="fw-bold" style="font-size:.88rem;"><?= $isAr ? 'مسار الفعالية' : 'Pipeline de l\'événement' ?></span>
                </div>
                <div class="card-body py-2">
                    <?php include __DIR__ . '/../partials/pipeline.php'; ?>
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
                <div class="card border-0 shadow-sm mb-3" style="border-radius:var(--wh-radius);overflow:hidden;">
                    <div style="padding:.65rem 1.25rem;background:#fff3cd;border-bottom:1px solid #ffeaa7;display:flex;align-items:center;gap:.5rem;">
                        <span style="width:28px;height:28px;border-radius:7px;background:rgba(245,158,11,.15);display:grid;place-items:center;color:var(--wh-amber);font-size:.85rem;"><i class="mdi mdi-star-outline"></i></span>
                        <span class="fw-bold" style="font-size:.88rem;"><?= e(__('evenements.evaluation')) ?></span>
                    </div>
                    <div class="card-body">
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
