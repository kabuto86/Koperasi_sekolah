<?php
// Log keluar vendor.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::start();
Csrf::verifyRequest();
VendorAuth::logout();
Response::ok(['logged_out' => true]);
