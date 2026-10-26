<?php
// Lupa kata laluan panel vendor — pautan reset (1 jam) dihantar melalui e-mel
require __DIR__ . '/../core/bootstrap.php';

VendorAuth::start();
View::partial('partials/password-page.php', ['area' => 'vendor', 'panelLabel' => t('app.vendor_panel'), 'mode' => 'forgot']);
