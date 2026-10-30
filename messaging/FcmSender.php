<?php
// Firebase Cloud Messaging HTTP v1 (push apps Flutter — B15). TIDAK menyentuh DB.
// Akaun perkhidmatan Firebase owner (satu apps untuk semua koperasi): fail JSON dari Firebase Console → Project settings →
// Service accounts → "Generate new private key", disimpan di config fcm_service_account_file (.gitignore — JANGAN commit).
// Token akses OAuth2: JWT RS256 ditandatangani kunci peribadi akaun (openssl) → oauth2.googleapis.com/token, dicache ~1 jam.
// Ujian: KOPERASI_MESSAGING_TEST_URL menghala token & hantar ke tools/fake-messaging.php ({url}/fcm/...).
//
//   $fcm = FcmSender::fromFile(APP_ROOT . '/config/firebase-service-account.json');
//   $fcm->send($pushToken, 'Tajuk', 'Teks', ['event' => 'order_ready']);   // gagal → MessagingException / PushTokenInvalidException
class FcmSender
{
    const CODE = 'fcm';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const SEND_URL = 'https://fcm.googleapis.com';
    const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    const TIMEOUT = 15;

    private $projectId;
    private $clientEmail;
    private $privateKey;
    private $accessToken = '';
    private $accessExpires = 0;

    public function __construct(array $account)
    {
        $this->projectId = (string) ($account['project_id'] ?? '');
        $this->clientEmail = (string) ($account['client_email'] ?? '');
        $this->privateKey = (string) ($account['private_key'] ?? '');
        if ($this->projectId === '' || $this->clientEmail === '' || $this->privateKey === '') {
            throw new MessagingException(self::CODE . ': akaun perkhidmatan tidak lengkap (project_id, client_email, private_key)');
        }
    }

    public static function fromFile($path)
    {
        $account = is_file((string) $path) ? json_decode((string) file_get_contents($path), true) : null;
        if (!is_array($account)) {
            throw new MessagingException(self::CODE . ': fail akaun perkhidmatan Firebase tiada / tidak sah');
        }
        return new self($account);
    }

    // $data = nilai rentetan sahaja (had FCM). Pulangkan id mesej FCM
    public function send($pushToken, $title, $body, array $data = [])
    {
        $payload = json_encode(['message' => [
            'token'        => (string) $pushToken,
            'notification' => ['title' => (string) $title, 'body' => (string) $body],
            'data'         => array_map('strval', $data),
            'android'      => ['priority' => 'high'],
        ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $url = $this->baseUrl(self::SEND_URL) . '/v1/projects/' . rawurlencode($this->projectId) . '/messages:send';
        $response = $this->http($url, $payload, ['Content-Type: application/json', 'Authorization: Bearer ' . $this->accessToken()]);
        if ($response['status'] === 200 && !empty($response['json']['name'])) {
            return (string) $response['json']['name'];
        }
        $reason = $this->errorReason($response['json']);
        $message = self::CODE . ': HTTP ' . $response['status'] . ' ' . ($reason !== '' ? $reason : mb_substr($response['body'], 0, 150, 'UTF-8'));
        if ($response['status'] === 404 || in_array($reason, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            throw new PushTokenInvalidException($message);
        }
        if ($response['status'] === 401) {
            $this->accessToken = '';
        }
        throw new MessagingException($message);
    }

    // Token akses OAuth2 (JWT bearer grant) — dicache sehingga 1 minit sebelum luput
    private function accessToken()
    {
        if ($this->accessToken !== '' && time() < $this->accessExpires - 60) {
            return $this->accessToken;
        }
        $now = time();
        $tokenUrl = $this->baseUrl(self::TOKEN_URL, '/token');
        $jwt = self::base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . self::base64Url(json_encode([
            'iss'   => $this->clientEmail,
            'scope' => self::SCOPE,
            'aud'   => self::TOKEN_URL,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));
        $key = openssl_pkey_get_private($this->privateKey);
        if ($key === false || !openssl_sign($jwt, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new MessagingException(self::CODE . ': kunci peribadi akaun perkhidmatan tidak sah');
        }
        $jwt .= '.' . self::base64Url($signature);
        $response = $this->http($tokenUrl, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]), ['Content-Type: application/x-www-form-urlencoded']);
        if ($response['status'] !== 200 || empty($response['json']['access_token'])) {
            throw new MessagingException(self::CODE . ': token OAuth gagal (HTTP ' . $response['status'] . ')');
        }
        $this->accessToken = (string) $response['json']['access_token'];
        $this->accessExpires = $now + (int) ($response['json']['expires_in'] ?? 3600);
        return $this->accessToken;
    }

    // FCM v1: error.details[].errorCode (UNREGISTERED …) atau error.status
    private function errorReason($json)
    {
        foreach ((array) ($json['error']['details'] ?? []) as $detail) {
            if (!empty($detail['errorCode'])) {
                return (string) $detail['errorCode'];
            }
        }
        return (string) ($json['error']['status'] ?? '');
    }

    private function baseUrl($live, $testPath = '')
    {
        $testUrl = getenv('KOPERASI_MESSAGING_TEST_URL');
        return $testUrl ? rtrim($testUrl, '/') . '/' . self::CODE . $testPath : $live;
    }

    private static function base64Url($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function http($url, $body, array $headers)
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($response === false) {
            throw new MessagingException(self::CODE . ': ' . $error);
        }
        return ['status' => $status, 'body' => (string) $response, 'json' => json_decode((string) $response, true)];
    }
}
