<?php
/** @var array $securite */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="futur-grid mb-4">
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= count($securite['sessions'] ?? []) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'الجلسات النشطة' : 'Sessions actives' ?></div>
    </div>
    <div class="futur-kpi">
        <div class="futur-kpi-value"><?= count($securite['evenements'] ?? []) ?></div>
        <div class="futur-kpi-label"><?= $isAr ? 'الأحداث الأمنية' : 'Événements de sécurité' ?></div>
    </div>
</div>

<div class="futur-card mb-4">
    <div class="futur-card-header">
        <span><i class="mdi mdi-shield-check"></i> <?= $isAr ? 'الجلسات النشطة' : 'Sessions actives' ?></span>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive">
            <table class="futur-table mb-0">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'المستخدم' : 'Utilisateur' ?></th>
                        <th><?= $isAr ? 'العنوان IP' : 'Adresse IP' ?></th>
                        <th><?= $isAr ? 'المتصفح' : 'Navigateur' ?></th>
                        <th><?= $isAr ? 'آخر نشاط' : 'Dernière activité' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($securite['sessions'])): ?>
                        <?php foreach ($securite['sessions'] as $s): ?>
                            <tr>
                                <td><?= e((string) ($s['user_id'] ?? '—')) ?></td>
                                <td><code><?= e($s['ip'] ?? '—') ?></code></td>
                                <td><small><?= e(mb_substr((string) ($s['user_agent'] ?? ''), 0, 40)) ?></small></td>
                                <td><?= e($s['updated_at'] ?? $s['created_at'] ?? '—') ?></td>
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
                        <tr><td colspan="5" class="text-center"><?= $isAr ? 'لا توجد جلسات نشطة' : 'Aucune session active' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-alert-circle-outline"></i> <?= $isAr ? 'الأحداث الأمنية الأخيرة' : 'Derniers événements de sécurité' ?></span>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive">
            <table class="futur-table mb-0">
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
                                <td><span class="badge bg-<?= ($ev['niveau'] ?? 'info') === 'critique' ? 'danger' : 'secondary' ?>"><?= e($ev['type'] ?? '—') ?></span></td>
                                <td><?= e(mb_substr((string) ($ev['message'] ?? ''), 0, 60)) ?></td>
                                <td><?= e((string) ($ev['user_id'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center"><?= $isAr ? 'لا توجد أحداث' : 'Aucun événement' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
