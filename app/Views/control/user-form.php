<?php
/**
 * Formulaire création / édition d'un compte (citoyen · président d'association).
 *
 * @var string $mode        'create' | 'edit'
 * @var array|null $user
 * @var array $roles        rôles disponibles (citoyen, association, epic)
 * @var array $associations
 * @var array $epics
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
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title">
                        <i class="mdi mdi-account-plus-outline me-2"></i>
                        <?= $editing ? ($isAr ? 'تعديل الحساب' : 'Modifier le compte') : ($isAr ? 'حساب جديد' : 'Nouveau compte') ?>
                    </h1>
                    <p class="wh-hero-sub">
                        <?= $isAr ? 'إنشاء أو تعديل حساب مواطن / رئيس جمعية' : 'Création ou édition d\'un compte citoyen / président d\'association' ?>
                    </p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-sm btn-outline-light" href="<?= url('control/utilisateurs') ?>">
                        <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="futur-card">
        <div class="futur-card-body">
            <form method="post" action="<?= $action ?>" novalidate>
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

                <div class="futur-form-row">
                    <div class="futur-form-group">
                        <label class="futur-form-label" for="prenom"><?= $isAr ? 'الاسم الأول' : 'Prénom' ?> <span class="required">*</span></label>
                        <input type="text" class="futur-form-control" id="prenom" name="prenom" value="<?= e($editing ? $userVal('prenom') : $oldVal('prenom')) ?>" required>
                    </div>
                    <div class="futur-form-group">
                        <label class="futur-form-label" for="nom"><?= $isAr ? 'الاسم العائلي' : 'Nom' ?> <span class="required">*</span></label>
                        <input type="text" class="futur-form-control" id="nom" name="nom" value="<?= e($editing ? $userVal('nom') : $oldVal('nom')) ?>" required>
                    </div>
                </div>

                <div class="futur-form-row">
                    <div class="futur-form-group">
                        <label class="futur-form-label" for="email"><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?> <span class="required">*</span></label>
                        <input type="email" class="futur-form-control" id="email" name="email" value="<?= e($editing ? $userVal('email') : $oldVal('email')) ?>" required>
                    </div>
                    <div class="futur-form-group">
                        <label class="futur-form-label" for="telephone"><?= $isAr ? 'الهاتف' : 'Téléphone' ?></label>
                        <input type="tel" class="futur-form-control" id="telephone" name="telephone" value="<?= e($editing ? $userVal('telephone') : $oldVal('telephone')) ?>" placeholder="+213 ...">
                    </div>
                </div>

                <div class="futur-form-row">
                    <div class="futur-form-group">
                        <label class="futur-form-label" for="role_user"><?= $isAr ? 'الدور' : 'Rôle' ?> <span class="required">*</span></label>
                        <select class="futur-form-control" id="role_user" name="role_user" required>
                            <?php foreach ($roles as $r): ?>
                                <?php
                                $sel = $editing
                                    ? ((string) ($user['role_user'] ?? '') === (string) $r['nom'] ? 'selected' : '')
                                    : (($oldVal('role_user', 'citoyen') === (string) $r['nom']) ? 'selected' : '');
                                ?>
                                <option value="<?= e($r['nom']) ?>" <?= $sel ?>>
                                    <?= $isAr
                                        ? match ($r['nom']) {
                                            'association' => 'رئيس جمعية',
                                            'epic'        => 'مؤسسة عامة',
                                            default       => 'مواطن',
                                        }
                                        : match ($r['nom']) {
                                            'association' => 'Président d\'association',
                                            'epic'        => 'EPIC (compte institutionnel)',
                                            default       => 'Citoyen',
                                        } ?>
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

                <div class="row g-3 mb-3" id="epic-row" style="display:none;">
                    <div class="col-md-6">
                        <label class="form-label" for="epic_id"><?= $isAr ? 'المؤسسة' : 'EPIC' ?> *</label>
                        <select class="form-select" id="epic_id" name="epic_id">
                            <option value=""><?= $isAr ? '— اختر مؤسسة —' : '— Sélectionner une EPIC —' ?></option>
                            <?php foreach ($epics as $e): ?>
                                <?php
                                $sel = $editing
                                    ? ((int) ($user['epic_id'] ?? 0) === (int) $e['id'] ? 'selected' : '')
                                    : ((int) $oldVal('epic_id') === (int) $e['id'] ? 'selected' : '');
                                ?>
                                <option value="<?= (int) $e['id'] ?>" <?= $sel ?>><?= e($e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?= $isAr ? 'حساب مرتبط بهذه المؤسسة' : 'Compte rattaché à une EPIC (rôle institutionnel)' ?></div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="password">
                            <?= $isAr ? 'كلمة المرور' : 'Mot de passe' ?>
                            <?= $editing ? '' : ' *' ?>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
                            <?php if (! $editing): ?>
                            <button type="button" class="btn btn-outline-secondary" id="genPassword" title="<?= $isAr ? 'توليد كلمة مرور عشوائية' : 'Générer un mot de passe aléatoire' ?>">
                                <i class="mdi mdi-shuffle"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($editing): ?>
                            <div class="form-text"><?= $isAr ? 'اتركه فارغًا للإبقاء على كلمة المرور الحالية' : 'Laisser vide pour conserver le mot de passe actuel' ?></div>
                        <?php else: ?>
                            <div class="form-text" id="pwdNotice" style="display:none;">
                                <i class="mdi mdi-alert-circle-outline me-1"></i><?= $isAr ? 'كلمة المرور تم توليدها — انسخها الآن، لن تظهر مرة أخرى' : 'Mot de passe généré — copiez-le maintenant, il ne sera plus affiché' ?>
                            </div>
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
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>

<script>
(function () {
    'use strict';
    var role   = document.getElementById('role_user');
    var assoc  = document.getElementById('association_id');
    var epic   = document.getElementById('epic_id');
    var assocWrap = assoc ? assoc.closest('.col-md-6') : null;
    var epicWrap = document.getElementById('epic-row');
    if (!role) return;

    function toggle() {
        var v = role.value;
        // Association
        if (assocWrap) assocWrap.style.display = v === 'association' ? '' : 'none';
        if (assoc) { assoc.required = v === 'association'; if (v !== 'association') assoc.value = ''; }
        // EPIC
        if (epicWrap) epicWrap.style.display = v === 'epic' ? '' : 'none';
        if (epic) { epic.required = v === 'epic'; if (v !== 'epic') epic.value = ''; }
    }
    role.addEventListener('change', toggle);
    toggle();

    // Password generator (create mode only)
    var genBtn = document.getElementById('genPassword');
    var pwdInput = document.getElementById('password');
    var pwdConfirm = document.getElementById('password_confirmation');
    var pwdNotice = document.getElementById('pwdNotice');
    if (genBtn && pwdInput) {
        genBtn.addEventListener('click', function () {
            // Générer un mot de passe sécurisé : 12 caractères, maj/min/chiffres/speciaux
            var charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&*';
            var len = 12;
            var arr = new Uint8Array(len);
            window.crypto.getRandomValues(arr);
            var pwd = '';
            for (var i = 0; i < len; i++) {
                pwd += charset[arr[i] % charset.length];
            }
            pwdInput.value = pwd;
            pwdInput.type = 'text';
            if (pwdConfirm) pwdConfirm.value = pwd;
            if (pwdNotice) pwdNotice.style.display = 'block';
            // Re-masquer après 3s
            setTimeout(function () { pwdInput.type = 'password'; }, 3000);
        });
    }
})();
</script>
