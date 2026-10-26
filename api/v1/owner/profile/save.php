<?php
// Simpan profil pengguna owner semasa.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::requireLogin();
Csrf::verifyRequest();

$job = new SaveOwnerProfileJob(OwnerAuth::id(), Request::all());
$user = $job->run();
Response::ok([
    'id'           => $user['id'],
    'full_name'    => $user['full_name'],
    'email'        => $user['email'],
    'language'     => $user['language'],
    'totp_enabled' => $user['totp_enabled'],
]);
