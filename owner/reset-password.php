<?php
// Tetapkan kata laluan panel owner dari pautan e-mel (reset, atau jemputan kakitangan baharu)
require __DIR__ . '/../core/bootstrap.php';

OwnerAuth::start();
View::partial('partials/password-page.php', ['area' => 'owner', 'panelLabel' => t('app.owner_panel'), 'mode' => 'reset']);
