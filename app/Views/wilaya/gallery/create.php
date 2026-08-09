<?php
/**
 * Galerie photos — Formulaire d'ajout de photos (upload multiple).
 *
 * @var array $event
 * @var array|null $album
 */
use App\Helpers\I18n;

$title = __('gallery.add_photos') . ' — ' . ($event['adresse'] ?? '');
$page  = 'wilaya.gallery.create';
$dir   = I18n::direction();
$maxSize = (int) config('security.upload_max', 5242880);
$maxMb = round($maxSize / 1048576, 1);
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title">
                <i class="mdi mdi-plus me-2"></i><?= e(__('gallery.add_photos')) ?>
            </h1>
            <p class="wh-page-sub">
                <?= e($event['adresse'] ?? '') ?>
                <?php if (! empty($event['date_evenement'])): ?>
                    · <?= e($event['date_evenement']) ?>
                <?php endif; ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>">
            <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
        </a>
    </div>

    <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" enctype="multipart/form-data" id="galleryForm" novalidate>
        <?= csrf_field() ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-camera-burst me-2"></i><?= e(__('gallery.select_photos')) ?></span>
            </div>
            <div class="card-body">

                <div class="wh-upload-zone" id="dropZone">
                    <div class="wh-upload-zone-inner">
                        <i class="mdi mdi-cloud-upload-outline" style="font-size:3rem;color:var(--wh-primary)"></i>
                        <h5 class="mt-3 mb-2"><?= e(__('gallery.drag_drop')) ?></h5>
                        <p class="text-muted mb-3"><?= e(__('gallery.or_click')) ?></p>
                        <input type="file" name="photos[]" id="fileInput" multiple
                               accept=".jpg,.jpeg,.png,.webp" class="d-none">
                        <button type="button" class="btn btn-outline-primary" id="browseBtn">
                            <i class="mdi mdi-folder-open me-1"></i><?= e(__('gallery.browse')) ?>
                        </button>
                    </div>
                    <div class="wh-upload-info mt-3">
                        <small class="text-muted">
                            <?= e(__('gallery.formats')) ?> · <?= e(__('gallery.max_size')) ?>: <?= $maxMb ?> Mo
                        </small>
                    </div>
                </div>

                <div id="previewContainer" class="row g-3 mt-3" style="display:none"></div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="btn btn-outline-secondary">
                <?= e(__('common.cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                <i class="mdi mdi-upload me-1"></i><?= e(__('gallery.upload')) ?>
            </button>
        </div>
    </form>
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
    border-color: var(--wh-primary, #2563eb);
    background: rgba(37, 99, 235, .04);
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

    browseBtn.addEventListener('click', () => fileInput.click());
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
        renderPreviews();
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
