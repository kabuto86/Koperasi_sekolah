<?php
// Log masuk vendor — e-mel + kata laluan (2FA hanya jika disediakan, PELAN 11)
require __DIR__ . '/../core/bootstrap.php';

if (VendorAuth::user() !== null) {
    VendorAuth::redirectFirstPage();
}

$reason = $_GET['reason'] ?? '';
$notices = ['suspended' => t('auth.vendor_suspended'), 'no_access' => t('auth.no_access')];

View::partial('partials/login-form.php', [
    'area'       => 'vendor',
    'loginTitle' => t('auth.vendor_title'),
    'panelLabel' => t('app.vendor_panel'),
    'notice'     => $notices[$reason] ?? '',
]);
