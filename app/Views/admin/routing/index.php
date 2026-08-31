<?php
/**
 * Gestion des règles de routage (organisation_rules).
 *
 * @var array $rules @var string $q @var int $page @var int $lastPage @var int $total
 * @var array $anomalies @var array $ca @var array $epics @var array|null $editing @var array|null $errors @var string|null $success
 */
$title = 'Règles de routage';
$page  = 'admin.routing.index';
?>
<style>
.wh-routing-hero{background:linear-gradient(135deg,#6d28d9 0%,#8B5CF6 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden}
.wh-routing-hero::before{content:"";position:absolute;top:-40%;right:-5%;width:300px;height:300px;background:rgba(255,255,255,.07);border-radius:50%}
.wh-routing-hero h1{position:relative;z-index:1;margin:0}
.wh-routing-hero p{position:relative;z-index:1;opacity:.85}
.wh-routing-hero .btn{position:relative;z-index:1}
@media(max-width:767.98px){.wh-routing-hero{padding:1.25rem 1rem}.wh-routing-hero h1{font-size:1.2rem}}
</style>

<div class="wh-page">
    <div class="wh-routing-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-router-network-outline"></i>
                    Règles de routage
                </h1>
                <p class="mt-1 mb-0">Priorité 1 : anomalie → organisation · Priorité 2 : anomalie + daira · Sinon : alerte admin.</p>
            </div>
            <a class="btn btn-light" href="<?= url('admin/routing?open=1') ?>">
                <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
            </a>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" data-autohide><i class="mdi mdi-check me-1"></i><?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (! empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" data-autohide>
        <ul class="mb-0">
            <?php foreach (is_array($errors) ? $errors : [$errors] as $err): ?>
                <li><?= e(is_string($err) ? $err : 'Erreur de saisie.') ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="get" action="<?= url('admin/routing') ?>" class="wh-filters mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Rechercher (anomalie, EPIC, daira)..." value="<?= e($q) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-filter-variant me-1"></i>Filtrer</button>
                <a href="<?= url('admin/routing') ?>" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-refresh"></i></a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-header" style="background:rgba(109,40,217,.08);border-bottom:2px solid #6d28d9"><h6 class="mb-0 fw-bold" style="color:#6d28d9"><i class="mdi mdi-router-network-outline me-2"></i><?= e(count($rules)) ?> règle(s) de routage</h6></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Priorité</th>
                    <th>Type d'anomalie</th>
                    <th>Daira</th>
                    <th>Organisation</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rules as $r): ?>
                    <tr>
                        <td><span class="fw-semibold"><?= (int) $r['priorite'] ?></span></td>
                        <td><?= e($r['anomalie_nom'] ?? '<span class="text-muted">Toutes</span>') ?></td>
                        <td><?= e($r['ca_nom'] ?? '<span class="text-muted">Toutes</span>') ?></td>
                        <td class="fw-semibold"><?= e($r['epic_nom'] ?? '—') ?></td>
                         <td>
                            <form method="post" action="<?= url('admin/routing/' . (int) $r['id'] . '/toggle') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit"
                                        class="btn btn-sm <?= ((int) $r['actif']) ? 'btn-outline-success' : 'btn-outline-secondary' ?>">
                                    <?= ((int) $r['actif']) ? '<i class="mdi mdi-eye-outline me-1"></i>Actif' : '<i class="mdi mdi-eye-off-outline me-1"></i>Inactif' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/routing/' . (int) $r['id'] . '/edit?open=1') ?>" title="Modifier"><i class="mdi mdi-pencil"></i></a>
                                <form method="post" action="<?= url('admin/routing/' . (int) $r['id'] . '/delete') ?>" data-confirm="<?= e(__('common.confirm_delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="mdi mdi-delete"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rules === []): ?>
                    <tr><td colspan="6"><div class="wh-empty"><i class="mdi mdi-router-network-outline"></i><p>Aucune règle de routage.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($lastPage > 1): ?>
    <nav class="d-flex justify-content-center mt-4" aria-label="Pagination">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('admin/routing?page=' . $i . ($q !== '' ? '&q=' . urlencode($q) : '')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal création / édition -->
<div class="modal fade" id="ruleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e($editing ? url('admin/routing/' . (int) $editing['id'] . '/update') : url('admin/routing')) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editing ? 'Modifier la règle' : 'Nouvelle règle' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="page" value="<?= (int) $page ?>">
                    <div class="mb-3">
                        <label class="form-label">Type d'anomalie</label>
                        <select class="form-select" name="anomalie_id">
                            <option value="0">Toutes (générale)</option>
                            <?php foreach ($anomalies as $a): ?>
                                <?php $sel = ! empty($editing) && (int) $editing['anomalie_id'] === (int) $a['id']; ?>
                                <option value="<?= (int) $a['id'] ?>" <?= $sel ? 'selected' : '' ?>><?= e($a['nom']) ?></option>
                            <?php endforeach; ?>
                         </select>
                        <div class="form-text">Une règle sur le type d'anomalie (sans daira) prédomine sur la règle daira.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Daira (optionnelle)</label>
                        <select class="form-select" name="ca_id">
                            <option value="0">Toutes les daires</option>
                            <?php foreach ($ca as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (! empty($editing) && (int) $editing['ca_id'] === (int) $c['id']) ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Organisation *</label>
                        <select class="form-select" name="epic_id" required>
                            <?php foreach ($epics as $e): ?>
                                <option value="<?= (int) $e['id'] ?>" <?= (! empty($editing) && (int) $editing['epic_id'] === (int) $e['id']) ? 'selected' : '' ?>><?= e($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Priorité</label>
                            <input type="number" name="priorite" class="form-control" value="<?= (int) ($editing['priorite'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif" value="1" <?= (! empty($editing) && (int) $editing['actif'] === 1) || empty($editing) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="actif">Actif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><?= $editing ? 'Enregistrer' : 'Créer' ?></button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('ruleModal');
    if (!modal) return;
    if (new URLSearchParams(window.location.search).has('open')) {
        var bs = new bootstrap.Modal(modal);
        bs.show();
    }
})();
</script>
