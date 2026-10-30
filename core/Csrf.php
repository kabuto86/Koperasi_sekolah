<?php
// Token CSRF setiap sesi. Halaman memberi token melalui <body data-csrf="...">;
// api.js menghantarnya dalam header X-CSRF-Token untuk setiap POST.
// Sesi mesti sudah dimulakan (OwnerAuth / VendorAuth / CustomerAuth — B2). Apps Flutter (token Bearer) dikecualikan — B15.
class Csrf
{
    const SESSION_KEY = '_csrf_token';

    public static function token()
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function isValid($token)
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        return is_string($token) && $expected !== '' && hash_equals($expected, $token);
    }

    // Panggil di awal setiap endpoint POST
    public static function verifyRequest()
    {
        if (!Request::isPost()) {
            return;
        }
        // Apps (B15): token Bearer yang SUDAH disahkan oleh CustomerAuth — tiada cookie, jadi tiada risiko CSRF
        if (ApiTokenAuth::token() !== null) {
            return;
        }
        $token = Request::header('X-CSRF-Token');
        if ($token === '') {
            $token = (string) ($_POST['_csrf'] ?? '');
        }
        if (!self::isValid($token)) {
            Response::fail(t('error.csrf'), [], 419);
        }
    }
}
