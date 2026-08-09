<?php
/** @var array $settings @var array $sections @var array $faq @var array $testimonials @var array $partners @var array $errors */
use App\Helpers\I18n;

$title = __('landing.admin.title');
$page  = 'admin.landing.index';
$dir   = I18n::direction();

$groupeLabel = static function (string $g) use ($dir): string {
    return match ($g) {
        'hero'         => $dir === 'rtl' ? 'الواجهة' : 'Héro',
        'stats'        => $dir === 'rtl' ? 'الإحصائيات' : 'Statistiques',
        'apropos'      => $dir === 'rtl' ? 'من نحن' : 'À propos',
        'fonctionnement' => $dir === 'rtl' ? 'طريقة العمل' : 'Fonctionnement',
        'contact'      => $dir === 'rtl' ? 'اتصال' : 'Contact',
        'footer'       => $dir === 'rtl' ? 'التذييل' : 'Footer',
        'general'      => $dir === 'rtl' ? 'عام' : 'Général',
        default        => ucfirst($g),
    };
};

$groupes = ['hero', 'stats', 'apropos', 'fonctionnement', 'contact', 'footer', 'general'];
$cleShort = static function (string $cle): string {
    return str_replace(['_fr', '_ar'], '', $cle);
};
$isTextarea = static function (string $cle): bool {
    return str_contains($cle, 'description') || str_contains($cle, 'texte_') || $cle === 'fonctionnement_etapes';
};
$sectionsVisible = $settings['sections_order'] ?? null;
$sectionsOrder = is_string($sectionsVisible) ? json_decode($sectionsVisible, true) : $sectionsVisible;
if (! is_array($sectionsOrder)) {
    $sectionsOrder = $sections;
}
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('landing.admin.title')) ?></h1>
            <p class="wh-page-sub"><?= e(__('landing.admin.subtitle')) ?></p>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="#contenu" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-text-box-outline me-1"></i><?= e(__('common.content')) ?></a>
        <a href="#faq" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-help-circle-outline me-1"></i>FAQ</a>
        <a href="#temoignages" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-format-quote-open me-1"></i><?= e(__('landing.admin.testimonials')) ?></a>
        <a href="#partenaires" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-handshake me-1"></i><?= e(__('landing.admin.partners')) ?></a>
        <a href="<?= url('admin/landing/gallery') ?>" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-image-multiple me-1"></i><?= e(__('landing.admin.gallery')) ?></a>
        <a href="<?= url('admin/landing/before-after') ?>" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-compare-horizontal me-1"></i><?= e(__('landing.admin.before_after')) ?></a>
    </div>

    <form method="post" action="<?= url('admin/landing/settings') ?>" id="contenu">
        <?= csrf_field() ?>
        <?php foreach ($groupes as $groupe): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header"><span><i class="mdi mdi-tune-vertical me-2"></i><?= e($groupeLabel($groupe)) ?></span></div>
                <div class="card-body">
                    <?php $found = false; ?>
                    <?php foreach ($settings as $cle => $valeur): ?>
                        <?php if (! str_starts_with($cle, $groupe . '_')) continue; ?>
                        <?php $found = true; ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="<?= e($cle) ?>"><?= e($cleShort($cle)) ?> <span class="wh-text-muted">(<?= e($cle) ?>)</span></label>
                            <?php if ($cle === 'fonctionnement_etapes'): ?>
                                <textarea class="form-control font-monospace" id="<?= e($cle) ?>" name="cle[]" hidden><?= e($cle) ?></textarea>
                                <textarea class="form-control font-monospace" id="valeur-<?= e($cle) ?>" name="valeur[]" rows="6"><?= e(is_array($valeur) ? json_encode($valeur, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) $valeur) ?></textarea>
                                <div class="wh-form-hint">JSON — liste d'étapes {titre_fr, titre_ar, description_fr, description_ar}</div>
                            <?php elseif ($isTextarea($cle)): ?>
                                <textarea class="form-control" id="valeur-<?= e($cle) ?>" name="valeur[]" rows="4"><?= e(is_array($valeur) ? '' : (string) $valeur) ?></textarea>
                                <input type="hidden" name="cle[]" value="<?= e($cle) ?>">
                            <?php else: ?>
                                <input type="text" class="form-control" id="valeur-<?= e($cle) ?>" name="valeur[]" value="<?= e(is_array($valeur) ? '' : (string) $valeur) ?>">
                                <input type="hidden" name="cle[]" value="<?= e($cle) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (! $found): ?>
                        <div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header"><span><i class="mdi mdi-sort-ascending me-2"></i><?= e(__('landing.admin.sections')) ?></span></div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($sectionsOrder as $section): ?>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="visibles[]" id="vis-<?= e($section) ?>"
                                       value="<?= e($section) ?>" <?= ! empty($settings['section_' . $section . '_visible']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="vis-<?= e($section) ?>"><?= e($section) ?></label>
                                <input type="hidden" name="all_sections[]" value="<?= e($section) ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="wh-form-hint mt-2"><?= e(__('landing.admin.sections_hint')) ?></div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-6" id="faq">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-help-circle-outline me-2"></i>FAQ</span>
                    <span class="wh-badge badge-blue"><?= count($faq) ?></span>
                </div>
                <div class="card-body">
                    <?php foreach ($faq as $f): ?>
                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom py-2">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate"><?= e($f['question_fr']) ?></div>
                                <small class="wh-text-muted text-truncate d-block"><?= e(mb_strimwidth((string) $f['reponse_fr'], 0, 70, '…')) ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <?php if (! (int) $f['actif']): ?><span class="wh-badge badge-gray">off</span><?php endif; ?>
                                <form method="post" action="<?= url('admin/landing/faq/' . (int) $f['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($faq === []): ?><div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div><?php endif; ?>

                    <hr>
                    <form method="post" action="<?= url('admin/landing/faq') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="question_fr">Question (FR) *</label>
                                <input type="text" class="form-control" id="question_fr" name="question_fr" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="question_ar">Question (AR)</label>
                                <input type="text" class="form-control" id="question_ar" name="question_ar">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="reponse_fr">Réponse (FR) *</label>
                                <textarea class="form-control" id="reponse_fr" name="reponse_fr" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="reponse_ar">Réponse (AR)</label>
                                <textarea class="form-control" id="reponse_ar" name="reponse_ar" rows="3"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="ordre"><?= e(__('common.details')) ?></label>
                                <input type="number" class="form-control" id="ordre" name="ordre" value="0">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actif" id="faq-actif" checked>
                                    <label class="form-check-label" for="faq-actif"><?= e(__('landing.admin.active')) ?></label>
                                </div>
                            </div>
                            <div class="col-md-4 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6" id="temoignages">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-format-quote-open me-2"></i><?= e(__('landing.admin.testimonials')) ?></span>
                    <span class="wh-badge badge-violet"><?= count($testimonials) ?></span>
                </div>
                <div class="card-body">
                    <?php foreach ($testimonials as $t): ?>
                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom py-2">
                            <div class="min-w-0">
                                <div class="fw-semibold"><?= e($t['auteur']) ?><?= $t['role'] ? ' <span class="wh-text-muted">(' . e($t['role']) . ')</span>' : '' ?></div>
                                <small class="wh-text-muted text-truncate d-block"><?= e(mb_strimwidth((string) $t['texte_fr'], 0, 70, '…')) ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <?php if (! (int) $t['actif']): ?><span class="wh-badge badge-gray">off</span><?php endif; ?>
                                <form method="post" action="<?= url('admin/landing/temoignages/' . (int) $t['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($testimonials === []): ?><div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div><?php endif; ?>

                    <hr>
                    <form method="post" action="<?= url('admin/landing/temoignages') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="auteur"><?= e(__('landing.admin.author')) ?> *</label>
                                <input type="text" class="form-control" id="auteur" name="auteur" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="role"><?= e(__('landing.admin.author_role')) ?></label>
                                <input type="text" class="form-control" id="role" name="role" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="texte_fr"><?= e(__('landing.admin.texte')) ?> (FR) *</label>
                                <textarea class="form-control" id="texte_fr" name="texte_fr" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="texte_ar"><?= e(__('landing.admin.texte')) ?> (AR)</label>
                                <textarea class="form-control" id="texte_ar" name="texte_ar" rows="3"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="note">Note</label>
                                <input type="number" class="form-control" id="note" name="note" min="1" max="5" value="5">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actif" id="t-actif" checked>
                                    <label class="form-check-label" for="t-actif"><?= e(__('landing.admin.active')) ?></label>
                                </div>
                            </div>
                            <div class="col-md-4 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6" id="partenaires">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-handshake me-2"></i><?= e(__('landing.admin.partners')) ?></span>
                    <span class="wh-badge badge-cyan"><?= count($partners) ?></span>
                </div>
                <div class="card-body">
                    <?php foreach ($partners as $p): ?>
                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom py-2">
                            <div class="min-w-0">
                                <div class="fw-semibold"><?= e($p['nom']) ?></div>
                                <?php if ($p['url']): ?><small class="wh-text-muted text-truncate d-block"><?= e($p['url']) ?></small><?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <?php if (! (int) $p['actif']): ?><span class="wh-badge badge-gray">off</span><?php endif; ?>
                                <form method="post" action="<?= url('admin/landing/partenaires/' . (int) $p['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($partners === []): ?><div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div><?php endif; ?>

                    <hr>
                    <form method="post" action="<?= url('admin/landing/partenaires') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="nom"><?= e(__('common.nom')) ?> *</label>
                                <input type="text" class="form-control" id="nom" name="nom" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="url">URL</label>
                                <input type="url" class="form-control" id="url" name="url" maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="ordre"><?= e(__('common.details')) ?></label>
                                <input type="number" class="form-control" id="ordre" name="ordre" value="0">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actif" id="p-actif" checked>
                                    <label class="form-check-label" for="p-actif"><?= e(__('landing.admin.active')) ?></label>
                                </div>
                            </div>
                            <div class="col-md-4 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
