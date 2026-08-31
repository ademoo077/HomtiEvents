<?php
/** @var array $album @var array $photos @var array|null $association @var int $participantsCount */
use App\Helpers\I18n;

$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$titre  = (string) ($album['titre'] ?? '');
$recit  = (string) ($album['recit'] ?? '');
$cover  = (string) ($album['couverture'] ?? '');
$coverUrl = ($cover !== '' && is_file(public_path($cover))) ? asset($cover) : null;
$backUrl = ! empty($album['evenement_id']) ? 'citoyen/evenement/' . (int) $album['evenement_id'] : 'citoyen';
$dateLabel = ! empty($album['date_evenement']) ? date('d/m/Y', strtotime((string) $album['date_evenement'])) : '';
$adresse = (string) ($album['adresse'] ?? '');
$shareUrl = url('citoyen/albums/' . (int) $album['id']);
?>

<section class="citoyen-section">
    <div class="citoyen-album-hero" data-reveal>
        <?php if ($coverUrl !== null): ?>
            <img src="<?= e($coverUrl) ?>" alt="<?= e($titre) ?>" loading="lazy">
        <?php endif; ?>

        <a class="citoyen-back-link" href="<?= url($backUrl) ?>"
           onclick="if (history.length > 1) { event.preventDefault(); history.back(); }">
            <i class="mdi mdi-arrow-left"></i> <?= $isAr ? 'رجوع' : 'Retour' ?>
        </a>

        <div class="citoyen-album-hero-body">
            <?php if (! empty($album['statut'])): ?>
                <span class="badge badge-programme"><?= $isAr ? 'منشور' : 'Album publié' ?></span>
            <?php endif; ?>
            <h1><?= e($titre !== '' ? $titre : 'Album') ?></h1>
            <p>
                <?= e($recit !== '' ? $recit : trim($adresse . ($adresse !== '' && $dateLabel !== '' ? ' — ' : '') . $dateLabel)) ?>
            </p>
            <?php if ($association !== null): ?>
                <div><?= association_badge($association) ?></div>
            <?php endif; ?>

            <div class="citoyen-album-actions">
                <?php if ($participantsCount > 0): ?>
                    <span class="badge badge-info">
                        <i class="mdi mdi-account-group"></i>
                        <?= $isAr ? $participantsCount . ' مشارك' : $participantsCount . ' participant' . ($participantsCount > 1 ? 's' : '') ?>
                    </span>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline" id="shareAlbumBtn">
                    <i class="mdi mdi-share-variant"></i> <?= $isAr ? 'مشاركة' : 'Partager' ?>
                </button>
            </div>
        </div>
    </div>

    <?php if ($photos !== []): ?>
        <div class="album-photo-grid" id="albumPhotoGrid">
            <?php foreach ($photos as $i => $ph):
                $cap = (string) ($ph['legende'] ?: $ph['title'] ?: ''); ?>
                <figure class="album-photo-tile" style="animation-delay:<?= min($i * 0.04, 0.6) ?>s">
                    <a href="<?= e(asset((string) $ph['image'])) ?>"
                       data-lightbox="album-gallery"
                       data-title="<?= e($cap) ?>">
                        <img src="<?= e(photo_src($ph)) ?>"
                             alt="<?= e($cap !== '' ? $cap : $titre) ?>" loading="lazy">
                    </a>
                    <?php if ($cap !== ''): ?>
                        <figcaption><?= e($cap) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="citoyen-album-empty">
            <i class="mdi mdi-image-multiple-outline"></i>
            <p><?= $isAr ? 'لا توجد صور في هذا الألبوم.' : 'Aucune photo dans cet album.' ?></p>
        </div>
    <?php endif; ?>
</section>

<link href="<?= asset('/assets/vendor/simplelightbox/simple-lightbox.min.css') ?>" rel="stylesheet">
<script src="<?= asset('/assets/vendor/simplelightbox/simple-lightbox.min.js') ?>"></script>
<script>
(function () {
    'use strict';
    var isAr = <?= $isAr ? 'true' : 'false' ?>;
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof SimpleLightbox !== 'undefined') {
            new SimpleLightbox('[data-lightbox="album-gallery"]', {
                captionsData: 'title',
                captionDelay: 250,
                closeText: isAr ? 'إغلاق' : 'Fermer'
            });
        }

        var shareBtn = document.getElementById('shareAlbumBtn');
        if (shareBtn) {
            var shareUrl = <?= json_encode($shareUrl, JSON_UNESCAPED_SLASHES) ?>;
            shareBtn.addEventListener('click', function () {
                var done = function () {
                    shareBtn.innerHTML = '<i class="mdi mdi-check"></i> ' + (isAr ? 'تم النسخ' : 'Lien copié');
                    setTimeout(function () {
                        shareBtn.innerHTML = '<i class="mdi mdi-share-variant"></i> ' + (isAr ? 'مشاركة' : 'Partager');
                    }, 2000);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(shareUrl).then(done);
                } else {
                    var tmp = document.createElement('input');
                    tmp.value = shareUrl;
                    document.body.appendChild(tmp);
                    tmp.select();
                    document.execCommand('copy');
                    document.body.removeChild(tmp);
                    done();
                }
            });
        }
    });
})();
</script>
