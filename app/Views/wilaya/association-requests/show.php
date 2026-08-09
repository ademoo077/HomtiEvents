<?php
/**
 * Demande d'inscription association — Détail (admin Wilaya).
 *
 * @var array $request
 */
use App\Helpers\I18n;

$title = 'Demande #' . (int) $request['id'];
$page  = 'wilaya.association-requests.index';
$dir   = I18n::direction();

$st = match($request['status'] ?? 'pending') {
    'approved' => ['badge-success', 'Approuvée'],
    'rejected' => ['badge-danger',  'Refusée'],
    default    => ['badge-warning', 'En attente'],
};

$isPending = ($request['status'] ?? '') === 'pending';
?>
<div class="wh-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-file-document-outline me-2"></i>Demande #<?= (int) $request['id'] ?>
            </h1>
            <p class="wh-page-sub">
                <?= e($request['association_name']) ?>
                <span class="wh-badge <?= e($st[0]) ?> ms-2"><?= e($st[1]) ?></span>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= url('admin/association-requests') ?>">
            <i class="mdi mdi-arrow-left me-1"></i>Retour à la liste
        </a>
    </div>

    <div class="row g-4">
        <!-- Infos association -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="mdi mdi-office-building me-1"></i> Informations de l'association
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:45%">Nom</td>
                            <td class="fw-semibold"><?= e($request['association_name']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">N° agrément</td>
                            <td><?= e($request['approval_number']) ?></td>
                        </tr>
                        <?php if (! empty($request['activity_domain'])): ?>
                            <tr>
                                <td class="text-muted">Domaine d'activité</td>
                                <td><?= e($request['activity_domain']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted">Téléphone</td>
                            <td><?= e($request['phone']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td><?= e($request['email']) ?></td>
                        </tr>
                        <?php if (! empty($request['commune'])): ?>
                            <tr>
                                <td class="text-muted">Commune</td>
                                <td><?= e($request['commune']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (! empty($request['wilaya'])): ?>
                            <tr>
                                <td class="text-muted">Wilaya</td>
                                <td><?= e($request['wilaya']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (! empty($request['address'])): ?>
                            <tr>
                                <td class="text-muted">Adresse</td>
                                <td><?= e($request['address']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (! empty($request['description'])): ?>
                            <tr>
                                <td class="text-muted">Description</td>
                                <td><?= nl2br(e($request['description'])) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (! empty($request['approval_file'])): ?>
                            <tr>
                                <td class="text-muted">Document agrément</td>
                                <td>
                                    <a href="<?= asset('/' . $request['approval_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-file-download me-1"></i>Voir le document
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Infos président -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="mdi mdi-account me-1"></i> Informations du président
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:45%">Nom complet</td>
                            <td class="fw-semibold"><?= e($request['president_firstname'] . ' ' . $request['president_lastname']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Téléphone</td>
                            <td><?= e($request['president_phone']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td><?= e($request['president_email']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Historique -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="mdi mdi-clock-outline me-1"></i> Historique
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:200px">Soumise le</td>
                            <td><?= e($request['created_at']) ?></td>
                        </tr>
                        <?php if (! empty($request['processed_at'])): ?>
                            <tr>
                                <td class="text-muted">Traitée le</td>
                                <td><?= e($request['processed_at']) ?></td>
                            </tr>
                            <?php if (! empty($request['rejection_reason'])): ?>
                                <tr>
                                    <td class="text-muted">Motif du refus</td>
                                    <td class="text-danger"><?= nl2br(e($request['rejection_reason'])) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <?php if ($isPending): ?>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="mdi mdi-close-circle me-1"></i>Refuser
            </button>
            <form method="post" action="<?= url('admin/association-requests/' . (int) $request['id'] . '/approve') ?>" class="d-inline"
                  onsubmit="return confirm('Valider cette demande et créer le compte président ?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success">
                    <i class="mdi mdi-check-circle me-1"></i>Valider
                </button>
            </form>
        </div>

        <!-- Modal refus -->
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" action="<?= url('admin/association-requests/' . (int) $request['id'] . '/reject') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rejectModalLabel">
                                <i class="mdi mdi-close-circle text-danger me-2"></i>Refuser la demande
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">
                                Veuillez indiquer le motif du refus pour la demande de <strong><?= e($request['association_name']) ?></strong>.
                            </p>
                            <div class="mb-3">
                                <label for="rejection_reason" class="form-label fw-medium">Motif du refus <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4"
                                          required placeholder="Décrivez les raisons du refus..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="mdi mdi-close-circle me-1"></i>Confirmer le refus
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
