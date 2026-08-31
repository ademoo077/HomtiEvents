<?php
/** @var array $favoris */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
$csrf = csrf_token();
?>
<section class="citoyen-section">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title"><i class="mdi mdi-heart-outline" aria-hidden="true"></i> <?= e(__('citoyen.nav_favoris')) ?></h2>
        <?php if (! empty($favoris)): ?>
        <a class="citoyen-section-more" href="<?= url('citoyen/explorer') ?>"><?= $isAr ? 'استكشاف' : 'Explorer' ?></a>
        <?php endif; ?>
    </div>

    <?php if (! empty($favoris)): ?>
        <div class="citoyen-event-list" id="favorisList">
            <?php foreach ($favoris as $fav):
                try {
                    $favDate = new DateTimeImmutable((string) $fav['date_evenement']);
                    $dayLabel = $favDate->format('d');
                    $monthLabel = $favDate->format('M');
                } catch (Throwable) {
                    $dayLabel = '—';
                    $monthLabel = '';
                }
            ?>
                <div class="citoyen-card">
                    <a class="citoyen-fav-card-link" href="<?= url('citoyen/evenement/' . (int) $fav['id']) ?>">
                        <div class="citoyen-card-date">
                            <span class="citoyen-card-day"><?= e($dayLabel) ?></span>
                            <span class="citoyen-card-month"><?= e($monthLabel) ?></span>
                        </div>
                        <div class="citoyen-card-body">
                            <h3 class="citoyen-card-title"><?= e($fav['adresse'] ?? 'Événement') ?></h3>
                            <p class="citoyen-card-meta">
                                <i class="mdi mdi-map-marker-outline"></i> <?= e($fav['commune_nom'] ?? '') ?>
                                <?php if (! empty($fav['association_nom'])): ?>
                                    <span class="citoyen-fav-assoc"><i class="mdi mdi-bank-outline"></i> <?= e($fav['association_nom']) ?></span>
                                <?php endif; ?>
                            </p>
                            <span class="badge badge-<?= e(statut_badge_class((string) ($fav['statut'] ?? 'programme'))) ?>"><?= e(statut_label((string) ($fav['statut'] ?? 'programme'))) ?></span>
                            <span class="participants-count"><i class="mdi mdi-account-group"></i> <?= (int) ($fav['participants_count'] ?? 0) ?></span>
                        </div>
                    </a>
                    <button type="button" class="citoyen-fav-btn active" data-fav-id="<?= (int) $fav['id'] ?>"
                            data-active="true" aria-pressed="true" title="<?= e(__('citoyen.remove_favorite')) ?>">
                        <i class="mdi mdi-heart"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="citoyen-empty">
            <i class="mdi mdi-heart-outline" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
            <?= e(__('citoyen.no_favorites')) ?>
        </div>
    <?php endif; ?>
</section>

<style>
.citoyen-card{position:relative;display:flex;align-items:stretch}
.citoyen-fav-card-link{flex:1;min-width:0;display:flex;gap:.85rem;align-items:center;text-decoration:none;color:inherit;padding:.9rem 1rem}
.citoyen-fav-assoc{color:#6B7280;margin-inline-start:.5rem}
.citoyen-fav-btn{flex:0 0 auto;align-self:center;width:38px;height:38px;margin-inline-end:1rem;border-radius:50%;border:none;background:#D4AF37;color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s;font-size:1.1rem}
.citoyen-fav-btn:hover{opacity:.85;transform:scale(1.05)}
@media (max-width:520px){.citoyen-fav-card-link{align-items:flex-start}}
</style>
<script>
(function(){
    'use strict';
    if (!window.WH_CSRF) return;
    var isAr = document.documentElement.dir === 'rtl';
    document.querySelectorAll('#favorisList .citoyen-fav-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.getAttribute('data-fav-id');
            if (!id) return;
            btn.disabled = true;
            fetch('<?= url('citoyen/favoris/') ?>' + id + '/toggle?ajax=1', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': window.WH_CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).then(function(r){ return r.json(); })
              .then(function(res){
                if (res && res.success && !res.saved) {
                    var card = btn.closest('.citoyen-card');
                    if (card) card.remove();
                    var remaining = document.querySelectorAll('#favorisList .citoyen-card');
                    if (!remaining.length) {
                        var empty = document.createElement('div');
                        empty.className = 'citoyen-empty';
                        empty.innerHTML = '<i class="mdi mdi-heart-outline" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>'
                            + (isAr ? 'لا توجد مفضلات بعد' : 'Aucun favori pour le moment');
                        var list = document.getElementById('favorisList');
                        list.innerHTML = '';
                        list.appendChild(empty);
                        var more = document.querySelector('.citoyen-section-header .citoyen-section-more');
                        if (more) more.style.display = 'none';
                    }
                }
              })
              .catch(function(){})
              .finally(function(){ btn.disabled = false; });
        });
    });
})();
</script>
