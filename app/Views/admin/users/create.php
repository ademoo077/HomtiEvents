<?php
/** @var array $associations @var array $epics @var array $roles @var array $errors */
$title = __('common.users');
$viewPage = 'admin.users.create';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';

$error = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? '<div class="form-error">' . e((string) $errors[$key]) . '</div>' : '';
};
?>
<style>
.wh-user-form-card {
    background: var(--wh-white);
    border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius);
    box-shadow: var(--wh-shadow);
    overflow: hidden;
}
.wh-user-form-head {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .9rem 1.1rem;
    border-bottom: 1px solid var(--wh-border);
    background: var(--wh-gray-soft);
}
.form-floating-roles {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: .6rem;
}
.role-option { position: relative; }
.role-option input { position: absolute; opacity: 0; pointer-events: none; }
.role-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
    padding: .8rem .5rem;
    border: 2px solid var(--wh-border);
    border-radius: .65rem;
    background: var(--wh-white);
    cursor: pointer;
    text-align: center;
    font-weight: 600;
    font-size: .82rem;
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.role-option label i { font-size: 1.3rem; color: var(--wh-gray); transition: color .15s; }
.role-option input:checked + label {
    border-color: var(--wh-blue);
    background: var(--wh-blue-soft);
    box-shadow: 0 0 0 3px rgba(11,94,215,.12);
}
.role-option input:checked + label i { color: var(--wh-blue); }
</style>

<div class="wh-page">
    <div class="wh-hero-panel mb-4" style="--hero-a:#6d28d9;--hero-b:#0B5ED7">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-account-plus-outline"></i>
                    <?= $isAr ? 'إضافة مستخدم جديد' : 'Créer un utilisateur' ?>
                </h1>
                <p class="mt-1 mb-0"><?= $isAr ? 'إنشاء حساب جديد مع تحديد الدور' : 'Créez un compte et attribuez-lui un rôle' ?></p>
            </div>
            <a href="<?= url('admin/users') ?>" class="btn btn-light">
                <i class="mdi <?= $isAr ? 'mdi-arrow-right' : 'mdi-arrow-left' ?> me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
            </a>
        </div>
    </div>

    <form method="post" action="<?= url('admin/users/store') ?>" novalidate>
        <?= csrf_field() ?>

        <!-- Informations -->
        <div class="wh-user-form-card mb-4">
            <div class="wh-user-form-head">
                <i class="mdi mdi-account-details-outline" style="color:var(--wh-blue);font-size:1.2rem"></i>
                <h3 class="mb-0" style="font-size:.95rem;font-weight:700"><?= $isAr ? 'معلومات الحساب' : 'Informations du compte' ?></h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="prenom"><?= $isAr ? 'الاسم' : 'Prénom' ?> *</label>
                        <input type="text" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" id="prenom" name="prenom" value="<?= e(old('prenom')) ?>" required maxlength="50">
                        <?= $error('prenom') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="nom"><?= $isAr ? 'اللقب' : 'Nom' ?> *</label>
                        <input type="text" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= e(old('nom')) ?>" required maxlength="50">
                        <?= $error('nom') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email"><?= $isAr ? 'البريد الإلكتروني' : 'Adresse email' ?> *</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e(old('email')) ?>" required>
                        <?= $error('email') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="telephone"><?= $isAr ? 'الهاتف' : 'Téléphone' ?></label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= e(old('telephone')) ?>" placeholder="+213 ...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password"><?= $isAr ? 'كلمة المرور' : 'Mot de passe' ?> *</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required minlength="8" autocomplete="new-password">
                        <?= $error('password') ?>
                        <div class="form-text"><?= $isAr ? '8 أحرف على الأقل' : 'Au moins 8 caractères' ?></div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end pb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= old('is_active', '1') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active"><?= $isAr ? 'الحساب نشط' : 'Compte actif' ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rôle -->
        <div class="wh-user-form-card mb-4">
            <div class="wh-user-form-head">
                <i class="mdi mdi-shield-account-outline" style="color:var(--wh-blue);font-size:1.2rem"></i>
                <h3 class="mb-0" style="font-size:.95rem;font-weight:700"><?= $isAr ? 'الدور' : 'Rôle' ?> *</h3>
            </div>
            <div class="card-body">
                <?= $error('role') ?>
                <div class="form-floating-roles mt-2" id="roleRadios">
                    <?php foreach ($roles as $val => $label): ?>
                        <?php
                        $icon = match ($val) {
                            'wilaya'      => 'mdi-shield-star',
                            'association' => 'mdi-handshake',
                            'epic'        => 'mdi-satellite-variant',
                            'membre'      => 'mdi-account-group',
                            'citoyen'     => 'mdi-account-outline',
                        };
                        ?>
                        <div class="role-option">
                            <input type="radio" name="role" id="role-<?= e($val) ?>" value="<?= e($val) ?>" <?= old('role', 'citoyen') === $val ? 'checked' : '' ?>>
                            <label for="role-<?= e($val) ?>"><i class="mdi <?= $icon ?>"></i><span><?= e($label) ?></span></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Association (si role=association) -->
                <div class="row g-3 mt-2" id="field-association" style="<?= old('role') === 'association' ? '' : 'display:none' ?>">
                    <div class="col-md-6">
                        <label class="form-label" for="association_id"><?= $isAr ? 'الجمعية' : 'Association' ?></label>
                        <select class="form-select" id="association_id" name="association_id">
                            <option value="0"><?= $isAr ? '— اختر الجمعية —' : '— Choisir l\'association —' ?></option>
                            <?php foreach ($associations as $a): ?>
                                <option value="<?= (int) $a['id'] ?>" <?= (int) old('association_id') === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- EPIC (si role=epic) -->
                <div class="row g-3 mt-2" id="field-epic" style="<?= old('role') === 'epic' ? '' : 'display:none' ?>">
                    <div class="col-md-6">
                        <label class="form-label" for="epic_id">EPIC</label>
                        <select class="form-select" id="epic_id" name="epic_id">
                            <option value="0">— <?= $isAr ? 'اختر EPIC' : 'Choisir l\'EPIC' ?> —</option>
                            <?php foreach ($epics as $ep): ?>
                                <option value="<?= (int) $ep['id'] ?>" <?= (int) old('epic_id') === (int) $ep['id'] ? 'selected' : '' ?>><?= e($ep['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary"><?= $isAr ? 'إلغاء' : 'Annuler' ?></a>
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= $isAr ? 'حفظ' : 'Créer le compte' ?></button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var radios = document.querySelectorAll('input[name="role"]');
    var assocField = document.getElementById('field-association');
    var epicField = document.getElementById('field-epic');
    function toggle() {
        var val = document.querySelector('input[name="role"]:checked');
        var role = val ? val.value : '';
        if (assocField) assocField.style.display = (role === 'association') ? '' : 'none';
        if (epicField) epicField.style.display = (role === 'epic') ? '' : 'none';
    }
    radios.forEach(function (r) { r.addEventListener('change', toggle); });
});
</script>
