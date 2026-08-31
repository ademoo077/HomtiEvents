<?php
/** @var string $content */
use App\Helpers\I18n;

$locale  = I18n::locale();
$langAttr = I18n::langAttribute();
$dir     = I18n::direction();
$isAr    = $dir === 'rtl';
$appName = e(settings('app.name') ?: __('app.name'));
$isLogged = is_logged();

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
    $nav('citoyen/favoris', 'citoyen.nav_favoris', 'mdi-heart-outline'),
    $nav('citoyen/notifications', 'citoyen.nav_notifications', 'mdi-bell-outline'),
    $nav('citoyen/profile', 'citoyen.nav_profile', 'mdi-account-circle-outline'),
];
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0F2B22">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= e(__('app.name')) ?>">
    <meta name="application-name" content="<?= e(__('app.name')) ?>">
    <?php if ($isLogged): ?>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?php endif; ?>
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="icon" href="<?= asset('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('/apple-touch-icon.png') ?>">
    <title><?= e($title ?? __('citoyen.title')) ?> — <?= e(__('app.name')) ?></title>
    <meta name="description" content="<?= e((string) ($og['description'] ?? __('citoyen.meta_description'))) ?>">
    <meta property="og:title" content="<?= e(($og['title'] ?? (__('app.name')))) ?>">
    <meta property="og:description" content="<?= e((string) ($og['description'] ?? __('citoyen.meta_description'))) ?>">
    <meta property="og:image" content="<?= e((string) ($og['image'] ?? asset('/assets/img/icon-192.png'))) ?>">
    <meta property="og:type" content="<?= ($og ?? null) ? 'article' : 'website' ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <?php $needsLeaflet = str_contains($currentPath, 'explorer') || str_contains($currentPath, 'evenement'); ?>
    <?php if ($needsLeaflet): ?>
    <link rel="stylesheet" href="<?= asset('/assets/vendor/leaflet/css/leaflet.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('/assets/css/citoyen.css') ?>">
    <script>
        window.WH_I18N = <?= json_encode(App\Helpers\I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;
        window.WH_CSRF = <?= json_encode(App\Helpers\Csrf::token()) ?>;
    </script>
    <noscript>
        <style>[data-reveal]{opacity:1;transform:none;transition:none}</style>
    </noscript>
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
            <?php if ($isAr): ?>
                <a class="citoyen-icon-btn" href="<?= url('lang/fr') ?>" aria-label="Français" title="Français" style="font-weight:700;font-size:.85rem;width:auto;padding:0 10px">FR</a>
            <?php else: ?>
                <a class="citoyen-icon-btn" href="<?= url('lang/ar') ?>" aria-label="العربية" title="العربية" style="font-weight:700;font-size:1rem;width:auto;padding:0 10px">ع</a>
            <?php endif; ?>
            <?php if ($isLogged): ?>
                <a class="citoyen-icon-btn" href="<?= url('citoyen/notifications') ?>" aria-label="<?= e(__('citoyen.nav_notifications')) ?>" title="<?= e(__('citoyen.nav_notifications')) ?>" style="position:relative">
                    <i class="mdi mdi-bell-outline"></i>
                </a>
                <form method="post" action="<?= url('auth/logout') ?>" data-confirm="<?= e(__('common.logout_confirm')) ?>" class="d-inline" style="margin:0;padding:0">
                    <?= csrf_field() ?>
                    <button type="submit" class="citoyen-icon-btn" aria-label="<?= e(__('common.logout')) ?>" title="<?= e(__('common.logout')) ?>" style="background:none;border:none;padding:0;cursor:pointer;line-height:0">
                        <i class="mdi mdi-logout"></i>
                    </button>
                </form>
            <?php else: ?>
                <a class="citoyen-icon-btn" href="<?= url('auth/login') ?>" aria-label="<?= $isAr ? 'دخول' : 'Connexion' ?>" title="<?= $isAr ? 'دخول' : 'Connexion' ?>">
                    <i class="mdi mdi-login"></i>
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
    <?php if ($isLogged): ?>
    <a class="citoyen-top-link<?= $scanItem['active'] ? ' active' : '' ?>" href="<?= url($scanItem['route']) ?>">
        <i class="mdi <?= e($scanItem['icon']) ?>"></i><span><?= e($scanItem['label']) ?></span>
    </a>
    <?php foreach ($itemsCenter as $item): ?>
        <a class="citoyen-top-link<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
            <i class="mdi <?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
    <?php endif; ?>
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
            <?php foreach ($items as $item): ?>
                <a class="citoyen-sidebar-link<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
                    <i class="mdi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ($isLogged): ?>
            <a class="citoyen-sidebar-link<?= $scanItem['active'] ? ' active' : '' ?>" href="<?= url($scanItem['route']) ?>">
                <i class="mdi <?= e($scanItem['icon']) ?>"></i>
                <span><?= e($scanItem['label']) ?></span>
            </a>
            <?php foreach ($itemsCenter as $item): ?>
                <a class="citoyen-sidebar-link<?= $item['active'] ? ' active' : '' ?>" href="<?= url($item['route']) ?>">
                    <i class="mdi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </nav>
        <?php if ($isLogged): ?>
        <div class="citoyen-sidebar-footer">
            <form method="post" action="<?= url('auth/logout') ?>" data-confirm="<?= e(__('common.logout_confirm')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="citoyen-sidebar-link" style="background:none;border:none;width:100%;text-align:start;cursor:pointer;padding:0;color:inherit">
                    <i class="mdi mdi-logout"></i>
                    <span><?= e(__('common.logout')) ?></span>
                </button>
            </form>
        </div>
        <?php endif; ?>
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

    <?php if ($isLogged): ?>
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
    <?php endif; ?>
</nav>

<!-- ═══ OFFLINE BANNER ═══ -->
<div class="wh-offline-banner" id="pwaOfflineBanner" role="status" style="display:none">
    <i class="mdi mdi-cloud-off-outline"></i>
    <span><?= $isAr ? 'أنت بدون اتصال — البيانات ستُزامن عند عودة الاتصال' : 'Vous êtes hors ligne — les données se synchroniseront à la reconnexion' ?></span>
</div>

<!-- ═══ TOAST ═══ -->
<style>
/* ── Offline banner ── */
.wh-offline-banner{position:fixed;top:0;left:0;right:0;z-index:10003;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;background:#7A5C00;color:#FEF3C7;font-size:.82rem;font-weight:600;text-align:center}
/* ── Toast ── */
.wh-toast{position:fixed;top:16px;left:50%;transform:translateX(-50%) translateY(-120%);z-index:10004;transition:transform .4s cubic-bezier(.17,.89,.32,1.28)}
.wh-toast.show{transform:translateX(-50%) translateY(0)}
.wh-toast-msg{display:inline-block;padding:10px 18px;border-radius:12px;background:var(--cit-card-bg,#fff);color:var(--cit-text,#22332B);font-size:.85rem;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,.18);border-left:4px solid var(--cit-primary,#1A4D3E)}
.wh-toast-error .wh-toast-msg{border-left-color:var(--cit-red,#E5484D)}
.wh-toast-success .wh-toast-msg{border-left-color:var(--cit-green,#2E6E5C)}
/* ── Pull-to-refresh ── */
.wh-pull-indicator{position:fixed;top:60px;left:50%;transform:translateX(-50%) translateY(-60px);z-index:10001;transition:transform .3s ease}
.wh-pull-indicator.ready .wh-pull-spinner{width:36px;height:36px;border:3px solid var(--cit-border,#E3E9E1);border-top-color:var(--cit-primary,#1A4D3E);border-radius:50%;animation:whSpin .75s linear infinite}
.wh-pull-indicator.refreshing .wh-pull-spinner{animation:whSpin .6s linear infinite}
.wh-pull-indicator.ready{transform:translateX(-50%) translateY(0)}
@keyframes whSpin{to{transform:rotate(360deg)}}
/* ── View transition ── */
@keyframes fadeSlideIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
::view-transition-old(root){animation:none}
::view-transition-new(root){animation:fadeSlideIn .25s ease}
</style>

<?php if ($needsLeaflet): ?>
<script src="<?= asset('/assets/vendor/leaflet/js/leaflet.js') ?>"></script>
<?php endif; ?>
<script src="<?= asset('/assets/js/citoyen.js') ?>"></script>
</body>
</html>
