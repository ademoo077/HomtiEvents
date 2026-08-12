<?php
/**
 * Espace profil — onglets Informations / Sécurité / Préférences.
 *
 * @var array $user @var string $role @var string $roleLabel
 * @var array $preferences @var array $errors @var string|null $success
 * @var array $widgets @var array|null $association @var array|null $epic
 * @var string|null $qrDataUri @var string|null $publicUrl
 */
use App\Helpers\I18n;
use App\Helpers\Rbac;

$isAr = I18n::direction() === 'rtl';
$fullName = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
$initials = mb_substr((string) ($user['prenom'] ?? ''), 0, 1) . mb_substr((string) ($user['nom'] ?? ''), 0, 1);
$avatarUrl = ! empty($user['avatar']) ? asset($user['avatar']) : null;
$avatarAlt = $avatarUrl ? asset($user['avatar']) . '?v=' . md5((string) ($user['avatar'])) : null;
$roleBadgeClass = match ($role) {
    'wilaya'      => 'wh-role wilaya',
    'association' => 'wh-role association',
    'epic'        => 'wh-role epic',
    default       => 'wh-role',
};
$tab = (string) ($_GET['tab'] ?? 'info');
$org = $role === 'association' ? $association : ($role === 'epic' ? $epic : null);
$orgName = $org['nom'] ?? ($org['nom_epic'] ?? ($org['nom'] ?? ''));
?>

