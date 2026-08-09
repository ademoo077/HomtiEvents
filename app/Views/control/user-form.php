<?php
/**
 * Formulaire création / édition d'un compte (citoyen · président d'association).
 *
 * @var string $mode        'create' | 'edit'
 * @var array|null $user
 * @var array $roles        rôles disponibles (citoyen, association)
 * @var array $associations
 * @var array $errors
 * @var array $old
 */
use App\Helpers\I18n;

$isAr  = I18n::direction() === 'rtl';
$editing = $mode === 'edit';
$oldVal = static fn (string $key, mixed $default = '') => (string) ($old[$key] ?? $default);
$userVal = static fn (string $key, mixed $default = '') => (string) ($user[$key] ?? $default);
$action = $editing
    ? url('control/utilisateurs/' . (int) $user['id'] . '/update')
    : url('control/utilisateurs');
?>
<div class="futur-control">
    <div class="futur-control-header">
        <div>
            <h2 class="futur-control-title">
                <i class="mdi mdi-account-plus-outline"></i>
                <?= $editing ? ($isAr ? 'تعديل الحساب' : 'Modifier le compte') : ($isAr ? 'حساب جديد' : 'Nouveau compte') ?>
            </h2>
            <p class="futur-control-sub">
                <?= $isAr ? 'إنشاء أو تعديل حساب مواطن / رئيس جمعية' : 'Création ou édition d\'un compte citoyen / président d\'association' ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= url('control/utilisateurs') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= $action ?>">
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

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="prenom"><?= $isAr ? 'الاسم الأول' : 'Prénom' ?> *</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?= e($editing ? $userVal('prenom') : $oldVal('prenom')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="nom"><?= $isAr ? 'الاسم العائلي' : 'Nom' ?> *</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?= e($editing ? $userVal('nom') : $oldVal('nom')) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="email"><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?> *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($editing ? $userVal('email') : $oldVal('email')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="telephone"><?= $isAr ? 'الهاتف' : 'Téléphone' ?></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= e($editing ? $userVal('telephone') : $oldVal('telephone')) ?>" placeholder="+213 ...">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="role_user"><?= $isAr ? 'الدور' : 'Rôle' ?> *</label>
                        <select class="form-select" id="role_user" name="role_user" required>
                            <?php foreach ($roles as $r): ?>
                                <?php
                                $sel = $editing
                                    ? ((string) ($user['role_user'] ?? '') === (string) $r['nom'] ? 'selected' : '')
                                    : (($oldVal('role_user', 'citoyen') === (string) $r['nom']) ? 'selected' : '');
                                ?>
                                <option value="<?= e($r['nom']) ?>" <?= $sel ?>>
                                    <?= $isAr
                                        ? (($r['nom'] === 'association') ? 'رئيس جمعية' : 'مواطن')
                                        : (($r['nom'] === 'association') ? 'Président d\'association' : 'Citoyen') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="association_id"><?= $isAr ? 'الجمعية' : 'Association' ?></label>
                        <select class="form-select" id="association_id" name="association_id">
                            <option value=""><?= $isAr ? '— بدون ربط —' : '— Non rattachée —' ?></option>
                            <?php foreach ($associations as $a): ?>
                                <?php
                                $sel = $editing
                                    ? ((int) ($user['association_id'] ?? 0) === (int) $a['id'] ? 'selected' : '')
                                    : ((int) $oldVal('association_id') === (int) $a['id'] ? 'selected' : '');
                                ?>
                                <option value="<?= (int) $a['id'] ?>" <?= $sel ?>><?= e($a['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?= $isAr ? 'فقط لحساب رئيس الجمعية' : 'Uniquement pour un compte président d\'association' ?></div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="password">
                            <?= $isAr ? 'كلمة المرور' : 'Mot de passe' ?>
                            <?= $editing ? '' : ' *' ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
                        <?php if ($editing): ?>
                            <div class="form-text"><?= $isAr ? 'اتركه فارغًا للإبقاء على كلمة المرور الحالية' : 'Laisser vide pour conserver le mot de passe actuel' ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (! $editing): ?>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation"><?= $isAr ? 'تأكيد كلمة المرور' : 'Confirmation' ?> *</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i><?= $editing ? ($isAr ? 'حفظ التعديلات' : 'Enregistrer') : ($isAr ? 'إنشاء الحساب' : 'Créer le compte') ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= url('control/utilisateurs') ?>"><?= $isAr ? 'إلغاء' : 'Annuler' ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var role = document.getElementById('role_user');
    var assoc = document.getElementById('association_id');
    if (!role || !assoc) return;

    function toggleAssoc() {
        var show = role.value === 'association';
        var wrap = assoc.closest('.col-md-6');
        if (wrap) {
            wrap.style.display = show ? '' : 'none';
        }
        assoc.required = show;
        if (!show) assoc.value = '';
    }
    role.addEventListener('change', toggleAssoc);
    toggleAssoc();
})();
</script>
