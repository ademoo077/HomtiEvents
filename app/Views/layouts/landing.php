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
    <meta name="theme-color" content="<?= e(settings('theme_primary', '#16a34a')) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/leaflet/css/leaflet.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/landing.css') ?>">

    <!-- Thème couleur dynamique (injecté depuis le CMS) -->
    <style>
        :root {
            <?php if (! empty($theme['primary'])): ?>--theme-primary: <?= e($theme['primary']) ?>;<?php endif; ?>
            <?php if (! empty($theme['primary_hover'])): ?>--theme-primary-hover: <?= e($theme['primary_hover']) ?>;<?php endif; ?>
            <?php if (! empty($theme['secondary'])): ?>--theme-secondary: <?= e($theme['secondary']) ?>;<?php endif; ?>
            <?php if (! empty($theme['tertiary'])): ?>--theme-tertiary: <?= e($theme['tertiary']) ?>;<?php endif; ?>
            <?php if (! empty($theme['accent_glow'])): ?>--theme-accent-glow: <?= e($theme['accent_glow']) ?>;<?php endif; ?>
            <?php if (! empty($theme['hero_gradient_1'])): ?>--theme-hero-gradient-1: <?= e($theme['hero_gradient_1']) ?>;<?php endif; ?>
            <?php if (! empty($theme['hero_gradient_2'])): ?>--theme-hero-gradient-2: <?= e($theme['hero_gradient_2']) ?>;<?php endif; ?>
            <?php if (! empty($theme['hero_gradient_3'])): ?>--theme-hero-gradient-3: <?= e($theme['hero_gradient_3']) ?>;<?php endif; ?>
            <?php if (! empty($theme['navbar_bg'])): ?>--theme-navbar-bg: <?= e($theme['navbar_bg']) ?>;<?php endif; ?>
            <?php if (! empty($theme['navbar_bg_scrolled'])): ?>--theme-navbar-bg-scrolled: <?= e($theme['navbar_bg_scrolled']) ?>;<?php endif; ?>
            <?php if (! empty($theme['footer_bg'])): ?>--theme-footer-bg: <?= e($theme['footer_bg']) ?>;<?php endif; ?>
            <?php if (! empty($theme['footer_text'])): ?>--theme-footer-text: <?= e($theme['footer_text']) ?>;<?php endif; ?>
        }
    </style>

    <noscript>
        <style>[data-reveal]{opacity:1;transform:none;transition:none}</style>
    </noscript>
</head>
<body class="landing-body">

<?php if (! empty($previewMode ?? null)): ?>
    <!-- ═══ BANDEAU APERÇU ADMIN ═══ -->
    <div class="wh-preview-bar" role="banner">
        <div class="wh-preview-bar-inner">
            <span class="wh-preview-dot" aria-hidden="true"></span>
            <strong><?= $isAr ? 'وضع المعاينة' : 'Mode aperçu' ?></strong>
            <span class="wh-preview-sep" aria-hidden="true">—</span>
            <span><?= $isAr ? 'المحتوى المعروض يعكس بيانات CMS الحالية.' : 'Le contenu affiché reflète les données CMS actuelles.' ?></span>
        </div>
        <a class="wh-preview-back" href="<?= e($previewBackUrl ?? url('admin/landing')) ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'العودة إلى الإدارة' : "Retour à l'administration" ?>
        </a>
    </div>
<?php endif; ?>

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
                <!-- Accueil & Carte as direct links -->
                <li><a class="site-menu-link" href="#top"><?= e(__('landing.nav_accueil')) ?></a></li>
                <li><a class="site-menu-link" href="#carte"><?= e(__('landing.interventions')) ?></a></li>

                <!-- Médiathèque Dropdown -->
                <li class="site-menu-item has-dropdown">
                    <button type="button" class="site-menu-link dropdown-toggle" aria-expanded="false" aria-haspopup="true" aria-label="<?= e(__('landing.nav_mediatheque')) ?>">
                        <span><?= e(__('landing.nav_mediatheque')) ?></span>
                        <i class="mdi mdi-chevron-down dropdown-arrow"></i>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li role="none"><a class="dropdown-item" href="#albums" role="menuitem"><?= e(__('landing.nav_albums')) ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#galerie" role="menuitem"><?= e(__('landing.galerie')) ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#before-after" role="menuitem"><?= e(__('landing.before_after')) ?></a></li>
                    </ul>
                </li>

                <!-- À propos Dropdown -->
                <li class="site-menu-item has-dropdown">
                    <button type="button" class="site-menu-link dropdown-toggle" aria-expanded="false" aria-haspopup="true" aria-label="<?= e(__('landing.nav_a_propos')) ?>">
                        <span><?= e(__('landing.nav_a_propos')) ?></span>
                        <i class="mdi mdi-chevron-down dropdown-arrow"></i>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li role="none"><a class="dropdown-item" href="#apropos" role="menuitem"><?= e(__('landing.nav_apropos')) ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#fonctionnement" role="menuitem"><?= e(__('landing.nav_fonctionnement')) ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#partenaires" role="menuitem"><?= e(__('landing.nav_partenaires')) ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#faq" role="menuitem"><?= e(__('landing.nav_faq')) ?></a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <div class="header-actions">
            <?php if ($isAr): ?>
                <a class="lang-pill" href="<?= url('lang/fr') ?>" aria-label="Passer au français" title="Français">FR</a>
            <?php else: ?>
                <a class="lang-pill" href="<?= url('lang/ar') ?>" aria-label="التبديل إلى العربية" title="العربية">العربية</a>
            <?php endif; ?>

            <div class="header-cta-group">
                <?php if (is_logged()): ?>
                    <a class="btn btn-primary btn-sm" href="<?= e(dashboard_path()) ?>">
                        <i class="mdi mdi-shield-lock-outline"></i><?= e(__('common.dashboard')) ?>
                    </a>
                <?php else: ?>
                    <a class="btn btn-outline btn-sm" href="<?= url('auth/login') ?>">
                        <i class="mdi mdi-login"></i><?= e(__('common.login')) ?>
                    </a>
                    <a class="btn btn-primary btn-sm" href="<?= url('auth/register') ?>">
                        <i class="mdi mdi-account-plus-outline"></i><?= e(__('common.register')) ?>
                    </a>
                <?php endif; ?>
            </div>

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
