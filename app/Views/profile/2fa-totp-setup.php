<?php
/** @var string $secret, string $qrCode, array $errors */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>

<div class="wh-hero" style="background:linear-gradient(135deg,#7C3AED 0%,#2563EB 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-qrcode me-2"></i><?= $isAr ? 'إعداد تطبيق المصادقة' : 'Configurer l\'authenticator' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'امسح الرمز بتطبيق Google Authenticator أو Authy' : 'Scannez le code avec Google Authenticator ou Authy' ?></p>
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

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="futur-card">
                <div class="futur-card-header">
                    <span><i class="mdi mdi-qrcode-scan"></i> <?= $isAr ? 'الخطوة 1: المسح' : 'Étape 1 : Scanner le QR code' ?></span>
                </div>
                <div class="futur-card-body text-center">
                    <p class="mb-3"><?= $isAr ? 'افتح تطبيق المصادقة وامسح الرمز أدناه' : 'Ouvrez votre application d\'authentification et scannez ce code' ?></p>

                    <div class="d-inline-block p-3 bg-white rounded shadow-sm mb-3" style="border:2px solid #e5e7eb;">
                        <img src="<?= $qrCode ?>" alt="QR Code TOTP" style="width:200px;height:200px;">
                    </div>

                    <!-- Secret manuel -->
                    <div class="mt-3">
                        <p class="text-muted small mb-1"><?= $isAr ? 'أو أدخل المفتاح يدوياً :' : 'Ou saisissez la clé manuellement :' ?></p>
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <code class="px-3 py-2 rounded" style="background:#f0f4ff;letter-spacing:3px;font-size:1.1rem;user-select:all;" id="secret-display"><?= e($secret) ?></code>
                            <button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('secret-display').textContent).then(()=>this.textContent='Copié!')">
                                <i class="mdi mdi-content-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="futur-card mt-4">
                <div class="futur-card-header">
                    <span><i class="mdi mdi-check-circle"></i> <?= $isAr ? 'الخطوة 2: التأكيد' : 'Étape 2 : Confirmer' ?></span>
                </div>
                <div class="futur-card-body">
                    <p class="text-muted mb-3"><?= $isAr ? 'أدخل الرمز المكون من 6 أرقام من تطبيق المصادقة' : 'Saisissez le code à 6 chiffres affiché dans votre application' ?></p>

                    <form method="post" action="<?= url('profile/2fa/totp/enable') ?>" class="d-flex gap-2 align-items-end">
                        <?= csrf_field() ?>
                        <input type="text" name="code" class="form-control" style="max-width:220px;letter-spacing:6px;text-align:center;font-size:1.25rem;"
                               inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required autofocus>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="mdi mdi-shield-check me-1"></i><?= $isAr ? 'تفعيل' : 'Activer' ?>
                        </button>
                    </form>

                    <div class="mt-3">
                        <a href="<?= url('profile/2fa') ?>" class="text-muted small">
                            <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'العودة' : 'Retour à la sécurité' ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}
</style>
