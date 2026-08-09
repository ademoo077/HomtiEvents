<?php /** @var array $errors */ ?>
<form method="post" action="<?= url('auth/forgot') ?>">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="email"><?= e(__('auth.email')) ?></label>
        <input class="form-control" type="email" id="email" name="email" required autofocus>
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Envoyer le lien</button>
</form>

<div class="text-center mt-3"><a href="<?= url('auth/login') ?>"><?= e(__('auth.login')) ?></a></div>
