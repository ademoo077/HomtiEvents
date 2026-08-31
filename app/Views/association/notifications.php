<?php
/** @var array $notifications @var int $page $lastPage $total $unread */
use App\Helpers\I18n;

$title = __('common.notifications');
$page  = 'association.notifications';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$notifUrl = static function (array $n): ?string {
    $data = json_decode((string) ($n['data_json'] ?? 'null'), true) ?? [];
    if (isset($data['evenement_id'])) {
        return url('association/' . (int) $data['evenement_id']);
    }
    if (isset($data['link'])) {
        return url((string) $data['link']);
    }
    return null;
};
?>
<div class="wh-page">
    <div style="background:linear-gradient(135deg,#198754 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-bell-outline"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('common.notifications')) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= $isAr ? 'سجل الإشعارات' : 'Vos notifications et alertes' ?></p>
                </div>
            </div>
            <?php if ($unread > 0): ?>
                <button type="button" class="btn btn-warning fw-bold btn-sm" data-notif-read-all>
                    <i class="mdi mdi-check-all me-1"></i><?= $isAr ? 'قراءة الكل' : 'Tout marquer comme lu' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($total > 0): ?>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">
                <?= $unread ?> <?= $isAr ? 'غير مقروء' : 'non lue(s)' ?> — <?= $total ?> <?= $isAr ? 'إشعار' : 'au total' ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if ($notifications === []): ?>
                <div class="futur-empty">
                    <i class="mdi mdi-bell-sleep-outline"></i>
                    <p class="futur-empty-title"><?= $isAr ? 'لا توجد إشعارات بعد' : 'Aucune notification pour le moment.' ?></p>
                    <p class="futur-empty-text"><?= $isAr ? 'Les notifications apparaîtront ici.' : 'Les notifications apparaîtront ici.' ?></p>
                </div>
            <?php else: ?>
                <?php $currentType = null; ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): ?>
                        <?php
                            $nType = (string) ($n['type'] ?? '');
                            $nUrl  = $notifUrl($n);
                            $nRead = (int) ($n['lu'] ?? 0) === 1;

                            if ($nType !== $currentType):
                                $currentType = $nType;
                                $groupUnread = count(array_filter(
                                    $notifications,
                                    static fn (array $x): bool => (string) ($x['type'] ?? '') === $nType && (int) ($x['lu'] ?? 0) === 0
                                ));
                        ?>
                        <li class="list-group-item wh-notif-group-header">
                            <i class="mdi <?= e(\App\Helpers\Notification::typeIcon($nType)) ?> me-2"></i>
                            <span class="fw-semibold"><?= e(\App\Helpers\Notification::typeLabel($nType)) ?></span>
                            <?php if ($groupUnread > 0): ?>
                                <span class="wh-notif-group-count"><?= $groupUnread ?></span>
                            <?php endif; ?>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item <?= $nRead ? 'wh-notif-read' : '' ?>">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="mdi <?= $nRead ? 'mdi-bell-outline text-muted' : 'mdi-bell-ring text-primary' ?> mt-1" style="font-size:1.3rem"></i>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <strong class="<?= $nRead ? 'text-muted' : '' ?>"><?= e($n['titre']) ?></strong>
                                        <?php if (! $nRead): ?>
                                            <span class="wh-notif-dot"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted mb-1"><?= e($n['message_notif']) ?></div>
                                    <small class="text-muted"><?= e(date('d/m/Y H:i', strtotime((string) $n['date_creation']))) ?></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($nUrl !== null): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e($nUrl) ?>">
                                            <i class="mdi mdi-eye me-1"></i><?= $isAr ? 'عرض' : 'Voir' ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (! $nRead): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-notif-id="<?= (int) $n['id'] ?>">
                                            <?= $isAr ? 'مقروء' : 'Marquer lu' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($lastPage > 1): ?>
        <nav aria-label="<?= e(__('common.pagination')) ?>" class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<style>
.wh-notif-read { background: #fff; }
[dir="rtl"] .wh-notif-read { background: #fff; }
.wh-notif-group-header {
    background: #f1f5f9;
    font-size: .85rem;
    padding-top: .45rem;
    padding-bottom: .45rem;
}
[dir="rtl"] .wh-notif-group-header { background: #f1f5f9; }
.wh-notif-group-count {
    display: inline-block;
    min-width: 1.25rem;
    padding: 0 .4rem;
    border-radius: 10px;
    background: var(--wh-primary, #4f46e5);
    color: #fff;
    font-size: .72rem;
    text-align: center;
    margin-inline-start: .4rem;
}
</style>
