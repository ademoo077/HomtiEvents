<?php
/**
 * Layout Association — Interface dédiée aux associations.
 *
 * @var string $content
 */
use App\Helpers\I18n;
use App\Helpers\Rbac;

$locale   = I18n::locale();
$langAttr = I18n::langAttribute();
$dir      = I18n::direction();
$isAr     = $dir === 'rtl';
$user     = current_user();
$appName  = e(settings('app.name') ?: __('app.name'));
$current  = $page ?? request_path();
$userRole = user_role();

$bootstrapCss = $isAr
    ? '/assets/vendor/bootstrap/bootstrap.rtl.min.css'
    : '/assets/vendor/bootstrap/bootstrap.min.css';

$userFullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
$userInitials = mb_substr((string) ($user['prenom'] ?? ''), 0, 1) . mb_substr((string) ($user['nom'] ?? ''), 0, 1);
$association  = \App\Helpers\Database::one(
    'SELECT * FROM associations WHERE id = ?',
    [(int) ($user['association_id'] ?? 0)]
);

$isActive = static function (string $prefix) use ($current): bool {
    return str_starts_with($current, $prefix);
};

$navItems = [
    ['label' => __('common.dashboard'),     'icon' => 'mdi-view-dashboard',  'href' => 'association',        'prefix' => 'association'],
    ['label' => __('common.evenements'),     'icon' => 'mdi-calendar-star',   'href' => 'association/create', 'prefix' => 'association/create'],
];
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? $appName) ?> — <?= e(__('app.name')) ?></title>
    <link rel="icon" href="<?= asset('/assets/img/icon-192.png') ?>">
    <link rel="manifest" href="<?= asset('/manifest.json') ?>">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
</head>
<body>
<div class="wh-app">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="wh-sidebar" id="whSidebar">
        <a class="wh-brand" href="<?= url('association') ?>">
            <span class="wh-brand-logo"><i class="mdi mdi-hand-heart-outline"></i></span>
            <span class="wh-brand-name"><?= e($appName) ?>
                <small><?= $isAr ? 'المنصة الرسمية' : 'Plateforme officielle' ?></small>
            </span>
        </a>

        <nav class="wh-nav" aria-label="<?= $isAr ? 'القائمة الرئيسية' : 'Navigation principale' ?>">
            <div class="wh-nav-section"><?= $isAr ? 'إدارة الجمعية' : 'Gestion de l\'association' ?></div>
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link <?= $isActive($item['prefix']) ? 'active' : '' ?>" href="<?= url($item['href']) ?>">
                    <i class="mdi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- ═══ OVERLAY MOBILE ═══ -->
    <div id="whSidebarBackdrop" data-sidebar-close style="display:none"></div>

    <!-- ═══ CONTENU PRINCIPAL ═══ -->
    <div class="wh-main">
        <header class="wh-header">
            <div class="d-flex align-items-center gap-3 w-100">
                <button type="button" class="btn btn-link wh-icon-btn wh-sidebar-toggle p-0" data-sidebar-open
                        aria-label="<?= $isAr ? 'فتح القائمة' : 'Ouvrir le menu' ?>">
                    <i class="mdi mdi-menu"></i>
                </button>

                <div class="wh-search d-none d-md-block">
                     <i class="mdi mdi-magnify"></i>
                     <form action="<?= e(url('association')) ?>" method="get" class="m-0">
                         <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" aria-label="<?= e(__('common.search')) ?>">
                     </form>
                 </div>

                <div class="ms-auto wh-header-actions">
                    <a class="wh-icon-btn" href="<?= url('/') ?>" title="<?= $isAr ? 'عرض الموقع' : 'Voir le site' ?>">
                        <i class="mdi mdi-earth"></i>
                    </a>

                    <?php if ($isAr): ?>
                        <a class="wh-icon-btn" href="<?= url('lang/fr') ?>" title="Français">FR</a>
                    <?php else: ?>
                        <a class="wh-icon-btn" href="<?= url('lang/ar') ?>" title="العربية">ع</a>
                    <?php endif; ?>

                    <?php if ($user !== null): ?>
                    <div class="dropdown">
                        <button class="wh-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="wh-user-avatar"><?= e($userInitials !== '' ? $userInitials : '?') ?></span>
                            <span class="wh-user-meta d-none d-sm-block">
                                <strong><?= e($userFullName !== '' ? $userFullName : __('common.welcome')) ?></strong>
                                <small><?= e($association['nom'] ?? __('common.association')) ?></small>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="<?= url('auth/logout') ?>"><i class="mdi mdi-logout me-2"></i><?= e(__('common.logout')) ?></a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="wh-content">
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

            <?= $content ?>
        </main>

        <footer class="wh-footer">
            <span>© <?= date('Y') ?> <?= e($appName) ?> — <?= $isAr ? 'جميع الحقوق محفوظة' : __('landing.footer_droits') ?></span>
            <span class="d-flex align-items-center gap-1">
                <i class="mdi mdi-shield-lock-outline"></i>
                <?= $isAr ? 'بوابة محمية' : 'Portail sécurisé' ?>
            </span>
        </footer>
    </div>
</div>

<div class="wh-toast-wrap"></div>

<script>window.WH_I18N = <?= json_encode(App\Helpers\I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;
window.WH_CSRF = <?= json_encode(App\Helpers\Csrf::token()) ?>;</script>
<script src="<?= asset('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('/assets/js/admin.js') ?>"></script>
</body>
</html>