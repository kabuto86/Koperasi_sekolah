<?php
// Log masuk owner — e-mel + kata laluan, kemudian 2FA TOTP wajib (PELAN 11).
// Log masuk pertama (atau selepas 2FA diset semula): sediakan aplikasi pengesah di langkah 2.
require __DIR__ . '/../core/bootstrap.php';

if (OwnerAuth::user() !== null) {
    Session::redirect(PanelRole::firstPage('owner', OwnerAuth::role()));
}

View::partial('partials/login-form.php', [
    'area'       => 'owner',
    'loginTitle' => t('auth.owner_title'),
    'panelLabel' => t('app.owner_panel'),
    'notice'     => '',
]);
