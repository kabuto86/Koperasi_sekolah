<?php
// Token API apps Flutter (B15 — PELAN 11, 15): header `Authorization: Bearer {64 hex}`. Hanya laluan store/.
// Token mentah TIDAK disimpan — DB menyimpan SHA-256 (api_tokens.token_hash). Token luput selepas 90 hari tidak digunakan.
// Permintaan token tidak memakai cookie / sesi, jadi CSRF tidak diperlukan (Csrf::verifyRequest melangkau bila token sah).
// CustomerAuth::customer() memanggil authenticate() bila header Bearer ada — token salah TIDAK jatuh balik ke sesi.
//
//   ApiTokenAuth::hasBearer()      → header Bearer dihantar (apps)
//   ApiTokenAuth::authenticate()   → baris token sah, atau 401 { auth_required, token_invalid } / 429
//   ApiTokenAuth::requireToken()   → 403 { token_required } bila permintaan dari sesi web (cth. daftar peranti push)
class ApiTokenAuth
{
    const LIFETIME_DAYS = 90;
    const MAX_ACTIVE_PER_CUSTOMER = 10;

    // Had kadar (PELAN 15): setiap token 120 permintaan / minit; token salah 30 / 15 minit setiap IP
    const RATE_PER_TOKEN = 120;
    const RATE_BAD_PER_IP = 30;

    private static $token = null;

    // Token mentah dari header Authorization ('' jika tiada). Apache CGI/FastCGI: .htaccess menyalin header ke HTTP_AUTHORIZATION
    public static function bearer()
    {
        $header = Request::header('Authorization');
        if ($header === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        return preg_match('/^Bearer\s+(\S+)$/i', trim($header), $match) ? $match[1] : '';
    }

    public static function hasBearer()
    {
        return self::bearer() !== '';
    }

    // Baris token [id, customer_id, device_name, expires_at] — dicache satu permintaan
    public static function authenticate()
    {
        if (self::$token !== null) {
            return self::$token;
        }
        $raw = self::bearer();
        $token = preg_match('/^[a-f0-9]{64}$/', $raw) ? (new AuthenticateApiTokenJob($raw))->run() : null;
        if ($token === null) {
            RateLimit::enforce('api_token_bad:' . Request::ip(), self::RATE_BAD_PER_IP, 900);
            Response::fail(t('api.token_invalid'), [], 401, ['auth_required' => true, 'token_invalid' => true]);
        }
        RateLimit::enforce('api_token:' . $token['id'], self::RATE_PER_TOKEN, 60);
        self::$token = $token;
        return $token;
    }

    // Token yang sah untuk permintaan ini, atau null (sesi web / belum disemak)
    public static function token()
    {
        return self::$token;
    }

    public static function id()
    {
        return self::$token === null ? 0 : (int) self::$token['id'];
    }

    // Laluan khas apps (peranti push, batal token semasa). Panggil SELEPAS CustomerAuth::requireLogin()
    public static function requireToken()
    {
        if (self::$token === null) {
            Response::fail(t('api.token_required'), [], 403, ['token_required' => true]);
        }
    }

    // Token ditolak selepas disahkan (cth. akaun ibu bapa dinyahaktif)
    public static function reject()
    {
        self::$token = null;
        Response::fail(t('api.token_invalid'), [], 401, ['auth_required' => true, 'token_invalid' => true]);
    }
}
