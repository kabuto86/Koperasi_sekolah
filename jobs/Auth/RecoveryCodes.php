<?php
// Kod pemulihan 2FA: 8 kod XXXX-XXXX, sekali guna, disimpan sebagai hash SHA-256 (totp_recovery_codes).
class RecoveryCodes
{
    const COUNT = 8;
    const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // tiada 0/O, 1/I

    // Kod lama dibuang; pulangkan kod baharu (dipapar sekali sahaja)
    public static function generate($userType, $userId)
    {
        Database::execute("DELETE FROM totp_recovery_codes WHERE user_type = ? AND user_id = ?", [$userType, $userId]);
        $codes = [];
        for ($i = 0; $i < self::COUNT; $i++) {
            $code = self::randomPart() . '-' . self::randomPart();
            Database::insert(
                "INSERT INTO totp_recovery_codes (user_type, user_id, code_hash) VALUES (?, ?, ?)",
                [$userType, $userId, Crypto::hashToken(self::normalize($code))]
            );
            $codes[] = $code;
        }
        return $codes;
    }

    // true jika kod sah dan belum diguna (kini ditanda diguna)
    public static function consume($userType, $userId, $code)
    {
        $normalized = self::normalize($code);
        if (strlen($normalized) !== 8) {
            return false;
        }
        $updated = Database::execute(
            "UPDATE totp_recovery_codes SET used_at = NOW()
              WHERE user_type = ? AND user_id = ? AND code_hash = ? AND used_at IS NULL",
            [$userType, $userId, Crypto::hashToken($normalized)]
        );
        return $updated === 1;
    }

    public static function looksLikeRecoveryCode($code)
    {
        return (bool) preg_match('/^[A-Za-z0-9]{4}-?[A-Za-z0-9]{4}$/', trim((string) $code));
    }

    private static function normalize($code)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    }

    private static function randomPart()
    {
        $part = '';
        for ($i = 0; $i < 4; $i++) {
            $part .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }
        return $part;
    }
}
