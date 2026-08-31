<?php
/**
 * Galerie associative — Photos d'un événement + soumission.
 *
 * @var array $event
 * @var array|null $album
 * @var array $photos
 */
use App\Helpers\I18n;

$title = __('common.gallery') . ' — ' . ($event['adresse'] ?? '');
$page  = 'association.gallery';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$maxUpload = (int) config('security.upload_max', 5242880);
?>
<div class="wh-page">
    <div style="background:linear-gradient(135deg,#0B5ED7 0%,#198754 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-camera"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('common.gallery')) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= e($event['adresse'] ?? '') ?> <?php if (! empty($event['date_evenement'])): ?> · <?= e($event['date_evenement']) ?><?php endif; ?></p>
                </div>
            </div>
            <a class="btn btn-light btn-sm" href="<?= url('association/gallery') ?>">
                <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
            </a>
        </div>
    </div>

    <?php if (! empty($event['motif_refus'])): ?>
        <div class="alert alert-warning">
            <i class="mdi mdi-alert-outline me-1"></i><?= e($event['motif_refus']) ?>
        </div>
    <?php endif; ?>

    <!-- ═══ FORMULAIRE DE SOUMISSION ═══ -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius);overflow:hidden;">
        <div style="padding:.65rem 1.25rem;background:#ede9fe;border-bottom:1px solid #ddd6fe;display:flex;align-items:center;gap:.5rem;">
            <span style="width:28px;height:28px;border-radius:7px;background:rgba(124,58,237,.15);display:grid;place-items:center;color:#7c3aed;font-size:.85rem;"><i class="mdi mdi-upload"></i></span>
            <span class="fw-bold" style="font-size:.88rem;"><?= e($isAr ? 'إرسال صور' : 'Soumettre des photos') ?></span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <?= e($isAr
                    ? 'ستُرسل الصور إلى الولاية للتحقق قبل النشر.'
                    : 'Les photos soumises seront validées par la Wilaya avant publication.') ?>
            </p>
            <form method="post" action="<?= url('association/evenements/' . (int) $event['id'] . '/photos') ?>" enctype="multipart/form-data" id="assocGalleryForm" novalidate>
                <?= csrf_field() ?>

                <div class="wh-upload-zone" id="dropZone">
                    <div class="wh-upload-zone-inner">
                        <i class="mdi mdi-cloud-upload-outline" style="font-size:3rem;color:var(--wh-primary)"></i>
                        <h5 class="mt-3 mb-2"><?= e($isAr ? 'اسحب الصور هنا' : 'Glissez-déposez vos photos ici') ?></h5>
                        <p class="text-muted mb-3"><?= e($isAr ? 'أو انقر للاختيار' : 'ou cliquez pour parcourir') ?></p>
                        <input type="file" name="photos[]" id="fileInput" multiple
                               accept=".jpg,.jpeg,.png,.webp" class="d-none" required>
                        <button type="button" class="btn btn-outline-primary" id="browseBtn">
                            <i class="mdi mdi-folder-open me-1"></i><?= e($isAr ? 'اختيار' : 'Parcourir') ?>
                        </button>
                    </div>
                    <div class="wh-upload-info mt-3">
                        <small class="text-muted">JPG, PNG, WebP · max <?= number_format($maxUpload / 1048576, 1) ?> Mo / image</small>
                    </div>
                </div>

                <div id="previewContainer" class="row g-3 mt-3" style="display:none"></div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="btn btn-success fw-bold" id="submitBtn" disabled>
                        <i class="mdi mdi-upload me-1"></i><?= e($isAr ? 'إرسال الصور' : 'Soumettre') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ PHOTOS ═══ -->
    <?php if ($photos === []): ?>
        <div class="futur-empty">
            <i class="mdi mdi-image-multiple"></i>
            <p class="futur-empty-title"><?= e($isAr ? 'لا توجد صور بعد' : 'Aucune photo pour le moment.') ?></p>
            <p class="futur-empty-text"><?= $isAr ? 'Ajoutez la première photo à cet album.' : 'Ajoutez la première photo à cet album.' ?></p>
            <a href="<?= url('association/gallery/' . (int) $album['id'] . '/photos/create') ?>" class="btn btn-primary futur-empty-action">
                <i class="mdi mdi-plus me-1"></i><?= $isAr ? 'إضافة صورة' : 'Ajouter une photo' ?>
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($photos as $photo): ?>
                <?php
                    $statut = (string) ($photo['status'] ?? 'active');
                    $badge  = match ($statut) {
                        'pending'  => 'bg-warning text-dark',
                        'rejected' => 'bg-danger',
                        'active'   => 'bg-success',
                        default    => 'bg-secondary',
                    };
                    $label  = match ($statut) {
                        'pending'  => $isAr ? 'قيد الانتظار' : 'En attente de validation',
                        'rejected' => $isAr ? 'مرفوضة' : 'Rejetée',
                        'active'   => $isAr ? 'منشورة' : 'Publiée',
                        default    => $statut,
                    };
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm overflow-hidden h-100">
                        <?php if (! empty($photo['image'])): ?>
                            <img src="<?= e(photo_src($photo)) ?>" alt="<?= e($photo['legende'] ?? '') ?>" class="w-100" style="aspect-ratio: 1/1; object-fit: cover;" loading="lazy">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="aspect-ratio: 1/1;">
                                <i class="mdi mdi-image-off" style="font-size: 1.8rem"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <span class="badge <?= $badge ?>"><?= e($label) ?></span>
                            <?php if ($statut === 'rejected' && ! empty($photo['motif_rejet'])): ?>
                                <p class="small text-muted mt-1 mb-1"><?= e($photo['motif_rejet']) ?></p>
                            <?php endif; ?>

                            <form method="post" action="<?= url('association/photos/' . (int) $photo['id'] . '/update') ?>" class="mt-1 wh-legend-form">
                                <?= csrf_field() ?>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="legende" class="form-control form-control-sm"
                                           value="<?= e((string) ($photo['legende'] ?? '')) ?>"
                                           placeholder="<?= e($isAr ? 'تعليق' : 'Légende') ?>" maxlength="255">
                                    <button type="submit" class="btn btn-outline-secondary wh-legend-save" title="<?= e($isAr ? 'حفظ' : 'Enregistrer') ?>">
                                        <i class="mdi mdi-content-save"></i>
                                    </button>
                                </div>
                            </form>

                            <?php if (in_array($statut, ['pending', 'rejected'], true)): ?>
                                <form method="post" action="<?= url('association/photos/' . (int) $photo['id'] . '/delete') ?>" class="d-inline"
                                      data-confirm="<?= e(__('gallery.delete_photo_confirm')) ?>"
                                      onsubmit="return confirm('<?= e($isAr ? 'حذف هذه الصورة؟' : 'Supprimer cette photo ?') ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 mt-1">
                                        <i class="mdi mdi-delete me-1"></i><?= e($isAr ? 'حذف' : 'Supprimer') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.wh-upload-zone {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: border-color .2s, background .2s;
    cursor: pointer;
}
.wh-upload-zone:hover,
.wh-upload-zone.dragover {
    border-color: var(--wh-primary, #1A4D3E);
    background: rgba(26, 77, 62, .04);
}
.wh-upload-zone.dragover {
    transform: scale(1.01);
}
.wh-upload-preview {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    background: #f1f5f9;
}
.wh-upload-preview img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}
.wh-upload-preview .wh-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0,0,0,.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
    font-size: 14px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const browseBtn = document.getElementById('browseBtn');
    const previewContainer = document.getElementById('previewContainer');
    const submitBtn = document.getElementById('submitBtn');
    let files = [];

    if (!dropZone || !fileInput || !browseBtn || !previewContainer || !submitBtn) { return; }

    browseBtn.addEventListener('click', (e) => { e.stopPropagation(); fileInput.click(); });
    dropZone.addEventListener('click', (e) => {
        if (e.target === dropZone || e.target.closest('.wh-upload-zone-inner')) {
            fileInput.click();
        }
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', () => addFiles(fileInput.files));

    function addFiles(newFiles) {
        for (let i = 0; i < newFiles.length; i++) {
            const f = newFiles[i];
            if (!f.type.match(/^image\/(jpeg|png|webp)$/)) continue;
            files.push(f);
        }
        fileInput.files = filesToFileList(files);
        renderPreviews();
    }

    function filesToFileList(list) {
        const dt = new DataTransfer();
        list.forEach(f => dt.items.add(f));
        return dt.files;
    }

    function renderPreviews() {
        previewContainer.innerHTML = '';
        if (files.length === 0) {
            previewContainer.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }
        previewContainer.style.display = 'flex';
        submitBtn.disabled = false;

        files.forEach((f, idx) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3';

            const card = document.createElement('div');
            card.className = 'wh-upload-preview';

            const img = document.createElement('img');
            const reader = new FileReader();
            reader.onload = (e) => { img.src = e.target.result; };
            reader.readAsDataURL(f);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'wh-remove';
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                files.splice(idx, 1);
                fileInput.files = filesToFileList(files);
                renderPreviews();
            });

            const name = document.createElement('small');
            name.className = 'd-block p-2 text-truncate text-muted';
            name.textContent = f.name + ' (' + (f.size / 1048576).toFixed(1) + ' Mo)';

            card.appendChild(img);
            card.appendChild(removeBtn);
            card.appendChild(name);
            col.appendChild(card);
            previewContainer.appendChild(col);
        });
    }
});
</script>
