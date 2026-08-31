<?php /** @var array $errors @var string $next */ ?>
<?php if (isset($errors['global'])): ?><div class="alert error"><?= e($errors['global']) ?></div><?php endif; ?>

<div class="mb-3">
    <a href="<?= url('/') ?>" class="d-inline-flex align-items-center gap-1" style="color:#5A6B60;font-size:.85rem;text-decoration:none">
        <i class="mdi mdi-arrow-left"></i> <?= App\Helpers\I18n::direction() === 'rtl' ? 'العودة للرئيسية' : "Retour à l'accueil" ?>
    </a>
</div>

<form method="post" action="<?= url('auth/login') ?>">
    <?= csrf_field() ?>
    <?php if (! empty($next)): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
    <div class="form-group">
        <label for="email"><?= e(__('auth.email')) ?></label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email', '')) ?>" required autofocus>
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
        <label for="password"><?= e(__('auth.password')) ?></label>
        <input class="form-control" type="password" id="password" name="password" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit"><?= e(__('auth.login_submit')) ?></button>
</form>

<div class="text-center mt-3">
    <a href="<?= url('auth/forgot') ?>" class="wh-text-muted"><?= e(__('auth.forgot')) ?></a>
</div>

<hr class="wh-divider">

<div class="text-center">
    <span class="wh-text-muted"><?= e(__('auth.no_account')) ?></span>
    <a class="btn btn-outline-secondary btn-block" href="<?= url('auth/register' . (! empty($next) ? '?next=' . urlencode($next) : '')) ?>"><?= e(__('auth.register')) ?></a>
</div>

<hr class="wh-divider">

<div class="text-center">
    <p class="wh-text-muted mb-2"><?= e(__('auth.association_account')) ?></p>
    <a class="btn btn-outline-secondary btn-block" href="<?= url('auth/register-association') ?>"><?= e(__('associations.inscription')) ?></a>
</div>
<div class="text-center mt-3 wh-text-muted" style="font-size:0.8rem">
    <?php if (config('app.debug', false)): ?>
    Comptes démo : wilaya@wilaya-harmonia.dz · president@elamel.dz · amina@citoyen.dz — mot de passe : <code>Harmonia@2026</code>
    <?php endif; ?>
</div>
