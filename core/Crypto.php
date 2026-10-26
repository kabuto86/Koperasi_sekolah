<?php
// Penyulitan rahsia (kredensial gateway, TOTP, kunci API WhatsApp/SMS) dengan sodium secretbox.
// Kunci 32 bait dalam config/secret.key (.gitignore) — jana dengan: php tools/generate-secret.php
// Hasil: base64(nonce . ciphertext). Kunci hilang = rahsia tidak boleh dibuka — simpan backup kunci.
class Crypto
{
    private static $key = null;

    public static function encrypt($plainText)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox((string) $plainText, $nonce, self::key());
        return base64_encode($nonce . $cipher);
    }

    // Pulangkan teks asal, atau null jika rosak / kunci salah
    public static function decrypt($encoded)
    {
        $raw = base64_decode((string) $encoded, true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, self::key());
        return $plain === false ? null : $plain;
    }

    // Token rawak untuk URL (reset kata laluan, QR ambil barang)
    public static function randomToken($bytes = 16)
    {
        return bin2hex(random_bytes($bytes));
    }

    // Token disimpan sebagai hash SHA-256 (PELAN 15)
    public static function hashToken($token)
    {
        return hash('sha256', (string) $token);
    }

    private static function key()
    {
        if (self::$key !== null) {
            return self::$key;
        }
        $file = APP_ROOT . '/' . Config::get('secret_key_file');
        if (!is_file($file)) {
            throw new RuntimeException('Kunci penyulitan tiada. Jalankan: php tools/generate-secret.php');
        }
        $key = base64_decode(trim((string) file_get_contents($file)), true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('config/secret.key tidak sah.');
        }
        self::$key = $key;
        return self::$key;
    }
}
