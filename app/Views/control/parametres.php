<?php
/** @var array $parametres @var array $groupes */
use App\Helpers\I18n;

$title = __('common.settings');
$page  = 'control.parametres';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-cog me-2"></i><?= e(__('common.settings')) ?></h1>
                    <p class="wh-hero-sub"><?= $isAr ? 'إعدادات النظام المركزية — SaaS' : 'Paramètres système centralisés — SaaS' ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($groupes as $g): ?>
        <?php
        $groupe = (string) $g['groupe'];
        $items = array_filter($parametres, fn($p) => $p['groupe'] === $groupe);
        $labels = [
            'securite'    => $isAr ? 'الأمان' : 'Sécurité',
            'maintenance' => $isAr ? 'الصيانة' : 'Maintenance',
            'quota'       => $isAr ? 'الحصة' : 'Quotas',
            'langue'      => $isAr ? 'اللغة' : 'Langue',
            'theme'       => $isAr ? 'المظهر' : 'Thème',
            'api'         => $isAr ? 'واجهة برمجة التطبيقات' : 'API',
            'stockage'    => $isAr ? 'التخزين' : 'Stockage',
            'email'       => $isAr ? 'البريد الإلكتروني' : 'Email',
            'notification'=> $isAr ? 'الإشعارات' : 'Notifications',
        ];
        $label = $labels[$groupe] ?? $groupe;
        ?>
        <div class="futur-card">
            <div class="futur-card-header">
                <span><i class="mdi mdi-gear-outline"></i> <?= e($label) ?></span>
            </div>
            <div class="futur-card-body">
                <form method="post" action="<?= e(url('control/parametres/enregistrer')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="groupe" value="<?= e($groupe) ?>">
                    <table class="futur-table futur-table-sm">
                        <thead>
                            <tr>
                                <th><?= $isAr ? 'المفتاح' : 'Clé' ?></th>
                                <th><?= $isAr ? 'القيمة' : 'Valeur' ?></th>
                                <th><?= $isAr ? 'الوصف' : 'Description' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $p): ?>
                                <tr>
                                    <td><code><?= e($p['cle']) ?></code></td>
                                    <td>
                                        <?php
                                        $v = $p['type'] === 'bool' ? ((int) $p['valeur'] ? ($isAr ? 'نعم' : 'Oui') : ($isAr ? 'لا' : 'Non')) : e($p['valeur']);
                                        ?>
                                        <input type="text" name="cle[]" value="<?= e($p['cle']) ?>" class="d-none">
                                        <input type="text" name="valeur[]" value="<?= e($p['valeur']) ?>" class="form-control form-control-sm" style="max-width:300px">
                                    </td>
                                    <td class="text-muted small"><?= e($p['description'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="futur-btn futur-btn-sm futur-btn-primary mt-3">
                        <i class="mdi mdi-content-save"></i> <?= $isAr ? 'حفظ' : 'Enregistrer' ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($groupes)): ?>
        <div class="futur-card">
            <div class="futur-card-body">
                <p class="text-muted"><?= e(__('common.no_data')) ?></p>
            </div>
        </div>
    <?php endif; ?>
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>
