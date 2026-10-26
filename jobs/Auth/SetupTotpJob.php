<?php
// Rahsia TOTP baharu untuk dipapar sebagai kod QR (two-factor/setup).
// Rahsia BELUM disimpan — pemanggil menyimpannya dalam sesi; VerifyTotpJob menyimpannya selepas kod disahkan.
class SetupTotpJob
{
    const TABLES = ['owner' => 'platform_users', 'vendor_user' => 'vendor_users'];

    private $userType;
    private $userId;

    public function __construct($userType, $userId)
    {
        $this->userType = $userType;
        $this->userId = (int) $userId;
    }

    // [enabled, secret, otpauth_url]
    public function run()
    {
        $table = self::TABLES[$this->userType];
        $user = Database::fetchOne("SELECT email, totp_secret IS NOT NULL AS enabled FROM $table WHERE id = ?", [$this->userId]);
        $secret = Totp::generateSecret();
        return [
            'enabled'     => (bool) $user['enabled'],
            'secret'      => $secret,
            'otpauth_url' => Totp::otpauthUrl($secret, $user['email']),
        ];
    }
}
