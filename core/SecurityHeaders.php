<?php
// Header keselamatan untuk setiap halaman.
// CSP tidak membenarkan skrip/gaya inline — semua JS & CSS mesti dalam fail.
class SecurityHeaders
{
    public static function send()
    {
        if (headers_sent()) {
            return;
        }

        header('Content-Security-Policy: ' . self::policy());
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // HSTS hanya bila sambungan HTTPS (XAMPP tempatan biasanya HTTP)
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private static function policy()
    {
        return "default-src 'self'; "
             . "script-src 'self'; "
             . "style-src 'self'; "
             . "img-src 'self' data:; "
             . "font-src 'self'; "
             . "connect-src 'self'; "
             . "object-src 'none'; "
             . "base-uri 'self'; "
             . "form-action 'self'; "
             . "frame-ancestors 'none'";
    }

    // Halaman yang menghantar borang ke gateway bayaran (store/payment-redirect.php — B8): tambah asal gateway dalam form-action
    public static function allowFormAction($origin)
    {
        if (headers_sent() || !preg_match('#^https?://[A-Za-z0-9.:-]+$#', $origin)) {
            return;
        }
        $csp = str_replace("form-action 'self'", "form-action 'self' " . $origin, self::policy());
        header('Content-Security-Policy: ' . $csp);
    }

    public static function isHttps()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}
