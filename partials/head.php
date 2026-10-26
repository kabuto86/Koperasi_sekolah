<?php
// Bahagian atas setiap halaman: <head> + pembuka <body>.
// Pemboleh ubah: $pageTitle (wajib), $bodyClass (pilihan), $extraCss (pilihan, senarai URL),
//   $loginUrl (pilihan — panel: api.js ke sini bila sesi tamat)
// data-csrf: token sesi semasa (OwnerAuth / VendorAuth / CustomerAuth sudah dimulakan) → header X-CSRF-Token
$bodyClass = $bodyClass ?? '';
$extraCss = $extraCss ?? [];
$csrfToken = session_status() === PHP_SESSION_ACTIVE ? Csrf::token() : '';
$loginUrl = $loginUrl ?? '';
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
<body class="<?= e($bodyClass) ?>" data-lang="<?= e(Lang::json()) ?>" data-base-url="<?= e(View::baseUrl()) ?>"
      data-csrf="<?= e($csrfToken) ?>" data-login-url="<?= e($loginUrl) ?>">
<a class="visually-hidden-focusable app-skip-link" href="#main"><?= e(t('common.skip_to_content')) ?></a>
