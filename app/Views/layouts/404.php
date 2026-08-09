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
    <title>404 — <?= $isAr ? 'الصفحة غير موجودة' : 'Page introuvable' ?></title>
    <link rel="icon" href="<?= asset('/assets/img/icon-192.png') ?>">
    <link rel="stylesheet" href="<?= asset($bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/admin.css') ?>">
</head>
<body>
<div class="wh-err">
    <div class="wh-err-code">404</div>
    <div class="wh-err-icon"><i class="mdi mdi-map-marker-question-outline"></i></div>
    <h1 class="wh-err-title"><?= $isAr ? 'الصفحة غير موجودة' : 'Page introuvable' ?></h1>
    <p class="wh-err-msg"><?= e($message ?? ($isAr ? 'الصفحة المطلوبة غير موجودة.' : 'La page demandée n\'existe pas.')) ?></p>
    <a class="btn btn-primary btn-icon" href="<?= url('/') ?>">
        <i class="mdi mdi-home-outline"></i><?= $isAr ? 'العودة إلى الرئيسية' : 'Retour à l\'accueil' ?>
    </a>
</div>
</body>
</html>
