<?php
// Kunci kaunter / tukar juruwang — juruwang dibuang dari sesi, syif kekal dibuka.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

PosAuth::clear($cooperative['id']);
Response::ok([]);
