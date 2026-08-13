<?php
/** @var array $settings @var array $sections @var array $faq @var array $testimonials @var array $partners @var array $errors */
use App\Helpers\I18n;

$title = __('landing.admin.title');
$page  = 'admin.landing.index';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

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
$themeColorKeys = [
    'theme_primary'        => ['label' => 'Couleur primaire',    'default' => '#16a345'],
    'theme_primary_hover'  => ['label' => 'Primaire hover',      'default' => '#15803d'],
    'theme_secondary'      => ['label' => 'Couleur secondaire',  'default' => '#22c55e'],
    'theme_tertiary'       => ['label' => 'Couleur tertiaire',   'default' => '#0ea5e9'],
    'theme_accent_glow'    => ['label' => 'Glow accent',         'default' => '#22c55e'],
    'theme_navbar_bg'      => ['label' => 'Fond header',         'default' => 'rgba(255,255,255,0.65)'],
    'theme_navbar_bg_scrolled' => ['label' => 'Fond header scroll', 'default' => 'rgba(255,255,255,0.85)'],
    'theme_footer_bg'      => ['label' => 'Fond footer',         'default' => '#ffffff'],
    'theme_footer_text'    => ['label' => 'Texte footer',        'default' => '#475569'],
];
$themePresetsRaw = $settings['theme_presets'] ?? '[]';
$themePresets = is_string($themePresetsRaw) ? json_decode($themePresetsRaw, true) : $themePresetsRaw;
if (! is_array($themePresets)) {
    $themePresets = [];
}
$currentThemeName = (string) ($settings['theme_name'] ?? 'vert');
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
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="<?= url('admin/landing/preview') ?>" target="_blank" rel="noopener" title="<?= e(__('landing.admin.preview_hint')) ?>">
                <i class="mdi mdi-eye-outline me-1"></i><?= e(__('landing.admin.preview')) ?>
            </a>
        </div>
    </div>

    <!-- Bandeau KPI + recherche rapide -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" data-tab="contenu" class="wh-kpi wh-kpi-sm wh-tab-trigger">
                        <span class="wh-kpi-icon blue"><i class="mdi mdi-text-box-multiple-outline"></i></span>
                        <span class="wh-kpi-label small">CMS</span>
                    </button>
                    <button type="button" data-tab="faq" class="wh-kpi wh-kpi-sm wh-tab-trigger">
                        <span class="wh-kpi-icon violet"><i class="mdi mdi-help-circle-outline"></i></span>
                        <span class="wh-kpi-label small">FAQ (<?= count($faq) ?>)</span>
                    </button>
                    <button type="button" data-tab="temoignages" class="wh-kpi wh-kpi-sm wh-tab-trigger">
                        <span class="wh-kpi-icon green"><i class="mdi mdi-format-quote-open"></i></span>
                        <span class="wh-kpi-label small"><?= $isAr ? 'الشهادات' : 'Témoignages' ?> (<?= count($testimonials) ?>)</span>
                    </button>
                    <button type="button" data-tab="partenaires" class="wh-kpi wh-kpi-sm wh-tab-trigger">
                        <span class="wh-kpi-icon amber"><i class="mdi mdi-handshake"></i></span>
                        <span class="wh-kpi-label small"><?= $isAr ? 'الشركاء' : 'Partenaires' ?> (<?= count($partners) ?>)</span>
                    </button>
                    <a href="<?= url('admin/landing/gallery') ?>" class="wh-kpi wh-kpi-sm">
                        <span class="wh-kpi-icon red"><i class="mdi mdi-image-multiple"></i></span>
                        <span class="wh-kpi-label small"><?= $isAr ? 'المعرض' : 'Galerie' ?> + <?= e(__('landing.admin.before_after')) ?></span>
                    </a>
                </div>
                <div class="col-md-4">
                    <input type="search" id="settingsSearch" class="form-control" placeholder="<?= $isAr ? 'بحث في الإعدادات' : 'Filtrer les champs...' ?>">
                    <div class="form-text small"><?= $isAr ? 'يفلتح الإعدادات تلقائيًا' : 'Filtre les sections de contenu en temps réel.' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Onglets de navigation ═══ -->
    <div class="wh-tabs-bar mb-3 d-flex flex-wrap align-items-center gap-2">
        <ul class="nav nav-pills flex-wrap gap-1" id="landingTabs">
            <li class="nav-item"><button type="button" class="nav-link active" data-tab="theme"><i class="mdi mdi-palette-swatch-outline me-1"></i><?= e(__('landing.admin.theme')) ?></button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab="contenu"><i class="mdi mdi-text-box-multiple-outline me-1"></i><?= e(__('common.content')) ?></button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab="sections"><i class="mdi mdi-sort-ascending me-1"></i><?= e(__('landing.admin.sections')) ?></button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab="faq"><i class="mdi mdi-help-circle-outline me-1"></i>FAQ</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab="temoignages"><i class="mdi mdi-format-quote-open me-1"></i><?= e(__('landing.admin.testimonials')) ?></button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-tab="partenaires"><i class="mdi mdi-handshake me-1"></i><?= e(__('landing.admin.partners')) ?></button></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/landing/gallery') ?>"><i class="mdi mdi-image-multiple me-1"></i><?= e(__('landing.admin.gallery')) ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/landing/before-after') ?>"><i class="mdi mdi-compare-horizontal me-1"></i><?= e(__('landing.admin.before_after')) ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/landing/news') ?>"><i class="mdi mdi-newspaper me-1"></i><?= $isAr ? 'الأخبار والأحداث' : 'Actualités & événements' ?></a></li>
        </ul>
        <button type="button" class="btn btn-outline-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#livePreviewModal">
            <i class="mdi mdi-eye-outline me-1"></i><?= e(__('landing.admin.preview_live')) ?>
        </button>
    </div>

    <!-- ═══ Onglet : Thème couleur ═══ -->
    <div class="wh-tab-pane" id="pane-theme">
    <div class="card border-0 shadow-sm mb-4" id="theme">
        <div class="card-header">
            <span><i class="mdi mdi-palette-swatch-outline me-2"></i><?= e(__('landing.admin.theme')) ?></span>
        </div>
        <div class="card-body">
            <p class="wh-text-muted"><?= e(__('landing.admin.theme_hint')) ?></p>

            <!-- Preset rapide -->
            <form method="post" action="<?= url('admin/landing/theme') ?>" class="mb-4">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label" for="preset"><?= e(__('landing.admin.theme_preset')) ?></label>
                        <select class="form-select" id="preset" name="preset">
                            <option value="custom" <?= $currentThemeName === 'custom' ? 'selected' : '' ?>><?= e(__('landing.admin.theme_custom')) ?></option>
                            <?php foreach ($themePresets as $p): ?>
                                <option value="<?= e($p['name']) ?>" <?= $currentThemeName === ($p['name'] ?? '') ? 'selected' : '' ?>>
                                    <?= e($p['label'] ?? $p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-secondary">
                            <i class="mdi mdi-check me-1"></i><?= e(__('common.apply')) ?>
                        </button>
                    </div>
                    <div class="col-md-3 d-grid">
                        <a href="<?= url('admin/landing/preview') ?>" target="_blank" class="btn btn-outline">
                            <i class="mdi mdi-eye-outline me-1"></i><?= e(__('landing.admin.preview')) ?>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Personnalisation manuelle -->
            <form method="post" action="<?= url('admin/landing/theme') ?>" id="themeForm">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <?php foreach ($themeColorKeys as $cle => $cfg): ?>
                        <div class="col-md-4">
                            <label class="form-label" for="<?= e($cle) ?>"><?= e($cfg['label']) ?></label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="<?= e($cle . '_color') ?>"
                                       value="<?= e(substr((string) ($settings[$cle] ?? $cfg['default']), 0, 7)) ?>"
                                       onchange="document.getElementById('<?= e($cle) ?>').value = this.value">
                                <input type="text" class="form-control" id="<?= e($cle) ?>" name="<?= e($cle) ?>"
                                       value="<?= e($settings[$cle] ?? $cfg['default']) ?>" placeholder="<?= e($cfg['default']) ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr class="my-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= url('admin/landing') ?>" class="btn btn-outline-secondary">
                        <i class="mdi mdi-refresh me-1"></i><?= e(__('common.reset')) ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- ═══ Onglet : Contenu (CMS) ═══ -->
    <div class="wh-tab-pane d-none" id="pane-contenu">
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
    </div>

    <!-- ═══ Onglet : Ordre & visibilité des sections ═══ -->
    <div class="wh-tab-pane d-none" id="pane-sections">
    <form method="post" action="<?= url('admin/landing/ordre') ?>" id="sectionForm">
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="mdi mdi-sort-ascending me-2"></i><?= e(__('landing.admin.sections')) ?></span>
                <span class="wh-badge badge-blue" id="sectionCount"><?= count($sectionsOrder) ?></span>
            </div>
            <div class="card-body">
                <div class="wh-form-hint mb-2"><?= e(__('landing.admin.sections_hint') . ' ' . __('landing.admin.sections_drag_hint')) ?></div>
                <div id="sectionList">
                    <?php foreach ($sectionsOrder as $section): ?>
                        <?php
                            $visibleKey = 'section_' . $section . '_visible';
                            $isVisible = ! empty($settings[$visibleKey]);
                        ?>
                        <div class="section-item border rounded p-3 mb-2 bg-white d-flex align-items-center justify-content-between"
                             data-section="<?= e($section) ?>">
                            <div class="d-flex align-items-center gap-3">
                                <span class="drag-handle" style="cursor:grab; cursor:-webkit-grab;">
                                    <i class="mdi mdi-drag-variant mdi-24px text-muted"></i>
                                </span>
                                <div class="form-check">
                                    <input class="form-check-input section-visible" type="checkbox"
                                           name="visibles[]" id="vis-<?= e($section) ?>"
                                           value="<?= e($section) ?>" <?= $isVisible ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="vis-<?= e($section) ?>"><?= e($section) ?></label>
                                </div>
                            </div>
                            <input type="hidden" name="all_sections[]" value="<?= e($section) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="ordre" id="sectionOrderInput">
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
    </div>

    <!-- ═══ Onglet : FAQ ═══ -->
    <div class="wh-tab-pane d-none" id="pane-faq">
    <div id="faq">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-help-circle-outline me-2"></i>FAQ</span>
                    <span class="wh-badge badge-blue"><?= count($faq) ?></span>
                </div>
                <div class="card-body">
                    <div class="wh-sortable" data-reorder="faq" data-empty="<?= $faq === [] ? '1' : '0' ?>">
                    <?php foreach ($faq as $f): ?>
                        <div class="wh-sortable-item d-flex align-items-center justify-content-between gap-2 border-bottom py-2"
                             draggable="true" data-id="<?= (int) $f['id'] ?>">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="wh-sortable-handle" title="Drag"><i class="mdi mdi-drag-vertical"></i></span>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate"><?= e($f['question_fr']) ?></div>
                                    <small class="wh-text-muted text-truncate d-block"><?= e(mb_strimwidth((string) $f['reponse_fr'], 0, 70, '…')) ?></small>
                                </div>
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
                    </div>
                    <?php if ($faq !== []): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 wh-reorder-save">
                            <i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>
                        </button>
                    <?php endif; ?>
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
    </div>

    <!-- ═══ Onglet : Témoignages ═══ -->
    <div class="wh-tab-pane d-none" id="pane-temoignages">
    <div id="temoignages">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-format-quote-open me-2"></i><?= e(__('landing.admin.testimonials')) ?></span>
                    <span class="wh-badge badge-violet"><?= count($testimonials) ?></span>
                </div>
                <div class="card-body">
                    <div class="wh-sortable" data-reorder="temoignages" data-empty="<?= $testimonials === [] ? '1' : '0' ?>">
                    <?php foreach ($testimonials as $t): ?>
                        <div class="wh-sortable-item d-flex align-items-center justify-content-between gap-2 border-bottom py-2"
                             draggable="true" data-id="<?= (int) $t['id'] ?>">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="wh-sortable-handle" title="Drag"><i class="mdi mdi-drag-vertical"></i></span>
                                <div class="min-w-0">
                                    <div class="fw-semibold"><?= e($t['auteur']) ?><?= $t['role'] ? ' <span class="wh-text-muted">(' . e($t['role']) . ')</span>' : '' ?></div>
                                    <small class="wh-text-muted text-truncate d-block"><?= e(mb_strimwidth((string) $t['texte_fr'], 0, 70, '…')) ?></small>
                                </div>
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
                    </div>
                    <?php if ($testimonials !== []): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 wh-reorder-save">
                            <i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>
                        </button>
                    <?php endif; ?>
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
    </div>

    <!-- ═══ Onglet : Partenaires ═══ -->
    <div class="wh-tab-pane d-none" id="pane-partenaires">
    <div id="partenaires">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <span><i class="mdi mdi-handshake me-2"></i><?= e(__('landing.admin.partners')) ?></span>
                    <span class="wh-badge badge-cyan"><?= count($partners) ?></span>
                </div>
                <div class="card-body">
                    <div class="wh-sortable" data-reorder="partenaires" data-empty="<?= $partners === [] ? '1' : '0' ?>">
                    <?php foreach ($partners as $p): ?>
                        <div class="wh-sortable-item d-flex align-items-center justify-content-between gap-2 border-bottom py-2"
                             draggable="true" data-id="<?= (int) $p['id'] ?>">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="wh-sortable-handle" title="Drag"><i class="mdi mdi-drag-vertical"></i></span>
                                <div class="min-w-0">
                                    <div class="fw-semibold"><?= e($p['nom']) ?></div>
                                    <?php if ($p['url']): ?><small class="wh-text-muted text-truncate d-block"><?= e($p['url']) ?></small><?php endif; ?>
                                </div>
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
                    </div>
                    <?php if ($partners !== []): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 wh-reorder-save">
                            <i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>
                        </button>
                    <?php endif; ?>
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

<!-- Modal : aperçu en direct -->
<div class="modal fade" id="livePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-eye-outline me-1"></i><?= e(__('landing.admin.preview_live')) ?></h5>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="previewReloadBtn">
                        <i class="mdi mdi-refresh me-1"></i><?= e(__('common.refresh')) ?>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <iframe id="livePreviewFrame" src="<?= e(url('admin/landing/preview')) ?>"
                        title="Aperçu en direct" loading="eager" class="wh-preview-frame"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ── Search filter ──
    var box = document.getElementById('settingsSearch');
    if (box) {
        box.addEventListener('input', function () {
            var q = box.value.toLowerCase();
            var cards = document.querySelectorAll('form#contenu .card');
            cards.forEach(function (card) {
                var txt = card.textContent.toLowerCase();
                card.style.display = q === '' || txt.indexOf(q) > -1 ? '' : 'none';
            });
        });
    }

    // ── Sections : drag & drop + visibility ──
    var sectionList = document.getElementById('sectionList');
    var sectionOrderInput = document.getElementById('sectionOrderInput');
    var sectionCountEl = document.getElementById('sectionCount');

    if (sectionList && sectionOrderInput) {
        // Update hidden input + count before submit
        var updateOrder = function () {
            var order = [];
            sectionList.querySelectorAll('.section-item').forEach(function (item) {
                order.push(item.getAttribute('data-section'));
            });
            sectionOrderInput.value = JSON.stringify(order);
            if (sectionCountEl) {
                sectionCountEl.textContent = order.length;
            }
        };

        // Simple drag-and-drop (no external library)
        var draggedEl = null;

        sectionList.addEventListener('dragstart', function (e) {
            draggedEl = e.target.closest('.section-item');
            if (draggedEl) {
                setTimeout(function () {
                    draggedEl.classList.add('dragging');
                }, 0);
            }
        });

        sectionList.addEventListener('dragend', function (e) {
            var el = e.target.closest('.section-item');
            if (el) {
                el.classList.remove('dragging');
            }
            updateOrder();
        });

        sectionList.addEventListener('dragover', function (e) {
            e.preventDefault();
            var afterElement = getDragAfterElement(sectionList, e.clientY);
            var draggable = draggedEl || e.target.closest('.section-item');
            if (!draggable) return;
            if (afterElement == null) {
                sectionList.appendChild(draggable);
            } else {
                sectionList.insertBefore(draggable, afterElement);
            }
        });

        function getDragAfterElement(container, y) {
            var draggableElements = container.querySelectorAll('.section-item:not(.dragging)');
            var closest = null;
            var closestOffset = Number.NEGATIVE_INFINITY;
            draggableElements.forEach(function (child) {
                var box = child.getBoundingClientRect();
                var offset = y - (box.top + box.height / 2);
                if (offset < 0 && offset > closestOffset) {
                    closestOffset = offset;
                    closest = child;
                }
            });
            return closest;
        }
    }

    // ── Tabs (pills) ──
    var tabTriggers = document.querySelectorAll('[data-tab]');
    var panes = document.querySelectorAll('.wh-tab-pane');

    var activateTab = function (name) {
        panes.forEach(function (pane) {
            pane.classList.toggle('d-none', pane.id !== 'pane-' + name);
        });
        tabTriggers.forEach(function (btn) {
            var on = btn.getAttribute('data-tab') === name;
            btn.classList.toggle('active', on);
            var pill = btn.closest('.nav-link');
            if (pill) {
                pill.classList.toggle('active', on);
            }
        });
    };

    tabTriggers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.getAttribute('data-tab');
            if (name) {
                activateTab(name);
            }
        });
    });

    // ── Aperçu en direct : rechargement du iframe ──
    var previewFrame = document.getElementById('livePreviewFrame');
    var previewModal = document.getElementById('livePreviewModal');
    var previewReloadBtn = document.getElementById('previewReloadBtn');

    if (previewModal && previewFrame) {
        previewModal.addEventListener('show.bs.modal', function () {
            previewFrame.src = previewFrame.src;
        });
    }
    if (previewReloadBtn && previewFrame) {
        previewReloadBtn.addEventListener('click', function () {
            previewFrame.src = previewFrame.src;
        });
    }

    // ── Drag & drop : FAQ / témoignages / partenaires ──
    var reorderSaveUrl = <?= json_encode(url('admin/landing/reorder')) ?>;

    var initSortable = function (container) {
        if (!container) return;
        var items = container.querySelectorAll('.wh-sortable-item');
        var dragging = null;

        items.forEach(function (item) {
            item.addEventListener('dragstart', function (e) {
                dragging = item;
                setTimeout(function () {
                    item.classList.add('wh-sortable-dragging');
                }, 0);
            });
            item.addEventListener('dragend', function () {
                dragging = null;
                item.classList.remove('wh-sortable-dragging');
            });
        });

        container.addEventListener('dragover', function (e) {
            if (!dragging) return;
            e.preventDefault();
            var afterElement = getSortableAfter(container, e.clientY);
            if (afterElement == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, afterElement);
            }
        });
    };

    function getSortableAfter(container, y) {
        var els = container.querySelectorAll('.wh-sortable-item:not(.wh-sortable-dragging)');
        var closest = null;
        var closestOffset = Number.NEGATIVE_INFINITY;
        els.forEach(function (child) {
            var box = child.getBoundingClientRect();
            var offset = y - (box.top + box.height / 2);
            if (offset < 0 && offset > closestOffset) {
                closestOffset = offset;
                closest = child;
            }
        });
        return closest;
    }

    document.querySelectorAll('.wh-sortable').forEach(initSortable);

    document.querySelectorAll('.wh-reorder-save').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var container = btn.closest('.card').querySelector('.wh-sortable');
            if (!container || container.getAttribute('data-empty') === '1') return;

            var ids = [];
            container.querySelectorAll('.wh-sortable-item').forEach(function (item) {
                ids.push(item.getAttribute('data-id'));
            });

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><?= e(__('common.saving')) ?>';

            fetch(reorderSaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': <?= json_encode(csrf_token()) ?>,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ type: container.getAttribute('data-reorder'), ids: ids })
            })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function () {
                btn.innerHTML = '<i class="mdi mdi-check me-1"></i><?= e(__('common.saved')) ?>';
                setTimeout(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>';
                }, 1500);
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>';
            });
        });
    });
})();
</script>

<style>
.wh-tab-pane.d-none {
    display: none;
}
.wh-sortable-item {
    cursor: grab;
    background: #fff;
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.wh-sortable-item.wh-sortable-dragging {
    opacity: 0.45;
    transform: scale(0.98);
}
.wh-sortable-handle {
    cursor: grab;
    color: var(--text-muted, #64748b);
}
.wh-preview-frame {
    width: 100%;
    height: 75vh;
    border: 0;
    display: block;
    background: #fff;
}
.section-item.dragging {
    opacity: 0.5;
    transform: scale(0.98);
}
</style>

