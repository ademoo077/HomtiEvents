<?php
/**
 * Demande d'inscription association — Formulaire de modification (admin Wilaya).
 *
 * @var array $request
 * @var array $errors
 * @var array $old
 */
use App\Helpers\I18n;

$title = 'Modifier la demande #' . (int) $request['id'];
$page  = 'wilaya.association-requests.index';
$dir   = I18n::direction();

$val = static function (string $key, mixed $default = '') use ($request, $old): string {
    return (string) ($old[$key] ?? $request[$key] ?? $default);
};
?>
<div class="wh-page">
    <div class="wh-hero" style="background: linear-gradient(135deg, #0B5ED7 0%, #6C63FF 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-pencil-outline me-2"></i>Modifier la demande #<?= (int) $request['id'] ?></h1>
                    <p class="wh-hero-sub"><?= e($request['association_name']) ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-light" href="<?= url('admin/association-requests/' . (int) $request['id']) ?>">
                        <i class="mdi mdi-arrow-left me-1"></i>Retour au détail
                    </a>
                    <a class="btn btn-outline-light" href="<?= url('admin/association-requests') ?>">
                        <i class="mdi mdi-view-list me-1"></i>Liste
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="<?= url('admin/association-requests/' . (int) $request['id'] . '/update') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <?php if (! empty($errors)): ?>
            <div class="alert alert-danger small">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e(is_string($err) ? $err : 'Erreur de saisie.') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Infos association -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="mdi mdi-office-building me-1"></i> Informations de l'association
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="association_name">Nom de l'association <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="association_name" name="association_name"
                                   value="<?= e($val('association_name')) ?>" required maxlength="150">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="approval_number">N° agrément</label>
                                <input type="text" class="form-control" id="approval_number" name="approval_number"
                                       value="<?= e($val('approval_number')) ?>" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="activity_domain">Domaine d'activité</label>
                                <input type="text" class="form-control" id="activity_domain" name="activity_domain"
                                       value="<?= e($val('activity_domain')) ?>" maxlength="100">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000"><?= e($val('description')) ?></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="address">Adresse</label>
                                <input type="text" class="form-control" id="address" name="address"
                                       value="<?= e($val('address')) ?>" maxlength="255">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="commune">Commune</label>
                                <input type="text" class="form-control" id="commune" name="commune"
                                       value="<?= e($val('commune')) ?>" maxlength="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="wilaya">Wilaya</label>
                                <input type="text" class="form-control" id="wilaya" name="wilaya"
                                       value="<?= e($val('wilaya')) ?>" maxlength="100">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?= e($val('phone')) ?>" placeholder="+213 ..." maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= e($val('email')) ?>" maxlength="100">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="website">Site web</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   value="<?= e($val('website')) ?>" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="approval_file">Document d'agrément</label>
                            <?php if (! empty($request['approval_file'])): ?>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <a href="<?= asset('/' . $request['approval_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-file-download me-1"></i>Document actuel
                                    </a>
                                    <span class="text-muted small">Remplacer le fichier ci-dessous.</span>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="approval_file" name="approval_file" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Infos président -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="mdi mdi-account me-1"></i> Informations du président
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="president_firstname">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="president_firstname" name="president_firstname"
                                       value="<?= e($val('president_firstname')) ?>" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="president_lastname">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="president_lastname" name="president_lastname"
                                       value="<?= e($val('president_lastname')) ?>" required maxlength="100">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="president_birthdate">Date de naissance</label>
                                <input type="date" class="form-control" id="president_birthdate" name="president_birthdate"
                                       value="<?= e($val('president_birthdate')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="president_phone">Téléphone</label>
                                <input type="tel" class="form-control" id="president_phone" name="president_phone"
                                       value="<?= e($val('president_phone')) ?>" placeholder="+213 ..." maxlength="20">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="president_email">Email</label>
                            <input type="email" class="form-control" id="president_email" name="president_email"
                                   value="<?= e($val('president_email')) ?>" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="president_address">Adresse</label>
                            <input type="text" class="form-control" id="president_address" name="president_address"
                                   value="<?= e($val('president_address')) ?>" maxlength="255">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="president_id_type">Type de pièce d'identité</label>
                                <select class="form-select" id="president_id_type" name="president_id_type">
                                    <?php $idType = $val('president_id_type'); ?>
                                    <option value="">—</option>
                                    <?php foreach (['CNI', 'Passeport', 'Permis de conduire', 'Autre'] as $opt): ?>
                                        <option value="<?= e($opt) ?>" <?= $idType === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="president_id_number">N° pièce d'identité</label>
                                <input type="text" class="form-control" id="president_id_number" name="president_id_number"
                                       value="<?= e($val('president_id_number')) ?>" maxlength="50">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            <i class="mdi mdi-information-outline me-1"></i>
                            La modification ne rejoue pas la validation : si la demande est en attente, elle reste en attente.
                            <?php if ($request['status'] === 'approved'): ?>
                                Le compte président et l'association déjà créés ne sont pas impactés.
                            <?php endif; ?>
                        </p>
                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-outline-secondary" href="<?= url('admin/association-requests/' . (int) $request['id']) ?>">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i>Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem}
    </style>
</div>
