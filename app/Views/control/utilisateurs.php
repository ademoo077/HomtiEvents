<?php
/** @var array $users @var array $roles */
use App\Helpers\I18n;

$title = __('common.users');
$page  = 'control.utilisateurs';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="wh-hero" style="background:linear-gradient(135deg,#DC2626 0%,#D97706 100%)">
        <div class="wh-hero-inner">
            <div class="wh-hero-row">
                <div class="wh-hero-text">
                    <h1 class="wh-hero-title"><i class="mdi mdi-account-multiple me-2"></i><?= e(__('common.users')) ?></h1>
                    <p class="wh-hero-sub"><?= $isAr ? 'إدارة حسابات المستخدمين — الحالة، الأدوار، الوقت' : 'Gestion des comptes utilisateurs — statut, rôles, accès' ?></p>
                </div>
                <div class="wh-hero-actions">
                    <a class="btn btn-sm btn-light" href="<?= url('control/utilisateurs/create') ?>">
                        <i class="mdi mdi-plus me-1"></i><?= $isAr ? 'حساب جديد' : 'Nouveau compte' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="futur-card">
        <div class="futur-card-body">
            <div class="table-responsive">
                <table class="futur-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $isAr ? 'الاسم' : 'Nom' ?></th>
                            <th><?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></th>
                            <th><?= $isAr ? 'الدور' : 'Rôle' ?></th>
                            <th><?= $isAr ? 'الحالة' : 'Statut' ?></th>
                            <th><?= $isAr ? 'تاريخ آخر الاتصال' : 'Dernière connexion' ?></th>
                            <th class="text-center"><?= $isAr ? 'إجراءات' : 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= (int) $u['id'] ?></td>
                                <td><?= e(($u['nom'] ?? '') . ' ' . ($u['prenom'] ?? '')) ?></td>
                                <td><?= e($u['email'] ?? '') ?></td>
                                <td><?= e($u['role_user'] ?? '-') ?></td>
                                <td>
                                    <span class="futur-chip chip-<?= ($u['status'] ?? 'actif') === 'actif' ? 'success' : (($u['status'] ?? '') === 'banni' ? 'danger' : 'warning') ?>">
                                        <?= e($u['status'] ?? 'actif') ?>
                                    </span>
                                </td>
                                <td><?= e($u['last_login'] ?? '-') ?></td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= url('control/utilisateurs/' . (int) $u['id'] . '/edit') ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="userAction(<?= (int) $u['id'] ?>, 'statut', 'banni')">
                                        <i class="mdi mdi-account-cancel-outline"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                            onclick="userAction(<?= (int) $u['id'] ?>, 'statut', 'actif')">
                                        <i class="mdi mdi-account-check-outline"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" class="text-center"><?= e(__('common.no_data')) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <style>.wh-hero{border-radius:0 0 1.5rem 1.5rem;padding:1.5rem;margin-bottom:1.5rem}.wh-hero-inner{max-width:1200px;margin:0 auto}.wh-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.wh-hero-title{color:#fff;font-size:1.35rem;font-weight:700;margin:0}.wh-hero-sub{color:rgba(255,255,255,.8);font-size:.85rem;margin:.25rem 0 0}.wh-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}</style>
</div>

<script>
function userAction(id, action, valeur) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', <?= json_encode(url('control/utilisateurs/action')) ?>);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= e(csrf_token()) ?>');
    xhr.onload = function() {
        if (xhr.status === 200) { location.reload(); }
    };
    xhr.send('id=' + id + '&action=' + action + '&valeur=' + valeur);
}
</script>
