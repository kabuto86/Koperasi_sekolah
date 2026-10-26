<?php
// Pilih koperasi aktif: disahkan milik vendor sesi (ValidateCooperativeJob) + akses kakitangan, disimpan dalam sesi.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::requireRole(VendorAuth::ALL_ROLES);
Csrf::verifyRequest();

$job = new ValidateCooperativeJob(VendorAuth::vendorId(), Request::int('cooperative_id'));
$cooperative = $job->run();
if ($cooperative === null || !VendorAuth::canAccessCooperative($cooperative['id'])) {
    Response::notFound();
}
VendorAuth::setCooperative($cooperative['id']);
Response::ok(['cooperative' => [
    'id'          => (int) $cooperative['id'],
    'slug'        => $cooperative['slug'],
    'school_name' => $cooperative['school_name'],
    'school_type' => $cooperative['school_type'],
]]);
