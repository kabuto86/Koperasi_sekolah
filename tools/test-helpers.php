<?php
// Pembantu ujian HTTP untuk tools/verify-bN.php (CLI sahaja): check(), kelas Browser (cookie, CSRF),
// log masuk owner/vendor/ibu bapa, buang kunci ujian. Dimuat selepas core/bootstrap.php.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$passed = 0;
$failed = 0;

function check($label, $condition)
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [LULUS] $label\n";
    } else {
        $failed++;
        echo "  [GAGAL] $label\n";
    }
}

// Pelanggan HTTP dengan balang cookie sendiri (satu pelayar)
class Browser
{
    public $csrf = '';
    private $jar;
    private $base;

    public function __construct($base)
    {
        $this->base = $base;
        $this->jar = tempnam(sys_get_temp_dir(), 'kopjar');
    }

    // Buka halaman: [status, lokasi redirect, html]. Token CSRF dibaca dari <body data-csrf>
    public function page($path)
    {
        $result = $this->send('GET', $path, null);
        if (preg_match('/data-csrf="([a-f0-9]*)"/', $result['body'], $m) && $m[1] !== '') {
            $this->csrf = $m[1];
        }
        return $result;
    }

    public function get($route, $params = [])
    {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->send('GET', 'api/v1/' . $route . '.php' . $query, null)['json'];
    }

    public function getStatus($route)
    {
        return $this->send('GET', 'api/v1/' . $route . '.php', null)['status'];
    }

    public function post($route, $body = [], $withCsrf = true)
    {
        return $this->send('POST', 'api/v1/' . $route . '.php', $body, $withCsrf)['json'];
    }

    public function postStatus($route, $body = [], $withCsrf = true)
    {
        return $this->send('POST', 'api/v1/' . $route . '.php', $body, $withCsrf)['status'];
    }

    public function cookie($name)
    {
        foreach (file($this->jar) as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) === 7 && $parts[5] === $name) {
                return $parts[6];
            }
        }
        return null;
    }

    private function send($method, $path, $body, $withCsrf = true)
    {
        $curl = curl_init($this->base . '/' . $path);
        $headers = ['Accept: application/json'];
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
            if ($withCsrf) {
                $headers[] = 'X-CSRF-Token: ' . $this->csrf;
            }
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $raw = (string) curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $location = (string) curl_getinfo($curl, CURLINFO_REDIRECT_URL);
        curl_close($curl);
        return ['status' => $status, 'location' => $location, 'body' => $raw, 'json' => json_decode($raw, true)];
    }
}

function totpNow($secret, $offset = 0)
{
    return Totp::codeAt($secret, Totp::currentStep() + $offset);
}

// Log masuk owner penuh (2FA). Pulangkan [Browser, rahsia TOTP]
function ownerLogin($base, $email, $secret = null)
{
    $browser = new Browser($base);
    $browser->page('owner/login.php');
    $res = $browser->post('owner/auth/login', ['email' => $email, 'password' => 'Demo@12345']);
    if (!empty($res['data']['setup'])) {
        $secret = $res['data']['setup']['secret'];
        $browser->post('owner/auth/verify-2fa', ['code' => totpNow($secret)]);
    } else {
        $browser->post('owner/auth/verify-2fa', ['code' => totpNow($secret, 1)]);
    }
    return [$browser, $secret];
}

function vendorLogin($base, $email)
{
    $browser = new Browser($base);
    $browser->page('vendor/login.php');
    $browser->post('vendor/auth/login', ['email' => $email, 'password' => 'Demo@12345']);
    $browser->page('vendor/login.php');
    return $browser;
}

function storeLogin($base, $login, $password = 'Demo@12345')
{
    $browser = new Browser($base);
    $browser->page('store/index.php?page=login');
    $res = $browser->post('store/auth/login', ['login' => $login, 'password' => $password]);
    $browser->page('store/index.php');
    return [$browser, $res];
}

function clearTestLocks()
{
    Database::execute("DELETE FROM login_attempts WHERE ip_address IN ('127.0.0.1', '::1', '0.0.0.0')");
    Database::execute("DELETE FROM rate_limits WHERE bucket_key LIKE '%127.0.0.1%' OR bucket_key LIKE 'forgot:%' OR bucket_key LIKE 'resend_verify:%'");
}
