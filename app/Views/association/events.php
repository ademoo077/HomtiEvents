<?php
/** @var array $filters @var array $evenements @var int $page $lastPage $total @var string $onglet @var array $tabCounts */
use App\Helpers\I18n;

$title = __('evenements.mes_evenements');
$page  = 'association.events';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$badgeColor = static function (string $statut): string {
    return match (statut_key($statut)) {
        'en_attente'            => 'badge-amber',
        'modification_demandee' => 'badge-amber',
        'valide'                => 'badge-blue',
        'programme'             => 'badge-cyan',
        'qr_genere'             => 'badge-violet',
        'en_cours'              => 'badge-blue',
        'termine'               => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};

$tabs = [
    'envoyes'   => ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE', 'VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE'],
    'pending'   => ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE'],
    'validated' => ['VALIDÉ'],
    'programme' => ['PROGRAMME', 'QR_GENERE'],
    'termine'   => ['EN_COURS', 'TERMINE'],
];
$tabLabels = [
    'all'       => $isAr ? 'الكل' : 'Tous',
    'envoyes'   => $isAr ? 'المُرسَلة' : 'Envoyés',
    'pending'   => $isAr ? 'في الانتظار' : 'En attente',
    'validated' => $isAr ? 'تمت الموافقة' : 'Validés',
    'programme' => $isAr ? 'مبرمج' : 'Programmés',
    'termine'   => $isAr ? 'مُنجَز' : 'Terminés',
];
?>
<div class="wh-page">
    <style>
        .wh-card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .wh-card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12) !important;
        }
        .wh-recent-badge {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
    <div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('evenements.mes_evenements')) ?></h1>
            <p class="wh-page-sub"><?= e($isAr ? 'نشاطاتك' : 'Suivi de vos demandes et événements') ?></p>
        </div>
        <a class="btn btn-primary" href="<?= url('association/create') ?>">
            <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
        </a>
    </div>

    <!-- Barre de recherche + onglets de filtre -->
    <form method="get" action="<?= url('association/events') ?>" class="row g-3 align-items-end mb-3">
        <div class="col-md-4">
            <label class="form-label"><?= e(__('common.search')) ?></label>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" class="form-control"
                   placeholder="<?= e($isAr ? 'ابحث ب العنوان أو الفئة' : 'Titre, catégorie…') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= e(__('common.status')) ?></label>
            <nav class="nav btn-group flex-wrap wh-btn-group">
                <?php foreach ($tabLabels as $k => $label): ?>
                    <a href="<?= url('association/events') . '?tab=' . $k . ($filters['q'] !== '' ? '&q=' . urlencode($filters['q']) : '') ?>"
                       class="btn btn-outline-secondary <?= $k === $onglet ? 'active' : '' ?>">
                        <?= e($label) ?>
                        <?php $nb = $tabCounts[$k] ?? 0; ?>
                        <?php if ($nb > 0): ?>
                            <span class="badge bg-<?= $k === $onglet ? 'light text-dark' : 'secondary' ?> ms-1"><?= $nb ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary"><?= e(__('common.search')) ?></button>
        </div>
    </form>

    <!-- Cartes événements -->
    <?php if (empty($evenements)): ?>
        <div class="wh-empty card shadow-sm py-5">
            <div class="card-body text-center">
                <i class="mdi mdi-calendar-blank-multiple text-muted" style="font-size: 2rem"></i>
                <p class="mb-2 mt-2"><?= e($isAr ? 'لا توجد نشاطات' : 'Aucun événement dans cette catégorie.') ?></p>
                <a href="<?= url('association/create') ?>" class="btn btn-sm btn-primary">
                    <i class="mdi mdi-plus me-1"></i><?= e(__('evenements.create')) ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($evenements as $e): ?>
                <?php
                    $statut = (string) ($e['statut'] ?? 'EN_ATTENTE');
                    $estValide = in_array($statut, ['VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'QR_GENERE', 'EN_COURS', 'TERMINE'], true);
                    $recemment = isset($e['updated_at']) && strtotime((string) $e['updated_at']) >= strtotime('-24 hours');
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm wh-card-hover">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <span class="wh-badge <?= $badgeColor($statut) ?>">
                                    <?php
                                        $icône = match ($statut) {
                                            'EN_ATTENTE', 'MODIFICATION_DEMANDEE' => 'mdi-clock-outline',
                                            'VALIDÉ' => 'mdi-check-circle',
                                            'PROGRAMME', 'QR_GENERE' => 'mdi-qrcode',
                                            'EN_COURS' => 'mdi-play-circle',
                                            'TERMINE' => 'mdi-check-circle-check',
                                            'REFUSE' => 'mdi-close-circle',
                                            default => 'mdi-information',
                                        };
                                    ?><i class="mdi <?= $icône ?> me-1"></i>
                                    <?= e(statut_label($statut)) ?>
                                </span>
                                <?php if ($recemment && $statut === 'VALIDÉ'): ?>
                                    <i class="mdi mdi-bell-ring-outline text-success" title="<?= e($isAr ? 'تمت الموافقة مؤخراً' : 'Validé récemment') ?>"
                                       style="font-size: 1.2rem"></i>
                                <?php endif; ?>
                            </div>

                            <h5 class="wh-card-title">
                                <?= e(substr((string) ($e['description'] ?? ''), 0, 60)) ?: 'Événement #' . ((int) $e['id']) ?>
                            </h5>
                            <p class="text-muted small mb-1">
                                <i class="mdi mdi-map-marker me-1"></i><?= e($e['commune_nom'] ?? '-') ?>
                            </p>
                            <?php if ($e['date_evenement']): ?>
                                <p class="text-muted small mb-0">
                                    <i class="mdi mdi-calendar me-1"></i>
                                    <?= e(date('d/m/Y', strtotime((string) $e['date_evenement']))) ?>
                                    <?php if ($e['heure']): ?> · <?= e(substr((string) $e['heure'], 0, 5)) ?><?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($statut === 'VALIDÉ'): ?>
                                <div class="mt-3">
                                    <label class="form-label small mb-1"><?= e(__('evenements.program.date_heure')) ?></label>
                                    <input type="date" class="form-control form-control-sm mb-1 wh-program-date"
                                           data-id="<?= (int) $e['id'] ?>">
                                    <input type="time" class="form-control form-control-sm mb-2 wh-program-heure"
                                           data-id="<?= (int) $e['id'] ?>">
                                </div>
                            <?php endif; ?>

                            <?php if ($statut === 'MODIFICATION_DEMANDEE'): ?>
                                <div class="mt-2 alert alert-warning py-1 mb-0 small">
                                    <i class="mdi mdi-alert-outline me-1"></i>
                                    <?= e($e['motif_refus'] ?? '') ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-auto d-flex gap-2 pt-3">
                                <a href="<?= url('association/' . (int) $e['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-eye me-1"></i><?= e(__('common.detail')) ?>
                                </a>
                                <?php if ($statut === 'VALIDÉ'): ?>
                                    <button type="button" class="btn btn-sm btn-success wh-program-btn"
                                            data-id="<?= (int) $e['id'] ?>">
                                        <i class="mdi mdi-calendar-check me-1"></i><?= e(__('evenements.program.action')) ?>
                                    </button>
                                <?php elseif ($statut === 'MODIFICATION_DEMANDEE'): ?>
                                    <a href="<?= url('association/' . (int) $e['id'] . '/edit') ?>" class="btn btn-sm btn-warning">
                                        <i class="mdi mdi-pencil me-1"></i><?= e(__('common.corriger')) ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (in_array($statut, ['EN_ATTENTE', 'MODIFICATION_DEMANDEE'], true)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger wh-annuler-btn"
                                            data-id="<?= (int) $e['id'] ?>">
                                        <i class="mdi mdi-close-circle me-1"></i><?= e(__('evenements.annuler')) ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($lastPage > 1): ?>
            <nav aria-label="<?= e(__('common.pagination')) ?>">
                <ul class="pagination justify-content-center">
                    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link"
                               href="?tab=<?= $onglet ?>&page=<?= $p ?><?= $filters['q'] !== '' ? '&q=' . urlencode($filters['q']) : '' ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Programmation express depuis la liste : POST vers wilaya/evenements/{id}/programmer
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wh-program-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const date = document.querySelector('.wh-program-date[data-id="' + id + '"]').value;
            const heure = document.querySelector('.wh-program-heure[data-id="' + id + '"]').value;
            if (!date || !heure) { alert('<?= e($isAr ? 'حدد التاريخ والوقت' : 'Veuillez choisir une date et une heure.') ?>'); return; }
            fetch('<?= url('association/events') ?>/' + id + '/programmer', { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body: new URLSearchParams({date_evenement: date, heure: heure, csrf_token: '<?= e(csrf_token()) ?>'}) })
                .then(r => r.text())
                .then(() => location.reload())
                .catch(() => location.reload());
        });
    });
});
    // Annulation d'une demande (EN_ATTENTE / MODIFICATION_DEMANDEE)
    document.querySelectorAll('.wh-annuler-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const motif = prompt('<?= e($isAr ? 'سبب الإلغاء (obligatoire)' : 'Motif de l\'annulation (obligatoire)') ?>');
            if (!motif || !motif.trim()) { alert('<?= e($isAr ? 'الموضوع مطلوب' : 'Le motif est obligatoire.') ?>'); return; }
            if (!confirm('<?= e($isAr ? 'هل أنت متأكد من إلغاء هذه الطلب؟' : 'Confirmer l\'annulation de cette demande ?') ?>')) return;
            fetch('<?= url('association/events') ?>/' + id + '/annuler', { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body: new URLSearchParams({motif: motif.trim(), csrf_token: '<?= e(csrf_token()) ?>'}) })
                .then(r => r.text())
                .then(() => location.reload())
                .catch(() => location.reload());
        });
    });
});
</script>
