<?php
/** @var array $user @var string $role @var array $upcoming @var array $past @var array $albums @var array $stats @var array $gamification @var array $recommandations */
use App\Helpers\I18n;
use App\Helpers\Gamification;
use App\Helpers\Session;

$isAr = I18n::direction() === 'rtl';
$locale = I18n::locale();
$csrf = csrf_token();

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
        <a class="citoyen-hero-chips-link" href="<?= url('citoyen/favoris') ?>" title="<?= e(__('citoyen.nav_favoris')) ?>">
            <span class="citoyen-hero-chips"><i class="mdi mdi-heart-outline"></i> <?= (int) ($stats['favoris'] ?? 0) ?></span>
            <small><?= e(__('citoyen.nav_favoris')) ?></small>
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
        <div class="citoyen-stat" data-reveal style="animation-delay:.05s">
            <i class="mdi mdi-calendar-star-outline stat-icon" aria-hidden="true"></i>
            <div class="citoyen-stat-value"><?= (int) ($stats['evenements_à_venir'] ?? 0) ?></div>
            <div class="citoyen-stat-label"><?= e(__('citoyen.stats_upcoming')) ?></div>
        </div>
        <div class="citoyen-stat" data-reveal style="animation-delay:.1s">
            <i class="mdi mdi-history stat-icon" aria-hidden="true"></i>
            <div class="citoyen-stat-value"><?= (int) ($stats['evenements_passés'] ?? 0) ?></div>
            <div class="citoyen-stat-label"><?= e(__('citoyen.stats_past')) ?></div>
        </div>
        <div class="citoyen-stat" data-reveal style="animation-delay:.15s">
            <i class="mdi mdi-image-multiple-outline stat-icon" aria-hidden="true"></i>
            <div class="citoyen-stat-value"><?= (int) ($stats['albums'] ?? 0) ?></div>
            <div class="citoyen-stat-label"><?= e(__('citoyen.stats_albums')) ?></div>
        </div>
        <div class="citoyen-stat" data-reveal style="animation-delay:.2s">
            <i class="mdi mdi-trophy-outline stat-icon" aria-hidden="true"></i>
            <div class="citoyen-stat-value">
                <?= (int) ($gamification['points'] ?? 0) ?>
                <?php if ((int) ($gamification['rank'] ?? 0) > 0): ?>
                    <span class="citoyen-stat-rank">#<?= (int) $gamification['rank'] ?></span>
                <?php endif; ?>
            </div>
            <div class="citoyen-stat-label"><?= e(__('citoyen.stats_points')) ?></div>
        </div>
    </div>
</section>

<!-- ═══ BADGES ═══ -->
<?php if ((int) ($gamification['badges'] ?? 0) > 0): ?>
<section class="citoyen-section" id="badges">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-medal-outline" aria-hidden="true"></i> <?= $isAr ? 'شاراتي' : 'Mes badges' ?></h2>
        <a href="<?= url('citoyen/profile') ?>" class="citoyen-btn citoyen-btn-ghost"><?= $isAr ? 'الكل' : 'Tout voir' ?> <i class="mdi mdi-arrow-right"></i></a>
    </div>
    <div class="citoyen-badges-row">
        <?php
        $userBadges = Gamification::badgesOf(Session::userId());
        foreach (array_slice($userBadges, 0, 6) as $badge):
        ?>
        <div class="citoyen-badge-chip" title="<?= e($badge['description'] ?? $badge['nom'] ?? '') ?>">
            <span class="citoyen-badge-dot" style="background:<?= e($badge['couleur'] ?? '#D4AF37') ?>"></span>
            <span><?= e($badge['nom'] ?? '') ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══ RECOMMANDATIONS ═══ -->
