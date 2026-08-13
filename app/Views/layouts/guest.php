<?php
/**
 * Layout invité — Pages d'authentification institutionnelles.
 *
 * @var string $content
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
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('app.name')) ?> — <?= e(__('app.tagline')) ?></title>
    <link rel="icon" href="<?= asset('/assets/img/icon-192.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <meta name="theme-color" content="#0B5ED7">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
    <script>
        (function () {
            var t;
            try { t = localStorage.getItem('wh-theme'); } catch (e) {}
            if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light');
        })();
    </script>
</head>
<body class="wh-auth">
    <div class="wh-auth-top">
        <?php if ($isAr): ?>
            <a class="wh-icon-btn" href="<?= url('lang/fr') ?>" title="Français">FR</a>
        <?php else: ?>
            <a class="wh-icon-btn" href="<?= url('lang/ar') ?>" title="العربية">ع</a>
        <?php endif; ?>
        <button type="button" class="wh-icon-btn" data-theme-toggle aria-label="Thème">
            <i class="mdi mdi-weather-night" data-theme-icon></i>
        </button>
    </div>

    <main class="wh-auth-inner">
        <div class="wh-auth-brand">
            <span class="wh-brand-logo"><i class="mdi mdi-map-marker-star-outline"></i></span>
            <span class="wh-auth-title"><?= e($appName) ?></span>
            <span class="wh-auth-tagline"><?= e(__('app.tagline')) ?></span>
        </div>

        <?php $success = flash('success'); $error = flash('error'); ?>
        <?php if ($success !== null): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" data-autohide role="alert">
                <i class="mdi mdi-check-circle"></i>
                <div class="flex-grow-1"><?= e($success) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" data-autohide role="alert">
                <i class="mdi mdi-alert-circle"></i>
                <div class="flex-grow-1"><?= e($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="wh-auth-card">
            <?= $content ?>
        </div>

        <p class="wh-auth-foot">
            <i class="mdi mdi-shield-lock-outline"></i>
            <?= $isAr ? 'بوابة رسمية آمنة — ولاية هارمونيا' : 'Portail officiel sécurisé — Wilaya Harmonia' ?>
        </p>
    </main>

<script>window.WH_I18N = <?= json_encode(App\Helpers\I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= asset('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('/assets/js/admin.js') ?>"></script>
</body>
</html>
