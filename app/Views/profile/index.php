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
    <div class="wh-hero" style="background: linear-gradient(135deg, #7C3AED 0%, #0B5ED7 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-account-circle-outline me-2"></i><?= $isAr ? 'ملفي الشخصي' : 'Mon profil' ?></h1>
                    <p class="wh-hero-sub"><?= $isAr ? 'إدارة معلوماتك وأمانك وتفضيلاتك' : 'Gérez vos informations, votre sécurité et vos préférences' ?></p>
                </div>
                <div class="wh-hero-actions">
<?php if ($role === 'association' || $role === 'epic'): ?>
                    <a class="btn btn-light" target="_blank" rel="noopener" href="<?= e((string) $publicUrl) ?>">
                        <i class="mdi mdi-eye-outline me-1"></i><?= $isAr ? 'عرض الصفحة العامة' : 'Voir la fiche publique' ?>
                    </a>
<?php elseif ($role === 'membre' && $association): ?>
                    <span class="badge bg-secondary text-white px-3 py-2">
                        <i class="mdi mdi-account-group-outline me-1"></i><?= $isAr ? 'عضو في' : 'Membre de' ?> <?= e($association['nom']) ?>
                    </span>
<?php endif; ?>
                </div>
            </div>
        </div>
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
                        <img src="<?= e($avatarAlt ?? $avatarUrl) ?>" alt="<?= e($fullName) ?>" loading="lazy" class="wh-avatar-lg rounded-circle object-fit-cover" style="width:96px;height:96px;">
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
                    <img src="<?= $qrDataUri ?>" alt="QR" width="96" height="96" loading="lazy" class="img-thumbnail">
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

    <!-- Association card for membre -->
    <?php if ($role === 'membre' && $association): ?>
        <div class="card border-0 shadow-sm wh-card-hover mb-4">
            <div class="card-header bg-light d-flex align-items-center gap-2">
                <i class="mdi mdi-account-group-outline text-primary"></i>
                <h3 class="h6 mb-0"><?= $isAr ? 'جمعية الأعضاء' : 'Votre association' ?></h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted"><?= $isAr ? 'الاسم' : 'Nom' ?></label>
                        <div class="fw-semibold"><?= e($association['nom'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted"><?= $isAr ? 'رقم الاعتماد' : 'Numéro agrément' ?></label>
                        <div class="fw-semibold"><?= e($association['numero_agrement'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted"><?= $isAr ? 'البلدية' : 'Commune' ?></label>
                        <div class="fw-semibold">
                            <?php 
                                $comm = $association['commune_id'] ?? null;
                                if ($comm) {
                                    $c = Database::one('SELECT nom FROM commune WHERE id = ?', [(int)$comm]);
                                    echo e($c['nom'] ?? '—');
                                } else { echo '—'; }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted"><?= $isAr ? 'الحالة' : 'Statut' ?></label>
                        <div>
                            <span class="badge <?= $association['valide'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= $association['valide'] ? ($isAr ? 'مصدقة' : 'Validée') : ($isAr ? 'قيد المراجعة' : 'En attente') ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-muted"><?= $isAr ? 'الوصف' : 'Description' ?></label>
                        <div class="text-muted small"><?= e(mb_substr((string) ($association['description'] ?? ''), 0, 200)) ?></div>
                    </div>
                </div>
                <div class="mt-3">
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('dashboard') ?>" target="_blank">
                        <i class="mdi mdi-view-dashboard me-1"></i><?= $isAr ? 'العودة للوحة التحكم' : 'Retour au tableau de bord' ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Idées & conseils pour membre -->
    <?php if ($role === 'membre' && ! empty($suggestions)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light d-flex align-items-center gap-2">
                <i class="mdi mdi-lightbulb-on-outline text-warning"></i>
                <h3 class="h6 mb-0"><?= $isAr ? 'أفكار وتوصيات' : 'Idées & conseils' ?></h3>
            </div>
            <div class="card-body">
                <div class="wh-ideas">
                    <?php foreach (array_slice($suggestions, 0, 4) as $s): ?>
                        <div class="wh-idea">
                            <span class="wh-idea-icon <?= e($s['color'] ?? 'primary') ?>">
                                <i class="mdi <?= e($s['icon']) ?>"></i>
                            </span>
                            <div class="wh-idea-body">
                                <?php if (! empty($s['titre'])): ?>
                                    <div class="wh-idea-title"><?= e($s['titre']) ?></div>
                                <?php endif; ?>
                                <div class="wh-idea-text"><?= e($s['texte']) ?></div>
                                <?php if (! empty($s['lien'])): ?>
                                    <a class="wh-idea-link" href="<?= e($s['lien']) ?>">
                                        <?= e($s['cta'] ?? ($isAr ? 'عرض التفاصيل' : 'Voir les détails')) ?>
                                        <i class="mdi <?= $isAr ? 'mdi-arrow-left' : 'mdi-arrow-right' ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
                                <label class="form-label"><?= e(__('common.email')) ?> *</label>
                                <input class="form-control" type="email" name="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" disabled readonly>
                                <div class="form-text">
                                    <?= e((string) $user['email']) ?> —
                                    <a href="#" onclick="document.getElementById('emailEditSection').style.display='block';this.style.display='none';return false;" class="text-primary">
                                        <?= $isAr ? 'تعديل' : 'Modifier' ?>
                                    </a>
                                </div>
                                <?php if (isset($errors['email'])): ?><div class="text-danger small mt-1"><?= e($errors['email']) ?></div><?php endif; ?>
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

            <!-- Email edit (hidden by default) -->
            <div class="card border-0 shadow-sm mt-4" id="emailEditSection" style="display:<?= !empty($errors['email']) ? 'block' : 'none' ?>;">
                <div class="card-header bg-light d-flex align-items-center gap-2">
                    <i class="mdi mdi-email-edit-outline text-primary"></i>
                    <h3 class="h6 mb-0"><?= $isAr ? 'تغيير البريد الإلكتروني' : 'Changer l\'adresse email' ?></h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small mb-3">
                        <i class="mdi mdi-information-outline me-1"></i>
                        <?= $isAr ? 'سيتم إرسال رسالة تأكيد إلى البريد الحالي لتأكيد التغيير.' : 'Un message de confirmation sera envoyé à votre adresse actuelle pour valider le changement.' ?>
                    </div>
                    <form method="post" action="<?= url('profile/update-email') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= $isAr ? 'البريد الإلكتروني الجديد' : 'Nouvelle adresse email' ?> *</label>
                                <input class="form-control" type="email" name="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" required>
                                <?php if (isset($errors['email'])): ?><div class="text-danger small mt-1"><?= e($errors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?= $isAr ? 'كلمة المرور للتأكيد' : 'Mot de passe pour confirmer' ?> *</label>
                                <input class="form-control" type="password" name="current_password" required autocomplete="current-password">
                                <?php if (isset($errors['current_password'])): ?><div class="text-danger small mt-1"><?= e($errors['current_password']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit"><i class="mdi mdi-email-check me-1"></i><?= $isAr ? 'تحديث البريد' : 'Mettre à jour l\'email' ?></button>
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

            <!-- 2FA Toggle -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-light d-flex align-items-center gap-2">
                    <i class="mdi mdi-shield-lock-outline text-primary"></i>
                    <h3 class="h6 mb-0"><?= $isAr ? 'المصادقة الثنائية' : 'Double authentification (2FA)' ?></h3>
                </div>
                <div class="card-body">
                    <?php
                    $twoFactor = \App\Helpers\Database::one('SELECT * FROM two_factor WHERE user_id = ?', [(int) $user['id']]);
                    $is2faEnabled = !empty($twoFactor['enabled']) && !empty($twoFactor['confirmed']);
                    $method2fa = $twoFactor['method'] ?? 'email';
                    ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge <?= $is2faEnabled ? 'bg-success' : 'bg-secondary' ?>">
                                    <i class="mdi <?= $is2faEnabled ? 'mdi-shield-check' : 'mdi-shield-off' ?> me-1"></i>
                                    <?= $is2faEnabled ? ($isAr ? 'مفعّلة' : 'Activée') : ($isAr ? 'معطّلة' : 'Désactivée') ?>
                                </span>
                                <?php if ($is2faEnabled): ?>
                                    <span class="text-muted small">
                                        <?= $method2fa === 'authenticator' ? ($isAr ? 'تطبيق مصادقة' : 'Authenticator') : 'Email' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small mt-1">
                                <?= $isAr ? 'حماية حسابك بمصادقة إضافية عند تسجيل الدخول' : 'Protégez votre compte avec une étape supplémentaire à la connexion' ?>
                            </div>
                        </div>
                        <div>
                            <a href="<?= url('profile/2fa') ?>" class="btn <?= $is2faEnabled ? 'btn-outline-warning' : 'btn-primary' ?>">
                                <i class="mdi <?= $is2faEnabled ? 'mdi-cog-outline' : 'mdi-shield-plus' ?> me-1"></i>
                                <?= $is2faEnabled ? ($isAr ? 'إدارة' : 'Gérer') : ($isAr ? 'تفعيل' : 'Activer') ?>
                            </a>
                        </div>
                    </div>
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

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
