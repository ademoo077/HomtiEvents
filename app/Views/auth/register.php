<?php /** @var array $errors @var string $next */ ?>
<form method="post" action="<?= url('auth/register') ?>">
    <?= csrf_field() ?>
    <?php if (! empty($next)): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
    <div class="flex" style="align-items:flex-start">
        <div class="form-group grow">
            <label for="nom"><?= e(__('common.nom')) ?></label>
            <input class="form-control" id="nom" name="nom" value="<?= e(old('nom', '')) ?>" required>
        </div>
        <div class="form-group grow">
            <label for="prenom"><?= e(__('common.prenom')) ?></label>
            <input class="form-control" id="prenom" name="prenom" value="<?= e(old('prenom', '')) ?>" required>
        </div>
    </div>
    <div class="form-group">
        <label for="email"><?= e(__('common.email')) ?></label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email', '')) ?>" required>
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
        <label for="telephone"><?= e(__('common.telephone')) ?></label>
        <input class="form-control" id="telephone" name="telephone" value="<?= e(old('telephone', '')) ?>" required>
        <?php if (isset($errors['telephone'])): ?><div class="form-error"><?= e($errors['telephone']) ?></div><?php endif; ?>
    </div>
    <div class="flex" style="align-items:flex-start">
        <div class="form-group grow">
            <label for="password"><?= e(__('common.password')) ?></label>
            <input class="form-control" type="password" id="password" name="password" required>
            <?php if (isset($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="form-group grow">
            <label for="password_confirmation"><?= e(__('auth.confirm_password')) ?></label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
    </div>
    <button class="btn btn-primary btn-block" type="submit"><?= e(__('auth.register_submit')) ?></button>
</form>

<div class="text-center mt-3">
    <span class="wh-text-muted"><?= e(__('auth.have_account')) ?></span>
    <a href="<?= url('auth/login' . (! empty($next) ? '?next=' . urlencode($next) : '')) ?>"><?= e(__('auth.login')) ?></a>
</div>
