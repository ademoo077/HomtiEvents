<?php
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="futur-card mb-4">
    <div class="futur-card-header">
        <span><i class="mdi mdi-cog-outline"></i> <?= $isAr ? 'إعدادات المنصة' : 'Paramètres de la plateforme' ?></span>
    </div>
    <div class="futur-card-body">
        <form method="post" action="<?= url('control/settings/save') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label fw-bold"><?= $isAr ? 'اسم المنصة' : 'Nom de la plateforme' ?></label>
                <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? 'Wilaya Harmonia') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?= $isAr ? 'البريد الإلكتروني للإدارة' : 'Email administrateur' ?></label>
                <input type="email" name="admin_email" class="form-control" value="<?= e($settings['admin_email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?= $isAr ? 'اللغة الافتراضية' : 'Langue par défaut' ?></label>
                <select name="default_lang" class="form-select">
                    <option value="fr" <?= ($settings['default_lang'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>Français</option>
                    <option value="ar" <?= ($settings['default_lang'] ?? '') === 'ar' ? 'selected' : '' ?>>العربية</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?= $isAr ? 'حد مشاركات اليوم' : 'Limite de publications par jour' ?></label>
                <input type="number" name="daily_post_limit" class="form-control" value="<?= e((string) ($settings['daily_post_limit'] ?? 50)) ?>" min="1" max="500">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?= $isAr ? 'السماح بالتسجيل' : 'Inscription ouverte' ?></label>
                <select name="registration_open" class="form-select">
                    <option value="1" <?= ($settings['registration_open'] ?? 1) == 1 ? 'selected' : '' ?>><?= $isAr ? 'نعم' : 'Oui' ?></option>
                    <option value="0" <?= ($settings['registration_open'] ?? 1) == 0 ? 'selected' : '' ?>><?= $isAr ? 'لا' : 'Non' ?></option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold"><?= $isAr ? 'وضع الصيانة' : 'Mode maintenance' ?></label>
                <select name="maintenance_mode" class="form-select">
                    <option value="0" <?= ($settings['maintenance_mode'] ?? 0) == 0 ? 'selected' : '' ?>><?= $isAr ? 'مغلق' : 'Désactivé' ?></option>
                    <option value="1" <?= ($settings['maintenance_mode'] ?? 0) == 1 ? 'selected' : '' ?>><?= $isAr ? 'مفعّل' : 'Activé' ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
            </button>
        </form>
    </div>
</div>
