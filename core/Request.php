<?php
// Membaca input permintaan. GET dari query string; POST dari badan JSON (api.js) atau borang biasa.
// PERINGATAN: vendor_id & customer_id TIDAK PERNAH dibaca dari sini — guna VendorAuth / CustomerAuth.
class Request
{
    private static $body = null;

    public static function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost()
    {
        return self::method() === 'POST';
    }

    // Endpoint hanya untuk kaedah ini (GET / POST) → 405
    public static function requireMethod($method)
    {
        if (self::method() !== $method) {
            Response::methodNotAllowed();
        }
    }

    public static function ip()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent()
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public static function header($name)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? (string) $_SERVER[$key] : '';
    }

    // Semua input: GET → query string, POST → badan
    public static function all()
    {
        if (self::isPost()) {
            return self::body();
        }
        return $_GET;
    }

    public static function get($key, $default = null)
    {
        $input = self::all();
        return array_key_exists($key, $input) ? $input[$key] : $default;
    }

    public static function string($key, $default = '')
    {
        $value = self::get($key, $default);
        if (!is_scalar($value)) {
            return $default;
        }
        return trim((string) $value);
    }

    // Integer atau null jika kosong / bukan nombor
    public static function int($key, $default = null)
    {
        $value = self::get($key, null);
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', trim($value))) {
            return (int) trim($value);
        }
        return $default;
    }

    public static function bool($key, $default = false)
    {
        $value = self::get($key, null);
        if ($value === null) {
            return $default;
        }
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    // Integer dari query string sahaja (cth. ?coop= pada URL webhook yang badannya POST)
    public static function queryInt($key)
    {
        $value = $_GET[$key] ?? null;
        return is_string($value) && preg_match('/^\d{1,10}$/', $value) ? (int) $value : null;
    }

    // Panggilan balik gateway (B8): badan mentah + medan borang/JSON + query + header tandatangan
    public static function webhook()
    {
        $raw = (string) file_get_contents('php://input');
        $post = $_POST;
        if (!$post && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
            parse_str($raw, $post);
        }
        return [
            'post'    => is_array($post) ? $post : [],
            'get'     => $_GET,
            'raw'     => $raw,
            'headers' => ['stripe-signature' => self::header('Stripe-Signature')],
        ];
    }

    public static function arrayValue($key)
    {
        $value = self::get($key, []);
        return is_array($value) ? $value : [];
    }

    private static function body()
    {
        if (self::$body !== null) {
            return self::$body;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            self::$body = is_array($decoded) ? $decoded : [];
        } else {
            self::$body = $_POST;
        }
        return self::$body;
    }
}
