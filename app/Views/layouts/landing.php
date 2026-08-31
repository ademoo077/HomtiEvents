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
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="حومتي ايفانت">
    <link rel="icon" href="<?= asset('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= asset('/apple-touch-icon.png') ?>">
    <meta name="theme-color" content="<?= e(settings('theme_primary', '#0F2B22')) ?>">
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

    <!-- Charte « Vert forêt / Or » : chargée APRÈS le thème CMS pour garantir le rendu maquette -->
    <link rel="stylesheet" href="<?= asset('/assets/css/landing-harmonia.css') ?>">

    <noscript>
        <style>[data-reveal]{opacity:1;transform:none;transition:none}</style>
    </noscript>
</head>
<body class="landing-body">

<!-- ═══ BANDEAU DÉMO ═══ -->
<div class="wh-demo-banner" id="demoBanner" role="alert">
    <div class="wh-demo-banner-inner">
        <i class="mdi mdi-information-outline"></i>
        <span><?= $isAr ? 'نسخة تجريبية — بيانات وهمية' : 'Version de démonstration — Données fictives' ?></span>
    </div>
    <button type="button" class="wh-demo-close" onclick="document.getElementById('demoBanner').style.display='none'" aria-label="<?= $isAr ? 'إغلاق' : 'Fermer' ?>">
        <i class="mdi mdi-close"></i>
    </button>
