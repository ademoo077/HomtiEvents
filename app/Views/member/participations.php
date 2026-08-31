<?php
/** @var array $participations */
use App\Helpers\I18n;

$title = __('citoyen.nav_my_participations');
$page  = 'member.participations';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="wh-page">
    <div class="wh-hero" style="background:linear-gradient(135deg,#4B5563 0%,#0B5ED7 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-clipboard-check-outline me-2"></i><?= e(__('citoyen.nav_my_participations')) ?></h1>
                    <p class="wh-hero-sub"><?= (int) count($participations) ?> <?= $isAr ? 'مشاركة مسجلة' : 'participation(s) enregistrée(s)' ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a href="<?= url('dashboard/scan') ?>" class="btn btn-sm btn-light">
                        <i class="mdi mdi-qrcode-scan me-1"></i><?= $isAr ? 'مسح QR' : 'Scanner QR' ?>
                    </a>
                    <a href="<?= url('dashboard') ?>" class="btn btn-sm btn-outline-light">
                        <i class="mdi mdi-view-dashboard me-1"></i><?= $isAr ? 'لوحة التحكم' : 'Dashboard' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (! empty($participations)): ?>
        <!-- Search bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-2">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0"><i class="mdi mdi-magnify text-muted"></i></span>
                    <input type="search" id="participationSearch" class="form-control border-0"
                           placeholder="<?= $isAr ? 'ابحث في المشاركات...' : 'Rechercher dans mes participations...' ?>"
                           aria-label="<?= $isAr ? 'بحث' : 'Rechercher' ?>">
                </div>
            </div>
        </div>

        <!-- Participations list -->
        <div class="row g-3" id="participationList">
            <?php foreach ($participations as $p): ?>
                <div class="col-md-6 col-lg-4 participation-item">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-success">
                                    <i class="mdi mdi-check-circle me-1"></i><?= $isAr ? 'مسجّل' : 'Scanné' ?>
                                </span>
                                <span class="small text-muted">
                                    <?= e(date('d/m/Y', strtotime((string) $p['date_evenement']))) ?>
                                </span>
                            </div>
                            <h6 class="card-title mb-1"><?= e($p['adresse'] ?? '') ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="mdi mdi-map-marker-outline me-1"></i><?= e($p['commune_nom'] ?? '') ?>
                            </p>
                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted">
                                    <i class="mdi mdi-clock-outline me-1"></i>
                                    <?= e(date('d/m/Y H:i', strtotime((string) ($p['heure_scan'] ?? '')))) ?>
                                </small>
                                <span class="badge <?= e(statut_badge_class((string) ($p['event_statut'] ?? 'programme'))) ?>">
                                    <?= e(statut_label((string) ($p['event_statut'] ?? 'programme'))) ?>
                                </span>
                            </div>
                            <?php if (! empty($p['album_id'])): ?>
                                <div class="mt-2">
                                    <a href="<?= url('citoyen/evenement/' . (int) $p['evenement_id']) ?>" class="badge bg-light text-dark text-decoration-none">
                                        <i class="mdi mdi-image-multiple me-1"></i><?= e($p['album_titre']) ?> (<?= (int) $p['nb_photos'] ?>)
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="wh-empty py-5 text-center text-muted">
            <i class="mdi mdi-clipboard-check-outline" style="font-size:2.5rem"></i>
            <p class="mb-0 mt-2"><?= $isAr ? 'لم تشارك في أي فعالية بعد.' : 'Aucune participation enregistrée pour le moment.' ?></p>
            <a href="<?= url('dashboard/scan') ?>" class="btn btn-primary btn-sm mt-3">
                <i class="mdi mdi-qrcode-scan me-1"></i><?= $isAr ? 'سجّل مشاركتك الآن' : 'Scanner un QR maintenant' ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var searchInput = document.getElementById('participationSearch');
    if (!searchInput) return;
    searchInput.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        var items = document.querySelectorAll('#participationList .participation-item');
        items.forEach(function(item) {
            item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
})();
</script>
<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
