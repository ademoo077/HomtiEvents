<?php
/** @var array|null $association @var array $events @var array $prochains @var array $passes
 *  @var array|null $prochain @var array $suggestions @var array $kpis
 */
use App\Helpers\I18n;

$title = __('members.dashboard_title');
$page  = 'member.dashboard';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$statutBadge = static function (string $statut): string {
    return match (strtolower($statut)) {
        'en_attente' => 'badge bg-secondary',
        'valide', 'programme', 'qr_genere' => 'badge bg-info',
        'en_cours' => 'badge bg-warning',
        'termine' => 'badge bg-success',
        'refuse', 'modification_demandee', 'annule' => 'badge bg-danger',
        default => 'badge bg-secondary',
    };
};

$countdown = static function (?int $jours) use ($isAr): string {
    if ($jours === null) {
        return $isAr ? '—' : '—';
    }
    if ($jours < 0) {
        return $isAr ? 'انتهى' : 'Passé';
    }
    if ($jours === 0) {
        return $isAr ? 'اليوم' : "Aujourd'hui";
    }
    if ($jours === 1) {
        return $isAr ? 'غداً' : 'Demain';
    }
    return ($isAr ? 'في ' : 'Dans ') . $jours . ($isAr ? ' يوم' : ' j');
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('members.dashboard_title')) ?></h1>
            <p class="wh-page-sub">
                <?= e($association['nom'] ?? '') ?> —
                <?= $isAr ? 'فعاليات الجمعية' : 'Événements de votre association' ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($kpis['en_cours'] > 0): ?>
                <span class="badge bg-warning text-dark px-3 py-2">
                    <i class="mdi mdi-broadcast me-1"></i>
                    <?= (int) $kpis['en_cours'] ?> <?= $isAr ? 'فعالية جارية الآن' : 'en cours maintenant' ?>
                </span>
            <?php endif; ?>
            <a href="<?= url('dashboard/scan') ?>" class="btn btn-primary">
                <i class="mdi mdi-qrcode-scan me-1"></i><?= $isAr ? 'مسح QR' : 'Scanner QR' ?>
            </a>
        </div>
    </div>

    <!-- ═══ Stats personnelles ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-calendar-blank-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $kpis['total'] ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مجموع الفعاليات' : 'Événements au total' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon green"><i class="mdi mdi-qrcode-scan"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $mesParticipations ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مشاركاتي' : 'Mes participations' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-star-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $mesPoints ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'نقاطي' : 'Mes points' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi wh-kpi-hover">
                <div class="wh-kpi-icon purple"><i class="mdi mdi-medal-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= count($mesBadges) ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'أوسمتي' : 'Mes badges' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Complétude du profil ═══ -->
    <?php
        $profileFields = [
            !empty($user['prenom']),
            !empty($user['nom']),
            !empty($user['telephone']),
            !empty($user['avatar']),
        ];
        $profileComplete = (int) round((count(array_filter($profileFields)) / count($profileFields)) * 100);
    ?>
    <?php if ($profileComplete < 100): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="position-relative" style="width:48px;height:48px;flex-shrink:0">
                <svg viewBox="0 0 36 36" style="width:48px;height:48px;transform:rotate(-90deg)">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none" stroke="#e5e7eb" stroke-width="3"></path>
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none" stroke="<?= $profileComplete >= 75 ? '#198754' : '#f59e0b' ?>"
                          stroke-width="3" stroke-dasharray="<?= $profileComplete ?>, 100"
                          stroke-linecap="round"></path>
                </svg>
                <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:.7rem;font-weight:700"><?= $profileComplete ?>%</span>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold small"><?= $isAr ? 'اكتمال الملف الشخصي' : 'Complétez votre profil' ?></div>
                <small class="text-muted">
                    <?= $isAr
                        ? ($profileComplete < 50 ? 'أضف معلوماتك الشخصية لتبدو جديداً في الفعاليات.' : 'almost there! أضف معلوماتك المعدة.')
                        : ($profileComplete < 50 ? 'Ajoutez vos informations pour être identifiable lors des événements.' : 'Presque terminé ! Complétez votre profil.') ?>
                </small>
            </div>
            <a href="<?= url('profile') ?>" class="btn btn-sm btn-outline-primary">
                <i class="mdi mdi-pencil me-1"></i><?= $isAr ? 'تعديل' : 'Compléter' ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Badges gagnés ═══ -->
    <?php if ($mesBadges !== []): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="mdi mdi-medal-outline text-warning"></i>
                <h3 class="h6 mb-0"><?= $isAr ? 'أوسمتي' : 'Mes badges' ?></h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($mesBadges as $badge): ?>
                        <div class="wh-badge-card" style="--badge-color: <?= e($badge['couleur'] ?? '#1A4D3E') ?>">
                            <div class="wh-badge-icon">
                                <i class="mdi <?= e($badge['icone'] ?? 'mdi-medal') ?>"></i>
                            </div>
                            <div class="wh-badge-info">
                                <div class="fw-semibold small"><?= e($badge['nom']) ?></div>
                                <small class="text-muted"><?= e($badge['description'] ?? '') ?></small>
                                <small class="text-muted d-block" style="font-size:.68rem">
                                    <?= e(date('d/m/Y', strtotime((string) $badge['date_obtention']))) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Participation récente ═══ -->
    <?php if ($dernieresParticipations !== []): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="mdi mdi-history text-primary"></i>
                <h3 class="h6 mb-0"><?= $isAr ? 'آخر المشاركات' : 'Participations récentes' ?></h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($dernieresParticipations as $p): ?>
                        <li class="list-group-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="wh-kpi-icon green" style="width:38px;height:38px;font-size:1rem;border-radius:.5rem">
                                    <i class="mdi mdi-check"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small"><?= e($p['adresse'] ?? '') ?></div>
                                    <small class="text-muted">
                                        <?= e($p['commune_nom'] ?? '') ?> ·
                                        <?= e(date('d/m/Y', strtotime((string) $p['date_evenement']))) ?>
                                        <?= ! empty($p['heure']) ? ' · ' . e(substr((string) $p['heure'], 0, 5)) : '' ?>
                                    </small>
                                </div>
                                <small class="text-muted text-nowrap">
                                    <i class="mdi mdi-clock-outline me-1"></i>
                                    <?= e(date('d/m H:i', strtotime((string) $p['heure_scan']))) ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Idées & conseils ═══ -->
    <?php if (! empty($suggestions)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light d-flex align-items-center gap-2">
                <i class="mdi mdi-lightbulb-on-outline text-warning"></i>
                <h3 class="h6 mb-0"><?= $isAr ? 'أفكار وتوصيات' : 'Idées & conseils' ?></h3>
            </div>
            <div class="card-body">
                <div class="wh-ideas">
                    <?php foreach (array_slice($suggestions, 0, 5) as $s): ?>
                        <div class="wh-idea">
                            <span class="wh-idea-icon <?= e($s['color'] ?? 'primary') ?>">
                                <i class="mdi <?= e($s['icon']) ?>"></i>
                            </span>
                            <div class="wh-idea-body">
                                <?php if (! empty($s['titre'])): ?>
                                    <div class="wh-idea-title"><?= e($s['titre']) ?></div>
                                <?php endif; ?>
                                <div class="wh-idea-text"><?= e($s['texte']) ?></div>
                                <?php if (! empty($s['lien'])): ?>
                                    <a class="wh-idea-link" href="<?= e($s['lien']) ?>">
                                        <?= e($s['cta'] ?? ($isAr ? 'عرض التفاصيل' : 'Voir les détails')) ?>
                                        <i class="mdi <?= $isAr ? 'mdi-arrow-left' : 'mdi-arrow-right' ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Prochain événement ═══ -->
    <?php if ($prochain !== null): ?>
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="row g-0 align-items-stretch">
                    <div class="col-md-4 wh-next-side d-flex flex-column justify-content-center text-white p-4"
                         style="background:linear-gradient(135deg,var(--wh-green,#1A4D3E),#0F2B22)">
                        <div class="small text-uppercase fw-bold opacity-75 mb-1">
                            <?= $isAr ? 'الحدث القادم' : 'Prochain événement' ?>
                        </div>
                        <div class="wh-next-date" style="font-size:2.2rem;font-weight:800;line-height:1.1">
                            <?= e(date('d', strtotime((string) $prochain['date_evenement']))) ?>
                            <span style="font-size:1.2rem"><?= e(date('M Y', strtotime((string) $prochain['date_evenement']))) ?></span>
                        </div>
                        <div class="mt-2">
                            <?php if (($prochain['jours_restants'] ?? null) !== null && (int) $prochain['jours_restants'] >= 0): ?>
                                <span class="badge bg-white text-dark">
                                    <i class="mdi mdi-clock-outline me-1"></i><?= $countdown($prochain['jours_restants']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (! empty($prochain['heure'])): ?>
                                <span class="badge bg-white text-dark ms-1"><?= e(substr((string) $prochain['heure'], 0, 5)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8 p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h4 class="mb-1"><?= e($prochain['adresse'] ?? '') ?></h4>
                                <div class="text-muted small mb-2">
                                    <?= e($prochain['commune_nom'] ?? '') ?>
                                    <?= ! empty($prochain['association_nom']) ? ' · ' . e($prochain['association_nom']) : '' ?>
                                </div>
                            </div>
                            <span class="<?= $statutBadge((string) $prochain['statut']) ?>">
                                <?= e(statut_label((string) $prochain['statut'])) ?>
                            </span>
                        </div>
                        <?php if (! empty($prochain['description'])): ?>
                            <p class="text-muted small mb-2"><?= e(mb_substr((string) $prochain['description'], 0, 180)) ?></p>
                        <?php endif; ?>
                        <?php if (($prochain['taux_remplissage'] ?? null) !== null): ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:10px;background:#e5e7eb">
                                    <div class="progress-bar <?= (int) $prochain['taux_remplissage'] >= 85 ? 'bg-danger' : ((int) $prochain['taux_remplissage'] >= 60 ? 'bg-warning' : 'bg-success') ?>"
                                         style="width:<?= (int) $prochain['taux_remplissage'] ?>%"></div>
                                </div>
                                <span class="small fw-bold text-nowrap">
                                    <?= (int) $prochain['participants'] ?>/<?= (int) $prochain['capacite'] ?>
                                </span>
                            </div>
                            <div class="small text-muted mt-1">
                                <?= (int) $prochain['taux_remplissage'] ?>% —
                                <?= (int) $prochain['places_restantes'] ?> <?= $isAr ? 'مكان متبقي' : 'places restantes' ?>
                            </div>
                        <?php else: ?>
                            <div class="small text-muted">
                                <i class="mdi mdi-account-group-outline me-1"></i>
                                <?= (int) $prochain['participants'] ?> <?= $isAr ? 'مشارك' : 'participant(s)' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-3" id="evenements">
        <h2 class="h5 mb-0">
            <i class="mdi mdi-calendar-blank-outline me-2"></i><?= $isAr ? 'الفعاليات' : 'Événements' ?>
        </h2>
    </div>

    <?php if ($events === []): ?>
        <div class="wh-empty py-5 text-center text-muted">
            <i class="mdi mdi-calendar-blank-outline" style="font-size:2.5rem"></i>
            <p class="mb-0 mt-2"><?= $isAr ? 'لا توجد فعاليات بعد.' : 'Aucun événement pour le moment.' ?></p>
        </div>
    <?php else: ?>
        <?php if ($prochains !== []): ?>
            <div class="row g-3 mb-2">
                <?php foreach ($prochains as $ev): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 <?= (int) ($ev['jours_restants'] ?? 1) === 0 ? 'wh-today' : '' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="<?= $statutBadge((string) $ev['statut']) ?>">
                                        <?= e(statut_label((string) $ev['statut'])) ?>
                                    </span>
                                    <?php if (($ev['jours_restants'] ?? null) !== null && (int) $ev['jours_restants'] >= 0): ?>
                                        <span class="small fw-bold <?= (int) $ev['jours_restants'] === 0 ? 'text-warning' : 'text-success' ?>">
                                            <?= $countdown($ev['jours_restants']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="card-title mb-1"><?= e($ev['adresse'] ?? '') ?></h5>
                                <p class="card-text text-muted small">
                                    <?= e(mb_substr((string) ($ev['description'] ?? ''), 0, 110)) ?>
                                </p>
                                <div class="small text-muted">
                                    <div><i class="mdi mdi-map-marker-outline me-1"></i><?= e($ev['commune_nom'] ?? '-') ?></div>
                                    <div><i class="mdi mdi-calendar me-1"></i><?= e(date('d/m/Y', strtotime((string) $ev['date_evenement']))) ?>
                                        <?= ! empty($ev['heure']) ? ' · ' . e(substr((string) $ev['heure'], 0, 5)) : '' ?></div>
                                </div>
                                <?php if (($ev['taux_remplissage'] ?? null) !== null): ?>
                                    <div class="progress mt-2" style="height:7px;background:#e5e7eb">
                                        <div class="progress-bar <?= (int) $ev['taux_remplissage'] >= 85 ? 'bg-danger' : ((int) $ev['taux_remplissage'] >= 60 ? 'bg-warning' : 'bg-success') ?>"
                                             style="width:<?= (int) $ev['taux_remplissage'] ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mt-1">
                                        <span><?= (int) $ev['participants'] ?>/<?= (int) $ev['capacite'] ?></span>
                                        <span><?= (int) $ev['taux_remplissage'] ?>%</span>
                                    </div>
                                <?php else: ?>
                                    <div class="small text-muted mt-2">
                                        <i class="mdi mdi-account-group-outline me-1"></i>
                                        <?= (int) $ev['participants'] ?> <?= $isAr ? 'مشارك' : 'participant(s)' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($passes !== []): ?>
            <div class="d-flex align-items-center gap-2 my-3">
                <h3 class="h6 mb-0 text-muted"><?= $isAr ? 'فعاليات سابقة' : 'Événements passés' ?></h3>
                <hr class="flex-grow-1">
            </div>
            <div class="row g-3">
                <?php foreach ($passes as $ev): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 opacity-75">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="<?= $statutBadge((string) $ev['statut']) ?>">
                                        <?= e(statut_label((string) $ev['statut'])) ?>
                                    </span>
                                    <span class="small text-muted"><?= $countdown($ev['jours_restants'] ?? null) ?></span>
                                </div>
                                <h5 class="card-title mb-1"><?= e($ev['adresse'] ?? '') ?></h5>
                                <div class="small text-muted">
                                    <div><i class="mdi mdi-map-marker-outline me-1"></i><?= e($ev['commune_nom'] ?? '-') ?></div>
                                    <div><i class="mdi mdi-calendar me-1"></i><?= e(date('d/m/Y', strtotime((string) $ev['date_evenement']))) ?></div>
                                    <div><i class="mdi mdi-account-group-outline me-1"></i>
                                        <?= (int) ($ev['participants'] ?? 0) ?> <?= $isAr ? 'مشارك' : 'participant(s)' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Journal d'activité -->
    <?php if (! empty($activites)): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header fw-semibold">
                <i class="mdi mdi-history me-2"></i><?= $isAr ? 'سجل النشاط' : 'Journal d\'activité' ?>
            </div>
            <div class="card-body">
                <div class="wh-activity-timeline">
                    <?php foreach ($activites as $act): ?>
                        <div class="wh-activity-item <?= e($act['cssClass']) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="mdi <?= e($act['icon']) ?> text-primary"></i>
                                <span class="wh-activity-text"><?= e($act['label']) ?></span>
                            </div>
                            <div class="wh-activity-time">
                                <?= e(date('d/m/Y H:i', strtotime($act['date']))) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.wh-today { border: 2px solid var(--wh-amber, #d4af37) !important; }
.wh-kpi-value { font-size: 1.45rem; font-weight: 800; line-height: 1.15; color: var(--wh-text, #1f2937); }
.wh-kpi-label { font-size: .8rem; color: #6b7280; }
.wh-next-side { background: linear-gradient(135deg, #1A4D3E, #0F2B22) !important; }
</style>
