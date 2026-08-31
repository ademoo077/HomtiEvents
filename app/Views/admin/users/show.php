<?php
/** @var array $user @var array $participations @var array $badges @var array $errors */
$title = $user['prenom'] . ' ' . $user['nom'];
$page  = 'admin.users.show';
use App\Helpers\I18n;
$isAr = I18n::direction() === 'rtl';
?>
<style>
.wh-users-detail-hero{background:linear-gradient(135deg,#6d28d9 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;color:#fff;position:relative;overflow:hidden}
.wh-users-detail-hero::before{content:"";position:absolute;top:-40%;right:-5%;width:300px;height:300px;background:rgba(255,255,255,.07);border-radius:50%}
.wh-users-detail-hero h1{position:relative;z-index:1;margin:0}
.wh-users-detail-hero p{position:relative;z-index:1;opacity:.85}
.wh-users-detail-hero .btn{position:relative;z-index:1}
@media(max-width:767.98px){.wh-users-detail-hero{padding:1.25rem 1rem}.wh-users-detail-hero h1{font-size:1.2rem}}
</style>

<div class="wh-page">
    <div class="wh-users-detail-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2" style="font-size:1.5rem">
                    <i class="mdi mdi-account-circle"></i>
                    <?= e($user['prenom'] . ' ' . $user['nom']) ?>
                </h1>
                <p class="mt-1 mb-0"><?= e(ucfirst($user['role_user'])) ?> — <?= e($user['email']) ?></p>
            </div>
            <a class="btn btn-light" href="<?= url('admin/users') ?>">
                <i class="mdi mdi-arrow-left me-1"></i><?= $isAr ? 'رجوع' : 'Retour' ?>
            </a>
        </div>
    </div>

    <?php if (! empty($user['deleted_at'])): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="mdi mdi-archive-outline"></i>
            <span><?= $isAr ? 'هذا الحساب مؤرشف — لا يمكن لصاحبه تسجيل الدخول.' : 'Compte archivé — son propriétaire ne peut plus se connecter.' ?></span>
        </div>
    <?php endif; ?>

    <?php if ($user['role_user'] === 'citoyen'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="wh-kpi"><div class="wh-kpi-icon blue"><i class="mdi mdi-account-group"></i></div><div><div class="wh-kpi-value"><?= (int) $user['participations'] ?></div><div class="wh-kpi-label"><?= e(__('common.participants')) ?></div></div></div>
        </div>
        <div class="col-md-4 col-6">
            <div class="wh-kpi"><div class="wh-kpi-icon violet"><i class="mdi mdi-trophy-outline"></i></div><div><div class="wh-kpi-value"><?= (int) $user['points'] ?></div><div class="wh-kpi-label"><?= e(__('common.points')) ?></div></div></div>
        </div>
        <div class="col-md-4 col-6">
            <div class="wh-kpi"><div class="wh-kpi-icon green"><i class="mdi mdi-star-outline"></i></div><div><div class="wh-kpi-value"><?= count($badges) ?></div><div class="wh-kpi-label"><?= e(__('common.badges')) ?></div></div></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background:var(--wh-blue-soft);border-bottom:2px solid var(--wh-blue)"><h6 class="mb-0 fw-bold" style="color:var(--wh-blue)"><i class="mdi mdi-account me-2"></i><?= $isAr ? 'المعلومات الشخصية' : 'Informations personnelles' ?></h6></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:40%"><?= $isAr ? 'الاسم' : 'Nom' ?></td><td class="fw-semibold"><?= e($user['nom'] . ' ' . $user['prenom']) ?></td></tr>
                        <tr><td class="text-muted"><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></td><td><?= e($user['email']) ?></td></tr>
                        <tr><td class="text-muted"><?= $isAr ? 'الهاتف' : 'Téléphone' ?></td><td><?= e($user['telephone'] ?? '-') ?></td></tr>
                        <tr><td class="text-muted"><?= $isAr ? 'الدور' : 'Rôle' ?></td><td><span class="wh-badge badge-<?= match($user['role_user']) { 'wilaya' => 'violet', 'association' => 'blue', 'epic' => 'cyan', default => 'gray' } ?>"><?= e(ucfirst($user['role_user'])) ?></span></td></tr>
                        <tr><td class="text-muted"><?= $isAr ? 'الحالة' : 'Statut' ?></td><td><?php if ((int) $user['is_active'] === 1): ?><span class="wh-badge badge-green"><?= $isAr ? 'نشط' : 'Actif' ?></span><?php else: ?><span class="wh-badge badge-red"><?= $isAr ? 'غير نشط' : 'Inactif' ?></span><?php endif; ?></td></tr>
                        <tr><td class="text-muted"><?= $isAr ? 'تاريخ الإنشاء' : 'Date de création' ?></td><td><?= e(date('d/m/Y H:i', strtotime((string) $user['created_at']))) ?></td></tr>
                    </table>
                </div>
            </div>

            <?php if ($user['role_user'] === 'citoyen'): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header" style="background:rgba(139,92,246,.08);border-bottom:2px solid #8B5CF6"><h6 class="mb-0 fw-bold" style="color:#8B5CF6"><i class="mdi mdi-star-outline me-2"></i><?= e(__('common.badges')) ?></h6></div>
                <div class="card-body">
                    <?php if ($badges): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($badges as $b): ?>
                                <span class="wh-badge badge-violet"><?php if ($b['icone']): ?><i class="mdi <?= e($b['icone']) ?>"></i> <?php endif; ?><?= e($b['nom']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="wh-empty"><p><?= e(__('common.no_data')) ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background:var(--wh-green-soft);border-bottom:2px solid var(--wh-green)"><h6 class="mb-0 fw-bold" style="color:var(--wh-green)"><i class="mdi mdi-link-variant me-2"></i><?= $isAr ? 'الارتباط' : 'Rattachement' ?></h6></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <?php if ($user['association_nom'] ?? null): ?><tr><td class="text-muted" style="width:40%"><?= $isAr ? 'الجمعية' : 'Association' ?></td><td class="fw-semibold"><?= e($user['association_nom']) ?></td></tr><?php endif; ?>
                        <?php if ($user['epic_nom'] ?? null): ?><tr><td class="text-muted">EPIC</td><td class="fw-semibold"><?= e($user['epic_nom']) ?></td></tr><?php endif; ?>
                        <?php if (!($user['association_nom'] ?? null) && !($user['epic_nom'] ?? null)): ?><tr><td colspan="2" class="text-muted text-center"><?= $isAr ? 'لا ارتباط' : 'Aucun rattachement' ?></td></tr><?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header" style="background:var(--wh-amber-soft);border-bottom:2px solid #F59E0B"><h6 class="mb-0 fw-bold" style="color:#F59E0B"><i class="mdi mdi-cog me-2"></i><?= $isAr ? 'إجراءات' : 'Actions' ?></h6></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="<?= url('admin/users/' . $user['id'] . '/toggle') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-<?= (int) $user['is_active'] === 1 ? 'outline-danger' : 'outline-success' ?>">
                                <i class="mdi mdi-<?= (int) $user['is_active'] === 1 ? 'account-off' : 'account-check' ?> me-1"></i>
                                <?= (int) $user['is_active'] === 1 ? ($isAr ? 'إيقاف' : 'Désactiver') : ($isAr ? 'تفعيل' : 'Activer') ?>
                            </button>
                        </form>
                        <?php if ($user['role_user'] !== 'wilaya'): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="mdi mdi-shield-key me-1"></i><?= $isAr ? 'تغيير الدور' : 'Changer rôle' ?></button>
                            <ul class="dropdown-menu">
                                <?php foreach (['citoyen', 'association', 'epic', 'wilaya'] as $r): ?>
                                    <?php if ($r !== $user['role_user']): ?>
                                    <li><form method="post" action="<?= url('admin/users/' . $user['id'] . '/role') ?>"><?= csrf_field() ?><input type="hidden" name="role" value="<?= e($r) ?>"><button type="submit" class="dropdown-item"><?= e(ucfirst($r)) ?></button></form></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <form method="post" action="<?= url('admin/users/' . $user['id'] . '/delete') ?>" data-confirm="<?= $isAr ? 'هل أنت متأكد من أرشفة هذا الحساب؟' : 'Archiver ce compte ?' ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" <?= ! empty($user['deleted_at']) ? 'disabled' : '' ?>><i class="mdi mdi-archive-outline me-1"></i><?= $isAr ? 'أرشفة' : 'Archiver' ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($user['role_user'] === 'citoyen' && !empty($participations)): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header" style="background:var(--wh-cyan-soft);border-bottom:2px solid #0891b2"><h6 class="mb-0 fw-bold" style="color:#0891b2"><i class="mdi mdi-calendar-check me-2"></i><?= $isAr ? 'المشاركات الأخيرة' : 'Dernières participations' ?></h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th><?= $isAr ? 'العنوان' : 'Adresse' ?></th>
                        <th><?= $isAr ? 'البلدية' : 'Commune' ?></th>
                        <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                        <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                        <th><?= $isAr ? 'وقت المسح' : 'Heure scan' ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($participations as $p): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($p['adresse']) ?></td>
                            <td class="wh-text-muted"><?= e($p['commune_nom'] ?? '-') ?></td>
                            <td class="wh-text-muted"><?= e(date('d/m/Y', strtotime((string) $p['date_evenement']))) ?></td>
                            <td><span class="wh-badge badge-<?= match($p['statut'] ?? '') { 'VALIDÉ' => 'green', 'PROGRAMME' => 'blue', 'EN_COURS' => 'cyan', 'TERMINE' => 'violet', default => 'gray' } ?>"><?= e($p['statut'] ?? '-') ?></span></td>
                            <td class="wh-text-muted"><?= $p['heure_scan'] ? e(date('d/m/Y H:i', strtotime((string) $p['heure_scan']))) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
