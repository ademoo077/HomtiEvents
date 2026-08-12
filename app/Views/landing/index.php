<?php
/** @var array $upcoming @var array $stats @var array $faq @var array $testimonials @var array $partners @var array $albums @var array $anomalies @var array $beforeAfter @var array $gallery @var array $mapEvents @var int $totalParticipants */
use App\Helpers\Database;
use App\Helpers\I18n;

$title = '';
$pick = static fn(string $fr, string $ar) => I18n::pick($fr, $ar);
$isAr = I18n::direction() === 'rtl';
$ordre = settings('sections_order', ['actualites', 'apropos', 'fonctionnement', 'anomalies', 'albums', 'temoignages', 'partenaires', 'faq']);
$ordre = is_array($ordre) ? $ordre : ['actualites', 'apropos', 'fonctionnement', 'anomalies', 'albums', 'temoignages', 'partenaires', 'faq'];
$visible = static fn(string $section): bool => (string) settings('section_' . $section . '_visible', '1') === '1';

$heroTitre = $pick((string) settings('hero_titre_fr', ''), (string) settings('hero_titre_ar', ''));
$heroSub   = $pick((string) settings('hero_sous_titre_fr', ''), (string) settings('hero_sous_titre_ar', ''));
?>

<div class="landing" id="top">

    <!-- ═══════════════ HERO PREMIUM ═══════════════ -->
    <section class="hero">
        <span class="orb orb-1" aria-hidden="true"></span>
        <span class="orb orb-2" aria-hidden="true"></span>
        <span class="orb orb-3" aria-hidden="true"></span>
        <span class="hero-grid" aria-hidden="true"></span>
        <span class="hero-particles" aria-hidden="true">
            <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
        </span>

        <div class="container hero-inner">
            <div class="hero-content" data-reveal>
                <span class="hero-badge">
                    <i class="mdi mdi-check-decagram-outline"></i>
                    <?= e(__('landing.hero_badge')) ?>
                </span>
                <h1 class="hero-title">
                    <?= e($heroTitre ?: __('app.name')) ?>
                    <span class="hero-title-accent"><?= $isAr ? 'هُم الـبطل' : 'Harmonia' ?></span>
                </h1>
                <p class="hero-sub"><?= e($heroSub ?: __('app.tagline')) ?></p>

                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg hero-btn" href="#carte">
                        <i class="mdi mdi-map-marker-radius-outline"></i><?= e(__('landing.cta_explorer')) ?>
                    </a>
                    <a class="btn btn-outline btn-lg hero-btn" href="<?= url('auth/register') ?>">
                        <i class="mdi mdi-account-plus-outline"></i><?= e(__('landing.cta_register')) ?>
                    </a>
                    <a class="btn btn-outline btn-lg hero-btn" href="<?= url('auth/register-association') ?>">
                        <i class="mdi mdi-domain"></i><?= e(__('associations.inscription')) ?>
                    </a>
                </div>

                <div class="hero-trust">
                    <div class="trust-avatars" aria-hidden="true">
                        <span class="t-avatar"><i class="mdi mdi-account"></i></span>
                        <span class="t-avatar"><i class="mdi mdi-account"></i></span>
                        <span class="t-avatar"><i class="mdi mdi-account"></i></span>
                        <span class="t-avatar t-avatar-plus"><i class="mdi mdi-plus"></i></span>
                    </div>
                    <span><strong>+<?= (int) $totalParticipants ?></strong> <?= $isAr ? 'مشاركة مواطنة' : __('landing.citoyen_participations') ?></span>
                </div>
            </div>

            <!-- Illustration moderne (100% CSS/HTML) -->
            <div class="hero-visual parallax-slow" aria-hidden="true">
                <div class="mock-window">
                    <div class="mock-topbar">
                        <span class="mock-dots"><i></i><i></i><i></i></span>
                        <span class="mock-url">wilaya-harmonia.dz</span>
                        <span class="mock-live"><i class="mdi mdi-check-decagram"></i> En ligne</span>
                    </div>
                    <div class="mock-body">
                        <div class="mock-chart">
                            <?php for ($i = 0; $i < 12; $i++): ?>
                                <span class="mock-bar bar-<?= $i + 1 ?>"></span>
                            <?php endfor; ?>
                        </div>
                        <div class="mock-side">
                            <div class="mock-donut"></div>
                            <div class="mock-legend">
                                <span><i class="dot dot-violet"></i>Interventions</span>
                                <span><i class="dot dot-cyan"></i>Associations</span>
                                <span><i class="dot dot-green"></i><?= e(__('landing.citoyens')) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="mock-footer">
                        <span class="mock-progress"><i></i></span>
                        <span class="mock-pct">87%</span>
                    </div>
                </div>

                <div class="float-chip chip-1">
                    <span class="chip-ico"><i class="mdi mdi-tree"></i></span>
                    <span><strong>+120</strong> <?= e(__('landing.trees_planted')) ?></span>
                </div>
                <div class="float-chip chip-2">
                    <span class="chip-ico"><i class="mdi mdi-qrcode-scan"></i></span>
                    <span><strong><?= e(__('landing.checkin')) ?></strong> <?= e(__('landing.qr_citoyen')) ?></span>
                </div>
                <div class="float-chip chip-3">
                    <span class="chip-ico"><i class="mdi mdi-hand-heart-outline"></i></span>
                    <span><strong>1 250</strong> <?= e(__('landing.participants')) ?></span>
                </div>
            </div>
        </div>

        <!-- Bande de statistiques animées -->
        <div class="container">
            <div class="hero-stats" role="list" aria-label="<?= e(__('landing.stat_band_title')) ?>">
                <?php foreach ($stats as $i => $s): ?>
                    <div class="hero-stat" role="listitem" data-reveal data-reveal-delay="<?= $i * 100 ?>">
                        <span class="stat-ico tint-<?= e($s['teinte']) ?>"><i class="mdi <?= e($s['icone']) ?>"></i></span>
                        <strong data-count="<?= (int) $s['valeur'] ?>">0</strong>
                        <span class="stat-label"><?= e($s['libelle']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="wave" aria-hidden="true">
            <svg viewBox="0 0 1440 110" preserveAspectRatio="none">
                <path d="M0,70 C240,120 480,10 720,50 C960,90 1200,30 1440,70 L1440,110 L0,110 Z" fill="var(--bg-primary)"></path>
            </svg>
        </div>
    </section>

    <!-- ═══════════════ SECTIONS ═══════════════ -->
    <style>
    /* ═══ Albums — filtres ═══ */
    .album-filters-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 24px;
    }
    .album-filters-row .filter-btn {
        border: 1px solid var(--lnd-border, rgba(148, 163, 184, 0.3));
        background: transparent;
        color: var(--text-muted, #94a3b8);
        padding: 7px 14px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .album-filters-row .filter-btn:hover {
        transform: translateY(-1px);
        color: var(--text, #fff);
        border-color: var(--lnd-accent, #16a34a);
    }
    .album-filters-row .filter-btn.active {
        background: var(--lnd-accent, #16a34a);
        color: #fff;
        border-color: var(--lnd-accent, #16a34a);
    }

    /* Couverture cliquable (bouton) */
    .album-cover.album-open {
        display: grid;
        place-items: center;
        width: 100%;
        padding: 0;
        border: 0;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.12));
        cursor: pointer;
    }
    .album-cover.album-open:focus-visible {
        outline: 2px solid var(--lnd-accent, #16a34a);
        outline-offset: 2px;
    }

    /* Métadonnées */
    .album-assoc { margin-top: 6px; }
    .album-meta { color: var(--text-muted, #94a3b8); }

    /* Placeholder de couverture */
    .placeholder-cover {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        background: var(--bg-muted, #f8f9fa);
        color: var(--text-muted, #6c757d);
    }
    .placeholder-cover .mdi { font-size: 3rem; opacity: 0.5; }

    /* Placeholder grille */
    .albums-placeholder {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 48px 16px;
        color: var(--text-muted, #94a3b8);
        text-align: center;
    }
    .albums-placeholder .mdi { font-size: 3rem; opacity: 0.5; }

    /* Utilitaires */
    .mdi-48px { font-size: 3rem; }
    .mt-1 { margin-top: 6px; }
    .py-4 { padding-block: 1.5rem; }

    /* ═══ Album Lightbox (personnalisé) ═══ */
    .wh-lightbox {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        opacity: 0;
        visibility: hidden;
        display: none;
        transition: opacity 0.28s ease, visibility 0.28s ease;
    }
    .wh-lightbox.open { opacity: 1; visibility: visible; display: flex; }
    body.wh-lb-open { overflow: hidden; }

    .wh-lightbox-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(2, 6, 23, 0.88);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }

    .wh-lightbox-panel {
        position: relative;
        display: flex;
        flex-direction: column;
        max-width: min(1080px, 100%);
        max-height: 94vh;
        width: 100%;
        border-radius: 20px;
        overflow: hidden;
        background: var(--card-bg, #0f172a);
        border: 1px solid var(--lnd-border, rgba(148, 163, 184, 0.25));
        box-shadow: 0 40px 90px rgba(0, 0, 0, 0.6);
        transform: translateY(16px) scale(0.98);
        transition: transform 0.28s ease;
    }
    .wh-lightbox.open .wh-lightbox-panel { transform: translateY(0) scale(1); }

    .wh-lightbox-close {
        position: absolute;
        top: 14px;
        inset-inline-end: 14px;
        z-index: 3;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background: rgba(15, 23, 42, 0.65);
        color: #fff;
        font-size: 1.2rem;
        cursor: pointer;
        display: grid;
        place-items: center;
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        transition: background 0.2s ease, transform 0.2s ease;
    }
    .wh-lightbox-close:hover { background: rgba(220, 38, 38, 0.85); transform: rotate(90deg); }

    .wh-lightbox-stage {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #05070f;
        min-height: 220px;
    }
    .wh-lightbox-img {
        width: 100%;
        max-height: 66vh;
        object-fit: contain;
    }

    .wh-lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 46px;
        height: 46px;
        border: none;
        border-radius: 50%;
        background: rgba(15, 23, 42, 0.65);
        color: #fff;
        font-size: 1.4rem;
        cursor: pointer;
        display: grid;
        place-items: center;
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        transition: background 0.2s ease;
        z-index: 2;
    }
    .wh-lightbox-nav:hover { background: rgba(99, 102, 241, 0.9); }
    .wh-lightbox-nav.prev { inset-inline-start: 14px; }
    .wh-lightbox-nav.next { inset-inline-end: 14px; }

    .wh-lightbox-counter {
        position: absolute;
        bottom: 12px;
        inset-inline-start: 16px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fff;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 2;
    }

    .wh-lightbox-caption {
        position: absolute;
        bottom: 12px;
        inset-inline-end: 16px;
        max-width: 60%;
        margin: 0;
        padding: 5px 12px;
        border-radius: 10px;
        font-size: 0.82rem;
        color: #e2e8f0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 2;
    }

    .wh-lightbox-narrative {
        padding: 18px 22px 22px;
        border-top: 1px solid var(--lnd-border, rgba(148, 163, 184, 0.2));
        background: var(--card-bg, #0f172a);
        max-height: 26vh;
        overflow-y: auto;
    }
    .wh-lightbox-narrative h4 {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 8px;
        font-size: 0.95rem;
        color: var(--accent-soft, #818cf8);
    }
    .wh-lightbox-narrative p {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.7;
        color: var(--text, #e2e8f0);
    }

    /* Gallery type styling */
    .gallery-type.album-link {
        background: var(--lnd-accent, #16a34a);
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.8em;
    }
    .btn-outline-primary {
        border: 2px solid var(--lnd-accent, #16a34a);
        color: var(--lnd-accent, #16a34a);
        transition: all 0.3s ease;
    }
    .btn-outline-primary:hover {
        background: var(--lnd-accent, #16a34a);
        color: #fff;
    }
    </style>

    <?php foreach ($ordre as $section): ?>
        <?php if (! $visible((string) $section)) { continue; } ?>

<?php if ($section === 'actualites'): ?>
            <section class="section section-events" id="actualites">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-calendar-star"></i><?= $isAr ? 'آخر الفعاليات' : 'À la une' ?></span>
                        <h2 class="section-title"><?= e(__('landing.actualites')) ?></h2>
                        <p class="section-lead"><?= $isAr ? 'تعرّف على الفعاليات القادمة المبرمجة عبر الولاية.' : 'Les prochaines opérations programmées à travers la wilaya.' ?></p>
                        <?php if (! empty($upcoming)): ?>
                            <span class="section-count"><i class="mdi mdi-calendar-clock-outline"></i><?= (int) count($upcoming) ?> <?= $isAr ? 'فعالية قادمة' : 'événements à venir' ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cards-grid">
                        <?php foreach ($upcoming as $idx => $ev):
                            $assoc = null;
                            if (! empty($ev['association_id'])) {
                                $assoc = Database::one('SELECT id, nom, numero_agrement, valide FROM associations WHERE id = ?', [(int) $ev['association_id']]);
                            }
                            $dtEv = new DateTimeImmutable((string) $ev['date_evenement']);
                            $moisFr = ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];
                            $moisAr = ['جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                            $nomMois = $isAr ? $moisAr[(int) $dtEv->format('n') - 1] : $moisFr[(int) $dtEv->format('n') - 1];
                            $heure = ! empty($ev['heure']) ? substr((string) $ev['heure'], 0, 5) : '';
                            $desc = trim((string) ($ev['description'] ?? ''));
                        ?>
                            <a class="card event-card hover" href="<?= url('checkin/' . \App\Helpers\QrCodeGenerator::tokenForEvent((int) $ev['id'])) ?>" data-reveal data-reveal-delay="<?= $idx * 100 ?>">
                                <div class="event-card-top">
                                    <span class="date-badge">
                                        <span class="day"><?= e($dtEv->format('d')) ?></span>
                                        <span class="month"><?= e($nomMois) ?></span>
                                        <span class="year"><?= e($dtEv->format('Y')) ?></span>
                                    </span>
                                    <span class="event-status <?= e(statut_key((string) ($ev['statut'] ?? ''))) ?>">
                                        <i class="mdi mdi-calendar-check"></i><?= e(statut_label((string) ($ev['statut'] ?? ''))) ?>
                                    </span>
                                </div>
                                <h3 class="event-title"><?= e($ev['adresse']) ?></h3>
                                <?php if ($desc !== ''): ?>
                                    <p class="event-desc"><?= e($desc) ?></p>
                                <?php endif; ?>
                                <?php if ($assoc !== null): ?>
                                    <div class="event-assoc"><?= association_badge($assoc) ?></div>
                                <?php endif; ?>
                                <div class="event-card-footer">
                                    <div class="event-meta">
                                        <?php if ($heure !== ''): ?>
                                            <span class="event-meta-chip"><i class="mdi mdi-clock-outline"></i><?= e($heure) ?></span>
                                        <?php endif; ?>
                                        <?php if (! empty($ev['commune_nom'])): ?>
                                            <span class="event-meta-chip"><i class="mdi mdi-map-marker-outline"></i><?= e($ev['commune_nom']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="event-voir"><?= $isAr ? 'التفاصيل' : 'Détails' ?> <i class="mdi mdi-arrow-right"></i></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <?php if (empty($upcoming)): ?>
                            <div class="card empty events-empty" style="grid-column: 1 / -1;">
                                <i class="mdi mdi-calendar-outline mdi-48px"></i>
                                <p><?= e(__('landing.actualites_vide')) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="section-cta" data-reveal data-reveal-delay="200">
                        <a class="btn btn-outline" href="<?= url('evenements') ?>">
                            <i class="mdi mdi-calendar-multiple"></i>
                            <?= $isAr ? 'جميع الفعاليات' : 'Voir tous les événements' ?>
                        </a>
                    </div>
                </div>
            </section>

                <?php elseif ($section === 'apropos'): ?>
            <section class="section section-about bg-muted" id="apropos">
                <div class="container about-inner">
                    <div class="about-visual" data-reveal aria-hidden="true">
                        <div class="about-card-glow">
                            <i class="mdi mdi-hand-heart-outline"></i>
                        </div>
                        <span class="orb orb-4"></span>
                    </div>
                    <div class="about-text" data-reveal data-reveal-delay="120">
                        <span class="eyebrow"><i class="mdi mdi-information-outline"></i><?= $isAr ? 'من نحن' : 'Qui sommes-nous' ?></span>
                        <h2 class="section-title left"><?= e($pick((string) settings('titre_apropos_fr', ''), (string) settings('titre_apropos_ar', ''))) ?></h2>
                        <p class="apropos-text"><?= e($pick((string) settings('texte_apropos_fr', ''), (string) settings('texte_apropos_ar', ''))) ?></p>
                        <ul class="about-points">
                            <li><i class="mdi mdi-check-circle-outline"></i><?= $isAr ? 'شراكة مواطنة حقيقية' : __('landing.citoyen_construction') ?></li>
                            <li><i class="mdi mdi-check-circle-outline"></i><?= $isAr ? 'شفافية في كل مرحلة' : 'Transparence à chaque étape' ?></li>
                            <li><i class="mdi mdi-check-circle-outline"></i><?= $isAr ? 'خدمة عمومية متجددة' : 'Un service public qui se modernise' ?></li>
                        </ul>
                    </div>
                </div>
            </section>

        <?php elseif ($section === 'fonctionnement'): ?>
            <?php $etapes = settings('fonctionnement_etapes', []); ?>
            <?php if (is_array($etapes) && count($etapes) > 0): ?>
                <section class="section section-how" id="fonctionnement">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <span class="eyebrow"><i class="mdi mdi-cog-outline"></i><?= $isAr ? 'طريقة العمل' : 'Le processus' ?></span>
                            <h2 class="section-title"><?= e($pick((string) settings('titre_fonctionnement_fr', ''), (string) settings('titre_fonctionnement_ar', ''))) ?></h2>
                            <p class="section-lead"><?= $isAr ? 'من الإبلاغ إلى الإنجاز، مسار واضح ومبسط.' : 'Du signalement à la réalisation, un parcours clair et simplifié.' ?></p>
                        </div>
                        <div class="steps">
                            <?php foreach ($etapes as $i => $etape): ?>
                                <div class="step-card" data-reveal data-reveal-delay="<?= $i * 90 ?>">
                                    <span class="step-num"><?= (int) $i + 1 ?></span>
                                    <span class="step-ico"><i class="mdi <?= e($etape['icone'] ?? 'mdi-star') ?>"></i></span>
                                    <h3><?= e($pick((string) ($etape['titre_fr'] ?? ''), (string) ($etape['titre_ar'] ?? ''))) ?></h3>
                                    <p><?= e($pick((string) ($etape['texte_fr'] ?? ''), (string) ($etape['texte_ar'] ?? ''))) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

        <?php elseif ($section === 'anomalies'): ?>
            <section class="section section-services bg-muted" id="anomalies">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-tools"></i><?= $isAr ? 'خدمات الولاية' : 'Nos services' ?></span>
                        <h2 class="section-title"><?= e(__('landing.anomalies')) ?></h2>
                        <p class="section-lead"><?= e(__('landing.anomalies_sub')) ?></p>
                    </div>
                    <div class="services-grid">
                        <?php foreach ($anomalies as $a): ?>
                            <div class="service-card" data-reveal style="--card-tint:<?= e($a['couleur'] ?? '#8b5cf6') ?>">
                                <span class="service-ico"><i class="mdi <?= e(str_replace('fa-', 'mdi-', (string) ($a['icone'] ?? 'mdi-alert'))) ?>"></i></span>
                                <strong><?= e($a['nom']) ?></strong>
                                <span class="chip chip-count"><?= (int) $a['total'] ?> <?= e(__('landing.anomalies_traitees')) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($anomalies)): ?><div class="card empty"><?= e(__('landing.anomalies_vide')) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

        <?php elseif ($section === 'albums'): ?>
            <section class="section section-albums" id="albums">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-camera-iris"></i><?= $isAr ? 'ذاكرة الأحداث' : 'La mémoire des événements' ?></span>
                        <h2 class="section-title"><?= e(__('landing.albums')) ?></h2>
                        <p class="section-lead"><?= e(__('landing.albums_sub')) ?></p>
                    </div>
                    
                    <!-- Filters -->
                    <div class="album-filters-row" data-reveal data-reveal-delay="100">
                        <button type="button" class="filter-btn active" data-filter="all">
                            <?= $isAr ? 'الكل' : 'Tous' ?>
                        </button>
                        <?php foreach ($anomalies as $anomaly): ?>
                            <button type="button" class="filter-btn" data-filter="anomaly-<?= (int) $anomaly['id'] ?>">
                                <?php
                                $ico = (string) ($anomaly['icone'] ?? '');
                                $icoMdi = str_starts_with($ico, 'fa-') ? 'mdi-' . substr($ico, 3) : $ico;
                                if (! $icoMdi) $icoMdi = 'mdi-alert-octagon';
                                ?>
                                <i class="mdi <?= e($icoMdi) ?>"></i> <?= e($anomaly['nom']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="albums-grid" id="albumsGrid">
                        <?php foreach ($albums as $al): ?>
                            <?php
                            $albumAnomalies = [];
                            if (! empty($al['id'])) {
                                $albumAnomalies = Database::all(
                                    'SELECT a.id, a.nom FROM anomalies a
                                     JOIN anomalies_evenement ae ON ae.anomalie_id = a.id
                                     WHERE ae.evenement_id = ?',
                                    [(int) $al['evenement_id']]
                                );
                            }
                            ?>
<article class="album-card" data-reveal data-album-id="<?= (int) $al['id'] ?>" data-anomalies="<?= implode(',', array_column($albumAnomalies, 'id')) ?>">
                                        <button type="button" class="album-cover album-open" onclick="openAlbumLightbox(<?= (int) $al['id'] ?>)" aria-label="<?= e($al['titre']) ?>">
                                            <?php
                                            $imgPath = (string) ($al['display_image'] ?? '');
                                            $imgExists = $imgPath && is_file(public_path($imgPath));
                                            ?>
                                            <?php if ($imgExists): ?>
                                                <img src="<?= asset($imgPath) ?>" alt="<?= e($al['titre']) ?>" loading="lazy">
                                                <?php else: ?>
                                                <div class="placeholder-cover">
                                            <i class="mdi mdi-image-multiple mdi-48px"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="album-count"><i class="mdi mdi-image"></i> <?= (int) $al['nb_photos_count'] ?> <?= e(__('landing.albums_photos')) ?></span>
                                </button>
                                <div class="album-body">
                                    <h3><?= e($al['titre']) ?></h3>
                                    <?php if (isset($al['association']) && $al['association'] !== null): ?>
                                        <div class="album-assoc"><?= association_badge($al['association']) ?></div>
                                    <?php endif; ?>
                                    <p class="album-meta">
                                        <i class="mdi mdi-map-marker-outline"></i><?= e($al['adresse']) ?>
                                        <span class="album-date"><?= e(date('d/m/Y', strtotime((string) ($al['date_evenement'] ?? '')))) ?></span>
                                    </p>

                                    <?php if (! empty($al['recit'])): ?>
                                        <blockquote class="album-recit">
                                            "<?= e(mb_substr((string) $al['recit'], 0, 120)) ?>…"
                                        </blockquote>
                                    <?php endif; ?>

                                    <span class="album-voir"><?= e(__('landing.albums_voir')) ?> <i class="mdi mdi-arrow-right"></i></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <div class="albums-placeholder" id="albumsPlaceholder" <?= empty($albums) ? '' : 'style="display:none"' ?>>
                            <i class="mdi mdi-camera mdi-48px"></i>
                            <p><?= $isAr ? 'جارٍ تجهيز المعرض قريبًا.' : 'La galerie est en cours de préparation, revenez bientôt !' ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Album Lightbox (personnalisé, sans Bootstrap) -->
            <div class="wh-lightbox" id="albumLightbox" role="dialog" aria-modal="true" aria-hidden="true">
                <div class="wh-lightbox-backdrop" data-lb-close></div>
                <div class="wh-lightbox-panel">
                    <button class="wh-lightbox-close" type="button" data-lb-close aria-label="<?= $isAr ? 'إغلاق' : 'Fermer' ?>">
                        <i class="mdi mdi-close"></i>
                    </button>

                    <div class="wh-lightbox-stage">
                        <button class="wh-lightbox-nav prev" type="button" data-lb-prev aria-label="<?= $isAr ? 'السابق' : 'Précédent' ?>">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <img class="wh-lightbox-img" id="lightboxImg" src="" alt="" draggable="false">
                        <button class="wh-lightbox-nav next" type="button" data-lb-next aria-label="<?= $isAr ? 'التالي' : 'Suivant' ?>">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                        <span class="wh-lightbox-counter" id="lightboxCounter"></span>
                        <p class="wh-lightbox-caption" id="lightboxCaption"></p>
                    </div>

                    <div class="wh-lightbox-narrative">
                        <h4><i class="mdi mdi-format-quote-open"></i><?= $isAr ? 'القصة الرسمية' : 'Récit officiel' ?></h4>
                        <p id="lightboxNarrativeText"></p>
                    </div>
                </div>
            </div>

        <?php elseif ($section === 'temoignages'): ?>
            <section class="section section-testimonials bg-muted" id="temoignages">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-comment-quote-outline"></i><?= $isAr ? 'آراء المواطنين' : __('landing.citoyen_voice') ?></span>
                        <h2 class="section-title"><?= e(__('landing.temoignages')) ?></h2>
                    </div>
                    <div class="cards-grid">
                        <?php foreach ($testimonials as $t): ?>
                            <div class="card testimonial" data-reveal>
                                <div class="stars" aria-label="<?= (int) $t['note'] ?> / 5"><?= str_repeat('★', (int) $t['note']) ?><?= str_repeat('☆', 5 - (int) $t['note']) ?></div>
                                <p><?= nl2br(e($pick((string) ($t['texte_fr'] ?? ''), (string) ($t['texte_ar'] ?? '')))) ?></p>
                                <div class="text-muted testimonial-author">— <?= e($t['auteur']) ?><?= ! empty($t['role']) ? ' · ' . e($t['role']) : '' ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($testimonials)): ?><div class="card empty"><?= $isAr ? 'لا توجد شهادات حالياً.' : 'Aucun témoignage pour le moment.' ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

        <?php elseif ($section === 'partenaires'): ?>
            <section class="section section-partners" id="partenaires">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-handshake-outline"></i><?= $isAr ? 'معًا ننجز' : 'Ensemble, on agit' ?></span>
                        <h2 class="section-title"><?= e(__('landing.partenaires')) ?></h2>
                    </div>
                    <div class="partners">
                        <?php foreach ($partners as $p): ?>
                            <a class="partner-card" href="<?= e($p['url'] ?? '#') ?>" target="_blank" rel="noopener" data-reveal>
                                <i class="mdi mdi-domain"></i>
                                <span><?= e($p['nom']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (empty($partners)): ?><div class="card empty"><?= $isAr ? 'لا يوجد شركاء حالياً.' : 'Aucun partenaire pour le moment.' ?></div><?php endif; ?>
                    </div>
                </div>
             </section>

        <?php elseif ($section === 'galerie'): ?>
            <section class="section section-gallery bg-muted" id="galerie">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-image-multiple-outline"></i><?= $isAr ? 'صور الأحداث' : 'Images des événements' ?></span>
                        <h2 class="section-title"><?= e(__('landing.galerie')) ?></h2>
                        <p class="section-lead"><?= e(__('landing.galerie_sub')) ?></p>
                    </div>
                    <?php if (! empty($gallery)): ?>
                        <div class="gallery-grid">
                            <?php foreach ($gallery as $g): ?>
                                <?php $isPhoto = (($g['type'] ?? '') !== 'album') && empty($g['lien']); ?>
                                <a class="gallery-item" href="<?= e($isPhoto ? '#' : ($g['lien'] ?? ($g['type'] === 'album' ? url('citoyen/albums/' . ($g['sort_order'] ?? '')) : '#'))) ?>"
                                   <?= ($g['lien'] && $g['type'] !== 'album') ? 'target="_blank" rel="noopener"' : '' ?>
                                   <?= $isPhoto ? 'data-lightbox="landing" data-title="' . e($pick((string) ($g['titre_fr'] ?? ''), (string) ($g['titre_ar'] ?? ''))) . '" data-full="' . e($g['image']) . '"' : '' ?> data-reveal>
                                    <img src="<?= e($g['image']) ?>" alt="<?= e($g['titre_fr'] ?? '') ?>" loading="lazy">
                                    <div class="gallery-overlay">
                                        <span class="gallery-title"><?= e($pick((string) ($g['titre_fr'] ?? ''), (string) ($g['titre_ar'] ?? ''))) ?></span>
                                        <?php if ($g['type'] === 'album'): ?>
                                            <span class="gallery-type album-link"><i class="mdi mdi-album"></i> <?= $isAr ? 'ألبوم' : 'Album' ?></span>
                                        <?php else: ?>
                                            <span class="gallery-type"><?= e(ucfirst($g['type'] ?? '')) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <!-- Lightbox (simple, zero-dépendance) -->
                        <div id="landingLightbox" class="lb-modal" role="dialog" aria-modal="true" aria-hidden="true" tabindex="-1">
                            <div class="lb-content">
                                <button type="button" class="lb-close" aria-label="Fermer"><i class="mdi mdi-close"></i></button>
                                <img class="lb-img" alt="">
                                <span class="lb-title"></span>
                            </div>
                        </div>
                        <!-- Bouton pour voir tous les albums -->
                        <div class="text-center mt-4">
                            <a href="<?= url('citoyen/albums') ?>" class="btn btn-outline-primary">
                                <i class="mdi mdi-album"></i> <?= $isAr ? 'جميع الألبومات' : 'Tous les albums' ?>
                            </a>
                        </div>
                     <?php else: ?>
                        <p class="text-muted text-center"><?= $isAr ? 'لا توجد صور حالياً.' : 'Aucune image disponible.' ?></p>
                     <?php endif; ?>
                </div>
            </section>

        <?php elseif ($section === 'before_after'): ?>
            <section class="section section-ba" id="before-after">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-compare-horizontal"></i><?= $isAr ? 'النتائج على أرض الواقع' : 'Les résultats concrets' ?></span>
                        <h2 class="section-title"><?= e(__('landing.before_after')) ?></h2>
                    </div>
                    <?php if (! empty($beforeAfter)): ?>
                        <div class="before-after-grid">
                            <?php foreach ($beforeAfter as $ba): ?>
                                <div class="ba-card" data-reveal>
                                    <div class="ba-photos">
                                        <div class="ba-photo">
                                            <img src="<?= e($ba['image_before']) ?>" alt="<?= $isAr ? 'قبل' : 'Avant' ?>" loading="lazy">
                                            <span class="ba-label ba-before"><?= $isAr ? 'قبل' : 'Avant' ?></span>
                                        </div>
                                        <div class="ba-photo">
                                            <img src="<?= e($ba['image_after']) ?>" alt="<?= $isAr ? 'بعد' : 'Après' ?>" loading="lazy">
                                            <span class="ba-label ba-after"><?= $isAr ? 'بعد' : 'Après' ?></span>
                                        </div>
                                    </div>
                                    <div class="ba-content">
                                        <h3><?= e($pick((string) ($ba['titre_fr'] ?? ''), (string) ($ba['titre_ar'] ?? ''))) ?></h3>
                                        <p class="ba-desc"><?= e($pick((string) ($ba['description_fr'] ?? ''), (string) ($ba['description_ar'] ?? ''))) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                         </div>
                     <?php endif; ?>
                 </div>
             </section>

        <?php elseif ($section === 'faq'): ?>
            <section class="section section-faq bg-muted" id="faq">
                <div class="container">
                    <div class="section-head" data-reveal>
                        <span class="eyebrow"><i class="mdi mdi-help-circle-outline"></i><?= $isAr ? 'استفساراتكم' : 'Vos questions' ?></span>
                        <h2 class="section-title"><?= e(__('landing.faq')) ?></h2>
                    </div>
                    <div class="faq">
                        <?php foreach ($faq as $i => $f): ?>
                            <details class="faq-item" <?= $i === 0 ? 'open' : '' ?> data-reveal data-reveal-delay="<?= min($i * 60, 300) ?>">
                                <summary><?= e($pick((string) ($f['question_fr'] ?? ''), (string) ($f['question_ar'] ?? ''))) ?></summary>
                                <p><?= nl2br(e($pick((string) ($f['reponse_fr'] ?? ''), (string) ($f['reponse_ar'] ?? '')))) ?></p>
                            </details>
                        <?php endforeach; ?>
                        <?php if (empty($faq)): ?><p class="text-muted text-center"><?= $isAr ? 'لا توجد أسئلة شائعة حالياً.' : 'Aucune question fréquente pour le moment.' ?></p><?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- ═══════════════ CARTE DES INTERVENTIONS ═══════════════ -->
    <section class="section section-map" id="carte">
        <div class="container">
            <div class="section-head" data-reveal>
                <span class="eyebrow"><i class="mdi mdi-map-outline"></i><?= $isAr ? 'التدخلات الميدانية' : 'Le terrain en direct' ?></span>
                <h2 class="section-title"><?= e(__('landing.interventions')) ?></h2>
                <p class="section-lead"><?= $isAr ? 'تابع عمليات الولاية على الخريطة.' : 'Suivez les opérations de la wilaya sur la carte.' ?></p>
            </div>
            <div class="landing-map glass-panel" id="landingMap" role="region" aria-label="<?= e(__('landing.interventions')) ?>"></div>
        </div>
    </section>

</div>

<script>
/* ── CARTE HEATMAP (Leaflet) ── */
document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('landingMap');
    if (mapEl) {
        const events = <?= json_encode($mapEvents ?? [], JSON_UNESCAPED_UNICODE) ?>;
        const validEvents = events.filter(e => Number(e.latitude) && Number(e.longitude));
        if (validEvents.length === 0) {
            mapEl.innerHTML = '<p class="text-muted text-center py-4"><?= $isAr ? 'لا توجد تدخلات لعرضها.' : 'Aucune intervention à afficher.' ?></p>';
        } else {
            const map = L.map('landingMap', { zoomControl: false, attributionControl: false, scrollWheelZoom: false });
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom: 17 }).addTo(map);

            L.heatLayer(validEvents.map(e => [Number(e.latitude), Number(e.longitude), 0.5]), {
                radius: 24, blur: 16, maxZoom: 12, gradient: { 0.4: '#06b6d8', 0.7: '#6366f1', 1.0: '#a855f7' }
            }).addTo(map);

            validEvents.forEach(e => {
                const color = (e.statut === 'PROGRAMME') ? '#22c55e' : (e.statut === 'EN_ATTENTE') ? '#f59e0b' : '#94a3b8';
                L.circleMarker([Number(e.latitude), Number(e.longitude)], {
                    radius: 7, color, fillColor: color, fillOpacity: .9, weight: 1.5
                }).bindPopup(
                    '<strong>' + (e.adresse || '') + '</strong><br>' +
                    '<small>' + (e.commune_nom || '') + ' · ' + (e.date_evenement || '') + '</small>'
                ).addTo(map);
            });

            const group = L.featureGroup(validEvents.map(e => L.circleMarker([Number(e.latitude), Number(e.longitude)])));
            map.fitBounds(group.getBounds().pad(0.15));
            setTimeout(() => map.invalidateSize(), 250);
        }
    }
});

/* ── Album Lightbox ── */
window.__albumsData = <?= json_encode($albums ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
var __currentAlbumId = null;
var __currentPhotoIndex = 0;

function assetPath(p) {
    if (!p) return '';
    if (/^(https?:)?\/\//.test(p) || p.charAt(0) === '/') return p;
    return '/' + p;
}

function escAttr(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function openAlbumLightbox(albumId) {
    var album = window.__albumsData.find(function (a) { return Number(a.id) === Number(albumId); });
    if (!album) return;

    __currentAlbumId = Number(albumId);

    var photos = album.photos || [];
    var images = album.couverture
        ? [{ image: album.couverture, legende: '' }].concat(photos)
        : photos.map(function (p) { return { image: p.image, legende: p.legende }; });

    if (images.length === 0) {
        images = [{ image: null, legende: '' }];
    }
    window.__lightboxImages = images;

    // Récit officiel
    var narrativeEl = document.getElementById('lightboxNarrativeText');
    narrativeEl.textContent = album.recit || (album.adresse || '') + ' — ' + (album.date_evenement ? new Date(album.date_evenement).toLocaleDateString() : '');

    __currentPhotoIndex = 0;
    showLightboxPhoto();

    var box = document.getElementById('albumLightbox');
    box.setAttribute('aria-hidden', 'false');
    box.classList.add('open');
    document.body.classList.add('wh-lb-open');
}

function showLightboxPhoto() {
    var imgs = window.__lightboxImages || [];
    var idx = __currentPhotoIndex;
    var img = imgs[idx];
    var imgEl = document.getElementById('lightboxImg');

    if (img && img.image) {
        imgEl.src = assetPath(img.image);
        imgEl.alt = img.legende || '';
        imgEl.style.display = '';
    } else {
        imgEl.removeAttribute('src');
        imgEl.style.display = 'none';
    }

    var counter = document.getElementById('lightboxCounter');
    counter.textContent = (idx + 1) + ' / ' + imgs.length;

    var caption = document.getElementById('lightboxCaption');
    caption.textContent = img && img.legende ? img.legende : '';
    caption.style.display = (img && img.legende) ? '' : 'none';

    document.querySelectorAll('[data-lb-prev]').forEach(function (b) { b.style.visibility = imgs.length > 1 ? 'visible' : 'hidden'; });
    document.querySelectorAll('[data-lb-next]').forEach(function (b) { b.style.visibility = imgs.length > 1 ? 'visible' : 'hidden'; });
}

function closeAlbumLightbox() {
    var box = document.getElementById('albumLightbox');
    box.classList.remove('open');
    box.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('wh-lb-open');
    __currentAlbumId = null;
}

function stepLightbox(dir) {
    var imgs = window.__lightboxImages || [];
    if (imgs.length === 0) return;
    __currentPhotoIndex = (__currentPhotoIndex + dir + imgs.length) % imgs.length;
    showLightboxPhoto();
}

document.addEventListener('click', function (e) {
    var closeTarget = e.target.closest('[data-lb-close]');
    if (closeTarget) { closeAlbumLightbox(); return; }
    if (e.target.closest('[data-lb-prev]')) { stepLightbox(-1); return; }
    if (e.target.closest('[data-lb-next]')) { stepLightbox(1); return; }
});

document.addEventListener('keydown', function (e) {
    var box = document.getElementById('albumLightbox');
    if (!box.classList.contains('open')) return;
    if (e.key === 'Escape') { closeAlbumLightbox(); }
    else if (e.key === 'ArrowLeft') { stepLightbox(-1); }
    else if (e.key === 'ArrowRight') { stepLightbox(1); }
});

/* ── Rendu des cartes albums (servi aussi au polling) ── */
function renderAlbumCard(al) {
    var anomalies = (al.anomalies || []).map(function (a) { return a.id; }).join(',');
    var cover;
    if (al.display_image) {
        cover = '<img src="' + assetPath(al.display_image) + '" alt="' + escAttr(al.titre) + '" loading="lazy">';
    } else {
        cover = '<div class="placeholder-cover"><i class="mdi mdi-image-multiple mdi-48px"></i></div>';
    }
    var assoc = '';
    if (al.association && Number(al.association.valide) === 1) {
        assoc = '<div class="album-assoc"><span class="badge-association-agreer" title="' + escAttr(al.association.nom) + '"><i class="mdi mdi-shield-check"></i><span><?= e(__('common.association_agreer')) ?></span></span></div>';
    }
    var recit = al.recit
        ? '<blockquote class="album-recit">"' + escAttr(al.recit).substring(0, 120) + '…"</blockquote>'
        : '';
    var date = al.date_evenement ? new Date(al.date_evenement).toLocaleDateString('fr-FR') : '';

    return '<article class="album-card" data-album-id="' + Number(al.id) + '" data-anomalies="' + anomalies + '">' +
        '<button type="button" class="album-cover album-open" onclick="openAlbumLightbox(' + Number(al.id) + ')" aria-label="' + escAttr(al.titre) + '">' + cover +
        '<span class="album-count"><i class="mdi mdi-image"></i> ' + Number(al.nb_photos_count || al.nb_photos || 0) + ' <?= e(__('landing.albums_photos')) ?></span>' +
        '</button>' +
        '<div class="album-body">' +
        '<h3>' + escAttr(al.titre) + '</h3>' +
        assoc +
        '<p class="album-meta"><i class="mdi mdi-map-marker-outline"></i>' + escAttr(al.adresse) +
        '<span class="album-date">' + escAttr(date) + '</span></p>' +
        recit +
        '<span class="album-voir"><?= e(__('landing.albums_voir')) ?> <i class="mdi mdi-arrow-right"></i></span>' +
        '</div>' +
        '</article>';
}

function renderAlbumGrid() {
    var grid = document.getElementById('albumsGrid');
    if (!grid) return;

    var placeholder = document.getElementById('albumsPlaceholder');
    var hasAlbums = window.__albumsData.length > 0;

    if (placeholder) { placeholder.style.display = hasAlbums ? 'none' : ''; }

    var currentFilter = document.querySelector('.album-filters-row .filter-btn.active');
    var filterValue = currentFilter ? currentFilter.getAttribute('data-filter') : 'all';

    grid.querySelectorAll('.album-card').forEach(function (c) { c.remove(); });

    window.__albumsData.forEach(function (al) {
        var wrap = document.createElement('div');
        wrap.innerHTML = renderAlbumCard(al);
        var card = wrap.firstChild;
        if (filterValue !== 'all') {
            var filterId = filterValue.replace('anomaly-', '');
            var show = (card.getAttribute('data-anomalies') || '').split(',').indexOf(filterId) !== -1;
            card.style.display = show ? '' : 'none';
        }
        grid.insertBefore(card, placeholder);
    });
}

/* ── Polling for gallery updates ── */
(function () {
    var lastUpdate = <?= json_encode($lastUpdate ?? date('Y-m-d H:i:s'), JSON_UNESCAPED_UNICODE) ?>;
    var POLL_INTERVAL = 30000; // 30 secondes

    function pollGallery() {
        fetch('<?= url('api/gallery/updates') ?>?since=' + encodeURIComponent(lastUpdate))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.timestamp) return;
                lastUpdate = data.timestamp;

                if (data.albums && data.albums.length > 0) {
                    var changed = false;
                    data.albums.forEach(function (al) {
                        var idx = window.__albumsData.findIndex(function (x) { return Number(x.id) === Number(al.id); });
                        if (idx >= 0) { window.__albumsData[idx] = al; }
                        else { window.__albumsData.push(al); }
                        changed = true;
                    });
                    if (changed) {
                        window.__albumsData.sort(function (a, b) {
                            return new Date(b.updated_at || b.date_creation) - new Date(a.updated_at || a.date_creation);
                        });
                        renderAlbumGrid();
                    }
                }
            })
            .catch(function () {});
    }

    // Polling uniquement sur la page d'accueil
    if (window.location.pathname === '/' || window.location.pathname === '/index.php') {
        setInterval(pollGallery, POLL_INTERVAL);
    }
})();

/* ── Album filtering ── */
document.addEventListener('DOMContentLoaded', function () {
    var container = document.querySelector('.album-filters-row');
    if (!container) return;

    container.addEventListener('click', function (e) {
        var btn = e.target.closest('.filter-btn');
        if (!btn) return;

        container.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');

        var filterValue = btn.getAttribute('data-filter');
        document.querySelectorAll('.albums-grid .album-card').forEach(function (card) {
            if (filterValue === 'all') { card.style.display = ''; return; }
            var filterId = filterValue.replace('anomaly-', '');
            var show = (card.getAttribute('data-anomalies') || '').split(',').indexOf(filterId) !== -1;
            card.style.display = show ? '' : 'none';
        });
    });

    renderAlbumGrid();
});
</script>
