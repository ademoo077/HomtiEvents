<?php
/** @var array<int, array<string,mixed>> $documents
 *  @var array<string,mixed> $evenement
 */
use App\Helpers\Database;
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
$id   = (int) ($evenement['id'] ?? 0);

$fmtSize = static function (int $bytes): string {
    if ($bytes < 1024) {
        return $bytes . ' o';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' Ko';
    }
    return round($bytes / 1048576, 1) . ' Mo';
};

$icon = static function (?string $mime): string {
    if ($mime === null) {
        return 'mdi-file-outline';
    }
    return match (true) {
        str_contains($mime, 'pdf')                     => 'mdi-file-pdf-box',
        str_contains($mime, 'image/')                  => 'mdi-file-image',
        str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') => 'mdi-file-excel-box',
        str_contains($mime, 'msword') || str_contains($mime, 'wordprocessingml') => 'mdi-file-word-box',
        str_contains($mime, 'zip') || str_contains($mime, 'compressed') => 'mdi-folder-zip',
        default                                        => 'mdi-file-document-outline',
    };
};
?>
<div id="event-documents" class="wh-dash-card p-3">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="mdi mdi-paperclip text-primary"></i><?= e(__('documents.title')) ?>
            <span class="badge bg-primary-subtle text-primary"><?= count($documents) ?></span>
        </h2>
        <?php if (can('evenement.edit')): ?>
        <form method="post" action="<?= url('wilaya/evenements/' . $id . '/documents') ?>" enctype="multipart/form-data" class="d-inline-flex gap-1 align-items-center">
            <?= csrf_field() ?>
            <label class="btn btn-sm btn-outline-primary btn-icon mb-0" style="cursor:pointer">
                <i class="mdi mdi-upload me-1"></i><?= e(__('documents.upload')) ?>
                <input type="file" name="documents[]" multiple hidden
                       accept=".pdf,.png,.jpg,.jpeg,.webp,.xls,.xlsx,.doc,.docx,.zip" onchange="this.form.submit()">
            </label>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($documents === []): ?>
        <div class="wh-empty-state py-4">
            <i class="mdi mdi-paperclip-off" style="font-size:2.4rem;opacity:.4"></i>
            <p class="mb-0 mt-2" style="font-size:.85rem"><?= e(__('documents.empty')) ?></p>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($documents as $doc): ?>
                <div class="list-group-item px-0 d-flex align-items-center gap-3">
                    <i class="mdi <?= e($icon($doc['type_mime'] ?? null)) ?> fs-3 text-primary" style="opacity:.8"></i>
                    <div class="flex-grow-1 min-w-0">
                        <a href="<?= url('wilaya/evenements/documents/' . (int) $doc['id'] . '/download') ?>"
                           class="text-decoration-none fw-semibold text-truncate d-block" style="color:var(--wh-text)"
                           title="<?= e($doc['nom']) ?>">
                            <?= e($doc['nom']) ?>
                        </a>
                        <div class="small" style="color:var(--wh-text-muted);font-size:.72rem">
                            <?php if (! empty($doc['taille'])): ?><?= $fmtSize((int) $doc['taille']) ?> · <?php endif; ?>
                            <?= e(date('d/m/Y H:i', strtotime((string) $doc['created_at']))) ?>
                            <?php if (! empty($doc['user_prenom']) || ! empty($doc['user_nom'])): ?>
                                · <?= e(__('documents.by')) ?> <?= e(trim(($doc['user_prenom'] ?? '') . ' ' . ($doc['user_nom'] ?? ''))) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <?php if (can('evenement.edit')): ?>
                        <form method="post" action="<?= url('wilaya/evenements/documents/' . (int) $doc['id'] . '/delete') ?>"
                              class="d-inline" data-confirm="<?= e(__('documents.deleted')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm btn-icon text-danger" title="<?= e(__('common.delete')) ?>">
                                <i class="mdi mdi-delete-outline"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
