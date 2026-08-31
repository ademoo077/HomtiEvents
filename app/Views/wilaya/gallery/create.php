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
    <!-- Gradient Hero -->
    <div class="mb-4" style="background:linear-gradient(135deg, #198754 0%, #0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden">
        <div style="position:absolute;top:-40%;right:-8%;width:320px;height:320px;background:rgba(255,255,255,.08);border-radius:50%"></div>
        <div style="position:absolute;bottom:-30%;left:5%;width:200px;height:200px;background:rgba(255,255,255,.05);border-radius:50%"></div>
        <div class="row align-items-center" style="position:relative;z-index:1">
            <div class="col-lg-7">
                <h1 class="mb-1" style="font-size:1.5rem;font-weight:800">
                    <i class="mdi mdi-camera-burst me-2"></i><?= e(__('gallery.add_photos')) ?>
                </h1>
                <p class="mb-0" style="opacity:.85;font-size:.9rem">
                    <?= e($event['adresse'] ?? '') ?>
                    <?php if (! empty($event['date_evenement'])): ?>
                        · <?= e($event['date_evenement']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <a class="btn btn-light btn-lg" href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>">
                    <i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?>
                </a>
            </div>
        </div>
    </div>

    <form method="post" action="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" enctype="multipart/form-data" id="galleryForm" novalidate>
        <?= csrf_field() ?>

        <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius)">
            <div class="card-header" style="background:var(--wh-purple-soft);border-bottom:1px solid rgba(102,16,242,.12)">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(102,16,242,.1);display:grid;place-items:center">
                        <i class="mdi mdi-camera-burst" style="color:var(--wh-purple);font-size:1rem"></i>
                    </div>
                    <span class="fw-bold"><?= e(__('gallery.select_photos')) ?></span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="wh-upload-zone" id="dropZone">
                    <div class="wh-upload-zone-inner">
                        <div style="width:70px;height:70px;margin:0 auto 1rem;background:var(--wh-purple-soft);border-radius:50%;display:grid;place-items:center">
                            <i class="mdi mdi-cloud-upload-outline" style="font-size:2rem;color:var(--wh-purple)"></i>
                        </div>
                        <h5 class="mt-2 mb-2 fw-bold"><?= e(__('gallery.drag_drop')) ?></h5>
                        <p class="text-muted mb-3" style="font-size:.9rem"><?= e(__('gallery.or_click')) ?></p>
                        <input type="file" name="photos[]" id="fileInput" multiple
                               accept=".jpg,.jpeg,.png,.webp" class="d-none">
                        <button type="button" class="btn btn-outline-primary" id="browseBtn" style="border-radius:.55rem">
                            <i class="mdi mdi-folder-open me-1"></i><?= e(__('gallery.browse')) ?>
                        </button>
                    </div>
                    <div class="wh-upload-info mt-3">
                        <small class="text-muted">
                            <i class="mdi mdi-information-outline me-1"></i><?= e(__('gallery.formats')) ?> · <?= e(__('gallery.max_size')) ?>: <?= $maxMb ?> Mo
                        </small>
                    </div>
                </div>

                <div id="previewContainer" class="row g-3 mt-3" style="display:none"></div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('wilaya/evenements/' . (int) $event['id'] . '/photos') ?>" class="btn btn-outline-secondary" style="border-radius:.55rem">
                <?= e(__('common.cancel')) ?>
            </a>
            <button type="submit" class="btn btn-primary fw-bold" id="submitBtn" disabled style="border-radius:.55rem">
                <i class="mdi mdi-upload me-1"></i><?= e(__('gallery.upload')) ?>
            </button>
        </div>
    </form>
</div>

<style>
.wh-upload-zone {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 2.5rem 2rem;
    text-align: center;
    transition: border-color .22s, background .22s, transform .22s;
    cursor: pointer;
    background: #fafbfc;
}
.wh-upload-zone:hover,
.wh-upload-zone.dragover {
    border-color: var(--wh-purple, #6610f2);
    background: rgba(102, 16, 242, .03);
}
.wh-upload-zone.dragover {
    transform: scale(1.01);
    border-color: var(--wh-purple, #6610f2);
    background: rgba(102, 16, 242, .06);
}
.wh-upload-preview {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: #f1f5f9;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
    transition:transform .18s,box-shadow .18s;
}
.wh-upload-preview:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.1)}
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
    background: rgba(220,53,69,.85);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
    font-size: 14px;
    transition:background .15s;
}
.wh-upload-preview .wh-remove:hover{background:rgba(220,53,69,1)}
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
