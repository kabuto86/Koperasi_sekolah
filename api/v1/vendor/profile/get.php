<?php
// Profil kakitangan vendor semasa (semua peranan, termasuk ketika langganan digantung).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES, true);

Response::ok(SaveVendorProfileJob::profile(VendorAuth::user()));
