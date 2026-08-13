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
    <!-- Header avec navigation par onglets -->
    <div class="futur-control-header mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="futur-control-title mb-1"><i class="mdi mdi-view-dashboard"></i> <?= e(__('control.center')) ?></h2>
                <p class="futur-control-sub mb-0"><?= $isAr ? 'مركز المراقبة — إدارة شاملة للمنصة' : 'Control Center — Supervision et gestion centralisée de la plateforme' ?></p>
            </div>
        </div>

        <!-- Onglets principaux -->
        <nav class="futur-tabs" role="tablist" aria-label="<?= e(__('control.tabs')) ?>">
            <?php foreach ($tabs as $key => $tab): ?>
                <a class="futur-tab <?= $activeTab === $key ? 'active' : '' ?>"
                   href="<?= url('control?tab=' . $key) ?>"
                   role="tab"
                   aria-selected="<?= $activeTab === $key ? 'true' : 'false' ?>">
                    <i class="mdi <?= $tab['icon'] ?>"></i>
                    <span><?= e($tab['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php
    // Inclure le contenu de l'onglet actif
    $tabFile = __DIR__ . '/control.tabs/' . $activeTab . '.php';
    if (is_file($tabFile)) {
        include $tabFile;
    } else {
        // Fallback vers l'ancien dashboard
        include __DIR__ . '/control.tabs/dashboard.php';
    }
    ?>
</div>

<style>
.futur-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    border-bottom: 2px solid var(--futur-border, #e2e8f0);
    padding-bottom: 2px;
    margin-top: 8px;
}
.futur-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px 8px 0 0;
    text-decoration: none;
    color: var(--futur-text-muted, #64748b);
    font-weight: 500;
    font-size: 0.85rem;
    transition: all 0.15s ease;
    background: transparent;
    border: 1px solid transparent;
    border-bottom: none;
    white-space: nowrap;
}
.futur-tab:hover {
    color: var(--futur-primary, #6366f1);
    background: rgba(99, 102, 241, 0.05);
}
.futur-tab.active {
    color: var(--futur-primary, #6366f1);
    background: var(--futur-bg, #f8fafc);
    border-color: var(--futur-border, #e2e8f0);
    border-bottom: 2px solid var(--futur-bg, #f8fafc);
    margin-bottom: -2px;
}
.futur-tab i { font-size: 1rem; }
@media (max-width: 768px) {
    .futur-tab { padding: 6px 10px; font-size: 0.78rem; }
    .futur-tab span { display: none; }
}
</style>