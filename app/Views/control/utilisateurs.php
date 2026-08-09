<?php
/** @var array $users @var array $roles */
use App\Helpers\I18n;

$title = __('common.users');
$page  = 'control.utilisateurs';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';
?>
<div class="futur-control">
    <div class="futur-control-header">
        <div>
            <h2 class="futur-control-title"><i class="mdi mdi-account-multiple"></i> <?= e(__('common.users')) ?></h2>
            <p class="futur-control-sub"><?= $isAr ? 'إدارة حسابات المستخدمين — الحالة، الأدوار، الوقت' : 'Gestion des comptes utilisateurs — statut, rôles, accès' ?></p>
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
