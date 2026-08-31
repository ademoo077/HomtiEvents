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
    'approved'               => 'badge-green',
    'rejected'               => 'badge-red',
    'modification_requested' => 'badge-orange',
    default                  => 'badge-amber',
};
$statusLabel = match ($status) {
    'approved'               => $isAr ? 'مقبول' : 'Approuvée',
    'rejected'               => $isAr ? 'مرفوضة' : 'Refusée',
    'modification_requested' => $isAr ? 'مدير modifications' : 'En attente de modifications',
    default                  => $isAr ? 'قيد الانتظار' : 'En attente de traitement',
};
?>
<div class="wh-page">
    <div style="background:linear-gradient(135deg,#198754 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-file-document-outline"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= $isAr ? 'تتبع طلب التسجيل' : 'Suivi de ma demande d\'inscription' ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= $isAr ? 'حالة طلب تسجيل جمعيتكم ووثيقة الاعتماد' : 'Statut de votre demande d\'inscription et document d\'agrément' ?></p>
                </div>
            </div>
            <?php if (! empty($association)): ?>
                <a class="btn btn-warning fw-bold btn-sm" href="<?= url('association') ?>">
                    <i class="mdi mdi-view-dashboard me-1"></i><?= $isAr ? 'فضاء الجمعية' : 'Espace association' ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($request === null): ?>
        <div class="futur-card">
            <div class="futur-card-body">
                <div class="futur-empty">
                    <i class="mdi mdi-file-search-outline"></i>
                    <p class="futur-empty-title"><?= $isAr ? 'لا يوجد طلب تسجيل مرتبط بهذا الحساب.' : 'Aucune demande d\'inscription associée à ce compte.' ?></p>
                    <p class="futur-empty-text"><?= $isAr ? 'Soumettez une demande pour rejoindre la plateforme.' : 'Soumettez une demande pour rejoindre la plateforme.' ?></p>
                    <a href="<?= url('register') ?>" class="btn btn-primary futur-empty-action">
                        <i class="mdi mdi-account-plus me-1"></i><?= $isAr ? 'إنشاء طلب' : 'Créer une demande' ?>
                    </a>
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
            <div class="card border-0 shadow-sm mb-4" style="border-radius:var(--wh-radius);overflow:hidden;">
                <div style="padding:.65rem 1.25rem;background:#ede9fe;border-bottom:1px solid #ddd6fe;display:flex;align-items:center;gap:.5rem;">
                    <span style="width:28px;height:28px;border-radius:7px;background:rgba(124,58,237,.15);display:grid;place-items:center;color:#7c3aed;font-size:.85rem;"><i class="mdi mdi-file-document-outline"></i></span>
                    <span class="fw-bold" style="font-size:.88rem;"><?= $isAr ? 'وثيقة الاعتماد' : 'Document d\'agrément' ?></span>
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

        <?php if ($status === 'modification_requested' && ! empty($request['modification_reason'])): ?>
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
                <i class="mdi mdi-alert-circle mt-1"></i>
                <div>
                    <strong><?= $isAr ? 'طلب تعديل' : 'Modification demandée' ?> :</strong>
                    <div class="mt-1"><?= e($request['modification_reason']) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($status, ['rejected', 'modification_requested'], true)): ?>
            <div class="d-flex gap-2 mt-4">
                <a href="<?= url('association/demande/edit') ?>" class="btn btn-warning fw-bold">
                    <i class="mdi mdi-pencil me-1"></i><?= $isAr ? 'تعديل الطلب' : 'Corriger ma demande' ?>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
