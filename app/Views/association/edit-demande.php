<?php
use App\Helpers\I18n;
/**
 * Espace association — Correction de la demande d'inscription.
 *
 * @var array $request
 * @var array $errors
 * @var array $old
 */
$old = $old ?? [];
$isAr = I18n::direction() === 'rtl';
$r = $request;

// Pré-remplir avec les données existantes ou les anciennes valeurs du formulaire
$v = function (string $field) use ($r, $old): string {
    return e($old[$field] ?? (string) ($r[$field] ?? ''));
};

$sections = [
    [
        'title' => $isAr ? 'معلومات الجمعية' : 'Informations de l\'association',
        'icon'  => 'mdi-office-building-outline',
        'fields' => [
            ['name' => 'association_name', 'label' => $isAr ? 'اسم الجمعية' : 'Nom de l\'association', 'type' => 'text', 'required' => true, 'col' => 12],
            ['name' => 'approval_number', 'label' => $isAr ? 'رقم الاعتماد' : 'N° d\'agrément', 'type' => 'text', 'required' => false, 'col' => 6],
            ['name' => 'activity_domain', 'label' => $isAr ? 'مجال النشاط' : 'Domaine d\'activité', 'type' => 'text', 'required' => false, 'col' => 6],
            ['name' => 'phone', 'label' => $isAr ? 'الهاتف' : 'Téléphone', 'type' => 'text', 'required' => false, 'col' => 6],
            ['name' => 'email', 'label' => $isAr ? 'البريد الإلكتروني' : 'Email', 'type' => 'email', 'required' => false, 'col' => 6],
            ['name' => 'commune', 'label' => $isAr ? 'البلدية' : 'Commune', 'type' => 'text', 'required' => false, 'col' => 6],
            ['name' => 'wilaya', 'label' => $isAr ? 'الولاية' : 'Wilaya', 'type' => 'text', 'required' => false, 'col' => 6],
            ['name' => 'address', 'label' => $isAr ? 'العنوان' : 'Adresse', 'type' => 'text', 'required' => false, 'col' => 12],
            ['name' => 'description', 'label' => $isAr ? 'الوصف' : 'Description', 'type' => 'textarea', 'required' => false, 'col' => 12],
            ['name' => 'approval_file', 'label' => $isAr ? 'وثيقة الاعتماد' : 'Document d\'agrément', 'type' => 'file', 'required' => false, 'col' => 12, 'accept' => '.jpg,.jpeg,.png,.webp,.pdf'],
        ],
    ],
    [
        'title' => $isAr ? 'معلومات رئيس الجمعية' : 'Informations du président',
        'icon'  => 'mdi-account-outline',
        'fields' => [
            ['name' => 'president_lastname', 'label' => $isAr ? 'اللقب' : 'Nom', 'type' => 'text', 'required' => true, 'col' => 6],
            ['name' => 'president_firstname', 'label' => $isAr ? 'الاسم' : 'Prénom', 'type' => 'text', 'required' => true, 'col' => 6],
            ['name' => 'president_phone', 'label' => $isAr ? 'الهاتف' : 'Téléphone', 'type' => 'text', 'required' => false, 'col' => 6],
            ['name' => 'president_email', 'label' => $isAr ? 'البريد الإلكتروني' : 'Email', 'type' => 'email', 'required' => false, 'col' => 6],
        ],
    ],
];
?>

