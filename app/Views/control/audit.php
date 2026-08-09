<?php
/** @var array $logs @var string $search @var int $page @var int $total */
$title = $title ?? 'Journal d\'audit — Control Center';
$page = 'control.audit';
?>
<div class="futur-control">
    <div class="futur-control-header">
        <div>
            <h2 class="futur-control-title"><i class="mdi mdi-shield-account-outline"></i> Journal d\'audit</h2>
            <p class="futur-control-sub">Logs immuables — normes SaaS gouvernementales</p>
        </div>
        <a href="<?= e(url('control/audit/export'), true) ?>" class="futur-btn futur-btn-sm futur-btn-outline">
            <i class="mdi mdi-download"></i> Exporter
        </a>
    </div>

    <div class="futur-card">
        <div class="futur-card-body">
            <div class="futur-table-container">
                <table class="futur-table">
                    <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Module</th><th>IP</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e($log['created_at']) ?></td>
                            <td><?= e(($log['nom'] ?? '') . ' ' . ($log['prenom'] ?? '')) ?></td>
                            <td><?= e($log['action']) ?></td>
                            <td><?= e($log['modele']) ?></td>
                            <td><?= e($log['ip_address'] ?? '-') ?></td>
                            <td>
                                <span class="futur-chip chip-<?= ($log['statut'] ?? 'succes') === 'succes' ? 'success' : 'danger' ?>">
                                    <?= e($log['statut'] ?? 'succes') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>