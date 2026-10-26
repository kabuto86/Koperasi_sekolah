<?php
// Log masuk kakitangan vendor. 2FA hanya jika disediakan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
VendorAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('vendor_login_ip:' . Request::ip(), 30, 900);

$job = new VendorLoginJob(Request::all());
$result = $job->run();
if ($result['requires_2fa']) {
    VendorAuth::beginPending($result['user_id']);
    Response::ok(['user' => null, 'requires_2fa' => true]);
}
VendorAuth::completeLogin($result['user_id'], $result['vendor_id']);

$user = VendorAuth::user();
(new WriteActivityLogJob($user['vendor_id'], null, $user['id'], 'login', 'vendor_user', $user['id']))->run();
Response::ok([
    'user'         => ['id' => $user['id'], 'name' => $user['full_name'], 'role' => $user['role']],
    'requires_2fa' => false,
    'billing_only' => $result['billing_only'],
]);
