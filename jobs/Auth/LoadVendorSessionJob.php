<?php
// Kakitangan vendor untuk sesi semasa: aktif, vendor sama seperti semasa log masuk, + status vendor
// dan senarai koperasi yang dibenarkan (bukan pentadbir) + kebenaran sebenar (B3b). null = sesi tidak sah lagi.
class LoadVendorSessionJob
{
    private $userId;
    private $vendorId;

    public function __construct($userId, $vendorId)
    {
        $this->userId = (int) $userId;
        $this->vendorId = (int) $vendorId;
    }

    public function run()
    {
        $row = Database::fetchOne(
            "SELECT u.id, u.vendor_id, u.email, u.full_name, u.phone, u.role, u.language,
                    u.totp_secret IS NOT NULL AS totp_enabled, v.code AS vendor_code, v.company_name, v.status AS vendor_status
               FROM vendor_users u
               JOIN vendors v ON v.id = u.vendor_id
              WHERE u.id = ? AND u.vendor_id = ? AND u.is_active = 1",
            [$this->userId, $this->vendorId]
        );
        if ($row === null) {
            return null;
        }
        $row['id'] = (int) $row['id'];
        $row['vendor_id'] = (int) $row['vendor_id'];
        $row['totp_enabled'] = (bool) $row['totp_enabled'];
        $row['cooperative_ids'] = [];
        if ($row['role'] !== 'vendor_admin') {
            $ids = Database::fetchAll(
                "SELECT vuc.cooperative_id FROM vendor_user_cooperatives vuc
                   JOIN cooperatives c ON c.id = vuc.cooperative_id AND c.vendor_id = vuc.vendor_id
                  WHERE vuc.vendor_id = ? AND vuc.vendor_user_id = ?",
                [$row['vendor_id'], $row['id']]
            );
            $row['cooperative_ids'] = array_map('intval', array_column($ids, 'cooperative_id'));
        }
        $row['permissions'] = (new LoadUserPermissionsJob('vendor', $row['id'], $row['role']))->run();
        return $row;
    }
}
