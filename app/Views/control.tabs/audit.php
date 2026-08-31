<?php
/** @var array $auditLogs */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
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
