<?php
// Log keluar owner.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::start();
Csrf::verifyRequest();
OwnerAuth::logout();
Response::ok(['logged_out' => true]);
