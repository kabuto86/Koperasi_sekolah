<?php
// Log masuk vendor — e-mel + kata laluan (2FA pilihan, PELAN 11)
require __DIR__ . '/../core/bootstrap.php';

View::partial('partials/login-form.php', [
    'area'        => 'vendor',
    'loginTitle'  => t('auth.vendor_title'),
    'panelLabel'  => t('app.vendor_panel'),
    'requires2fa' => false,
]);
