<?php
/**
 * Layout Membre — Espace membre d'association (role: membre).
 * Distinct de l'espace association (role: association).
 *
 * @var string $content
 */
use App\Helpers\I18n;
use App\Helpers\Notification;

$locale   = I18n::locale();
$langAttr = I18n::langAttribute();
$dir      = I18n::direction();
$isAr     = $dir === 'rtl';
$user     = current_user();
$appName  = e(settings('app.name') ?: __('app.name'));
$current  = $page ?? request_path();
$userRole = user_role();

// Notifications in-app
$unreadNotifs = 0;
$recentNotifs = [];
if ($user !== null) {
    $unreadNotifs = Notification::unreadCount((int) $user['id']);
    $recentNotifs = Notification::recent((int) $user['id'], 8);
}

$notifUrl = static function (array $n): ?string {
    $data = json_decode((string) ($n['data_json'] ?? 'null'), true) ?? [];
    $role = user_role();

    if (isset($data['evenement_id'])) {
        $eventId = (int) $data['evenement_id'];
        return $role === 'membre' ? url('dashboard') : url('association/' . $eventId);
    }
    if (isset($data['request_id'])) {
        return $role === 'wilaya'
            ? url('admin/association-requests/' . (int) $data['request_id'])
            : url('association/demande');
    }
    if (isset($data['link'])) {
        return url((string) $data['link']);
    }
    return null;
};

$bootstrapCss = $isAr
    ? '/assets/vendor/bootstrap/bootstrap.rtl.min.css'
    : '/assets/vendor/bootstrap/bootstrap.min.css';

$userFullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
$userInitials = mb_substr((string) ($user['prenom'] ?? ''), 0, 1) . mb_substr((string) ($user['nom'] ?? ''), 0, 1);

$association = \App\Helpers\Database::one(
    'SELECT * FROM associations WHERE id = ?',
    [(int) ($user['association_id'] ?? 0)]
);

$isActive = static function (string $prefix) use ($current): bool {
    if ($prefix === 'dashboard') {
        return $current === 'dashboard';
    }
    return str_starts_with($current, $prefix);
};

$navItems = [
    ['label' => __('common.dashboard'),       'icon' => 'mdi-view-dashboard',  'href' => 'dashboard',              'prefix' => 'dashboard'],
    ['label' => __('citoyen.nav_my_participations'), 'icon' => 'mdi-clipboard-check-outline', 'href' => 'dashboard/participations', 'prefix' => 'dashboard/participations'],
    ['label' => __('common.profile'),         'icon' => 'mdi-account-circle',  'href' => 'profile',                'prefix' => 'profile'],
    ['label' => __('common.notifications'),   'icon' => 'mdi-bell-ring',       'href' => 'notifications',          'prefix' => 'notifications'],
];
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="حومتي ايفانت">
    <title><?= e($title ?? $appName) ?> — <?= e(__('app.name')) ?></title>
    <link rel="icon" href="<?= asset('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= asset('/apple-touch-icon.png') ?>">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/control-center.css') ?>">
