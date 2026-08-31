<?php
/** @var array $association @var array $score @var array $derniersEvenements @var array $membres */
use App\Helpers\I18n;

$title = $association['nom'] ?? 'Association';
$page  = 'wilaya.evenements';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$statutBadge = static function (string $statut): string {
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

$scoreClass = $score['score'] >= 80 ? 'wh-score-excellent' : ($score['score'] >= 60 ? 'wh-score-good' : ($score['score'] >= 40 ? 'wh-score-average' : 'wh-score-poor'));
?>
<div class="wh-page">
    <div class="wh-hero" style="background: linear-gradient(135deg, #0B5ED7 0%, #198754 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text d-flex align-items-start gap-2">
                    <a href="<?= url('wilaya/evenements') ?>" class="wh-icon-btn wh-icon-btn-light"><i class="mdi mdi-arrow-left"></i></a>
                    <div>
                        <h1 class="wh-hero-title"><i class="mdi mdi-office-building me-2"></i><?= e($association['nom'] ?? '') ?></h1>
                        <p class="wh-hero-sub">
                            <i class="mdi mdi-map-marker-outline me-1"></i><?= e($association['commune_nom'] ?? '—') ?>
                            — <?= e($association['ca_id'] ?? '') ?>
                        </p>
                    </div>
                </div>
                <div class="wh-hero-actions">
                    <?php if (!empty($association['valide'])): ?>
                        <span class="wh-badge badge-green"><i class="mdi mdi-check-circle me-1"></i><?= $isAr ? 'موثقة' : 'Validée' ?></span>
                    <?php else: ?>
                        <span class="wh-badge badge-amber"><i class="mdi mdi-clock-outline me-1"></i><?= $isAr ? 'بانتظار التوثيق' : 'En attente' ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-star"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($association['total_evenements'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'فعالية' : 'Événements' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon green"><i class="mdi mdi-account-group"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($association['membres_actifs'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'عضو' : 'Membres' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon cyan"><i class="mdi mdi-account-multiple"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) ($association['total_participants'] ?? 0) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مشارك' : 'Participants' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wh-kpi">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-star"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= ($association['note_moyenne'] ?? '—') ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'متوسط التقييم' : 'Note moy.' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Score + Infos -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="mdi mdi-information-outline me-2"></i><?= $isAr ? 'معلومات' : 'Informations' ?>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small"><?= $isAr ? 'الاسم' : 'Nom' ?></dt>
                        <dd class="col-sm-7 fw-medium"><?= e($association['nom'] ?? '—') ?></dd>

                        <dt class="col-sm-5 text-muted small"><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></dt>
                        <dd class="col-sm-7">
                            <?php if (!empty($association['email'])): ?>
                                <a href="mailto:<?= e($association['email']) ?>"><?= e($association['email']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </dd>

                        <dt class="col-sm-5 text-muted small"><?= $isAr ? 'الهاتف' : 'Téléphone' ?></dt>
                        <dd class="col-sm-7">
                            <?php if (!empty($association['telephone'])): ?>
                                <a href="tel:<?= e($association['telephone']) ?>"><?= e($association['telephone']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </dd>

                        <dt class="col-sm-5 text-muted small"><?= $isAr ? 'الرقم' : 'N° Agrément' ?></dt>
                        <dd class="col-sm-7"><?= e($association['numero_agrement'] ?? '—') ?></dd>

                        <dt class="col-sm-5 text-muted small"><?= $isAr ? 'تاريخ الإنشاء' : 'Date création' ?></dt>
                        <dd class="col-sm-7"><?= ($association['date_creation'] ?? '') ? e(date('d/m/Y', strtotime((string) $association['date_creation']))) : '—' ?></dd>

                        <dt class="col-sm-5 text-muted small"><?= $isAr ? 'العمادة' : 'Commune' ?></dt>
                        <dd class="col-sm-7"><?= e($association['commune_nom'] ?? '—') ?></dd>

                        <dt class="col-sm-5 text-muted small"><?= $isAr ? 'الرئيس' : 'Président' ?></dt>
                        <dd class="col-sm-7"><?= e($association['nom_prenom_president'] ?? '—') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="mdi mdi-chart-gauge me-2"></i><?= $isAr ? 'مؤشر الأداء' : 'Score de performance' ?>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <div class="wh-score-ring <?= $scoreClass ?> mb-3" style="--score: <?= (int) $score['score'] ?>">
                        <span><?= (int) $score['score'] ?></span>
                    </div>
                    <div class="text-center small text-muted">
                        <div class="mb-1"><?= $isAr ? 'مشاركة' : 'Participation' ?> : <?= (int) $score['details']['participation'] ?>/30</div>
                        <div class="mb-1"><?= $isAr ? 'تقييم' : 'Évaluation' ?> : <?= (int) $score['details']['evaluation'] ?>/30</div>
                        <div class="mb-1"><?= $isAr ? 'إنجاز' : 'Complétion' ?> : <?= (int) $score['details']['completion'] ?>/20</div>
                        <div><?= $isAr ? 'حجم' : 'Volume' ?> : <?= (int) $score['details']['volume'] ?>/20</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="mdi mdi-trophy me-2"></i><?= $isAr ? 'الأعضاء' : 'Top membres' ?>
                </div>
                <div class="card-body p-0">
                    <?php if ($membres === []): ?>
                        <div class="p-3 text-center text-muted small"><?= $isAr ? 'لا يوجد أعضاء' : 'Aucun membre' ?></div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach (array_slice($membres, 0, 5) as $m): ?>
                                <li class="list-group-item d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="wh-avatar wh-avatar-sm">
                                            <?= e(mb_strtoupper(mb_substr((string) ($m['prenom'] ?? ''), 0, 1) . mb_substr((string) ($m['nom'] ?? ''), 0, 1))) ?>
                                        </span>
                                        <div>
                                            <div class="fw-medium small"><?= e(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? '')) ?></div>
                                            <div class="text-muted" style="font-size:.7rem"><?= e($m['email'] ?? '') ?></div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary-subtle text-primary"><?= (int) ($m['participations'] ?? 0) ?></span>
                                        <span class="badge bg-warning-subtle text-warning ms-1"><?= (int) ($m['points'] ?? 0) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Derniers événements -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-semibold">
            <i class="mdi mdi-calendar-star me-2"></i><?= $isAr ? 'آخر الفعاليات' : 'Derniers événements' ?>
            <span class="badge bg-secondary ms-2"><?= count($derniersEvenements) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if ($derniersEvenements === []): ?>
                <div class="p-4 text-center text-muted"><?= $isAr ? 'لا توجد فعاليات' : 'Aucun événement' ?></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= $isAr ? 'العنوان' : 'Adresse' ?></th>
                                <th><?= $isAr ? 'البلدية' : 'Commune' ?></th>
                                <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                                <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                                <th><?= $isAr ? 'المشاركون' : 'Participants' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($derniersEvenements as $ev): ?>
                                <tr>
                                    <td><a href="<?= url('wilaya/evenements/' . (int) $ev['id']) ?>" class="fw-medium">#<?= (int) $ev['id'] ?></a></td>
                                    <td><?= e(mb_substr((string) ($ev['adresse'] ?? ''), 0, 50)) ?></td>
                                    <td class="text-muted small"><?= e($ev['commune_nom'] ?? '—') ?></td>
                                    <td class="text-muted small"><?= ($ev['date_evenement'] ?? '') ? e(date('d/m/Y', strtotime((string) $ev['date_evenement']))) : '—' ?></td>
                                    <td><span class="wh-badge <?= $statutBadge((string) ($ev['statut'] ?? '')) ?>"><?= e(statut_label((string) ($ev['statut'] ?? ''))) ?></span></td>
                                    <td class="text-muted small"><?= (int) ($ev['participants'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tous les membres -->
    <?php if ($membres !== []): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold">
            <i class="mdi mdi-account-group me-2"></i><?= $isAr ? 'جميع الأعضاء' : 'Tous les membres' ?>
            <span class="badge bg-secondary ms-2"><?= count($membres) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                            <th><?= $isAr ? 'البريد' : 'Email' ?></th>
                            <th><?= $isAr ? 'الهاتف' : 'Tél.' ?></th>
                            <th><?= $isAr ? 'المشاركات' : 'Participations' ?></th>
                            <th><?= $isAr ? 'النقاط' : 'Points' ?></th>
                            <th><?= $isAr ? 'الحالة' : 'État' ?></th>
                            <th><?= $isAr ? 'آخر دخول' : 'Dernière connexion' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membres as $m): ?>
                            <tr>
                                <td class="fw-medium"><?= e(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? '')) ?></td>
                                <td class="text-muted small"><?= e($m['email'] ?? '') ?></td>
                                <td class="text-muted small"><?= e($m['telephone'] ?? '—') ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= (int) ($m['participations'] ?? 0) ?></span></td>
                                <td><span class="badge bg-warning-subtle text-warning"><?= (int) ($m['points'] ?? 0) ?></span></td>
                                <td>
                                    <?php if ($m['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success"><?= $isAr ? 'نشط' : 'Actif' ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= $isAr ? 'غير نشط' : 'Inactif' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= ($m['last_login'] ?? '') ? e(date('d/m/Y H:i', strtotime((string) $m['last_login']))) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem}
.wh-icon-btn-light{color:#fff;border-color:rgba(255,255,255,.5)}.wh-icon-btn-light:hover{background:rgba(255,255,255,.15);color:#fff}
    </style>
</div>
