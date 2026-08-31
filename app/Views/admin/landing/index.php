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
        'navbar'       => $dir === 'rtl' ? 'شريط التنقل' : 'Navigation (header)',
        'map'          => $dir === 'rtl' ? 'الخريطة' : 'Carte',
        'general'      => $dir === 'rtl' ? 'عام' : 'Général',
        default        => ucfirst($g),
    };
};

$groupes = ['hero', 'stats', 'apropos', 'fonctionnement', 'navbar', 'footer', 'map', 'contact', 'general'];
$boolKeys = ['navbar_visible', 'navbar_cta_visible', 'footer_show_titles', 'footer_show_navigation', 'footer_show_liens', 'footer_show_contact', 'map_visible', 'map_heatmap', 'general_upcoming_visible'];
$cleLabel = static function (string $cle): string {
    return match ($cle) {
        'general_upcoming_visible' => 'Afficher la section « Prochains événements »',
        'general_upcoming_max'     => 'Nombre maximum d\'événements affichés',
        default                    => '',
    };
};
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

$nbGallery  = (int) (\App\Helpers\Database::one('SELECT COUNT(*) AS c FROM landing_gallery')['c'] ?? 0);
$nbBefAft   = (int) (\App\Helpers\Database::one('SELECT COUNT(*) AS c FROM landing_before_after')['c'] ?? 0);
$nbNews     = (int) (\App\Helpers\Database::one('SELECT COUNT(*) AS c FROM landing_news WHERE deleted_at IS NULL')['c'] ?? 0);
$nbVisibleSections = 0;
foreach ($sections as $s) { $vis = $settings['section_' . $s . '_visible'] ?? '1'; if ($vis === '1' || $vis === 1) { $nbVisibleSections++; } }
$totalCms = count($faq) + count($testimonials) + count($partners) + $nbGallery + $nbBefAft + $nbNews;
?>
<div class="wh-page">

    <!-- ═══ Gradient Hero ═══ -->
    <div class="mb-4" style="background:linear-gradient(135deg, #0B5ED7 0%, #6610f2 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden">
        <div style="position:absolute;top:-40%;right:-8%;width:320px;height:320px;background:rgba(255,255,255,.08);border-radius:50%"></div>
        <div style="position:absolute;bottom:-30%;left:5%;width:200px;height:200px;background:rgba(255,255,255,.05);border-radius:50%"></div>
        <div class="row align-items-center" style="position:relative;z-index:1">
            <div class="col-lg-6">
                <h1 class="mb-1" style="font-size:1.5rem;font-weight:800">
                    <i class="mdi mdi-web me-2"></i><?= e(__('landing.admin.title')) ?>
                </h1>
                <p class="mb-2" style="opacity:.85;font-size:.9rem"><?= e(__('landing.admin.subtitle')) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <span style="background:rgba(255,255,255,.18);padding:.25rem .7rem;border-radius:999px;font-size:.72rem;font-weight:700">
                        <i class="mdi mdi-text-box-multiple-outline me-1"></i><?= $totalCms ?> <?= $isAr ? 'عناصر CMS' : 'éléments' ?>
                    </span>
                    <span style="background:rgba(255,255,255,.18);padding:.25rem .7rem;border-radius:999px;font-size:.72rem;font-weight:700">
                        <i class="mdi mdi-check-decagram me-1"></i><?= $nbVisibleSections ?>/<?= count($sections) ?> <?= $isAr ? 'أقسام' : 'sections' ?>
                    </span>
                    <span style="background:rgba(255,255,255,.18);padding:.25rem .7rem;border-radius:999px;font-size:.72rem;font-weight:700">
                        <i class="mdi mdi-newspaper me-1"></i><?= $nbNews ?> <?= $isAr ? 'أخبار' : 'news' ?>
                    </span>
                    <span style="background:rgba(255,255,255,.18);padding:.25rem .7rem;border-radius:999px;font-size:.72rem;font-weight:700">
                        <i class="mdi mdi-image-multiple me-1"></i><?= $nbGallery + $nbBefAft ?> <?= $isAr ? 'صور' : 'images' ?>
                    </span>
                </div>
            </div>
            <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                <a class="btn btn-warning btn-lg fw-bold" href="<?= url('admin/landing/preview') ?>" target="_blank" rel="noopener">
                    <i class="mdi mdi-eye-outline me-1"></i><?= e(__('landing.admin.preview')) ?>
                </a>
                <button type="button" class="btn btn-light btn-lg ms-2" data-bs-toggle="modal" data-bs-target="#livePreviewModal">
                    <i class="mdi mdi-fullscreen me-1"></i><?= e(__('landing.admin.preview_live')) ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ Tab Navigation ═══ -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius)">
        <div class="card-body p-2 px-3">
            <ul class="nav nav-pills flex-wrap gap-1 mb-0" id="landingTabs">
                <li class="nav-item"><button type="button" class="nav-link active" data-tab="theme"><i class="mdi mdi-palette-swatch-outline me-1"></i><?= e(__('landing.admin.theme')) ?></button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-tab="contenu"><i class="mdi mdi-text-box-multiple-outline me-1"></i><?= e(__('common.content')) ?></button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-tab="sections"><i class="mdi mdi-sort-ascending me-1"></i><?= e(__('landing.admin.sections')) ?></button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-tab="faq"><i class="mdi mdi-help-circle-outline me-1"></i>FAQ <span class="badge bg-primary ms-1" style="font-size:.65rem"><?= count($faq) ?></span></button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-tab="temoignages"><i class="mdi mdi-format-quote-open me-1"></i><?= e(__('landing.admin.testimonials')) ?> <span class="badge bg-success ms-1" style="font-size:.65rem"><?= count($testimonials) ?></span></button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-tab="partenaires"><i class="mdi mdi-handshake me-1"></i><?= e(__('landing.admin.partners')) ?> <span class="badge bg-warning ms-1" style="font-size:.65rem"><?= count($partners) ?></span></button></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('admin/landing/gallery') ?>"><i class="mdi mdi-image-multiple me-1"></i><?= e(__('landing.admin.gallery')) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('admin/landing/before-after') ?>"><i class="mdi mdi-compare-horizontal me-1"></i><?= e(__('landing.admin.before_after')) ?></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('admin/landing/news') ?>"><i class="mdi mdi-newspaper me-1"></i><?= $isAr ? 'الأخبار' : 'Actualités' ?></a></li>
                <li class="nav-item"><button type="button" class="nav-link" data-tab="seo"><i class="mdi mdi-search me-1"></i>SEO</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-tab="advanced"><i class="mdi mdi-cog-outline me-1"></i><?= $isAr ? 'متقدم' : 'Avancé' ?></button></li>
            </ul>
        </div>
    </div>

    <!-- ═══ Search bar ═══ -->
    <div class="mb-3">
        <div class="input-group" style="max-width:400px;border-radius:.55rem;overflow:hidden">
            <span class="input-group-text" style="background:#f1f5f9;border-right:0"><i class="mdi mdi-magnify" style="color:#64748b"></i></span>
            <input type="search" id="settingsSearch" class="form-control" style="border-left:0;background:#fff" placeholder="<?= $isAr ? 'بحث في الإعدادات...' : 'Filtrer les champs...' ?>">
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: Theme                                           -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane" id="pane-theme">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header" style="background:var(--wh-purple-soft);border-bottom:1px solid rgba(102,16,242,.12)">
            <div class="d-flex align-items-center gap-2">
                <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(102,16,242,.1);display:grid;place-items:center"><i class="mdi mdi-palette-swatch-outline" style="color:var(--wh-purple);font-size:1rem"></i></div>
                <span class="fw-bold"><?= e(__('landing.admin.theme')) ?></span>
            </div>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4" style="font-size:.88rem"><?= e(__('landing.admin.theme_hint')) ?></p>

            <!-- Preset rapide -->
            <form method="post" action="<?= url('admin/landing/theme') ?>" class="mb-4 p-3" style="background:#f8fafc;border-radius:.75rem">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-medium" style="font-size:.8rem;color:#475569"><?= e(__('landing.admin.theme_preset')) ?></label>
                        <select class="form-select" id="preset" name="preset" style="border-radius:.55rem">
                            <option value="custom" <?= $currentThemeName === 'custom' ? 'selected' : '' ?>><?= e(__('landing.admin.theme_custom')) ?></option>
                            <?php foreach ($themePresets as $p): ?>
                                <option value="<?= e($p['name']) ?>" <?= $currentThemeName === ($p['name'] ?? '') ? 'selected' : '' ?>>
                                    <?= e($p['label'] ?? $p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary" style="border-radius:.55rem"><i class="mdi mdi-check me-1"></i><?= e(__('common.apply')) ?></button>
                    </div>
                    <div class="col-md-4 d-grid">
                        <a href="<?= url('admin/landing/preview') ?>" target="_blank" class="btn btn-outline-secondary" style="border-radius:.55rem"><i class="mdi mdi-eye-outline me-1"></i><?= e(__('landing.admin.preview')) ?></a>
                    </div>
                </div>
            </form>

            <!-- Personnalisation manuelle -->
            <form method="post" action="<?= url('admin/landing/theme') ?>" id="themeForm">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <?php foreach ($themeColorKeys as $cle => $cfg): ?>
                        <div class="col-md-4">
                            <label class="form-label fw-medium" for="<?= e($cle) ?>" style="font-size:.8rem;color:#475569"><?= e($cfg['label']) ?></label>
                            <div class="input-group" style="border-radius:.55rem;overflow:hidden">
                                <input type="color" class="form-control form-control-color" style="border-radius:.55rem 0 0 .55rem" id="<?= e($cle . '_color') ?>"
                                       value="<?= e(substr((string) ($settings[$cle] ?? $cfg['default']), 0, 7)) ?>"
                                       onchange="document.getElementById('<?= e($cle) ?>').value = this.value">
                                <input type="text" class="form-control" id="<?= e($cle) ?>" name="<?= e($cle) ?>"
                                       value="<?= e($settings[$cle] ?? $cfg['default']) ?>" placeholder="<?= e($cfg['default']) ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= url('admin/landing') ?>" class="btn btn-outline-secondary" style="border-radius:.55rem"><i class="mdi mdi-refresh me-1"></i><?= e(__('common.reset')) ?></a>
                    <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: Contenu (CMS)                                   -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane d-none" id="pane-contenu">
    <form method="post" action="<?= url('admin/landing/settings') ?>" id="contenu">
        <?= csrf_field() ?>
        <?php foreach ($groupes as $groupe): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:1px solid rgba(11,94,215,.12)">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(11,94,215,.1);display:grid;place-items:center"><i class="mdi mdi-tune-vertical" style="color:var(--wh-blue);font-size:1rem"></i></div>
                        <span class="fw-bold"><?= e($groupeLabel($groupe)) ?></span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php $found = false; ?>
                    <?php foreach ($settings as $cle => $valeur): ?>
                        <?php if (! str_starts_with($cle, $groupe . '_')) continue; ?>
                        <?php $found = true; ?>
                        <div class="mb-3 p-3" style="background:#f8fafc;border-radius:.6rem">
                            <?php if (in_array($cle, $boolKeys, true)): ?>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="valeur-<?= e($cle) ?>" <?= (string) $valeur === '1' ? 'checked' : '' ?>
                                           onchange="document.getElementById('valeurHidden-<?= e($cle) ?>').value = this.checked ? '1' : '0';">
                                    <input type="hidden" name="cle[]" value="<?= e($cle) ?>">
                                    <input type="hidden" name="valeur[]" id="valeurHidden-<?= e($cle) ?>" value="<?= (string) $valeur === '1' ? '1' : '0' ?>">
                                    <label class="form-check-label fw-medium" for="valeur-<?= e($cle) ?>" style="font-size:.88rem"><?= e($cleLabel($cle) !== '' ? $cleLabel($cle) : $cleShort($cle)) ?> <span class="text-muted" style="font-size:.72rem">(<?= e($cle) ?>)</span></label>
                                </div>
                            <?php else: ?>
                            <label class="form-label fw-medium" for="<?= e($cle) ?>" style="font-size:.82rem;color:#475569"><?= e($cleLabel($cle) !== '' ? $cleLabel($cle) : $cleShort($cle)) ?> <span class="text-muted" style="font-size:.72rem">(<?= e($cle) ?>)</span></label>
                            <?php if ($cle === 'fonctionnement_etapes'): ?>
                                <textarea class="form-control font-monospace" id="<?= e($cle) ?>" name="cle[]" hidden><?= e($cle) ?></textarea>
                                <textarea class="form-control font-monospace" id="valeur-<?= e($cle) ?>" name="valeur[]" rows="6" style="border-radius:.55rem"><?= e(is_array($valeur) ? json_encode($valeur, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) $valeur) ?></textarea>
                                <div class="form-text small"><i class="mdi mdi-information-outline me-1"></i>JSON — liste d'étapes {titre_fr, titre_ar, description_fr, description_ar}</div>
                            <?php elseif ($isTextarea($cle)): ?>
                                <textarea class="form-control" id="valeur-<?= e($cle) ?>" name="valeur[]" rows="4" style="border-radius:.55rem"><?= e(is_array($valeur) ? '' : (string) $valeur) ?></textarea>
                                <input type="hidden" name="cle[]" value="<?= e($cle) ?>">
                            <?php else: ?>
                                <input type="text" class="form-control" id="valeur-<?= e($cle) ?>" name="valeur[]" value="<?= e(is_array($valeur) ? '' : (string) $valeur) ?>" style="border-radius:.55rem">
                                <input type="hidden" name="cle[]" value="<?= e($cle) ?>">
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (! $found): ?>
                        <div class="text-center py-3 text-muted"><i class="mdi mdi-information-outline me-1"></i><?= e(__('common.no_data')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: Ordre & visibilité des sections                 -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane d-none" id="pane-sections">
    <form method="post" action="<?= url('admin/landing/ordre') ?>" id="sectionForm">
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between" style="background:var(--wh-green-soft);border-bottom:1px solid rgba(25,135,84,.12)">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(25,135,84,.1);display:grid;place-items:center"><i class="mdi mdi-sort-ascending" style="color:var(--wh-green);font-size:1rem"></i></div>
                    <span class="fw-bold"><?= e(__('landing.admin.sections')) ?></span>
                </div>
                <span class="badge bg-primary" style="font-size:.7rem" id="sectionCount"><?= count($sectionsOrder) ?></span>
            </div>
            <div class="card-body p-4">
                <div class="form-text mb-3"><i class="mdi mdi-information-outline me-1"></i><?= e(__('landing.admin.sections_hint') . ' ' . __('landing.admin.sections_drag_hint')) ?></div>
                <div id="sectionList">
                    <?php foreach ($sectionsOrder as $section): ?>
                        <?php
                            $visibleKey = 'section_' . $section . '_visible';
                            $isVisible = ! empty($settings[$visibleKey]);
                        ?>
                        <div class="section-item border rounded p-3 mb-2 bg-white d-flex align-items-center justify-content-between"
                             data-section="<?= e($section) ?>" style="border-radius:.6rem;transition:transform .15s,box-shadow .15s">
                            <div class="d-flex align-items-center gap-3">
                                <span class="drag-handle" style="cursor:grab;cursor:-webkit-grab;color:#94a3b8">
                                    <i class="mdi mdi-drag-variant mdi-20px"></i>
                                </span>
                                <div class="form-check mb-0">
                                    <input class="form-check-input section-visible" type="checkbox"
                                           name="visibles[]" id="vis-<?= e($section) ?>"
                                           value="<?= e($section) ?>" <?= $isVisible ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-medium" for="vis-<?= e($section) ?>" style="font-size:.88rem"><?= e($section) ?></label>
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
            <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: FAQ                                             -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane d-none" id="pane-faq">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex align-items-center justify-content-between" style="background:var(--wh-blue-soft);border-bottom:1px solid rgba(11,94,215,.12)">
            <div class="d-flex align-items-center gap-2">
                <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(11,94,215,.1);display:grid;place-items:center"><i class="mdi mdi-help-circle-outline" style="color:var(--wh-blue);font-size:1rem"></i></div>
                <span class="fw-bold">FAQ</span>
            </div>
            <span class="badge bg-primary" style="font-size:.7rem"><?= count($faq) ?></span>
        </div>
        <div class="card-body p-4">
            <div class="wh-sortable" data-reorder="faq" data-empty="<?= $faq === [] ? '1' : '0' ?>">
            <?php foreach ($faq as $f): ?>
                <div class="wh-sortable-item d-flex align-items-center justify-content-between gap-2 border-bottom py-3"
                     draggable="true" data-id="<?= (int) $f['id'] ?>">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <span class="wh-sortable-handle" title="Drag"><i class="mdi mdi-drag-vertical" style="color:#94a3b8"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate"><?= e($f['question_fr']) ?></div>
                            <small class="text-muted text-truncate d-block" style="font-size:.78rem"><?= e(mb_strimwidth((string) $f['reponse_fr'], 0, 70, '…')) ?></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <?php if (! (int) $f['actif']): ?><span class="badge bg-secondary" style="font-size:.65rem">off</span><?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius:.5rem"
                                onclick="editFaq(<?= (int) $f['id'] ?>, <?= e(json_encode($f['question_fr'])) ?>, <?= e(json_encode($f['question_ar'] ?? '')) ?>, <?= e(json_encode($f['reponse_fr'])) ?>, <?= e(json_encode($f['reponse_ar'] ?? '')) ?>, <?= (int) $f['ordre'] ?>, <?= (int) $f['actif'] ?>)"
                                title="Modifier"><i class="mdi mdi-pencil"></i></button>
                        <form method="post" action="<?= url('admin/landing/faq/' . (int) $f['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:.5rem"><i class="mdi mdi-delete"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if ($faq !== []): ?>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2 wh-reorder-save" style="border-radius:.5rem">
                    <i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>
                </button>
            <?php endif; ?>
            <?php if ($faq === []): ?>
                <div class="text-center py-4 text-muted"><i class="mdi mdi-help-circle-outline" style="font-size:2rem;opacity:.3"></i><p class="mb-0 mt-2 small"><?= e(__('common.no_data')) ?></p></div>
            <?php endif; ?>

            <hr class="my-4">
            <h6 class="fw-bold mb-3" style="font-size:.9rem"><i class="mdi mdi-plus-circle-outline me-1" style="color:var(--wh-green)"></i><?= $isAr ? 'إضافة سؤال' : 'Ajouter une question' ?></h6>
            <form method="post" action="<?= url('admin/landing/faq') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Question (FR) *</label>
                        <input type="text" class="form-control" id="question_fr" name="question_fr" required style="border-radius:.55rem">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Question (AR)</label>
                        <input type="text" class="form-control" id="question_ar" name="question_ar" dir="rtl" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Réponse (FR) *</label>
                        <textarea class="form-control" id="reponse_fr" name="reponse_fr" rows="3" required style="border-radius:.55rem"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Réponse (AR)</label>
                        <textarea class="form-control" id="reponse_ar" name="reponse_ar" rows="3" dir="rtl" style="border-radius:.55rem"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Ordre</label>
                        <input type="number" class="form-control" id="ordre" name="ordre" value="0" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="actif" id="faq-actif" checked>
                            <label class="form-check-label" for="faq-actif"><?= e(__('landing.admin.active')) ?></label>
                        </div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary" style="border-radius:.55rem"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: Témoignages                                     -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane d-none" id="pane-temoignages">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex align-items-center justify-content-between" style="background:var(--wh-green-soft);border-bottom:1px solid rgba(25,135,84,.12)">
            <div class="d-flex align-items-center gap-2">
                <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(25,135,84,.1);display:grid;place-items:center"><i class="mdi mdi-format-quote-open" style="color:var(--wh-green);font-size:1rem"></i></div>
                <span class="fw-bold"><?= e(__('landing.admin.testimonials')) ?></span>
            </div>
            <span class="badge bg-success" style="font-size:.7rem"><?= count($testimonials) ?></span>
        </div>
        <div class="card-body p-4">
            <div class="wh-sortable" data-reorder="temoignages" data-empty="<?= $testimonials === [] ? '1' : '0' ?>">
            <?php foreach ($testimonials as $t): ?>
                <div class="wh-sortable-item d-flex align-items-center justify-content-between gap-2 border-bottom py-3"
                     draggable="true" data-id="<?= (int) $t['id'] ?>">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <span class="wh-sortable-handle" title="Drag"><i class="mdi mdi-drag-vertical" style="color:#94a3b8"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold"><?= e($t['auteur']) ?><?= $t['role'] ? ' <span class="text-muted" style="font-size:.78rem">(' . e($t['role']) . ')</span>' : '' ?></div>
                            <small class="text-muted text-truncate d-block" style="font-size:.78rem"><?= e(mb_strimwidth((string) $t['texte_fr'], 0, 70, '…')) ?></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <?php if (! (int) $t['actif']): ?><span class="badge bg-secondary" style="font-size:.65rem">off</span><?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius:.5rem"
                                onclick="editTemoignage(<?= (int) $t['id'] ?>, <?= e(json_encode($t['auteur'])) ?>, <?= e(json_encode($t['role'] ?? '')) ?>, <?= e(json_encode($t['texte_fr'])) ?>, <?= e(json_encode($t['texte_ar'] ?? '')) ?>, <?= (int) $t['note'] ?>, <?= (int) $t['actif'] ?>)"
                                title="Modifier"><i class="mdi mdi-pencil"></i></button>
                        <form method="post" action="<?= url('admin/landing/temoignages/' . (int) $t['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:.5rem"><i class="mdi mdi-delete"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if ($testimonials !== []): ?>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2 wh-reorder-save" style="border-radius:.5rem">
                    <i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>
                </button>
            <?php endif; ?>
            <?php if ($testimonials === []): ?>
                <div class="text-center py-4 text-muted"><i class="mdi mdi-format-quote-open" style="font-size:2rem;opacity:.3"></i><p class="mb-0 mt-2 small"><?= e(__('common.no_data')) ?></p></div>
            <?php endif; ?>

            <hr class="my-4">
            <h6 class="fw-bold mb-3" style="font-size:.9rem"><i class="mdi mdi-plus-circle-outline me-1" style="color:var(--wh-green)"></i><?= $isAr ? 'إضافة شهادة' : 'Ajouter un témoignage' ?></h6>
            <form method="post" action="<?= url('admin/landing/temoignages') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><?= e(__('landing.admin.author')) ?> *</label>
                        <input type="text" class="form-control" id="auteur" name="auteur" required maxlength="100" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><?= e(__('landing.admin.author_role')) ?></label>
                        <input type="text" class="form-control" id="role" name="role" maxlength="100" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><?= e(__('landing.admin.texte')) ?> (FR) *</label>
                        <textarea class="form-control" id="texte_fr" name="texte_fr" rows="3" required style="border-radius:.55rem"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><?= e(__('landing.admin.texte')) ?> (AR)</label>
                        <textarea class="form-control" id="texte_ar" name="texte_ar" rows="3" dir="rtl" style="border-radius:.55rem"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Note</label>
                        <select class="form-select" id="note" name="note" style="border-radius:.55rem">
                            <option value="5">5 ★★★★★</option><option value="4">4 ★★★★</option><option value="3">3 ★★★</option><option value="2">2 ★★</option><option value="1">1 ★</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="actif" id="t-actif" checked><label class="form-check-label" for="t-actif"><?= e(__('landing.admin.active')) ?></label></div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary" style="border-radius:.55rem"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: Partenaires                                     -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane d-none" id="pane-partenaires">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex align-items-center justify-content-between" style="background:var(--wh-amber-soft);border-bottom:1px solid rgba(245,158,11,.12)">
            <div class="d-flex align-items-center gap-2">
                <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(245,158,11,.1);display:grid;place-items:center"><i class="mdi mdi-handshake" style="color:#d97706;font-size:1rem"></i></div>
                <span class="fw-bold"><?= e(__('landing.admin.partners')) ?></span>
            </div>
            <span class="badge bg-warning text-dark" style="font-size:.7rem"><?= count($partners) ?></span>
        </div>
        <div class="card-body p-4">
            <div class="wh-sortable" data-reorder="partenaires" data-empty="<?= $partners === [] ? '1' : '0' ?>">
            <?php foreach ($partners as $p): ?>
                <div class="wh-sortable-item d-flex align-items-center justify-content-between gap-2 border-bottom py-3"
                     draggable="true" data-id="<?= (int) $p['id'] ?>">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <span class="wh-sortable-handle" title="Drag"><i class="mdi mdi-drag-vertical" style="color:#94a3b8"></i></span>
                        <?php if (! empty($p['logo'])): ?>
                            <img src="<?= e($p['logo']) ?>" alt="" loading="lazy" style="width:36px;height:36px;object-fit:contain;border-radius:.5rem;background:#f1f5f9;padding:3px">
                        <?php else: ?>
                            <div style="width:36px;height:36px;border-radius:.5rem;background:#f1f5f9;display:grid;place-items:center;color:#94a3b8"><i class="mdi mdi-image-outline"></i></div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <div class="fw-semibold"><?= e($p['nom']) ?></div>
                            <?php if ($p['url']): ?><small class="text-muted text-truncate d-block" style="font-size:.75rem"><?= e($p['url']) ?></small><?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <?php if (! (int) $p['actif']): ?><span class="badge bg-secondary" style="font-size:.65rem">off</span><?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius:.5rem"
                                onclick="editPartenaire(<?= (int) $p['id'] ?>, <?= e(json_encode($p['nom'])) ?>, <?= e(json_encode($p['url'] ?? '')) ?>, <?= e(json_encode($p['logo'] ?? '')) ?>, <?= (int) ($p['ordre'] ?? 0) ?>, <?= (int) $p['actif'] ?>)"
                                title="Modifier"><i class="mdi mdi-pencil"></i></button>
                        <form method="post" action="<?= url('admin/landing/partenaires/' . (int) $p['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:.5rem"><i class="mdi mdi-delete"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if ($partners !== []): ?>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2 wh-reorder-save" style="border-radius:.5rem">
                    <i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>
                </button>
            <?php endif; ?>
            <?php if ($partners === []): ?>
                <div class="text-center py-4 text-muted"><i class="mdi mdi-handshake" style="font-size:2rem;opacity:.3"></i><p class="mb-0 mt-2 small"><?= e(__('common.no_data')) ?></p></div>
            <?php endif; ?>

            <hr class="my-4">
            <h6 class="fw-bold mb-3" style="font-size:.9rem"><i class="mdi mdi-plus-circle-outline me-1" style="color:var(--wh-green)"></i><?= $isAr ? 'إضافة شريك' : 'Ajouter un partenaire' ?></h6>
            <form method="post" action="<?= url('admin/landing/partenaires') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><?= e(__('common.nom')) ?> *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required maxlength="100" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">URL</label>
                        <input type="url" class="form-control" id="url" name="url" maxlength="255" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Logo</label>
                        <input type="file" class="form-control" id="p_logo_file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Ordre</label>
                        <input type="number" class="form-control" id="ordre" name="ordre" value="0" style="border-radius:.55rem">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="actif" id="p-actif" checked><label class="form-check-label" for="p-actif"><?= e(__('landing.admin.active')) ?></label></div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary" style="border-radius:.55rem"><i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: SEO (NEW)                                       -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane d-none" id="pane-seo">
    <form method="post" action="<?= url('admin/landing/settings') ?>">
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:1px solid rgba(11,94,215,.12)">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(11,94,215,.1);display:grid;place-items:center"><i class="mdi mdi-search" style="color:var(--wh-blue);font-size:1rem"></i></div>
                    <span class="fw-bold">SEO & Métadonnées</span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Meta Title (FR)</label>
                        <input type="text" class="form-control" name="cle[]" value="seo_title_fr" hidden>
                        <input type="text" class="form-control" name="valeur[]" value="<?= e($settings['seo_title_fr'] ?? '') ?>" placeholder="Wilaya d'Alger — Plateforme citoyenne" style="border-radius:.55rem">
                        <div class="form-text small"><i class="mdi mdi-information-outline me-1"></i>50-60 caractères recommandés</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Meta Title (AR)</label>
                        <input type="text" class="form-control" name="cle[]" value="seo_title_ar" hidden>
                        <input type="text" class="form-control" name="valeur[]" value="<?= e($settings['seo_title_ar'] ?? '') ?>" dir="rtl" placeholder="ولاية الجزائر — المنصة المواطنة" style="border-radius:.55rem">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Meta Description (FR)</label>
                        <input type="text" class="form-control" name="cle[]" value="seo_description_fr" hidden>
                        <textarea class="form-control" name="valeur[]" rows="3" placeholder="Découvrez les événements, associations et activités de la Wilaya d'Alger..." style="border-radius:.55rem"><?= e($settings['seo_description_fr'] ?? '') ?></textarea>
                        <div class="form-text small"><i class="mdi mdi-information-outline me-1"></i>150-160 caractères recommandés</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Meta Description (AR)</label>
                        <input type="text" class="form-control" name="cle[]" value="seo_description_ar" hidden>
                        <textarea class="form-control" name="valeur[]" rows="3" dir="rtl" placeholder="اكتشف الفعاليات والجمعيات وأنشطة ولاية الجزائر..." style="border-radius:.55rem"><?= e($settings['seo_description_ar'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">OG Image URL</label>
                        <input type="text" class="form-control" name="cle[]" value="seo_og_image" hidden>
                        <input type="url" class="form-control" name="valeur[]" value="<?= e($settings['seo_og_image'] ?? '') ?>" placeholder="https://..." style="border-radius:.55rem">
                        <div class="form-text small">Image pour les partages sociaux (1200×630px)</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Canonical URL</label>
                        <input type="text" class="form-control" name="cle[]" value="seo_canonical" hidden>
                        <input type="url" class="form-control" name="valeur[]" value="<?= e($settings['seo_canonical'] ?? '') ?>" placeholder="https://..." style="border-radius:.55rem">
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background:var(--wh-purple-soft);border-bottom:1px solid rgba(102,16,242,.12)">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(102,16,242,.1);display:grid;place-items:center"><i class="mdi mdi-share-variant" style="color:var(--wh-purple);font-size:1rem"></i></div>
                    <span class="fw-bold">Réseaux sociaux</span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><i class="mdi mdi-facebook me-1" style="color:#1877f2"></i>Facebook</label>
                        <input type="text" class="form-control" name="cle[]" value="social_facebook" hidden>
                        <input type="url" class="form-control" name="valeur[]" value="<?= e($settings['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/..." style="border-radius:.55rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><i class="mdi mdi-instagram me-1" style="color:#e4405f"></i>Instagram</label>
                        <input type="text" class="form-control" name="cle[]" value="social_instagram" hidden>
                        <input type="url" class="form-control" name="valeur[]" value="<?= e($settings['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/..." style="border-radius:.55rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><i class="mdi mdi-twitter me-1" style="color:#1da1f2"></i>X / Twitter</label>
                        <input type="text" class="form-control" name="cle[]" value="social_twitter" hidden>
                        <input type="url" class="form-control" name="valeur[]" value="<?= e($settings['social_twitter'] ?? '') ?>" placeholder="https://x.com/..." style="border-radius:.55rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><i class="mdi mdi-youtube me-1" style="color:#ff0000"></i>YouTube</label>
                        <input type="text" class="form-control" name="cle[]" value="social_youtube" hidden>
                        <input type="url" class="form-control" name="valeur[]" value="<?= e($settings['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/..." style="border-radius:.55rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><i class="mdi mdi-linkedin me-1" style="color:#0a66c2"></i>LinkedIn</label>
                        <input type="text" class="form-control" name="cle[]" value="social_linkedin" hidden>
                        <input type="url" class="form-control" name="valeur[]" value="<?= e($settings['social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/..." style="border-radius:.55rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569"><i class="mdi mdi-email-outline me-1"></i>Email</label>
                        <input type="text" class="form-control" name="cle[]" value="social_email" hidden>
                        <input type="email" class="form-control" name="valeur[]" value="<?= e($settings['social_email'] ?? '') ?>" placeholder="contact@..." style="border-radius:.55rem">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PANE: Avancé (NEW)                                    -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="wh-tab-pane d-none" id="pane-advanced">
    <form method="post" action="<?= url('admin/landing/settings') ?>">
        <?= csrf_field() ?>

        <!-- Analytics -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:1px solid rgba(11,94,215,.12)">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(11,94,215,.1);display:grid;place-items:center"><i class="mdi mdi-chart-areaspline" style="color:var(--wh-blue);font-size:1rem"></i></div>
                    <span class="fw-bold">Analytics & Tracking</span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Google Analytics ID</label>
                        <input type="text" class="form-control" name="cle[]" value="analytics_ga_id" hidden>
                        <input type="text" class="form-control" name="valeur[]" value="<?= e($settings['analytics_ga_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX" style="border-radius:.55rem;max-width:400px">
                        <div class="form-text small">Mesure ID (gtag.js) — laisser vide pour désactiver</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Google Tag Manager ID</label>
                        <input type="text" class="form-control" name="cle[]" value="analytics_gtm_id" hidden>
                        <input type="text" class="form-control" name="valeur[]" value="<?= e($settings['analytics_gtm_id'] ?? '') ?>" placeholder="GTM-XXXXXXX" style="border-radius:.55rem;max-width:400px">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Code de tracking personnalisé (HEAD)</label>
                        <input type="text" class="form-control" name="cle[]" value="analytics_custom_head" hidden>
                        <textarea class="form-control font-monospace" name="valeur[]" rows="4" placeholder="<!-- Pixel, Hotjar, etc. -->" style="border-radius:.55rem;font-size:.78rem"><?= e($settings['analytics_custom_head'] ?? '') ?></textarea>
                        <div class="form-text small"><i class="mdi mdi-shield-lock-outline me-1 text-danger"></i>Script injecté dans le &lt;head&gt; — vérifier avant sauvegarde</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Mode -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background:var(--wh-amber-soft);border-bottom:1px solid rgba(245,158,11,.12)">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;border-radius:.5rem;background:rgba(245,158,11,.1);display:grid;place-items:center"><i class="mdi mdi-wrench-outline" style="color:#d97706;font-size:1rem"></i></div>
                    <span class="fw-bold">Mode maintenance</span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="valeur-maintenance_mode" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>
                           onchange="document.getElementById('valeurHidden-maintenance_mode').value = this.checked ? '1' : '0';">
                    <input type="hidden" name="cle[]" value="maintenance_mode">
                    <input type="hidden" name="valeur[]" id="valeurHidden-maintenance_mode" value="<?= ($settings['maintenance_mode'] ?? '0') === '1' ? '1' : '0' ?>">
                    <label class="form-check-label fw-semibold" for="valeur-maintenance_mode">Activer le mode maintenance</label>
                </div>
                <div class="p-3 rounded-3" style="background:#fffbeb;border:1px solid #fde68a">
                    <div class="d-flex align-items-start gap-2">
                        <i class="mdi mdi-alert-circle-outline text-warning" style="font-size:1.2rem;margin-top:2px"></i>
                        <div>
                            <div class="fw-semibold small">Attention</div>
                            <div class="text-muted" style="font-size:.82rem">En mode maintenance, le site public affichera une page de maintenance. Les administrateurs restent connectés.</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Message de maintenance (FR)</label>
                    <input type="text" class="form-control" name="cle[]" value="maintenance_message_fr" hidden>
                    <input type="text" class="form-control" name="valeur[]" value="<?= e($settings['maintenance_message_fr'] ?? 'Le site est en maintenance. Nous serons de retour très bientôt.') ?>" style="border-radius:.55rem">
                </div>
                <div class="mt-3">
                    <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Message de maintenance (AR)</label>
                    <input type="text" class="form-control" name="cle[]" value="maintenance_message_ar" hidden>
                    <input type="text" class="form-control" name="valeur[]" value="<?= e($settings['maintenance_message_ar'] ?? 'الموقع في وضع الصيانة. سنعود قريباً.') ?>" dir="rtl" style="border-radius:.55rem">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
        </div>
    </form>
    </div>

<!-- ═══ Modals d'édition ═══ -->

<!-- Modal FAQ -->
<div class="modal fade" id="editFaqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:var(--wh-radius);overflow:hidden">
            <form id="editFaqForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0" style="background:var(--wh-blue-soft)">
                    <h5 class="modal-title fw-bold" style="font-size:.95rem"><i class="mdi mdi-pencil me-1" style="color:var(--wh-blue)"></i>Modifier la FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="question_fr" id="faq_q_fr">
                    <input type="hidden" name="question_ar" id="faq_q_ar">
                    <input type="hidden" name="reponse_fr" id="faq_r_fr">
                    <input type="hidden" name="reponse_ar" id="faq_r_ar">
                    <input type="hidden" name="ordre" id="faq_ordre">
                    <input type="hidden" name="actif" id="faq_actif">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Question (FR) *</label>
                            <input type="text" class="form-control" id="faq_q_fr_input" required style="border-radius:.55rem">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Question (AR)</label>
                            <input type="text" class="form-control" id="faq_q_ar_input" dir="rtl" style="border-radius:.55rem">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Réponse (FR) *</label>
                            <textarea class="form-control" id="faq_r_fr_input" rows="3" required style="border-radius:.55rem"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Réponse (AR)</label>
                            <textarea class="form-control" id="faq_r_ar_input" rows="3" dir="rtl" style="border-radius:.55rem"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Ordre</label>
                            <input type="number" class="form-control" id="faq_ordre_input" min="0" style="border-radius:.55rem">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="faq_actif_input"><label class="form-check-label" for="faq_actif_input">Actif</label></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:.55rem"><?= e(__('common.cancel')) ?></button>
                    <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Témoignage -->
<div class="modal fade" id="editTemoignageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:var(--wh-radius);overflow:hidden">
            <form id="editTemoignageForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0" style="background:var(--wh-green-soft)">
                    <h5 class="modal-title fw-bold" style="font-size:.95rem"><i class="mdi mdi-pencil me-1" style="color:var(--wh-green)"></i>Modifier le témoignage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="auteur" id="t_auteur">
                    <input type="hidden" name="role" id="t_role">
                    <input type="hidden" name="texte_fr" id="t_texte_fr">
                    <input type="hidden" name="texte_ar" id="t_texte_ar">
                    <input type="hidden" name="note" id="t_note">
                    <input type="hidden" name="actif" id="t_actif">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Auteur *</label>
                            <input type="text" class="form-control" id="t_auteur_input" required maxlength="100" style="border-radius:.55rem">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Rôle</label>
                            <input type="text" class="form-control" id="t_role_input" maxlength="100" style="border-radius:.55rem">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Texte (FR) *</label>
                            <textarea class="form-control" id="t_texte_fr_input" rows="3" required style="border-radius:.55rem"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Texte (AR)</label>
                            <textarea class="form-control" id="t_texte_ar_input" rows="3" dir="rtl" style="border-radius:.55rem"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Note</label>
                            <select class="form-select" id="t_note_input" style="border-radius:.55rem">
                                <option value="5">5 ★★★★★</option><option value="4">4 ★★★★</option><option value="3">3 ★★★</option><option value="2">2 ★★</option><option value="1">1 ★</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="t_actif_input"><label class="form-check-label" for="t_actif_input">Actif</label></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:.55rem"><?= e(__('common.cancel')) ?></button>
                    <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Partenaire -->
<div class="modal fade" id="editPartenaireModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:var(--wh-radius);overflow:hidden">
            <form id="editPartenaireForm" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="nom" id="p_nom">
                <input type="hidden" name="url" id="p_url">
                <input type="hidden" name="ordre" id="p_ordre">
                <input type="hidden" name="actif" id="p_actif">
                <div class="modal-header border-0" style="background:var(--wh-amber-soft)">
                    <h5 class="modal-title fw-bold" style="font-size:.95rem"><i class="mdi mdi-pencil me-1" style="color:#d97706"></i>Modifier le partenaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Nom *</label>
                            <input type="text" class="form-control" id="p_nom_input" required maxlength="100" style="border-radius:.55rem">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">URL</label>
                            <input type="url" class="form-control" id="p_url_input" maxlength="255" style="border-radius:.55rem">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Logo actuel</label>
                            <div id="p_logo_preview" class="mb-2" style="display:none;">
                                <img src="" alt="" loading="lazy" style="max-height:48px;max-width:200px;object-fit:contain;border-radius:.5rem;background:#f1f5f9;padding:4px">
                            </div>
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Remplacer le logo</label>
                            <input type="file" class="form-control" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg" style="border-radius:.55rem">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium" style="font-size:.82rem;color:#475569">Ordre</label>
                            <input type="number" class="form-control" id="p_ordre_input" min="0" style="border-radius:.55rem">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="p_actif_input"><label class="form-check-label" for="p_actif_input">Actif</label></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:.55rem"><?= e(__('common.cancel')) ?></button>
                    <button type="submit" class="btn btn-primary fw-bold" style="border-radius:.55rem"><i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : aperçu en direct -->
<div class="modal fade" id="livePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0" style="border-radius:var(--wh-radius);overflow:hidden">
            <div class="modal-header border-0" style="background:var(--wh-blue-soft)">
                <h5 class="modal-title fw-bold" style="font-size:.95rem"><i class="mdi mdi-eye-outline me-1" style="color:var(--wh-blue)"></i><?= e(__('landing.admin.preview_live')) ?></h5>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="previewReloadBtn" style="border-radius:.5rem">
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
            var cards = document.querySelectorAll('.wh-tab-pane:not(.d-none) .card');
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
        var updateOrder = function () {
            var order = [];
            sectionList.querySelectorAll('.section-item').forEach(function (item) {
                order.push(item.getAttribute('data-section'));
            });
            sectionOrderInput.value = JSON.stringify(order);
            if (sectionCountEl) sectionCountEl.textContent = order.length;
        };

        var draggedEl = null;

        sectionList.addEventListener('dragstart', function (e) {
            draggedEl = e.target.closest('.section-item');
            if (draggedEl) setTimeout(function () { draggedEl.classList.add('dragging'); }, 0);
        });

        sectionList.addEventListener('dragend', function (e) {
            var el = e.target.closest('.section-item');
            if (el) el.classList.remove('dragging');
            updateOrder();
        });

        sectionList.addEventListener('dragover', function (e) {
            e.preventDefault();
            var afterElement = getDragAfterElement(sectionList, e.clientY);
            var draggable = draggedEl || e.target.closest('.section-item');
            if (!draggable) return;
            if (afterElement == null) { sectionList.appendChild(draggable); } else { sectionList.insertBefore(draggable, afterElement); }
        });

        function getDragAfterElement(container, y) {
            var draggableElements = container.querySelectorAll('.section-item:not(.dragging)');
            var closest = null;
            var closestOffset = Number.NEGATIVE_INFINITY;
            draggableElements.forEach(function (child) {
                var box = child.getBoundingClientRect();
                var offset = y - (box.top + box.height / 2);
                if (offset < 0 && offset > closestOffset) { closestOffset = offset; closest = child; }
            });
            return closest;
        }
    }

    // ── Tabs ──
    var tabTriggers = document.querySelectorAll('[data-tab]');
    var panes = document.querySelectorAll('.wh-tab-pane');

    var activateTab = function (name) {
        panes.forEach(function (pane) { pane.classList.toggle('d-none', pane.id !== 'pane-' + name); });
        tabTriggers.forEach(function (btn) {
            var on = btn.getAttribute('data-tab') === name;
            btn.classList.toggle('active', on);
            var pill = btn.closest('.nav-link');
            if (pill) pill.classList.toggle('active', on);
        });
    };

    tabTriggers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.getAttribute('data-tab');
            if (name) activateTab(name);
        });
    });

    // ── Live preview ──
    var previewFrame = document.getElementById('livePreviewFrame');
    var previewModal = document.getElementById('livePreviewModal');
    var previewReloadBtn = document.getElementById('previewReloadBtn');

    if (previewModal && previewFrame) {
        previewModal.addEventListener('show.bs.modal', function () { previewFrame.src = previewFrame.src; });
    }
    if (previewReloadBtn && previewFrame) {
        previewReloadBtn.addEventListener('click', function () { previewFrame.src = previewFrame.src; });
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
                setTimeout(function () { item.classList.add('wh-sortable-dragging'); }, 0);
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
            if (afterElement == null) { container.appendChild(dragging); } else { container.insertBefore(dragging, afterElement); }
        });
    };

    function getSortableAfter(container, y) {
        var els = container.querySelectorAll('.wh-sortable-item:not(.wh-sortable-dragging)');
        var closest = null;
        var closestOffset = Number.NEGATIVE_INFINITY;
        els.forEach(function (child) {
            var box = child.getBoundingClientRect();
            var offset = y - (box.top + box.height / 2);
            if (offset < 0 && offset > closestOffset) { closestOffset = offset; closest = child; }
        });
        return closest;
    }

    document.querySelectorAll('.wh-sortable').forEach(initSortable);

    document.querySelectorAll('.wh-reorder-save').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var container = btn.closest('.card').querySelector('.wh-sortable');
            if (!container || container.getAttribute('data-empty') === '1') return;

            var ids = [];
            container.querySelectorAll('.wh-sortable-item').forEach(function (item) { ids.push(item.getAttribute('data-id')); });

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><?= e(__('common.saving')) ?>';

            fetch(reorderSaveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': <?= json_encode(csrf_token()) ?>, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ type: container.getAttribute('data-reorder'), ids: ids })
            })
            .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
            .then(function () {
                btn.innerHTML = '<i class="mdi mdi-check me-1"></i><?= e(__('common.saved')) ?>';
                setTimeout(function () { btn.disabled = false; btn.innerHTML = '<i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>'; }, 1500);
            })
            .catch(function () { btn.disabled = false; btn.innerHTML = '<i class="mdi mdi-content-save me-1"></i><?= e(__('landing.admin.save_order')) ?>'; });
        });
    });

    // ── FAQ Edit Modal ──
    window.editFaq = function (id, qFr, qAr, rFr, rAr, ordre, actif) {
        document.getElementById('editFaqForm').action = <?= json_encode(url('admin/landing/faq/0/update')) ?>.replace('/0/', '/' + id + '/');
        document.getElementById('faq_q_fr_input').value = qFr || '';
        document.getElementById('faq_q_ar_input').value = qAr || '';
        document.getElementById('faq_r_fr_input').value = rFr || '';
        document.getElementById('faq_r_ar_input').value = rAr || '';
        document.getElementById('faq_ordre_input').value = ordre || 0;
        document.getElementById('faq_actif_input').checked = !!actif;
        new bootstrap.Modal(document.getElementById('editFaqModal')).show();
    };
    document.getElementById('editFaqForm').addEventListener('submit', function () {
        var f = this;
        f.querySelector('[name="question_fr"]').value = document.getElementById('faq_q_fr_input').value;
        f.querySelector('[name="question_ar"]').value = document.getElementById('faq_q_ar_input').value;
        f.querySelector('[name="reponse_fr"]').value = document.getElementById('faq_r_fr_input').value;
        f.querySelector('[name="reponse_ar"]').value = document.getElementById('faq_r_ar_input').value;
        f.querySelector('[name="ordre"]').value = document.getElementById('faq_ordre_input').value;
        f.querySelector('[name="actif"]').value = document.getElementById('faq_actif_input').checked ? '1' : '0';
    });

    // ── Témoignage Edit Modal ──
    window.editTemoignage = function (id, auteur, role, texteFr, texteAr, note, actif) {
        document.getElementById('editTemoignageForm').action = <?= json_encode(url('admin/landing/temoignages/0/update')) ?>.replace('/0/', '/' + id + '/');
        document.getElementById('t_auteur_input').value = auteur || '';
        document.getElementById('t_role_input').value = role || '';
        document.getElementById('t_texte_fr_input').value = texteFr || '';
        document.getElementById('t_texte_ar_input').value = texteAr || '';
        document.getElementById('t_note_input').value = note || 5;
        document.getElementById('t_actif_input').checked = !!actif;
        new bootstrap.Modal(document.getElementById('editTemoignageModal')).show();
    };
    document.getElementById('editTemoignageForm').addEventListener('submit', function () {
        var f = this;
        f.querySelector('[name="auteur"]').value = document.getElementById('t_auteur_input').value;
        f.querySelector('[name="role"]').value = document.getElementById('t_role_input').value;
        f.querySelector('[name="texte_fr"]').value = document.getElementById('t_texte_fr_input').value;
        f.querySelector('[name="texte_ar"]').value = document.getElementById('t_texte_ar_input').value;
        f.querySelector('[name="note"]').value = document.getElementById('t_note_input').value;
        f.querySelector('[name="actif"]').value = document.getElementById('t_actif_input').checked ? '1' : '0';
    });

    // ── Partenaire Edit Modal ──
    window.editPartenaire = function (id, nom, url, logo, ordre, actif) {
        document.getElementById('editPartenaireForm').action = <?= json_encode(url('admin/landing/partenaires/0/update')) ?>.replace('/0/', '/' + id + '/');
        document.getElementById('p_nom_input').value = nom || '';
        document.getElementById('p_url_input').value = url || '';
        document.getElementById('p_ordre_input').value = ordre || 0;
        document.getElementById('p_actif_input').checked = !!actif;
        var preview = document.getElementById('p_logo_preview');
        if (logo) { preview.style.display = 'block'; preview.querySelector('img').src = logo; } else { preview.style.display = 'none'; }
        new bootstrap.Modal(document.getElementById('editPartenaireModal')).show();
    };
    document.getElementById('editPartenaireForm').addEventListener('submit', function () {
        var f = this;
        f.querySelector('[name="nom"]').value = document.getElementById('p_nom_input').value;
        f.querySelector('[name="url"]').value = document.getElementById('p_url_input').value;
        f.querySelector('[name="ordre"]').value = document.getElementById('p_ordre_input').value;
        f.querySelector('[name="actif"]').value = document.getElementById('p_actif_input').checked ? '1' : '0';
    });

})();
</script>

<style>
.wh-tab-pane.d-none { display: none; }
.wh-sortable-item {
    cursor: grab;
    background: #fff;
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.wh-sortable-item:hover { background: #f8fafc; }
.wh-sortable-item.wh-sortable-dragging { opacity: 0.4; transform: scale(0.98); }
.wh-sortable-handle { cursor: grab; color: #94a3b8; }
.wh-preview-frame { width: 100%; height: 75vh; border: 0; display: block; background: #fff; }
.section-item.dragging { opacity: 0.5; transform: scale(0.98); }
</style>
