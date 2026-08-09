<?php
/**
 * Espace association — Suivi de la demande d'inscription.
 *
 * @var array|null $request
 * @var array|null $association
 */
use App\Helpers\I18n;

$title = __('associations.inscription_request');
$page  = 'association.demande';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$status = $request['status'] ?? 'pending';
$badge = match ($status) {
    'approved' => 'badge-green',
    'rejected' => 'badge-red',
    default    => 'badge-amber',
};
$statusLabel = match ($status) {
    'approved' => $isAr ? 'مقبول' : 'Approuvée',
    'rejected' => $isAr ? 'مرفوضة' : 'Refusée',
    default    => $isAr ? 'قيد الانتظار' : 'En attente de traitement',
};
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= $isAr ? 'تتبع طلب التسجيل' : 'Suivi de ma demande d\'inscription' ?></h1>
            <p class="wh-page-sub">
                <?= $isAr ? 'حالة طلب تسجيل جمعيتكم ووثيقة الاعتماد' : 'Statut de votre demande d\'inscription et document d\'agrément' ?>
            </p>
        </div>
        <?php if (! empty($association)): ?>
            <a class="btn btn-primary" href="<?= url('association') ?>">
                <i class="mdi mdi-view-dashboard me-1"></i><?= $isAr ? 'فضاء الجمعية' : 'Espace association' ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($request === null): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="wh-empty p-4 text-center">
                    <i class="mdi mdi-file-search-outline" style="font-size:2.5rem;color:var(--wh-text-muted)"></i>
                    <p class="mb-0 mt-2"><?= $isAr ? 'لا يوجد طلب تسجيل مرتبط بهذا الحساب.' : 'Aucune demande d\'inscription associée à ce compte.' ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if (! empty($association)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                <i class="mdi mdi-check-circle"></i>
                <div>
                    <strong><?= $isAr ? 'تمت الموافقة على طلبكم' : 'Votre demande a été approuvée.' ?></strong>
                    <?= $isAr ? 'يمكنكم الآن إدارة فعاليات جمعيتكم من فضاء الجمعية.' : 'Vous pouvez maintenant gérer les événements de votre association depuis l\'espace association.' ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1"><?= e($request['association_name']) ?></h5>
                        <small class="text-muted">
                            <?= $isAr ? 'أرسل في' : 'Soumise le' ?> <?= e(date('d/m/Y', strtotime((string) $request['created_at']))) ?>
                        </small>
                    </div>
                    <span class="wh-badge <?= $badge ?>" style="font-size:.85rem;padding:.45rem .9rem">
                        <?= $statusLabel ?>
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="wh-fact"><span class="text-muted"><?= $isAr ? 'رقم الاعتماد' : 'N° d\'agrément' ?></span><strong><?= e($request['approval_number'] ?? '-') ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="wh-fact"><span class="text-muted"><?= $isAr ? 'مجال النشاط' : 'Domaine d\'activité' ?></span><strong><?= e($request['activity_domain'] ?: '-') ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="wh-fact"><span class="text-muted"><?= $isAr ? 'العنوان' : 'Adresse' ?></span><strong><?= e($request['address'] ?: '-') ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="wh-fact"><span class="text-muted"><?= $isAr ? 'الهاتف' : 'Téléphone' ?></span><strong><?= e($request['phone'] ?: '-') ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (! empty($request['approval_file'])): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <span><i class="mdi mdi-file-document-outline me-2"></i><?= $isAr ? 'وثيقة الاعتماد' : 'Document d\'agrément' ?></span>
                </div>
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="mdi <?= str_ends_with((string) $request['approval_file'], '.pdf') ? 'mdi-file-pdf-box text-danger' : 'mdi-image-outline text-primary' ?>" style="font-size:2rem"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="text-truncate"><?= e(basename((string) $request['approval_file'])) ?></div>
                        <small class="text-muted"><?= $isAr ? 'اضغط للاطلاع أو التحميل' : 'Cliquez pour visualiser ou télécharger' ?></small>
                    </div>
                    <a href="<?= asset('/' . ltrim((string) $request['approval_file'], '/')) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-download me-1"></i><?= $isAr ? 'تحميل' : 'Télécharger' ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($status === 'rejected' && ! empty($request['rejection_reason'])): ?>
            <div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
                <i class="mdi mdi-close-circle mt-1"></i>
                <div>
                    <strong><?= $isAr ? 'سبب الرفض' : 'Motif du refus' ?> :</strong>
                    <div class="mt-1"><?= e($request['rejection_reason']) ?></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
