<?php
/** @var array $recoveryCodes, bool $regenerated */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>

<div class="wh-hero" style="background:linear-gradient(135deg,#059669 0%,#0D9488 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-key-variant me-2"></i><?= $isAr ? 'أكواد الاسترداد' : 'Codes de récupération 2FA' ?></h1>
                <p class="wh-hero-sub"><?= !empty($regenerated) ? ($isAr ? 'تم إعادة التوليد' : 'Régénérés avec succès') : ($isAr ? 'تم التفعيل بنجاح' : 'Activation réussie') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="mdi mdi-alert-circle-outline" style="font-size:1.5rem;"></i>
        <div>
            <strong><?= $isAr ? 'مهم جداً' : 'Important' ?></strong><br>
            <?= $isAr ? 'احفظ هذه الأكواد في مكان آمن. لن يتم عرضها مرة أخرى.' : 'Enregistrez ces codes dans un lieu sûr. Ils ne seront plus jamais affichés.' ?>
            <?= $isAr ? 'كل كود يستخدم مرة واحدة فقط.' : 'Chaque code n\'est utilisable qu\'une seule fois.' ?>
        </div>
    </div>

    <div class="futur-card">
        <div class="futur-card-header">
            <span><i class="mdi mdi-key-variant"></i> <?= $isAr ? 'أكواد الاسترداد الخاصة بك' : 'Vos codes de récupération' ?></span>
            <span class="badge bg-secondary"><?= count($recoveryCodes) ?> codes</span>
        </div>
        <div class="futur-card-body">
            <div class="row g-2" id="recovery-codes">
                <?php foreach ($recoveryCodes as $i => $code): ?>
                    <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#f8f9fa;border:1px solid #e5e7eb;font-family:monospace;">
                            <span class="text-muted small">#<?= $i + 1 ?></span>
                            <strong class="user-select-all" style="letter-spacing:2px;"><?= e($code) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-primary" onclick="copyAll()">
                    <i class="mdi mdi-content-copy me-1"></i><?= $isAr ? 'نسخ الكل' : 'Copier tous les codes' ?>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="downloadCodes()">
                    <i class="mdi mdi-download me-1"></i><?= $isAr ? 'تحميل' : 'Télécharger' ?>
                </button>
                <a href="<?= url('profile/2fa') ?>" class="btn btn-primary">
                    <i class="mdi mdi-check me-1"></i><?= $isAr ? 'تم الحفظ' : 'J\'ai enregistré mes codes' ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copyAll() {
    var codes = [];
    document.querySelectorAll('#recovery-codes strong').forEach(function(el) {
        codes.push(el.textContent.trim());
    });
    navigator.clipboard.writeText(codes.join('\n')).then(function() {
        alert('<?= $isAr ? 'تم النسخ' : 'Codes copiés' ?>');
    });
}

function downloadCodes() {
    var lines = ['=== Codes de récupération 2FA — Wilaya Harmonia ===', 'Date : ' + new Date().toLocaleDateString(), '', 'Chaque code n\'est utilisable qu\'une fois.', 'Gardez-les dans un lieu sûr !', ''];
    document.querySelectorAll('#recovery-codes strong').forEach(function(el) {
        lines.push(el.textContent.trim());
    });
    var blob = new Blob([lines.join('\n')], {type: 'text/plain'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'recovery-codes-2fa.txt';
    a.click();
}
</script>

<style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}
</style>
