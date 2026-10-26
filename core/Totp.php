<?php
// TOTP (RFC 6238): HMAC-SHA1, 6 digit, langkah 30 saat — serasi Google / Microsoft Authenticator.
// Rahsia disimpan DISULITKAN (Crypto) dalam totp_secret; di sini hanya teks base32.
class Totp
{
    const PERIOD = 30;
    const DIGITS = 6;
    const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    // Rahsia baharu 160 bit dalam base32 (32 aksara)
    public static function generateSecret()
    {
        $bytes = random_bytes(20);
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            $secret .= self::BASE32[bindec(str_pad($chunk, 5, '0'))];
        }
        return $secret;
    }

    public static function otpauthUrl($secret, $accountEmail)
    {
        $issuer = Config::get('name');
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $accountEmail)
             . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&digits=6&period=30';
    }

    public static function currentStep()
    {
        return (int) floor(time() / self::PERIOD);
    }

    // Kod untuk langkah masa tertentu
    public static function codeAt($secret, $step)
    {
        $key = self::base32Decode($secret);
        $counter = pack('N*', 0) . pack('N*', $step);
        $hash = hash_hmac('sha1', $counter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
               | ((ord($hash[$offset + 1]) & 0xFF) << 16)
               | ((ord($hash[$offset + 2]) & 0xFF) << 8)
               | (ord($hash[$offset + 3]) & 0xFF);
        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    // Pulangkan langkah masa yang sepadan (±1 langkah untuk jam peranti), atau null.
    // $lastStep = langkah terakhir yang pernah diterima — kod lama/sama ditolak (elak guna semula)
    public static function verify($secret, $code, $lastStep = null)
    {
        $code = preg_replace('/\s+/', '', (string) $code);
        if (!preg_match('/^[0-9]{6}$/', $code)) {
            return null;
        }
        $now = self::currentStep();
        for ($step = $now - 1; $step <= $now + 1; $step++) {
            if ($lastStep !== null && $step <= (int) $lastStep) {
                continue;
            }
            if (hash_equals(self::codeAt($secret, $step), $code)) {
                return $step;
            }
        }
        return null;
    }

    private static function base32Decode($secret)
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', (string) $secret));
        $bits = '';
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos(self::BASE32, $char)), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }
        return $bytes;
    }
}
