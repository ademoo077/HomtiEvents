<?php
/** @var array $participations */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
?>
<section class="citoyen-section">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-clipboard-check-outline" aria-hidden="true"></i> <?= e(__('citoyen.nav_my_participations')) ?></h2>
        <div class="citoyen-search">
            <i class="mdi mdi-magnify"></i>
            <input type="search" id="participationSearch" placeholder="<?= $isAr ? 'بحث...' : 'Filtrer...' ?>" aria-label="<?= $isAr ? 'تصفية المشاركات' : 'Filtrer les participations' ?>">
        </div>
    </div>

    <?php if (! empty($participations)): ?>
        <div class="participation-summary" data-reveal>
            <div class="participation-summary-icon"><i class="mdi mdi-clipboard-check-outline" aria-hidden="true"></i></div>
            <div class="participation-summary-body">
                <strong><?= (int) count($participations) ?></strong>
                <span><?= $isAr ? 'مشاركة مسجلة في أحداث الولاية' : 'participations enregistrées aux événements de la wilaya' ?></span>
            </div>
        </div>
        <div class="citoyen-event-list" id="participationList">
            <?php foreach ($participations as $p): ?>
                <a class="citoyen-card" href="<?= url('citoyen/evenement/' . (int) $p['evenement_id']) ?>">
                    <div class="citoyen-card-date">
                        <span class="citoyen-card-day"><?= e((new DateTimeImmutable((string) $p['date_evenement']))->format('d')) ?></span>
                        <span class="citoyen-card-month"><?= e((new DateTimeImmutable((string) $p['date_evenement']))->format('M')) ?></span>
                    </div>
                    <div class="citoyen-card-body">
                        <h3 class="citoyen-card-title"><?= e($p['adresse'] ?? 'Événement') ?></h3>
                        <p class="citoyen-card-meta">
                            <i class="mdi mdi-map-marker-outline"></i> <?= e($p['commune_nom'] ?? '') ?>
                            <span class="participants-count"><i class="mdi mdi-qrcode"></i> <?= e(date('d/m/Y H:i', strtotime((string) ($p['heure_scan'] ?? '')))) ?></span>
                        </p>
                        <span class="badge badge-<?= e(statut_key((string) ($p['event_statut'] ?? 'programme'))) ?>"><?= e(statut_label((string) ($p['event_statut'] ?? 'programme'))) ?></span>
                        <?php if (! empty($p['album_id'])): ?>
                            <span class="badge badge-album">
                                <i class="mdi mdi-image-multiple"></i> <?= e($p['album_titre']) ?> (<?= (int) $p['nb_photos'] ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                    <i class="mdi mdi-chevron-right citoyen-card-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="citoyen-empty"><?= e(__('citoyen.no_participations')) ?></div>
    <?php endif; ?>
</section>

<script>
(function() {
    var searchInput = document.getElementById('participationSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        var items = document.querySelectorAll('#participationList .citoyen-card');

        items.forEach(function(item) {
            item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
})();
</script>
