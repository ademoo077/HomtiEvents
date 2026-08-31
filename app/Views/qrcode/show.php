<?php
/** @var array $qr */
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;

$title = __('common.qrcode');
$page  = 'qrcode.show';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$checkinUrl = network_url('checkin/' . $qr['token_qr']);
$expired    = ! empty($qr['date_expiration']) && strtotime((string) $qr['date_expiration']) < time();
?>
<div class="wh-hero" style="background: linear-gradient(135deg, #0B5ED7 0%, #6C63FF 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-qrcode me-2"></i><?= e(__('common.qrcode')) ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'رمز حضور الحدث رقم ' . (int) $qr['evenement_id'] : 'QR code de présence — événement n°' . (int) $qr['evenement_id'] ?></p>
            </div>
            <div class="wh-hero-actions">
                <a class="btn btn-light" href="<?= e(url('qrcode/scan')) ?>">
                    <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($expired): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="mdi mdi-alert-outline"></i>
        <div><?= $isAr ? 'انتهت صلاحية هذا الرمز.' : 'Ce QR code a expiré.' ?></div>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mx-auto" style="max-width:420px">
    <div class="card-body text-center py-4">
        <div class="mx-auto mb-3">
            <img src="<?= QrCodeGenerator::pngDataUri($checkinUrl, 240) ?>" alt="QR code" class="img-fluid" style="max-width:240px">
        </div>
        <p class="text-muted small mb-2"><?= $isAr ? 'امسح الرمز لإثبات حضورك.' : 'Scannez ce code pour enregistrer votre présence.' ?></p>
        <a href="<?= e($checkinUrl) ?>" target="_blank" rel="noopener" class="text-break text-decoration-none small"><?= e($checkinUrl) ?></a>

        <hr class="my-3">
        <div class="d-flex justify-content-center gap-4 text-center small">
            <div>
                <div class="text-muted"><?= $isAr ? 'البداية' : 'Début' ?></div>
                <div class="fw-semibold"><?= ! empty($qr['date_debut']) ? e(date('d/m/Y H:i', strtotime((string) $qr['date_debut']))) : '—' ?></div>
            </div>
            <div>
                <div class="text-muted"><?= $isAr ? 'الانتهاء' : 'Fin' ?></div>
                <div class="fw-semibold"><?= ! empty($qr['date_expiration']) ? e(date('d/m/Y H:i', strtotime((string) $qr['date_expiration']))) : '—' ?></div>
            </div>
        </div>
    </div>
</div>

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
