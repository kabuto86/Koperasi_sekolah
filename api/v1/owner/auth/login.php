<?php
// Log masuk owner langkah 1 (e-mel + kata laluan) → 2FA wajib. Akaun tanpa 2FA terima rahsia TOTP untuk disediakan.
require __DIR__ . '/../../../../core/bootstrap.php';

Request::requireMethod('POST');
OwnerAuth::start();
Csrf::verifyRequest();
RateLimit::enforce('owner_login_ip:' . Request::ip(), 20, 900);

$job = new OwnerLoginJob(Request::all());
$result = $job->run();
OwnerAuth::beginPending($result['user_id'], $result['setup_secret']);

$setup = null;
if ($result['setup_secret'] !== null) {
    $setup = [
        'secret'      => $result['setup_secret'],
        'otpauth_url' => Totp::otpauthUrl($result['setup_secret'], strtolower(Request::string('email'))),
    ];
}
Response::ok(['requires_2fa' => true, 'setup' => $setup]);
