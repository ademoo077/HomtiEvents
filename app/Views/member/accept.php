<?php
/** @var array $invitation @var array|null $existing @var array|null $association */
use App\Helpers\I18n;

$title = __('members.accept_title');
$page  = 'members.accept';
$dir   = I18n::direction();
$isAr  = $dir === 'rtl';

$errors = errors();
$old    = old();
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card border-0 shadow-sm mt-4 mb-4">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <span class="wh-auth-logo"><i class="mdi mdi-account-plus-outline"></i></span>
                    <h1 class="h4 mb-1"><?= e(__('members.accept_title')) ?></h1>
                    <p class="text-muted">
                        <?= $isAr ? 'دعوة للانضمام إلى' : 'Invitation à rejoindre' ?>
                        <strong><?= e($association['nom'] ?? '') ?></strong>
                        <span class="badge bg-light text-dark ms-1"><?= e($invitation['email']) ?></span>
                    </p>
                </div>

                <?php if ($existing !== null): ?>
                    <div class="alert alert-info">
                        <i class="mdi mdi-information-outline me-1"></i>
                        <?= $isAr ? 'لديك حساب بالفعل. سجّل الدخول لقبول الدعوة وربط حسابك بالجمعية.' : 'Vous possédez déjà un compte. Connectez-vous pour accepter l\'invitation et rattacher votre compte à l\'association.' ?>
                    </div>
                    <a class="btn btn-primary w-100" href="<?= url('auth/login?next=' . urlencode(url('invitations/' . $invitation['token']))) ?>">
                        <i class="mdi mdi-login me-1"></i><?= e(__('members.login_to_accept')) ?>
                    </a>
                <?php else: ?>
                    <?php if (! empty($errors['global'])): ?>
                        <div class="alert alert-danger"><?= e($errors['global']) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= url('invitations/' . $invitation['token']) ?>" class="row g-2">
                        <?= csrf_field() ?>
                        <div class="col-6">
                            <label class="form-label"><?= e(__('common.prenom')) ?></label>
                            <input type="text" name="prenom" class="form-control" required
                                   value="<?= e($old['prenom'] ?? '') ?>">
                            <?php if (! empty($errors['prenom'])): ?><div class="text-danger small"><?= e($errors['prenom']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-6">
                            <label class="form-label"><?= e(__('common.nom')) ?></label>
                            <input type="text" name="nom" class="form-control" required
                                   value="<?= e($old['nom'] ?? '') ?>">
                            <?php if (! empty($errors['nom'])): ?><div class="text-danger small"><?= e($errors['nom']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('common.telephone')) ?></label>
                            <input type="tel" name="telephone" class="form-control"
                                   value="<?= e($old['telephone'] ?? '') ?>">
                            <?php if (! empty($errors['telephone'])): ?><div class="text-danger small"><?= e($errors['telephone']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= e(__('auth.password')) ?></label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                            <?php if (! empty($errors['password'])): ?><div class="text-danger small"><?= e($errors['password']) ?></div><?php endif; ?>
                            <small class="text-muted"><?= $isAr ? '8 أحرف على الأقل.' : 'Au moins 8 caractères.' ?></small>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-check me-1"></i><?= e(__('members.accept_btn')) ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
