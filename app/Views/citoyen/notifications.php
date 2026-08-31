<?php
/** @var array $notifications @var int $unreadCount */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';

$typeIcons = [
    'participation'     => 'mdi-check-circle-outline',
    'badge'             => 'mdi-medal-outline',
    'evenement_valide'  => 'mdi-check-decagram-outline',
    'evenement_refuse'  => 'mdi-close-circle-outline',
    'association_request' => 'mdi-account-clock-outline',
    'tfa_code'          => 'mdi-shield-key-outline',
    'comment'           => 'mdi-comment-outline',
    'system'            => 'mdi-cog-outline',
];
$typeColors = [
    'participation'     => '#1A4D3E',
    'badge'             => '#D4AF37',
    'evenement_valide'  => '#2E6E5C',
    'evenement_refuse'  => '#E5484D',
    'association_request' => '#1A4D3E',
    'tfa_code'          => '#D4AF37',
    'comment'           => '#5A6B60',
    'system'            => '#5A6B60',
];
?>

<section class="citoyen-section">
    <div class="citoyen-section-header">
        <h2 class="citoyen-section-title">
            <i class="mdi mdi-bell-outline" aria-hidden="true"></i>
            <?= $isAr ? 'الإشعارات' : 'Notifications' ?>
            <?php if ($unreadCount > 0): ?>
                <span class="citoyen-notif-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </h2>
        <?php if ($unreadCount > 0): ?>
        <form method="post" action="<?= url('citoyen/notifications/read-all') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="citoyen-btn-ghost">
                <i class="mdi mdi-check-all"></i> <?= $isAr ? 'تعيين الكل كمقروء' : 'Tout marquer lu' ?>
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="citoyen-empty" style="padding:3rem 1rem;text-align:center">
            <i class="mdi mdi-bell-off-outline" style="font-size:2.5rem;color:var(--cit-muted);display:block;margin-bottom:.5rem"></i>
            <p><?= $isAr ? 'لا توجد إشعارات بعد' : 'Aucune notification pour le moment' ?></p>
        </div>
    <?php else: ?>
        <div class="citoyen-notif-list">
            <?php foreach ($notifications as $notif):
                $type = (string) ($notif['type'] ?? 'system');
                $icon = $typeIcons[$type] ?? 'mdi-bell-outline';
                $color = $typeColors[$type] ?? '#5A6B60';
                $isUnread = ! (bool) ($notif['lu'] ?? false);
                $dateLabel = time_ago((string) ($notif['date_creation'] ?? ''));
            ?>
                <div class="citoyen-notif-item<?= $isUnread ? ' unread' : '' ?>" data-id="<?= (int) $notif['id'] ?>"<?= $isUnread ? ' role="button" tabindex="0" data-action="mark-read" aria-label="' . e($isAr ? 'تعيين كمقروء' : 'Marquer comme lu') . '"' : '' ?>>
                    <div class="citoyen-notif-icon" style="color:<?= $color ?>;background:<?= $color ?>12" aria-hidden="true">
                        <i class="mdi <?= e($icon) ?>"></i>
                    </div>
                    <div class="citoyen-notif-body">
                        <strong class="citoyen-notif-title"><?= e((string) ($notif['titre'] ?? '')) ?></strong>
                        <p class="citoyen-notif-msg"><?= e((string) ($notif['message_notif'] ?? '')) ?></p>
                        <span class="citoyen-notif-time"><i class="mdi mdi-clock-outline"></i> <?= e($dateLabel) ?></span>
                    </div>
                    <?php if ($isUnread): ?>
                        <span class="citoyen-notif-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    'use strict';

    var badge = document.querySelector('.citoyen-notif-badge');

    function decrementBadge() {
        if (!badge) return;
        var n = parseInt(badge.textContent, 10);
        if (!isNaN(n)) {
            n = Math.max(0, n - 1);
            if (n === 0) {
                badge.remove();
            } else {
                badge.textContent = String(n);
            }
        }
    }

    function markRead(el) {
        var id = el.dataset.id;
        if (!id || el.dataset.busy) return;
        el.dataset.busy = '1';
        fetch('<?= url('notifications/') ?>' + id + '/read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.WH_CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function () {
            el.classList.remove('unread');
            delete el.dataset.action;
            el.removeAttribute('role');
            el.removeAttribute('tabindex');
            el.removeAttribute('aria-label');
            var dot = el.querySelector('.citoyen-notif-dot');
            if (dot) dot.remove();
            decrementBadge();
        }).catch(function () { delete el.dataset.busy; });
    }

    document.querySelectorAll('.citoyen-notif-item[data-action]').forEach(function (el) {
        el.addEventListener('click', function () { markRead(el); });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                markRead(el);
            }
        });
    });
})();
</script>
