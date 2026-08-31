<?php
/** @var array $evenements */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
?>

<div class="wh-hero" style="background: linear-gradient(135deg, #0B5ED7 0%, #6C63FF 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-qrcode-scan me-2"></i><?= e(__('common.qrcode')) ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'فحص رموز QR لحضور الفعاليات.' : 'Scanner les QR codes de présence des événements.' ?></p>
            </div>
        </div>
    </div>
</div>

<div class="citoyen-section">

    <div class="citoyen-scan-area">
        <div class="citoyen-scan-icon">
            <i class="mdi mdi-qrcode-scan"></i>
        </div>
        <p class="citoyen-scan-text"><?= $isAr ? 'ألصق القيمة المستخرجة من الرمز ثم تحقق من الحضور.' : 'Collez la valeur extraite du QR code puis validez la présence.' ?></p>

        <form id="qrScanForm" class="citoyen-scan-form">
            <div class="citoyen-form-group">
                <label class="citoyen-form-label" for="token"><?= $isAr ? 'رمز QR' : 'Token QR' ?></label>
                <input type="text" class="citoyen-input" id="token" required
                       placeholder="ex : ab12cd34"
                       autocomplete="off" autofocus>
            </div>
            <button type="submit" class="citoyen-btn citoyen-btn-primary citoyen-btn-block">
                <i class="mdi mdi-check-decagram-outline me-1"></i><?= $isAr ? 'تحقق من الحضور' : 'Valider la présence' ?>
            </button>
        </form>

        <p class="citoyen-scan-hint">
            <i class="mdi mdi-information-outline me-1"></i>
            <?= $isAr ? 'يتم استخراج القيمة بمسح رمز QR بكاميرا الهاتف ثم لصقها هنا.' : 'La valeur s\'obtient en scannant le QR code avec l\'appareil photo, puis se colle ici.' ?>
        </p>
    </div>

    <?php if (! empty($evenements)): ?>
    <div class="citoyen-event-list" style="margin-top:16px;">
        <h3 class="citoyen-section-title" style="margin-bottom:8px;"><?= $isAr ? 'آخر رموز QR الصادرة' : 'Derniers QR codes émis' ?></h3>
        <?php foreach ($evenements as $e): ?>
        <div class="citoyen-card">
            <div class="citoyen-card-body">
                <h3 class="citoyen-card-title"><?= e(mb_substr((string) ($e['adresse'] ?? ''), 0, 40)) ?></h3>
                <p class="citoyen-card-meta">
                    <i class="mdi mdi-calendar"></i> <?= e(date('d/m/Y', strtotime((string) $e['date_evenement']))) ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="citoyen-empty" style="margin-top:16px;"><?= $isAr ? 'لا توجد رموز QR صادرة.' : 'Aucun QR code émis pour le moment.' ?></div>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('qrScanForm');
    var input = document.getElementById('token');
    if (!form || !input) { return; }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var value = input.value.trim().replace(/[^\w-]/g, '');
        if (value) { window.location.href = <?= json_encode(url('checkin')) ?> + '/' + encodeURIComponent(value); }
    });
})();
</script>

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>