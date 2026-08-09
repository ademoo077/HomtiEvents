<?php
/** @var array $item */
use App\Helpers\I18n;

$editing = ! empty($item['id']);
$title = $editing ? __('common.edit') : __('common.create');
$page  = $editing ? 'admin.landing.gallery.edit' : 'admin.landing.gallery.create';
$dir   = I18n::direction();

$val = static function (string $k) use ($item): string {
    return (string) (old($k) ?? ($item[$k] ?? ''));
};

$currentImage = $val('image');
$maxSize = (int) config('security.upload_max', 5242880);
$maxMb = round($maxSize / 1048576, 1);
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('landing.admin.gallery')) ?> — <?= e($title) ?></h1>
            <p class="wh-page-sub"><?= e(__('common.informations')) ?></p>
        </div>
        <a href="<?= url('admin/landing/gallery') ?>" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
    </div>

    <form method="post" action="<?= $editing ? url('admin/landing/gallery/' . (int) $item['id'] . '/update') : url('admin/landing/gallery') ?>"
          enctype="multipart/form-data" id="galleryItemForm" novalidate>
        <?= csrf_field() ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header"><span><i class="mdi mdi-image-multiple me-2"></i><?= e(__('landing.admin.gallery')) ?></span></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="titre_fr"><?= e(__('landing.admin.titre')) ?> (FR) *</label>
                                <input type="text" class="form-control" id="titre_fr" name="titre_fr" value="<?= e($val('titre_fr')) ?>" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="titre_ar"><?= e(__('landing.admin.titre')) ?> (AR)</label>
                                <input type="text" class="form-control" id="titre_ar" name="titre_ar" value="<?= e($val('titre_ar')) ?>" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="image">URL / chemin image</label>
                                <input type="text" class="form-control" id="image" name="image" value="<?= e($currentImage) ?>"
                                       maxlength="255" placeholder="/uploads/landing/photo.jpg ou https://…">
                                <div class="wh-form-hint"><?= e(__('landing.admin.image_or_url')) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="lien">Lien</label>
                                <input type="url" class="form-control" id="lien" name="lien" value="<?= e($val('lien')) ?>" maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="type">Type</label>
                                <select class="form-select" id="type" name="type">
                                    <?php foreach (['album', 'evenement', 'actualite'] as $t): ?>
                                        <option value="<?= e($t) ?>" <?= $val('type') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sort_order"><?= e(__('common.details')) ?></label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= (int) $val('sort_order') ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actif" id="actif" <?= $editing && ! (int) $item['actif'] ? '' : 'checked' ?>>
                                    <label class="form-check-label" for="actif"><?= e(__('landing.admin.active')) ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('admin/landing/gallery') ?>" class="btn btn-outline-secondary"><?= e(__('common.cancel')) ?></a>
                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header"><span><i class="mdi mdi-image me-2"></i><?= e(__('gallery.preview')) ?></span></div>
                    <div class="card-body">
                        <div class="wh-img-zone" id="imgDropZone" tabindex="0" role="button" aria-label="<?= e(__('landing.admin.upload_image')) ?>">
                            <input type="file" name="image_file" id="imgFile" accept=".jpg,.jpeg,.png,.webp" class="d-none">
                            <img id="imgPreview" class="wh-img-preview" src="<?= e($currentImage) ?>" alt="<?= e($val('titre_fr')) ?>" <?= $currentImage === '' ? 'hidden' : '' ?>>
                            <div id="imgPlaceholder" class="wh-img-placeholder" <?= $currentImage === '' ? '' : 'hidden' ?>>
                                <i class="mdi mdi-cloud-upload-outline"></i>
                                <span><?= e(__('landing.admin.upload_image')) ?></span>
                                <small><?= e(__('landing.admin.image_upload_hint')) ?></small>
                            </div>
                        </div>
                        <div class="wh-img-info mt-2 text-center">
                            <small class="text-muted"><?= e(__('gallery.formats')) ?> · <?= e(__('gallery.max_size')) ?>: <?= $maxMb ?> Mo</small>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100" id="imgBrowseBtn">
                                <i class="mdi mdi-folder-open me-1"></i><?= e(__('gallery.browse')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.wh-img-zone {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: border-color .2s, background .2s;
    cursor: pointer;
    background: #f8fafc;
}
.wh-img-zone:hover,
.wh-img-zone.dragover {
    border-color: var(--wh-primary, #2563eb);
    background: rgba(37, 99, 235, .05);
}
.wh-img-preview {
    width: 100%;
    max-height: 260px;
    object-fit: contain;
    border-radius: 8px;
}
.wh-img-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
    padding: 2rem 0;
    color: #64748b;
}
.wh-img-placeholder i { font-size: 2.4rem; color: var(--wh-primary, #2563eb); }
.wh-img-placeholder small { font-size: .75rem; }
.wh-img-zone:focus { outline: 2px solid var(--wh-primary, #2563eb); outline-offset: 2px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const zone = document.getElementById('imgDropZone');
    const fileInput = document.getElementById('imgFile');
    const browseBtn = document.getElementById('imgBrowseBtn');
    const preview = document.getElementById('imgPreview');
    const placeholder = document.getElementById('imgPlaceholder');
    const urlInput = document.getElementById('image');

    function setPreview(src) {
        preview.src = src;
        preview.hidden = false;
        placeholder.hidden = true;
    }

    browseBtn.addEventListener('click', (e) => { e.preventDefault(); fileInput.click(); });
    zone.addEventListener('click', (e) => { if (e.target !== urlInput) fileInput.click(); });
    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) fileInput.files = e.dataTransfer.files;
        if (fileInput.files.length > 0) showFile(fileInput.files[0]);
    });

    fileInput.addEventListener('change', () => { if (fileInput.files.length > 0) showFile(fileInput.files[0]); });

    function showFile(file) {
        if (!file.type.match(/^image\/(jpeg|png|webp)$/)) return;
        const reader = new FileReader();
        reader.onload = (ev) => setPreview(ev.target.result);
        reader.readAsDataURL(file);
    }

    urlInput.addEventListener('input', () => {
        const v = urlInput.value.trim();
        if (v !== '') setPreview(v);
    });
});
</script>