<div class="wh-page">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><?= $isAr ? 'ملفي الشخصي' : 'Mon profil' ?></h1>
            <div class="text-muted small"><?= $isAr ? 'إدارة معلوماتك وأمانك وتفضيلاتك' : 'Gérez vos informations, votre sécurité et vos préférences' ?></div>
        </div>
        <?php if ($role === 'association' || $role === 'epic'): ?>
            <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= e((string) $publicUrl) ?>">
                <i class="mdi mdi-eye-outline me-1"></i><?= $isAr ? 'عرض الصفحة العامة' : 'Voir la fiche publique' ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($success !== null): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" data-autohide role="alert">
            <i class="mdi mdi-check-circle"></i>
            <div class="flex-grow-1"><?= e($success) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm wh-card-hover mb-4">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <div class="position-relative">
                <form method="post" action="<?= url('profile/avatar') ?>" enctype="multipart/form-data" id="avatarForm" class="d-none">
                    <?= csrf_field() ?>
                    <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('avatarForm').submit()">
                </form>
                <button type="button" class="btn btn-link p-0 border-0" onclick="document.getElementById('avatarInput').click()" title="<?= $isAr ? 'تغيير الصورة' : 'Changer la photo' ?>">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= e($avatarAlt ?? $avatarUrl) ?>" alt="<?= e($fullName) ?>" class="wh-avatar-lg rounded-circle object-fit-cover" style="width:96px;height:96px;">
                    <?php else: ?>
                        <span class="wh-avatar-lg rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width:96px;height:96px;font-size:2rem;"><?= e($initials ?: '?') ?></span>
                    <?php endif; ?>
                </button>
                <?php if ($avatarUrl): ?>
                    <form method="post" action="<?= url('profile/avatar/remove') ?>" class="position-absolute" style="top:-8px;right:-8px;">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-danger rounded-circle" title="<?= $isAr ? 'حذف الصورة' : 'Supprimer la photo' ?>"><i class="mdi mdi-close"></i></button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="flex-grow-1">
                <h2 class="h4 mb-1"><?= e($fullName !== '' ? $fullName : ($user['email'] ?? '')) ?></h2>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-pill <?= $roleBadgeClass ?>"><?= e($roleLabel) ?></span>
                    <?php if ($orgName): ?><span class="badge rounded-pill bg-light text-dark border"><?= e($orgName) ?></span><?php endif; ?>
                </div>
                <div class="text-muted small mt-1"><i class="mdi mdi-email-outline me-1"></i><?= e((string) $user['email']) ?></div>
            </div>
            <div class="text-end">
                <div class="text-muted small mb-1"><?= $isAr ? 'مؤشر رمز الاستجابة السريعة' : 'QR code de la fiche' ?></div>
                <?php if ($qrDataUri): ?>
                    <img src="<?= $qrDataUri ?>" alt="QR" width="96" height="96" class="img-thumbnail">
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (! empty($widgets)): ?>
        <div class="card-body border-top">
            <div class="row g-3">
                <?php foreach ($widgets as $w): ?>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-light">
                        <i class="mdi <?= e($w['icon']) ?> fs-4 text-primary"></i>
                        <div>
                            <div class="fs-4 fw-bold lh-1"><?= (int) $w['value'] ?></div>
                            <div class="small text-muted"><?= e($w['label']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link <?= $tab === 'info' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabInfo" type="button" role="tab"><i class="mdi mdi-account-edit me-1"></i><?= $isAr ? 'المعلومات' : 'Informations' ?></button></li>
        <li class="nav-item"><button class="nav-link <?= $tab === 'security' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabSecurity" type="button" role="tab"><i class="mdi mdi-shield-lock me-1"></i><?= $isAr ? 'الأمان' : 'Sécurité' ?></button></li>
        <li class="nav-item"><button class="nav-link <?= $tab === 'prefs' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabPrefs" type="button" role="tab"><i class="mdi mdi-bell-cog me-1"></i><?= $isAr ? 'التفضيلات' : 'Préférences' ?></button></li>
        <li class="nav-item"><button class="nav-link <?= $tab === 'rgpd' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabRgpd" type="button" role="tab"><i class="mdi mdi-shield-account me-1"></i><?= $isAr ? 'الخصوصية' : 'Confidentialité' ?></button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show <?= $tab === 'info' ? 'active' : '' ?>" id="tabInfo" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="<?= url('profile/update') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= e(__('common.prenom')) ?> *</label>
                                <input class="form-control" type="text" name="prenom" value="<?= e(old('prenom', $user['prenom'] ?? '')) ?>" required>
                                <?php if (isset($errors['prenom'])): ?><div class="text-danger small mt-1"><?= e($errors['prenom']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= e(__('common.nom')) ?> *</label>
                                <input class="form-control" type="text" name="nom" value="<?= e(old('nom', $user['nom'] ?? '')) ?>" required>
                                <?php if (isset($errors['nom'])): ?><div class="text-danger small mt-1"><?= e($errors['nom']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= e(__('common.email')) ?></label>
                                <input class="form-control" type="email" value="<?= e((string) $user['email']) ?>" disabled readonly>
                                <div class="form-text"><?= $isAr ? 'البريد الإلكتروني للاتصال، لا يمكن تعديله' : 'Email de connexion — non modifiable' ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= e(__('common.telephone')) ?></label>
                                <input class="form-control" type="tel" name="telephone" value="<?= e(old('telephone', $user['telephone'] ?? '')) ?>">
                                <?php if (isset($errors['telephone'])): ?><div class="text-danger small mt-1"><?= e($errors['telephone']) ?></div><?php endif; ?>
                            </div>
                            <?php if ($role === 'association' && $association): ?>
                                <div class="col-12"><hr></div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= $isAr ? 'اسم الجمعية' : 'Nom de l\'association' ?></label>
                                    <input class="form-control" type="text" value="<?= e((string) ($association['nom'] ?? '')) ?>" disabled readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= $isAr ? 'رقم الاعتماد' : 'Numéro d\'agrément' ?></label>
                                    <input class="form-control" type="text" value="<?= e((string) ($association['numero_agrement'] ?? '')) ?>" disabled readonly>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary mt-4" type="submit"><i class="mdi mdi-content-save me-1"></i><?= e(__('profil.save')) ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade show <?= $tab === 'security' ? 'active' : '' ?>" id="tabSecurity" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="<?= url('profile/password') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= $isAr ? 'كلمة المرور الحالية' : 'Mot de passe actuel' ?> *</label>
                                <input class="form-control" type="password" name="current_password" required autocomplete="current-password">
                                <?php if (isset($errors['current_password'])): ?><div class="text-danger small mt-1"><?= e($errors['current_password']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <label class="form-label"><?= $isAr ? 'كلمة المرور الجديدة' : 'Nouveau mot de passe' ?> *</label>
                                <input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password">
                                <?php if (isset($errors['password'])): ?><div class="text-danger small mt-1"><?= e($errors['password']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= $isAr ? 'تأكيد كلمة المرور' : 'Confirmer le mot de passe' ?> *</label>
                                <input class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
                                <?php if (isset($errors['password_confirmation'])): ?><div class="text-danger small mt-1"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-text mt-2"><i class="mdi mdi-information-outline me-1"></i><?= $isAr ? '8 أحرف على الأقل' : '8 caractères minimum' ?></div>
                        <button class="btn btn-primary mt-3" type="submit"><i class="mdi mdi-shield-lock me-1"></i><?= $isAr ? 'تغيير كلمة المرور' : 'Changer le mot de passe' ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade show <?= $tab === 'prefs' ? 'active' : '' ?>" id="tabPrefs" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="<?= url('profile/preferences') ?>">
                        <?= csrf_field() ?>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notif_inapp" value="1" id="prefInapp" <?= (int) ($preferences['notif_inapp'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="prefInapp"><i class="mdi mdi-bell-outline me-1"></i><?= $isAr ? 'إشعارات داخل التطبيق' : 'Notifications in-app' ?></label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notif_email" value="1" id="prefEmail" <?= (int) ($preferences['notif_email'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="prefEmail"><i class="mdi mdi-email-outline me-1"></i><?= $isAr ? 'إشعارات عبر البريد الإلكتروني' : 'Notifications par email' ?></label>
                        </div>
                        <div class="mb-3" style="max-width:320px;">
                            <label class="form-label"><?= $isAr ? 'اللغة' : 'Langue' ?></label>
                            <select class="form-select" name="langue">
                                <option value="" <?= empty($preferences['langue']) ? 'selected' : '' ?>><?= $isAr ? 'افتراضية' : 'Par défaut' ?></option>
                                <option value="fr" <?= ($preferences['langue'] ?? '') === 'fr' ? 'selected' : '' ?>>Français</option>
                                <option value="ar" <?= ($preferences['langue'] ?? '') === 'ar' ? 'selected' : '' ?>>العربية</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save me-1"></i><?= e(__('profil.save')) ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade show <?= $tab === 'rgpd' ? 'active' : '' ?>" id="tabRgpd" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1"><?= $isAr ? 'تصدير بياناتي' : 'Exporter mes données' ?></h5>
                        <div class="text-muted small"><?= $isAr ? 'نسخة كاملة من بياناتك بصيغة JSON (RGPD)' : 'Copie complète de vos données au format JSON (RGPD)' ?></div>
                    </div>
                    <a class="btn btn-outline-primary" href="<?= url('profile/export') ?>"><i class="mdi mdi-download me-1"></i>JSON</a>
                </div>
            </div>
            <div class="card border-0 shadow-sm border-danger">
                <div class="card-body">
                    <h5 class="text-danger mb-1"><?= $isAr ? 'إلغاء تنشيط الحساب' : 'Désactivation du compte' ?></h5>
                    <div class="text-muted small mb-3"><?= $isAr ? 'سيرسل طلبك إلى الإدارة للمراجعة. لن يتم حذف بياناتك تلقائياً.' : 'Votre demande sera transmise à l\'administration pour examen. Vos données ne sont pas supprimées automatiquement.' ?></div>
                    <form method="post" action="<?= url('profile/deactivate') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label"><?= $isAr ? 'سبب الطلب' : 'Motif de la demande' ?> *</label>
                                <textarea class="form-control" name="motif" rows="3" required minlength="10" maxlength="500"><?= e(old('motif', '')) ?></textarea>
                                <?php if (isset($errors['motif'])): ?><div class="text-danger small mt-1"><?= e($errors['motif']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label"><?= $isAr ? 'كلمة المرور الحالية' : 'Mot de passe actuel' ?> *</label>
                                <input class="form-control" type="password" name="current_password" required autocomplete="current-password">
                                <?php if (isset($errors['current_password'])): ?><div class="text-danger small mt-1"><?= e($errors['current_password']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn-outline-danger mt-3" type="submit" onclick="return confirm('<?= $isAr ? 'هل أنت متأكد؟' : 'Êtes-vous sûr ?' ?>')"><i class="mdi mdi-account-cancel me-1"></i><?= $isAr ? 'طلب إلغاء التنشيط' : 'Demander la désactivation' ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
