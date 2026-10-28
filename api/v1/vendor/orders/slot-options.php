<?php
// Slot ambil (modul pembukaan sekolah) untuk tapisan; [] jika modul tidak aktif.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::PANEL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListOrderSlotOptionsJob($cooperative);
Response::ok($job->run());
