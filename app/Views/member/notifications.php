<?php
/** @var array $notifications @var int $unreadCount */
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';

$notifUrl = static function (array $n): ?string {
    $data = json_decode((string) ($n['data_json'] ?? 'null'), true) ?? [];
    if (isset($data['evenement_id'])) {
        return url('dashboard');
    }
    if (isset($data['link'])) {
        return url((string) $data['link']);
    }
    return null;
};
?>
<div class="wh-page">
    <div class="wh-hero" style="background:linear-gradient(135deg,#4B5563 0%,#0B5ED7 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-bell-outline me-2"></i><?= e(__('common.notifications')) ?></h1>
                    <p class="wh-hero-sub"><?= $unreadCount > 0 ? ($isAr ? 'لديك ' . $unreadCount . ' إشعار غير مقروء' : $unreadCount . ' notification(s) non lue(s)') : ($isAr ? 'لا توجد إشعارات جديدة' : 'Aucune notification nouvelle') ?></p>
                </div>
                <div class="wh-hero-actions">
                    <?php if ($unreadCount > 0): ?>
                        <form method="post" action="<?= url('notifications/read-all') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-light">
                                <i class="mdi mdi-check-all me-1"></i><?= $isAr ? 'تحديد الكل كمقروء' : 'Tout marquer comme lu' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="futur-card">
        <div class="futur-card-body p-0">
            <?php if ($notifications === []): ?>
                <div class="futur-empty">
                    <i class="mdi mdi-bell-sleep-outline"></i>
                    <p class="futur-empty-title"><?= $isAr ? 'لا توجد إشعارات' : 'Aucune notification' ?></p>
                    <p class="futur-empty-text"><?= $isAr ? 'Les notifications apparaîtront ici.' : 'Les notifications apparaîtront ici.' ?></p>
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): 
                        $nUrl = $notifUrl($n);
                        $nRead = (int) ($n['lu'] ?? 0) === 1;
                    ?>
                        <li class="list-group-item wh-notif-item <?= $nRead ? 'read' : '' ?>">
                            <a href="<?= e($nUrl ?? '#') ?>" class="d-flex gap-3 text-decoration-none text-reset" data-notif-id="<?= (int) $n['id'] ?>" <?= $nUrl === null ? 'data-notif-nolink="1"' : '' ?>>
                                <i class="mdi <?= $nRead ? 'mdi-bell-outline' : 'mdi-bell-ring' ?> wh-notif-icon"></i>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold small wh-notif-title"><?= e($n['titre']) ?></div>
                                    <div class="small text-muted text-truncate"><?= e($n['message_notif']) ?></div>
                                    <small class="text-muted d-block" style="font-size:.7rem">
                                        <?= e(date('d/m H:i', strtotime((string) $n['date_creation']))) ?>
                                    </small>
                                </div>
                                <?php if (! $nRead): ?>
                                    <span class="wh-notif-dot"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var item = e.target.closest('[data-notif-id]');
    if (item) {
        var id = item.getAttribute('data-notif-id');
        fetch('/notifications/' + id + '/read', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.WH_CSRF }
        });
        item.classList.add('read');
        var dot = item.querySelector('.wh-notif-dot');
        if (dot) dot.remove();
        if (item.hasAttribute('data-notif-nolink')) {
            e.preventDefault();
        }
    }
});
</script>
<style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>