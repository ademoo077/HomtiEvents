<?php
/**
 * Galerie associative — Liste des événements avec leur album photos.
 *
 * @var array $events
 */
use App\Helpers\I18n;

$title = __('common.gallery');
$page  = 'association.gallery';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="wh-page">
    <style>
        .wh-card-hover { transition: transform .2s ease, box-shadow .2s ease; }
        .wh-card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,.12) !important; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-image-multiple me-2"></i><?= e(__('common.gallery')) ?>
            </h1>
            <p class="wh-page-sub"><?= e($isAr ? 'إدارة صور فعالياتك' : 'Soumettez et suivez les photos de vos événements') ?></p>
        </div>
    </div>

    <?php if ($events === []): ?>
        <div class="wh-empty card shadow-sm py-5">
            <div class="card-body text-center">
                <i class="mdi mdi-image-multiple text-muted" style="font-size: 2rem"></i>
                <p class="mb-2 mt-2"><?= e($isAr ? 'لا توجد فعاليات بعد' : 'Aucun événement enregistré.') ?></p>
                <a href="<?= url('association/create') ?>" class="btn btn-sm btn-primary">
                    <i class="mdi mdi-plus me-1"></i><?= e($isAr ? 'إنشاء فعالية' : __('evenements.create')) ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($events as $e): ?>
                <?php
                    $nbPhotos    = (int) ($e['nb_photos'] ?? 0);
                    $nbPending   = (int) ($e['nb_pending'] ?? 0);
                    $nbRejected  = (int) ($e['nb_rejected'] ?? 0);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm wh-card-hover">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <span class="wh-badge badge-<?= $e['album_id'] !== null ? 'blue' : 'gray' ?>">
                                    <?php if ($e['album_id'] !== null && $e['album_statut'] === 'publie'): ?>
                                        <i class="mdi mdi-check-circle me-1"></i><?= e($isAr ? 'منشور' : 'Album publié') ?>
                                    <?php elseif ($e['album_id'] !== null): ?>
                                        <i class="mdi mdi-pencil-outline me-1"></i><?= e($isAr ? 'قيد التحرير' : 'Album en cours') ?>
                                    <?php else: ?>
                                        <i class="mdi mdi-image-off-outline me-1"></i><?= e($isAr ? 'لا يوجد ألبوم' : 'Aucun album') ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <h5 class="wh-card-title"><?= e($e['adresse'] ?: 'Événement #' . (int) $e['id']) ?></h5>
                            <?php if ($e['date_evenement']): ?>
                                <p class="text-muted small mb-1">
                                    <i class="mdi mdi-calendar me-1"></i><?= e(date('d/m/Y', strtotime((string) $e['date_evenement']))) ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-2 my-2">
                                <span class="badge bg-light text-dark border"><?= $nbPhotos ?> <?= e($isAr ? 'صورة' : 'photos') ?></span>
                                <?php if ($nbPending > 0): ?>
                                    <span class="badge bg-warning text-dark"><?= $nbPending ?> <?= e($isAr ? 'قيد الانتظار' : 'en attente') ?></span>
                                <?php endif; ?>
                                <?php if ($nbRejected > 0): ?>
                                    <span class="badge bg-danger"><?= $nbRejected ?> <?= e($isAr ? 'مرفوضة' : 'rejetées') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto pt-3">
                                <a href="<?= url('association/evenements/' . (int) $e['id'] . '/photos') ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="mdi mdi-camera me-1"></i><?= e($isAr ? 'عرض الصور' : 'Gérer les photos') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
