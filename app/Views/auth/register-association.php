<?php
use App\Helpers\I18n;
/**
 * Inscription association — Demande d'inscription (public).
 * La demande est soumise à la Wilaya via /admin/association-requests.
 *
 * @var array $errors
 * @var array $old
 */
$old = $old ?? [];
$isAr = I18n::direction() === 'rtl';

$sections = [
    [
        'title' => __('associations.info_association'),
        'icon'  => 'mdi-office-building-outline',
        'fields' => [
            ['name' => 'association_name', 'label' => __('associations.nom'), 'type' => 'text', 'required' => true, 'col' => 12],
            ['name' => 'approval_number', 'label' => __('associations.numero_agrement'), 'type' => 'text', 'required' => true, 'col' => 6],
            ['name' => 'activity_domain', 'label' => __('associations.activity_domain'), 'type' => 'text', 'required' => false, 'col' => 6],
            ['name' => 'phone', 'label' => __('common.telephone'), 'type' => 'text', 'required' => true, 'col' => 6],
            ['name' => 'email', 'label' => __('common.email'), 'type' => 'email', 'required' => true, 'col' => 6],
            ['name' => 'daira_id', 'label' => __('associations.daira'), 'type' => 'daira', 'required' => true, 'col' => 6],
            ['name' => 'commune', 'label' => __('associations.commune'), 'type' => 'commune', 'required' => true, 'col' => 6],
            ['name' => 'wilaya', 'label' => __('common.wilaya'), 'type' => 'hidden', 'required' => false, 'col' => 0, 'value' => 'Alger'],
            ['name' => 'address', 'label' => __('associations.address'), 'type' => 'text', 'required' => false, 'col' => 12],
            ['name' => 'description', 'label' => __('associations.description'), 'type' => 'textarea', 'required' => false, 'col' => 12],
            ['name' => 'approval_file', 'label' => __('associations.approval_file'), 'type' => 'file', 'required' => false, 'col' => 12, 'accept' => '.jpg,.jpeg,.png,.webp,.pdf'],
        ],
    ],
    [
        'title' => __('associations.info_president'),
        'icon'  => 'mdi-account-outline',
        'fields' => [
            ['name' => 'president_lastname', 'label' => __('common.nom'), 'type' => 'text', 'required' => true, 'col' => 6],
            ['name' => 'president_firstname', 'label' => __('common.prenom'), 'type' => 'text', 'required' => true, 'col' => 6],
            ['name' => 'president_phone', 'label' => __('common.telephone'), 'type' => 'text', 'required' => true, 'col' => 6],
            ['name' => 'president_email', 'label' => __('common.email'), 'type' => 'email', 'required' => true, 'col' => 6],
        ],
    ],
    [
        'title' => __('associations.info_securite'),
        'icon'  => 'mdi-lock-outline',
        'fields' => [
            ['name' => 'password', 'label' => __('auth.password'), 'type' => 'password', 'required' => true, 'col' => 6],
            ['name' => 'password_confirmation', 'label' => __('auth.confirm_password'), 'type' => 'password', 'required' => true, 'col' => 6],
        ],
    ],
];
?>
<h2 class="mb-2" style="font-size:1.4rem;font-weight:700">
    <i class="mdi mdi-office-building me-1"></i> <?= e(__('associations.inscription')) ?>
</h2>
<p class="text-muted mb-4" style="font-size:.88rem">
    <?= e(__('associations.inscription_help')) ?><br>
    <?= e(__('associations.inscription_request_help')) ?>
</p>

<div class="mb-3">
    <a href="<?= url('/') ?>" class="d-inline-flex align-items-center gap-1" style="color:#5A6B60;font-size:.85rem;text-decoration:none">
        <i class="mdi mdi-arrow-left"></i> <?= $isAr ? 'العودة للرئيسية' : "Retour à l'accueil" ?>
    </a>
</div>

