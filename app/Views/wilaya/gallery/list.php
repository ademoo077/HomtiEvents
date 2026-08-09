<?php
/**
 * Galerie photos — Liste des événements avec albums.
 *
 * @var array $events
 */
use App\Helpers\I18n;

$title = __('common.gallery');
$page  = 'wilaya.gallery.list';
$dir   = I18n::direction();

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'                => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-image-multiple me-2"></i><?= e(__('common.gallery')) ?>
            </h1>
            <p class="wh-page-sub"><?= count($events) ?> <?= e(__('common.evenements')) ?></p>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($events as $ev): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <h6 class="card-title mb-0">
                                <?php if (! empty($ev['album_id'])): ?>
                                    <a href="<?= url('wilaya/evenements/' . (int) $ev['id'] . '/photos') ?>" class="text-decoration-none">
                                        <?= e($ev['adresse']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= e($ev['adresse']) ?>
                                <?php endif; ?>
                            </h6>
                            <span class="wh-badge <?= $badgeColor((string) $ev['statut']) ?> flex-shrink-0">
                                <?= e(statut_label((string) $ev['statut'])) ?>
                            </span>
                        </div>
                        <p class="text-muted small mb-2">
                            <i class="mdi mdi-map-marker-outline me-1"></i><?= e($ev['commune_nom'] ?? '') ?>
                            <?php if (! empty($ev['date_evenement'])): ?>
                                · <?= e($ev['date_evenement']) ?>
                            <?php endif; ?>
                        </p>
                         <?php if (! empty($ev['album_id'])): ?>
                             <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                                 <i class="mdi mdi-image text-primary"></i>
                                 <span class="fw-semibold"><?= (int) $ev['nb_photos'] ?></span>
                                 <span class="text-muted"><?= e(__('gallery.photos_count')) ?></span>
                                 <span class="badge <?= ($ev['album_statut'] ?? 'brouillon') === 'publie' ? 'bg-success' : 'bg-secondary' ?>">
                                     <?= e(__('gallery.status_' . (($ev['album_statut'] ?? 'brouillon') === 'publie' ? 'published' : 'draft'))) ?>
                                 </span>
                                 <?php if (($ev['album_statut'] ?? 'brouillon') === 'publie'): ?>
                                     <a href="<?= url('wilaya/albums/' . (int) $ev['album_id'] . '/unpublish') ?>" class="btn btn-sm btn-outline-warning" title="<?= e(__('gallery.unpublish')) ?>">
                                         <i class="mdi mdi-eye-off"></i>
                                     </a>
                                 <?php else: ?>
                                     <a href="<?= url('wilaya/albums/' . (int) $ev['album_id'] . '/publish') ?>" class="btn btn-sm btn-outline-success" title="<?= e(__('gallery.publish')) ?>">
                                         <i class="mdi mdi-eye"></i>
                                     </a>
                                 <?php endif; ?>
                             </div>
                         <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <?php if (! empty($ev['album_id'])): ?>
                            <a href="<?= url('wilaya/evenements/' . (int) $ev['id'] . '/photos') ?>" class="btn btn-sm btn-primary w-100">
                                <i class="mdi mdi-image-multiple me-1"></i><?= e(__('common.gallery')) ?>
                            </a>
                        <?php else: ?>
                            <a href="<?= url('wilaya/evenements/' . (int) $ev['id'] . '/photos/create') ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.create_album')) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($events === []): ?>
            <div class="col-12">
                <div class="wh-empty">
                    <i class="mdi mdi-image-multiple"></i>
                    <p><?= e(__('common.no_data')) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
