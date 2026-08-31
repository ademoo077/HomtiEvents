<?php
/** @var array $event @var array $photos @var array|null $album @var bool $hasParticipated */
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;

$isAr = I18n::direction() === 'rtl';
$eventDateStr = (string) ($event['date_evenement'] ?? '');
$eventTime = $event['heure'] ?? '';
try {
    $eventDateObj = new DateTimeImmutable($eventDateStr);
} catch (Throwable) {
    $eventDateObj = null;
}
$todayStr = date('Y-m-d');
$isPast = $eventDateObj === null || $eventDateStr < $todayStr;
$isToday = $eventDateStr === $todayStr;
$dateDebut = $event['date_debut'] ?? null;
$statut = (string) ($event['statut'] ?? 'PROGRAMME');
$openForScan = in_array($statut, ['PROGRAMME', 'QR_GENERE', 'EN_COURS'], true);
$anomalies = $event['anomalies'] ?? '';
$eventId = (int) ($event['id'] ?? 0);
$hasQr = $openForScan && $eventId > 0;
$qrStreamUrl = $hasQr ? url('event/qr/stream/' . $eventId) : '';
$eventUrl = url('evenement/' . $eventId);
$eventTitle = (string) ($event['adresse'] ?? 'Événement');

/* ── Partage enrichi (WhatsApp / email) ── */
$shareDateFmt   = $eventDateObj ? $eventDateObj->format('d/m/Y') : '';
$shareTimeFmt   = $eventTime !== '' ? substr((string) $eventTime, 0, 5) : '';
$shareCommune   = (string) ($event['commune_nom'] ?? '');
$shareAsso      = (string) ($event['association_nom'] ?? '');
$shareDesc      = (string) ($event['description'] ?? '');
$shareLines     = array_filter([
    ($isAr ? '📅 حدث: ' : '📅 Événement : ') . $eventTitle,
    $shareDateFmt !== '' ? ($isAr ? '📆 التاريخ: ' : '📆 Date : ') . $shareDateFmt : '',
    $shareTimeFmt !== '' ? ($isAr ? '⏰ الساعة: ' : '🕐 Heure : ') . $shareTimeFmt : '',
    $shareCommune !== '' ? ($isAr ? '📍 المكان: ' : '📍 Lieu : ') . $shareCommune : '',
    $shareAsso !== ''    ? ($isAr ? '🏛 الجمعية: ' : '🏛 Association : ') . $shareAsso : '',
    $shareDesc !== ''    ? 'ℹ️ ' . mb_strimwidth($shareDesc, 0, 180) : '',
    '',
    $eventUrl,
]);
$shareText      = implode("\n", $shareLines);
$shareSubject   = ($isAr ? 'دعوة إلى حدث: ' : 'Invitation à un événement : ') . $eventTitle;
?>
<section class="citoyen-section">
    <div class="citoyen-event-detail">

        <a class="citoyen-back-link" href="<?= url('citoyen/explorer') ?>"
           onclick="if (history.length > 1) { event.preventDefault(); history.back(); }">
            <i class="mdi mdi-arrow-left"></i> <?= $isAr ? 'رجوع' : 'Retour' ?>
        </a>

        <?php if ($eventId > 0): ?>
        <button type="button" class="citoyen-fav-btn-lg<?= ($isFavori ?? false) ? ' active' : '' ?>"
                id="eventFavBtn" data-fav-id="<?= $eventId ?>"
                data-active="<?= ($isFavori ?? false) ? 'true' : 'false' ?>"
                aria-pressed="<?= ($isFavori ?? false) ? 'true' : 'false' ?>"
                title="<?= e(__('citoyen.add_favorite')) ?>">
            <i class="mdi mdi-heart<?= ($isFavori ?? false) ? '' : '-outline' ?>"></i>
        </button>
        <?php endif; ?>

        <div class="event-detail-header" data-reveal>
            <span class="badge badge-<?= e(statut_badge_class($statut)) ?>"><?= e(statut_label($statut)) ?></span>
            <?php if ($isToday): ?>
                <span class="badge badge-today"><i class="mdi mdi-clock-fast"></i> <?= $isAr ? 'اليوم' : "Aujourd'hui" ?></span>
            <?php endif; ?>
            <h2 class="event-detail-title"><?= e($event['adresse'] ?? 'Événement') ?></h2>
            <?php if (! empty($event['description'])): ?>
                <p class="event-detail-description"><?= e($event['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="event-detail-meta">
            <div class="meta-item">
                <i class="mdi mdi-calendar"></i>
                <?php if ($eventDateObj !== null): ?>
                    <?php if ($dateDebut && $dateDebut !== $eventDateStr): ?>
                        <span><?= e((new DateTimeImmutable((string) $dateDebut))->format('d/m/Y')) ?> → <?= e($eventDateObj->format('d/m/Y')) ?></span>
                    <?php else: ?>
                        <span><?= e($eventDateObj->format('d/m/Y')) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span>—</span>
                <?php endif; ?>
            </div>
            <?php if (! empty($eventTime)): ?>
                <div class="meta-item">
                    <i class="mdi mdi-clock-outline"></i>
                    <span><?= e(substr((string) $eventTime, 0, 5)) ?></span>
                </div>
            <?php endif; ?>
            <?php if (! empty($event['commune_nom'])): ?>
                <div class="meta-item">
                    <i class="mdi mdi-map-marker-outline"></i>
                    <span><?= e($event['commune_nom']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (! empty($event['association_nom'])): ?>
                <div class="meta-item">
                    <i class="mdi mdi-bank-outline"></i>
                    <span><?= e($event['association_nom']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($anomalies !== ''): ?>
            <div class="event-detail-anomalies">
                <?php foreach (array_filter(array_map('trim', explode(',', (string) $anomalies))) as $an): ?>
                    <span class="badge badge-anomalie"><i class="mdi mdi-alert-decagram-outline"></i> <?= e($an) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="participants-bar" data-reveal>
            <div class="participants-count-display">
                <i class="mdi mdi-account-group"></i>
                <strong id="participantCountStrong"><?= (int) ($event['participants_count'] ?? 0) ?></strong> <?= $isAr ? 'مشارك' : 'participants' ?>
                <?php if (! empty($event['capacite'])): ?>
                    <span class="participants-capacity">
                        / <?= (int) $event['capacite'] ?> <?= $isAr ? 'مقعد' : 'places' ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php if (! $hasParticipated): ?>
            <?php if (!$isPast && $openForScan && (!($isPublic ?? false) || is_logged())): ?>
                <a href="<?= url('qrcode/scan-optimise') ?>" class="btn-participate-today">
                    <i class="mdi mdi-qrcode-scan"></i> <?= $isAr ? 'أمسح للمشاركة' : 'Scanner pour participer' ?>
                </a>
            <?php elseif (($isPublic ?? false) && !$isPast && $openForScan): ?>
                <span class="participated-badge">
                    <i class="mdi mdi-login"></i> <?= $isAr ? 'سجّل الدخول للمشاركة' : 'Connectez-vous pour participer' ?>
                </span>
            <?php endif; ?>
        <?php else: ?>
                <span class="participated-badge">
                    <i class="mdi mdi-check-circle"></i> <?= $isAr ? 'لقد شاركت في هذا الحدث' : 'Vous avez participé' ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if (! empty($event['capacite'])): ?>
            <?php $capPct = (int) $event['capacite'] > 0 ? min(100, round(((int) ($event['participants_count'] ?? 0) / (int) $event['capacite']) * 100)) : 0; ?>
            <div class="participants-capacity-bar" data-reveal>
                <div class="progress" style="height:8px;border-radius:8px;background:#E5E7EB">
                    <div class="progress-bar" role="progressbar"
                         aria-label="<?= e($isAr ? 'نسبة إشغال الحدث' : 'Taux de remplissage de l\'événement') ?>"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $capPct ?>"
                         style="width:<?= $capPct ?>%;background:linear-gradient(90deg,#1A4D3E,#D4AF37);border-radius:8px"></div>
                </div>
                <small class="text-muted">
                    <?= $capPct ?>% <?= $isAr ? 'من السعة القصوى' : 'de la capacité maximale' ?>
                </small>
            </div>
        <?php endif; ?>

        <?php if ($hasQr): ?>
        <div class="event-qr-section" data-reveal>
            <div class="event-qr-card">
                <div class="event-qr-visual">
                    <img src="<?= e($qrStreamUrl) ?>" alt="QR Code — <?= e($eventTitle) ?>" width="160" height="160" loading="lazy" class="event-qr-img" id="eventQrImg">
                </div>
                <div class="event-qr-info">
                    <h3><i class="mdi mdi-qrcode-scan"></i> <?= $isAr ? 'رمز الحضور' : 'QR Code de présence' ?></h3>
                    <p class="event-qr-desc"><?= $isAr ? 'امسح الرمز لتسجيل حضورك في هذا الحدث.' : 'Scannez ce code pour enregistrer votre participation.' ?></p>
                    <div class="event-qr-actions">
                        <button type="button" class="btn-ev-qr btn-ev-qr-wa" onclick="shareEventWhatsApp()">
                            <i class="mdi mdi-whatsapp"></i> <?= $isAr ? 'مشاركة عبر واتساب' : 'Partager sur WhatsApp' ?>
                        </button>
                        <button type="button" class="btn-ev-qr btn-ev-qr-email" onclick="shareEventEmail()">
                            <i class="mdi mdi-email-outline"></i> <?= $isAr ? 'بريد' : 'Email' ?>
                        </button>
                        <button type="button" class="btn-ev-qr btn-ev-qr-download" onclick="downloadEventQr()">
                            <i class="mdi mdi-download"></i> <?= $isAr ? 'تحميل' : 'Télécharger' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (! empty($event['latitude']) && ! empty($event['longitude'])): ?>
            <div class="event-detail-map" id="eventDetailMap"></div>
        <?php endif; ?>

        <?php if ($isPast && $album !== null): ?>
            <div class="album-section" data-reveal>
                <h3 class="section-title"><?= e((string) ($album['titre'] ?? '')) ?></h3>
                <?php if (! empty($photos)): ?>
                    <div class="album-gallery">
                        <?php foreach ($photos as $photo): ?>
                            <a href="<?= asset((string) $photo['image']) ?>" class="album-photo-link"
                               data-lightbox="event-album"
                               data-title="<?= e((string) ($photo['legende'] ?: $photo['title'] ?: '')) ?>">
                                <img src="<?= e(photo_src($photo)) ?>"
                                     alt="<?= e((string) ($photo['legende'] ?: $photo['title'] ?: '')) ?>" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="citoyen-empty"><?= $isAr ? 'لا توجد صور متاحة' : 'Aucune photo disponible' ?></div>
                <?php endif; ?>

                <?php if (! empty($album['recit'])): ?>
                    <div class="album-recit">
                        <h4><?= $isAr ? 'القصة' : 'Récit' ?></h4>
                        <p><?= e((string) $album['recit']) ?></p>
                        <a href="<?= url('citoyen/albums/' . (int) ($album['id'] ?? 0)) ?>" class="btn-participate-today">
                            <?= $isAr ? 'عرض الألبوم' : "Voir l'album" ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($isPast): ?>
            <div class="citoyen-simple-card">
                <i class="mdi mdi-information-outline"></i>
                <p><?= $isAr ? 'لا يتوفر ألبوم صور لهذا الحدث.' : 'Aucun album photo n\'est disponible pour cet événement.' ?></p>
            </div>
        <?php endif; ?>

        <?php
        // Intégrer la section commentaires
        $isLogged = is_logged();
        include __DIR__ . '/../partials/event_comments.php';
        ?>
    </div>
</section>

<div class="ev-sticky-bar" id="evStickyBar">
    <button type="button" class="ev-sticky-btn ev-sticky-share" onclick="shareEventWhatsApp()">
        <i class="mdi mdi-share-variant"></i> <span><?= $isAr ? 'مشاركة' : 'Partager' ?></span>
    </button>
    <?php if (!$hasParticipated && !$isPast && $openForScan && (!($isPublic ?? false) || is_logged())): ?>
        <a href="<?= url('qrcode/scan-optimise') ?>" class="ev-sticky-btn ev-sticky-primary">
            <i class="mdi mdi-qrcode-scan"></i> <span><?= $isAr ? 'أمسح للمشاركة' : 'Scanner pour participer' ?></span>
        </a>
    <?php endif; ?>
</div>

<link href="<?= asset('/assets/vendor/simplelightbox/simple-lightbox.min.css') ?>" rel="stylesheet">
<script src="<?= asset('/assets/vendor/simplelightbox/simple-lightbox.min.js') ?>"></script>
<style>
.event-qr-section { margin:1.5rem 0; }
.event-qr-card {
    display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap;
    background:linear-gradient(135deg, #0F2B22 0%, #1A4D3E 100%);
    border-radius:16px; padding:1.5rem; color:#FAF6EC;
    border:1px solid rgba(212,175,55,0.3);
}
.event-qr-visual {
    flex:0 0 auto; background:#fff; border-radius:12px; padding:0.6rem;
    box-shadow:0 0 0 3px rgba(212,175,55,0.4), 0 8px 24px rgba(0,0,0,0.3);
}
.event-qr-img { display:block; width:160px; height:160px; border-radius:8px; }
.event-qr-info { flex:1; min-width:200px; }
.event-qr-info h3 { font-size:1.1rem; font-weight:700; margin:0 0 0.4rem; }
.event-qr-info h3 .mdi { color:#F0C95C; }
.event-qr-desc { font-size:0.85rem; color:rgba(250,246,236,0.8); margin:0 0 1rem; }
.event-qr-actions { display:flex; gap:0.6rem; flex-wrap:wrap; }
.btn-ev-qr {
    display:inline-flex; align-items:center; gap:0.4rem;
    padding:0.5rem 1rem; border-radius:999px; font-weight:600; font-size:0.85rem;
    border:none; cursor:pointer; transition:0.2s;
}
.btn-ev-qr-wa { background:#25D366; color:#fff; }
.btn-ev-qr-wa:hover { background:#1da851; transform:translateY(-1px); }
.btn-ev-qr-email { background:#7c3aed; color:#fff; }
.btn-ev-qr-email:hover { background:#6d28d9; transform:translateY(-1px); }
.btn-ev-qr-download { background:rgba(255,255,255,0.15); color:#FAF6EC; border:1px solid rgba(255,255,255,0.3); }
.btn-ev-qr-download:hover { background:rgba(255,255,255,0.25); }
@media (max-width:520px) {
    .event-qr-card { flex-direction:column; text-align:center; }
    .event-qr-actions { justify-content:center; }
}
.citoyen-fav-btn-lg{position:absolute;top:0;inset-inline-end:0;width:42px;height:42px;border-radius:50%;border:1px solid #EDE7DA;background:#fff;color:#9CA3AF;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s;font-size:1.25rem;box-shadow:0 2px 8px rgba(15,43,34,.08)}
.citoyen-event-detail{position:relative}
.citoyen-fav-btn-lg:hover{border-color:#D4AF37;color:#D4AF37;transform:scale(1.08)}
.citoyen-fav-btn-lg.active{background:#D4AF37;color:#fff;border-color:#D4AF37}
.ev-sticky-bar{display:none}
@media (max-width:720px){
    .ev-sticky-bar{position:fixed;left:0;right:0;bottom:0;z-index:120;display:flex;gap:.5rem;padding:.6rem .75rem calc(.6rem + env(safe-area-inset-bottom));background:rgba(255,255,255,.96);backdrop-filter:blur(8px);border-top:1px solid #EDE7DA;box-shadow:0 -4px 16px rgba(15,43,34,.08)}
    .ev-sticky-btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.66rem .75rem;border-radius:999px;font-weight:700;font-size:.85rem;border:none;cursor:pointer;transition:.2s;text-decoration:none}
    .ev-sticky-share{background:#fff;color:#1A4D3E;border:1px solid #1A4D3E}
    .ev-sticky-primary{background:linear-gradient(135deg,#1A4D3E,#125A3B);color:#fff;box-shadow:0 4px 12px rgba(26,77,62,.3)}
    .ev-sticky-primary:hover{transform:translateY(-1px)}
    body{padding-bottom:76px}
}
</style>
<script>
(function () {
    'use strict';

    var eventId = <?= $eventId ?>;
    var isAr = <?= $isAr ? 'true' : 'false' ?>;
    var countStrong = document.getElementById('participantCountStrong');
    var eventTitle = <?= json_encode($eventTitle) ?>;
    var eventUrl = <?= json_encode($eventUrl) ?>;

    /* ── WhatsApp share ── */
    window.shareEventWhatsApp = function () {
        var text = <?= json_encode($shareText, JSON_UNESCAPED_UNICODE) ?>;
        window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank', 'noopener,noreferrer');
    };

    /* ── Email share ── */
    window.shareEventEmail = function () {
        var subject = <?= json_encode($shareSubject, JSON_UNESCAPED_UNICODE) ?>;
        var body    = <?= json_encode($shareText, JSON_UNESCAPED_UNICODE) ?>;
        window.location.href = 'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    };

    /* ── Download QR ── */
    window.downloadEventQr = function () {
        var img = document.getElementById('eventQrImg');
        if (!img || !img.src) return;
        var a = document.createElement('a');
        a.href = img.src;
        a.download = 'qr-evenement-' + eventId + '.png';
        a.click();
    };

    /* ── Compteur de participants en temps réel ── */
    var isPastEvent = <?= $isPast ? 'true' : 'false' ?>;
    function pollParticipants() {
        fetch('/api/evenements/' + eventId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success && data.data && typeof data.data.participants === 'number') {
                    countStrong.textContent = data.data.participants;
                }
            })
            .catch(function () {});
    }
    if (!isPastEvent) {
        pollParticipants();
        setInterval(pollParticipants, 15000);
    }

    /* ── Carte (après chargement de Leaflet) ── */
    document.addEventListener('DOMContentLoaded', function () {
        var latEl = <?= json_encode((string) ($event['latitude'] ?? '')) ?>;
        var lonEl = <?= json_encode((string) ($event['longitude'] ?? '')) ?>;
        var lat = parseFloat(latEl);
        var lon = parseFloat(lonEl);
        if (!isNaN(lat) && !isNaN(lon) && window.L) {
            var map = L.map('eventDetailMap').setView([lat, lon], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
            }).addTo(map);
            L.marker([lat, lon]).addTo(map);
        }
    });

    /* ── Lightbox ── */
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof SimpleLightbox !== 'undefined') {
            new SimpleLightbox('.album-photo-link', {
                captionsData: 'title',
                captionDelay: 250,
                closeText: isAr ? 'إغلاق' : 'Fermer'
            });
        }
    });

    /* ── Favori ── */
    var favBtn = document.getElementById('eventFavBtn');
    if (favBtn && window.WH_CSRF) {
        favBtn.addEventListener('click', function () {
            var id = favBtn.getAttribute('data-fav-id');
            if (!id) return;
            favBtn.disabled = true;
            fetch('/citoyen/favoris/' + id + '/toggle?ajax=1', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': window.WH_CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then(function (r) { return r.json(); })
              .then(function (res) {
                if (res && res.success) {
                    var icon = favBtn.querySelector('.mdi');
                    if (res.saved) {
                        favBtn.classList.add('active');
                        favBtn.setAttribute('data-active','true');
                        favBtn.setAttribute('aria-pressed','true');
                        if (icon) { icon.classList.remove('mdi-heart-outline'); icon.classList.add('mdi-heart'); }
                    } else {
                        favBtn.classList.remove('active');
                        favBtn.setAttribute('data-active','false');
                        favBtn.setAttribute('aria-pressed','false');
                        if (icon) { icon.classList.add('mdi-heart-outline'); icon.classList.remove('mdi-heart'); }
                    }
                }
              })
              .catch(function () {})
              .finally(function () { favBtn.disabled = false; });
        });
    }
})();
</script>
