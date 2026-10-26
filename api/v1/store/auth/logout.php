<?php
// Log keluar ibu bapa.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
CustomerAuth::start();
Csrf::verifyRequest();
CustomerAuth::logout();
Response::ok([]);
