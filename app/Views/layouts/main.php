<?php
/**
 * Layout principal — Back-office administratif (Wilaya / admin).
 *
 * @var string $content
 * @var string $navMode  'admin' (défaut) | 'control'
 */
use App\Helpers\I18n;
use App\Helpers\Notification;
use App\Helpers\Rbac;

$locale   = I18n::locale();
$langAttr = I18n::langAttribute();
$dir      = I18n::direction();
$isAr     = $dir === 'rtl';
$user     = current_user();
$appName  = e(settings('app.name') ?: __('app.name'));
$navMode  = $navMode ?? 'admin';
$current  = $page ?? request_path();

$bootstrapCss = $isAr
    ? '/assets/vendor/bootstrap/bootstrap.rtl.min.css'
    : '/assets/vendor/bootstrap/bootstrap.min.css';

$userFullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
$userInitials = mb_substr((string) ($user['prenom'] ?? ''), 0, 1) . mb_substr((string) ($user['nom'] ?? ''), 0, 1);
$userRole     = user_role();

// Notifications in-app (cloche du header)
$unreadNotifs = 0;
$recentNotifs = [];
$pendingRequestsCount = 0;
if ($user !== null) {
    $userId = (int) $user['id'];
    $unreadNotifs = Notification::unreadCount($userId);
    $recentNotifs = Notification::recent($userId, 8);
    if ($userRole === 'wilaya') {
        $pendingRequestsCount = (int) \App\Helpers\Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'pending'");
    }
}

$isActive = static function (string $prefix) use ($current): bool {
    return str_starts_with($current, $prefix);
};

$adminNav = [
    ['label' => __('common.dashboard'), 'icon' => 'mdi-view-dashboard',     'href' => 'wilaya/dashboard',  'prefix' => 'wilaya/dashboard'],
    ['label' => __('common.evenements'),   'icon' => 'mdi-calendar-star',      'href' => 'wilaya/evenements',  'prefix' => 'wilaya/evenements'],
    ['label' => __('common.epic'),         'icon' => 'mdi-satellite-variant',  'href' => 'admin/epics',        'prefix' => 'admin/epics'],
    ['label' => __('common.anomalies'),    'icon' => 'mdi-alert-octagon',      'href' => 'admin/anomalies',    'prefix' => 'admin/anomalies'],
    ['label' => __('common.users'),       'icon' => 'mdi-account-multiple',    'href' => 'admin/users',        'prefix' => 'admin/users'],
    ['label' => 'Demandes inscription',    'icon' => 'mdi-account-plus',        'href' => 'admin/association-requests', 'prefix' => 'admin/association-requests'],
    ['label' => __('common.statistiques'), 'icon' => 'mdi-chart-box',           'href' => 'admin/stats',         'prefix' => 'admin/stats'],
    ['label' => __('landing.actualites'),  'icon' => 'mdi-web',                'href' => 'admin/landing',      'prefix' => 'admin/landing'],
    ['label' => __('common.gallery'),      'icon' => 'mdi-image-multiple',     'href' => 'wilaya/gallery',     'prefix' => 'wilaya/gallery'],
    ['label' => __('common.qrcode'),       'icon' => 'mdi-qrcode-scan',        'href' => 'qrcode/scan',        'prefix' => 'qrcode'],
];

$controlNav = [
    ['label' => __('common.dashboard'),   'icon' => 'mdi-view-dashboard',    'href' => 'control',            'prefix' => 'control/index'],
    ['label' => __('common.users'),       'icon' => 'mdi-account-multiple',  'href' => 'control/utilisateurs','prefix' => 'control/utilisateurs'],
    ['label' => __('common.associations'),'icon' => 'mdi-handshake',         'href' => 'control/associations','prefix' => 'control/associations'],
    ['label' => __('common.epic'),        'icon' => 'mdi-satellite-variant', 'href' => 'control/epic',        'prefix' => 'control/epic'],
    ['label' => __('common.rules'),       'icon' => 'mdi-scale-balance',     'href' => 'control/regles',      'prefix' => 'control/regles'],
    ['label' => __('common.settings'),    'icon' => 'mdi-cog',               'href' => 'control/parametres',  'prefix' => 'control/parametres'],
    ['label' => __('common.content'),     'icon' => 'mdi-file-document-check','href' => 'control/content',    'prefix' => 'control/content'],
    ['label' => __('common.audit'),       'icon' => 'mdi-clipboard-file',    'href' => 'control/audit',       'prefix' => 'control/audit'],
    ['label' => __('common.supervision'), 'icon' => 'mdi-radar',             'href' => 'control/supervision', 'prefix' => 'control/supervision'],
];

$nav = $navMode === 'control' ? $controlNav : $adminNav;

