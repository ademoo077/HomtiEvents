<?php
/** @var array $epics */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-satellite-variant me-2"></i><?= $isAr ? 'منظمات EPIC' : 'Organisations EPIC' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'إدارة المؤسسات العمومية ذات الطابع الصناعي' : 'Gestion des établissements publics à caractère industriel' ?></p>
            </div>
            <div class="wh-hero-actions">
                <a class="btn btn-light" href="<?= url('control/epic/create') ?>">
                    <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="d-flex mb-4">
    <div class="futur-search" style="max-width: 320px;">
        <i class="mdi mdi-magnify"></i>
        <input type="search" class="form-control" placeholder="<?= e(__('common.search')) ?>..." id="epic-search" data-table="epic-table">
    </div>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-satellite-variant"></i> <?= $isAr ? 'منظمات EPIC' : 'Organisations EPIC' ?></span>
        <select class="form-select form-select-sm" id="epic-status-filter" style="width: auto;" aria-label="<?= e(__('common.status')) ?>">
            <option value=""><?= e(__('common.status')) ?> : <?= e(__('common.all')) ?></option>
            <option value="1"><?= $isAr ? 'نشط' : 'Actif' ?></option>
            <option value="0"><?= $isAr ? 'معطل' : 'Inactif' ?></option>
        </select>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0" id="epic-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                        <th><?= $isAr ? 'الولاية' : 'Wilaya' ?></th>
                        <th><?= $isAr ? 'الدائرة' : 'Daira' ?></th>
                        <th><?= $isAr ? 'الوصف' : 'Description' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($epics as $e): ?>
                        <tr data-status="<?= (int) ($e['actif'] ?? 1) ?>">
                            <td>
                                <div class="futur-user-cell">
                                    <div class="futur-avatar" style="background: var(--wh-purple-soft); color: var(--wh-purple);"><i class="mdi mdi-satellite-variant"></i></div>
                                    <div>
                                        <div class="futur-user-name"><?= e($e['nom']) ?></div>
                                        <div class="futur-user-meta"><i class="mdi mdi-map-marker"></i> <?= e(($e['wilaya'] ?? '') . ($e['daira'] ? ' / ' . $e['daira'] : '')) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($e['wilaya'] ?? '—') ?></td>
                            <td><?= e($e['daira'] ?? '—') ?></td>
                            <td class="wh-text-muted" style="max-width: 300px;"><?= e(mb_substr((string) ($e['description'] ?? ''), 0, 80)) ?></td>
                            <td>
                                <span class="futur-badge <?= (int) ($e['actif'] ?? 1) ? 'active' : 'inactive' ?>">
                                    <?= (int) ($e['actif'] ?? 1) ? ($isAr ? 'نشط' : 'Actif') : ($isAr ? 'معطل' : 'Inactif') ?>
                                </span>
                            </td>
                            <td>
                                <div class="futur-kebab">
                                    <button type="button" class="futur-kebab-btn" aria-label="<?= e(__('common.actions')) ?>" aria-expanded="false">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <div class="futur-kebab-menu" role="menu">
                                        <a href="<?= url('control/epic/' . (int) $e['id'] . '/edit') ?>" role="menuitem"><i class="mdi mdi-pencil"></i> <?= e(__('common.edit')) ?></a>
                                        <form method="post" action="<?= url('control/epic/' . (int) $e['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-danger" role="menuitem"><i class="mdi mdi-delete"></i> <?= e(__('common.delete')) ?></button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($epics)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="futur-empty">
                                    <i class="mdi mdi-satellite-variant"></i>
                                    <p class="futur-empty-title"><?= e(__('common.no_data')) ?></p>
                                    <p class="futur-empty-text"><?= $isAr ? 'لا توجد منظمات EPIC' : 'Aucune organisation EPIC' ?></p>
                                    <a href="<?= url('control/epic/create') ?>" class="btn btn-primary futur-empty-action"><i class="mdi mdi-plus me-1"></i> <?= e(__('common.create')) ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
</style>

<script>
(function () {
    var searchInput = document.getElementById('epic-search');
    var table = document.getElementById('epic-table');
    if (searchInput && table) {
        searchInput.addEventListener('input', function () {
            var term = this.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr[data-status]');
            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }
    var statusFilter = document.getElementById('epic-status-filter');
    if (statusFilter && table) {
        statusFilter.addEventListener('change', function () {
            var val = this.value;
            var rows = table.querySelectorAll('tbody tr[data-status]');
            rows.forEach(function (row) {
                row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
            });
        });
    }
    document.querySelectorAll('.futur-kebab-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var menu = this.nextElementSibling;
            var isOpen = menu.classList.contains('is-open');
            document.querySelectorAll('.futur-kebab-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            if (!isOpen) menu.classList.add('is-open');
            this.setAttribute('aria-expanded', !isOpen);
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('.futur-kebab-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
    });
})();
</script>
