<?php /** @var string $token @var array $errors */ ?>
<form method="post" action="<?= url('auth/reset/' . $token) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="password"><?= e(__('common.password')) ?></label>
        <input class="form-control" type="password" id="password" name="password" required>
        <?php if (isset($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
        <label for="password_confirmation"><?= e(__('auth.confirm_password')) ?></label>
        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Réinitialiser</button>
</form>
