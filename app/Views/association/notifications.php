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
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('common.notifications')) ?></h1>
            <p class="wh-page-sub"><?= $isAr ? 'سجل الإشعارات' : 'Vos notifications et alertes' ?></p>
        </div>
        <?php if ($unread > 0): ?>
            <button type="button" class="btn btn-outline-primary" data-notif-read-all>
                <i class="mdi mdi-check-all me-1"></i><?= $isAr ? 'قراءة الكل' : 'Tout marquer comme lu' ?>
            </button>
        <?php endif; ?>
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
                <div class="wh-empty py-5 text-center">
                    <i class="mdi mdi-bell-sleep-outline text-muted" style="font-size:2.5rem"></i>
                    <p class="mb-0 mt-2 text-muted"><?= $isAr ? 'لا توجد إشعارات بعد' : 'Aucune notification pour le moment.' ?></p>
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): $nUrl = $notifUrl($n); $nRead = (int) ($n['lu'] ?? 0) === 1; ?>
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
</style>
