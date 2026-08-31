<?php
/** @var array $auditLogs */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-file-document-outline me-2"></i><?= $isAr ? 'سجلات التدقيق' : 'Journal d\'audit' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'تتبع الإجراءات على المنصة' : 'Traçabilité des actions sur la plateforme' ?></p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex mb-4">
    <div class="futur-search" style="max-width: 320px;">
        <i class="mdi mdi-magnify"></i>
        <input type="search" class="form-control" placeholder="<?= e(__('common.search')) ?>..." id="audit-search" data-table="audit-table">
    </div>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-file-document-outline"></i> <?= $isAr ? 'سجلات التدقيق' : 'Journal d\'audit' ?></span>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0" id="audit-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'الكيان' : 'Entité' ?></th>
                        <th><?= $isAr ? 'المعرف' : 'ID' ?></th>
                        <th><?= $isAr ? 'الإجراء' : 'Action' ?></th>
                        <th><?= $isAr ? 'المستخدم' : 'Utilisateur' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><small class="text-muted"><?= e($log['created_at'] ?? '—') ?></small></td>
                            <td><span class="futur-badge" style="background: var(--wh-gray-soft); color: var(--wh-gray);"><?= e($log['type'] ?? '—') ?></span></td>
                            <td><?= e($log['entite'] ?? '—') ?></td>
                            <td><code><?= e((string) ($log['entite_id'] ?? '')) ?></code></td>
                            <td><?= e($log['action'] ?? '—') ?></td>
                            <td><?= e((string) ($log['user_id'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($auditLogs)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="futur-empty">
                                    <i class="mdi mdi-file-document-outline"></i>
                                    <p class="futur-empty-title"><?= e(__('common.no_data')) ?></p>
                                    <p class="futur-empty-text"><?= $isAr ? 'لا توجد سجلات تدقيق' : 'Aucun journal d\'audit' ?></p>
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
    var searchInput = document.getElementById('audit-search');
    var table = document.getElementById('audit-table');
    if (searchInput && table) {
        searchInput.addEventListener('input', function () {
            var term = this.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }
})();
</script>