<?php if (! empty($recommandations)): ?>
<section class="citoyen-section" id="recommandations">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-lightbulb-on-outline" aria-hidden="true"></i> <?= e(__('citoyen.recommendations_title')) ?></h2>
        <a class="citoyen-section-more" href="<?= url('citoyen/explorer') ?>"><?= $isAr ? 'عرض الكل' : 'Tout voir' ?></a>
    </div>
    <div class="citoyen-reco-list">
        <?php foreach ($recommandations as $rec):
            try {
                $recDate = new DateTimeImmutable((string) $rec['date_evenement']);
                $rDay = $recDate->format('d');
                $rMonth = $recDate->format('M');
            } catch (Throwable) {
                $rDay = '—';
                $rMonth = '';
            }
            $reasonKey = 'citoyen.reco_reason_' . ($rec['raison'] ?? 'populaire');
        ?>
            <div class="citoyen-reco-card" data-reveal>
                <a class="citoyen-reco-link" href="<?= url('citoyen/evenement/' . (int) $rec['id']) ?>">
                    <div class="citoyen-card-date">
                        <span class="citoyen-card-day"><?= e($rDay) ?></span>
                        <span class="citoyen-card-month"><?= e($rMonth) ?></span>
                    </div>
                    <div class="citoyen-card-body">
                        <h3 class="citoyen-card-title"><?= e($rec['adresse'] ?? 'Événement') ?></h3>
                        <p class="citoyen-card-meta">
                            <i class="mdi mdi-map-marker-outline"></i> <?= e($rec['commune_nom'] ?? '') ?>
                            <?php if (! empty($rec['heure'])): ?>
                                <span class="citoyen-card-heure"><?= e(mb_substr((string) $rec['heure'], 0, 5)) ?></span>
                            <?php endif; ?>
                        </p>
                        <span class="citoyen-reco-reason"><i class="mdi mdi-lightbulb-on-outline"></i> <?= e(__($reasonKey)) ?></span>
                        <?php if (! empty($rec['anomalies'])): ?>
                            <span class="citoyen-reco-theme"><?= e($rec['anomalies']) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <button type="button" class="citoyen-fav-btn" data-fav-id="<?= (int) $rec['id'] ?>"
                        data-active="false" aria-pressed="false" title="<?= e(__('citoyen.add_favorite')) ?>">
                    <i class="mdi mdi-heart-outline"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

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
            <?php foreach ($upcoming as $ev):
                try {
                    $evDate = new DateTimeImmutable((string) $ev['date_evenement']);
                    $dayLabel = $evDate->format('d');
                    $monthLabel = $evDate->format('M');
                } catch (Throwable) {
                    $dayLabel = '—';
                    $monthLabel = '';
                }
            ?>
            <a class="citoyen-card" href="<?= url('citoyen/evenement/' . (int) $ev['id']) ?>" data-reveal>
                <div class="citoyen-card-date">
                    <span class="citoyen-card-day"><?= e($dayLabel) ?></span>
                    <span class="citoyen-card-month"><?= e($monthLabel) ?></span>
                </div>
                <div class="citoyen-card-body">
                    <h3 class="citoyen-card-title"><?= e($ev['adresse']) ?></h3>
                    <p class="citoyen-card-meta">
                        <i class="mdi mdi-map-marker-outline"></i> <?= e($ev['commune_nom'] ?? '') ?>
                        <?php if (! empty($ev['heure'])): ?>
                            <span class="citoyen-card-heure"><?= e($ev['heure']) ?></span>
                        <?php endif; ?>
                    </p>
                    <?php $enCours = strtoupper((string) ($ev['statut'] ?? '')) === 'EN_COURS'; ?>
                    <span class="badge <?= $enCours ? 'badge-en-cours' : 'badge-programme' ?>"><?= e(__($enCours ? 'evenements.statut_en_cours' : 'evenements.statut_programme')) ?></span>
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
    <div class="citoyen-event-list" id="nearbyList" aria-live="polite">
        <div class="citoyen-empty" id="nearbyEmpty"><?= e(__('citoyen.nearby_hint')) ?></div>
    </div>
</section>

