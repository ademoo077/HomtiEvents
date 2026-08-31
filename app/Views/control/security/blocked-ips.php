<?php
/** @var array $blockedIps */
use App\Helpers\I18n;

$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
$now   = time();
?>

<div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#F59E0B 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-shield-lock me-2"></i><?= $isAr ? 'إدارة IP المحظورة' : 'Gestion des IP bloquées' ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'حظر أو رفع حظر عناوين IP' : 'Bloquer ou débloquer des adresses IP manuellement' ?></p>
            </div>
            <a href="<?= url('control?tab=security') ?>" class="btn btn-outline-light btn-sm">
                <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'العودة' : 'Retour' ?>
            </a>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success mx-4 mt-3" id="flash-msg"><?= e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger mx-4 mt-3"><?= e($_SESSION['flash_error']) ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Formulaire de blocage -->
        <div class="col-lg-4">
            <div class="futur-card mb-4">
                <div class="futur-card-header" style="background:linear-gradient(135deg,#DC2626,#991B1B);">
                    <span><i class="mdi mdi-plus-circle" style="color:#fff;"></i> <span style="color:#fff;"><?= $isAr ? 'حظر IP جديد' : 'Bloquer une IP' ?></span></span>
                </div>
                <div class="futur-card-body">
                    <form method="post" action="<?= url('control/security/block-ip') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="ip_address" class="form-label small fw-semibold">Adresse IP</label>
                            <input type="text" id="ip_address" name="ip_address" class="form-control" placeholder="192.168.1.100" required
                                   pattern="^(\d{1,3}\.){3}\d{1,3}$" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label small fw-semibold"><?= $isAr ? 'السبب' : 'Raison' ?></label>
                            <input type="text" id="reason" name="reason" class="form-control" value="<?= $isAr ? 'حظر يدوي' : 'Blocage manuel' ?>" placeholder="<?= $isAr ? 'سبب الحظر' : 'Raison du blocage' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="duree_min" class="form-label small fw-semibold"><?= $isAr ? 'المدة (دقيقة)' : 'Durée (minutes)' ?></label>
                            <select id="duree_min" name="duree_min" class="form-select">
                                <option value="0"><?= $isAr ? 'دائم' : 'Permanent' ?></option>
                                <option value="30">30 <?= $isAr ? 'دقيقة' : 'min' ?></option>
                                <option value="60">1 <?= $isAr ? 'ساعة' : 'heure' ?></option>
                                <option value="360">6 <?= $isAr ? 'ساعات' : 'heures' ?></option>
                                <option value="1440">24 <?= $isAr ? 'ساعة' : 'heures' ?></option>
                                <option value="10080">7 <?= $isAr ? 'أيام' : 'jours' ?></option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="mdi mdi-shield-off me-1"></i><?= $isAr ? 'حظر' : 'Bloquer' ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stats -->
            <div class="futur-card">
                <div class="futur-card-header">
                    <span><i class="mdi mdi-chart-bar"></i> <?= $isAr ? 'الإحصائيات' : 'Statistiques' ?></span>
                </div>
                <div class="futur-card-body">
                    <?php
                    $total = count($blockedIps);
                    $permanent = 0;
                    $expired = 0;
                    foreach ($blockedIps as $bip) {
                        if (empty($bip['expires_at'])) {
                            $permanent++;
                        } elseif (strtotime($bip['expires_at']) < $now) {
                            $expired++;
                        }
                    }
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><?= $isAr ? 'الإجمالي' : 'Total' ?></span>
                        <span class="badge bg-secondary"><?= $total ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><?= $isAr ? 'دائمة' : 'Permanentes' ?></span>
                        <span class="badge bg-dark"><?= $permanent ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><?= $isAr ? 'منتهية' : 'Expirées' ?></span>
                        <span class="badge bg-warning text-dark"><?= $expired ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des IP bloquées -->
        <div class="col-lg-8">
            <div class="futur-card">
                <div class="futur-card-header">
                    <span><i class="mdi mdi-format-list-bulleted"></i> <?= $isAr ? 'عناوين IP المحظورة' : 'IP bloquées' ?></span>
                    <span class="badge bg-danger"><?= count($blockedIps) ?></span>
                </div>
                <div class="futur-card-body p-0">
                    <?php if (empty($blockedIps)): ?>
                        <div class="text-center py-5">
                            <i class="mdi mdi-shield-check" style="font-size:3rem;color:#198754;"></i>
                            <p class="mt-2 text-muted mb-0"><?= $isAr ? 'لا توجد عناوين IP محظورة' : 'Aucune IP bloquée' ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f8f9fa;">
                                    <tr>
                                        <th>IP</th>
                                        <th><?= $isAr ? 'السبب' : 'Raison' ?></th>
                                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                                        <th><?= $isAr ? 'الأصل' : 'Origine' ?></th>
                                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                                        <th class="text-end"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blockedIps as $bip): ?>
                                        <?php
                                        $expTs = !empty($bip['expires_at']) ? strtotime($bip['expires_at']) : null;
                                        $isExpired = $expTs !== null && $expTs < $now;
                                        $isPermanent = $expTs === null;

                                        if ($isExpired) {
                                            $statusBadge = 'bg-secondary';
                                            $statusLabel = 'Expirée';
                                        } elseif ($isPermanent) {
                                            $statusBadge = 'bg-danger';
                                            $statusLabel = 'Permanent';
                                        } else {
                                            $statusBadge = 'bg-warning text-dark';
                                            $statusLabel = 'Actif';
                                        }
                                        ?>
                                        <tr style="<?= $isExpired ? 'opacity:0.5;' : '' ?>">
                                            <td>
                                                <code class="fw-bold"><?= e($bip['ip_address']) ?></code>
                                            </td>
                                            <td class="small"><?= e($bip['raison'] ?? '') ?></td>
                                            <td><span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span></td>
                                            <td class="small text-muted">
                                                <?php if (!empty($bip['blocked_by'])): ?>
                                                    ID #<?= (int) $bip['blocked_by'] ?>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= e($bip['created_at'] ?? '') ?></td>
                                            <td class="text-end">
                                                <?php if (!$isExpired): ?>
                                                <form method="post" action="<?= url('control/security/unblock-ip') ?>" class="d-inline" onsubmit="return confirm('Débloquer cette IP ?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="ip_address" value="<?= e($bip['ip_address']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="mdi mdi-lock-open-variant me-1"></i><?= $isAr ? 'إلغاء' : 'Débloquer' ?>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
setTimeout(function() { var el = document.getElementById('flash-msg'); if (el) el.style.display = 'none'; }, 5000);
</script>

<style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}
</style>
