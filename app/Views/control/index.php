<?php
/** @var array $modules @var array $regles @var array $securite @var array $statistiques */
use App\Helpers\I18n;

$title = __('common.dashboard');
$page  = 'control.index';
$dir    = I18n::direction();
$isAr   = $dir === 'rtl';

$tabs = [
    'dashboard'     => ['label' => $isAr ? 'لوحة التحكم' : 'Tableau de bord', 'icon' => 'mdi-view-dashboard'],
    'users'         => ['label' => $isAr ? 'المستخدمون' : 'Utilisateurs', 'icon' => 'mdi-account-multiple'],
    'epics'         => ['label' => $isAr ? 'EPICs' : 'EPICs', 'icon' => 'mdi-satellite-variant'],
    'communes'      => ['label' => $isAr ? 'البلديات' : 'Communes', 'icon' => 'mdi-city'],
    'associations'  => ['label' => $isAr ? 'الجمعيات' : 'Associations', 'icon' => 'mdi-account-group'],
    'rules'         => ['label' => $isAr ? 'القواعد' : 'Règles métier', 'icon' => 'mdi-scale-balance'],
    'settings'      => ['label' => $isAr ? 'الإعدادات' : 'Paramètres', 'icon' => 'mdi-cog-outline'],
    'audit'         => ['label' => $isAr ? 'سجل التدقيق' : 'Audit Logs', 'icon' => 'mdi-file-document-outline'],
    'content'       => ['label' => $isAr ? 'المحتوى' : 'Contenu', 'icon' => 'mdi-file-document-multiple-outline'],
    'security'      => ['label' => $isAr ? 'الأمان' : 'Sécurité', 'icon' => 'mdi-shield-check'],
];

$activeTab = (string) input('tab', 'dashboard');
?>
<div class="futur-control">
    <div class="futur-control-header mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="futur-control-title mb-1"><i class="mdi mdi-view-dashboard"></i> <?= e(__('control.center')) ?></h2>
                <p class="futur-control-sub mb-0"><?= $isAr ? 'مركز المراقبة — إدارة شاملة للمنصة' : 'Control Center — Supervision et gestion centralisée de la plateforme' ?></p>
            </div>
        </div>

        <nav class="futur-tabs" role="tablist" aria-label="<?= e(__('control.tabs')) ?>">
            <?php foreach ($tabs as $key => $tab): ?>
                <a class="futur-tab <?= $activeTab === $key ? 'active' : '' ?>"
                   href="<?= url('control?tab=' . $key) ?>"
                   data-tab="<?= e($key) ?>"
                   role="tab"
                   aria-selected="<?= $activeTab === $key ? 'true' : 'false' ?>">
                    <i class="mdi <?= $tab['icon'] ?>"></i>
                    <span><?= e($tab['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div id="cc-tab-content">
        <?php
        $tabFile = __DIR__ . '/control.tabs/' . $activeTab . '.php';
        if (is_file($tabFile)) {
            include $tabFile;
        } else {
            include __DIR__ . '/control.tabs/dashboard.php';
        }
        ?>
    </div>
</div>

<script>
(function () {
    var container = document.getElementById('cc-tab-content');
    var tabs      = document.querySelectorAll('.futur-tab[data-tab]');
    var current   = <?= json_encode($activeTab) ?>;
    var baseUrl   = <?= json_encode(url('control')) ?>;
    var tabUrl    = <?= json_encode(url('control/tab/')) ?>;

    /* ── Restaurer dernier onglet visité ─────────────────────── */
    try {
        var saved = sessionStorage.getItem('cc_last_tab');
        if (saved && saved !== current && document.querySelector('.futur-tab[data-tab="' + saved + '"]')) {
            navigateTo(saved, false);
        }
    } catch (e) { /* sessionStorage indisponible */ }

    /* ── Clic sur un onglet ─────────────────────────────────── */
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            var target = this.getAttribute('data-tab');
            if (target === current) return;
            navigateTo(target, true);
        });
    });

    /* ── Navigation ─────────────────────────────────────────── */
    function navigateTo(tab, pushState) {
        /* Spinner sur l'onglet cible */
        var targetEl = document.querySelector('.futur-tab[data-tab="' + tab + '"]');
        if (targetEl) targetEl.classList.add('is-loading');

        /* Skeleton dans le contenu */
        container.classList.add('cc-loading');

        fetch(tabUrl + tab, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(function (html) {
            container.innerHTML = html;
            current = tab;

            /* Mettre à jour les classes actives */
            tabs.forEach(function (t) {
                var isActive = t.getAttribute('data-tab') === tab;
                t.classList.toggle('active', isActive);
                t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            /* URL */
            if (pushState) {
                history.pushState({ tab: tab }, '', baseUrl + '?tab=' + tab);
                try { sessionStorage.setItem('cc_last_tab', tab); } catch (e) {}
            }

            /* Scroll en haut */
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function () {
            /* Fallback : rechargement complet */
            window.location.href = baseUrl + '?tab=' + tab;
        })
        .finally(function () {
            if (targetEl) targetEl.classList.remove('is-loading');
            container.classList.remove('cc-loading');
        });
    }

    /* ── Popstate (bouton retour navigateur) ────────────────── */
    window.addEventListener('popstate', function (e) {
        var state = e.state;
        if (state && state.tab) {
            navigateTo(state.tab, false);
        }
    });

    /* État initial pour le bouton retour */
    if (history.replaceState) {
        history.replaceState({ tab: current }, '', window.location.href);
    }
})();
</script>
