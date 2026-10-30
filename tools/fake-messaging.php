<?php
// Pembekal WhatsApp/SMS palsu untuk ujian B13 (tools/verify-b13.php) + FCM palsu untuk B15 (tools/verify-b15.php) — pelayan router `php -S`:
//   FAKE_MESSAGING_STATE=/tmp/state.json php -S 127.0.0.1:8121 tools/fake-messaging.php
// Aplikasi dihala ke sini dengan KOPERASI_MESSAGING_TEST_URL=http://127.0.0.1:8121 ({url}/{kod pembekal}/...).
// Meniru WhatsApp Cloud, Twilio & iSMS secukupnya. Rahsia sah = 'secret'. Nombor 60199999999 sentiasa ditolak.
// FCM (B15): /fcm/token menyemak tandatangan JWT RS256 dengan kunci awam FAKE_FCM_PUBLIC_KEY (fail PEM); token peranti bermula
// UNREGISTERED → 404 UNREGISTERED, FAIL → 503.
// /_admin/requests = senarai permintaan diterima. Bukan sebahagian aplikasi — CLI / ujian sahaja.
if (PHP_SAPI !== 'cli-server') {
    exit(1);
}

const FAKE_SECRET = 'secret';
const FAKE_REJECTED = '60199999999';

$file = getenv('FAKE_MESSAGING_STATE') ?: sys_get_temp_dir() . '/kop-fake-messaging.json';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function state($file)
{
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($data) ? $data : ['requests' => []];
}

function reply($status, $body, $type = 'application/json')
{
    http_response_code($status);
    header('Content-Type: ' . $type);
    echo is_string($body) ? $body : json_encode($body);
    exit;
}

function remember($file, array $entry)
{
    $state = state($file);
    $state['requests'][] = $entry;
    file_put_contents($file, json_encode($state), LOCK_EX);
}

if ($path === '/_admin/requests') {
    reply(200, state($file)['requests']);
}

// WhatsApp Cloud: POST /whatsapp_cloud/{phone_number_id}/messages, Bearer token, JSON
if (preg_match('#^/whatsapp_cloud/([^/]+)/messages$#', $path, $m)) {
    $body = json_decode((string) file_get_contents('php://input'), true);
    $ok = ($_SERVER['HTTP_AUTHORIZATION'] ?? '') === 'Bearer ' . FAKE_SECRET && ($body['to'] ?? '') !== FAKE_REJECTED;
    remember($file, ['provider' => 'whatsapp_cloud', 'id' => $m[1], 'to' => $body['to'] ?? '', 'text' => $body['text']['body'] ?? '', 'ok' => $ok]);
    $ok ? reply(200, ['messages' => [['id' => 'wamid.' . bin2hex(random_bytes(4))]]]) : reply(401, ['error' => ['message' => 'Invalid token']]);
}

// Twilio: POST /twilio/Accounts/{sid}/Messages.json, basic auth sid:token, borang
if (preg_match('#^/twilio/Accounts/([^/]+)/Messages\.json$#', $path, $m)) {
    $ok = ($_SERVER['PHP_AUTH_USER'] ?? '') === $m[1] && ($_SERVER['PHP_AUTH_PW'] ?? '') === FAKE_SECRET
        && ltrim(str_replace('whatsapp:', '', $_POST['To'] ?? ''), '+') !== FAKE_REJECTED;
    remember($file, ['provider' => 'twilio', 'id' => $m[1], 'to' => $_POST['To'] ?? '', 'from' => $_POST['From'] ?? '', 'text' => $_POST['Body'] ?? '', 'ok' => $ok]);
    $ok ? reply(201, ['sid' => 'SM' . bin2hex(random_bytes(8))]) : reply(401, ['message' => 'Authenticate']);
}

// iSMS: GET /isms/isms_send_all_id.php?un&pwd&dstno&msg
if ($path === '/isms/isms_send_all_id.php') {
    $ok = ($_GET['pwd'] ?? '') === FAKE_SECRET && ($_GET['dstno'] ?? '') !== FAKE_REJECTED;
    remember($file, ['provider' => 'isms', 'id' => $_GET['un'] ?? '', 'to' => $_GET['dstno'] ?? '', 'text' => $_GET['msg'] ?? '', 'ok' => $ok]);
    reply(200, $ok ? '2000 = SUCCESS' : '-1004 = INVALID PASSWORD', 'text/plain');
}

// FCM OAuth: POST /fcm/token (grant jwt-bearer, assertion RS256)
if ($path === '/fcm/token') {
    $parts = explode('.', (string) ($_POST['assertion'] ?? ''));
    $decode = function ($value) {
        return base64_decode(strtr($value, '-_', '+/'));
    };
    $claims = count($parts) === 3 ? json_decode($decode($parts[1]), true) : null;
    $publicKey = (string) @file_get_contents((string) getenv('FAKE_FCM_PUBLIC_KEY'));
    $ok = is_array($claims) && ($_POST['grant_type'] ?? '') === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
        && json_decode($decode($parts[0]), true)['alg'] === 'RS256'
        && openssl_verify($parts[0] . '.' . $parts[1], $decode($parts[2]), $publicKey, OPENSSL_ALGO_SHA256) === 1
        && ($claims['scope'] ?? '') === 'https://www.googleapis.com/auth/firebase.messaging' && ($claims['exp'] ?? 0) > time();
    remember($file, ['provider' => 'fcm_token', 'iss' => $claims['iss'] ?? '', 'ok' => $ok]);
    $ok ? reply(200, ['access_token' => 'fake-fcm-access', 'expires_in' => 3600, 'token_type' => 'Bearer'])
        : reply(400, ['error' => 'invalid_grant']);
}

// FCM v1: POST /fcm/v1/projects/{project}/messages:send, Bearer fake-fcm-access, JSON { message: { token, notification, data } }
if (preg_match('#^/fcm/v1/projects/([^/]+)/messages:send$#', $path, $m)) {
    $body = json_decode((string) file_get_contents('php://input'), true);
    $token = (string) ($body['message']['token'] ?? '');
    $auth = ($_SERVER['HTTP_AUTHORIZATION'] ?? '') === 'Bearer fake-fcm-access';
    $entry = ['provider' => 'fcm', 'id' => $m[1], 'to' => $token, 'title' => $body['message']['notification']['title'] ?? '',
        'text' => $body['message']['notification']['body'] ?? '', 'data' => $body['message']['data'] ?? [], 'ok' => false];
    if (!$auth) {
        remember($file, $entry);
        reply(401, ['error' => ['status' => 'UNAUTHENTICATED']]);
    }
    if (strpos($token, 'UNREGISTERED') === 0) {
        remember($file, $entry);
        reply(404, ['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]]);
    }
    if (strpos($token, 'FAIL') === 0) {
        remember($file, $entry);
        reply(503, ['error' => ['status' => 'UNAVAILABLE']]);
    }
    remember($file, ['ok' => true] + $entry);
    reply(200, ['name' => 'projects/' . $m[1] . '/messages/' . bin2hex(random_bytes(6))]);
}

reply(404, ['error' => 'not found']);
