<?php
/** @var array $securite */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-shield-lock-outline me-2"></i><?= $isAr ? 'الأمان' : 'Sécurité' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'الجلسات النشطة والأحداث الأمنية' : 'Sessions actives, événements de sécurité et IP bloquées' ?></p>
            </div>
        </div>
    </div>
</div>

<div class="futur-grid mb-4">
    <div class="futur-kpi">
        <div class="futur-kpi-head"><div class="futur-kpi-icon blue"><i class="mdi mdi-login-variant"></i></div></div>
        <div class="futur-kpi-value"><?= count($securite['sessions'] ?? []) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'الجلسات النشطة' : 'Sessions actives' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-head"><div class="futur-kpi-icon red"><i class="mdi mdi-alert-circle-outline"></i></div></div>
        <div class="futur-kpi-value"><?= count($securite['evenements'] ?? []) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'الأحداث الأمنية' : 'Événements sécurité' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-head"><div class="futur-kpi-icon amber"><i class="mdi mdi-shield-off-outline"></i></div></div>
        <div class="futur-kpi-value"><?= (int) ($securite['blocked_count'] ?? 0) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'عناوين IP محظورة' : 'IP bloquées actives' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-head"><div class="futur-kpi-icon green"><i class="mdi mdi-shield-check"></i></div></div>
        <div class="futur-kpi-value"><?= count(array_filter($securite['evenements'] ?? [], fn($e) => ($e['type'] ?? '') === 'login_success')) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'عمليات تسجيل ناجحة (24 ساعة)' : 'Connexions réussies (24h)' ?></div>
    </div>
</div>

<!-- Sessions actives -->
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
                                <td><code><?= e($s['ip_address'] ?? $s['ip'] ?? '—') ?></code></td>
                                <td class="wh-text-muted" style="max-width: 200px;"><small><?= e(mb_substr((string) ($s['user_agent'] ?? ''), 0, 40)) ?></small></td>
                                <td class="wh-text-muted"><?= e($s['last_activity'] ?? $s['updated_at'] ?? $s['created_at'] ?? '—') ?></td>
                                <td>
                                    <form method="post" action="<?= url('control/security/revoke') ?>" class="d-inline" onsubmit="return confirm('Révoquer cette session ?')">
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

<!-- IP bloquées -->
<?php if (!empty($securite['blocked_ips'])): ?>
<div class="futur-card mb-4">
    <div class="futur-card-header" style="border-left: 3px solid #DC2626;">
        <span><i class="mdi mdi-shield-off-outline" style="color:#DC2626;"></i> <?= $isAr ? 'عناوين IP محظورة' : 'IP bloquées' ?> <span class="badge bg-danger ms-2"><?= (int) ($securite['blocked_count'] ?? 0) ?></span></span>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive futur-table-responsive">
            <table class="futur-table mb-0">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'العنوان IP' : 'Adresse IP' ?></th>
                        <th><?= $isAr ? 'السبب' : 'Raison' ?></th>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'تنتهي في' : 'Expire' ?></th>
                        <th><?= $isAr ? 'تم بواسطة' : 'Bloqué par' ?></th>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($securite['blocked_ips'] as $ip): ?>
                        <tr>
                            <td><code style="color:#DC2626;"><?= e($ip['ip_address'] ?? '') ?></code></td>
                            <td><?= e($ip['raison'] ?? '—') ?></td>
                            <td><span class="futur-badge <?= ($ip['trigger_type'] ?? '') === 'auto' ? 'futur-badge-admin' : '' ?>"><?= e($ip['trigger_type'] ?? '—') ?></span></td>
                            <td><?= $ip['expires_at'] ? e($ip['expires_at']) : '<span class="text-danger fw-bold">Permanent</span>' ?></td>
                            <td><?= e((string) ($ip['blocked_by'] ?? '—')) ?></td>
                            <td><small class="text-muted"><?= e($ip['created_at'] ?? '—') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Événements de sécurité -->
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
                        <th><?= $isAr ? 'الشدة' : 'Sévérité' ?></th>
                        <th><?= $isAr ? 'الرسالة' : 'Message' ?></th>
                        <th><?= $isAr ? 'المستخدم' : 'Utilisateur' ?></th>
                        <th><?= $isAr ? 'العنوان IP' : 'IP' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($securite['evenements'])): ?>
                        <?php foreach (array_slice($securite['evenements'], 0, 30) as $ev): ?>
                            <?php
                                $sevClass = match(true) {
                                    ($ev['severity'] ?? 1) >= 3 => 'text-danger fw-bold',
                                    ($ev['severity'] ?? 1) === 2 => 'text-warning',
                                    default => 'text-muted',
                                };
                                $sevLabel = match((int) ($ev['severity'] ?? 1)) {
                                    3 => 'Critique',
                                    2 => 'Moyen',
                                    default => 'Info',
                                };
                            ?>
                            <tr>
                                <td><small class="text-muted"><?= e($ev['created_at'] ?? '—') ?></small></td>
                                <td><span class="futur-badge"><?= e($ev['type'] ?? '—') ?></span></td>
                                <td class="<?= $sevClass ?>"><?= $sevLabel ?></td>
                                <td class="wh-text-muted" style="max-width: 350px;"><?= e(mb_substr((string) ($ev['message'] ?? ''), 0, 80)) ?></td>
                                <td><?= e((string) ($ev['user_id'] ?? '—')) ?></td>
                                <td><code style="font-size:.75rem;"><?= e($ev['ip_address'] ?? '—') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="futur-empty">
                                    <i class="mdi mdi-alert-circle-outline"></i>
                                    <p class="futur-empty-title"><?= $isAr ? 'لا توجد أحداث' : 'Aucun événement de sécurité' ?></p>
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
