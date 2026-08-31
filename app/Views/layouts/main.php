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
    ['label' => $isAr ? 'التقويم' : 'Calendrier', 'icon' => 'mdi-calendar-month', 'href' => 'wilaya/calendrier', 'prefix' => 'wilaya/calendrier'],
    ['label' => ($isAr ? 'متابعة مباشرة' : 'Suivi en direct'), 'icon' => 'mdi-map-marker-radius', 'href' => 'wilaya/suivi', 'prefix' => 'wilaya/suivi'],
    ['label' => __('common.epic'),         'icon' => 'mdi-satellite-variant',  'href' => 'admin/epics',        'prefix' => 'admin/epics'],
    ['label' => __('common.anomalies'),    'icon' => 'mdi-alert-octagon',      'href' => 'admin/anomalies',    'prefix' => 'admin/anomalies'],
    ['label' => __('common.users'),       'icon' => 'mdi-account-multiple',    'href' => 'admin/users',        'prefix' => 'admin/users'],
    ['label' => __('associations.inscription_request'), 'icon' => 'mdi-account-plus',        'href' => 'admin/association-requests', 'prefix' => 'admin/association-requests'],
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

// Espace membre d'association : navigation réduite, sans les volets d'administration.
if ($userRole === 'membre') {
    $membreNav = [
        ['label' => __('common.dashboard'), 'icon' => 'mdi-view-dashboard',  'href' => 'dashboard', 'prefix' => 'dashboard'],
        ['label' => $isAr ? 'ملفي الشخصي' : 'Mon profil', 'icon' => 'mdi-account-circle-outline', 'href' => 'profile', 'prefix' => 'profile'],
    ];
    $nav = $membreNav;
}
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
    <meta name="apple-mobile-web-app-title" content="<?= e(settings('app.name') ?: __('app.name')) ?>">
    <meta name="application-name" content="<?= e(settings('app.name') ?: __('app.name')) ?>">
    <meta name="msapplication-TileColor" content="#0F2B22">
    <meta name="msapplication-TileImage" content="<?= asset('/assets/img/icon-144.png') ?>">
    <meta name="theme-color" content="#0F2B22">
    <title><?= e($title ?? $appName) ?> — <?= e(__('app.name')) ?></title>
    <link rel="icon" href="<?= asset('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/design-tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/control-center.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/tailwind.css') ?>">