<form method="post" action="<?= url('auth/register-association') ?>" enctype="multipart/form-data" novalidate>
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
                                      <?= $field['required'] ? 'required' : '' ?>><?= e($old[$field['name']] ?? '') ?></textarea>
                        <?php elseif ($field['type'] === 'file'): ?>
                            <label class="form-label fw-medium" style="font-size:.85rem">
                                <?= e($field['label']) ?>
                            </label>
                            <input type="file"
                                   class="form-control <?= isset($errors[$field['name']]) ? 'is-invalid' : '' ?>"
                                   name="<?= e($field['name']) ?>"
                                   accept="<?= e($field['accept'] ?? '') ?>"
                                   style="border-radius:8px;font-size:.85rem">
                            <div class="form-text" style="font-size:.75rem">JPG, PNG, WebP ou PDF — <?= e(__('associations.approval_file_hint')) ?></div>
                        <?php elseif ($field['type'] === 'daira'): ?>
                            <label class="form-label fw-medium" style="font-size:.85rem">
                                <?= e($field['label']) ?>
                                <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>
                            <select class="form-select" id="daira_id" name="daira_id"
                                    style="border-radius:8px;font-size:.88rem"
                                    <?= $field['required'] ? 'required' : '' ?>>
                                <option value=""><?= $isAr ? 'اختر' : 'Choisir' ?></option>
                                <?php foreach (($dairas ?? []) as $d): ?>
                                    <option value="<?= (int) $d['id'] ?>" <?= (($old['daira_id'] ?? '') == $d['id']) ? 'selected' : '' ?>>
                                        <?= e($d['nom']) ?> (<?= e($d['code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors[$field['name']])): ?><div class="text-danger small"><?= e($errors[$field['name']]) ?></div><?php endif; ?>
                        <?php elseif ($field['type'] === 'commune'): ?>
                            <label class="form-label fw-medium" style="font-size:.85rem">
                                <?= e($field['label']) ?>
                                <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>
                            <select class="form-select" id="commune" name="commune"
                                    style="border-radius:8px;font-size:.88rem"
                                    <?= $field['required'] ? 'required' : '' ?>>
                                <option value=""><?= $isAr ? 'اختر' : 'Choisir' ?></option>
                            </select>
                            <?php if (isset($errors[$field['name']])): ?><div class="text-danger small"><?= e($errors[$field['name']]) ?></div><?php endif; ?>
                        <?php elseif ($field['type'] === 'hidden'): ?>
                            <input type="hidden" name="<?= e($field['name']) ?>" value="<?= e($field['value'] ?? '') ?>">
                        <?php else: ?>
                            <label class="form-label fw-medium" style="font-size:.85rem">
                                <?= e($field['label']) ?>
                                <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>
                            <input type="<?= e($field['type']) ?>"
                                   class="form-control <?= isset($errors[$field['name']]) ? 'is-invalid' : '' ?>"
                                   name="<?= e($field['name']) ?>"
                                   value="<?= e($old[$field['name']] ?? '') ?>"
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

    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="<?= url('auth/login') ?>" class="text-muted" style="font-size:.85rem">
            <i class="mdi mdi-arrow-left me-1"></i> <?= e(__('auth.have_account')) ?>
        </a>
        <button type="submit" class="btn btn-primary px-4" style="border-radius:8px">
            <i class="mdi mdi-send me-1"></i> <?= e(__('associations.inscription_submit')) ?>
        </button>
    </div>
</form>

<script>
(function () {
    var dairas = <?= json_encode($dairas ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var communesByDaira = <?= json_encode($communesByDaira ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var communeSelect = document.getElementById('commune');
    var dairaSelect = document.getElementById('daira_id');
    if (!communeSelect || !dairaSelect) return;

    function populateCommunes(dairaId) {
        communeSelect.innerHTML = '<option value=""><?= $isAr ? 'اختر' : 'Choisir' ?></option>';
        if (!dairaId) return;
        var communes = communesByDaira[parseInt(dairaId, 10)];
        if (!communes) return;
        communes.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.nom;
            opt.textContent = c.nom + (c.nom_ar ? ' (' + c.nom_ar + ')' : '');
            communeSelect.appendChild(opt);
        });
    }

    dairaSelect.addEventListener('change', function () {
        populateCommunes(this.value);
    });

    var oldDaira = <?= json_encode($old['daira_id'] ?? null, JSON_UNESCAPED_UNICODE) ?>;
    if (oldDaira) {
        dairaSelect.value = oldDaira;
        populateCommunes(oldDaira);
        var oldCommune = <?= json_encode($old['commune'] ?? null, JSON_UNESCAPED_UNICODE) ?>;
        if (oldCommune) communeSelect.value = oldCommune;
    }
})();
</script>
