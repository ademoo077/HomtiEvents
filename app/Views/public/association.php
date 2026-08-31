<?php
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';
$nom   = $association['nom'] ?? '';
$desc  = $association['description'] ?? '';
$car   = $association['caractere'] ?? '';
$agr   = $association['numero_agrement'] ?? '';
$comm  = $commune['nom'] ?? '';
$carLabel = $isAr
    ? ($car === 'comite_quartier' ? 'لجنة حي' : 'جمعية')
    : ($car === 'comite_quartier' ? 'Comité de quartier' : 'Association');
?>

<div class="wh-hero" style="background: linear-gradient(135deg, #198754 0%, #0B5ED7 100%)">
    <div class="wh-hero-inner">
        <div class="wh-hero-row">
            <div class="wh-hero-text">
                <h1 class="wh-hero-title"><i class="mdi mdi-account-group-outline me-2"></i><?= e($nom) ?></h1>
                <p class="wh-hero-sub">
                    <?= e($carLabel) ?><?= $comm ? ' · ' . e($comm) : '' ?><?= $agr ? ' · ' . ($isAr ? 'رقم الاعتماد' : 'Agrément') . ' ' . e($agr) : '' ?>
                </p>
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
                <i class="mdi mdi-calendar-star-outline stat-icon"></i>
                <div class="citoyen-stat-value"><?= (int) ($stats['evenements_realises'] ?? 0) ?></div>
                <div class="citoyen-stat-label"><?= $isAr ? 'فعالية' : 'Événements' ?></div>
            </div>
            <div class="citoyen-stat">
                <i class="mdi mdi-account-group stat-icon"></i>
                <div class="citoyen-stat-value"><?= (int) ($stats['participants'] ?? 0) ?></div>
                <div class="citoyen-stat-label"><?= $isAr ? 'مشارك' : 'Participants' ?></div>
            </div>
            <div class="citoyen-stat">
                <i class="mdi mdi-star-outline stat-icon"></i>
                <div class="citoyen-stat-value"><?= e((string) ($stats['note_moyenne'] ?? '—')) ?></div>
                <div class="citoyen-stat-label"><?= $isAr ? 'متوسط التقييم' : 'Note moy.' ?></div>
            </div>
        </div>

        <?php if ($events !== []): ?>
        <section class="citoyen-section" data-reveal>
            <div class="citoyen-section-header">
                <h2 class="citoyen-section-title"><i class="mdi mdi-calendar-star"></i> <?= $isAr ? 'الفعاليات' : 'Événements' ?></h2>
            </div>
            <div class="citoyen-event-list">
                <?php foreach ($events as $ev):
                    $evDate = $ev['date_evenement'] ?? '';
                    $evStatut = $ev['statut'] ?? '';
                ?>
                <a href="<?= url('evenement/' . (int) $ev['id']) ?>" class="citoyen-card" data-reveal>
                    <?php if ($evDate): ?>
                    <div class="citoyen-card-date">
                        <span class="citoyen-card-day"><?= e((new DateTimeImmutable($evDate))->format('d')) ?></span>
                        <span class="citoyen-card-month"><?= e((new DateTimeImmutable($evDate))->format('M')) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="citoyen-card-body">
                        <h3 class="citoyen-card-title"><?= e($ev['adresse'] ?? ($isAr ? 'حدث' : 'Événement')) ?></h3>
                        <p class="citoyen-card-meta">
                            <i class="mdi mdi-map-marker-outline"></i> <?= e($ev['commune_nom'] ?? '') ?>
                        </p>
                        <?php
                            $badge = match($evStatut) {
                                'TERMINE' => 'badge-termine', 'EN_COURS' => 'badge-en-cours', default => 'badge-programme',
                            };
                            $label = match($evStatut) {
                                'TERMINE' => ($isAr ? 'منجز' : 'Terminé'), 'EN_COURS' => ($isAr ? 'جاري' : 'En cours'),
                                default => ($isAr ? 'م zaprogrammé' : 'Programmé'),
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

        <?php if ($photos !== []): ?>
        <section class="citoyen-section" data-reveal>
            <div class="citoyen-section-header">
                <h2 class="citoyen-section-title"><i class="mdi mdi-image-multiple-outline"></i> <?= $isAr ? 'معرض الصور' : 'Galerie photos' ?></h2>
            </div>
            <div class="citoyen-album-grid">
                <?php foreach ($photos as $photo): ?>
                <div class="citoyen-album-card">
                    <div class="citoyen-album-cover">
                        <img src="<?= asset((string) $photo['image']) ?>" alt="<?= e((string) ($photo['legende'] ?? '')) ?>" loading="lazy">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</section>

<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
