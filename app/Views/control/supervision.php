<?php
/** @var array $data */
use App\Helpers\I18n;

$title = __('common.supervision');
$page  = 'control.supervision';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-radar me-2"></i><?= e(__('common.supervision')) ?></h1>
                    <p class="wh-hero-sub"><?= $isAr ? 'مراقبة الوقت الحقيقي — الجلسات، المخربين، الأنشطة' : 'Surveillance temps réel — sessions, suspects, activités' ?></p>
                </div>
                <div class="wh-hero-actions">
                    <button class="btn btn-sm btn-light" onclick="refreshSupervision()">
                        <i class="mdi mdi-refresh"></i> <?= $isAr ? 'تحديث' : 'Actualiser' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="futur-grid">
        <div class="futur-kpi">
            <div class="futur-kpi-value" id="kpi-sessions">0</div>
            <div class="futur-kpi-label"><?= $isAr ? 'جلسات نشطة' : 'Sessions actives' ?></div>
        </div>
        <div class="futur-kpi">
            <div class="futur-kpi-value" id="kpi-suspects">0</div>
            <div class="futur-kpi-label"><?= $isAr ? 'أنشطة مشتبكة' : 'Activités suspectes' ?></div>
        </div>
    </div>

    <div class="futur-card">
        <div class="futur-card-header">
            <span><i class="mdi mdi-shield-alert-outline"></i> <?= $isAr ? 'الأنشطة المشتبكة' : 'Activités suspectes' ?></span>
        </div>
        <div class="futur-card-body">
            <div id="supervision-content">
                <p class="text-muted"><?= $isAr ? 'جارٍ التحميل...' : 'Chargement...' ?></p>
            </div>
        </div>
    </div>
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>

<script>
function refreshSupervision() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', <?= json_encode(url('control/supervision')) ?>);
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= e(csrf_token()) ?>');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                document.getElementById('kpi-sessions').textContent = data.sessions || 0;
                document.getElementById('kpi-suspects').textContent = data.suspects || 0;

                var content = '<div class="table-responsive"><table class="futur-table">';
                content += '<thead><tr><th><?= $isAr ? "نوع" : "Type" ?></th><th><?= $isAr ? "الحالة" : "Statut" ?></th><th><?= $isAr ? "الوصف" : "Message" ?></th></tr></thead><tbody>';
                (data.evenements || []).forEach(function(e) {
                    content += '<tr><td>' + e.type + '</td><td>' + e.status + '</td><td>' + (e.message || '') + '</td></tr>';
                });
                content += '</tbody></table></div>';
                document.getElementById('supervision-content').innerHTML = content;
            } catch(e) {}
        }
    };
    xhr.send();
}

refreshSupervision();
setInterval(refreshSupervision, 30000);
</script>
