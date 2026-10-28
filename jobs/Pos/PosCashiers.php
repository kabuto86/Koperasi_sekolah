<?php
// Kakitangan yang boleh guna POS koperasi ini (PELAN 18.11): vendor sesi, aktif, PIN ditetapkan,
// ada akses koperasi (vendor_admin = semua koperasi). Sama dengan syarat PosPinLoginJob.
class PosCashiers
{
    const ACCESS_SQL = "u.vendor_id = ? AND u.is_active = 1 AND u.pin_hash IS NOT NULL
        AND (u.role = 'vendor_admin' OR EXISTS (
             SELECT 1 FROM vendor_user_cooperatives vuc
              WHERE vuc.vendor_id = u.vendor_id AND vuc.vendor_user_id = u.id AND vuc.cooperative_id = ?))";

    // [id, full_name, role] atau null
    public static function find($vendorId, $cooperativeId, $userId)
    {
        $row = Database::fetchOne(
            "SELECT u.id, u.full_name, u.role FROM vendor_users u WHERE u.id = ? AND " . self::ACCESS_SQL,
            [(int) $userId, (int) $vendorId, (int) $cooperativeId]
        );
        return $row === null ? null : self::shape($row);
    }

    // Senarai untuk skrin log masuk PIN — pin_locked = 5 PIN salah (pentadbir tetapkan PIN baharu di Kakitangan)
    public static function forCounter($vendorId, $cooperativeId)
    {
        $rows = Database::fetchAll(
            "SELECT u.id, u.full_name, u.role, u.pin_failed_attempts FROM vendor_users u WHERE " . self::ACCESS_SQL . "
              ORDER BY FIELD(u.role, 'cashier', 'coop_manager', 'vendor_admin'), u.full_name",
            [(int) $vendorId, (int) $cooperativeId]
        );
        return array_map(function ($row) {
            return self::shape($row) + ['pin_locked' => (int) $row['pin_failed_attempts'] >= PosPinLoginJob::MAX_ATTEMPTS];
        }, $rows);
    }

    public static function shape(array $row)
    {
        return ['id' => (int) $row['id'], 'full_name' => $row['full_name'], 'role' => $row['role']];
    }
}
