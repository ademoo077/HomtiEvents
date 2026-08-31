<?php
/** @var array $communes */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="futur-search" style="max-width: 320px;">
        <i class="mdi mdi-magnify"></i>
        <input type="search" class="form-control" placeholder="<?= e(__('common.search')) ?>..." id="commune-search" data-table="commune-table">
    </div>
    <a class="btn btn-primary" href="<?= url('control/communes/create') ?>">
        <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
    </a>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-city"></i> <?= $isAr ? 'البلديات' : 'Communes' ?></span>
        <select class="form-select form-select-sm" id="commune-status-filter" style="width: auto;" aria-label="<?= e(__('common.status')) ?>">
            <option value=""><?= e(__('common.status')) ?> : <?= e(__('common.all')) ?></option>
            <option value="1"><?= $isAr ? 'نشط' : 'Actif' ?></option>
            <option value="0"><?= $isAr ? 'معطل' : 'Inactif' ?></option>
        </select>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0" id="commune-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                        <th><?= $isAr ? 'الولاية' : 'Wilaya' ?></th>
                        <th><?= $isAr ? 'الدائرة' : 'Daira' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($communes as $c): ?>
                        <tr data-status="<?= (int) ($c['actif'] ?? 1) ?>">
                            <td>
                                <div class="futur-user-cell">
                                    <div class="futur-avatar" style="background: var(--wh-cyan-soft); color: var(--wh-cyan);"><i class="mdi mdi-city"></i></div>
                                    <div>
                                        <div class="futur-user-name"><?= e($c['nom']) ?></div>
                                        <div class="futur-user-meta"><i class="mdi mdi-map-marker"></i> <?= e(($c['wilaya'] ?? '') . ($c['daira'] ? ' / ' . $c['daira'] : '')) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($c['wilaya'] ?? '—') ?></td>
                            <td><?= e($c['daira'] ?? '—') ?></td>
                            <td>
                                <span class="futur-badge <?= (int) ($c['actif'] ?? 1) ? 'active' : 'inactive' ?>">
                                    <?= (int) ($c['actif'] ?? 1) ? ($isAr ? 'نشط' : 'Actif') : ($isAr ? 'معطل' : 'Inactif') ?>
                                </span>
                            </td>
                            <td>
                                <div class="futur-kebab">
                                    <button type="button" class="futur-kebab-btn" aria-label="<?= e(__('common.actions')) ?>" aria-expanded="false">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <div class="futur-kebab-menu" role="menu">
                                        <a href="<?= url('control/communes/' . (int) $c['id'] . '/edit') ?>" role="menuitem"><i class="mdi mdi-pencil"></i> <?= e(__('common.edit')) ?></a>
                                        <form method="post" action="<?= url('control/communes/' . (int) $c['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-danger" role="menuitem"><i class="mdi mdi-delete"></i> <?= e(__('common.delete')) ?></button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($communes)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="futur-empty">
                                    <i class="mdi mdi-city-outline"></i>
                                    <p class="futur-empty-title"><?= e(__('common.no_data')) ?></p>
                                    <p class="futur-empty-text"><?= $isAr ? 'لا توجد بلديات' : 'Aucune commune' ?></p>
                                    <a href="<?= url('control/communes/create') ?>" class="btn btn-primary futur-empty-action"><i class="mdi mdi-plus me-1"></i> <?= e(__('common.create')) ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    var searchInput = document.getElementById('commune-search');
    var table = document.getElementById('commune-table');
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
    var statusFilter = document.getElementById('commune-status-filter');
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
