<?php
/** @var array $evenements */
use App\Helpers\I18n;

$title = __('common.qrcode');
$page  = 'qrcode.scan';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
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

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center gap-2">
                    <i class="mdi mdi-qrcode-scan text-primary"></i>
                    <?= $isAr ? 'إدخال رمز QR' : 'Saisie d\'un code QR' ?>
                </h5>
                <p class="text-muted small"><?= $isAr ? 'ألصق القيمة المستخرجة من الرمز ثم تحقق من الحضور.' : 'Collez la valeur extraite du QR code puis validez la présence.' ?></p>

                <form id="qrScanForm" class="row g-2">
                    <div class="col-12">
                        <label class="form-label" for="token"><?= $isAr ? 'رمز QR' : 'Token QR' ?></label>
                        <input type="text" class="form-control" id="token" required
                               placeholder="ex : ab12cd34"
                               autocomplete="off" autofocus>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="mdi mdi-check-decagram-outline me-1"></i><?= $isAr ? 'تحقق من الحضور' : 'Valider la présence' ?>
                        </button>
                    </div>
                </form>
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

                <hr>
                <p class="text-muted small mb-0">
                    <i class="mdi mdi-information-outline me-1"></i>
                    <?= $isAr ? 'يتم استخراج القيمة بمسح رمز QR بكاميرا الهاتف ثم لصقها هنا.' : 'La valeur s\'obtient en scannant le QR code avec l\'appareil photo, puis se colle ici.' ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center gap-2">
                    <i class="mdi mdi-calendar-clock-outline text-primary"></i>
                    <?= $isAr ? 'آخر رموز QR الصادرة' : 'Derniers QR codes émis' ?>
                </h5>

                <?php if (empty($evenements)): ?>
                    <p class="text-muted text-center py-4 mb-0"><?= $isAr ? 'لا توجد رموز QR صادرة.' : 'Aucun QR code émis pour le moment.' ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th><?= $isAr ? 'الحدث' : 'Événement' ?></th>
                                <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                                <th class="text-end"><?= $isAr ? 'إجراء' : 'Action' ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($evenements as $e): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e(mb_substr((string) ($e['adresse'] ?? ''), 0, 40)) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width:220px">
                                            <?= e(url('checkin/' . $e['token_qr'])) ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap"><?= e(date('d/m/Y', strtotime((string) $e['date_evenement']))) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('wilaya/evenements/' . $e['id'] . '/qrcode')) ?>"
                                           title="<?= $isAr ? 'عرض الرمز' : 'Afficher le QR code' ?>">
                                            <i class="mdi mdi-qrcode"></i>
                                        </a>
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

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
