<?php
/** @var array $evenements @var array $filters @var int $page @var int $lastPage @var int $total
 *  @var array $communes @var array $associations @var array $epics @var array $anomalies
 *  @var int $totalRequests @var int $pendingRequests @var int $approvedRequests @var int $rejectedRequests
 */
use App\Helpers\I18n;

$pageNum = is_numeric($page ?? null) ? (int) $page : 1;
$title   = __('evenements.title');
$page    = 'wilaya.evenements.index';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$keep = static function (string $key, mixed $value = ''): string {
    if ($value === '') {
        $value = $_GET[$key] ?? '';
    }
    return $value !== '' && $value !== null ? '&' . $key . '=' . urlencode((string) $value) : '';
};

$activeFilters = array_filter($filters, static fn($v) => $v !== '' && $v !== null);
$hasFilters = !empty($activeFilters);

$sortBy = (string) ($filters['sort'] ?? 'created_at');
$sortDir = (($filters['dir'] ?? 'desc') === 'asc') ? 'asc' : 'desc';
$sortUrl = static function (string $col) use ($keep, $sortBy, $sortDir, $hasFilters): string {
    $dir = ($sortBy === $col && $sortDir === 'asc') ? 'desc' : 'asc';
    $qs = url('wilaya/evenements?sort=' . $col . '&dir=' . $dir);
    if ($hasFilters) {
        foreach (['q','statut','commune_id','association_id','epic_id','anomalie_id','du','au'] as $k) {
            $qs .= $keep($k);
        }
    }
    return $qs;
};
$sortIndicator = static function (string $col) use ($sortBy, $sortDir): string {
    if ($sortBy !== $col) {
        return '<i class="mdi mdi-sort-variant text-muted" style="font-size:.85rem;opacity:.5"></i>';
    }
    return $sortDir === 'asc'
        ? '<i class="mdi mdi-arrow-up" style="font-size:.85rem"></i>'
        : '<i class="mdi mdi-arrow-down" style="font-size:.85rem"></i>';
};
?>

<style>
/* ═══ Evenements Index — Styles spécifiques page ═══ */
/* La structure du hero (radius, padding, déco, overlay) est fournie par
   le composant unifié .wh-hero-panel (admin.css). Ici : surcharge du dégradé,
   des boutons et du filigrane. */