<!-- ═══ EVENEMENTS PASSÉS ═══ -->
<section class="citoyen-section" id="passe">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-history" aria-hidden="true"></i> <?= e(__('citoyen.past_events')) ?></h2>
    </div>
    <div class="citoyen-event-list citoyen-past-collapse collapsed" id="pastList">
        <?php if (! empty($past)): ?>
            <?php foreach ($past as $ev):
                $assoc = ! empty($ev['assoc_nom']) ? [
                    'id'              => $ev['assoc_id'],
                    'nom'             => $ev['assoc_nom'],
                    'numero_agrement' => $ev['assoc_agrement'],
                    'valide'          => $ev['assoc_valide'],
                ] : null;
            ?>
            <a class="citoyen-card citoyen-card-past" href="<?= url('citoyen/evenement/' . (int) $ev['id']) ?>" data-reveal>
                <div class="citoyen-card-date">
                    <?php
                    try {
                        $pastDate = new DateTimeImmutable((string) $ev['date_evenement']);
                        $dayLabel = $pastDate->format('d');
                        $monthLabel = $pastDate->format('M');
                    } catch (Throwable) {
                        $dayLabel = '—';
                        $monthLabel = '';
                    }
                    ?>
                    <span class="citoyen-card-day"><?= e($dayLabel) ?></span>
                    <span class="citoyen-card-month"><?= e($monthLabel) ?></span>
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
            <div class="citoyen-past-fade"></div>
        <?php else: ?>
            <div class="citoyen-empty"><?= e(__('citoyen.no_past')) ?></div>
        <?php endif; ?>
    </div>
    <?php if (! empty($past)): ?>
    <button type="button" class="citoyen-past-toggle" id="btnPastToggle"><?= $isAr ? 'عرض المزيد' : 'Voir plus' ?></button>
    <?php endif; ?>
</section>

<!-- ═══ ALBUMS ═══ -->
<section class="citoyen-section" id="albums">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-image-multiple-outline" aria-hidden="true"></i> <?= e(__('citoyen.albums')) ?></h2>
        <?php if (! empty($albums)): ?>
        <a class="citoyen-section-more" href="<?= url('citoyen/explorer') ?>"><?= $isAr ? 'عرض الكل' : 'Voir tout' ?></a>
        <?php endif; ?>
    </div>
    <?php if (! empty($albums)): ?>
    <div class="citoyen-album-grid">
        <?php foreach ($albums as $al): ?>
        <?php
            $alDate = null;
            try { $alDate = new \DateTimeImmutable((string)($al['date_evenement'] ?? $al['date_creation'] ?? '')); } catch (\Throwable $e) {}
        ?>
        <a class="citoyen-album-card" href="<?= url('citoyen/albums/' . (int) $al['id']) ?>" data-reveal>
            <div class="citoyen-album-cover">
                <?php if (! empty($al['couverture']) && is_file(public_path((string) $al['couverture']))): ?>
                    <img src="<?= asset((string) $al['couverture']) ?>" alt="<?= e($al['titre']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="citoyen-album-placeholder">
                        <i class="mdi mdi-image-multiple-outline"></i>
                    </div>
                <?php endif; ?>
                <span class="citoyen-album-count"><i class="mdi mdi-image"></i> <?= (int) $al['nb_photos'] ?></span>
            </div>
            <div class="citoyen-album-body">
                <h3 class="citoyen-album-title"><?= e($al['titre']) ?></h3>
                <div class="citoyen-album-details">
                    <?php if ($alDate): ?>
                    <span class="citoyen-album-date"><i class="mdi mdi-calendar-outline"></i> <?= e($alDate->format('d M Y')) ?></span>
                    <?php endif; ?>
                    <span class="citoyen-album-loc"><i class="mdi mdi-map-marker-outline"></i> <?= e($al['adresse']) ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="citoyen-album-empty-state">
        <div class="citoyen-album-empty-icon"><i class="mdi mdi-image-multiple-outline"></i></div>
        <h3 class="citoyen-album-empty-title"><?= $isAr ? 'لا توجد ألبومات بعد' : 'Aucun album pour le moment' ?></h3>
        <p class="citoyen-album-empty-text"><?= $isAr ? 'ستظهر هنا ألبومات الصور بعد نشرها من طرف الجمعيات.' : 'Les albums photos apparaîtront ici une fois publiés par les associations.' ?></p>
    </div>
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

