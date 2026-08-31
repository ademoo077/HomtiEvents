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
    <div style="background:linear-gradient(135deg,#198754 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-image-multiple"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('landing.admin.gallery')) ?> — <?= e($title) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= e(__('common.informations')) ?></p>
                </div>
            </div>
            <a href="<?= url('admin/landing/gallery') ?>" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i><?= e(__('common.back')) ?></a>
        </div>
    </div>

    <form method="post" action="<?= $editing ? url('admin/landing/gallery/' . (int) $item['id'] . '/update') : url('admin/landing/gallery') ?>"
          enctype="multipart/form-data" id="galleryItemForm" novalidate>
        <?= csrf_field() ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header" style="background:#ede9fe;border-bottom:1px solid #ddd6fe;">
                        <span class="d-flex align-items-center gap-2 fw-bold">
                            <span style="width:32px;height:32px;border-radius:8px;background:rgba(124,58,237,.15);display:grid;place-items:center;color:#7c3aed;"><i class="mdi mdi-image-multiple"></i></span>
                            <?= e(__('landing.admin.gallery')) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="titre_fr"><?= e(__('landing.admin.titre')) ?> (FR) *</label>
                                <input type="text" class="form-control" id="titre_fr" name="titre_fr" value="<?= e($val('titre_fr')) ?>" required maxlength="255" style="border-radius:.55rem;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="titre_ar"><?= e(__('landing.admin.titre')) ?> (AR)</label>
                                <input type="text" class="form-control" id="titre_ar" name="titre_ar" value="<?= e($val('titre_ar')) ?>" maxlength="255" style="border-radius:.55rem;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="image">URL / chemin image</label>
                                <input type="text" class="form-control" id="image" name="image" value="<?= e($currentImage) ?>"
                                       maxlength="255" placeholder="/uploads/landing/photo.jpg ou https://…" style="border-radius:.55rem;">
                                <div class="wh-form-hint"><?= e(__('landing.admin.image_or_url')) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="lien">Lien</label>
                                <input type="url" class="form-control" id="lien" name="lien" value="<?= e($val('lien')) ?>" maxlength="255" style="border-radius:.55rem;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="type">Type</label>
                                <select class="form-select" id="type" name="type" style="border-radius:.55rem;">
                                    <?php foreach (['album', 'evenement', 'actualite'] as $t): ?>
                                        <option value="<?= e($t) ?>" <?= $val('type') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sort_order"><?= e(__('common.details')) ?></label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= (int) $val('sort_order') ?>" style="border-radius:.55rem;">
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
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;">
                        <span class="d-flex align-items-center gap-2 fw-bold">
                            <span style="width:32px;height:32px;border-radius:8px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);"><i class="mdi mdi-image"></i></span>
                            <?= e(__('gallery.preview')) ?>
                        </span>
                    </div>
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
                            <button type="button" class="btn btn-outline-primary btn-sm w-100" id="imgBrowseBtn" style="border-radius:.55rem;">
                                <i class="mdi mdi-folder-open me-1"></i><?= e(__('gallery.browse')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid var(--wh-border);">
            <a href="<?= url('admin/landing/gallery') ?>" class="btn btn-outline-secondary" style="border-radius:.55rem;"><?= e(__('common.cancel')) ?></a>
            <button type="submit" class="btn btn-primary" style="border-radius:.55rem;"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
</div>

<style>
.wh-img-zone {
    border: 2px dashed #c4b5fd;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: border-color .2s, background .2s;
    cursor: pointer;
    background: #f8fafc;
}
.wh-img-zone:hover,
.wh-img-zone.dragover {
    border-color: #7c3aed;
    background: rgba(124,58,237,.05);
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
.wh-img-placeholder i { font-size: 2.4rem; color: #7c3aed; }
.wh-img-placeholder small { font-size: .75rem; }
.wh-img-zone:focus { outline: 2px solid #7c3aed; outline-offset: 2px; }
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
