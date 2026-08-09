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
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
         <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                     <div>
                         <h5 class="mb-1"><?= e($album['titre'] ?? '') ?></h5>
                         <small class="text-muted">
                             <?= count($photos) ?> <?= e(__('gallery.photos_count')) ?>
                             <?php if (! empty($album['statut'])): ?>
                                 · <span class="badge <?= $album['statut'] === 'publie' ? 'bg-success' : 'bg-secondary' ?>">
                                     <?= e(__('gallery.status_' . ($album['statut'] === 'publie' ? 'published' : 'draft'))) ?>
                                 </span>
                             <?php endif; ?>
                         </small>
                     </div>
                     <div class="d-flex gap-1">
                         <?php if ($album['statut'] === 'publie'): ?>
                             <a href="<?= url('wilaya/albums/' . (int) $album['id'] . '/unpublish') ?>" class="btn btn-sm btn-outline-warning" title="<?= e(__('gallery.unpublish')) ?>">
                                 <i class="mdi mdi-eye-off"></i>
                             </a>
                         <?php else: ?>
                             <a href="<?= url('wilaya/albums/' . (int) $album['id'] . '/publish') ?>" class="btn btn-sm btn-outline-success" title="<?= e(__('gallery.publish')) ?>">
                                 <i class="mdi mdi-eye"></i>
                             </a>
                         <?php endif; ?>
                         <a href="<?= url('wilaya/albums/' . (int) $album['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="<?= e(__('common.edit')) ?>">
                             <i class="mdi mdi-pencil"></i>
                         </a>
                     </div>
                 </div>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($photos as $photo): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 wh-photo-card">
                        <div class="card-img-top ratio ratio-1x1 overflow-hidden" style="background:#f1f5f9">
                            <?php if (! empty($photo['image'])): ?>
                                <img src="<?= e($photo['image']) ?>" alt="<?= e($photo['legende'] ?? '') ?>"
                                     loading="lazy" style="object-fit:cover">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center text-muted h-100">
                                    <i class="mdi mdi-image-off" style="font-size:2rem"></i>
                 </div>
                 <?php if (! empty($album['id']) && $photos !== []): ?>
                     <div class="mt-3 pt-3 border-top">
                         <div class="d-flex align-items-center justify-content-between mb-2">
                             <small class="fw-semibold text-muted">
                                 <i class="mdi mdi-image-outline me-1"></i><?= e(__('gallery.cover_active')) ?>
                             </small>
                             <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#coverPicker" aria-expanded="false">
                                 <i class="mdi mdi-palette me-1"></i><?= e(__('gallery.cover')) ?>
                             </button>
                         </div>
                         <div class="collapse" id="coverPicker">
                             <div class="row g-2">
                                 <?php foreach ($photos as $ph): ?>
                                     <div class="col-4 col-md-2">
                                         <form method="post" action="<?= url('wilaya/albums/' . (int) $album['id'] . '/cover') ?>" class="d-inline">
                                             <?= csrf_field() ?>
                                             <input type="hidden" name="photo_id" value="<?= (int) $ph['id'] ?>">
                                             <button type="submit" class="btn p-0 border-0 bg-transparent <?= ($album['couverture'] ?? '') === $ph['image'] ? 'ring ring-2 ring-primary' : '' ?>">
                                                 <img src="<?= e($ph['image']) ?>" alt="" class="img-thumbnail" style="width:100%;aspect-ratio:1/1;object-fit:cover">
                                             </button>
                                         </form>
                                     </div>
                                 <?php endforeach; ?>
                             </div>
                         </div>
                     </div>
                 <?php endif; ?>
                 <div class="mt-3">
                     <form method="post" action="<?= url('wilaya/albums/' . (int) $album['id'] . '/update') ?>" class="row g-2 align-items-end">
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
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-2">
                            <?php if (! empty($photo['legende'])): ?>
                                <p class="card-text small text-muted mb-0 text-truncate">
                                    <?= e($photo['legende']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-end gap-1 p-2">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('wilaya/photos/' . (int) $photo['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                <i class="mdi mdi-pencil"></i>
                            </a>
                            <form method="post" action="<?= url('wilaya/photos/' . (int) $photo['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.delete')) ?>">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($photos === []): ?>
                <div class="col-12">
                    <div class="wh-empty">
                        <i class="mdi mdi-image-multiple"></i>
                        <p><?= e(__('gallery.no_photos')) ?></p>
                        <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos/create') ?>" class="btn btn-primary mt-2">
                            <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.add_first')) ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
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
