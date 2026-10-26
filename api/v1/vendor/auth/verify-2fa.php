<?php
// Log masuk vendor langkah 2 (hanya pengguna yang ada 2FA).
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::start();
Csrf::verifyRequest();

$userId = VendorAuth::pendingId();
if ($userId === 0) {
    Response::fail(t('auth.session_expired'));
}
$job = new VerifyTotpJob('vendor_user', $userId, Request::string('code'));
$job->run();

$vendor = new LoadVendorPendingJob($userId);
VendorAuth::completeLogin($userId, $vendor->run());
$user = VendorAuth::user();
(new WriteActivityLogJob($user['vendor_id'], null, $user['id'], 'login', 'vendor_user', $user['id']))->run();
Response::ok(['user' => ['id' => $user['id'], 'name' => $user['full_name'], 'role' => $user['role']]]);
