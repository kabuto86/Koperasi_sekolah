<?php
// Senarai kakitangan owner (PELAN 18.7 OwnerStaff).
class ListOwnerStaffJob
{
    public function run()
    {
        $rows = Database::fetchAll(
            "SELECT id, full_name, email, role, is_active, totp_secret IS NOT NULL AS totp_enabled, last_login_at
               FROM platform_users ORDER BY role = 'owner' DESC, full_name"
        );
        return array_map([self::class, 'shape'], $rows);
    }

    public static function shape($row)
    {
        return [
            'id'            => (int) $row['id'],
            'full_name'     => $row['full_name'],
            'email'         => $row['email'],
            'role'          => $row['role'],
            'is_active'     => (bool) $row['is_active'],
            'totp_enabled'  => (bool) $row['totp_enabled'],
            'last_login_at' => IsoDate::format($row['last_login_at']),
        ];
    }

    public static function find($id)
    {
        $row = Database::fetchOne(
            "SELECT id, full_name, email, role, is_active, totp_secret IS NOT NULL AS totp_enabled, last_login_at
               FROM platform_users WHERE id = ?",
            [(int) $id]
        );
        return $row === null ? null : self::shape($row);
    }
}
