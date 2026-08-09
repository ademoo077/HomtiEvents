<?php
/** @var array $citoyen @var array $participations @var array $badges @var array $errors */
$title = __('common.citoyens');
$page  = 'admin.citoyens.show';

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'                => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};

$initiales = mb_substr((string) $citoyen['prenom'], 0, 1) . mb_substr((string) $citoyen['nom'], 0, 1);
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= url('admin/citoyens') ?>" class="wh-icon-btn"><i class="mdi mdi-arrow-left"></i></a>
            <span class="wh-user-avatar" style="width:48px;height:48px;font-size:1.1rem"><?= e($initiales) ?></span>
            <div>
                <h1 class="wh-page-title"><?= e($citoyen['prenom'] . ' ' . $citoyen['nom']) ?></h1>
                <p class="wh-page-sub"><?= e($citoyen['email']) ?></p>
            </div>
        </div>
        <form method="post" action="<?= url('admin/citoyens/' . (int) $citoyen['id'] . '/toggle') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-<?= (int) $citoyen['is_active'] === 1 ? 'danger' : 'success' ?>">
                <i class="mdi mdi-<?= (int) $citoyen['is_active'] === 1 ? 'account-off' : 'account-check' ?> me-1"></i>
                <?= (int) $citoyen['is_active'] === 1 ? e(__('common.reject')) : e(__('common.validate')) ?>
            </button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-account-group"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $citoyen['participations'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.participants')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon violet"><i class="mdi mdi-trophy-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $citoyen['points'] ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.points')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon green"><i class="mdi mdi-star-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= count($badges) ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.badges')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon <?= (int) $citoyen['is_active'] === 1 ? 'green' : 'red' ?>"><i class="mdi mdi-shield-account-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $citoyen['is_active'] === 1 ? e(__('common.validate')) : e(__('common.reject')) ?></div>
                    <div class="wh-kpi-label"><?= e(__('common.status')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header"><span><i class="mdi mdi-calendar-star me-2"></i><?= e(__('common.participants')) ?></span></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th><?= e(__('common.evenements')) ?></th>
                                <th><?= e(__('common.commune')) ?></th>
                                <th><?= e(__('common.status')) ?></th>
                                <th><?= e(__('common.date')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($participations as $p): ?>
                                <tr>
                                    <td><a href="<?= url('wilaya/evenements/' . (int) $p['id']) ?>" class="text-decoration-none fw-semibold"><?= e($p['adresse']) ?></a></td>
                                    <td class="wh-text-muted"><?= e($p['commune_nom'] ?? '-') ?></td>
                                    <td><span class="wh-badge <?= $badgeColor((string) $p['statut']) ?>"><?= e(statut_label((string) $p['statut'])) ?></span></td>
                                    <td class="wh-text-muted"><?= $p['heure_scan'] ? e(date('d/m/Y H:i', strtotime((string) $p['heure_scan']))) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($participations === []): ?>
                                <tr><td colspan="4"><div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div></td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header"><span><i class="mdi mdi-star-outline me-2"></i><?= e(__('common.badges')) ?></span></div>
                <div class="card-body">
                    <?php if ($badges): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($badges as $b): ?>
                                <span class="wh-badge badge-violet">
                                    <?php if ($b['icone']): ?><i class="mdi <?= e($b['icone']) ?>"></i> <?php endif; ?><?= e($b['nom']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
