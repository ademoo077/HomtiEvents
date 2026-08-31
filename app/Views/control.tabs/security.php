<?php
/** @var array $securite */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="futur-grid mb-4">
    <div class="futur-kpi">
        <div class="futur-kpi-head"><div class="futur-kpi-icon blue"><i class="mdi mdi-login-variant"></i></div></div>
        <div class="futur-kpi-value"><?= count($securite['sessions'] ?? []) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'الجلسات النشطة' : 'Sessions actives' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-head"><div class="futur-kpi-icon red"><i class="mdi mdi-alert-circle-outline"></i></div></div>
        <div class="futur-kpi-value"><?= count($securite['evenements'] ?? []) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'الأحداث الأمنية' : 'Événements de sécurité' ?></div>
    </div>
</div>

<div class="futur-card mb-4">
    <div class="futur-card-header">
        <span><i class="mdi mdi-shield-check"></i> <?= $isAr ? 'الجلسات النشطة' : 'Sessions actives' ?></span>
        <div class="futur-search" style="max-width: 280px;">
            <i class="mdi mdi-magnify"></i>
            <input type="search" class="form-control" placeholder="<?= e(__('common.search')) ?>..." id="session-search" data-table="session-table">
        </div>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0" id="session-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'المستخدم' : 'Utilisateur' ?></th>
                        <th><?= $isAr ? 'العنوان IP' : 'Adresse IP' ?></th>
                        <th><?= $isAr ? 'المتصفح' : 'Navigateur' ?></th>
                        <th><?= $isAr ? 'آخر نشاط' : 'Dernière activité' ?></th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($securite['sessions'])): ?>
                        <?php foreach ($securite['sessions'] as $s): ?>
                            <tr>
                                <td>
                                    <div class="futur-user-cell">
                                        <div class="futur-avatar"><?= e(mb_strtoupper(mb_substr((string) ($s['user_id'] ?? 'U'), 0, 1))) ?></div>
                                        <div><div class="futur-user-name"><?= e((string) ($s['user_id'] ?? '—')) ?></div></div>
                                    </div>
                                </td>
                                <td><code><?= e($s['ip'] ?? '—') ?></code></td>
                                <td class="wh-text-muted" style="max-width: 200px;"><small><?= e(mb_substr((string) ($s['user_agent'] ?? ''), 0, 40)) ?></small></td>
                                <td class="wh-text-muted"><?= e($s['updated_at'] ?? $s['created_at'] ?? '—') ?></td>
                                <td>
                                    <form method="post" action="<?= url('control/security/revoke') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="session_id" value="<?= e((string) ($s['id'] ?? '')) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= $isAr ? 'إلغاء' : 'Révoquer' ?>">
                                            <i class="mdi mdi-close-circle"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="futur-empty">
                                    <i class="mdi mdi-login-variant"></i>
                                    <p class="futur-empty-title"><?= $isAr ? 'لا توجد جلسات نشطة' : 'Aucune session active' ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-alert-circle-outline"></i> <?= $isAr ? 'الأحداث الأمنية الأخيرة' : 'Derniers événements de sécurité' ?></span>
        <div class="futur-search" style="max-width: 280px;">
            <i class="mdi mdi-magnify"></i>
            <input type="search" class="form-control" placeholder="<?= e(__('common.search')) ?>..." id="security-search" data-table="security-table">
        </div>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0" id="security-table">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'الرسالة' : 'Message' ?></th>
                        <th><?= $isAr ? 'المستخدم' : 'Utilisateur' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($securite['evenements'])): ?>
                        <?php foreach (array_slice($securite['evenements'], 0, 20) as $ev): ?>
                            <tr>
                                <td><small class="text-muted"><?= e($ev['created_at'] ?? '—') ?></small></td>
                                <td><span class="futur-badge <?= ($ev['niveau'] ?? 'info') === 'critique' ? 'futur-badge-admin' : '' ?>"><?= e($ev['type'] ?? '—') ?></span></td>
                                <td class="wh-text-muted" style="max-width: 400px;"><?= e(mb_substr((string) ($ev['message'] ?? ''), 0, 80)) ?></td>
                                <td><?= e((string) ($ev['user_id'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="futur-empty">
                                    <i class="mdi mdi-alert-circle-outline"></i>
                                    <p class="futur-empty-title"><?= $isAr ? 'لا توجد أحداث' : 'Aucun événement' ?></p>
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
    var searchInput = document.getElementById('session-search');
    var table = document.getElementById('session-table');
    if (searchInput && table) {
        searchInput.addEventListener('input', function () {
            var term = this.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) { row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none'; });
        });
    }
    searchInput = document.getElementById('security-search');
    table = document.getElementById('security-table');
    if (searchInput && table) {
        searchInput.addEventListener('input', function () {
            var term = this.value.toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) { row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none'; });
        });
    }
})();
</script>
