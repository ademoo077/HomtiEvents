<?php
/** @var array $association @var array $membres @var array $invitations @var array $stats */
use App\Helpers\I18n;

$title = __('members.title');
$page  = 'association.members';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$inviteUrl = static fn (string $token): string => url('invitations/' . $token);

$pending = array_values(array_filter($invitations, static fn (array $i): bool => $i['statut'] === 'pending'));
$accepted = array_values(array_filter($invitations, static fn (array $i): bool => $i['statut'] === 'accepted'));

$activityLabel = static function (?string $lastScan) use ($isAr): array {
    if ($lastScan === null || $lastScan === '') {
        return ['text' => $isAr ? 'لم يشارك بعد' : 'Aucune participation', 'class' => 'secondary'];
    }
    $diff = time() - strtotime($lastScan);
    if ($diff < 86400) {
        return ['text' => $isAr ? 'نشط اليوم' : 'Actif aujourd\'hui', 'class' => 'success'];
    }
    if ($diff < 7 * 86400) {
        return ['text' => $isAr ? 'نشط هذا الأسبوع' : 'Actif cette semaine', 'class' => 'info'];
    }
    if ($diff < 30 * 86400) {
        return ['text' => $isAr ? 'نشط هذا الشهر' : 'Actif ce mois', 'class' => 'warning'];
    }
    return ['text' => $isAr ? 'غير نشط' : 'Inactif', 'class' => 'secondary'];
};
?>
<div class="wh-page">
    <div style="background:linear-gradient(135deg,#198754 0%,#0B5ED7 100%);border-radius:var(--wh-radius);padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:grid;place-items:center;font-size:1.3rem;"><i class="mdi mdi-account-group-outline"></i></div>
                <div>
                    <h1 style="font-size:1.35rem;font-weight:700;margin:0;"><?= e(__('members.title')) ?></h1>
                    <p style="margin:0;opacity:.85;font-size:.85rem;"><?= e($association['nom'] ?? '') ?> — <?= $isAr ? 'إدارة أعضاء الجمعية' : 'Gestion des membres de votre association' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Statistiques globales ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="wh-kpi">
                <div class="wh-kpi-icon blue"><i class="mdi mdi-account-group-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $stats['total'] ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'عضو' : 'Membres' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi">
                <div class="wh-kpi-icon green"><i class="mdi mdi-account-check-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $stats['actifs'] ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'نشط' : 'Actifs' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi">
                <div class="wh-kpi-icon amber"><i class="mdi mdi-clock-outline"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $stats['invitations'] ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'دعوة' : 'Invitations' ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="wh-kpi">
                <div class="wh-kpi-icon purple"><i class="mdi mdi-qrcode-scan"></i></div>
                <div>
                    <div class="wh-kpi-value"><?= (int) $stats['participations'] ?></div>
                    <div class="wh-kpi-label"><?= $isAr ? 'مشاركة' : 'Participations' ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Formulaire d'invitation + invitations en attente -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="padding:.65rem 1.25rem;background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;">
                    <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);font-size:.85rem;"><i class="mdi mdi-account-plus-outline"></i></span> <?= e(__('members.invite')) ?></span>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= url('association/membres/invite') ?>" class="row g-2">
                        <?= csrf_field() ?>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('common.email')) ?></label>
                            <input type="email" name="email" class="form-control" required
                                   placeholder="<?= $isAr ? 'مثال: membre@example.dz' : 'ex. membre@example.dz' ?>"
                                   value="<?= e(old('email', '')) ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-send me-1"></i><?= e(__('members.invite_btn')) ?>
                            </button>
                        </div>
                    </form>
                    <p class="text-muted small mb-0 mt-3">
                        <i class="mdi mdi-information-outline me-1"></i>
                        <?= $isAr
                            ? 'سيتم إنشاء رابط دعوة. شاركه مع الشخص المعني لإنشاء حسابه كعضو.'
                            : 'Un lien d\'invitation est généré : partagez-le avec la personne pour créer son compte membre.' ?>
                    </p>
                </div>
            </div>

            <!-- Créer un compte membre directement -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header d-flex align-items-center justify-content-between" style="padding:.65rem 1.25rem;background:#ede9fe;border-bottom:1px solid #ddd6fe;">
                    <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(124,58,237,.15);display:grid;place-items:center;color:#7c3aed;font-size:.85rem;"><i class="mdi mdi-account-plus"></i></span> <?= $isAr ? 'إنشاء حساب عضو' : 'Créer un compte membre' ?></span>
                    <button class="btn btn-sm btn-purple" type="button" data-bs-toggle="collapse" data-bs-target="#createMemberForm" aria-expanded="false" style="background:#ede9fe;color:#7c3aed;border:1px solid #ddd6fe;">
                        <i class="mdi mdi-plus me-1"></i><?= $isAr ? 'جديد' : 'Nouveau' ?>
                    </button>
                </div>
                <div class="collapse" id="createMemberForm">
                    <div class="card-body border-top">
                        <form method="post" action="<?= url('association/membres/create') ?>">
                            <?= csrf_field() ?>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-medium"><?= $isAr ? 'الاسم الأول' : 'Prénom' ?></label>
                                    <input type="text" name="prenom" class="form-control form-control-sm" required maxlength="50"
                                           placeholder="<?= $isAr ? 'مثال: أحمد' : 'ex. Ahmed' ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-medium"><?= $isAr ? 'اللقب' : 'Nom' ?></label>
                                    <input type="text" name="nom" class="form-control form-control-sm" required maxlength="50"
                                           placeholder="<?= $isAr ? 'مثال: بن علي' : 'ex. Benali' ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-medium"><?= e(__('common.email')) ?></label>
                                    <input type="email" name="email" class="form-control form-control-sm" required maxlength="100"
                                           placeholder="<?= $isAr ? 'مثال: membre@example.dz' : 'ex. membre@example.dz' ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-medium"><?= e(__('common.telephone')) ?> <span class="text-muted">(<?= $isAr ? 'اختياري' : 'optionnel' ?>)</span></label>
                                    <input type="tel" name="telephone" class="form-control form-control-sm" maxlength="20"
                                           placeholder="<?= $isAr ? '0555 00 00 00' : '0555 00 00 00' ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-medium"><?= $isAr ? 'كلمة المرور' : 'Mot de passe' ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" name="password" id="newMemberPassword" class="form-control" required minlength="6"
                                               placeholder="<?= $isAr ? '6 أحرف على الأقل' : '6 caractères minimum' ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newMemberPassword', this)">
                                            <i class="mdi mdi-eye-off"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 mt-1">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="mdi mdi-account-check me-1"></i><?= $isAr ? 'إنشاء الحساب' : 'Créer le compte' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Invitations en attente -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header" style="padding:.65rem 1.25rem;background:#fef3c7;border-bottom:1px solid #fde68a;">
                    <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(245,158,11,.15);display:grid;place-items:center;color:var(--wh-amber);font-size:.85rem;"><i class="mdi mdi-clock-outline"></i></span> <?= e(__('members.invitations_pending')) ?> <span class="wh-badge badge-amber"><?= count($pending) ?></span></span>
                </div>
                <div class="card-body p-0">
                    <?php if ($pending === []): ?>
                        <div class="futur-empty">
                            <i class="mdi mdi-email-outline"></i>
                            <p class="futur-empty-title"><?= $isAr ? 'لا توجد دعوات معلّقة.' : 'Aucune invitation en attente.' ?></p>
                            <p class="futur-empty-text"><?= $isAr ? 'Envoyez une invitation pour ajouter un membre.' : 'Envoyez une invitation pour ajouter un membre.' ?></p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pending as $inv): ?>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="mdi mdi-email-outline text-muted mt-1"></i>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold text-break"><?= e($inv['email']) ?></div>
                                            <small class="text-muted">
                                                <?= e(date('d/m/Y H:i', strtotime((string) $inv['created_at']))) ?>
                                                — <?= $isAr ? 'ينتهي في' : 'expire le' ?>
                                                <?= e(date('d/m/Y', strtotime((string) $inv['expires_at']))) ?>
                                            </small>
                                            <div class="input-group input-group-sm mt-1">
                                                <input type="text" class="form-control form-control-sm" readonly
                                                       value="<?= e($inviteUrl((string) $inv['token'])) ?>">
                                                <button type="button" class="btn btn-outline-secondary" data-copy
                                                        data-copy-target="cib-<?= (int) $inv['id'] ?>"
                                                        title="<?= $isAr ? 'نسخ الرابط' : 'Copier le lien' ?>">
                                                    <i class="mdi mdi-content-copy"></i>
                                                </button>
                                                <input type="hidden" id="cib-<?= (int) $inv['id'] ?>" value="<?= e($inviteUrl((string) $inv['token'])) ?>">
                                            </div>
                                        </div>
                                        <form method="post" action="<?= url('association/membres/invitations/' . (int) $inv['id'] . '/revoke') ?>"
                                              data-confirm="<?= e(__('members.revoke_confirm')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= $isAr ? 'إلغاء' : 'Révoquer' ?>">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Invitations acceptées (historique récent) -->
            <?php if ($accepted !== []): ?>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header" style="padding:.65rem 1.25rem;background:var(--wh-green-soft);border-bottom:1px solid #b7e4c7;">
                    <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(25,135,84,.15);display:grid;place-items:center;color:var(--wh-green);font-size:.85rem;"><i class="mdi mdi-check-circle-outline"></i></span> <?= $isAr ? 'دعوات مقبولة' : 'Invitations acceptées' ?> <span class="wh-badge badge-green"><?= count($accepted) ?></span></span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($accepted, 0, 5) as $inv): ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="mdi mdi-check-circle text-success"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold small text-break"><?= e($inv['email']) ?></div>
                                        <small class="text-muted">
                                            <?= $isAr ? 'قبلت في' : 'Acceptée le' ?>
                                            <?= e(date('d/m/Y', strtotime((string) ($inv['accepted_at'] ?? $inv['created_at'])))) ?>
                                        </small>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Membres existants -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="padding:.65rem 1.25rem;background:var(--wh-blue-soft);border-bottom:1px solid #b6d4fe;">
                    <span class="d-flex align-items-center gap-2 fw-bold" style="font-size:.88rem;"><span style="width:28px;height:28px;border-radius:7px;background:rgba(11,94,215,.15);display:grid;place-items:center;color:var(--wh-blue);font-size:.85rem;"><i class="mdi mdi-account-group-outline"></i></span> <?= e(__('members.list')) ?> <span class="wh-badge badge-blue"><?= count($membres) ?></span></span>
                </div>
                <?php if ($membres !== []): ?>
                <div class="card-body pb-0">
                    <div class="wh-input-icon-wrap">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" class="form-control" id="memberSearch"
                               placeholder="<?= $isAr ? 'بحث عن عضو...' : 'Rechercher un membre...' ?>"
                               aria-label="<?= $isAr ? 'بحث' : 'Recherche' ?>">
                    </div>
                </div>
                <?php endif; ?>
                <div class="card-body p-0">
                    <?php if ($membres === []): ?>
                        <div class="futur-empty">
                            <i class="mdi mdi-account-group-outline"></i>
                            <p class="futur-empty-title"><?= $isAr ? 'لا يوجد أعضاء بعد' : 'Aucun membre pour le moment' ?></p>
                            <p class="futur-empty-text"><?= $isAr ? 'Invitez le premier membre de votre équipe.' : 'Invitez le premier membre de votre équipe.' ?></p>
                            <button type="button" class="btn btn-primary futur-empty-action" data-bs-toggle="modal" data-bs-target="#inviteModal">
                                <i class="mdi mdi-account-plus me-1"></i><?= $isAr ? 'دعوة عضو' : 'Inviter un membre' ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush" id="membersList">
                            <?php foreach ($membres as $m): ?>
                                <?php $activity = $activityLabel($m['dernier_scan'] ?? null); ?>
                                <li class="list-group-item wh-member-item" data-member-search="<?= e(strtolower(trim((string) $m['prenom'] . ' ' . $m['nom'] . ' ' . $m['email']))) ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (! empty($m['avatar'])): ?>
                                            <img src="<?= e($m['avatar']) ?>" loading="lazy" class="rounded-circle wh-user-avatar" alt="">
                                        <?php else: ?>
                                            <span class="wh-user-avatar"><?= e(mb_substr((string) $m['prenom'], 0, 1) . mb_substr((string) $m['nom'], 0, 1)) ?></span>
                                        <?php endif; ?>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="fw-semibold"><?= e(trim((string) $m['prenom'] . ' ' . $m['nom'])) ?></span>
                                                <?php if ((int) $m['is_active'] !== 1): ?>
                                                    <span class="badge bg-secondary"><?= $isAr ? 'موقوف' : 'Désactivé' ?></span>
                                                <?php endif; ?>
                                                <span class="badge bg-<?= e($activity['class']) ?> wh-badge">
                                                    <?= e($activity['text']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted text-break d-block">
                                                <?= e($m['email']) ?>
                                                <?php if (! empty($m['telephone'])): ?> • <?= e($m['telephone']) ?><?php endif; ?>
                                            </small>
                                            <!-- Stats du membre -->
                                            <div class="d-flex align-items-center gap-3 mt-1 wh-member-stats">
                                                <span class="small text-muted" title="<?= $isAr ? 'مشاركات' : 'Participations' ?>">
                                                    <i class="mdi mdi-qrcode-scan me-1"></i><?= (int) $m['participations'] ?>
                                                </span>
                                                <span class="small text-muted" title="<?= $isAr ? 'نقاط' : 'Points' ?>">
                                                    <i class="mdi mdi-star-outline me-1"></i><?= (int) ($m['points'] ?? 0) ?>
                                                </span>
                                                <span class="small text-muted" title="<?= $isAr ? 'أوسمة' : 'Badges' ?>">
                                                    <i class="mdi mdi-medal-outline me-1"></i><?= (int) $m['badges_count'] ?>
                                                </span>
                                                <?php if (! empty($m['last_login'])): ?>
                                                    <span class="small text-muted" title="<?= $isAr ? 'آخر دخول' : 'Dernière connexion' ?>">
                                                        <i class="mdi mdi-login me-1"></i><?= e(date('d/m', strtotime((string) $m['last_login']))) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <form method="post" action="<?= url('association/membres/' . (int) $m['id'] . '/remove') ?>"
                                              data-confirm="<?= e(__('members.remove_confirm')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= $isAr ? 'إزالة' : 'Retirer' ?>">
                                                <i class="mdi mdi-account-remove-outline"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="futur-empty d-none" id="memberNoResult">
                            <i class="mdi mdi-magnify-close"></i>
                            <p class="futur-empty-title"><?= $isAr ? 'لا توجد نتائج.' : 'Aucun résultat.' ?></p>
                            <p class="futur-empty-text"><?= $isAr ? 'Essayez un autre terme de recherche.' : 'Essayez un autre terme de recherche.' ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var input = document.getElementById('memberSearch');
    var list = document.getElementById('membersList');
    var noResult = document.getElementById('memberNoResult');
    if (!input || !list) return;
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        var items = list.querySelectorAll('.wh-member-item');
        var visible = 0;
        items.forEach(function (item) {
            var match = !q || item.getAttribute('data-member-search').indexOf(q) !== -1;
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (noResult) noResult.classList.toggle('d-none', visible > 0);
    });
})();
function togglePassword(fieldId, btn) {
    var input = document.getElementById(fieldId);
    if (!input) return;
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    var icon = btn.querySelector('i');
    if (icon) {
        icon.className = isPassword ? 'mdi mdi-eye' : 'mdi mdi-eye-off';
    }
}
</script>
