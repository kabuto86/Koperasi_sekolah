<?php
// Bahagian atas setiap halaman: <head> + pembuka <body>.
// Pemboleh ubah: $pageTitle (wajib), $bodyClass (pilihan), $extraCss (pilihan, senarai URL)
$bodyClass = $bodyClass ?? '';
$extraCss = $extraCss ?? [];
?>
<!doctype html>
<html lang="<?= e(Lang::code()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(t('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(View::asset('lib/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(View::asset('lib/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(View::asset('fonts/noto-sans-sc/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(View::asset('fonts/noto-sans-tamil/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(View::asset('css/app.css')) ?>">
    <?php foreach ($extraCss as $cssUrl): ?>
    <link rel="stylesheet" href="<?= e($cssUrl) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>" data-lang="<?= e(Lang::json()) ?>" data-base-url="<?= e(View::baseUrl()) ?>">
