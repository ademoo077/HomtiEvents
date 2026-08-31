<?php
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';
$nom   = $epic['nom'] ?? '';
$desc  = $epic['description'] ?? '';
$color = $epic['couleur'] ?? '#0F2B22';
?>

<div class="wh-hero" style="background: linear-gradient(135deg, #198754 0%, #0B5ED7 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-satellite-variant me-2"></i><?= e($nom) ?></h1>
                <p class="wh-hero-sub"><?= $isAr ? 'مؤسسة عمومية ذات طابع صناعي (EPIC)' : 'Établissement public à caractère industriel (EPIC)' ?></p>
            </div>
        </div>
    </div>
</div>

<section class="citoyen-section">
    <div class="citoyen-event-detail">

        <?php if ($desc): ?>
            <div class="citoyen-simple-card" data-reveal>
                <p class="mb-0" style="text-align:initial;line-height:1.65"><?= nl2br(e($desc)) ?></p>
            </div>
        <?php endif; ?>

        <div class="citoyen-stats-grid" data-reveal>
            <div class="citoyen-stat">
                <i class="mdi mdi-check-decagram stat-icon"></i>
                <div class="citoyen-stat-value"><?= (int) ($stats['interventions_terminees'] ?? 0) ?></div>
                <div class="citoyen-stat-label"><?= $isAr ? 'منجز' : 'Terminées' ?></div>
            </div>
            <div class="citoyen-stat">
                <i class="mdi mdi-progress-clock stat-icon"></i>
                <div class="citoyen-stat-value"><?= (int) ($stats['interventions_en_cours'] ?? 0) ?></div>
                <div class="citoyen-stat-label"><?= $isAr ? 'جاري' : 'En cours' ?></div>
            </div>
            <div class="citoyen-stat">
                <i class="mdi mdi-alert-octagon-outline stat-icon"></i>
                <div class="citoyen-stat-value"><?= (int) ($stats['anomalies_traitees'] ?? 0) ?></div>
                <div class="citoyen-stat-label"><?= $isAr ? 'شذوذ' : 'Anomalies' ?></div>
            </div>
            <div class="citoyen-stat">
                <i class="mdi mdi-clock-outline stat-icon"></i>
                <div class="citoyen-stat-value"><?= e((string) ($stats['delai_moyen_jours'] ?? '—')) ?></div>
                <div class="citoyen-stat-label"><?= $isAr ? 'أيام' : 'Jours moy.' ?></div>
            </div>
        </div>

        <?php if ($anomalies !== []): ?>
        <section class="citoyen-section" data-reveal>
            <div class="citoyen-section-header">
                <h2 class="citoyen-section-title"><i class="mdi mdi-alert-octagon-outline"></i> <?= $isAr ? 'الشذوذات' : 'Anomalies traitées' ?></h2>
            </div>
            <div class="citoyen-event-list">
                <?php foreach ($anomalies as $a): ?>
                <div class="citoyen-card">
                    <div class="citoyen-card-body">
                        <h3 class="citoyen-card-title"><i class="mdi mdi-alert-decagram-outline"></i> <?= e((string) $a['nom']) ?></h3>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($recentEvents !== []): ?>
        <section class="citoyen-section" data-reveal>
            <div class="citoyen-section-header">
                <h2 class="citoyen-section-title"><i class="mdi mdi-calendar-check"></i> <?= $isAr ? 'التدخلات الأخيرة' : 'Interventions récentes' ?></h2>
            </div>
            <div class="citoyen-event-list">
                <?php foreach ($recentEvents as $ev):
                    $evDate = $ev['date_evenement'] ?? '';
                    $intStatut = $ev['intervention_statut'] ?? '';
                ?>
                <a href="<?= url('evenement/' . (int) $ev['id']) ?>" class="citoyen-card" data-reveal>
                    <?php if ($evDate): ?>
                    <div class="citoyen-card-date">
                        <span class="citoyen-card-day"><?= e((new DateTimeImmutable($evDate))->format('d')) ?></span>
                        <span class="citoyen-card-month"><?= e((new DateTimeImmutable($evDate))->format('M')) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="citoyen-card-body">
                        <h3 class="citoyen-card-title"><?= e($ev['adresse'] ?? ($isAr ? 'حدث' : 'Intervention')) ?></h3>
                        <p class="citoyen-card-meta">
                            <i class="mdi mdi-map-marker-outline"></i> <?= e($ev['commune_nom'] ?? '') ?>
                        </p>
                        <?php
                            $badge = match($intStatut) {
                                'TERMINE' => 'badge-termine', 'EN_COURS' => 'badge-en-cours', 'ANOMALIE' => 'badge-anomalie',
                                default => 'badge-programme',
                            };
                            $label = match($intStatut) {
                                'TERMINE' => ($isAr ? 'منجز' : 'Terminé'), 'EN_COURS' => ($isAr ? 'جاري' : 'En cours'),
                                'ANOMALIE' => ($isAr ? 'شذوذ' : 'Anomalie'), default => ($isAr ? 'متأثر' : 'Affecté'),
                            };
                        ?>
                        <span class="badge <?= $badge ?>"><?= e($label) ?></span>
                    </div>
                    <i class="mdi mdi-chevron-right citoyen-card-arrow"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</section>

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
