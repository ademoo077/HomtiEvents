<?php
/** @var string $message */
use App\Helpers\I18n;

$locale   = I18n::locale();
$langAttr = I18n::langAttribute();
$dir      = I18n::direction();
$isAr     = $dir === 'rtl';

$bootstrapCss = $isAr
    ? '/assets/vendor/bootstrap/bootstrap.rtl.min.css'
    : '/assets/vendor/bootstrap/bootstrap.min.css';
?>
<!DOCTYPE html>
<html lang="<?= e($langAttr) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — <?= $isAr ? 'الوصول مرفوض' : 'Accès refusé' ?></title>
    <link rel="icon" href="<?= asset('/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
</head>
<body>
<div class="wh-err">
    <div class="wh-err-code">403</div>
    <div class="wh-err-icon wh-err-icon-red"><i class="mdi mdi-shield-lock-outline"></i></div>
    <h1 class="wh-err-title"><?= $isAr ? 'الوصول مرفوض' : 'Accès refusé' ?></h1>
    <p class="wh-err-msg"><?= e($message ?? ($isAr ? 'ليست لديك صلاحية الوصول إلى هذا المورد.' : 'Vous n\'avez pas la permission d\'accéder à cette ressource.')) ?></p>
    <a class="btn btn-primary btn-icon" href="<?= url('/') ?>">
        <i class="mdi mdi-home-outline"></i><?= $isAr ? 'العودة إلى الرئيسية' : 'Retour à l\'accueil' ?>
    </a>
</div>
</body>
</html>