<script>window.WH_I18N = <?= json_encode(I18n::lines(), JSON_UNESCAPED_UNICODE) ?>;
window.WH_CSRF = <?= json_encode(\App\Helpers\Csrf::token()) ?>;</script>
</head>
<body>
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
.wh-demo-banner{position:fixed;top:0;inset-inline:0;z-index:9999;display:flex;align-items:center;justify-content:space-between;padding:7px 16px;background:linear-gradient(90deg,#fbbf24,#f59e0b);color:#451a03;font-size:.8rem;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.1);direction:ltr}
html[dir="rtl"] .wh-demo-banner{direction:rtl}
.wh-demo-banner-inner{display:flex;align-items:center;gap:8px;justify-content:center;flex:1}
.wh-demo-banner .mdi-information-outline{font-size:1.05rem}
.wh-demo-close{background:none;border:none;cursor:pointer;color:#451a03;padding:4px;border-radius:4px;display:flex;align-items:center;justify-content:center;transition:background .2s}
.wh-demo-close:hover{background:rgba(0,0,0,.1)}
.wh-demo-close .mdi{font-size:1.05rem}
</style>
<script>
(function(){var b=document.getElementById('demoBanner');if(b&&localStorage.getItem('wh_demo_closed')==='1'){b.style.display='none'}if(b){b.querySelector('.wh-demo-close').addEventListener('click',function(){localStorage.setItem('wh_demo_closed','1')})}})();
</script>

<div class="wh-app">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="wh-sidebar" id="whSidebar">
        <a class="wh-brand" href="<?= e(dashboard_path()) ?>">
            <span class="wh-brand-logo"><i class="mdi mdi-map-marker-star-outline"></i></span>
            <span class="wh-brand-name"><?= e($appName) ?>
                <small><?= $isAr ? 'المنصة الرسمية' : 'Plateforme officielle' ?></small>
            </span>
        </a>

        <nav class="wh-nav" aria-label="<?= $isAr ? 'القائمة principale' : 'Navigation principale' ?>">
            <div class="wh-nav-section"><?= $navMode === 'control'
                ? ($isAr ? 'مركز المراقبة' : 'Control Center')
                : ($userRole === 'epic'
                    ? ($isAr ? 'العمليات' : 'Opérations')
                    : ($userRole === 'membre'
                        ? ($isAr ? 'مساحة العضو' : 'Espace membre')
                        : ($isAr ? 'الإدارة' : 'Administration'))) ?></div>
            <?php foreach ($nav as $item): ?>
                <?php if ($navMode === 'admin' && $item['prefix'] === 'qrcode'): ?>
                    <div class="wh-nav-section"><?= $isAr ? 'Outils' : 'Outils' ?></div>
                <?php endif; ?>
                <a class="nav-link <?= $isActive($item['prefix']) ? 'active' : '' ?>" href="<?= url($item['href']) ?>" data-nav-section>
                    <i class="mdi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($navMode === 'admin' && $item['prefix'] === 'admin/association-requests' && $pendingRequestsCount > 0): ?>
                        <span class="wh-badge-pill" data-pending-badge><?= $pendingRequestsCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <div class="wh-nav-search d-none d-md-block">
                <i class="mdi mdi-magnify"></i>
                <form action="#" method="get" class="m-0">
                    <input type="text" placeholder="<?= e(__('common.search')) ?>" aria-label="<?= e(__('common.search')) ?>" class="form-control">
                </form>
            </div>
        </nav>

        <div class="wh-sidebar-foot">
            <?php if ($user !== null): ?>
            <a class="wh-sidebar-user" href="<?= url('profile') ?>" title="<?= $isAr ? 'ملفي الشخصي' : 'Mon profil' ?>">
                <span class="wh-sidebar-user-avatar"><?= e($userInitials !== '' ? $userInitials : '?') ?></span>
                <span class="wh-sidebar-user-meta">
                    <strong><?= e($userFullName !== '' ? $userFullName : __('common.welcome')) ?></strong>
                    <small><?= e($userRole ? Rbac::roleLabel($userRole) : '') ?></small>
                </span>
            </a>
            <div class="d-flex align-items-center gap-2 mt-2">
                <a class="btn btn-sm btn-light w-100 py-1" href="<?= url('profile') ?>">
                    <i class="mdi mdi-account-circle me-1"></i><?= $isAr ? 'الملف الشخصي' : 'Profil' ?>
                </a>
                <form method="post" action="<?= url('auth/logout') ?>" data-confirm="<?= e(__('common.logout_confirm')) ?>" class="flex-shrink-0 m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-light py-1" title="<?= e(__('common.logout')) ?>">
                        <i class="mdi mdi-logout"></i>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="wh-sidebar-foot-note"><i class="mdi mdi-shield-check-outline me-1"></i><?= $isAr ? 'منصة آمنة ومعتمدة' : 'Plateforme sécurisée et certifiée' ?></div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ═══ OVERLAY MOBILE ═══ -->
    <div id="whSidebarBackdrop" data-sidebar-close></div>

    <!-- ═══ CONTENU PRINCIPAL ═══ -->
    <div class="wh-main">
        <header class="wh-header wh-glass">
            <div class="d-flex align-items-center gap-3 w-100">
                <button type="button" class="btn btn-link wh-icon-btn wh-sidebar-toggle p-0" data-sidebar-open
                        aria-label="<?= $isAr ? 'فتح القائمة' : 'Ouvrir le menu' ?>">
                    <i class="mdi mdi-menu"></i>
                </button>
                <button type="button" class="wh-sidebar-toggle-collapse d-none d-lg-inline-grid" id="collapseToggle" title="<?= $isAr ? 'طي القائمة' : 'Réduire' ?>"><i class="mdi mdi-chevron-left"></i></button>

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

<button type="button" class="wh-scroll-top" id="scrollTopBtn" aria-label="<?= $isAr ? 'العودة للأعلى' : 'Retour en haut' ?>">
    <i class="mdi mdi-chevron-up"></i>
</button>

<script src="<?= asset('/assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('<?= asset('/sw.js') ?>').catch(function () {});
    });
}
// ── Notifications temps réel : polling badge + toasts (SSE fallback)
(function(){
    var badge=document.getElementById('notifBadge');
    if(!badge) return;
    var lastCount=parseInt(badge.textContent||'0',10)||0;
    var url='<?= url('api/notifications/unread') ?>';
    var fetching=false;
    function bump(count){
        badge.textContent=count;
        badge.style.display= count>0 ? '' : 'none';
        if(count>lastCount && typeof showToast==='function'){
            var diff=count-lastCount;
            showToast(diff+' nouvelle(s) notification(s)', 'info');
        }
        // WebPush badge if available
        if('setAppBadge' in navigator){ try{ navigator.setAppBadge(count||0);}catch(e){} }
        lastCount=count;
    }
    async function poll(){
        if(fetching) return; fetching=true;
        try{ var r=await fetch(url,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}); var j=await r.json(); if(j && j.success) bump(parseInt(j.count,10)||0); }catch(e){}
        fetching=false;
    }
    setInterval(poll,15000);
    // SSE si supporté (EventSource sur /api/notifications/stream — dégrade gracieusement)
    try{
        var esUrl='<?= url('api/notifications/stream') ?>';
        var es=new EventSource(esUrl);
        es.onmessage=function(ev){ try{ var d=JSON.parse(ev.data); if(d.count!=null) bump(parseInt(d.count,10)||0); }catch(e){} };
        es.onerror=function(){ es.close(); };
    }catch(e){}
})();
// ── Command Palette IA (Ctrl+K)
(function(){
  var palette=document.getElementById('whPalette');
  if(!palette) return;
  var input=palette.querySelector('.wh-command-input'), list=palette.querySelector('.wh-command-list');
  var items=[
    {label:'Dashboard', icon:'mdi-view-dashboard', href:'<?= url('wilaya/dashboard') ?>', keys:'dashboard'},
    {label:'Événements — Liste', icon:'mdi-calendar-star', href:'<?= url('wilaya/evenements') ?>', keys:'evenements liste'},
    {label:'Événements — Créer', icon:'mdi-plus', href:'<?= url('wilaya/evenements/create') ?>', keys:'creer evenement'},
    {label:'Calendrier', icon:'mdi-calendar-month', href:'<?= url('wilaya/calendrier') ?>', keys:'calendrier'},
    {label:'Suivi en direct', icon:'mdi-map-marker-radius', href:'<?= url('wilaya/suivi') ?>', keys:'suivi carte map'},
    {label:'EPICs', icon:'mdi-satellite-variant', href:'<?= url('admin/epics') ?>', keys:'epic'},
    {label:'Anomalies', icon:'mdi-alert-octagon', href:'<?= url('admin/anomalies') ?>', keys:'anomalies'},
    {label:'Utilisateurs', icon:'mdi-account-multiple', href:'<?= url('admin/users') ?>', keys:'users utilisateurs'},
    {label:'Demandes', icon:'mdi-account-plus', href:'<?= url('admin/association-requests') ?>', keys:'demandes association'},
    {label:'Statistiques', icon:'mdi-chart-box', href:'<?= url('admin/stats') ?>', keys:'stats statistiques'},
    {label:'Landing CMS', icon:'mdi-web', href:'<?= url('admin/landing') ?>', keys:'landing cms'},
    {label:'Galerie', icon:'mdi-image-multiple', href:'<?= url('wilaya/gallery') ?>', keys:'galerie photos'},
    {label:'Notifications', icon:'mdi-bell-outline', href:'<?= url('wilaya/notifications') ?>', keys:'notifications'},
  ];
  function render(q){
    list.innerHTML='';
    var f=items.filter(function(it){ return !q || (it.label+it.keys).toLowerCase().indexOf(q.toLowerCase())!==-1; }).slice(0,8);
    if(!f.length){ list.innerHTML='<div class="wh-command-empty">Aucun résultat</div>'; return;}
    f.forEach(function(it,i){
      var el=document.createElement('div'); el.className='wh-command-item'+(i===0?' is-active':'');
      el.innerHTML='<i class="mdi '+it.icon+'"></i><span>'+it.label+'</span><span style="margin-left:auto;opacity:.4;font-size:.72rem">↵</span>';
      el.addEventListener('click',function(){ location.href=it.href; });
      list.appendChild(el);
    });
  }
  function open(){ palette.classList.add('is-open'); input.value=''; render(''); setTimeout(function(){ input.focus(); },10); }
  function close(){ palette.classList.remove('is-open'); }
  document.addEventListener('keydown',function(e){
    if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='k'){ e.preventDefault(); palette.classList.contains('is-open')?close():open(); }
    if(e.key==='Escape' && palette.classList.contains('is-open')) close();
  });
  palette.addEventListener('click',function(e){ if(e.target===palette) close(); });
  input.addEventListener('input',function(){ render(input.value); });
  input.addEventListener('keydown',function(e){
    if(e.key==='Enter'){ var a=list.querySelector('.wh-command-item.is-active'); if(a) a.click(); }
    if(e.key==='ArrowDown' || e.key==='ArrowUp'){
      e.preventDefault();
      var els=[...list.querySelectorAll('.wh-command-item')]; var idx=els.findIndex(function(x){return x.classList.contains('is-active')});
      if(els.length){ els.forEach(function(x){x.classList.remove('is-active')}); var n=e.key==='ArrowDown'?(idx+1)%els.length:(idx-1+els.length)%els.length; els[n].classList.add('is-active'); }
    }
  });
  document.querySelector('.wh-search input')?.addEventListener('focus',function(){ open(); });
  document.getElementById('collapseToggle')?.addEventListener('click',function(){
    document.querySelector('.wh-app').classList.toggle('has-collapsed');
    localStorage.setItem('wh_collapsed', document.querySelector('.wh-app').classList.contains('has-collapsed')?'1':'0');
    this.querySelector('.mdi').className = document.querySelector('.wh-app').classList.contains('has-collapsed') ? 'mdi mdi-chevron-right' : 'mdi mdi-chevron-left';
  });
  try{ if(localStorage.getItem('wh_collapsed')==='1'){ document.querySelector('.wh-app').classList.add('has-collapsed'); var ic=document.querySelector('#collapseToggle .mdi'); if(ic) ic.className='mdi mdi-chevron-right'; } }catch(e){}
})();
</script>
<div class="wh-command-palette" id="whPalette" role="dialog" aria-modal="true"><div class="wh-command-box"><input class="wh-command-input" placeholder="Rechercher… (Ctrl+K) — ex: evenements, suivi, epic"><div class="wh-command-list"></div></div></div>
<script src="<?= asset('/assets/js/admin.js') ?>"></script>
</body>
</html>
