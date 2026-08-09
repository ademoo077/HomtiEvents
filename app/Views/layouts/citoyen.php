<?php
/** @var string $content */
use App\Helpers\I18n;

$locale  = I18n::locale();
$langAttr = I18n::langAttribute();
$dir     = I18n::direction();
$isAr    = $dir === 'rtl';
$appName = e(settings('app.name') ?: __('app.name'));

$currentPath = trim(request_path(), '/');

$nav = function (string $route, string $label, string $icon) use ($currentPath, $isAr): array {
    $active = $currentPath === $route || str_starts_with($currentPath, rtrim($route, '/') . '/');

    return [
        'route'  => $route,
        'label'  => __($label),
        'icon'   => $icon,
        'active' => $active,
    ];
};

$items = [
    $nav('citoyen', 'citoyen.nav_home', 'mdi-home-outline'),
    $nav('citoyen/explorer', 'citoyen.nav_explorer', 'mdi-compass-outline'),
];
$isScan  = $currentPath === 'qrcode/scan' || $currentPath === 'qrcode/scan-optimise';
$scanItem = ['route' => 'qrcode/scan-optimise', 'label' => __('citoyen.nav_scan'), 'icon' => 'mdi-qrcode-scan', 'active' => $isScan];
$itemsCenter = [
    $nav('citoyen/participations', 'citoyen.nav_my_participations', 'mdi-clipboard-check-outline'),
    $nav('citoyen/profile', 'citoyen.nav_profile', 'mdi-account-circle-outline'),
];
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= e(__('citoyen.title')) ?> — <?= e(__('app.name')) ?></title>
    <meta name="description" content="<?= e(__('citoyen.meta_description')) ?>">
    <link rel="icon" href="<?= asset('/assets/img/icon-192.png') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/leaflet/css/leaflet.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/citoyen.css') ?>">
    <noscript>
        <style>[data-reveal]{opacity:1;transform:none;transition:none}</style>
    </noscript>
    <script>
        (function () {
            var t; try { t = localStorage.getItem('wh-theme'); } catch (e) {}
            if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body class="citoyen-body">

<a class="skip-link" href="#main"><?= $isAr ? 'تخطّ إلى المحتوى' : 'Aller au contenu' ?></a>

<!-- ═══ HEADER ═══ -->
<header class="citoyen-header" id="citoyenHeader">
    <div class="citoyen-header-inner">
        <a class="citoyen-brand" href="<?= url('citoyen') ?>" aria-label="<?= e(__('app.name')) ?>">
            <i class="mdi mdi-map-marker-star-outline"></i>
            <span class="citoyen-brand-name"><?= e(__('app.name')) ?></span>
        </a>
        <div class="citoyen-header-actions">
            <button type="button" class="citoyen-icon-btn" data-theme-toggle aria-label="<?= $isAr ? 'الوضع الليلي' : 'Thème' ?>" title="<?= $isAr ? 'الوضع الليلي' : 'Thème' ?>">
                <i class="mdi mdi-weather-night" data-theme-icon></i>
            </button>
            <?php if ($isAr): ?>
                <a class="citoyen-icon-btn" href="<?= url('lang/fr') ?>" aria-label="Français" title="Français">FR</a>
            <?php else: ?>
                <a class="citoyen-icon-btn" href="<?= url('lang/ar') ?>" aria-label="العربية" title="العربية">ع</a>
            <?php endif; ?>
            <?php if (is_logged()): ?>
                <a class="citoyen-icon-btn" href="<?= url('auth/logout') ?>" aria-label="<?= e(__('common.logout')) ?>" title="<?= e(__('common.logout')) ?>">
                    <i class="mdi mdi-logout"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ═══ TOP NAV (mobile) ═══ -->
<nav class="citoyen-top-nav" aria-label="<?= e(__('citoyen.navigation')) ?>">
    <?php foreach ($items as $item): ?>
        <a class="citoyen-top-link<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
            <i class="mdi <?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
    <a class="citoyen-top-link<?= $scanItem['active'] ? ' active' : '' ?>" href="<?= url($scanItem['route']) ?>">
        <i class="mdi <?= e($scanItem['icon']) ?>"></i><span><?= e($scanItem['label']) ?></span>
    </a>
    <?php foreach ($itemsCenter as $item): ?>
        <a class="citoyen-top-link<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
            <i class="mdi <?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<div class="citoyen-shell">
    <!-- ═══ SIDEBAR (desktop) ═══ -->
    <aside class="citoyen-sidebar" id="citoyenSidebar" aria-label="<?= e(__('citoyen.navigation')) ?>">
        <div class="citoyen-sidebar-brand">
            <a class="citoyen-brand" href="<?= url('citoyen') ?>">
                <i class="mdi mdi-map-marker-star-outline"></i>
                <span class="citoyen-brand-name"><?= e(__('app.name')) ?></span>
            </a>
        </div>
        <nav class="citoyen-sidebar-nav">
            <?php foreach (array_merge($items, [$scanItem], $itemsCenter) as $item): ?>
                <a class="citoyen-sidebar-link<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
                    <i class="mdi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="citoyen-sidebar-footer">
            <a class="citoyen-sidebar-link" href="<?= url('auth/logout') ?>">
                <i class="mdi mdi-logout"></i>
                <span><?= e(__('common.logout')) ?></span>
            </a>
        </div>
    </aside>

    <!-- ═══ MAIN CONTENT ═══ -->
    <main id="main" class="citoyen-main">
        <?= $content ?>
    </main>
</div>

<!-- ═══ BOTTOM NAV (mobile) ═══ -->
<nav class="citoyen-bottom-nav" aria-label="<?= e(__('citoyen.navigation')) ?>">
    <?php foreach ($items as $item): ?>
        <a class="citoyen-nav-item<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
            <i class="mdi <?= e($item['icon']) ?>"></i>
            <span><?= e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>

    <!-- FAB Scan Button (permanent, accessible everywhere) -->
    <a class="citoyen-scan-fab<?= $scanItem['active'] ? ' active' : '' ?>" href="<?= url('qrcode/scan-optimise') ?>" aria-label="<?= $isAr ? 'مسح' : 'Scanner' ?>" title="<?= $isAr ? 'مسح' : 'Scanner' ?>">
        <i class="mdi mdi-qrcode-scan"></i>
    </a>

    <?php foreach ($itemsCenter as $item): ?>
        <a class="citoyen-nav-item<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
            <i class="mdi <?= e($item['icon']) ?>"></i>
            <span><?= e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<script>window.WH_I18N = <?= json_encode(App\Helpers\I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= asset('/assets/vendor/leaflet/js/leaflet.js') ?>"></script>
<script src="<?= asset('/assets/js/citoyen.js') ?>"></script>
</body>
</html>
