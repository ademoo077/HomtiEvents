<?php
/** @var array $intervention @var array $participants @var array $preuvesAvant @var array $preuvesApres */
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;
use App\Helpers\Gamification;

$title = e(mb_substr((string) ($intervention['evenement_adresse'] ?? ''), 0, 60));
$page  = 'epic.show';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$checkinUrl = network_url('checkin/' . $intervention['token_qr']);
$ptsParticipation = Gamification::POINTS_PARTICIPATION;

$interventionStatut = strtolower((string) ($intervention['intervention_statut'] ?? 'AFFECTE'));
$accepte = (string) ($intervention['accepte'] ?? 'EN_ATTENTE');
$cloture = (string) ($intervention['cloture'] ?? 'OUVERTE');
$isCloturee = $cloture === 'CLOTUREE';
$isAccepte = $accepte === 'ACCEPTE';
$isEnAttente = $accepte === 'EN_ATTENTE';
$isRefuse = $accepte === 'REFUSE';
$canAct = $isAccepte && ! $isCloturee;

$badgeClass = match ($interventionStatut) {
    'en_cours' => 'bg-warning text-dark',
    'termine'  => 'bg-success',
    'anomalie' => 'bg-danger',
    default    => 'bg-secondary',
};
$pillColor = match ($interventionStatut) {
    'en_cours' => 'text-warning',
    'termine'  => 'text-success',
    'anomalie' => 'text-danger',
    default    => 'text-secondary',
};
$pillIcon = match ($interventionStatut) {
    'en_cours' => 'mdi-progress-wrench',
    'termine'  => 'mdi-check-circle',
    'anomalie' => 'mdi-alert-octagon',
    default    => 'mdi-clipboard-check-outline',
};
$acceptBadge = $isEnAttente ? ['text-warning', 'mdi-clock-alert', __('epic.pending_badge')] : ($isAccepte ? ['text-success', 'mdi-check-decagram', __('epic.accepted_badge')] : ['text-danger', 'mdi-close-octagon', __('epic.refused_badge')]);

$statutPills = [
    ['code' => 'AFFECTE',  'label' => __('epic.statut_affecte'),  'cls' => 'btn-outline-secondary'],
    ['code' => 'EN_COURS', 'label' => __('epic.statut_en_cours'), 'cls' => 'btn-outline-warning'],
    ['code' => 'ANOMALIE', 'label' => __('epic.statut_anomalie'), 'cls' => 'btn-outline-danger'],
];

