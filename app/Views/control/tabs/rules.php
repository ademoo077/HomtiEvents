<?php
/** @var array $regles */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-shield-check me-2"></i><?= $isAr ? 'قواعد التوجيه' : 'Règles de routage' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'تكوين قواعد توجيه الشكاوى' : 'Configuration du routage des signalements' ?></p>
            </div>
            <div class="wh-hero-actions">
                <a class="btn btn-light" href="<?= url('control/regles/create') ?>">
                    <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="d-flex mb-4">
    <div class="futur-search" style="max-width: 320px;">
        <i class="mdi mdi-magnify"></i>
        <input type="search" class="form-control" placeholder="<?= e(__('common.search')) ?>..." id="rule-search" data-table="rule-table">
    </div>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-shield-check"></i> <?= $isAr ? 'قواعد التوجيه' : 'Règles de routage' ?></span>
        <select class="form-select form-select-sm" id="rule-status-filter" style="width: auto;" aria-label="<?= e(__('common.status')) ?>">
            <option value=""><?= e(__('common.status')) ?> : <?= e(__('common.all')) ?></option>
            <option value="1"><?= $isAr ? 'نشط' : 'Active' ?></option>
            <option value="0"><?= $isAr ? 'معطل' : 'Inactive' ?></option>
        </select>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0" id="rule-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'المفتاح' : 'Clé' ?></th>
                        <th><?= $isAr ? 'النشاط' : 'Activité' ?></th>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'القيمة' : 'Valeur' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'État' ?></th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regles as $r): ?>
                        <tr data-status="<?= (int) ($r['actif'] ?? 1) ?>">
                            <td>
                                <div class="futur-user-cell">
                                    <div class="futur-avatar" style="background: var(--wh-info-soft); color: var(--wh-info);"><i class="mdi mdi-key-variant"></i></div>
                                    <div>
                                        <div class="futur-user-name"><?= e($r['cle']) ?></div>
                                        <div class="futur-user-meta"><i class="mdi mdi-tag"></i> <?= e($r['activite'] ?? '—') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($r['activite'] ?? '—') ?></td>
                            <td><span class="futur-badge" style="background: var(--wh-info-soft); color: var(--wh-info);"><?= e($r['type'] ?? '—') ?></span></td>
                            <td class="wh-text-muted" style="max-width: 200px;"><?= e((string) ($r['valeur'] ?? '')) ?></td>
                            <td>
                                <span class="futur-badge <?= (int) ($r['actif'] ?? 1) ? 'active' : 'inactive' ?>">
                                    <?= (int) ($r['actif'] ?? 1) ? ($isAr ? 'نشط' : 'Active') : ($isAr ? 'معطل' : 'Inactive') ?>
                                </span>
                            </td>
                            <td>
                                <div class="futur-kebab">
                                    <button type="button" class="futur-kebab-btn" aria-label="<?= e(__('common.actions')) ?>" aria-expanded="false">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <div class="futur-kebab-menu" role="menu">
                                        <a href="<?= url('control/regles/' . (int) $r['id'] . '/edit') ?>" role="menuitem"><i class="mdi mdi-pencil"></i> <?= e(__('common.edit')) ?></a>
                                        <form method="post" action="<?= url('control/regles/' . (int) $r['id'] . '/delete') ?>" class="d-inline" data-confirm="<?= e(__('common.delete_confirm')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-danger" role="menuitem"><i class="mdi mdi-delete"></i> <?= e(__('common.delete')) ?></button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($regles)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="futur-empty">
                                    <i class="mdi mdi-shield-off-outline"></i>
                                    <p class="futur-empty-title"><?= e(__('common.no_data')) ?></p>
                                    <p class="futur-empty-text"><?= $isAr ? 'لا توجد قواعد توجيه' : 'Aucune règle de routage' ?></p>
                                    <a href="<?= url('control/regles/create') ?>" class="btn btn-primary futur-empty-action"><i class="mdi mdi-plus me-1"></i> <?= e(__('common.create')) ?></a>
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
    var searchInput = document.getElementById('rule-search');
    var table = document.getElementById('rule-table');
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
    var statusFilter = document.getElementById('rule-status-filter');
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
