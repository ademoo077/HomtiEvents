<?php
/** @var array $logs @var string $search @var int $page @var int $total */
$title = $title ?? 'Journal d\'audit — Control Center';
$page = 'control.audit';
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-shield-account-outline me-2"></i>Journal d'audit</h1>
                    <p class="wh-hero-sub">Logs immuables — normes SaaS gouvernementales</p>
                </div>
                <div class="wh-hero-actions">
                    <a href="<?= e(url('control/audit/export'), true) ?>" class="btn btn-sm btn-outline-light">
                        <i class="mdi mdi-download"></i> Exporter
                    </a>
                </div>
            </div>
        </div>
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
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>