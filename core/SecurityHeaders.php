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

        $csp = "default-src 'self'; "
             . "script-src 'self'; "
             . "style-src 'self'; "
             . "img-src 'self' data:; "
             . "font-src 'self'; "
             . "connect-src 'self'; "
             . "object-src 'none'; "
             . "base-uri 'self'; "
             . "form-action 'self'; "
             . "frame-ancestors 'none'";

        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
