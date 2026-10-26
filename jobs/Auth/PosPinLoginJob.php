<?php
// Log masuk PIN juruwang di kaunter POS (PELAN 11, 18.11). Endpoint vendor/pos/pin-login disambung dalam B10.
// Syarat: kakitangan vendor sesi, aktif, PIN ditetapkan, ada akses koperasi (pentadbir = semua).
// 5 PIN salah berturut-turut → dikunci sehingga pentadbir menetapkan PIN baharu di Kakitangan.
class PosPinLoginJob
{
    const MAX_ATTEMPTS = 5;

    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $pin;

    public function __construct($vendorId, $cooperativeId, $userId, $pin)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->pin = (string) $pin;
    }

    // [id, full_name, role]
    public function run()
    {
        $user = Database::fetchOne(
            "SELECT u.id, u.full_name, u.role, u.pin_hash, u.pin_failed_attempts
               FROM vendor_users u
              WHERE u.id = ? AND u.vendor_id = ? AND u.is_active = 1 AND u.pin_hash IS NOT NULL
                AND (u.role = 'vendor_admin' OR EXISTS (
                     SELECT 1 FROM vendor_user_cooperatives vuc
                      WHERE vuc.vendor_id = u.vendor_id AND vuc.vendor_user_id = u.id AND vuc.cooperative_id = ?))",
            [$this->userId, $this->vendorId, $this->cooperativeId]
        );
        if ($user === null) {
            throw new ValidationException(t('pos.error.no_access'), [], 403);
        }
        if ((int) $user['pin_failed_attempts'] >= self::MAX_ATTEMPTS) {
            throw new ValidationException(t('pos.pin_locked'), [], 423);
        }

        $valid = Password::verify($this->pin, $user['pin_hash']);
        LoginLockout::record('pos_pin', 'pos:' . $this->vendorId . ':' . $user['id'], $valid);
        if (!$valid) {
            Database::execute(
                "UPDATE vendor_users SET pin_failed_attempts = pin_failed_attempts + 1 WHERE id = ? AND vendor_id = ?",
                [$user['id'], $this->vendorId]
            );
            $left = self::MAX_ATTEMPTS - (int) $user['pin_failed_attempts'] - 1;
            $message = $left > 0 ? t('pos.pin_wrong', ['left' => $left]) : t('pos.pin_locked');
            throw new ValidationException($message, ['pin' => $message]);
        }

        Database::execute(
            "UPDATE vendor_users SET pin_failed_attempts = 0, last_login_at = NOW() WHERE id = ? AND vendor_id = ?",
            [$user['id'], $this->vendorId]
        );
        if (Password::needsRehash($user['pin_hash'])) {
            Database::execute("UPDATE vendor_users SET pin_hash = ? WHERE id = ? AND vendor_id = ?", [Password::hash($this->pin), $user['id'], $this->vendorId]);
        }
        return ['id' => (int) $user['id'], 'full_name' => $user['full_name'], 'role' => $user['role']];
    }
}