</div>
<style>
.wh-demo-banner {
    position: fixed; top: 0; inset-inline: 0; z-index: 9999;
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 16px;
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
    color: #451a03; font-size: 0.82rem; font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    direction: ltr;
}
html[dir="rtl"] .wh-demo-banner { direction: rtl; }
.wh-demo-banner-inner {
    display: flex; align-items: center; gap: 8px;
    justify-content: center; flex: 1;
}
.wh-demo-banner .mdi-information-outline { font-size: 1.1rem; }
.wh-demo-close {
    background: none; border: none; cursor: pointer;
    color: #451a03; padding: 4px; border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.wh-demo-close:hover { background: rgba(0,0,0,0.1); }
.wh-demo-close .mdi { font-size: 1.1rem; }
@media (max-width: 640px) {
    .wh-demo-banner { font-size: 0.75rem; padding: 6px 12px; }
}
</style>
<script>
(function() {
    var b = document.getElementById('demoBanner');
    if (b && localStorage.getItem('wh_demo_closed') === '1') { b.style.display = 'none'; }
    if (b) {
        b.querySelector('.wh-demo-close').addEventListener('click', function() {
            localStorage.setItem('wh_demo_closed', '1');
        });
    }
})();
</script>

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
<?php if ((string) settings('navbar_visible', '1') === '1'): ?>
<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a class="site-brand" href="#top" aria-label="حومتي ايفانت — <?= $isAr ? 'الرئيسية' : 'Accueil' ?>">
            <span class="site-logo">
                <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true" focusable="false">
                    <circle cx="32" cy="32" r="30" fill="#14392E"/>
                    <circle cx="32" cy="32" r="30" fill="none" stroke="#D4AF37" stroke-width="2.5"/>
                    <circle cx="32" cy="32" r="25" fill="none" stroke="#D4AF37" stroke-width="0.8" opacity="0.45"/>
                    <path d="M32 20 L37 27 L34.5 27 L39 34 L36 34 L40.5 41 L23.5 41 L28 34 L25 34 L29.5 27 L27 27 Z" fill="#D4AF37"/>
                    <rect x="30.5" y="41" width="3" height="6" rx="1.2" fill="#D4AF37"/>
                    <path d="M32 8 L33.5 11.5 L37 11.9 L34.3 14.3 L35.1 17.8 L32 16 L28.9 17.8 L29.7 14.3 L27 11.9 L30.5 11.5 Z" fill="#D4AF37"/>
                </svg>
            </span>
            <span class="site-name">Homti<span class="text-gradient">Events</span></span>
        </a>

        <nav class="site-nav" id="siteNav" aria-label="<?= $isAr ? 'القائمة الرئيسية' : 'Navigation principale' ?>">
            <ul class="site-menu">
                <!-- Accueil & Carte as direct links -->
                <li><a class="site-menu-link" href="#top"><?= e(__('landing.nav_accueil')) ?></a></li>
                <?php if ((string) settings('map_visible', '1') !== '0'): ?>
                <li><a class="site-menu-link" href="#carte"><?= e(__('landing.interventions')) ?></a></li>
                <?php endif; ?>

                <!-- Médiathèque Dropdown -->
                <li class="site-menu-item has-dropdown">
                    <button type="button" class="site-menu-link dropdown-toggle" aria-expanded="false" aria-haspopup="true" aria-label="<?= e(__('landing.nav_mediatheque')) ?>">
                        <span><?= e(__('landing.nav_mediatheque')) ?></span>
                        <i class="mdi mdi-chevron-down dropdown-arrow"></i>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li role="none"><a class="dropdown-item" href="<?= url('actualites') ?>" role="menuitem"><?= e(__('landing.nav_actualites')) ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#albums" role="menuitem"><?= e(__('landing.nav_albums')) ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#actualites" role="menuitem"><?= $isAr ? 'أحداث قادمة' : 'Événements à venir' ?></a></li>
                        <li role="none"><a class="dropdown-item" href="#galerie" role="menuitem"><?= e(__('landing.galerie')) ?></a></li>
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

            <div class="mobile-cta" id="mobileCta">
                <?php if ((string) settings('navbar_cta_visible', '1') === '1'): ?>
                <?php if (is_logged()): ?>
                    <a class="btn btn-primary" href="<?= e(dashboard_path()) ?>">
                        <i class="mdi mdi-shield-lock-outline"></i><?= e(__('common.dashboard')) ?>
                    </a>
                <?php else: ?>
                    <a class="btn btn-outline" href="<?= url('auth/login') ?>">
                        <i class="mdi mdi-login"></i><?= e(__('common.login')) ?>
                    </a>
                    <a class="btn btn-primary" href="<?= url('auth/register') ?>">
                        <i class="mdi mdi-account-plus-outline"></i><?= e(__('common.register')) ?>
                    </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </nav>

        <div class="header-actions">
            <?php if ($isAr): ?>
                <a class="lang-pill" href="<?= url('lang/fr') ?>" aria-label="Passer au français" title="Français">FR</a>
            <?php else: ?>
                <a class="lang-pill" href="<?= url('lang/ar') ?>" aria-label="التبديل إلى العربية" title="العربية">العربية</a>
            <?php endif; ?>

            <?php if ((string) settings('navbar_cta_visible', '1') === '1'): ?>
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
            <?php endif; ?>

            <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="<?= $isAr ? 'فتح القائمة' : 'Ouvrir le menu' ?>">
                <i class="mdi mdi-menu"></i>
            </button>
        </div>
    </div>
</header>
<?php endif; ?>

<!-- ═══ CONTENU ═══ -->
<main id="main">
    <?= $content ?>
</main>

<!-- ═══ FOOTER PREMIUM ═══ -->
<footer class="site-footer" id="footer">
    <div class="footer-glow" aria-hidden="true"></div>
    <div class="container footer-grid">
        <div class="footer-col footer-brand-col">
            <a class="site-brand" href="#top" aria-label="حومتي ايفانت">
            <span class="site-logo">
                <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true" focusable="false">
                    <circle cx="32" cy="32" r="30" fill="#14392E"/>
                    <circle cx="32" cy="32" r="30" fill="none" stroke="#D4AF37" stroke-width="2.5"/>
                    <circle cx="32" cy="32" r="25" fill="none" stroke="#D4AF37" stroke-width="0.8" opacity="0.45"/>
                    <path d="M32 20 L37 27 L34.5 27 L39 34 L36 34 L40.5 41 L23.5 41 L28 34 L25 34 L29.5 27 L27 27 Z" fill="#D4AF37"/>
                    <rect x="30.5" y="41" width="3" height="6" rx="1.2" fill="#D4AF37"/>
                    <path d="M32 8 L33.5 11.5 L37 11.9 L34.3 14.3 L35.1 17.8 L32 16 L28.9 17.8 L29.7 14.3 L27 11.9 L30.5 11.5 Z" fill="#D4AF37"/>
                </svg>
            </span>
            <span class="site-name">Homti<span class="text-gradient">Events</span></span>
            </a>
            <p><?= e(App\Helpers\I18n::pick((string) settings('footer_description_fr', ''), (string) settings('footer_description_ar', ''))) ?></p>
            <div class="social-links">
                <span class="social-follow-label"><?= $isAr ? 'تابعونا' : 'Suivez-nous' ?></span>
                <?php foreach (['facebook' => 'mdi-facebook', 'instagram' => 'mdi-instagram', 'youtube' => 'mdi-youtube', 'x' => 'mdi-twitter'] as $reseau => $icone): ?>
                    <?php if ($url = settings('social_' . $reseau, '')): ?>
                        <a class="social-link" href="<?= e((string) $url) ?>" target="_blank" rel="noopener" aria-label="<?= e($reseau) ?>"><i class="mdi <?= e($icone) ?>"></i></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ((string) settings('footer_show_navigation', '1') !== '0'): ?>
        <div class="footer-col">
            <?php if ((string) settings('footer_show_titles', '1') === '1'): ?>
            <h4><?= e((string) settings('footer_titre_navigation', '') ?: __('landing.footer_navigation')) ?></h4>
            <?php endif; ?>
            <ul>
                <li><a href="#apropos"><?= e(__('landing.nav_apropos')) ?></a></li>
                <li><a href="#fonctionnement"><?= e(__('landing.nav_fonctionnement')) ?></a></li>
                <li><a href="#albums"><?= e(__('landing.albums')) ?></a></li>
                <li><a href="#galerie"><?= e(__('landing.galerie')) ?></a></li>
                <li><a href="#actualites"><?= $isAr ? 'أحداث قادمة' : 'Événements à venir' ?></a></li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ((string) settings('footer_show_liens', '1') !== '0'): ?>
        <div class="footer-col">
            <?php if ((string) settings('footer_show_titles', '1') === '1'): ?>
            <h4><?= e((string) settings('footer_titre_liens', '') ?: __('landing.footer_liens')) ?></h4>
            <?php endif; ?>
            <ul>
                <li><a href="#partenaires"><?= e(__('landing.nav_partenaires')) ?></a></li>
                <?php if ((string) settings('map_visible', '1') !== '0'): ?>
                <li><a href="#carte"><?= e(__('landing.interventions')) ?></a></li>
                <?php endif; ?>
                <li><a href="#faq"><?= e(__('landing.nav_faq')) ?></a></li>
                <li><a href="<?= url('actualites') ?>"><?= e(__('landing.actualites')) ?></a></li>
                <li><a href="<?= url('classement') ?>"><?= e(__('common.leaderboard')) ?></a></li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ((string) settings('footer_show_contact', '1') !== '0'): ?>
        <div class="footer-col">
            <?php if ((string) settings('footer_show_titles', '1') === '1'): ?>
            <h4><?= e((string) settings('footer_titre_contact', '') ?: __('landing.footer_contact')) ?></h4>
            <?php endif; ?>
            <ul class="footer-contact">
                <li><i class="mdi mdi-map-marker-outline"></i><?= e(settings('contact_adresse', '')) ?></li>
                <li><i class="mdi mdi-email-outline"></i><a href="mailto:<?= e(settings('contact_email', '')) ?>"><?= e(settings('contact_email', '')) ?></a></li>
                <li><i class="mdi mdi-phone-outline"></i><a href="tel:<?= e(str_replace(' ', '', (string) settings('contact_telephone', ''))) ?>"><?= e(settings('contact_telephone', '')) ?></a></li>
            </ul>
        </div>
        <?php endif; ?>
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
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('<?= asset('/sw.js') ?>').catch(function () {});
    });
}
</script>
<script src="<?= asset('/assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= asset('/assets/js/landing.js') ?>"></script>
</body>
</html>