.wh-ev-hero{background:linear-gradient(130deg,#084298 0%,var(--wh-blue) 42%,#0f8a70 100%)}
.wh-ev-hero h1{font-size:1.6rem}
.wh-ev-hero h1 .mdi{font-size:1.35rem}
.wh-ev-hero .btn{border-radius:.65rem;transition:transform .18s var(--wh-ease),box-shadow .18s var(--wh-ease)}
.wh-ev-hero .btn:hover{transform:translateY(-2px)}
.wh-ev-hero .btn-light{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.35);color:#fff;backdrop-filter:blur(6px);font-weight:600}
.wh-ev-hero .btn-light:hover{background:rgba(255,255,255,.28);border-color:rgba(255,255,255,.5);color:#fff}
.wh-ev-hero .btn-warning{background:#fbbf24;border-color:#fbbf24;color:#000;font-weight:700;box-shadow:0 8px 20px -8px rgba(251,191,36,.7)}
.wh-ev-hero .btn-warning:hover{background:#fcd34d;border-color:#fcd34d;color:#000}
.wh-hero-watermark{position:absolute;bottom:-1.1rem;right:1.4rem;font-size:7.5rem;line-height:1;color:rgba(255,255,255,.10);transform:rotate(-8deg);pointer-events:none;user-select:none;z-index:1}
html[dir="rtl"] .wh-hero-watermark{right:auto;left:1.4rem}
@media(max-width:767.98px){.wh-ev-hero h1{font-size:1.2rem}.wh-hero-watermark{font-size:5rem;opacity:.7}}

/* Grille KPI */
.wh-kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:.75rem}

/* Filter bar */
.wh-filter-toggle{display:flex;align-items:center;gap:.5rem;padding:.75rem 1.1rem;background:var(--wh-gray-soft);border-bottom:1px solid var(--wh-border);cursor:pointer;font-weight:600;font-size:.88rem;color:var(--wh-text);user-select:none;transition:background .15s}
.wh-filter-toggle:hover{background:var(--wh-gray-light)}
.wh-filter-toggle .mdi{transition:transform .2s;font-size:1.1rem}
.wh-filter-toggle.open .mdi.mdi-chevron-down{transform:rotate(180deg)}
.wh-filter-body{padding:1rem 1.1rem;display:none}
.wh-filter-body.show{display:block}
.wh-filter-chips{padding:.5rem 1.1rem}

/* Table card */
.wh-table-header h3{font-size:.92rem;font-weight:700;margin:0;display:flex;align-items:center;gap:.5rem}
.wh-table-header h3 .mdi{color:var(--wh-blue);font-size:1.1rem}
.wh-row-ev{transition:background .12s,border-color .12s}
.wh-row-ev:hover{background:var(--wh-gray-soft)!important}
.wh-row-ev td{border-bottom-color:var(--wh-border);vertical-align:middle}
.wh-ev-link{color:var(--wh-text);text-decoration:none;font-weight:600;transition:color .15s}
.wh-row-ev:hover .wh-ev-link{color:var(--wh-blue)}
.wh-actions{display:inline-flex;gap:.2rem;opacity:.4;transition:opacity .15s}
.wh-row-ev:hover .wh-actions{opacity:1}
.wh-id-tag{display:inline-flex;align-items:center;justify-content:center;min-width:30px;padding:.12rem .4rem;border-radius:.35rem;background:var(--wh-gray-soft);color:var(--wh-text-muted);font-size:.7rem;font-weight:700}

/* Date cell */
.wh-dt{font-size:.82rem;color:var(--wh-text-muted);white-space:nowrap}
.wh-dt .mdi{margin-inline-end:.2rem;font-size:.9rem}
@media(max-width:767.98px){.wh-table-header{flex-direction:column;align-items:flex-start;gap:.5rem}}

/* Tri serveur */
.wh-sort-link{display:inline-flex;align-items:center;gap:.3rem;color:var(--wh-text);text-decoration:none;font-weight:600;font-size:.85rem;white-space:nowrap;transition:color .15s}
.wh-sort-link:hover{color:var(--wh-blue)}
table thead th{white-space:nowrap}
table thead th:not(:first-child):not(:last-child){padding-inline-end:.9rem}
</style>

<div class="wh-page">
    <!-- ═══ HERO ═══ -->
    <div class="wh-hero-panel wh-ev-hero mb-4">
        <i class="mdi mdi-calendar-star wh-hero-watermark" aria-hidden="true"></i>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2">
                    <i class="mdi mdi-calendar-star"></i>
                    <?= e(__('evenements.title')) ?>
                </h1>
                <p>
                    <?= (int) $totalEvenements ?> <?= $isAr ? 'حدث مسجل' : 'événement(s) enregistré(s)' ?>
                    <?php if ((int) ($pendingRequests ?? 0) > 0): ?>
                        <span class="d-inline-flex align-items-center gap-1 ms-2" style="background:rgba(255,255,255,.2);padding:.15rem .6rem;border-radius:999px;font-size:.78rem;font-weight:600">
                            <i class="mdi mdi-alert-circle-outline"></i>
                            <?= (int) ($pendingRequests ?? 0) ?> <?= $isAr ? 'قيد المراجعة' : 'en attente' ?>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2" style="position:relative;z-index:1">
                <a class="btn btn-light" href="<?= url('wilaya/evenements/export') ?>">
                    <i class="mdi mdi-download me-1"></i><?= e(__('common.export')) ?>
                </a>
                <a class="btn btn-warning fw-bold" href="<?= url('wilaya/evenements/create') ?>">
                    <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
                </a>
            </div>
        </div>
    </div>

    <!-- ═══ KPI ═══ -->
    <div class="wh-kpi-row mb-4">
        <?= view('partials.kpi_card', ['value' => (int) $totalEvenements, 'label' => $isAr ? 'المجموع' : 'Total', 'icon' => 'mdi-calendar-multiple', 'accent' => 'var(--wh-blue)', 'bg' => 'var(--wh-blue-soft)', 'link' => url('wilaya/evenements')]) ?>
        <?= view('partials.kpi_card', ['value' => (int) $pendingRequests, 'label' => $isAr ? 'قيد الانتظار' : 'En attente', 'icon' => 'mdi-clock-outline', 'accent' => 'var(--wh-amber)', 'bg' => '#fff3cd', 'link' => url('wilaya/evenements?statut=EN_ATTENTE')]) ?>
        <?= view('partials.kpi_card', ['value' => (int) $activeRequests, 'label' => $isAr ? 'جارية' : 'En cours', 'icon' => 'mdi-play-circle-outline', 'accent' => '#22d3ee', 'bg' => '#cff4fc', 'link' => url('wilaya/evenements?statut=EN_COURS')]) ?>
        <?= view('partials.kpi_card', ['value' => (int) $completedRequests, 'label' => $isAr ? 'منجزة' : 'Terminé', 'icon' => 'mdi-check-all', 'accent' => 'var(--wh-green)', 'bg' => 'var(--wh-green-soft)', 'link' => url('wilaya/evenements?statut=TERMINE')]) ?>
        <?= view('partials.kpi_card', ['value' => (int) $rejectedRequests, 'label' => $isAr ? 'مرفوضة' : 'Refusé', 'icon' => 'mdi-close-circle-outline', 'accent' => 'var(--wh-red)', 'bg' => '#f8d7da', 'link' => url('wilaya/evenements?statut=REFUSE')]) ?>
    </div>

    <!-- ═══ FILTRES ═══ -->
    <form method="get" action="<?= url('wilaya/evenements') ?>" class="wh-filter-bar mb-4" id="filterForm">
        <div class="wh-filter-toggle" onclick="document.querySelector('.wh-filter-body').classList.toggle('show');this.classList.toggle('open')">
            <i class="mdi mdi-filter-variant"></i>
            <?= $isAr ? 'البحث والتصفية' : 'Recherche et filtres' ?>
            <?php if ($hasFilters): ?>
                <span class="wh-badge badge-blue"><?= count($activeFilters) ?></span>
            <?php endif; ?>
            <i class="mdi mdi-chevron-down" style="margin-inline-start:auto"></i>
        </div>
        <?php if ($hasFilters): ?>
            <div class="wh-filter-body show">
        <?php else: ?>
            <div class="wh-filter-body">
        <?php endif; ?>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <div class="wh-input-icon-wrap">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" name="q" class="form-control" placeholder="<?= $isAr ? 'بحث عن عنوان، commune...' : 'Rechercher adresse, commune...' ?>"
                               value="<?= e((string) ($filters['q'] ?? '')) ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><i class="mdi mdi-filter-variant me-1"></i><?= $isAr ? 'الحالة' : 'Statut' ?></label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value=""><?= $isAr ? 'الكل' : 'Tous' ?></option>
                        <?php foreach (\App\Helpers\EvenementService::STATUTS as $s): ?>
                            <option value="<?= e($s) ?>" <?= (($filters['statut'] ?? '') === $s) ? 'selected' : '' ?>><?= e(statut_label($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><i class="mdi mdi-map-marker me-1"></i><?= $isAr ? 'البلدية' : 'Commune' ?></label>
                    <select name="commune_id" class="form-select form-select-sm">
                        <option value=""><?= $isAr ? 'الكل' : 'Toutes' ?></option>
                        <?php foreach ($communes as $c): ?>
                            <option value="<?= e((string) $c['id']) ?>" <?= (string) ($filters['commune_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><i class="mdi mdi-handshake me-1"></i><?= $isAr ? 'الجمعية' : 'Association' ?></label>
                    <select name="association_id" class="form-select form-select-sm">
                        <option value=""><?= $isAr ? 'الكل' : 'Toutes' ?></option>
                        <?php foreach ($associations as $a): ?>
                            <option value="<?= e((string) $a['id']) ?>" <?= (string) ($filters['association_id'] ?? '') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><i class="mdi mdi-satellite-variant me-1"></i><?= $isAr ? 'ال EPIC' : 'EPIC' ?></label>
                    <select name="epic_id" class="form-select form-select-sm">
                        <option value=""><?= $isAr ? 'الكل' : 'Toutes' ?></option>
                        <?php foreach ($epics as $ep): ?>
                            <option value="<?= e((string) $ep['id']) ?>" <?= (string) ($filters['epic_id'] ?? '') === (string) $ep['id'] ? 'selected' : '' ?>><?= e($ep['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><i class="mdi mdi-alert-octagon me-1"></i><?= $isAr ? 'الشذوذ' : 'Anomalie' ?></label>
                    <select name="anomalie_id" class="form-select form-select-sm">
                        <option value=""><?= $isAr ? 'الكل' : 'Toutes' ?></option>
                        <?php foreach ($anomalies as $an): ?>
                            <option value="<?= e((string) $an['id']) ?>" <?= (string) ($filters['anomalie_id'] ?? '') === (string) $an['id'] ? 'selected' : '' ?>><?= e($an['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><i class="mdi mdi-calendar-start me-1"></i><?= $isAr ? 'من' : 'Du' ?></label>
                    <input type="date" name="du" class="form-control form-control-sm" value="<?= e((string) ($filters['du'] ?? '')) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.78rem"><i class="mdi mdi-calendar-end me-1"></i><?= $isAr ? 'إلى' : 'Au' ?></label>
                    <input type="date" name="au" class="form-control form-control-sm" value="<?= e((string) ($filters['au'] ?? '')) ?>">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="mdi mdi-magnify me-1"></i><?= $isAr ? 'بحث' : 'Rechercher' ?>
                    </button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= url('wilaya/evenements') ?>" class="btn btn-outline-secondary" title="<?= $isAr ? 'مسح' : 'Réinitialiser' ?>">
                            <i class="mdi mdi-close"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($hasFilters): ?>
            <div class="wh-filter-chips">
                <?php if (($filters['statut'] ?? '') !== ''): ?>
                    <span class="wh-filter-chip"><?= e(statut_label($filters['statut'])) ?></span>
                <?php endif; ?>
                <?php if (($filters['commune_id'] ?? '') !== ''): ?>
                    <span class="wh-filter-chip"><i class="mdi mdi-map-marker"></i> <?= $isAr ? 'بلدية' : 'Commune' ?></span>
                <?php endif; ?>
                <?php if (($filters['association_id'] ?? '') !== ''): ?>
                    <span class="wh-filter-chip"><i class="mdi mdi-handshake"></i> <?= $isAr ? 'جمعية' : 'Association' ?></span>
                <?php endif; ?>
                <?php if (($filters['epic_id'] ?? '') !== ''): ?>
                    <span class="wh-filter-chip"><i class="mdi mdi-satellite-variant"></i> EPIC</span>
                <?php endif; ?>
                <?php if (($filters['du'] ?? '') !== '' || ($filters['au'] ?? '') !== ''): ?>
                    <span class="wh-filter-chip"><i class="mdi mdi-calendar"></i> <?= $isAr ? 'نطاق التاريخ' : 'Période' ?></span>
                <?php endif; ?>
                <?php if (($filters['q'] ?? '') !== ''): ?>
                    <span class="wh-filter-chip"><i class="mdi mdi-magnify"></i> "<?= e($filters['q']) ?>"</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </form>

    <!-- ═══ VIEW TOGGLE + BULK BAR === -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-primary active" id="btnList"><i class="mdi mdi-format-list-bulleted me-1"></i><?= $isAr ? 'قائمة' : 'Liste' ?></button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnKanban"><i class="mdi mdi-view-column me-1"></i>Kanban</button>
        </div>
        <div id="bulkBar" class="d-flex flex-wrap gap-2 align-items-center" style="display:none!important">
            <span class="small text-muted"><span id="bulkCount">0</span> <?= $isAr ? 'محدد' : 'sélectionné(s)' ?></span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bulk="export"><i class="mdi mdi-download me-1"></i>CSV</button>
            <button type="button" class="btn btn-sm btn-outline-warning" data-bulk="modif"><i class="mdi mdi-pencil me-1"></i><?= $isAr ? 'تعديل' : 'Modif.' ?></button>
            <select class="form-select form-select-sm" id="bulkEpic" style="width:auto"><option value=""><?= $isAr ? 'إسناد EPIC' : 'Réaffecter EPIC' ?></option><?php foreach ($epics as $ep): ?><option value="<?= (int)$ep['id'] ?>"><?= e($ep['nom']) ?></option><?php endforeach; ?></select>
            <input type="date" class="form-control form-control-sm" id="bulkDate" style="width:auto">
            <input type="time" class="form-control form-control-sm" id="bulkHeure" value="09:00" style="width:auto">
            <button type="button" class="btn btn-sm btn-success" data-bulk="programmer"><i class="mdi mdi-calendar-check me-1"></i>Programmer</button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bulk="archiver"><i class="mdi mdi-archive me-1"></i><?= e(__('common.archive')) ?></button>
        </div>
    </div>

    <!-- ═══ KANBAN === -->
    <div id="kanbanView" style="display:none">
        <div class="wh-kanban" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.75rem">
            <?php $kanbanCols = ['EN_ATTENTE'=>'#f59e0b','MODIFICATION_DEMANDEE'=>'#f59e0b','VALIDÉ'=>'#0B5ED7','PROGRAMME'=>'#22d3ee','QR_GENERE'=>'#8b5cf6','EN_COURS'=>'#0B5ED7','TERMINE'=>'#198754','REFUSE'=>'#dc3545']; ?>
            <?php foreach ($kanbanCols as $kStat=>$kColor): ?>
                <div class="wh-kanban-col" data-statut="<?= e($kStat) ?>" style="background:var(--wh-gray-soft);border-radius:var(--wh-radius);padding:.6rem;min-height:320px">
                    <div class="fw-bold small d-flex align-items-center gap-2 mb-2" style="color:<?= e($kColor) ?>"><span style="width:10px;height:10px;border-radius:50%;background:<?= e($kColor) ?>;display:inline-block"></span><?= e(statut_label($kStat)) ?> <span class="badge bg-white text-muted border ms-auto kanban-count">0</span></div>
                    <div class="kanban-list" data-statut="<?= e($kStat) ?>" style="min-height:260px;display:flex;flex-direction:column;gap:.5rem"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ TABLE ═══ -->
    <div class="wh-table-card" id="listView">
        <div class="wh-table-header">
            <h3>
                <i class="mdi mdi-format-list-bulleted"></i>
                <?= $isAr ? 'قائمة الأحداث' : 'Liste des événements' ?>
                <span class="wh-badge badge-blue"><?= e($total) ?></span>
            </h3>
            <?php if (!empty($evenements)): ?>
                <span class="text-muted" style="font-size:.78rem">
                    <i class="mdi mdi-file-document-outline me-1"></i>
                    <?= $isAr ? 'صفحة' : 'Page' ?> <?= $pageNum ?>/<?= $lastPage ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" class="form-check-input" data-check-all></th>
                    <th style="width:55px"><a class="wh-sort-link" href="<?= $sortUrl('id') ?>">ID <?= $sortIndicator('id') ?></a></th>
                    <th><a class="wh-sort-link" href="<?= $sortUrl('adresse') ?>"><?= e(__('common.adresse')) ?> <?= $sortIndicator('adresse') ?></a></th>
                    <th><a class="wh-sort-link" href="<?= $sortUrl('commune') ?>"><?= e(__('common.commune')) ?> <?= $sortIndicator('commune') ?></a></th>
                    <th><a class="wh-sort-link" href="<?= $sortUrl('association') ?>"><?= e(__('common.association')) ?> <?= $sortIndicator('association') ?></a></th>
                    <th><a class="wh-sort-link" href="<?= $sortUrl('statut') ?>"><?= e(__('common.status')) ?> <?= $sortIndicator('statut') ?></a></th>
                    <th><a class="wh-sort-link" href="<?= $sortUrl('date_evenement') ?>"><?= e(__('common.date')) ?> <?= $sortIndicator('date_evenement') ?></a></th>
                    <th style="width:100px"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($evenements as $ev): ?>
                    <tr class="wh-row-ev">
                        <td><input type="checkbox" class="form-check-input" data-bulk-id value="<?= e((string) $ev['id']) ?>"></td>
                        <td><span class="wh-id-tag">#<?= (int) $ev['id'] ?></span></td>
                        <td>
                            <a href="<?= url('wilaya/evenements/' . $ev['id']) ?>" class="wh-ev-link"><?= e($ev['adresse']) ?></a>
                            <?php if ((int) ($ev['nb_anomalies'] ?? 0) > 0): ?>
                                <span class="wh-badge badge-red ms-1">
                                    <i class="mdi mdi-alert-octagon"></i> <?= (int) $ev['nb_anomalies'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size:.85rem"><?= e($ev['commune_nom'] ?? '-') ?></span>
                        </td>
                        <td>
                            <?php if (!empty($ev['association_id'])): ?>
                                <a href="<?= url('wilaya/associations/' . (int) $ev['association_id']) ?>" class="text-decoration-none" style="font-size:.85rem;font-weight:500">
                                    <?= e($ev['association_nom'] ?? '-') ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:.85rem">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= view('partials.status_badge', ['statut' => $ev['statut'], 'icon' => true]) ?>
                        </td>
                        <td>
                            <?php if ($ev['date_evenement']): ?>
                                <div class="wh-dt">
                                    <i class="mdi mdi-calendar-outline"></i>
                                    <?= e(date('d/m/Y', strtotime((string) $ev['date_evenement']))) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="wh-actions">
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('wilaya/evenements/' . $ev['id']) ?>" title="<?= e(__('common.view')) ?>">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('wilaya/evenements/' . $ev['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                    <i class="mdi mdi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('wilaya/evenements/' . $ev['id'] . '/archiver') ?>" data-confirm="<?= e(__('common.archive')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.archive')) ?>">
                                        <i class="mdi mdi-archive"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($evenements === []): ?>
                    <tr>
                        <td colspan="8">
                            <div class="wh-empty">
                                <i class="mdi mdi-calendar-remove"></i>
                                <p class="mb-1 fw-semibold"><?= $isAr ? 'لا توجد أحداث' : 'Aucun événement trouvé' ?></p>
                                <p style="font-size:.8rem;color:var(--wh-text-muted)">
                                    <?= $isAr ? 'جرّب تغيير معايير البحث' : 'Essayez de modifier vos critères de recherche' ?>
                                </p>
                                <a href="<?= url('wilaya/evenements/create') ?>" class="btn btn-sm btn-primary mt-2">
                                    <i class="mdi mdi-plus me-1"></i><?= $isAr ? 'إنشاء حدث' : 'Créer un événement' ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ PRESETS + JS === -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
    (function(){
        var isAr=document.documentElement.dir==='rtl';
        // presets
        var presets={en_retard:"retard=1", sans_epic:"sans_epic=1", aujourdhui:"du="+new Date().toISOString().slice(0,10)+"&au="+new Date().toISOString().slice(0,10)};        var bar=document.createElement('div'); bar.className='d-flex flex-wrap gap-1 mb-2';
        bar.innerHTML='<button type="button" class="btn btn-sm btn-outline-warning" data-preset="en_retard"><i class="mdi mdi-alert me-1"></i>'+(isAr?'متأخر': 'En retard')+'</button>'
            +'<button type="button" class="btn btn-sm btn-outline-info" data-preset="sans_epic"><i class="mdi mdi-satellite-variant me-1"></i>'+(isAr?'بدون EPIC':'Sans EPIC')+'</button>'
            +'<button type="button" class="btn btn-sm btn-outline-success" data-preset="aujourdhui"><i class="mdi mdi-calendar-today me-1"></i>'+(isAr?'اليوم':'Aujourd\'hui')+'</button>'
            +'<button type="button" class="btn btn-sm btn-outline-secondary" id="savePreset"><i class="mdi mdi-content-save me-1"></i>'+(isAr?'حفظ الفلتر':'Sauver filtre')+'</button>'
            +'<span id="savedPresets" class="d-flex gap-1"></span>';
        var form=document.getElementById('filterForm'); if(form) form.parentNode.insertBefore(bar, form);
        try{ var sp=JSON.parse(localStorage.getItem('wh_presets')||'[]'); var c=document.getElementById('savedPresets'); sp.forEach(function(p){ var b=document.createElement('button'); b.className='btn btn-sm btn-outline-primary'; b.textContent=p.name; b.onclick=function(){ location.href='<?= url('wilaya/evenements') ?>?'+p.qs; }; c.appendChild(b); }); }catch(e){}
        bar.querySelectorAll('[data-preset]').forEach(function(b){ b.addEventListener('click',function(){ var k=b.getAttribute('data-preset'); location.href='<?= url('wilaya/evenements') ?>?'+presets[k]; });});
        document.getElementById('savePreset')?.addEventListener('click',function(){ var name=prompt(isAr?'اسم الفلتر':'Nom du preset'); if(!name) return; var qs=new URLSearchParams(new FormData(form)).toString(); try{ var arr=JSON.parse(localStorage.getItem('wh_presets')||'[]'); arr.push({name:name,qs:qs}); localStorage.setItem('wh_presets',JSON.stringify(arr)); location.reload(); }catch(e){} });

        // ── Recherche instantanée (debounce 400 ms) sur le champ q ──
        var qInput = form ? form.querySelector('input[name="q"]') : null;
        if (qInput) {
            var debounceTimer = null;
            qInput.addEventListener('input', function () {
                var value = qInput.value;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    // S'applique uniquement si d'autres filtres importants ne changent pas en direct.
                    form.submit();
                }, 400);
            });
        }

        // view toggle
        var btnList=document.getElementById('btnList'), btnKanban=document.getElementById('btnKanban');
        var listView=document.getElementById('listView'), kanbanView=document.getElementById('kanbanView');
        function setView(v){ if(v==='kanban'){ listView.style.display='none'; kanbanView.style.display=''; btnKanban.classList.add('active'); btnKanban.classList.remove('btn-outline-primary'); btnKanban.classList.add('btn-primary'); btnList.classList.remove('active'); btnList.classList.add('btn-outline-primary'); btnList.classList.remove('btn-primary'); localStorage.setItem('wh_ev_view','kanban'); renderKanban(); } else { listView.style.display=''; kanbanView.style.display='none'; btnList.classList.add('active'); btnList.classList.add('btn-primary'); btnList.classList.remove('btn-outline-primary'); btnKanban.classList.remove('active'); btnKanban.classList.add('btn-outline-primary'); btnKanban.classList.remove('btn-primary'); localStorage.setItem('wh_ev_view','list'); } }
        btnList?.addEventListener('click',function(){ setView('list'); }); btnKanban?.addEventListener('click',function(){ setView('kanban'); });
        try{ if(localStorage.getItem('wh_ev_view')==='kanban') setView('kanban'); }catch(e){}

        // bulk
        var checks=document.querySelectorAll('[data-bulk-id]'), all=document.querySelector('[data-check-all]'), bulkBar=document.getElementById('bulkBar'), bulkCount=document.getElementById('bulkCount');
        function updBulk(){ var sel=[...checks].filter(c=>c.checked); bulkBar.style.display= sel.length? '' : 'none'; bulkBar.style.setProperty('display', sel.length?'flex':'none','important'); if(bulkCount) bulkCount.textContent=sel.length; }
        checks.forEach(c=>c.addEventListener('change',updBulk)); all?.addEventListener('change',function(){ checks.forEach(c=>c.checked=all.checked); updBulk(); });

        function bulkIds(){ return [...checks].filter(c=>c.checked).map(c=>c.value); }
        async function postBulk(action, extra={}){
            var ids=bulkIds(); if(!ids.length) return alert(isAr?'حدد أحداثاً':'Sélectionnez des événements');
            var fd=new FormData(); fd.append('_token', window.WH_CSRF); fd.append('action', action); ids.forEach(id=>fd.append('ids[]', id));
            Object.entries(extra).forEach(([k,v])=> fd.append(k, v));
            var r=await fetch('<?= url('wilaya/evenements/bulk') ?>',{method:'POST', headers:{'X-CSRF-TOKEN': window.WH_CSRF}, body: fd}); if(r.ok) location.reload(); else alert('Erreur bulk');
        }
        document.querySelector('[data-bulk="export"]')?.addEventListener('click',function(){ var ids=bulkIds().join(','); if(!ids) return; location.href='<?= url('wilaya/evenements/export') ?>?ids='+encodeURIComponent(ids); });
        document.querySelector('[data-bulk="archiver"]')?.addEventListener('click',function(){ if(confirm(isAr?'أرشفة المحدد؟':'Archiver la sélection ?')) postBulk('archiver'); });
        document.querySelector('[data-bulk="modif"]')?.addEventListener('click',function(){ var motif=prompt(isAr?'سبب التعديل':'Motif modifications'); if(motif) postBulk('modification', {motif:motif}); });
        document.querySelector('[data-bulk="programmer"]')?.addEventListener('click',function(){
            var d=document.getElementById('bulkDate')?.value, h=document.getElementById('bulkHeure')?.value||'09:00';
            if(!d) return alert(isAr?'اختر تاريخاً':'Choisissez une date');
            if(!bulkIds().length) return alert(isAr?'حدد أحداثاً':'Sélectionnez des événements');
            var epicVal=document.getElementById('bulkEpic')?.value;
            var extra={date:d, heure:h}; if(epicVal) extra['epics[]']=epicVal;
            postBulk('programmer', extra);
        });
        // EPIC réaffectation bulk
        document.getElementById('bulkEpic')?.addEventListener('change',function(){
            var v=this.value; if(!v) return; if(!confirm(isAr?'إسناد EPIC للمحدد؟':'Réaffecter EPIC à la sélection ?')) return;
            if(!bulkIds().length) return alert(isAr?'حدد أحداثاً':'Sélectionnez des événements');
            postBulk('epic', {'epics[]': v});
        });

        // kanban render + drag
        var eventsData=<?= json_encode(array_map(function($ev){ return ['id'=>$ev['id'],'adresse'=>$ev['adresse'],'commune'=>$ev['commune_nom'],'statut'=>$ev['statut'],'assos'=>$ev['association_nom']]; }, $evenements), JSON_UNESCAPED_UNICODE) ?>;
        function renderKanban(){
            var cols=document.querySelectorAll('.kanban-list'); cols.forEach(c=>c.innerHTML='');
            eventsData.forEach(function(ev){
                var card=document.createElement('div'); card.className='card border-0 shadow-sm p-2'; card.style.cursor='grab'; card.draggable=true; card.dataset.id=ev.id;
                card.innerHTML='<div class="fw-semibold small text-truncate">'+ev.adresse+'</div><div class="small text-muted">'+(ev.commune||'')+(ev.assos?' · '+ev.assos:'')+'</div><span class="badge bg-light border text-muted" style="font-size:.65rem">#'+ev.id+'</span>';
                var col=document.querySelector('.kanban-list[data-statut="'+ev.statut+'"]'); if(col) col.appendChild(card); else document.querySelector('.kanban-list[data-statut="EN_ATTENTE"]')?.appendChild(card);
            });
            document.querySelectorAll('.kanban-list').forEach(function(list){
                new Sortable(list,{group:'kanban', animation:150, onEnd: async function(evt){
                    var id=evt.item.dataset.id, newStatut=evt.to.getAttribute('data-statut');
                    var oldStatut=evt.from.getAttribute('data-statut');
                    if(newStatut===oldStatut) return;
                    var fd=new FormData(); fd.append('_token', window.WH_CSRF); fd.append('statut', newStatut); fd.append('motif', '');
                    var r=await fetch('<?= url('wilaya/evenements') ?>/'+id+'/statut',{method:'POST', headers:{'X-CSRF-TOKEN': window.WH_CSRF}, body: fd});
                    if(!r.ok){ var t=await r.text(); alert(isAr?'انتقال غير مسموح':'Transition interdite'); evt.from.appendChild(evt.item); }
                    else { evt.item.style.opacity='.6'; setTimeout(()=>location.reload(),400); }
                }});
            });
            document.querySelectorAll('.wh-kanban-col').forEach(function(col){
                var s=col.getAttribute('data-statut'); var cnt=col.querySelector('.kanban-list')?.children.length||0; var b=col.querySelector('.kanban-count'); if(b) b.textContent=cnt;
            });
        }
    })();
    </script>

    <!-- ═══ PAGINATION ═══ -->
    <?php if ($lastPage > 1): ?>
    <nav class="d-flex justify-content-center mt-4" aria-label="Pagination">
        <ul class="pagination">
            <?php if ($pageNum > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url('wilaya/evenements?page=' . ($pageNum - 1) . $keep('statut') . $keep('commune_id') . $keep('association_id') . $keep('epic_id') . $keep('anomalie_id') . $keep('du') . $keep('au') . $keep('q') . $keep('sort') . $keep('dir')) ?>">
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                <li class="page-item <?= $i === $pageNum ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('wilaya/evenements?page=' . $i . $keep('statut') . $keep('commune_id') . $keep('association_id') . $keep('epic_id') . $keep('anomalie_id') . $keep('du') . $keep('au') . $keep('q') . $keep('sort') . $keep('dir')) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($pageNum < $lastPage): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url('wilaya/evenements?page=' . ($pageNum + 1) . $keep('statut') . $keep('commune_id') . $keep('association_id') . $keep('epic_id') . $keep('anomalie_id') . $keep('du') . $keep('au') . $keep('q') . $keep('sort') . $keep('dir')) ?>">
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
