<?php
// Sesi PHP dengan nama berasingan untuk owner / vendor / ibu bapa (PELAN 11).
// Cookie HttpOnly, SameSite=Lax, Secure bila HTTPS. Sesi idle terlalu lama dikosongkan.
class Session
{
    public static function start($name, $idleSeconds, $cookieLifetime = 0)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (session_name() === $name) {
                return;
            }
            session_write_close();
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $idleSeconds);
        session_name($name);
        session_set_cookie_params([
            'lifetime' => $cookieLifetime,
            'path'     => View::baseUrl() . '/',
            'secure'   => SecurityHeaders::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $now = time();
        $last = $_SESSION['_last_activity'] ?? $now;
        if ($now - $last > $idleSeconds) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_last_activity'] = $now;
    }

    // Selepas log masuk: id sesi baharu (elak session fixation)
    public static function regenerate()
    {
        session_regenerate_id(true);
    }

    // Lanjutkan cookie sesi ("ingat saya")
    public static function extendCookie($lifetime)
    {
        if (headers_sent()) {
            return;
        }
        setcookie(session_name(), session_id(), [
            'expires'  => time() + $lifetime,
            'path'     => View::baseUrl() . '/',
            'secure'   => SecurityHeaders::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function destroy()
    {
        $_SESSION = [];
        if (!headers_sent()) {
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => View::baseUrl() . '/',
                'secure'   => SecurityHeaders::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }

    // Halaman: pergi ke URL lain dan berhenti
    public static function redirect($path)
    {
        header('Location: ' . View::url($path));
        exit;
    }
}
