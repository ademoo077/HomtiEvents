<?php
/**
 * Présence en temps réel — liste des inscrits pendant l'événement.
 *
 * @var array $event
 */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
$title = $isAr ? 'الحضور المباشر' : 'Présences en direct';
$page  = 'association.presence';

$eventId   = (int) $event['id'];
$pollUrl   = url('api/association/evenements/' . $eventId . '/presence');
$capacite  = ! empty($event['capacite']) ? (int) $event['capacite'] : null;
?>
<div class="wh-page">
    <div style="background:linear-gradient(135deg,#198754 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-account-multiple-check"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= $isAr ? 'الحضور المباشر' : 'Présences en direct' ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= e((string) ($event['adresse'] ?? '')) ?> <?php if (! empty($event['date_evenement'])): ?> · <?= e(date('d/m/Y', strtotime((string) $event['date_evenement']))) ?><?php endif; ?></p>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="live-dot"><span></span> <?= $isAr ? 'مباشر' : 'En direct' ?></span>
                <a class="btn btn-light btn-sm" href="<?= url('association/' . $eventId) ?>">
                    <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon green"><i class="mdi mdi-account-check"></i></div>
                <div>
                    <div class="wh-kpi-value" id="prsCount">0</div>
                    <div class="wh-kpi-label"><?= $isAr ? 'الحاضرون الآن' : 'Présents enregistrés' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-ticket-confirmation-outline"></i></div>
                <div>
                    <div class="wh-kpi-value" id="prsCapacite"><?= $capacite !== null ? e((string) $capacite) : '∞' ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'السعة' : 'Capacité' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-account-group"></i></div>
                <div>
                    <div class="wh-kpi-value" id="prsRestantes">—</div>
                    <div class="wh-kpi-label"><?= $isAr ? 'الأماكن المتبقية' : 'Places restantes' ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between" style="padding:.65rem 1.25rem;background:var(--wh-green-soft);border-bottom:1px solid #b7e4c7;">
            <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(25,135,84,.15);display:grid;place-items:center;color:var(--wh-green);font-size:.85rem;"><i class="mdi mdi-account-multiple"></i></span> <?= $isAr ? 'قائمة المشاركين' : 'Liste des participants' ?></span>
            <span class="wh-badge badge-green" id="prsBadge">0</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                            <th><?= $isAr ? 'البريد' : 'Email' ?></th>
                            <th><?= $isAr ? 'الهاتف' : 'Téléphone' ?></th>
                            <th class="text-end"><?= $isAr ? 'وقت المسح' : 'Heure de scan' ?></th>
                        </tr>
                    </thead>
                    <tbody id="prsBody">
                        <tr><td colspan="4" class="text-center text-muted py-4"><?= $isAr ? 'جارٍ التحميل…' : 'Chargement…' ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header d-flex align-items-center justify-content-between" style="padding:.65rem 1.25rem;background:var(--wh-blue-soft,#e7f1fb);border-bottom:1px solid #bfd7ee;">
            <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(13,110,253,.12);display:grid;place-items:center;color:#0B5ED7;font-size:.85rem;"><i class="mdi mdi-account-heart-outline"></i></span> <?= $isAr ? 'مشاركات بدون حساب (ضيوف)' : 'Participations sans compte (invités)' ?></span>
            <span class="wh-badge" style="background:#0B5ED7;color:#fff;" id="invCount">0</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                            <th><?= $isAr ? 'الهاتف' : 'Téléphone' ?></th>
                            <th class="text-end"><?= $isAr ? 'الوقت' : 'Heure' ?></th>
                        </tr>
                    </thead>
                    <tbody id="invBody">
                        <tr><td colspan="3" class="text-center text-muted py-4"><?= $isAr ? 'لا يوجد ضيوف بعد' : 'Aucun invité pour le moment' ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.live-dot { display: inline-flex; align-items: center; gap: .5rem; font-weight: 600; font-size: .85rem; color: var(--wh-green, #198754); }
.live-dot span { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; animation: whPulse 1.4s infinite; }
@keyframes whPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .4; transform: scale(.75); } }
</style>

<script>
(function () {
    'use strict';
    var pollUrl = <?= json_encode($pollUrl, JSON_UNESCAPED_SLASHES) ?>;
    var isAr = <?= $isAr ? 'true' : 'false' ?>;
    var body = document.getElementById('prsBody');
    var countEl = document.getElementById('prsCount');
    var badgeEl = document.getElementById('prsBadge');
    var restEl = document.getElementById('prsRestantes');
    var invBody = document.getElementById('invBody');
    var invCount = document.getElementById('invCount');

    function fmtDate(v) {
        if (!v) return '—';
        var d = new Date(v.replace(' ', 'T'));
        return d.toLocaleDateString(isAr ? 'ar-DZ' : 'fr-FR') + ' ' + d.toLocaleTimeString(isAr ? 'ar-DZ' : 'fr-FR', { hour: '2-digit', minute: '2-digit' });
    }

    function render(data) {
        if (!data || !data.success) return;
        countEl.textContent = data.count;
        badgeEl.textContent = data.count;
        var capacite = data.capacite;
        if (capacite) {
            restEl.textContent = Math.max(0, capacite - data.count);
        } else {
            restEl.textContent = '∞';
        }

        if (!data.participants || data.participants.length === 0) {
            body.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">' + (isAr ? 'لا يوجد حضور بعد — انتظر أول عملية مسح.' : 'Aucune présence pour le moment — en attente du premier scan.') + '</td></tr>';
            return;
        }

        var rows = data.participants.map(function (p) {
            return '<tr>'
                + '<td>' + esc(p.prenom || '') + ' ' + esc(p.nom || '') + '</td>'
                + '<td>' + esc(p.email || '—') + '</td>'
                + '<td>' + esc(p.telephone || '—') + '</td>'
                + '<td class="text-end">' + fmtDate(p.heure_scan) + '</td>'
                + '</tr>';
        }).join('');
        body.innerHTML = rows;

        var invitees = data.invitees || [];
        invCount.textContent = invitees.length;
        if (invitees.length === 0) {
            invBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">' + (isAr ? 'لا يوجد ضيوف بعد' : 'Aucun invité pour le moment') + '</td></tr>';
        } else {
            invBody.innerHTML = invitees.map(function (g) {
                return '<tr>'
                    + '<td>' + esc(g.prenom || '') + ' ' + esc(g.nom || '') + '</td>'
                    + '<td>' + esc(g.telephone || '—') + '</td>'
                    + '<td class="text-end">' + fmtDate(g.created_at) + '</td>'
                    + '</tr>';
            }).join('');
        }
    }

    function esc(v) {
        return String(v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function load() {
        fetch(pollUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {});
    }
    load();
    setInterval(load, 10000);
})();
</script>
