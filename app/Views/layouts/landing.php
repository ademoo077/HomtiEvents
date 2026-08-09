<?php
/** @var string $content */
use App\Helpers\I18n;

$locale  = I18n::locale();
$langAttr = I18n::langAttribute();
$dir     = I18n::direction();
$isAr    = $dir === 'rtl';
$appName = e(settings('app.name') ?: __('app.name'));
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(__('app.name')) ?> — <?= e(__('app.tagline')) ?></title>
    <meta name="description" content="<?= e(settings('hero_sous_titre_fr', '')) ?>">
    <link rel="icon" href="<?= asset('/assets/img/icon-192.png') ?>">
    <link rel="manifest" href="<?= asset('/manifest.json') ?>">
    <meta name="theme-color" content="#6366f1">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/leaflet/css/leaflet.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/landing.css') ?>">
    <noscript>
        <style>[data-reveal]{opacity:1;transform:none;transition:none}</style>
    </noscript>
</head>
<body class="landing-body">

<a class="skip-link" href="#main"><?= $isAr ? 'تخطّ إلى المحتوى' : 'Aller au contenu' ?></a>

<!-- ═══ HEADER UNIQUE — sticky glassmorphism ═══ -->
<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a class="site-brand" href="#top" aria-label="Wilaya Harmonia — <?= $isAr ? 'الرئيسية' : 'Accueil' ?>">
            <span class="site-logo"><i class="mdi mdi-map-marker-star-outline"></i></span>
            <span class="site-name">Wilaya <span class="text-gradient">Harmonia</span></span>
        </a>

        <nav class="site-nav" id="siteNav" aria-label="<?= $isAr ? 'القائمة الرئيسية' : 'Navigation principale' ?>">
            <ul class="site-menu">
                <li><a href="#top"><?= e(__('landing.nav_accueil')) ?></a></li>
                <li><a href="#apropos"><?= e(__('landing.nav_apropos')) ?></a></li>
                <li><a href="#fonctionnement"><?= e(__('landing.nav_fonctionnement')) ?></a></li>
                <li><a href="#albums"><?= e(__('landing.nav_albums')) ?></a></li>
                <li><a href="#galerie"><?= e(__('landing.galerie')) ?></a></li>
                <li><a href="#before-after"><?= e(__('landing.before_after')) ?></a></li>
                <li><a href="#partenaires"><?= e(__('landing.nav_partenaires')) ?></a></li>
                <li><a href="#carte"><?= e(__('landing.interventions')) ?></a></li>
                <li><a href="#faq"><?= e(__('landing.nav_faq')) ?></a></li>
            </ul>
            <div class="mobile-cta">
            <?php if (is_logged()): ?>
                <a class="btn btn-primary btn-block" href="<?= e(dashboard_path()) ?>">
                    <i class="mdi mdi-shield-lock-outline"></i><?= e(__('common.dashboard')) ?>
                </a>
            <?php else: ?>
                    <a class="btn btn-outline btn-block" href="<?= url('auth/login') ?>">
                        <i class="mdi mdi-login"></i><?= e(__('common.login')) ?>
                    </a>
                    <a class="btn btn-primary btn-block" href="<?= url('auth/register') ?>">
                        <i class="mdi mdi-account-plus-outline"></i><?= e(__('common.register')) ?>
                    </a>
                    <a class="btn btn-outline btn-block" href="<?= url('auth/register-association') ?>">
                        <i class="mdi mdi-domain"></i><?= e(__('associations.inscription')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="header-actions">
            <?php if ($isAr): ?>
                <a class="lang-pill" href="<?= url('lang/fr') ?>" aria-label="Passer au français" title="Français">FR</a>
            <?php else: ?>
                <a class="lang-pill" href="<?= url('lang/ar') ?>" aria-label="التبديل إلى العربية" title="العربية">العربية</a>
            <?php endif; ?>

            <?php if (is_logged()): ?>
                <a class="btn btn-primary btn-sm header-cta" href="<?= e(dashboard_path()) ?>">
                    <i class="mdi mdi-shield-lock-outline"></i><?= e(__('common.dashboard')) ?>
                </a>
            <?php else: ?>
                <a class="btn btn-outline btn-sm header-cta" href="<?= url('auth/login') ?>">
                    <i class="mdi mdi-login"></i><?= e(__('common.login')) ?>
                </a>
                <a class="btn btn-primary btn-sm header-cta" href="<?= url('auth/register') ?>">
                    <i class="mdi mdi-account-plus-outline"></i><?= e(__('common.register')) ?>
                </a>
                <a class="btn btn-outline btn-sm header-cta" href="<?= url('auth/register-association') ?>">
                    <i class="mdi mdi-domain"></i><?= e(__('associations.inscription')) ?>
                </a>
            <?php endif; ?>

            <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="<?= $isAr ? 'فتح القائمة' : 'Ouvrir le menu' ?>">
                <i class="mdi mdi-menu"></i>
            </button>
        </div>
    </div>
</header>

<!-- ═══ CONTENU ═══ -->
<main id="main">
    <?= $content ?>
</main>

<!-- ═══ FOOTER PREMIUM ═══ -->
<footer class="site-footer" id="footer">
    <div class="footer-glow" aria-hidden="true"></div>
    <div class="container footer-grid">
        <div class="footer-col footer-brand-col">
            <a class="site-brand" href="#top" aria-label="Wilaya Harmonia">
                <span class="site-logo"><i class="mdi mdi-map-marker-star-outline"></i></span>
                <span class="site-name">Wilaya <span class="text-gradient">Harmonia</span></span>
            </a>
            <p><?= e(App\Helpers\I18n::pick((string) settings('footer_description_fr', ''), (string) settings('footer_description_ar', ''))) ?></p>
            <div class="social-links">
                <?php foreach (['facebook' => 'mdi-facebook', 'instagram' => 'mdi-instagram', 'youtube' => 'mdi-youtube', 'x' => 'mdi-twitter'] as $reseau => $icone): ?>
                    <?php if ($url = settings('social_' . $reseau, '')): ?>
                        <a class="social-link" href="<?= e((string) $url) ?>" target="_blank" rel="noopener" aria-label="<?= e($reseau) ?>"><i class="mdi <?= e($icone) ?>"></i></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="footer-col">
            <h4><?= e(__('landing.footer_navigation')) ?></h4>
            <ul>
                <li><a href="#apropos"><?= e(__('landing.nav_apropos')) ?></a></li>
                <li><a href="#fonctionnement"><?= e(__('landing.nav_fonctionnement')) ?></a></li>
                <li><a href="#albums"><?= e(__('landing.albums')) ?></a></li>
                <li><a href="#galerie"><?= e(__('landing.galerie')) ?></a></li>
                <li><a href="#before-after"><?= e(__('landing.before_after')) ?></a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4><?= e(__('landing.footer_liens')) ?></h4>
            <ul>
                <li><a href="#partenaires"><?= e(__('landing.nav_partenaires')) ?></a></li>
                <li><a href="#carte"><?= e(__('landing.interventions')) ?></a></li>
                <li><a href="#faq"><?= e(__('landing.nav_faq')) ?></a></li>
                <li><a href="<?= url('evenements') ?>"><?= e(__('landing.actualites')) ?></a></li>
                <li><a href="<?= url('classement') ?>"><?= e(__('common.leaderboard')) ?></a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4><?= e(__('landing.footer_contact')) ?></h4>
            <ul class="footer-contact">
                <li><i class="mdi mdi-map-marker-outline"></i><?= e(settings('contact_adresse', '')) ?></li>
                <li><i class="mdi mdi-email-outline"></i><a href="mailto:<?= e(settings('contact_email', '')) ?>"><?= e(settings('contact_email', '')) ?></a></li>
                <li><i class="mdi mdi-phone-outline"></i><a href="tel:<?= e(str_replace(' ', '', (string) settings('contact_telephone', ''))) ?>"><?= e(settings('contact_telephone', '')) ?></a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom container">
        <span>© <?= date('Y') ?> <?= e(__('app.name')) ?> — <?= e(__('landing.footer_droits')) ?></span>
        <a class="footer-top" href="#top" aria-label="<?= e(__('landing.footer_monter')) ?>">
            <i class="mdi mdi-chevron-up"></i><?= e(__('landing.footer_monter')) ?>
        </a>
    </div>
</footer>

<script>window.WH_I18N = <?= json_encode(App\Helpers\I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= asset('/assets/vendor/leaflet/js/leaflet.js') ?>"></script>
<script src="<?= asset('/assets/vendor/leaflet-heat/leaflet-heat.js') ?>"></script>
<script src="<?= asset('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= asset('/assets/js/landing.js') ?>"></script>
</body>
</html>
