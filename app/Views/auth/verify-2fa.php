<?php /** @var array $errors, string $method */
$isTotp = ($method ?? 'email') === 'authenticator';
?>
<?php if (isset($errors['code'])): ?><div class="alert error"><?= e($errors['code']) ?></div><?php endif; ?>

<div class="text-center mb-3">
    <i class="mdi <?= $isTotp ? 'mdi-qrcode-scan' : 'mdi-shield-lock-outline' ?>" style="font-size:2.5rem;color:#1A4D3E"></i>
</div>

<?php if ($isTotp): ?>
    <p class="wh-text-muted text-center">Ouvrez votre application authenticator et saisissez le code affiché.</p>
<?php else: ?>
    <p class="wh-text-muted text-center"><?= e(__('auth.2fa_sent')) ?></p>
<?php endif; ?>

<form method="post" action="<?= url('auth/verify-2fa') ?>">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="code"><?= e(__('auth.2fa_code')) ?></label>
        <input class="form-control text-center" type="text" id="code" name="code"
               inputmode="numeric" pattern="\d{6}" maxlength="6"
               autocomplete="one-time-code" required autofocus
               style="letter-spacing:6px;font-size:1.25rem;max-width:240px;margin:0 auto;">
    </div>
    <button class="btn btn-primary btn-block mt-2" type="submit"><?= e(__('auth.2fa_submit')) ?></button>
</form>

<hr class="wh-divider">

<div class="text-center">
    <a href="<?= url('auth/login') ?>" class="wh-text-muted"><?= e(__('auth.2fa_cancel')) ?></a>
</div>