<style>
.citoyen-reco-list{display:flex;flex-direction:column;gap:.65rem}
.citoyen-reco-card{position:relative;display:flex;align-items:stretch;gap:.85rem;background:#fff;border:1px solid #EDE7DA;border-radius:16px;padding:.85rem 1rem;transition:box-shadow .2s}
.citoyen-reco-card:hover{box-shadow:0 6px 20px rgba(15,43,34,.08)}
.citoyen-reco-link{flex:1;min-width:0;display:flex;gap:.85rem;align-items:center;text-decoration:none;color:inherit}
.citoyen-reco-reason{display:inline-flex;align-items:center;gap:.25rem;font-size:.72rem;font-weight:600;color:#B8860B;background:#FBF3DF;border-radius:999px;padding:.15rem .5rem;margin-inline-end:.35rem}
.citoyen-reco-reason .mdi{color:#D4AF37}
.citoyen-reco-theme{display:inline-block;font-size:.72rem;color:#6B7280;background:#F3F4F6;border-radius:999px;padding:.15rem .5rem}
.citoyen-fav-btn{flex:0 0 auto;align-self:center;width:38px;height:38px;border-radius:50%;border:1px solid #EDE7DA;background:#fff;color:#9CA3AF;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s;font-size:1.1rem}
.citoyen-fav-btn:hover{border-color:#D4AF37;color:#D4AF37}
.citoyen-fav-btn.active{background:#D4AF37;color:#fff;border-color:#D4AF37}
@media (max-width:520px){.citoyen-reco-link{align-items:flex-start}}
.citoyen-album-grid{display:flex;overflow-x:auto;gap:1rem;padding-bottom:.5rem;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch}
.citoyen-album-card{flex:0 0 230px;scroll-snap-align:start}
.citoyen-past-collapse{position:relative;overflow:hidden;transition:max-height .35s ease}
.citoyen-past-collapse.collapsed{max-height:260px}
.citoyen-past-fade{position:absolute;left:0;right:0;bottom:0;height:4rem;background:linear-gradient(to top,#F9F7F1,transparent);pointer-events:none}
.citoyen-past-toggle{display:block;margin:.75rem auto 0;border:1px solid #D4AF37;color:#B8860B;background:#fff;border-radius:999px;padding:.4rem 1.1rem;font-weight:600;font-size:.82rem;cursor:pointer;transition:.2s}
.citoyen-past-toggle:hover{background:#FBF3DF}
</style>
<script>
(function(){
    'use strict';
    if (!window.WH_CSRF) return;
    var isAr = document.documentElement.dir === 'rtl';
    document.querySelectorAll('.citoyen-fav-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.getAttribute('data-fav-id');
            if (!id) return;
            btn.disabled = true;
            fetch('<?= url('citoyen/favoris/') ?>' + id + '/toggle?ajax=1', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': window.WH_CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then(function(r){ return r.json(); })
              .then(function(res){
                if (res && res.success) {
                    var icon = btn.querySelector('.mdi');
                    if (res.saved) {
                        btn.classList.add('active');
                        btn.setAttribute('data-active','true');
                        btn.setAttribute('aria-pressed','true');
                        if (icon) { icon.classList.remove('mdi-heart-outline'); icon.classList.add('mdi-heart'); }
                        btn.title = btn.title;
                    } else {
                        btn.classList.remove('active');
                        btn.setAttribute('data-active','false');
                        btn.setAttribute('aria-pressed','false');
                        if (icon) { icon.classList.add('mdi-heart-outline'); icon.classList.remove('mdi-heart'); }
                    }
                }
              })
              .catch(function(){})
              .finally(function(){ btn.disabled = false; });
        });
    });

    /* ── Recherche filtre la liste des événements à venir ── */
    var searchBox = document.getElementById('eventSearch');
    var upcomingList = document.getElementById('upcomingList');
    if (searchBox && upcomingList) {
        var cards = Array.prototype.slice.call(upcomingList.querySelectorAll('.citoyen-card'));
        var emptyMsg = <?= json_encode(e(__('citoyen.no_upcoming')), JSON_UNESCAPED_UNICODE) ?>;
        searchBox.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var shown = 0;
            cards.forEach(function (card) {
                var match = !q || card.textContent.toLowerCase().indexOf(q) !== -1;
                card.style.display = match ? '' : 'none';
                if (match) shown++;
            });
            var empty = upcomingList.querySelector('.citoyen-empty');
            if (shown === 0) {
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'citoyen-empty';
                    empty.textContent = emptyMsg;
                    upcomingList.appendChild(empty);
                }
                empty.style.display = '';
            } else if (empty) {
                empty.style.display = 'none';
            }
        });
    }

    /* ── Replier / déplier les événements passés ── */
    var pastToggle = document.getElementById('btnPastToggle');
    var pastList = document.getElementById('pastList');
    if (pastToggle && pastList) {
        pastToggle.addEventListener('click', function () {
            var collapsed = pastList.classList.toggle('collapsed');
            pastToggle.textContent = collapsed
                ? (isAr ? 'عرض المزيد' : 'Voir plus')
                : (isAr ? 'عرض أقل' : 'Réduire');
        });
    }
})();
</script>