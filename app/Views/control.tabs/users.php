<?php
/** @var array $users */
use App\Helpers\I18n;
use App\Helpers\Rbac;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="futur-search" style="max-width: 320px;">
        <i class="mdi mdi-magnify"></i>
        <input type="search" class="form-control" placeholder="<?= e(__('common.search')) ?>..." id="user-search" data-table="user-table">
    </div>
    <a class="btn btn-primary" href="<?= url('control/utilisateurs/create') ?>">
        <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
    </a>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-account-multiple"></i> <?= e(__('common.users')) ?></span>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="user-role-filter" style="width: auto;" aria-label="<?= e(__('common.role')) ?>">
                <option value=""><?= e(__('common.role')) ?> : <?= e(__('common.all')) ?></option>
                <option value="citoyen"><?= $isAr ? 'مواطن' : 'Citoyen' ?></option>
                <option value="association"><?= $isAr ? 'رئيس جمعية' : 'Président association' ?></option>
                <option value="epic"><?= $isAr ? 'مؤسسة عامة' : 'EPIC' ?></option>
                <option value="admin"><?= $isAr ? 'مدير' : 'Admin' ?></option>
            </select>
            <select class="form-select form-select-sm" id="user-status-filter" style="width: auto;" aria-label="<?= e(__('common.status')) ?>">
                <option value=""><?= e(__('common.status')) ?> : <?= e(__('common.all')) ?></option>
                <option value="1"><?= $isAr ? 'نشط' : 'Actif' ?></option>
                <option value="0"><?= $isAr ? 'غير نشط' : 'Inactif' ?></option>
            </select>
        </div>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0" id="user-table">
                <thead>
                    <tr>
                        <th><?= e(__('common.name')) ?></th>
                        <th><?= e(__('common.email')) ?></th>
                        <th><?= e(__('common.role')) ?></th>
                        <th><?= $isAr ? 'الجمعية' : 'Association' ?></th>
                        <th><?= $isAr ? 'EPIC' : 'EPIC' ?></th>
                        <th><?= e(__('common.status')) ?></th>
                        <th><?= e(__('common.created')) ?></th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr data-role="<?= e($u['role_user']) ?>" data-status="<?= (int) ($u['is_active'] ?? 1) ?>">
                            <td>
                                <div class="futur-user-cell">
                                    <div class="futur-avatar"><?= e(mb_strtoupper(mb_substr((string) ($u['prenom'] ?? ''), 0, 1) . mb_substr((string) ($u['nom'] ?? ''), 0, 1))) ?></div>
                                    <div>
                                        <div class="futur-user-name"><?= e($u['prenom'] . ' ' . $u['nom']) ?></div>
                                        <div class="futur-user-meta"><i class="mdi mdi-email"></i> <?= e($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="wh-text-muted" style="max-width: 200px;"><?= e($u['email']) ?></td>
                            <td><span class="futur-badge futur-badge-<?= e(Rbac::roleKey($u['role_user']) ?? 'default') ?>"><?= e(Rbac::label($u['role_user'])) ?></span></td>
                            <td><?= e($u['association_nom'] ?? '—') ?></td>
                            <td><?= e($u['epic_nom'] ?? '—') ?></td>
                            <td>
                                <span class="futur-badge <?= (int) $u['is_active'] === 1 ? 'active' : 'inactive' ?>">
                                    <?= (int) $u['is_active'] === 1 ? ($isAr ? 'نشط' : 'Actif') : ($isAr ? 'غير نشط' : 'Inactif') ?>
                                </span>
                            </td>
                            <td class="wh-text-muted"><?= date('d/m/Y', strtotime((string) $u['created_at'])) ?></td>
                            <td>
                                <div class="futur-kebab">
                                    <button type="button" class="futur-kebab-btn" aria-label="<?= e(__('common.actions')) ?>" aria-expanded="false">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <div class="futur-kebab-menu" role="menu">
                                        <a href="<?= url('control/utilisateurs/' . (int) $u['id'] . '/edit') ?>" role="menuitem"><i class="mdi mdi-pencil"></i> <?= e(__('common.edit')) ?></a>
                                        <form method="post" action="<?= url('control/utilisateurs/' . (int) $u['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-danger" role="menuitem"><i class="mdi mdi-delete"></i> <?= e(__('common.delete')) ?></button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="futur-empty">
                                    <i class="mdi mdi-account-off"></i>
                                    <p class="futur-empty-title"><?= e(__('common.no_data')) ?></p>
                                    <p class="futur-empty-text"><?= $isAr ? 'لا يوجد مستخدمين لعرضهم' : 'Aucun utilisateur à afficher' ?></p>
                                    <a href="<?= url('control/utilisateurs/create') ?>" class="btn btn-primary futur-empty-action"><i class="mdi mdi-plus me-1"></i> <?= e(__('common.create')) ?></a>
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
    // Search filter
    var searchInput = document.getElementById('user-search');
    var table = document.getElementById('user-table');
    if (searchInput && table) {
        searchInput.addEventListener('input', function () {
            var term = this.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr[data-role]');
            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }
    // Role filter
    var roleFilter = document.getElementById('user-role-filter');
    if (roleFilter && table) {
        roleFilter.addEventListener('change', function () {
            var val = this.value;
            var rows = table.querySelectorAll('tbody tr[data-role]');
            rows.forEach(function (row) {
                row.style.display = (!val || row.dataset.role === val) ? '' : 'none';
            });
        });
    }
    // Status filter
    var statusFilter = document.getElementById('user-status-filter');
    if (statusFilter && table) {
        statusFilter.addEventListener('change', function () {
            var val = this.value;
            var rows = table.querySelectorAll('tbody tr[data-status]');
            rows.forEach(function (row) {
                row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
            });
        });
    }
    // Kebab menus
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