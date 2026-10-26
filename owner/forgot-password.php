<?php
// Lupa kata laluan panel owner — pautan reset (1 jam) dihantar melalui e-mel
require __DIR__ . '/../core/bootstrap.php';

OwnerAuth::start();
View::partial('partials/password-page.php', ['area' => 'owner', 'panelLabel' => t('app.owner_panel'), 'mode' => 'forgot']);
