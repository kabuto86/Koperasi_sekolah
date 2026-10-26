<?php
// Rahsia TOTP baharu (pindah ke peranti baharu). Disimpan dalam sesi sehingga disahkan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireLogin();

$job = new SetupTotpJob('owner', OwnerAuth::id());
$setup = $job->run();
OwnerAuth::setSetupSecret($setup['secret']);
Response::ok($setup);
