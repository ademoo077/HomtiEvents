<?php
use App\Helpers\I18n;

$dir  = I18n::direction();
$isAr = $dir === 'rtl';
?>
<div class="futur-card mb-4">
    <div class="futur-card-header">
        <span><i class="mdi mdi-file-document-multiple-outline"></i> <?= $isAr ? 'إدارة المحتوى' : 'Gestion du contenu' ?></span>
    </div>
    <div class="futur-card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="<?= url('control/content/events') ?>" class="futur-card-link">
                    <div class="futur-card">
                        <div class="futur-card-body text-center">
                            <i class="mdi mdi-calendar-star mdi-24px text-primary mb-2"></i>
                            <h6><?= $isAr ? 'الأحداث' : 'Événements' ?></h6>
                            <small class="text-muted"><?= $isAr ? 'إدارة أحداث المنصة' : 'Gérer les événements' ?></small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('control/content/news') ?>" class="futur-card-link">
                    <div class="futur-card">
                        <div class="futur-card-body text-center">
                            <i class="mdi mdi-newspaper mdi-24px text-success mb-2"></i>
                            <h6><?= $isAr ? 'الأخبار' : 'Actualités' ?></h6>
                            <small class="text-muted"><?= $isAr ? 'إدارة أخبار المنصة' : 'Gérer les actualités' ?></small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= url('control/content/pages') ?>" class="futur-card-link">
                    <div class="futur-card">
                        <div class="futur-card-body text-center">
                            <i class="mdi mdi-file-document mdi-24px text-info mb-2"></i>
                            <h6><?= $isAr ? 'الصفحات' : 'Pages' ?></h6>
                            <small class="text-muted"><?= $isAr ? 'إدارة صفحات الموقع' : 'Gérer les pages' ?></small>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="futur-card">
    <div class="futur-card-header">
        <span><i class="mdi mdi-shield-check"></i> <?= $isAr ? 'مراقبة المحتوى' : 'Modération du contenu' ?></span>
    </div>
    <div class="futur-card-body p-0">
        <div class="table-responsive">
            <table class="futur-table mb-0">
                <thead>
                    <tr>
                        <th><?= $isAr ? 'النوع' : 'Type' ?></th>
                        <th><?= $isAr ? 'المحتوى' : 'Contenu' ?></th>
                        <th><?= $isAr ? 'المؤلف' : 'Auteur' ?></th>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingContent)): ?>
                        <?php foreach ($pendingContent as $item): ?>
                            <tr>
                                <td><span class="badge bg-info"><?= e($item['type'] ?? '—') ?></span></td>
                                <td><?= e(mb_substr((string) ($item['titre'] ?? ''), 0, 50)) ?></td>
                                <td><?= e($item['auteur'] ?? '—') ?></td>
                                <td><?= e($item['created_at'] ?? '—') ?></td>
                                <td>
                                    <span class="futur-chip chip-warning"><?= $isAr ? 'قيد المراجعة' : 'En attente' ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center"><?= $isAr ? 'لا يوجد محتوى معلّق' : 'Aucun contenu en attente' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
