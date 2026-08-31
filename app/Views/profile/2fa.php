<?php
/** @var array|null $twoFactor, int $recoveryCount, bool $codeRequested, string $flashSuccess, array $errors */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
$isLtr = $dir !== 'rtl';
$isEnabled = !empty($twoFactor['enabled']) && !empty($twoFactor['confirmed']);
$method = $twoFactor['method'] ?? 'email';
?>

<div class="wh-hero" style="background:linear-gradient(135deg,#7C3AED 0%,#2563EB 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-shield-lock-outline me-2"></i><?= $isAr ? 'المصادقة الثنائية' : 'Authentification à deux facteurs (2FA)' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'تأمين حسابك بمصادقة إضافية' : 'Protégez votre compte avec une couche de sécurité supplémentaire' ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mx-4 mt-3">
    <?php foreach ($errors as $err): ?>
        <p class="mb-1"><?= e(is_array($err) ? implode(', ', $err) : $err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flashSuccess)): ?>
<div class="alert alert-success mx-4 mt-3"><?= e($flashSuccess) ?></div>
<?php endif; ?>

<div class="container py-4">
    <!-- Status actuel -->
    <div class="futur-card mb-4">
        <div class="futur-card-header">
            <span><i class="mdi mdi-shield-check" style="color:<?= $isEnabled ? '#198754' : '#DC2626' ?>;"></i>
                <?= $isAr ? 'الحالة' : 'État actuel' ?>
                <span class="badge ms-2" style="background:<?= $isEnabled ? '#198754' : '#DC2626' ?>;color:#fff;">
                    <?= $isEnabled ? ($isAr ? 'مفعّلة' : 'Activée') : ($isAr ? 'معطّلة' : 'Désactivée') ?>
                </span>
            </span>
        </div>
        <div class="futur-card-body">
            <?php if ($isEnabled): ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="mdi mdi-check-circle" style="font-size:2rem;color:#198754;"></i>
                    <div>
                        <p class="mb-0 fw-bold"><?= $isAr ? 'المصادقة الثنائية مفعّلة' : 'La 2FA est activée' ?></p>
                        <small class="text-muted">
                            <?= $isAr ? 'الطريقة' : 'Méthode' ?> : <?= $method === 'authenticator' ? ($isAr ? 'تطبيق مصادقة' : 'Application authenticator') : 'Email' ?>
                            &middot;
                            <?= $recoveryCount ?> <?= $isAr ? 'أكواد استرداد متبقية' : 'codes de secours restants' ?>
                        </small>
                    </div>
                </div>

                <!-- Désactiver -->
                <div class="border rounded p-3 mt-3" style="border-color:#fecaca !important;">
                    <h6 class="text-danger"><i class="mdi mdi-shield-off-outline me-1"></i><?= $isAr ? 'تعطيل المصادقة الثنائية' : 'Désactiver la 2FA' ?></h6>
                    <p class="text-muted small mb-2"><?= $isAr ? 'أدخل كلمة المرور للتأكيد' : 'Saisissez votre mot de passe pour confirmer' ?></p>
                    <form method="post" action="<?= url('profile/2fa/disable') ?>" class="d-flex gap-2 align-items-end" onsubmit="return confirm('Êtes-vous sûr ? La 2FA sera désactivée.')">
                        <?= csrf_field() ?>
                        <div class="flex-grow-1" style="max-width:300px;">
                            <input type="password" name="password" class="form-control" placeholder="<?= $isAr ? 'كلمة المرور' : 'Mot de passe actuel' ?>" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger"><?= $isAr ? 'تعطيل' : 'Désactiver' ?></button>
                    </form>
                </div>

                <div class="mt-3">
                    <form method="post" action="<?= url('profile/2fa/recovery/regenerate') ?>" onsubmit="return confirm('Régénérer les codes ? Les anciens codes seront invalidés.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="mdi mdi-refresh me-1"></i><?= $isAr ? 'إعادة توليد أكواد الاسترداد' : 'Régénérer les codes de secours' ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="text-center py-3">
                    <i class="mdi mdi-shield-lock-outline" style="font-size:3rem;color:#6b7280;"></i>
                    <p class="mt-2 mb-0"><?= $isAr ? 'المصادقة الثنائية غير مفعّلة حالياً' : 'La 2FA n\'est pas activée sur votre compte' ?></p>
                    <small class="text-muted"><?= $isAr ? 'ننصح بتفعيلها لحماية حسابك' : 'Nous vous recommandons de l\'activer pour sécuriser votre compte' ?></small>
                </div>

                <?php if ($codeRequested): ?>
                    <!-- Saisie du code pour confirmation -->
                    <div class="border rounded p-3 mt-3" style="border-color:#bfdbfe;background:#eff6ff;">
                        <h6 class="text-primary mb-2"><i class="mdi mdi-email-check-outline me-1"></i><?= $isAr ? 'أدخل رمز التحقق' : 'Confirmer l\'activation' ?></h6>
                        <p class="small text-muted mb-2"><?= $isAr ? 'أدخل الرمز المكون من 6 أرقام المرسل إلى بريدك الإلكتروني' : 'Saisissez le code à 6 chiffres envoyé par email' ?></p>
                        <form method="post" action="<?= url('profile/2fa/confirm') ?>" class="d-flex gap-2 align-items-end">
                            <?= csrf_field() ?>
                            <input type="text" name="code" class="form-control" style="max-width:180px;letter-spacing:4px;text-align:center;"
                                   inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required autofocus>
                            <button type="submit" class="btn btn-primary"><?= $isAr ? 'تأكيد' : 'Confirmer' ?></button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Méthode : Email -->
                    <div class="border rounded p-3 mt-3" style="border-color:#d1d5db;">
                        <h6 class="mb-2"><i class="mdi mdi-email-outline me-1"></i><?= $isAr ? 'تفعيل بالبريد الإلكتروني' : 'Activer par email' ?></h6>
                        <p class="small text-muted mb-2"><?= $isAr ? 'نرسل رمز تحقق إلى بريدك عند كل تسجيل دخول' : 'Un code vous sera envoyé par email à chaque connexion' ?></p>
                        <form method="post" action="<?= url('profile/2fa/enable') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="method" value="email">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-email-fast-outline me-1"></i><?= $isAr ? 'تفعيل بالبريد' : 'Activer par email' ?>
                            </button>
                        </form>
                    </div>

                    <!-- Méthode : Authenticator (TOTP) -->
                    <div class="border rounded p-3 mt-3" style="border-color:#d1d5db;">
                        <h6 class="mb-2"><i class="mdi mdi-qrcode-scan me-1"></i><?= $isAr ? 'تفعيل بتطبيق المصادقة' : 'Activer avec un authenticator' ?></h6>
                        <p class="small text-muted mb-2"><?= $isAr ? 'استخدم Google Authenticator أو Authy — يعمل بدون إنترنت' : 'Utilisez Google Authenticator ou Authy — fonctionne hors ligne' ?></p>
                        <a href="<?= url('profile/2fa/totp/setup') ?>" class="btn btn-outline-primary">
                            <i class="mdi mdi-qrcode me-1"></i><?= $isAr ? 'إعداد TOTP' : 'Configurer l\'authenticator' ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}
</style>
