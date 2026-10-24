<?php
// Log masuk owner — e-mel + kata laluan, kemudian 2FA TOTP wajib (PELAN 11)
require __DIR__ . '/../core/bootstrap.php';

View::partial('partials/login-form.php', [
    'area'        => 'owner',
    'loginTitle'  => t('auth.owner_title'),
    'panelLabel'  => t('app.owner_panel'),
    'requires2fa' => true,
]);
