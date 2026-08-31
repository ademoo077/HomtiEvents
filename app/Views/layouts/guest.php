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
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? __('app.name')) ?> — <?= e(__('app.tagline')) ?></title>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="حومتي ايفانت">
    <link rel="icon" href="<?= asset('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= asset('/apple-touch-icon.png') ?>">
    <meta name="theme-color" content="#0F2B22">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/control-center.css') ?>">
    <style>
        /* ═══ Charte landing (forêt / or) — pages d'authentification ═══ */
        body.wh-auth {
            background:
                linear-gradient(160deg, rgba(10, 30, 24, .84) 0%, rgba(26, 77, 62, .72) 55%, rgba(15, 43, 34, .88) 100%),
                url('<?= asset('/assets/img/hero-background.jpg') ?>') center / cover no-repeat fixed,
                var(--wh-foret-deep, #0F2B22);
        }
        .wh-auth-inner { max-width: 580px; }

        .wh-auth-top .wh-icon-btn {
            background: rgba(15, 43, 34, .6);
            border: 1px solid rgba(212, 175, 55, .55);
            color: var(--wh-or-light, #F0C95C);
            border-radius: 999px;
            width: 40px;
            height: 40px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .25);
        }
        .wh-auth-top .wh-icon-btn:hover {
            background: var(--wh-or, #D4AF37);
            color: var(--wh-foret-deep, #0F2B22);
        }

        .wh-auth-brand .wh-brand-logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--wh-foret-mid, #2E6E5C), var(--wh-foret-deep, #0F2B22));
            border: 2px solid var(--wh-or, #D4AF37);
            color: var(--wh-or-light, #F0C95C);
            font-size: 1.9rem;
            box-shadow: 0 8px 20px rgba(212, 175, 55, .25), 0 4px 12px rgba(0, 0, 0, .35);
        }
        .wh-auth-title { color: #F6EFDD; font-size: 1.55rem; font-weight: 800; }
        .wh-auth-tagline { color: #C9D6CE; }

        .wh-auth-card {
            max-width: 560px;
            border: 1px solid rgba(212, 175, 55, .5);
            border-top: 3px solid var(--wh-or, #D4AF37);
            border-radius: 20px;
            background: linear-gradient(180deg, #FFFDF6 0%, var(--wh-cream, #FAF6EC) 100%);
            box-shadow: 0 24px 56px rgba(6, 20, 15, .45), 0 6px 18px rgba(6, 20, 15, .3), inset 0 1px 0 rgba(255, 255, 255, .9);
            padding: 2.25rem 2rem;
        }

        .wh-auth-card .form-group label,
        .wh-auth-card .form-label {
            color: var(--wh-foret-dark, #14392E);
            font-weight: 600;
            font-size: .85rem;
            margin-bottom: .3rem;
        }
        .wh-auth-card .form-control,
        .wh-auth-card .form-select {
            border-radius: .65rem;
            border: 1px solid rgba(20, 57, 46, .22);
            background: #FFFFFF;
            padding: .6rem .85rem;
            font-size: .92rem;
        }
        .wh-auth-card .form-control:focus,
        .wh-auth-card .form-select:focus {
            border-color: var(--wh-or, #D4AF37);
            box-shadow: 0 0 0 .2rem rgba(212, 175, 55, .22);
        }
        .wh-auth-card .form-control::placeholder { color: #9AA98F; }

        .wh-auth-card .btn-primary {
            background: linear-gradient(135deg, var(--wh-foret, #1A4D3E), var(--wh-foret-mid, #2E6E5C));
            border: 1px solid rgba(212, 175, 55, .6);
            border-radius: .65rem;
            color: #fff;
            font-weight: 600;
            padding: .65rem 1rem;
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }
        .wh-auth-card .btn-primary:hover {
            filter: brightness(1.12);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(20, 57, 46, .35);
        }
        .wh-auth-card .btn-outline-secondary {
            border: 1px solid rgba(20, 57, 46, .35);
            color: var(--wh-foret, #1A4D3E);
            border-radius: .65rem;
            font-weight: 600;
        }
        .wh-auth-card .btn-outline-secondary:hover {
            background: var(--wh-foret, #1A4D3E);
            border-color: var(--wh-foret, #1A4D3E);
            color: #fff;
        }

        .wh-auth-card a { color: var(--wh-foret-mid, #2E6E5C); font-weight: 600; }
        .wh-auth-card a:hover { color: var(--wh-or, #B8932C); }
        .wh-auth-card .wh-text-muted { color: #5A6B60; }
        .wh-auth-card .wh-divider { border-top: 1px dashed rgba(20, 57, 46, .22); }
        .wh-auth-card .alert { border-radius: .75rem; }
        .wh-auth-card h2 { color: var(--wh-foret-dark, #14392E); }
        .wh-auth-card .text-muted { color: #5A6B60 !important; }

        .wh-auth-foot { color: rgba(255, 255, 255, .78); }
        .wh-auth-foot i { color: var(--wh-or-light, #F0C95C); }

        @media (max-width: 480px) {
            .wh-auth-card { padding: 1.75rem 1.25rem; }
        }
    </style>
</head>
<body class="wh-auth">
    <div class="wh-auth-top">
        <?php if ($isAr): ?>
            <a class="wh-icon-btn" href="<?= url('lang/fr') ?>" title="Français">FR</a>
        <?php else: ?>
            <a class="wh-icon-btn" href="<?= url('lang/ar') ?>" title="العربية">ع</a>
        <?php endif; ?>
    </div>

    <main class="wh-auth-inner">
        <div class="wh-auth-brand">
            <span class="wh-brand-logo"><i class="mdi mdi-map-marker-star-outline"></i></span>
            <span class="wh-auth-title"><?= e($appName) ?></span>
            <span class="wh-auth-tagline"><?= e(__('app.tagline')) ?></span>
        </div>

        <div class="wh-auth-card">
            <?php $success = flash('success'); $error = flash('error'); ?>
            <?php if ($success !== null): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" data-autohide="6000">
                    <i class="mdi mdi-check-circle me-1"></i> <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" data-autohide="6000">
                    <i class="mdi mdi-alert-circle me-1"></i> <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </div>

        <p class="wh-auth-foot">
            <i class="mdi mdi-shield-lock-outline"></i>
            <?= $isAr ? 'بوابة رسمية آمنة — حومتي ايفانت' : 'Portail officiel sécurisé — حومتي ايفانت' ?>
        </p>
    </main>

<script>window.WH_I18N = <?= json_encode(App\Helpers\I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= asset('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('/assets/js/admin.js') ?>"></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('<?= asset('/sw.js') ?>').catch(function () {});
    });
}
</script>
</body>
</html>
