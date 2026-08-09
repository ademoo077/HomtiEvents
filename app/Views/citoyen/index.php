<?php
/** @var array $user @var string $role @var array $upcoming @var array $past @var array $albums @var array $stats */
use App\Helpers\Database;
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
$locale = I18n::locale();

$firstName = $user['prenom'] ?? ($user['nom'] ?? '');
$lastInitial = mb_substr((string) ($user['nom'] ?? ''), 0, 1);
$firstInitial = mb_substr((string) ($user['prenom'] ?? ''), 0, 1);
$initials = strtoupper($firstInitial . $lastInitial) ?: '?';
$greetKey = (int) date('G') < 18 ? 'citoyen.greeting_day' : 'citoyen.greeting_evening';

try {
    $dateFormatter = new IntlDateFormatter(
        $locale === 'ar' ? 'ar' : 'fr_FR',
        IntlDateFormatter::NONE,
        IntlDateFormatter::NONE,
        null,
        IntlDateFormatter::GREGORIAN,
        'EEEE d MMMM yyyy'
    );
    $todayLabel = $dateFormatter->format(new DateTimeImmutable());
} catch (Throwable $e) {
    $todayLabel = date('d/m/Y');
}
?>

<!-- ═══ HERO ═══ -->
<section class="citoyen-hero" data-reveal>
    <div class="citoyen-hero-top">
        <div class="citoyen-hero-avatar" aria-hidden="true"><?= e($initials) ?></div>
        <div class="citoyen-hero-info">
            <span class="citoyen-hero-greet"><?= e(__($greetKey)) ?>,</span>
            <h1 class="citoyen-hero-name"><?= e($firstName) ?></h1>
            <span class="citoyen-hero-date"><i class="mdi mdi-calendar-blank-outline"></i> <?= e($todayLabel) ?></span>
        </div>
        <a class="citoyen-hero-chips-link" href="<?= url('citoyen/participations') ?>" title="<?= e(__('citoyen.nav_my_participations')) ?>">
            <span class="citoyen-hero-chips"><i class="mdi mdi-clipboard-check-outline"></i> <?= (int) ($stats['participations'] ?? 0) ?></span>
            <small><?= e(__('citoyen.nav_my_participations')) ?></small>
        </a>
    </div>
    <p class="citoyen-hero-sub"><?= e(__('citoyen.greeting_sub')) ?></p>
    <a class="citoyen-hero-action" href="<?= url('qrcode/scan-optimise') ?>">
        <span class="citoyen-hero-action-icon"><i class="mdi mdi-qrcode-scan"></i></span>
        <span class="citoyen-hero-action-text"><?= e(__('citoyen.scan_qr')) ?></span>
        <i class="mdi mdi-chevron-right citoyen-hero-action-arrow"></i>
    </a>
</section>

<!-- ═══ STATS ═══ -->
<section class="citoyen-section" id="top">
    <div class="citoyen-stats-grid">
        <div class="citoyen-stat">
            <i class="mdi mdi-calendar-star-outline stat-icon" aria-hidden="true"></i>
            <div class="citoyen-stat-value"><?= (int) ($stats['evenements_à_venir'] ?? 0) ?></div>
            <div class="citoyen-stat-label"><?= e(__('citoyen.stats_upcoming')) ?></div>
        </div>
        <div class="citoyen-stat">
            <i class="mdi mdi-history stat-icon" aria-hidden="true"></i>
            <div class="citoyen-stat-value"><?= (int) ($stats['evenements_passés'] ?? 0) ?></div>
            <div class="citoyen-stat-label"><?= e(__('citoyen.stats_past')) ?></div>
        </div>
        <div class="citoyen-stat">
            <i class="mdi mdi-image-multiple-outline stat-icon" aria-hidden="true"></i>
            <div class="citoyen-stat-value"><?= (int) ($stats['albums'] ?? 0) ?></div>
            <div class="citoyen-stat-label"><?= e(__('citoyen.stats_albums')) ?></div>
        </div>
    </div>
</section>

<!-- ═══ EVENEMENTS À VENIR ═══ -->
<section class="citoyen-section" id="evenements">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-calendar-month-outline" aria-hidden="true"></i> <?= e(__('citoyen.upcoming_events')) ?></h2>
        <div class="citoyen-search">
            <i class="mdi mdi-magnify"></i>
            <input type="search" id="eventSearch" placeholder="<?= e(__('citoyen.search_placeholder')) ?>" aria-label="<?= e(__('citoyen.search_events')) ?>">
        </div>
    </div>
    <div class="citoyen-event-list" id="upcomingList">
        <?php if (! empty($upcoming)): ?>
            <?php foreach ($upcoming as $ev): ?>
            <a class="citoyen-card" href="<?= url('citoyen/evenement/' . (int) $ev['id']) ?>" data-reveal>
                <div class="citoyen-card-date">
                    <span class="citoyen-card-day"><?= e((new DateTimeImmutable((string) $ev['date_evenement']))->format('d')) ?></span>
                    <span class="citoyen-card-month"><?= e((new DateTimeImmutable((string) $ev['date_evenement']))->format('M')) ?></span>
                </div>
                <div class="citoyen-card-body">
                    <h3 class="citoyen-card-title"><?= e($ev['adresse']) ?></h3>
                    <p class="citoyen-card-meta">
                        <i class="mdi mdi-map-marker-outline"></i> <?= e($ev['commune_nom'] ?? '') ?>
                        <?php if (! empty($ev['heure'])): ?>
                            <span class="citoyen-card-heure"><?= e($ev['heure']) ?></span>
                        <?php endif; ?>
                    </p>
                    <span class="badge badge-programme"><?= e(__('evenements.statut_programme')) ?></span>
                </div>
                <span class="citoyen-card-scan-btn" aria-hidden="true">
                    <i class="mdi mdi-qrcode-scan"></i>
                </span>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="citoyen-empty"><?= e(__('citoyen.no_upcoming')) ?></div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══ EVENEMENTS PROCHES ═══ -->
