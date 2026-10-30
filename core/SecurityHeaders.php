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

        header_remove('X-Powered-By');
        header('Content-Security-Policy: ' . self::policy());
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // B14: tiada kamera/mikrofon/lokasi (pengimbas POS = papan kekunci USB), tetingkap lain tidak boleh mencapai tetingkap ini
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');

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

    // Di belakang proksi dipercayai (config trusted_proxies) X-Forwarded-Proto diterima; selain itu diabaikan (boleh dipalsukan)
    public static function isHttps()
    {
        if (Request::fromTrustedProxy() && strtolower(Request::header('X-Forwarded-Proto')) === 'https') {
            return true;
        }
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}