$latitude  = (string) ($intervention['latitude'] ?? '');
$longitude = (string) ($intervention['longitude'] ?? '');
$hasMap = $latitude !== '' && $longitude !== '';
$fmtDate = static fn ($v): string => $v != null && $v !== '' ? date('d/m/Y H:i', strtotime((string) $v)) : '—';
?>
<div class="wh-page">
    <div class="wh-hero" style="background: linear-gradient(135deg, #0891B2 0%, #0B5ED7 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-map-marker-radius me-2"></i><?= e($intervention['evenement_adresse'] ?? 'Intervention') ?></h1>
                    <p class="wh-hero-sub">
                        <?= e($intervention['association_nom'] ?? '-') ?> •
                        <?= e($intervention['commune_nom'] ?? '-') ?>
                    </p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-outline-light" href="<?= url('epic/agenda') ?>">
                        <i class="mdi mdi-calendar-month me-1"></i><?= $isAr ? 'الأجندة' : 'Agenda' ?>
                    </a>
                    <a class="btn btn-outline-light" href="<?= url('epic') ?>">
                        <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <!-- Détails -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header">
                    <span><i class="mdi mdi-information-outline me-1"></i><?= e(__('common.details')) ?></span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="wh-statut-pill <?= $pillColor ?>">
                            <i class="mdi <?= $pillIcon ?>"></i>
                            <?= e(__('epic.statut_' . $interventionStatut)) ?>
                        </span>
                        <span class="badge <?= $badgeClass ?>">
                            <?= e(statut_label((string) ($intervention['evenement_statut'] ?? 'EN_ATTENTE'))) ?>
                        </span>
                        <span class="wh-statut-pill <?= $acceptBadge[0] ?>">
                            <i class="mdi <?= $acceptBadge[1] ?>"></i> <?= e($acceptBadge[2]) ?>
                        </span>
                        <span class="badge <?= $isCloturee ? 'bg-success' : 'bg-secondary' ?>">
                            <i class="mdi <?= $isCloturee ? 'mdi-lock-check' : 'mdi-lock-open-variant' ?>"></i>
                            <?= e($isCloturee ? __('epic.closed_badge') : __('epic.open_badge')) ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong><?= e(__('common.description')) ?>:</strong>
                        <p class="mb-0"><?= nl2br(e($intervention['description'] ?? '-')) ?></p>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <i class="mdi mdi-calendar-month"></i>
                                <strong><?= e(__('common.date')) ?>:</strong>
                                <?= ($intervention['date_evenement'] ?? '') ? e(date('d/m/Y', strtotime((string) $intervention['date_evenement']))) : '—' ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <i class="mdi mdi-clock-outline"></i>
                                <strong><?= e(__('common.heure')) ?>:</strong> <?= e($intervention['heure'] ?? '-') ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <i class="mdi mdi-map-marker-outline"></i>
                                <strong><?= $isAr ? 'البلدية' : 'Commune' ?>:</strong> <?= e($intervention['commune_nom'] ?? '-') ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <i class="mdi mdi-account-tie"></i>
                                <strong><?= $isAr ? 'الجمعية' : 'Association' ?>:</strong> <?= e($intervention['association_nom'] ?? '-') ?>
                            </div>
                        </div>
                        <?php if ($isAccepte): ?>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="mdi mdi-play-circle-outline"></i>
                                    <strong><?= e(__('epic.date_debut_reel')) ?>:</strong> <?= e($fmtDate($intervention['date_debut_reel'] ?? null)) ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="mdi mdi-stop-circle-outline"></i>
                                    <strong><?= e(__('epic.date_fin_reel')) ?>:</strong> <?= e($fmtDate($intervention['date_fin_reel'] ?? null)) ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="mdi mdi-calendar-check-outline"></i>
                                    <strong><?= e(__('epic.date_cloture')) ?>:</strong> <?= e($fmtDate($intervention['date_cloture'] ?? null)) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Carte -->
            <?php if ($hasMap): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header">
                        <span><i class="mdi mdi-map-outline me-1"></i><?= e(__('epic.map_title')) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div id="epicMap" style="height:260px;min-height:200px"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Acceptation / Refus -->
            <?php if ($isEnAttente): ?>
                <div class="card border-0 shadow-sm mb-3 border-start border-4 border-warning">
                    <div class="card-header">
                        <span><i class="mdi mdi-handshake-outline me-1"></i><?= e(__('epic.pending_acceptation')) ?></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3"><?= $isAr ? 'هل تقبل هذه التدخلية الموكلة إليك؟' : 'Souhaitez-vous accepter cette intervention qui vous a été affectée ?' ?></p>
                        <div class="d-flex flex-wrap gap-2">
                            <form action="<?= url('epic/' . (int) ($intervention['intervention_id'] ?? 0) . '/accepter') ?>" method="post"
                                  data-ajax-accept data-confirm="<?= e(__('epic.accept_confirm')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success">
                                    <i class="mdi mdi-check-bold me-1"></i><?= e(__('epic.accept_btn')) ?>
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#refuseModal">
                                <i class="mdi mdi-close-thick me-1"></i><?= e(__('epic.refuse_btn')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php elseif ($isRefuse): ?>
                <div class="card border-0 shadow-sm mb-3 border-start border-4 border-danger">
                    <div class="card-header">
                        <span><i class="mdi mdi-close-octagon-outline me-1"></i><?= e(__('epic.refused_badge')) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (! empty($intervention['motif_refus'])): ?>
                            <div class="mb-2">
                                <strong><?= e(__('epic.refused_motif')) ?>:</strong>
                                <p class="mb-0"><?= nl2br(e($intervention['motif_refus'])) ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="text-muted small">
                            <i class="mdi mdi-calendar-clock me-1"></i><?= e(__('epic.date_acceptation')) ?>: <?= e($fmtDate($intervention['date_acceptation'] ?? null)) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Workflow intervention (acceptée, non clôturée) -->
            <?php if ($canAct): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header">
                        <span><i class="mdi mdi-progress-wrench"></i> <?= e(__('epic.intervention_statut')) ?></span>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('epic/' . (int) ($intervention['intervention_id'] ?? 0) . '/statut') ?>" method="post"
                              data-confirm="<?= e(__('epic.status_confirm')) ?>"
                              class="d-flex flex-column gap-3">
                            <?= csrf_field() ?>
                            <div class="wh-statut-switch">
                                <?php foreach ($statutPills as $p): ?>
                                    <input type="radio" class="btn-check" name="statut" id="st-<?= e($p['code']) ?>"
                                           value="<?= e($p['code']) ?>"
                                           <?= (($intervention['intervention_statut'] ?? '') === $p['code']) ? 'checked' : '' ?>>
                                    <label class="btn <?= $p['cls'] ?>" for="st-<?= e($p['code']) ?>">
                                        <?= e($p['label']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-primary align-self-start">
                                <i class="mdi mdi-content-save-outline me-1"></i><?= $isAr ? 'حفظ الحالة' : 'Enregistrer le statut' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Preuves avant / après -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header">
                        <span><i class="mdi mdi-camera-outline me-1"></i><?= e(__('epic.preuves')) ?></span>
                    </div>
                    <div class="card-body">
                        <?php foreach (['AVANT' => __('epic.preuve_avant'), 'APRES' => __('epic.preuve_apres')] as $typeCode => $typeLabel): ?>
                            <?php $liste = $typeCode === 'AVANT' ? $preuvesAvant : $preuvesApres; ?>
                            <div class="<?= $typeCode === 'APRES' ? 'mt-4' : '' ?>">
                                <h6 class="fw-semibold"><?= e($typeLabel) ?> <span class="badge bg-secondary"><?= count($liste) ?></span></h6>
                                <form action="<?= url('epic/' . (int) ($intervention['intervention_id'] ?? 0) . '/preuves') ?>" method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="type" value="<?= e($typeCode) ?>">
                                    <input type="file" name="preuves[]" accept="image/*" multiple class="form-control form-control-sm" style="max-width:280px">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-upload me-1"></i><?= e($typeCode === 'AVANT' ? __('epic.upload_avant') : __('epic.upload_apres')) ?>
                                    </button>
                                </form>
                                <div class="row g-2">
                                    <?php foreach ($liste as $preuve): ?>
                                        <div class="col-6 col-md-4 col-xl-3">
                                            <div class="position-relative">
                                                <img src="<?= e(url((string) $preuve['fichier'])) ?>" alt="<?= basename((string) $preuve['fichier']) ?>" class="img-fluid rounded" loading="lazy"
                                                     style="height:110px;width:100%;object-fit:cover">
                                                <form action="<?= url('epic/' . (int) ($intervention['intervention_id'] ?? 0) . '/preuves/' . (int) $preuve['id'] . '/delete') ?>" method="post"
                                                      data-confirm="<?= e(__('epic.delete_preuve')) ?>"
                                                      class="position-absolute top-0 end-0 m-1">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger btn-sm-ico" title="<?= e(__('epic.delete_preuve')) ?>">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <small class="text-muted d-block text-truncate"><?= e(($preuve['prenom'] ?? '') . ' ' . ($preuve['nom'] ?? '')) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($liste)): ?>
                                        <div class="col-12 text-muted small"><i class="mdi mdi-image-off-outline me-1"></i><?= e(__('epic.no_preuves')) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Clôture -->
                <div class="card border-0 shadow-sm mb-3 border-start border-4 border-success">
                    <div class="card-header">
                        <span><i class="mdi mdi-lock-check-outline me-1"></i><?= e(__('epic.close_intervention')) ?></span>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('epic/' . (int) ($intervention['intervention_id'] ?? 0) . '/cloturer') ?>" method="post"
                              data-ajax-cloturer data-confirm="<?= e(__('epic.close_confirm')) ?>" class="d-flex flex-column gap-2">
                            <?= csrf_field() ?>
                            <label class="form-label mb-0 small"><?= e(__('epic.rapport_label')) ?> *</label>
                            <textarea name="rapport" class="form-control" rows="4" placeholder="<?= e(__('epic.rapport_placeholder')) ?>"></textarea>
                            <button type="submit" class="btn btn-success align-self-start">
                                <i class="mdi mdi-lock-check me-1"></i><?= e(__('epic.close_intervention')) ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif ($isCloturee): ?>
                <!-- Rapport de clôture -->
                <div class="card border-0 shadow-sm mb-3 border-start border-4 border-success">
                    <div class="card-header">
                        <span><i class="mdi mdi-file-document-check-outline me-1"></i><?= e(__('epic.rapport')) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (! empty($intervention['rapport'])): ?>
                            <p class="mb-2"><?= nl2br(e($intervention['rapport'])) ?></p>
                        <?php else: ?>
                            <p class="text-muted small mb-0"><?= $isAr ? 'لا يوجد تقرير.' : 'Aucun rapport.' ?></p>
                        <?php endif; ?>
                        <div class="text-muted small mt-2">
                            <i class="mdi mdi-calendar-check me-1"></i><?= e(__('epic.date_cloture')) ?>: <?= e($fmtDate($intervention['date_cloture'] ?? null)) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- QR code -->
            <?php if (! empty($intervention['token_qr'])): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header">
                        <span><i class="mdi mdi-qrcode-scan"></i> <?= $isAr ? 'رمز QR للتمثيل' : 'QR code de présence' ?></span>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center py-4">
                        <img src="<?= e(QrCodeGenerator::pngDataUri($checkinUrl, 240)) ?>" alt="QR code" class="img-fluid rounded" style="max-width:240px">
                        <code class="text-muted small mt-3 text-break" style="max-width:100%"><?= e($checkinUrl) ?></code>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php if (! empty($intervention['association_nom'])): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header">
                        <span><i class="mdi mdi-account-tie-outline"></i> <?= e(__('epic.association_contact')) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="fw-semibold mb-1"><?= e($intervention['association_nom']) ?></div>
                        <?php if (! empty($intervention['association_president'])): ?>
                            <div class="small text-muted mb-1">
                                <i class="mdi mdi-account-outline me-1"></i><?= e($intervention['association_president']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($intervention['association_telephone'])): ?>
                            <div class="small mb-1">
                                <i class="mdi mdi-phone-outline me-1"></i>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', (string) $intervention['association_telephone'])) ?>">
                                    <?= e($intervention['association_telephone']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($intervention['association_email'])): ?>
                            <div class="small">
                                <i class="mdi mdi-email-outline me-1"></i>
                                <a href="mailto:<?= e($intervention['association_email']) ?>"><?= e($intervention['association_email']) ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="mdi mdi-account-group"></i> <?= e(__('common.participants')) ?></span>
                    <span class="wh-badge badge-soft"><?= count($participants) ?></span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($participants as $p): ?>
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <div class="wh-avatar-pts" data-bs-toggle="tooltip" title="<?= $ptsParticipation ?> <?= $isAr ? 'نقطة' : 'points' ?>">
                                    <?= e(mb_strtoupper(mb_substr((string) ($p['prenom'] ?? '?'), 0, 1))) ?>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold"><?= e(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? '')) ?></div>
                                    <small class="text-muted">
                                        <i class="mdi mdi-check-decagram text-success"></i>
                                        <?= ($p['heure_scan'] ?? '') ? e(date('d/m/Y H:i', strtotime((string) $p['heure_scan']))) : '—' ?>
                                    </small>
                                </div>
                                <span class="text-success small fw-semibold">
                                    <i class="mdi mdi-plus-circle-outline"></i> <?= $ptsParticipation ?> pts
                                </span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($participants)): ?>
                            <li class="list-group-item text-center text-muted py-4">
                                <i class="mdi mdi-account-outline mdi-24px"></i>
                                <p class="mb-0 mt-1"><?= $isAr ? 'لا يوجد مشاركون بعد.' : 'Aucun participant pour le moment.' ?></p>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal refuse -->
<div class="modal fade" id="refuseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('epic/' . (int) ($intervention['intervention_id'] ?? 0) . '/refuser') ?>" method="post" data-ajax-refuser>
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= e(__('epic.refuse_btn')) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label"><?= e(__('epic.refused_motif')) ?> *</label>
                    <textarea name="motif" class="form-control" rows="3" required placeholder="<?= e(__('epic.refuse_placeholder')) ?>"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(__('common.cancel')) ?></button>
                    <button type="submit" class="btn btn-danger"><?= e(__('epic.refuse_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($hasMap): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script>
(function(){
    var csrfToken = window.WH_CSRF || '<?= csrf_token() ?>';

    function post(btn, action){
        return fetch(action, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: new FormData(btn.closest('form'))
        }).then(function(r){ return r.json().catch(function(){ return {success:false, error:'Erreur serveur'}; }); });
    }

    function notify(success, msg){
        var el = document.getElementById('whToastHost') || document.body;
        var d = document.createElement('div');
        d.className = 'toast align-items-center text-bg-' + (success ? 'success' : 'danger') + ' border-0 wh-toast';
        d.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        el.appendChild(d);
        var t = new bootstrap.Toast(d, {delay: 3000});
        t.show();
        d.addEventListener('hidden.bs.toast', function(){ d.remove(); });
    }

    document.querySelectorAll('form[data-ajax-accept]').forEach(function(f){
        f.addEventListener('submit', function(e){
            e.preventDefault();
            if (this.dataset.confirm && ! window.confirm(this.dataset.confirm)) return;
            post(this.querySelector('button'), this.action).then(function(res){
                if (res.success) { notify(true, res.message || 'OK'); setTimeout(function(){ location.reload(); }, 600); }
                else notify(false, res.error || 'Erreur');
            });
        });
    });

    document.querySelectorAll('form[data-ajax-refuser]').forEach(function(f){
        f.addEventListener('submit', function(e){
            e.preventDefault();
            var modal = bootstrap.Modal.getInstance(document.getElementById('refuseModal'));
            post(this.querySelector('button[type=submit]'), this.action).then(function(res){
                if (modal) modal.hide();
                if (res.success) { notify(true, res.message || 'OK'); setTimeout(function(){ location.reload(); }, 600); }
                else notify(false, res.error || 'Erreur');
            });
        });
    });

    document.querySelectorAll('form[data-ajax-cloturer]').forEach(function(f){
        f.addEventListener('submit', function(e){
            e.preventDefault();
            if (this.dataset.confirm && ! window.confirm(this.dataset.confirm)) return;
            var rapport = this.querySelector('textarea[name=rapport]').value.trim();
            if (!rapport) { notify(false, '<?= e(__('epic.rapport_required')) ?>'); return; }
            post(this.querySelector('button[type=submit]'), this.action).then(function(res){
                if (res.success) { notify(true, res.message || 'OK'); setTimeout(function(){ location.reload(); }, 800); }
                else notify(false, res.error || 'Erreur');
            });
        });
    });

    <?php if ($hasMap): ?>
    var lat = parseFloat('<?= e($latitude) ?>');
    var lng = parseFloat('<?= e($longitude) ?>');
    if (!isNaN(lat) && !isNaN(lng)) {
        var map = L.map('epicMap', {scrollWheelZoom:false}).setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OSM', maxZoom:19}).addTo(map);
        L.marker([lat, lng]).addTo(map)
            .bindPopup('<?= e(str_replace("'", "\\'", (string) ($intervention['evenement_adresse'] ?? ''))) ?>')
            .openPopup();
    }
    <?php endif; ?>
})();
</script>

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem}.wh-avatar-pts{width:38px;height:38px;flex-shrink:0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;background:linear-gradient(135deg,#0891B2,#0B5ED7);font-size:.9rem}.btn-sm-ico{padding:.15rem .35rem;font-size:.75rem}.wh-toast{position:fixed;top:20px;inset-inline-end:20px;z-index:2000;min-width:260px}</style>
