<?php
/** @var array $auditLogs */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-file-document-outline"></i> <?= $isAr ? 'سجلات التدقيق' : 'Journal d\'audit' ?></span>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive">
            <table class="futur-table mb-0">
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
                            <td><span class="badge bg-secondary"><?= e($log['type'] ?? '—') ?></span></td>
                            <td><?= e($log['entite'] ?? '—') ?></td>
                            <td><?= e((string) ($log['entite_id'] ?? '')) ?></td>
                            <td><?= e($log['action'] ?? '—') ?></td>
                            <td><?= e((string) ($log['user_id'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($auditLogs)): ?>
                        <tr><td colspan="6" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
