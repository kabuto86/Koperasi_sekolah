<?php
// Log masuk vendor — e-mel + kata laluan (2FA hanya jika disediakan, PELAN 11)
require __DIR__ . '/../core/bootstrap.php';

if (VendorAuth::user() !== null) {
    Session::redirect(PanelRole::firstPage('vendor', VendorAuth::role()));
}

View::partial('partials/login-form.php', [
    'area'       => 'vendor',
    'loginTitle' => t('auth.vendor_title'),
    'panelLabel' => t('app.vendor_panel'),
    'notice'     => ($_GET['reason'] ?? '') === 'suspended' ? t('auth.vendor_suspended') : '',
]);
