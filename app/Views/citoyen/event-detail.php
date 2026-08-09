<?php
/** @var array $event @var array $photos @var array|null $album @var bool $hasParticipated */
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;

$isAr = I18n::direction() === 'rtl';
$eventDateStr = (string) ($event['date_evenement'] ?? '');
$eventTime = $event['heure'] ?? '';
$eventDateObj = new DateTimeImmutable($eventDateStr);
$todayStr = date('Y-m-d');
$isPast = $eventDateStr < $todayStr;
$isToday = $eventDateStr === $todayStr;
$dateDebut = $event['date_debut'] ?? null;
$statut = (string) ($event['statut'] ?? 'PROGRAMME');
$openForScan = in_array($statut, ['PROGRAMME', 'QR_GENERE', 'EN_COURS'], true);
$anomalies = $event['anomalies'] ?? '';
?>
<section class="citoyen-section">
    <div class="citoyen-event-detail">

        <a class="citoyen-back-link" href="<?= url('citoyen/explorer') ?>"
           onclick="if (history.length > 1) { event.preventDefault(); history.back(); }">
            <i class="mdi mdi-arrow-left"></i> <?= $isAr ? 'رجوع' : 'Retour' ?>
        </a>

        <div class="event-detail-header" data-reveal>
            <span class="badge badge-<?= e(statut_key($statut)) ?>"><?= e(statut_label($statut)) ?></span>
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
                <?php if ($dateDebut && $dateDebut !== $eventDateStr): ?>
                    <span><?= e((new DateTimeImmutable((string) $dateDebut))->format('d/m/Y')) ?> → <?= e($eventDateObj->format('d/m/Y')) ?></span>
                <?php else: ?>
                    <span><?= e($eventDateObj->format('d/m/Y')) ?></span>
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
            </div>
            <?php if (! $hasParticipated): ?>
                <?php if (!$isPast && $openForScan): ?>
                    <a href="<?= url('qrcode/scan-optimise') ?>" class="btn-participate-today">
                        <i class="mdi mdi-qrcode-scan"></i> <?= $isAr ? 'أمسح للمشاركة' : 'Scanner pour participer' ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <span class="participated-badge">
                    <i class="mdi mdi-check-circle"></i> <?= $isAr ? 'لقد شاركت في هذا الحدث' : 'Vous avez participé' ?>
                </span>
            <?php endif; ?>
        </div>

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
                                <img src="<?= asset((string) $photo['image']) ?>"
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
    </div>
</section>

<link href="<?= asset('/assets/vendor/simplelightbox/simple-lightbox.min.css') ?>" rel="stylesheet">
<script src="<?= asset('/assets/vendor/simplelightbox/simple-lightbox.min.js') ?>"></script>
<script>
(function () {
    'use strict';

    var eventId = <?= (int) ($event['id'] ?? 0) ?>;
    var isAr = <?= $isAr ? 'true' : 'false' ?>;
    var countStrong = document.getElementById('participantCountStrong');

    /* ── Compteur de participants en temps réel ── */
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
    pollParticipants();
    setInterval(pollParticipants, 15000);

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
})();
</script>
