<?php
/** @var array $items @var array $errors */
$title = 'Actualités & événements';
$page  = 'admin.landing.news';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';

$nbPublie   = 0;
$nbBrouillon = 0;
$nbEvenement = 0;
$nbActualite = 0;
foreach ($items as $it) {
    if (($it['statut'] ?? '') === 'publie') { $nbPublie++; } else { $nbBrouillon++; }
    if (($it['type'] ?? '') === 'evenement') { $nbEvenement++; } else { $nbActualite++; }
}
?>
    <div class="wh-page">

    <div style="background:linear-gradient(135deg,#0B5ED7 0%,#198754 60%,#6610f2 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-newspaper"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= $isAr ? 'الأخبار والأحداث القادمة' : 'Actualités & événements à venir' ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= count($items) ?> <?= $isAr ? 'عنصر' : 'éléments' ?></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light" href="<?= url('actualites') ?>" target="_blank">
                    <i class="mdi mdi-eye me-1"></i><?= $isAr ? 'رؤية على الموقع' : 'Voir sur le site' ?>
                </a>
                <a class="btn btn-warning fw-bold" href="<?= url('admin/landing/news/create') ?>">
                    <i class="mdi mdi-plus me-1"></i><?= e(__('common.create')) ?>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-counter"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= count($items) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'الإجمالي' : 'Total' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon green"><i class="mdi mdi-check-circle"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= $nbPublie ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'منشور' : 'Publiés' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-file-document-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= $nbBrouillon ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مسودة' : 'Brouillons' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon purple"><i class="mdi mdi-calendar-star"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= $nbEvenement ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'أحداث' : 'Événements' ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;">
            <span class="d-flex align-items-center gap-2 fw-bold">
                <span style="width:32px;height:32px;border-radius:8px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);"><i class="mdi mdi-table-large"></i></span>
                <?= $isAr ? 'قائمة العناصر' : 'Liste des éléments' ?>
            </span>
            <span class="wh-badge badge-blue"><?= count($items) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                    <th><?= $isAr ? 'العنوان' : 'Titre' ?></th>
                    <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                    <th><?= $isAr ? 'المكان' : 'Lieu' ?></th>
                    <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                    <th><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr id="news-row-<?= (int) $item['id'] ?>">
                        <td>
                            <span class="wh-badge badge-<?= $item['type'] === 'evenement' ? 'blue' : 'green' ?>">
                                <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?> me-1"></i>
                                <?= $item['type'] === 'evenement' ? ($isAr ? 'حدث' : 'Événement') : ($isAr ? 'خبر' : 'Actualité') ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= e($item['titre_fr']) ?></div>
                            <?php if ($item['titre_ar']): ?>
                                <small class="wh-text-muted"><?= e($item['titre_ar']) ?></small>
                            <?php endif; ?>
                            <?php if ($item['evenement_id']): ?>
                                <div><span class="wh-badge badge-blue"><i class="mdi mdi-link-variant me-1"></i><?= $isAr ? 'مرتبط بحدث #' . (int) $item['evenement_id'] : 'Événement lié #' . (int) $item['evenement_id'] ?></span></div>
                            <?php endif; ?>
                        </td>
                        <td class="wh-text-muted">
                            <?= $item['date_event'] ? e(date('d/m/Y', strtotime((string) $item['date_event']))) : '-' ?>
                        </td>
                        <td class="wh-text-muted"><?= e($item['lieu'] ?? '-') ?></td>
                        <td>
                            <button type="button" class="btn btn-sm wh-toggle-btn <?= ($item['statut'] ?? '') === 'publie' ? 'btn-outline-success' : 'btn-outline-warning' ?>"
                                    data-toggle-url="<?= url('admin/landing/news/' . (int) $item['id'] . '/toggle') ?>"
                                    data-csrf="<?= csrf_token() ?>"
                                    data-id="<?= (int) $item['id'] ?>">
                                <?php if (($item['statut'] ?? '') === 'publie'): ?>
                                    <i class="mdi mdi-check-circle me-1"></i><?= $isAr ? 'منشور' : 'Publié' ?>
                                <?php else: ?>
                                    <i class="mdi mdi-pencil-outline me-1"></i><?= $isAr ? 'مسودة' : 'Brouillon' ?>
                                <?php endif; ?>
                            </button>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('actualites') ?>" target="_blank" title="<?= $isAr ? 'رؤية على الموقع' : 'Voir sur le site' ?>">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/landing/news/' . $item['id'] . '/edit') ?>" title="<?= e(__('common.edit')) ?>">
                                    <i class="mdi mdi-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('admin/landing/news/' . $item['id'] . '/delete') ?>" data-confirm="<?= e(__('common.delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= e(__('common.delete')) ?>">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <tr><td colspan="6"><div class="wh-empty"><i class="mdi mdi-newspaper"></i><p><?= e(__('common.no_data')) ?></p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.wh-toggle-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var url = btn.getAttribute('data-toggle-url');
        var csrf = btn.getAttribute('data-csrf');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.statut === 'publie') {
                btn.className = 'btn btn-sm btn-outline-success wh-toggle-btn';
                btn.innerHTML = '<i class="mdi mdi-check-circle me-1"></i><?= $isAr ? "منشور" : "Publié" ?>';
            } else {
                btn.className = 'btn btn-sm btn-outline-warning wh-toggle-btn';
                btn.innerHTML = '<i class="mdi mdi-pencil-outline me-1"></i><?= $isAr ? "مسودة" : "Brouillon" ?>';
            }
            btn.disabled = false;
        })
        .catch(function () { btn.disabled = false; });
    });
});
</script>

<style>
.wh-kpi { display:flex; align-items:center; gap:1rem; padding:1rem; border-radius:12px; background:#fff; border:1px solid var(--wh-border); box-shadow:var(--wh-shadow); }
.wh-kpi-hover:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.08); transition:all .2s ease; }
.wh-kpi-icon { width:48px; height:48px; border-radius:12px; display:grid; place-items:center; font-size:1.2rem; flex-shrink:0; }
.wh-kpi-icon.blue { background:var(--wh-blue-soft); color:var(--wh-blue); }
.wh-kpi-icon.green { background:var(--wh-green-soft); color:var(--wh-green); }
.wh-kpi-icon.amber { background:#fef3c7; color:var(--wh-amber); }
.wh-kpi-icon.purple { background:#ede9fe; color:#7c3aed; }
.wh-kpi-value { font-size:1.6rem; font-weight:700; line-height:1.2; }
.wh-kpi-label { font-size:.8rem; color:var(--text-muted,#64748b); margin-top:.1rem; }
</style>