<div class="wh-page">
    <div style="background:linear-gradient(135deg,#D97706 0%,#B45309 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-pencil-outline"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= $isAr ? 'تعديل طلب التسجيل' : 'Corriger ma demande d\'inscription' ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= $isAr ? 'عدّل معلوماتكم وأعدو الإرسال' : 'Modifiez vos informations et resoumettez la demande' ?></p>
                </div>
            </div>
            <a class="btn btn-light btn-sm fw-bold" href="<?= url('association/demande') ?>">
                <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'عودة' : 'Retour' ?>
            </a>
        </div>
    </div>

    <?php if (! empty($request['modification_reason'])): ?>
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
            <i class="mdi mdi-alert-circle mt-1"></i>
            <div>
                <strong><?= $isAr ? 'طلب تعديل من الولاية' : 'Modification demandée par la Wilaya' ?> :</strong>
                <div class="mt-1"><?= e($request['modification_reason']) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (! empty($request['rejection_reason'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
            <i class="mdi mdi-close-circle mt-1"></i>
            <div>
                <strong><?= $isAr ? 'سبب الرفض' : 'Motif du refus' ?> :</strong>
                <div class="mt-1"><?= e($request['rejection_reason']) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('association/demande/update') ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <?php foreach ($sections as $section): ?>
            <div class="mb-4">
                <h6 class="fw-bold text-primary mb-3" style="font-size:.95rem">
                    <i class="mdi <?= e($section['icon']) ?> me-1"></i> <?= e($section['title']) ?>
                </h6>
                <div class="row g-3">
                    <?php foreach ($section['fields'] as $field): ?>
                        <div class="col-12 col-md-<?= (int) $field['col'] ?>">
                            <?php if ($field['type'] === 'textarea'): ?>
                                <label class="form-label fw-medium" style="font-size:.85rem">
                                    <?= e($field['label']) ?>
                                    <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <textarea class="form-control <?= isset($errors[$field['name']]) ? 'is-invalid' : '' ?>"
                                          name="<?= e($field['name']) ?>"
                                          rows="3"
                                          style="border-radius:8px;font-size:.88rem"
                                          <?= $field['required'] ? 'required' : '' ?>><?= $v($field['name']) ?></textarea>
                            <?php elseif ($field['type'] === 'file'): ?>
                                <label class="form-label fw-medium" style="font-size:.85rem">
                                    <?= e($field['label']) ?>
                                </label>
                                <?php if (! empty($r[$field['name']])): ?>
                                    <div class="mb-2 d-flex align-items-center gap-2">
                                        <i class="mdi mdi-file-document-outline text-primary" style="font-size:1.2rem"></i>
                                        <small class="text-muted"><?= e(basename((string) $r[$field['name']])) ?></small>
                                        <small class="text-muted">— <?= $isAr ? 'اتركه فارغاً للاحتفاظ بالملف الحالي' : 'Laissez vide pour conserver le fichier actuel' ?></small>
                                    </div>
                                <?php endif; ?>
                                <input type="file"
                                       class="form-control <?= isset($errors[$field['name']]) ? 'is-invalid' : '' ?>"
                                       name="<?= e($field['name']) ?>"
                                       accept="<?= e($field['accept'] ?? '') ?>"
                                       style="border-radius:8px;font-size:.85rem">
                                <div class="form-text" style="font-size:.75rem">JPG, PNG, WebP ou PDF</div>
                            <?php else: ?>
                                <label class="form-label fw-medium" style="font-size:.85rem">
                                    <?= e($field['label']) ?>
                                    <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="<?= e($field['type']) ?>"
                                       class="form-control <?= isset($errors[$field['name']]) ? 'is-invalid' : '' ?>"
                                       name="<?= e($field['name']) ?>"
                                       value="<?= $v($field['name']) ?>"
                                       style="border-radius:8px;font-size:.88rem"
                                       <?= $field['required'] ? 'required' : '' ?>>
                            <?php endif; ?>
                            <?php if (isset($errors[$field['name']])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors[$field['name']]) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr class="my-3">
        <?php endforeach; ?>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= url('association/demande') ?>" class="btn btn-outline-secondary">
                <i class="mdi mdi-close me-1"></i><?= $isAr ? 'إلغاء' : 'Annuler' ?>
            </a>
            <button type="submit" class="btn btn-warning fw-bold px-4">
                <i class="mdi mdi-send me-1"></i><?= $isAr ? 'إرسال التعديلات' : 'Resoumettre la demande' ?>
            </button>
        </div>
    </form>
</div>
