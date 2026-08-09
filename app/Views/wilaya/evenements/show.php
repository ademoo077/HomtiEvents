<?php
/** @var array $event @var ?array $commune @var ?array $association @var array $anomalies
 *  @var array $epics @var int $participants @var ?array $qr @var array $historique
 *  @var array $transitions @var array $statuts @var string $statutActuel @var array $epicsListe
 */
use App\Helpers\EvenementService;
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;

$title = __('evenements.title') . ' #' . (int) $event['id'];
$page  = 'wilaya.evenements.show';
$dir   = I18n::direction();

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'                => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};

$isDeleted = ! empty($event['deleted_at']);
$permission = static function (string $p): bool {
    return can($p);
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= url('wilaya/evenements') ?>" class="wh-icon-btn"><i class="mdi mdi-arrow-left"></i></a>
            <div>
                <h1 class="wh-page-title d-flex align-items-center gap-2"><?= e(__('evenements.title')) ?> #<?= (int) $event['id'] ?>
                    <span class="wh-badge <?= $badgeColor($event['statut']) ?>"><?= e(statut_label((string) $event['statut'])) ?></span>
                </h1>
                <p class="wh-page-sub"><?= e($event['adresse']) ?></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if (! $isDeleted && $permission('evenement.edit')): ?>
                <a class="btn btn-outline-secondary" href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/edit') ?>">
                    <i class="mdi mdi-pencil me-1"></i><?= e(__('common.edit')) ?>
                </a>
            <?php endif; ?>
            <?php if (! $isDeleted && $permission('evenement.delete')): ?>
                <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/archiver') ?>" data-confirm="<?= e(__('common.archive')) ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger"><i class="mdi mdi-archive me-1"></i><?= e(__('common.archive')) ?></button>
                </form>
            <?php endif; ?>
            <?php if ($isDeleted && $permission('evenement.delete')): ?>
                <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/restaurer') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-success"><i class="mdi mdi-restore me-1"></i><?= e(__('common.restore')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-star"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= $event['date_evenement'] ? e(date('d/m/Y', strtotime((string) $event['date_evenement']))) : '—' ?></div>
                    <div class="wh-kpi-label"><?= e(__('evenements.program.date')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon green"><i class="mdi mdi-account-group"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $participants ?></div>
                    <div class="wh-kpi-label"><?= e(__('evenements.participants_count')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-satellite-variant"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= count($epics) ?></div>
                    <div class="wh-kpi-label"><?= e(__('evenements.epics_assigned')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon red"><i class="mdi mdi-alert-octagon"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= count($anomalies) ?></div>
                    <div class="wh-kpi-label"><?= e(__('evenements.anomalies')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (! empty($photos)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="mdi mdi-image-multiple me-2"></i><?= e(__('common.gallery')) ?></span>
            <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="btn btn-sm btn-outline-primary">
                <i class="mdi mdi-cog me-1"></i><?= e(__('common.gallery')) ?>
            </a>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <?php foreach (array_slice($photos, 0, 8) as $photo): ?>
                    <div class="col-4 col-md-3 col-lg-2">
                        <div class="ratio ratio-1x1 rounded overflow-hidden" style="background:#f1f5f9">
                            <?php if (! empty($photo['image'])): ?>
                                <img src="<?= e($photo['image']) ?>" alt="<?= e($photo['legende'] ?? '') ?>"
                                     loading="lazy" style="object-fit:cover">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center text-muted">
                                    <i class="mdi mdi-image-off"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($photos) > 8): ?>
                <div class="text-center mt-3">
                    <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="text-decoration-none">
                        +<?= count($photos) - 8 ?> <?= e(__('gallery.photos_count')) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif (can('gallery.upload')): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center">
            <i class="mdi mdi-image-plus text-muted" style="font-size:2rem"></i>
            <p class="mb-2 mt-2"><?= e(__('gallery.no_photos')) ?></p>
            <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos/create') ?>" class="btn btn-sm btn-primary">
                <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.add_photos')) ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (! $isDeleted): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <span><i class="mdi mdi-source-branch me-2"></i><?= e(__('common.status')) ?></span>
        </div>
        <div class="card-body">
            <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/statut') ?>" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <label class="form-label" for="statut"><?= e(__('common.status')) ?></label>
                    <select class="form-select" id="statut" name="statut" required>
                        <?php foreach ($statuts as $s): ?>
                            <?php if (EvenementService::transitionAutorisee($statutActuel, $s)): ?>
                                <option value="<?= e($s) ?>"><?= e(statut_label($s)) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="motif"><?= e(__('evenements.motif_refus')) ?></label>
                    <input type="text" class="form-control" id="motif" name="motif" placeholder="<?= e(__('evenements.motif_refus')) ?>">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-check me-1"></i><?= e(__('common.validate')) ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-information-outline me-2"></i><?= e(__('common.informations')) ?></span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4"><?= e(__('common.commune')) ?></dt>
                        <dd class="col-sm-8"><?= e($commune['nom'] ?? '-') ?></dd>

                        <dt class="col-sm-4"><?= e(__('common.association')) ?></dt>
                        <dd class="col-sm-8">
                            <?= e($association['nom'] ?? '-') ?>
                            <?php if ($association): ?>
                                <span class="wh-text-muted">(<?= e($association['email'] ?? '') ?>)</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4"><?= e(__('evenements.program.heure')) ?></dt>
                        <dd class="col-sm-8"><?= $event['heure'] ? e(substr((string) $event['heure'], 0, 5)) : '—' ?></dd>

                        <dt class="col-sm-4"><?= e(__('common.description')) ?></dt>
                        <dd class="col-sm-8"><?= nl2br(e($event['description'])) ?></dd>

                        <?php if ($event['informations_complementaires']): ?>
                            <dt class="col-sm-4"><?= e(__('evenements.complementaires')) ?></dt>
                            <dd class="col-sm-8"><?= nl2br(e($event['informations_complementaires'])) ?></dd>
                        <?php endif; ?>

                        <?php if ($event['motif_refus']): ?>
                            <dt class="col-sm-4 text-danger"><?= e(__('evenements.motif_refus')) ?></dt>
                            <dd class="col-sm-8 text-danger"><?= e($event['motif_refus']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-alert-octagon me-2"></i><?= e(__('evenements.anomalies')) ?></span>
                </div>
                <div class="card-body">
                    <?php if ($anomalies): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($anomalies as $an): ?>
                                <span class="wh-badge badge-red"><i class="mdi mdi-alert-octagon"></i> <?= e($an['nom']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-satellite-variant me-2"></i><?= e(__('evenements.epics_assigned')) ?></span>
                </div>
                <div class="card-body">
                    <?php if ($epics): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                <tr><th><?= e(__('common.epic')) ?></th><th><?= e(__('common.date')) ?></th><th><?= e(__('evenements.complementaires')) ?></th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($epics as $ep): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($ep['nom']) ?><br><small class="wh-text-muted"><?= e($ep['description'] ?? '') ?></small></td>
                                        <td><?= $ep['date_affectation'] ? e(date('d/m/Y', strtotime((string) $ep['date_affectation']))) : '—' ?></td>
                                        <td class="wh-text-muted"><?= e($ep['observation'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-clock-outline me-2"></i><?= e(__('common.historique')) ?></span>
                </div>
                <div class="card-body">
                    <?php if ($transitions): ?>
                        <ul class="timeline list-unstyled mb-0">
                            <?php foreach ($transitions as $t): ?>
                                <li class="d-flex gap-3 mb-3">
                                    <span class="wh-dot mt-2"></span>
                                    <div>
                                        <div>
                                            <span class="wh-badge badge-gray"><?= e(statut_label((string) $t['statut_avant'])) ?></span>
                                            <i class="mdi mdi-arrow-right mx-1"></i>
                                            <span class="wh-badge <?= $badgeColor($t['statut_apres']) ?>"><?= e(statut_label((string) $t['statut_apres'])) ?></span>
                                        </div>
                                        <small class="wh-text-muted">
                                            <?= e((string) ($t['user_nom'] ?? '') . ' ' . ($t['user_prenom'] ?? '')) ?> — <?= e(date('d/m/Y H:i', strtotime((string) $t['created_at']))) ?>
                                        </small>
                                        <?php if ($t['motif']): ?>
                                            <div class="wh-text-muted small"><?= e($t['motif']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <?php if ($qr): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-qrcode me-2"></i><?= e(__('common.qrcode')) ?></span>
                </div>
                <div class="card-body text-center">
                    <img src="<?= QrCodeGenerator::pngDataUri(url('checkin/' . $qr['token_qr']), 220) ?>" alt="QR" class="img-fluid mb-2" style="max-width:200px">
                    <div class="wh-text-muted small mb-3">
                        <a href="<?= url('checkin/' . $qr['token_qr']) ?>" class="text-decoration-none" target="_blank" rel="noopener">
                            <?= e(url('checkin/' . $qr['token_qr'])) ?>
                        </a>
                        <br>
                        <?php if ($qr['date_expiration']): ?>
                            <?= e(date('d/m/Y H:i', strtotime((string) $qr['date_expiration']))) ?>
                        <?php endif; ?>
                    </div>
                    <?php if (! $isDeleted && $permission('qrcode.generate')): ?>
                        <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/regen-qr') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-refresh me-1"></i><?= e(__('common.regenerate')) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (! $isDeleted && $permission('epic.assign')): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-satellite-variant me-2"></i><?= e(__('evenements.epics_assigned')) ?></span>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/epics') ?>">
                        <?= csrf_field() ?>
                        <select class="form-select mb-3" name="epics[]" multiple size="6">
                            <?php foreach ($epicsListe as $ep): ?>
                                <?php $assigned = in_array((int) $ep['id'], array_column($epics, 'id'), true); ?>
                                <option value="<?= (int) $ep['id'] ?>" <?= $assigned ? 'selected' : '' ?>><?= e($ep['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($historique): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <span><i class="mdi mdi-clipboard-text-outline me-2"></i><?= e(__('common.audit')) ?></span>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($historique as $h): ?>
                            <li class="mb-3">
                                <div class="fw-semibold small"><?= e($h['action']) ?></div>
                                <small class="wh-text-muted"><?= e(date('d/m/Y H:i', strtotime((string) $h['date_action']))) ?></small>
                                <?php if ($h['observation']): ?>
                                    <div class="small wh-text-muted"><?= e($h['observation']) ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
