<?php
// Profil pengguna owner semasa.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
OwnerAuth::requireLogin();

$user = OwnerAuth::user();
Response::ok([
    'id'           => $user['id'],
    'full_name'    => $user['full_name'],
    'email'        => $user['email'],
    'language'     => $user['language'],
    'totp_enabled' => $user['totp_enabled'],
]);