<section class="citoyen-section" id="proches">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-map-marker-radius-outline" aria-hidden="true"></i> <?= e(__('citoyen.nearby_events')) ?></h2>
        <button class="citoyen-btn citoyen-btn-primary" id="btnNearby" type="button">
            <i class="mdi mdi-crosshairs-gps"></i> <?= e(__('citoyen.find_nearby')) ?>
        </button>
    </div>
    <div class="citoyen-event-list" id="nearbyList">
        <div class="citoyen-empty" id="nearbyEmpty"><?= e(__('citoyen.nearby_hint')) ?></div>
    </div>
</section>

<!-- ═══ EVENEMENTS PASSÉS ═══ -->
<section class="citoyen-section" id="passe">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-history" aria-hidden="true"></i> <?= e(__('citoyen.past_events')) ?></h2>
    </div>
    <div class="citoyen-event-list" id="pastList">
        <?php if (! empty($past)): ?>
            <?php foreach ($past as $ev):
                $assoc = null;
                if (! empty($ev['association_id'])) {
                    $assoc = Database::one('SELECT id, nom, numero_agrement, valide FROM associations WHERE id = ?', [(int) $ev['association_id']]);
                }
            ?>
            <a class="citoyen-card citoyen-card-past" href="<?= url('citoyen/evenement/' . (int) $ev['id']) ?>" data-reveal>
                <div class="citoyen-card-date">
                    <span class="citoyen-card-day"><?= e((new DateTimeImmutable((string) $ev['date_evenement']))->format('d')) ?></span>
                    <span class="citoyen-card-month"><?= e((new DateTimeImmutable((string) $ev['date_evenement']))->format('M')) ?></span>
                </div>
                <div class="citoyen-card-body">
                    <h3 class="citoyen-card-title"><?= e($ev['adresse']) ?></h3>
                    <p class="citoyen-card-meta">
                        <i class="mdi mdi-map-marker-outline"></i> <?= e($ev['commune_nom'] ?? '') ?>
                    </p>
                    <?php if ($assoc !== null): ?>
                        <div class="mt-1"><?= association_badge($assoc) ?></div>
                    <?php endif; ?>
                    <span class="badge badge-termine"><?= e(__('evenements.statut_termine')) ?></span>
                </div>
                <i class="mdi mdi-chevron-right citoyen-card-arrow"></i>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="citoyen-empty"><?= e(__('citoyen.no_past')) ?></div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══ ALBUMS ═══ -->
<section class="citoyen-section" id="albums">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-image-multiple-outline" aria-hidden="true"></i> <?= e(__('citoyen.albums')) ?></h2>
    </div>
    <?php if (! empty($albums)): ?>
    <div class="citoyen-album-grid">
        <?php foreach ($albums as $al): ?>
        <a class="citoyen-album-card" href="<?= url('citoyen/albums/' . (int) $al['id']) ?>" data-reveal>
            <div class="citoyen-album-cover">
                <?php if (! empty($al['couverture']) && is_file(public_path((string) $al['couverture']))): ?>
                    <img src="<?= asset((string) $al['couverture']) ?>" alt="<?= e($al['titre']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="citoyen-album-placeholder">
                        <i class="mdi mdi-image-outline"></i>
                    </div>
                <?php endif; ?>
                <span class="citoyen-album-count"><i class="mdi mdi-image"></i> <?= (int) $al['nb_photos'] ?></span>
            </div>
            <div class="citoyen-album-body">
                <h3 class="citoyen-album-title"><?= e($al['titre']) ?></h3>
                <p class="citoyen-album-meta">
                    <i class="mdi mdi-map-marker-outline"></i> <?= e($al['adresse']) ?>
                </p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="citoyen-empty"><?= e(__('citoyen.no_albums')) ?></div>
    <?php endif; ?>
</section>

<!-- ═══ SCAN QR ═══ -->
<section class="citoyen-section" id="scan">
    <a class="citoyen-scan-cta" href="<?= url('qrcode/scan-optimise') ?>" data-reveal>
        <div class="citoyen-scan-cta-icon">
            <i class="mdi mdi-qrcode-scan"></i>
        </div>
        <div class="citoyen-scan-cta-body">
            <h3 class="citoyen-scan-cta-title"><?= e(__('citoyen.scan_qr')) ?></h3>
            <p class="citoyen-scan-cta-text"><?= e(__('citoyen.scan_hint')) ?></p>
        </div>
        <i class="mdi mdi-chevron-right citoyen-scan-cta-arrow"></i>
    </a>
</section>