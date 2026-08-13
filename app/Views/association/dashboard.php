<?php
/** @var array $association @var array $stats @var array $historique @var array $evaluations */
use App\Helpers\I18n;

$title = __('common.dashboard');
$page  = 'association.dashboard';
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
        'termine'                => 'badge-green',
        'refuse'                => 'badge-red',
        default                 => 'badge-gray',
    };
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="wh-page-title-icon green"><i class="mdi mdi-view-dashboard-outline"></i></div>
            <div>
                <h1 class="wh-page-title"><?= e(__('common.dashboard')) ?></h1>
                <p class="wh-page-sub">
                    <?= e(($association['nom'] ?? 'Association')) ?> — <?= $isAr ? 'مراقبة نشاطات الجمعية' : 'Suivi de l\'activité de votre association' ?>
                </p>
                <div class="mt-1"><?= association_badge($association) ?></div>
                <?php if (($stats['avg_note'] ?? 0) > 0): ?>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fs-6">
                            <?= str_repeat('★', (int) round((float) $stats['avg_note'])) ?><?= str_repeat('☆', 5 - (int) round((float) $stats['avg_note'])) ?>
                        </span>
                        <span class="wh-text-muted small">
                            <strong><?= e(number_format((float) $stats['avg_note'], 1)) ?></strong> / 5
                            — <?= $isAr ? 'متوسط التقييم' : 'note moyenne' ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <a class="btn btn-primary" href="<?= url('association/create') ?>">
            <i class="mdi mdi-plus-circle me-1"></i><?= e(__('evenements.create')) ?>
        </a>
    </div>

    <!-- Alerte : action requise (modifications demandées) -->
    <?php if (! empty($attention)): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center gap-2 mb-4" role="alert">
            <i class="mdi mdi-alert-octagon-outline fs-4"></i>
            <div class="flex-grow-1">
                <strong><?= $isAr ? 'إجراء مطلوب' : 'Action requise' ?></strong>
                — <?= count($attention) ?> <?= $isAr ? 'نشاط بانتظار تعديلكم' : (count($attention) > 1 ? 'événements en attente de votre modification' : 'événement en attente de votre modification') ?>
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach (array_slice($attention, 0, 3) as $item): ?>
                        <li>
                            <a href="<?= url('association/' . (int) $item['id']) ?>"><?= e(mb_substr((string) ($item['adresse'] ?? ''), 0, 60)) ?></a>
                            <?php if (! empty($item['motif_refus'])): ?>
                                <span class="text-muted small">— <?= e(mb_substr((string) $item['motif_refus'], 0, 80)) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a class="btn btn-sm btn-outline-warning" href="<?= url('association?statut=MODIFICATION_DEMANDEE') ?>">
                <?= $isAr ? 'عرض الكل' : 'Voir tout' ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- KPIs : compteurs de statuts cliquables -->
    <?php $sc = $statutsCounts ?? []; ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association') ?>">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-star"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($stats['created'] ?? 0) ?></div>
                    <div class="wh-kpi-label">
                        <?= $isAr ? 'المنشأة' : 'Créés' ?>
                        <?php $tc = $trends['created'] ?? null; if ($tc !== null): ?>
                            <?php $diff = (int) ($tc['current'] ?? 0) - (int) ($tc['previous'] ?? 0); ?>
                            <span class="trend <?= $diff > 0 ? 'trend-up' : ($diff < 0 ? 'trend-down' : 'trend-flat') ?>"
                                  title="<?= $isAr ? 'مقارنة بالشهر الماضي' : 'vs mois précédent' ?>">
                                <?= $diff > 0 ? '▲' : ($diff < 0 ? '▼' : '•') ?> <?= abs($diff) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=EN_ATTENTE') ?>">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-clock-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['EN_ATTENTE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'قيد الانتظار' : 'En attente' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=PROGRAMME') ?>">
                <div class="wh-kpi-icon gray"><i class="mdi mdi-calendar-clock"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['PROGRAMME'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مبرمجة' : 'Programmés' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=TERMINE') ?>">
                <div class="wh-kpi-icon green"><i class="mdi mdi-check-circle"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['TERMINE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'منجزة' : 'Terminés' ?></div>
                </div>
            </a>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link wh-kpi-action wh-kpi-attention" href="<?= url('association?statut=MODIFICATION_DEMANDEE') ?>">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-alert-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['MODIFICATION_DEMANDEE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'تعديل مطلوب' : 'Modification demandée' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a class="wh-kpi wh-kpi-link" href="<?= url('association?statut=REFUSE') ?>">
                <div class="wh-kpi-icon red"><i class="mdi mdi-close-circle-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($sc['REFUSE'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مرفوضة' : 'Refusés' ?></div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-check-decagram-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($stats['validated'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'المصدقة' : 'Validés' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon purple"><i class="mdi mdi-account-group-outline"></i></div>
                <div>
                    <div class="wh-kpi-count"><?= (int) ($stats['participants'] ?? 0) ?></div>
                    <div class="wh-kpi-label">
                        <?= $isAr ? 'المشاركون' : 'Participants' ?>
                        <?php $tp = $trends['participants'] ?? null; if ($tp !== null): ?>
                            <?php $diff = (int) ($tp['current'] ?? 0) - (int) ($tp['previous'] ?? 0); ?>
                            <span class="trend <?= $diff > 0 ? 'trend-up' : ($diff < 0 ? 'trend-down' : 'trend-flat') ?>"
                                  title="<?= $isAr ? 'مقارنة بالشهر الماضي' : 'vs mois précédent' ?>">
                                <?= $diff > 0 ? '▲' : ($diff < 0 ? '▼' : '•') ?> <?= abs($diff) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Idées & conseils (suggestions intelligentes selon l'état du compte) -->
    <?php
        $suggestions = [];
        if (($association['email'] ?? '') === '' || ($association['telephone'] ?? '') === '') {
            $suggestions[] = ['icon' => 'mdi-account-edit-outline', 'color' => 'primary',
                'texte' => $isAr ? 'أكمل معلومات الاتصال الخاصة بجمعيتك (البريد الإلكتروني / الهاتف) لضمان التواصل.' : 'Complétez vos coordonnées (email / téléphone) pour que la Wilaya puisse vous joindre.'];

        }
        if (($sc['EN_ATTENTE'] ?? 0) > 0) {
            $suggestions[] = ['icon' => 'mdi-clock-outline', 'color' => 'warning',
                'texte' => $isAr ? 'لديك ' . (int) $sc['EN_ATTENTE'] . ' طلب بانتظار موافقة الوالي، تابع حالة طلباتك.' : (int) $sc['EN_ATTENTE'] . ' demande(s) en attente de décision de la Wilaya — suivez leur avancement.'];
        }
        if (($sc['MODIFICATION_DEMANDEE'] ?? 0) > 0) {
            $suggestions[] = ['icon' => 'mdi-pencil-circle-outline', 'color' => 'danger',
                'texte' => $isAr ? 'طلبات بانتظار تعديلكم: انقر عليها وصحح البيانات المطلوبة ثم أعد إرسالها.' : 'Des événements attendent vos corrections — modifiez-les puis re-soumettez-les à la Wilaya.'];
        }
        if (($sc['VALIDÉ'] ?? 0) > 0) {
            $suggestions[] = ['icon' => 'mdi-calendar-check-outline', 'color' => 'success',
                'texte' => $isAr ? 'أحداث مؤكدة بانتظار تحديد التاريخ والوقت — قم ببرمجتها.' : 'Événement(s) validé(s) en attente de date/heure — programmez-les pour générer le QR code.'];
        }
        if (($sc['PROGRAMME'] ?? 0) > 0) {
            $suggestions[] = ['icon' => 'mdi-qrcode-scan', 'color' => 'info',
                'texte' => $isAr ? 'أحداث مبرمجة: شارك رمز QR مع المشاركين لتسجيل حضورهم.' : 'Événement(s) programmé(s) : partagez le QR code aux participants pour l\'enregistrement.'];
        }
        if (($sc['TERMINE'] ?? 0) > 0) {
            $suggestions[] = ['icon' => 'mdi-star-circle-outline', 'color' => 'purple',
                'texte' => $isAr ? 'أحداث منجزة: لا تنسَ إرسال تقييمك وتقرير المشاركة.' : 'Événement(s) terminé(s) : pensez à évaluer et renseigner le bilan de participation.'];
        }
        if ($suggestions === []) {
            $suggestions[] = ['icon' => 'mdi-lightbulb-on-outline', 'color' => 'primary',
                'texte' => $isAr ? 'ابدأ بإرسال طلب حدث جديد عبر زر "إنشاء حدث".' : 'Commencez par soumettre une nouvelle demande d\'événement via le bouton « Créer un événement ».'];
        }
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center gap-2">
            <i class="mdi mdi-lightbulb-on-outline text-warning"></i>
            <h3 class="h6 mb-0"><?= $isAr ? 'أفكار ونصائح' : 'Idées & conseils' ?></h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($suggestions as $s): ?>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 align-items-start p-2 rounded-3 bg-light">
                            <i class="mdi <?= e($s['icon']) ?> text-<?= e($s['color']) ?>" style="font-size:1.25rem"></i>
                            <span class="small"><?= e($s['texte']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Événements déjà envoyés à la Wilaya -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center gap-2">
            <i class="mdi mdi-send-outline text-primary"></i>
            <h3 class="h6 mb-0"><?= $isAr ? 'الطلبات المرسلة' : 'Événements envoyés' ?></h3>
            <a class="ms-auto btn btn-sm btn-outline-primary" href="<?= url('association/events?tab=envoyes') ?>">
                <?= $isAr ? 'عرض الكل' : 'Voir tout' ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($envoyes)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= $isAr ? 'الحدث' : 'Événement' ?></th>
                                <th><?= $isAr ? 'البلدية' : 'Commune' ?></th>
                                <th><?= $isAr ? 'أُرسل في' : 'Envoyé le' ?></th>
                                <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($envoyes as $ev): ?>
                                <tr>
                                    <td><?= e(mb_substr((string) ($ev['description'] ?? ''), 0, 50)) ?: 'Événement #' . (int) $ev['id'] ?></td>
                                    <td><?= e($ev['commune_nom'] ?? '-') ?></td>
                                    <td class="text-nowrap"><?= e(date('d/m/Y', strtotime((string) ($ev['created_at'] ?? 'now')))) ?></td>
                                    <td>
                                        <span class="wh-badge <?= $badgeColor((string) ($ev['statut'] ?? 'EN_ATTENTE')) ?>">
                                            <?= e(statut_label((string) ($ev['statut'] ?? 'EN_ATTENTE'))) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= url('association/' . (int) $ev['id']) ?>">
                                            <i class="mdi mdi-eye me-1"></i><?= e(__('common.detail')) ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0"><?= $isAr ? 'لا توجد طلبات مرسلة بعد.' : 'Aucun événement envoyé pour le moment.' ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historique des actions -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center gap-2">
            <i class="mdi mdi-history text-primary"></i>
            <h3 class="h6 mb-0"><?= $isAr ? 'النشاط الأخير' : 'Historique récent' ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($historique)): ?>
                <div class="wh-timeline">
                    <?php foreach ($historique as $h): ?>
                        <div class="wh-timeline-item">
                            <span class="wh-timeline-marker <?= $badgeColor((string) ($h['nouveau_statut'] ?? 'EN_ATTENTE')) ?>"
                                  title="<?= e(statut_label((string) ($h['nouveau_statut'] ?? 'EN_ATTENTE'))) ?>"></span>
                            <div class="wh-timeline-content">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="badge <?= $badgeColor((string) ($h['nouveau_statut'] ?? 'EN_ATTENTE')) ?>">
                                        <?= e(statut_label((string) ($h['nouveau_statut'] ?? 'EN_ATTENTE'))) ?>
                                    </span>
                                    <span class="wh-timeline-date">
                                        <i class="mdi mdi-clock-outline"></i>
                                        <?= e(date('d/m/Y H:i', strtotime((string) ($h['created_at'] ?? 'now')))) ?>
                                    </span>
                                </div>
                                <div class="fw-semibold"><?= e($h['action'] ?? '') ?></div>
                                <div class="wh-timeline-meta">
                                    <i class="mdi mdi-map-marker"></i>
                                    <?= e(mb_substr((string) ($h['adresse'] ?? '-'), 0, 60)) ?>
                                    <?php if (! empty($h['acteur_nom']) && ! str_contains((string) ($h['action'] ?? ''), 'Wilaya')): ?>
                                        <span class="wh-timeline-meta-sep">•</span>
                                        <i class="mdi mdi-account-circle"></i>
                                        <?= e($h['acteur_nom']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted"><?= $isAr ? 'لا توجد أنشطة حديثة.' : 'Aucune activité récente.' ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Évaluations -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex align-items-center gap-2">
            <i class="mdi mdi-star-circle text-warning"></i>
            <h3 class="h6 mb-0"><?= $isAr ? 'التقييمات الأخيرة' : 'Évaluations récentes' ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($evaluations)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= $isAr ? 'الحدث' : 'Événement' ?></th>
                                <th><?= $isAr ? 'المقيّم' : 'Évalué par' ?></th>
                                <th class="text-center"><?= $isAr ? 'التقييم' : 'Note' ?></th>
                                <th><?= $isAr ? 'التعليقات' : 'Commentaires' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $ev): ?>
                                <tr>
                                    <td><?= e(mb_substr((string) ($ev['adresse'] ?? ''), 0, 40)) ?></td>
                                    <td>
                                        <?= e(($ev['evaluateur_prenom'] ?? '') . ' ' . ($ev['evaluateur_nom'] ?? '')) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">
                                            <?= str_repeat('★', (int) ($ev['note'] ?? 0)) ?><?= str_repeat('☆', 5 - (int) ($ev['note'] ?? 0)) ?>
                                        </span>
                                    </td>
                                    <td><?= e(mb_substr((string) ($ev['description'] ?? ''), 0, 60)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted"><?= $isAr ? 'لا توجد تقييمات حتى الآن.' : 'Aucune évaluation pour le moment.' ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.wh-kpi.purple .wh-kpi-icon {
    background: rgba(168, 85, 247, 0.2);
    color: #a855f7;
}
</style>