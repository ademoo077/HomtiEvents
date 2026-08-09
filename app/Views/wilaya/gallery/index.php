<?php
/**
 * Galerie photos — Liste des photos d'un événement.
 *
 * @var array $event
 * @var array|null $album
 * @var array $photos
 */
use App\Helpers\I18n;

$title = __('common.gallery') . ' — ' . ($event['adresse'] ?? '');
$page  = 'wilaya.gallery.index';
$dir   = I18n::direction();

$cover = (string) ($album['couverture'] ?? '');
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-image-multiple me-2"></i><?= e(__('common.gallery')) ?>
            </h1>
            <p class="wh-page-sub">
                <?= e($event['adresse'] ?? '') ?>
                <?php if (! empty($event['commune_nom'])): ?>
                    — <?= e($event['commune_nom']) ?>
                <?php endif; ?>
                <?php if (! empty($event['date_evenement'])): ?>
                    · <?= e($event['date_evenement']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= url('wilaya/evenements/' . (int) $event['id']) ?>">
                <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
            </a>
            <a class="btn btn-primary" href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos/create') ?>">
                <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.add_photos')) ?>
            </a>
        </div>
    </div>

    <?php if ($album !== null): ?>

        <!-- ═══ GESTION DE L'ALBUM ═══ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <?php if ($photos !== []): ?>
                            <img src="<?= e($cover !== '' ? $cover : $photos[0]['image']) ?>"
                                 alt="<?= e($album['titre'] ?? '') ?>" class="wh-album-cover">
                        <?php else: ?>
                            <div class="wh-album-cover d-flex align-items-center justify-content-center text-muted">
                                <i class="mdi mdi-image-multiple"></i>
                            </div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <h5 class="mb-1 text-truncate"><?= e($album['titre'] ?? '') ?></h5>
                            <?php if (! empty($album['recit'])): ?>
                                <p class="text-muted small mb-1 wh-album-recit"><?= e($album['recit']) ?></p>
                            <?php endif; ?>
                            <small class="text-muted">
                                <?= count($photos) ?> <?= e(__('gallery.photos_count')) ?>
                                <span class="mx-1">·</span>
                                <span class="badge <?= $album['statut'] === 'publie' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= e(__('gallery.status_' . ($album['statut'] === 'publie' ? 'published' : 'draft'))) ?>
                                </span>
                            </small>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if ($album['statut'] === 'publie'): ?>
                            <form method="post" action="<?= url('wilaya/albums/' . (int) $album['id'] . '/unpublish') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="<?= e(__('gallery.unpublish')) ?>">
                                    <i class="mdi mdi-eye-off me-1"></i><?= e(__('gallery.unpublish')) ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= url('wilaya/albums/' . (int) $album['id'] . '/publish') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-success" title="<?= e(__('gallery.publish')) ?>">
                                    <i class="mdi mdi-eye me-1"></i><?= e(__('gallery.publish')) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#coverPicker" aria-expanded="false">
                            <i class="mdi mdi-palette me-1"></i><?= e(__('gallery.cover')) ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#albumEdit" aria-expanded="false">
                            <i class="mdi mdi-pencil me-1"></i><?= e(__('common.edit')) ?>
                        </button>
                    </div>
                </div>

                <!-- Sélecteur de couverture -->
                <div class="collapse mt-3" id="coverPicker">
                    <div class="pt-3 border-top">
                        <small class="fw-semibold text-muted d-block mb-2">
                            <i class="mdi mdi-image-outline me-1"></i><?= e(__('gallery.cover_active')) ?>
                        </small>
                        <div class="row g-2">
                            <?php foreach ($photos as $ph): ?>
                                <div class="col-4 col-md-2">
                                    <form method="post" action="<?= url('wilaya/albums/' . (int) $album['id'] . '/cover') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="photo_id" value="<?= (int) $ph['id'] ?>">
                                        <button type="submit" class="btn p-0 border-0 bg-transparent wh-cover-btn <?= $cover === (string) $ph['image'] ? 'wh-cover-active' : '' ?>"
                                                title="<?= e(__('gallery.cover')) ?>">
                                            <img src="<?= e($ph['image']) ?>" alt="" loading="lazy">
                                            <?php if ($cover === (string) $ph['image']): ?>
                                                <span class="wh-cover-check"><i class="mdi mdi-check"></i></span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Édition du titre / récit -->
                <div class="collapse mt-3" id="albumEdit">
                    <form method="post" action="<?= url('wilaya/albums/' . (int) $album['id'] . '/update') ?>" class="row g-2 align-items-end pt-3 border-top">
                        <?= csrf_field() ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium" for="album_titre"><?= e(__('common.title')) ?></label>
                            <input type="text" class="form-control form-control-sm" id="album_titre" name="titre" value="<?= e($album['titre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium" for="album_recit"><?= e(__('gallery.description')) ?></label>
                            <textarea class="form-control form-control-sm" id="album_recit" name="recit" rows="2"><?= e($album['recit'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══ GRILLE MASONRY ═══ -->
        <div class="wh-masonry" id="photoGrid">
            <?php foreach ($photos as $i => $photo): ?>
                <?php $hasImage = ! empty($photo['image']); ?>
                <figure class="wh-photo" data-idx="<?= $i ?>">
                    <?php if ($hasImage): ?>
                        <button type="button" class="wh-photo-media wh-lb-open" data-src="<?= e($photo['image']) ?>" data-caption="<?= e($photo['legende'] ?? '') ?>" aria-label="<?= e($photo['legende'] ?? __('common.gallery')) ?>">
                            <img src="<?= e($photo['image']) ?>" alt="<?= e($photo['legende'] ?? '') ?>" loading="lazy">
                            <span class="wh-photo-order" title="<?= e(__('gallery.position')) ?>">#<?= $i + 1 ?></span>
                            <span class="wh-photo-zoom"><i class="mdi mdi-magnify-plus"></i></span>
                        </button>
                    <?php else: ?>
                        <div class="wh-photo-media wh-photo-empty">
                            <i class="mdi mdi-image-off"></i>
                            <span class="wh-photo-order" title="<?= e(__('gallery.position')) ?>">#<?= $i + 1 ?></span>
                        </div>
                    <?php endif; ?>
                    <figcaption class="wh-photo-meta">
                        <?= $photo['legende'] ? e($photo['legende']) : '<span class="text-muted fst-italic">—</span>' ?>
                    </figcaption>
                    <div class="wh-photo-actions">
                        <a class="btn btn-sm btn-light" href="<?= url('wilaya/photos/' . (int) $photo['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                            <i class="mdi mdi-pencil"></i>
                        </a>
                        <form method="post" action="<?= url('wilaya/photos/' . (int) $photo['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-light text-danger" title="<?= e(__('common.delete')) ?>">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </form>
                    </div>
                </figure>
            <?php endforeach; ?>

            <?php if ($photos === []): ?>
                <div class="wh-empty w-100">
                    <i class="mdi mdi-image-multiple"></i>
                    <p><?= e(__('gallery.no_photos')) ?></p>
                    <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos/create') ?>" class="btn btn-primary mt-2">
                        <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.add_first')) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lightbox -->
        <div class="wh-lightbox" id="whLightbox" hidden>
            <button type="button" class="wh-lb-close" aria-label="<?= e(__('common.close')) ?>"><i class="mdi mdi-close"></i></button>
            <button type="button" class="wh-lb-nav wh-lb-prev" aria-label="<?= e(__('common.previous')) ?>"><i class="mdi mdi-chevron-left"></i></button>
            <button type="button" class="wh-lb-nav wh-lb-next" aria-label="<?= e(__('common.next')) ?>"><i class="mdi mdi-chevron-right"></i></button>
            <figure class="wh-lb-figure">
                <img class="wh-lb-img" src="" alt="">
                <figcaption class="wh-lb-caption"></figcaption>
            </figure>
        </div>

    <?php else: ?>
        <div class="wh-empty">
            <i class="mdi mdi-image-multiple"></i>
            <p><?= e(__('gallery.no_album')) ?></p>
            <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos/create') ?>" class="btn btn-primary mt-2">
                <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.create_album')) ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.wh-album-cover {
    width: 84px;
    height: 84px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
    background: #f1f5f9;
    font-size: 2rem;
}
.wh-album-recit {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.wh-masonry {
    columns: 3 260px;
    column-gap: 1rem;
}
.wh-photo {
    break-inside: avoid;
    margin: 0 0 1rem;
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .08), 0 1px 2px rgba(15, 23, 42, .04);
    transition: box-shadow .18s, transform .18s;
}
.wh-photo:hover { box-shadow: 0 10px 24px rgba(15, 23, 42, .14); transform: translateY(-2px); }
.wh-photo-media {
    display: block;
    width: 100%;
    padding: 0;
    border: none;
    background: #f1f5f9;
    position: relative;
    cursor: zoom-in;
}
.wh-photo-media img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: cover;
}
.wh-photo-empty {
    aspect-ratio: 4/3;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 2rem;
    cursor: default;
}
.wh-photo-order {
    position: absolute;
    top: 8px;
    inset-inline-start: 8px;
    z-index: 2;
    background: rgba(15, 23, 42, .72);
    color: #fff;
    font-size: .72rem;
    font-weight: 700;
    padding: .16rem .5rem;
    border-radius: 999px;
    backdrop-filter: blur(4px);
}
.wh-photo-zoom {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, .35);
    color: #fff;
    font-size: 1.8rem;
    opacity: 0;
    transition: opacity .18s;
}
.wh-photo-media:hover .wh-photo-zoom { opacity: 1; }
.wh-photo-meta {
    padding: .5rem .65rem .35rem;
    font-size: .8rem;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wh-photo-actions {
    position: absolute;
    top: 8px;
    inset-inline-end: 8px;
    z-index: 2;
    display: flex;
    gap: .3rem;
    opacity: 0;
    transform: translateY(-4px);
    transition: opacity .18s, transform .18s;
}
.wh-photo:hover .wh-photo-actions,
.wh-photo:focus-within .wh-photo-actions { opacity: 1; transform: none; }
.wh-photo-actions .btn {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    box-shadow: 0 2px 6px rgba(15, 23, 42, .2);
}

.wh-cover-btn {
    width: 100%;
    position: relative;
    border-radius: 8px;
    overflow: hidden;
}
.wh-cover-btn img {
    width: 100%;
    aspect-ratio: 1/1;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: border-color .15s, opacity .15s;
}
.wh-cover-btn:hover img { opacity: .85; }
.wh-cover-active img { border-color: var(--wh-primary, #2563eb); }
.wh-cover-check {
    position: absolute;
    top: 4px;
    inset-inline-end: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--wh-primary, #2563eb);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
}

.wh-lightbox {
    position: fixed;
    inset: 0;
    z-index: 3000;
    background: rgba(2, 6, 23, .92);
    display: flex;
    align-items: center;
    justify-content: center;
}
.wh-lightbox[hidden] { display: none; }
.wh-lb-figure {
    margin: 0;
    max-width: min(1100px, 92vw);
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .75rem;
}
.wh-lb-img {
    max-width: 100%;
    max-height: 78vh;
    border-radius: 10px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, .5);
}
.wh-lb-caption {
    color: #e2e8f0;
    font-size: .9rem;
    text-align: center;
    min-height: 1.1em;
}
.wh-lb-close,
.wh-lb-nav {
    position: absolute;
    border: none;
    background: rgba(255, 255, 255, .12);
    color: #fff;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    cursor: pointer;
    transition: background .15s;
}
.wh-lb-close:hover, .wh-lb-nav:hover { background: rgba(255, 255, 255, .24); }
.wh-lb-close { top: 18px; inset-inline-end: 18px; }
.wh-lb-prev { inset-inline-start: 18px; top: 50%; transform: translateY(-50%); }
.wh-lb-next { inset-inline-end: 18px; top: 50%; transform: translateY(-50%); }
@media (max-width: 575.98px) {
    .wh-lb-prev { inset-inline-start: 6px; }
    .wh-lb-next { inset-inline-end: 6px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const items = Array.from(document.querySelectorAll('.wh-lb-open'));
    const lightbox = document.getElementById('whLightbox');
    const lbImg = lightbox.querySelector('.wh-lb-img');
    const lbCaption = lightbox.querySelector('.wh-lb-caption');
    const closeBtn = lightbox.querySelector('.wh-lb-close');
    const prevBtn = lightbox.querySelector('.wh-lb-prev');
    const nextBtn = lightbox.querySelector('.wh-lb-next');

    if (items.length === 0) return;

    let current = 0;

    function show(idx) {
        current = (idx + items.length) % items.length;
        const el = items[current];
        lbImg.src = el.dataset.src;
        lbImg.alt = el.dataset.caption || '';
        lbCaption.textContent = el.dataset.caption || '';
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function hide() {
        lightbox.hidden = true;
        lbImg.src = '';
        document.body.style.overflow = '';
    }

    items.forEach((el, idx) => el.addEventListener('click', () => show(idx)));
    closeBtn.addEventListener('click', hide);
    prevBtn.addEventListener('click', () => show(current - 1));
    nextBtn.addEventListener('click', () => show(current + 1));
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) hide(); });

    document.addEventListener('keydown', (e) => {
        if (lightbox.hidden) return;
        if (e.key === 'Escape') hide();
        if (e.key === 'ArrowLeft') show(current - 1);
        if (e.key === 'ArrowRight') show(current + 1);
    });
});
</script>
