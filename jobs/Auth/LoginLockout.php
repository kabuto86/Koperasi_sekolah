<?php
// Kunci log masuk: 5 cubaan gagal dalam 15 minit → dikunci 15 minit (PELAN 11).
// Setiap cubaan direkod dalam login_attempts (+ IP). Dikira ikut pengecam (e-mel / telefon) —
// sama untuk akaun wujud atau tidak, jadi kunci tidak mendedahkan kewujudan akaun. Cubaan semasa dikunci
// juga direkod (gagal) — kunci tamat 15 minit selepas cubaan terakhir.
class LoginLockout
{
    const MAX_FAILURES = 5;
    const LOCK_MINUTES = 15;

    public static function isLocked($type, $identifier)
    {
        $failures = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM login_attempts
              WHERE login_type = ? AND identifier = ? AND success = 0
                AND attempted_at > NOW() - INTERVAL " . self::LOCK_MINUTES . " MINUTE
                AND attempted_at > COALESCE((SELECT MAX(s.attempted_at) FROM login_attempts s
                                              WHERE s.login_type = ? AND s.identifier = ? AND s.success = 1), '1970-01-01')",
            [$type, $identifier, $type, $identifier]
        );
        return $failures >= self::MAX_FAILURES;
    }

    public static function record($type, $identifier, $success)
    {
        Database::insert(
            "INSERT INTO login_attempts (login_type, identifier, ip_address, user_agent, success) VALUES (?, ?, ?, ?, ?)",
            [$type, substr($identifier, 0, 191), Request::ip(), Request::userAgent(), $success ? 1 : 0]
        );
    }

    // Kemas kini lajur failed_attempts / locked_until pada jadual pengguna (untuk paparan pentadbir)
    public static function markUser($table, $userId, $success)
    {
        if (!in_array($table, ['platform_users', 'vendor_users', 'customers'], true)) {
            return;
        }
        if ($success) {
            Database::execute("UPDATE $table SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?", [$userId]);
            return;
        }
        Database::execute(
            "UPDATE $table SET failed_attempts = LEAST(failed_attempts + 1, 255),
                    locked_until = IF(failed_attempts >= ?, NOW() + INTERVAL " . self::LOCK_MINUTES . " MINUTE, locked_until)
              WHERE id = ?",
            [self::MAX_FAILURES, $userId]
        );
    }
}
