<?php
// Pengguna owner untuk sesi semasa — hanya akaun aktif. null = sesi tidak sah lagi.
class LoadOwnerSessionJob
{
    private $userId;

    public function __construct($userId)
    {
        $this->userId = (int) $userId;
    }

    public function run()
    {
        $row = Database::fetchOne(
            "SELECT id, email, full_name, role, language, totp_secret IS NOT NULL AS totp_enabled
               FROM platform_users WHERE id = ? AND is_active = 1",
            [$this->userId]
        );
        if ($row === null) {
            return null;
        }
        $row['id'] = (int) $row['id'];
        $row['totp_enabled'] = (bool) $row['totp_enabled'];
        return $row;
    }
}
