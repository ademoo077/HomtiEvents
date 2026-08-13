<?php
/** @var array $association @var array $membres @var array $invitations */
use App\Helpers\I18n;

$title = __('members.title');
$page  = 'association.members';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$inviteUrl = static fn (string $token): string => url('invitations/' . $token);
?>
<div class="wh-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="wh-page-title"><?= e(__('members.title')) ?></h1>
            <p class="wh-page-sub">
                <?= e($association['nom'] ?? '') ?> —
                <?= $isAr ? 'إدارة أعضاء الجمعية' : 'Gestion des membres de votre association' ?>
            </p>
        </div>
    </div>

    <div class="row g-3">
        <!-- Formulaire d'invitation -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <span><i class="mdi mdi-account-plus-outline"></i> <?= e(__('members.invite')) ?></span>
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

            <!-- Invitations en attente -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header">
                    <span><i class="mdi mdi-clock-outline"></i> <?= e(__('members.invitations_pending')) ?></span>
                    <?php $pending = array_values(array_filter($invitations, static fn (array $i): bool => $i['statut'] === 'pending')); ?>
                    <span class="badge bg-secondary ms-1"><?= count($pending) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if ($pending === []): ?>
                        <div class="wh-empty p-4 text-center text-muted small">
                            <?= $isAr ? 'لا توجد دعوات معلّقة.' : 'Aucune invitation en attente.' ?>
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
                                              data-confirm="<?= $isAr ? 'إلغاء هذه الدعوة؟' : 'Révoquer cette invitation ?' ?>">
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
        </div>

        <!-- Membres existants -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <span><i class="mdi mdi-account-group-outline"></i> <?= e(__('members.list')) ?></span>
                    <span class="badge bg-primary ms-1"><?= count($membres) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if ($membres === []): ?>
                        <div class="wh-empty p-4 text-center text-muted">
                            <i class="mdi mdi-account-group-outline" style="font-size:2rem"></i>
                            <p class="mb-0 mt-2"><?= $isAr ? 'لا يوجد أعضاء بعد. ابدأ بدعوة أول عضو.' : 'Aucun membre pour le moment. Invitez le premier membre.' ?></p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($membres as $m): ?>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (! empty($m['avatar'])): ?>
                                            <img src="<?= e($m['avatar']) ?>" class="rounded-circle wh-user-avatar" alt="">
                                        <?php else: ?>
                                            <span class="wh-user-avatar"><?= e(mb_substr((string) $m['prenom'], 0, 1) . mb_substr((string) $m['nom'], 0, 1)) ?></span>
                                        <?php endif; ?>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold"><?= e(trim((string) $m['prenom'] . ' ' . $m['nom'])) ?></div>
                                            <small class="text-muted text-break">
                                                <?= e($m['email']) ?>
                                                <?php if (! empty($m['telephone'])): ?> • <?= e($m['telephone']) ?><?php endif; ?>
                                            </small>
                                        </div>
                                        <?php if ((int) $m['is_active'] !== 1): ?>
                                            <span class="badge bg-secondary"><?= $isAr ? 'موقوف' : 'Désactivé' ?></span>
                                        <?php endif; ?>
                                        <form method="post" action="<?= url('association/membres/' . (int) $m['id'] . '/remove') ?>"
                                              data-confirm="<?= $isAr ? 'إزالة هذا العضو من الجمعية؟' : 'Retirer ce membre de l\'association ?' ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= $isAr ? 'إزالة' : 'Retirer' ?>">
                                                <i class="mdi mdi-account-remove-outline"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
