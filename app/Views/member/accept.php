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
<div class="wh-page" style="max-width:600px;margin:0 auto">
    <div class="wh-hero text-center" style="background:linear-gradient(135deg,#4B5563 0%,#0B5ED7 100%);border-radius:0 0 1.5rem 1.5rem;padding:2rem 1.5rem;margin-bottom:1.5rem">
        <div class="wh-hero-inner">
            <span class="d-inline-flex align-items-center justify-content-center mb-2" style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.15);backdrop-filter:blur(8px)"><i class="mdi mdi-account-plus-outline" style="font-size:2rem;color:#fff"></i></span>
            <h1 class="wh-hero-title" style="font-size:1.3rem"><?= e(__('members.accept_title')) ?></h1>
            <p class="wh-hero-sub" style="margin-top:.5rem">
                <?= $isAr ? 'دعوة للانضمام إلى' : 'Invitation à rejoindre' ?>
                <strong><?= e($association['nom'] ?? '') ?></strong>
                <span class="badge bg-light text-dark ms-1"><?= e($invitation['email']) ?></span>
            </p>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

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
