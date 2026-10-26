<?php
// Tetapkan kata laluan panel vendor dari pautan e-mel (reset, atau jemputan kakitangan baharu)
require __DIR__ . '/../core/bootstrap.php';

VendorAuth::start();
View::partial('partials/password-page.php', ['area' => 'vendor', 'panelLabel' => t('app.vendor_panel'), 'mode' => 'reset']);
