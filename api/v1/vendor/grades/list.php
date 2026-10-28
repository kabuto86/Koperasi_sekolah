<?php
// Darjah/tingkatan ikut jenis sekolah koperasi (juruwang juga — tapisan skrin serah, B9).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('GET');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
$cooperative = VendorAuth::requireCooperative(Request::int('cooperative_id'));

$job = new ListCooperativeGradesJob($cooperative['school_type']);
Response::ok($job->run());