// Espace EPIC : navigation réduite aux seules vues accessibles à un EPIC.
if ($userRole === 'epic') {
    $epicNav = [
        ['label' => __('common.dashboard'), 'icon' => 'mdi-view-dashboard', 'href' => 'epic/dashboard',       'prefix' => 'epic/dashboard'],
        ['label' => __('common.evenements'), 'icon' => 'mdi-calendar-star', 'href' => 'epic',                 'prefix' => 'epic'],
        ['label' => __('common.export'),     'icon' => 'mdi-file-export',  'href' => 'epic/dashboard/export',  'prefix' => 'epic/dashboard/export'],
    ];
    $nav = $epicNav;
}
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? $appName) ?> — <?= e(__('app.name')) ?></title>
    <link rel="icon" href="<?= asset('/assets/img/icon-192.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
    <script>
        /* Anti-flash du thème */
        (function () {
            var t;
            try { t = localStorage.getItem('wh-theme'); } catch (e) {}
            if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light');
        })();
    </script>
</head>
<body>
<div class="wh-app">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="wh-sidebar" id="whSidebar">
        <a class="wh-brand" href="<?= e(dashboard_path()) ?>">
            <span class="wh-brand-logo"><i class="mdi mdi-map-marker-star-outline"></i></span>
            <span class="wh-brand-name"><?= e($appName) ?>
                <small><?= $isAr ? 'المنصة الرسمية' : 'Plateforme officielle' ?></small>
            </span>
        </a>

        <nav class="wh-nav" aria-label="<?= $isAr ? 'القائمة الرئيسية' : 'Navigation principale' ?>">
            <div class="wh-nav-section"><?= $navMode === 'control' ? ($isAr ? 'مركز المراقبة' : 'Control Center') : ($isAr ? 'الإدارة' : 'Administration') ?></div>
            <?php foreach ($nav as $item): ?>
                <?php if ($navMode === 'admin' && $item['prefix'] === 'qrcode'): ?>
                    <div class="wh-nav-section"><?= $isAr ? 'أدوات' : 'Outils' ?></div>
                <?php endif; ?>
                <a class="nav-link <?= $isActive($item['prefix']) ? 'active' : '' ?>" href="<?= url($item['href']) ?>">
                    <i class="mdi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($navMode === 'admin' && $item['prefix'] === 'admin/association-requests' && $pendingRequestsCount > 0): ?>
                        <span class="wh-badge-pill" data-pending-badge><?= $pendingRequestsCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="wh-sidebar-foot">
            <i class="mdi mdi-shield-check-outline"></i>
            <span><?= $isAr ? 'منصة آمنة ومعتمدة' : 'Plateforme sécurisée et certifiée' ?></span>
        </div>
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
                     <form action="<?= e(dashboard_path()) ?>" method="get" class="m-0">
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
                                <?php foreach ($recentNotifs as $n): $nData = json_decode((string) ($n['data_json'] ?? 'null'), true) ?? []; ?>
                                    <?php
                                        $nUrl = null;
                                        $nType = (string) ($n['type'] ?? '');
                                        if ($nType === 'association_request' && ! empty($nData['request_id']) && $userRole === 'wilaya') {
                                            $nUrl = url('admin/association-requests/' . (int) $nData['request_id']);
                                        } elseif (in_array($nType, ['evenement_create', 'evenement_annule', 'evenement_resoumis', 'routing_alerte', 'sla_retard', 'epic_anomalie'], true) && ! empty($nData['evenement_id'])) {
                                            $nUrl = url('wilaya/evenements/' . (int) $nData['evenement_id']);
                                        } elseif (isset($nData['link'])) {
                                            $nUrl = url((string) $nData['link']);
                                        }
                                        $nRead = (int) ($n['lu'] ?? 0) === 1;
                                    ?>
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
                            <?php endif; ?>
                            <?php if ($userRole === 'wilaya'): ?>
                                <div class="text-center py-2 border-top">
                                    <a class="btn btn-sm btn-outline-primary w-100"
                                       href="<?= url('wilaya/notifications') ?>">
                                        <?= $isAr ? 'عرض كل الإشعارات' : 'Voir toutes les notifications' ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="button" class="wh-icon-btn" data-theme-toggle aria-label="Thème">
                        <i class="mdi mdi-weather-night" data-theme-icon></i>
                    </button>

                    <?php if ($user !== null): ?>
                    <div class="dropdown">
                        <button class="wh-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="wh-user-avatar"><?= e($userInitials !== '' ? $userInitials : '?') ?></span>
                            <span class="wh-user-meta d-none d-sm-block">
                                <strong><?= e($userFullName !== '' ? $userFullName : __('common.welcome')) ?></strong>
                                <small><?= e($userRole ? Rbac::roleLabel($userRole) : '') ?></small>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="<?= url('profile') ?>"><i class="mdi mdi-account-circle me-2"></i><?= $isAr ? 'ملفي الشخصي' : 'Mon profil' ?></a></li>
                            <li><hr class="dropdown-divider"></li>
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
