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
        <form method="post" action="<?= url('control/settings/save') ?>" novalidate>
            <?= csrf_field() ?>

            <div class="futur-form-row">
                <div class="futur-form-group">
                    <label class="futur-form-label" for="site_name"><?= $isAr ? 'اسم المنصة' : 'Nom de la plateforme' ?> <span class="required">*</span></label>
                    <input type="text" class="futur-form-control" id="site_name" name="site_name" value="<?= e($settings['site_name'] ?? 'حومتي ايفانت') ?>" required>
                </div>
                <div class="futur-form-group">
                    <label class="futur-form-label" for="admin_email"><?= $isAr ? 'البريد الإلكتروني للإدارة' : 'Email administrateur' ?></label>
                    <input type="email" class="futur-form-control" id="admin_email" name="admin_email" value="<?= e($settings['admin_email'] ?? '') ?>">
                </div>
            </div>

            <div class="futur-form-row">
                <div class="futur-form-group">
                    <label class="futur-form-label" for="default_lang"><?= $isAr ? 'اللغة الافتراضية' : 'Langue par défaut' ?></label>
                    <select class="futur-form-control" id="default_lang" name="default_lang">
                        <option value="fr" <?= ($settings['default_lang'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>Français</option>
                        <option value="ar" <?= ($settings['default_lang'] ?? '') === 'ar' ? 'selected' : '' ?>>العربية</option>
                    </select>
                </div>
                <div class="futur-form-group">
                    <label class="futur-form-label" for="daily_post_limit"><?= $isAr ? 'حد مشاركات اليوم' : 'Limite de publications par jour' ?></label>
                    <input type="number" class="futur-form-control" id="daily_post_limit" name="daily_post_limit" value="<?= e((string) ($settings['daily_post_limit'] ?? 50)) ?>" min="1" max="500">
                </div>
            </div>

            <div class="futur-form-row">
                <div class="futur-form-group">
                    <label class="futur-form-label" for="registration_open"><?= $isAr ? 'السماح بالتسجيل' : 'Inscription ouverte' ?></label>
                    <select class="futur-form-control" id="registration_open" name="registration_open">
                        <option value="1" <?= ($settings['registration_open'] ?? 1) == 1 ? 'selected' : '' ?>><?= $isAr ? 'نعم' : 'Oui' ?></option>
                        <option value="0" <?= ($settings['registration_open'] ?? 1) == 0 ? 'selected' : '' ?>><?= $isAr ? 'لا' : 'Non' ?></option>
                    </select>
                </div>
                <div class="futur-form-group">
                    <label class="futur-form-label" for="maintenance_mode"><?= $isAr ? 'وضع الصيانة' : 'Mode maintenance' ?></label>
                    <select class="futur-form-control" id="maintenance_mode" name="maintenance_mode">
                        <option value="0" <?= ($settings['maintenance_mode'] ?? 0) == 0 ? 'selected' : '' ?>><?= $isAr ? 'مغلق' : 'Désactivé' ?></option>
                        <option value="1" <?= ($settings['maintenance_mode'] ?? 0) == 1 ? 'selected' : '' ?>><?= $isAr ? 'مفعّل' : 'Activé' ?></option>
                    </select>
                </div>
            </div>

            <div class="futur-form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-content-save me-1"></i><?= e(__('common.save')) ?>
                </button>
            </div>
        </form>
    </div>
</div>
