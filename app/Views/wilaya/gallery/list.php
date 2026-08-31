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
$isAr  = I18n::locale() === 'ar';

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'               => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};

$totalPhotos = 0;
$publishedCount = 0;
$draftCount = 0;
foreach ($events as $ev) {
    $totalPhotos += (int) ($ev['nb_photos'] ?? 0);
    if (($ev['album_statut'] ?? 'brouillon') === 'publie') {
        $publishedCount++;
    } else {
        $draftCount++;
    }
}
?>
<div class="wh-page">

    <!-- Gradient Hero -->
    <div class="mb-4" style="background:linear-gradient(135deg, #0B5ED7 0%, #6610f2 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden">
        <div style="position:absolute;top:-40%;right:-8%;width:320px;height:320px;background:rgba(255,255,255,.08);border-radius:50%"></div>
        <div style="position:absolute;bottom:-30%;left:5%;width:200px;height:200px;background:rgba(255,255,255,.05);border-radius:50%"></div>
        <div class="row align-items-center" style="position:relative;z-index:1">
            <div class="col-lg-8">
                <h1 class="mb-1" style="font-size:1.5rem;font-weight:800">
                    <i class="mdi mdi-image-multiple me-2"></i><?= e(__('common.gallery')) ?>
                </h1>
                <p class="mb-0" style="opacity:.85;font-size:.9rem">
                    <?= count($events) ?> <?= e(__('common.evenements')) ?> · <?= $totalPhotos ?> <?= e(__('landing.albums_photos')) ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 d-flex gap-2 justify-content-lg-end">
                <div class="text-center" style="background:rgba(255,255,255,.15);border-radius:.65rem;padding:.6rem 1.2rem;min-width:80px">
                    <div style="font-size:1.3rem;font-weight:800"><?= $totalPhotos ?></div>
                    <div style="font-size:.7rem;opacity:.85"><?= $isAr ? 'صورة' : 'photos' ?></div>
                </div>
                <div class="text-center" style="background:rgba(255,255,255,.15);border-radius:.65rem;padding:.6rem 1.2rem;min-width:80px">
                    <div style="font-size:1.3rem;font-weight:800"><?= count($events) ?></div>
                    <div style="font-size:.7rem;opacity:.85"><?= $isAr ? 'حدث' : 'événements' ?></div>
                </div>
                <div class="text-center" style="background:rgba(255,255,255,.15);border-radius:.65rem;padding:.6rem 1.2rem;min-width:80px">
                    <div style="font-size:1.3rem;font-weight:800"><?= $publishedCount ?></div>
                    <div style="font-size:.7rem;opacity:.85"><?= $isAr ? 'منشور' : 'publiés' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="row g-3">
        <?php foreach ($events as $ev): ?>
            <?php
                $hasAlbum = ! empty($ev['album_id']);
                $coverUrl = $hasAlbum && ! empty($ev['album_couverture']) ? e(photo_src(['image' => $ev['album_couverture']])) : '';
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100" style="transition:transform .2s,box-shadow .2s;border-radius:var(--wh-radius);overflow:hidden">
                    <!-- Cover strip -->
                    <div style="height:140px;background:linear-gradient(135deg,#0B5ED7,#6610f2);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden">
                        <?php if ($coverUrl): ?>
                            <img src="<?= $coverUrl ?>" alt="" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">
                        <?php else: ?>
                            <i class="mdi mdi-image-multiple" style="font-size:3rem;color:rgba(255,255,255,.4)"></i>
                        <?php endif; ?>
                        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.5) 0%,transparent 60%)"></div>
                        <?php if ($hasAlbum): ?>
                            <span style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,.92);color:#334155;font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:999px">
                                <?= (int) ($ev['nb_photos'] ?? 0) ?> <?= $isAr ? 'صورة' : 'photos' ?>
                            </span>
                        <?php endif; ?>
                        <span class="wh-badge <?= $badgeColor((string) $ev['statut']) ?>" style="position:absolute;bottom:10px;left:10px">
                            <?= e(statut_label((string) $ev['statut'])) ?>
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column" style="padding:1rem 1.15rem">
                        <h6 class="card-title fw-bold mb-1" style="font-size:.92rem">
                            <?php if ($hasAlbum): ?>
                                <a href="<?= url('wilaya/evenements/' . (int) $ev['id'] . '/photos') ?>" class="text-decoration-none" style="color:inherit">
                                    <?= e($ev['adresse']) ?>
                                </a>
                            <?php else: ?>
                                <?= e($ev['adresse']) ?>
                            <?php endif; ?>
                        </h6>
                        <p class="text-muted small mb-2" style="font-size:.8rem">
                            <i class="mdi mdi-map-marker-outline me-1"></i><?= e($ev['commune_nom'] ?? '') ?>
                            <?php if (! empty($ev['date_evenement'])): ?>
                                · <?= e($ev['date_evenement']) ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($hasAlbum): ?>
                            <div class="d-flex align-items-center gap-2 mt-auto pt-2" style="border-top:1px solid #f1f5f9">
                                <span class="badge <?= ($ev['album_statut'] ?? 'brouillon') === 'publie' ? 'bg-success' : 'bg-secondary' ?>" style="font-size:.68rem">
                                    <?= e(__('gallery.status_' . (($ev['album_statut'] ?? 'brouillon') === 'publie' ? 'published' : 'draft'))) ?>
                                </span>
                                <?php if (($ev['album_statut'] ?? 'brouillon') === 'publie'): ?>
                                    <form method="post" action="<?= url('wilaya/albums/' . (int) $ev['album_id'] . '/unpublish') ?>" data-confirm="<?= e(__('gallery.unpublish_confirm')) ?>" class="d-inline ms-auto">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-warning" style="padding:.15rem .4rem;font-size:.7rem" title="<?= e(__('gallery.unpublish')) ?>"><i class="mdi mdi-eye-off"></i></button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= url('wilaya/albums/' . (int) $ev['album_id'] . '/publish') ?>" data-confirm="<?= e(__('gallery.publish_confirm')) ?>" class="d-inline ms-auto">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success" style="padding:.15rem .4rem;font-size:.7rem" title="<?= e(__('gallery.publish')) ?>"><i class="mdi mdi-eye"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="px-3 pb-3">
                        <?php if ($hasAlbum): ?>
                            <a href="<?= url('wilaya/evenements/' . (int) $ev['id'] . '/photos') ?>" class="btn btn-sm btn-primary w-100 fw-semibold" style="border-radius:.55rem">
                                <i class="mdi mdi-image-multiple me-1"></i><?= e(__('common.gallery')) ?>
                            </a>
                        <?php else: ?>
                            <a href="<?= url('wilaya/evenements/' . (int) $ev['id'] . '/photos/create') ?>" class="btn btn-sm btn-outline-primary w-100" style="border-radius:.55rem">
                                <i class="mdi mdi-plus me-1"></i><?= e(__('gallery.create_album')) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($events === []): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <div style="width:80px;height:80px;margin:0 auto 1rem;background:var(--wh-purple-soft);border-radius:50%;display:grid;place-items:center">
                        <i class="mdi mdi-image-multiple" style="font-size:2.2rem;color:var(--wh-purple)"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?= $isAr ? 'لا توجد ألبومات بعد' : 'Aucun album pour le moment' ?></h5>
                    <p class="text-muted mb-3" style="font-size:.9rem"><?= $isAr ? 'أنشئ أول ألبوم صور لحدثك' : 'Créez votre premier album photo pour un événement' ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
