<?php
/**
 * Layout public — Fiches association / EPIC accessibles à tous.
 * Léger, sans navigation citoyen ni back-office.
 */
use App\Helpers\I18n;

$locale   = I18n::locale();
$langAttr = I18n::langAttribute();
$dir      = I18n::direction();
$isAr     = $dir === 'rtl';
$appName  = e(settings('app.name') ?: __('app.name'));

$bootstrapCss = $isAr
    ? '/assets/vendor/bootstrap/bootstrap.rtl.min.css'
    : '/assets/vendor/bootstrap/bootstrap.min.css';
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0F2B22">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= e($title ?? $appName) ?> — <?= e(__('app.name')) ?></title>
    <meta name="description" content="<?= e((string) ($og['description'] ?? '')) ?>">
    <meta property="og:title" content="<?= e(($og['title'] ?? $appName)) ?>">
    <meta property="og:description" content="<?= e((string) ($og['description'] ?? '')) ?>">
    <meta property="og:image" content="<?= e((string) ($og['image'] ?? asset('/assets/img/icon-192.png'))) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?= asset('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/citoyen.css') ?>">
    <style>
        body { background: #F5F3EE; min-height: 100vh; margin: 0; }
    </style>
</head>
<body class="citoyen-body">
    <header class="citoyen-header" id="citoyenHeader">
        <div class="citoyen-header-inner">
            <a class="citoyen-brand" href="<?= url('/') ?>">
                <i class="mdi mdi-map-marker-star-outline"></i>
                <span class="citoyen-brand-name"><?= $appName ?></span>
            </a>
            <div class="citoyen-header-actions">
                <?php if ($isAr): ?>
                    <a class="citoyen-icon-btn" href="<?= url('lang/fr') ?>" title="Français">FR</a>
                <?php else: ?>
                    <a class="citoyen-icon-btn" href="<?= url('lang/ar') ?>" title="العربية">ع</a>
                <?php endif; ?>
                <a class="citoyen-icon-btn" href="<?= url('auth/login') ?>" title="<?= $isAr ? 'دخول' : 'Connexion' ?>">
                    <i class="mdi mdi-login"></i>
                </a>
            </div>
        </div>
    </header>

    <main id="main" class="citoyen-main" style="max-width:800px;margin:0 auto;padding:1.5rem 1rem 3rem;">
        <?= $content ?>
    </main>

    <footer style="text-align:center;padding:1.5rem 1rem;color:#6B7C72;font-size:.82rem;">
        <i class="mdi mdi-shield-lock-outline"></i>
        <?= $isAr ? 'بوابة رسمية — حومتي ايفانت' : 'Portail officiel — حومتي ايفانت' ?>
    </footer>

<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('<?= asset('/sw.js') ?>').catch(function () {});
    });
}
</script>
</body>
</html>