<script>window.WH_I18N = <?= json_encode(App\Helpers\I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;
window.WH_CSRF = <?= json_encode(App\Helpers\Csrf::token()) ?>;</script>
</head>
<body>
<div class="wh-app">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="wh-sidebar" id="whSidebar">
        <a class="wh-brand" href="<?= url('dashboard') ?>">
            <span class="wh-brand-logo"><i class="mdi mdi-account-circle"></i></span>
            <span class="wh-brand-name"><?= e($appName) ?>
                <small><?= $isAr ? 'مؤشر العضو' : 'Espace membre' ?></small>
            </span>
        </a>

        <nav class="wh-nav" aria-label="<?= $isAr ? 'القائمة الرئيسية' : 'Navigation principale' ?>">
            <div class="wh-nav-section"><?= $isAr ? 'إدارة العضوية' : 'Gestion membre' ?></div>
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link <?= $isActive($item['prefix']) ? 'active' : '' ?>" href="<?= url($item['href']) ?>">
                    <i class="mdi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($association): ?>
        <div class="wh-sidebar-footer">
            <div class="small text-muted px-3 py-2 border-top">
                <div class="fw-bold"><?= e($association['nom'] ?? '') ?></div>
                <div class="text-truncate"><?= e($association['wilaya'] ?? '') ?></div>
            </div>
        </div>
        <?php endif; ?>
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
                    <form action="<?= e(url('dashboard')) ?>" method="get" class="m-0">
                        <input type="text" name="q" class="form-control" placeholder="<?= e(__('common.search')) ?>" aria-label="<?= e(__('common.search')) ?>">
                    </form>
                </div>

                <div class="ms-auto wh-header-actions">
                    <a class="wh-icon-btn" href="<?= url('/') ?>" title="<?= $isAr ? 'عرض الموقع' : 'Voir le site' ?>">
                        <i class="mdi mdi-earth"></i>
                    </a>

                    <?php if ($user !== null): ?>
                    <div class="dropdown wh-notif">
                        <button type="button" class="wh-icon-btn" data-bs-toggle="dropdown" aria-expanded="false"
                                aria-label="<?= $isAr ? 'التنبيهات' : 'Notifications' ?>">
                            <i class="mdi mdi-bell-outline"></i>
                            <span class="wh-notif-badge" data-notif-badge id="notifBadge"
                                  <?= $unreadNotifs > 0 ? '' : 'style="display:none"' ?>><?= $unreadNotifs ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm wh-notif-menu">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                <strong class="small"><?= $isAr ? 'التنبيهات' : 'Notifications' ?></strong>
                                <?php if ($unreadNotifs > 0): ?>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-primary text-decoration-none"
                                            data-notif-read-all><?= $isAr ? 'قراءة الكل' : 'Tout marquer lu' ?></button>
                                <?php endif; ?>
                            </div>
                            <?php if ($recentNotifs === []): ?>
                                <div class="wh-empty p-3 text-center text-muted small">
                                    <?= $isAr ? 'لا توجد تنبيهات' : 'Aucune notification' ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentNotifs as $n): $nUrl = $notifUrl($n); $nRead = (int) ($n['lu'] ?? 0) === 1; ?>
                                    <a class="dropdown-item wh-notif-item <?= $nRead ? 'read' : '' ?>"
                                       href="<?= e($nUrl ?? '#') ?>"
                                       data-notif-id="<?= (int) $n['id'] ?>"
                                       <?= $nUrl === null ? 'data-notif-nolink="1"' : '' ?>>
                                        <div class="d-flex gap-2 align-items-start">
                                            <i class="mdi <?= $nRead ? 'mdi-bell-outline' : 'mdi-bell-ring' ?> wh-notif-icon"></i>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="fw-semibold small wh-notif-title"><?= e($n['titre']) ?></div>
                                                <div class="small text-muted text-truncate"><?= e($n['message_notif']) ?></div>
                                                <small class="text-muted d-block" style="font-size:.7rem">
                                                    <?= e(date('d/m H:i', strtotime((string) $n['date_creation']))) ?>
                                                </small>
                                            </div>
                                            <?php if (! $nRead): ?>
                                                <span class="wh-notif-dot"></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <div class="text-center py-2 border-top">
                                    <a class="btn btn-sm btn-outline-primary w-100"
                                       href="<?= url('notifications') ?>">
                                        <?= $isAr ? 'عرض كل الإشعارات' : 'Voir toutes les notifications' ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

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
                            <li><a class="dropdown-item" href="<?= url('profile') ?>"><i class="mdi mdi-account-circle me-2"></i><?= $isAr ? 'ملفي الشخصي' : 'Mon profil' ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= url('auth/logout') ?>" data-confirm="<?= e(__('common.logout_confirm')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item" style="background:none;border:none;width:100%;text-align:left;cursor:pointer"><i class="mdi mdi-logout me-2"></i><?= e(__('common.logout')) ?></button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="wh-content">
            <?php $success = flash('success'); $error = flash('error'); ?>
            <?php if ($success !== null): ?>
                <script>document.addEventListener('DOMContentLoaded', function () { showToast(<?= json_encode($success) ?>, 'success'); });</script>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <script>document.addEventListener('DOMContentLoaded', function () { showToast(<?= json_encode($error) ?>, 'error'); });</script>
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

<script src="<?= asset('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('<?= asset('/sw.js') ?>').catch(function () {});
    });
}
</script>
<script src="<?= asset('/assets/js/admin.js') ?>"></script>
</body>
</html>