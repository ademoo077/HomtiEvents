<?php
/** @var array $actualites @var array $evenements @var array $items @var array $prochains @var array $theme */
use App\Helpers\I18n;

$isAr  = I18n::direction() === 'rtl';
$mois  = $isAr
    ? ['جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أوت', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
    : ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
$dateBadge = static function (?string $date) use ($mois): string {
    if ($date === null) {
        return '';
    }
    $dt = new DateTimeImmutable($date);

    return '<span class="news-date-badge"><span class="nbd-day">' . $dt->format('d') . '</span><span class="nbd-month">' . $mois[$dt->format('n') - 1] . '</span></span>';
};
$titreItem = static fn (array $item): string => $isAr ? ((string) ($item['titre_ar'] ?: $item['titre_fr'])) : (string) $item['titre_fr'];
$descItem  = static fn (array $item): string => $isAr
    ? ((string) ($item['description_ar'] ?: $item['description_fr']))
    : ((string) ($item['description_fr'] ?: $item['description_ar']));
?>
<div class="landing" id="top">

    <!-- ═══ BANNIÈRE DE PAGE ═══ -->
    <section class="page-hero" data-reveal>
        <div class="container">
            <span class="eyebrow"><i class="mdi mdi-newspaper-variant-outline"></i><?= $isAr ? 'ميديا' : 'Médiathèque' ?></span>
            <h1 class="page-title"><?= $isAr ? 'الأخبار والفعاليات القادمة' : 'Actualités & événements à venir' ?></h1>
            <p class="page-lead"><?= $isAr ? 'تابع آخر الأخبار والفعاليات المبرمجة عبر ولاية الجزائر.' : 'Restez informé des dernières nouvelles et des prochaines activités à travers la wilaya.' ?></p>
        </div>
    </section>

    <!-- ═══ CONTENU ═══ -->
    <section class="section section-news" id="actualites">
        <div class="container">
            <div class="actualites-layout">

                <!-- Colonne principale : grille filtrable -->
                <div class="actualites-main">
                    <div class="news-filters" data-reveal>
                        <button class="news-filter-btn active" data-filter="all">
                            <i class="mdi mdi-apps"></i> <?= $isAr ? 'الكل' : 'Tout' ?>
                        </button>
                        <button class="news-filter-btn" data-filter="actualite">
                            <i class="mdi mdi-newspaper"></i> <?= $isAr ? 'أخبار' : 'Actualités' ?>
                        </button>
                        <button class="news-filter-btn" data-filter="evenement">
                            <i class="mdi mdi-calendar-star"></i> <?= $isAr ? 'أحداث' : 'Événements' ?>
                        </button>
                    </div>

                    <div class="news-grid" id="newsGrid">
                        <?php foreach ($items as $item): $hue = ((int) $item['id'] * 47) % 360; ?>
                            <div class="news-card" data-type="<?= e($item['type']) ?>" data-reveal>
                                <?php if ($item['image']): ?>
                                    <div class="news-card-image">
                                        <img src="<?= e(asset((string) $item['image'])) ?>" alt="<?= e($titreItem($item)) ?>" loading="lazy">
                                        <?= $dateBadge($item['date_event']) ?>
                                        <span class="news-type-badge <?= $item['type'] === 'evenement' ? 'type-event' : 'type-news' ?>">
                                            <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                            <?= $item['type'] === 'evenement' ? ($isAr ? 'حدث' : 'Événement') : ($isAr ? 'خبر' : 'Actualité') ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="news-card-image news-card-placeholder" style="background:linear-gradient(135deg, hsl(<?= $hue ?>, 52%, 88%), hsl(<?= ($hue + 45) % 360 ?>, 45%, 76%))">
                                        <?= $dateBadge($item['date_event']) ?>
                                        <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                        <span class="news-type-badge <?= $item['type'] === 'evenement' ? 'type-event' : 'type-news' ?>">
                                            <i class="mdi mdi-<?= $item['type'] === 'evenement' ? 'calendar-star' : 'newspaper' ?>"></i>
                                            <?= $item['type'] === 'evenement' ? ($isAr ? 'حدث' : 'Événement') : ($isAr ? 'خبر' : 'Actualité') ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="news-card-body">
                                    <div class="news-card-meta">
                                        <?php if ($item['date_event']): ?>
                                            <span class="news-date">
                                                <i class="mdi mdi-calendar-outline"></i>
                                                <?= e((new DateTimeImmutable((string) $item['date_event']))->format('d M Y')) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($item['lieu']): ?>
                                            <span class="news-location">
                                                <i class="mdi mdi-map-marker-outline"></i>
                                                <?= e((string) ($isAr ? ($item['lieu_ar'] ?: $item['lieu']) : $item['lieu'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <h3 class="news-card-title"><?= e($titreItem($item)) ?></h3>

                                    <?php if ($descItem($item) !== ''): ?>
                                        <p class="news-card-desc"><?= e(mb_strimwidth($descItem($item), 0, 130, '…')) ?></p>
                                    <?php endif; ?>

                                    <?php if ($item['url_externe']): ?>
                                        <a href="<?= e((string) $item['url_externe']) ?>" target="_blank" rel="noopener" class="news-card-link">
                                            <?= $isAr ? 'المزيد' : 'En savoir plus' ?>
                                            <i class="mdi mdi-arrow-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($items)): ?>
                            <div class="news-empty">
                                <i class="mdi mdi-newspaper-variant-outline"></i>
                                <p><?= $isAr ? 'لا أخبار حالياً.' : 'Aucune actualité pour le moment.' ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Colonne latérale : prochains événements -->
                <aside class="actualites-aside" data-reveal>
                    <div class="aside-card">
                        <div class="aside-head">
                            <span class="aside-icon"><i class="mdi mdi-calendar-clock"></i></span>
                            <h3 class="aside-title"><?= $isAr ? 'فعاليات قادمة' : 'Prochains événements' ?></h3>
                            <?php if (!empty($prochains)): ?>
                                <span class="aside-count"><?= count($prochains) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($prochains)): ?>
                            <ul class="upcoming-list">
                                <?php foreach ($prochains as $ev): ?>
                                    <li class="upcoming-item">
                                        <?php if ($ev['date_event']): ?>
                                            <span class="upcoming-date">
                                                <span class="upcoming-day"><?= e((new DateTimeImmutable((string) $ev['date_event']))->format('d')) ?></span>
                                                <span class="upcoming-month"><?= e($mois[(int) (new DateTimeImmutable((string) $ev['date_event']))->format('n') - 1]) ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="upcoming-date upcoming-date-empty"><i class="mdi mdi-calendar-question"></i></span>
                                        <?php endif; ?>
                                        <div class="upcoming-info">
                                            <h4><?= e($titreItem($ev)) ?></h4>
                                            <p>
                                                <?php if ($ev['heure']): ?>
                                                    <span class="upcoming-meta"><i class="mdi mdi-clock-outline"></i><?= e((string) $ev['heure']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($ev['lieu']): ?>
                                                    <span class="upcoming-meta"><i class="mdi mdi-map-marker-outline"></i><?= e((string) ($isAr ? ($ev['lieu_ar'] ?: $ev['lieu']) : $ev['lieu'])) ?></span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <i class="mdi mdi-chevron-right upcoming-arrow" aria-hidden="true"></i>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <a class="aside-cta" href="#newsGrid" data-filter-go="evenement">
                                <?= $isAr ? 'كل الفعاليات' : 'Tous les événements' ?>
                                <i class="mdi mdi-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <p class="aside-empty"><?= $isAr ? 'لا توجد فعاليات قادمة.' : 'Aucun événement à venir.' ?></p>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <style>
        /* ═══ PAGE ACTUALITÉS ═══ */
        .page-hero {
            position: relative;
            padding: 72px 0 28px;
            text-align: center;
            background:
                radial-gradient(720px 220px at 20% 0%, var(--theme-hero-gradient-1, rgba(22,163,74,0.16)), transparent 70%),
                radial-gradient(620px 200px at 85% 10%, var(--theme-hero-gradient-2, rgba(34,197,94,0.08)), transparent 70%);
        }
        .page-title {
            font-size: clamp(1.9rem, 4vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin: 0 0 12px;
            color: var(--text, #1e293b);
        }
        .page-lead { color: var(--text-muted, #64748b); font-size: 1.05rem; line-height: 1.7; margin: 0; }

        .actualites-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 36px;
            align-items: start;
        }
        .actualites-main { min-width: 0; }
        .actualites-main .news-filters { justify-content: flex-start; margin-bottom: 28px; }

        .actualites-aside { position: sticky; top: calc(var(--header-h, 76px) + 20px); }
        .aside-card {
            position: relative;
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border, rgba(148, 163, 184, 0.2));
            border-radius: var(--theme-border-radius, 18px);
            padding: 22px;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .aside-card::before {
            content: '';
            position: absolute;
            inset-inline: 0 0;
            top: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--theme-primary, #16a34a), var(--theme-secondary, #22c55e), var(--theme-tertiary, #0ea5e9));
        }
        .aside-card::after {
            content: '';
            position: absolute;
            inset-inline-end: -70px;
            top: -70px;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--theme-hero-gradient-1, rgba(22, 163, 74, 0.16)), transparent 70%);
            pointer-events: none;
        }

        .aside-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }
        .aside-icon {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            font-size: 1.35rem;
            color: #fff;
            background: linear-gradient(135deg, var(--theme-primary, #16a34a), var(--theme-secondary, #22c55e));
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.3);
        }
        .aside-title {
            font-size: 1.02rem;
            font-weight: 800;
            margin: 0;
            color: var(--text, #1e293b);
            line-height: 1.2;
        }
        .aside-count {
            margin-inline-start: auto;
            min-width: 26px;
            height: 26px;
            padding: 0 8px;
            display: grid;
            place-items: center;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--theme-primary, #16a34a);
            background: rgba(22, 163, 74, 0.12);
            border: 1px solid rgba(22, 163, 74, 0.25);
        }

        .upcoming-list { list-style: none; margin: 0; padding: 0; position: relative; z-index: 1; }
        .upcoming-item {
            position: relative;
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 13px 10px;
            margin-inline: -10px;
            border-radius: 14px;
            border: 1px solid transparent;
            transition: transform 0.28s var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1)), background 0.28s, border-color 0.28s, box-shadow 0.28s;
        }
        .upcoming-item:first-child { padding-top: 13px; }
        .upcoming-item:hover {
            background: rgba(22, 163, 74, 0.06);
            border-color: rgba(22, 163, 74, 0.16);
            transform: translateX(-4px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        [dir="rtl"] .upcoming-item:hover { transform: translateX(4px); }
        .upcoming-item + .upcoming-item { border-top: 1px dashed var(--border, rgba(148, 163, 184, 0.2)); }

        .upcoming-date {
            flex: 0 0 auto;
            width: 54px;
            text-align: center;
            color: #fff;
            border-radius: 14px;
            padding: 9px 0 7px;
            background: linear-gradient(160deg, var(--theme-primary, #16a34a), var(--theme-secondary, #22c55e));
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.28);
            position: relative;
        }
        .upcoming-date::after {
            content: '';
            position: absolute;
            inset-inline-start: 8px;
            top: 5px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.25);
        }
        .upcoming-date .upcoming-day {
            display: block;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1.1;
        }
        .upcoming-date .upcoming-month {
            display: block;
            font-size: 0.66rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            opacity: 0.95;
            margin-top: 2px;
        }
        .upcoming-date-empty { background: rgba(148, 163, 184, 0.25); color: var(--text-muted, #64748b); box-shadow: none; }
        .upcoming-date-empty::after { display: none; }
        .upcoming-date-empty i { font-size: 1.3rem; line-height: 1.9; }

        .upcoming-info { min-width: 0; flex: 1 1 auto; }
        .upcoming-info h4 {
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0 0 6px;
            color: var(--text, #1e293b);
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            transition: color 0.25s;
        }
        .upcoming-item:hover .upcoming-info h4 { color: var(--theme-primary, #16a34a); }
        .upcoming-info p { margin: 0; font-size: 0.8rem; color: var(--text-muted, #64748b); line-height: 1.6; }
        .upcoming-meta {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-inline-end: 12px;
        }
        .upcoming-meta i { font-size: 0.9rem; color: var(--theme-secondary, #22c55e); }

        .upcoming-arrow {
            flex: 0 0 auto;
            font-size: 1.2rem;
            color: var(--theme-primary, #16a34a);
            opacity: 0;
            transform: translateX(-8px);
            transition: opacity 0.25s, transform 0.25s;
        }
        [dir="rtl"] .upcoming-arrow { transform: translateX(8px); }
        .upcoming-item:hover .upcoming-arrow { opacity: 1; transform: translateX(0); }

        .aside-cta {
            position: relative;
            z-index: 1;
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 16px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--theme-primary, #16a34a);
            background: rgba(22, 163, 74, 0.08);
            border: 1px solid rgba(22, 163, 74, 0.22);
            transition: background 0.25s, color 0.25s, transform 0.25s;
        }
        .aside-cta i { transition: transform 0.25s; }
        .aside-cta:hover {
            background: var(--theme-primary, #16a34a);
            color: #fff;
            text-decoration: none;
            transform: translateY(-2px);
        }
        .aside-cta:hover i { transform: translateX(4px); }
        [dir="rtl"] .aside-cta:hover i { transform: translateX(-4px); }
        .aside-empty { margin: 0; color: var(--text-muted, #64748b); position: relative; z-index: 1; }

        .news-date-badge {
            position: absolute;
            top: 12px;
            inset-inline-start: 12px;
            z-index: 2;
            background: rgba(255, 255, 255, 0.94);
            color: #1e293b;
            border-radius: 12px;
            text-align: center;
            padding: 6px 10px 5px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.16);
            backdrop-filter: blur(4px);
        }
        .news-date-badge .nbd-day { display: block; font-size: 1.15rem; font-weight: 800; line-height: 1; }
        .news-date-badge .nbd-month { display: block; font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--theme-primary, #16a34a); margin-top: 2px; }

        .news-card-placeholder > i { color: rgba(255, 255, 255, 0.85); }

        @media (max-width: 1024px) {
            .actualites-layout { grid-template-columns: 1fr; }
            .actualites-aside { position: static; }
        }
        @media (max-width: 640px) {
            .page-hero { padding: 48px 0 20px; }
            .actualites-main .news-filters { justify-content: center; flex-wrap: wrap; }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-filter-go]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var target = document.querySelector('.news-filter-btn[data-filter="' + link.getAttribute('data-filter-go') + '"]');
                if (target) { target.click(); }
            });
        });
    });
    </script>
</div>
