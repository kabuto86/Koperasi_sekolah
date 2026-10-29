<?php
// Status vendor ikut invois tertunggak (PELAN 14):
//   invois `issued` melepasi due_at → past_due · melepasi due_at + grace_days → suspended (store "ditutup sementara")
//   semua invois tertunggak dibayar / dibatalkan → active semula. Gantung MANUAL oleh owner (vendor.suspend) tidak dibuka automatik.
class VendorBillingStatus
{
    // Invois tertunggak tertua vendor (due_at lepas), atau null
    public static function oldestOverdue($vendorId)
    {
        return Database::fetchOne(
            "SELECT id, invoice_no, due_at FROM invoices WHERE vendor_id = ? AND status = 'issued' AND due_at < NOW() ORDER BY due_at LIMIT 1",
            [(int) $vendorId]
        );
    }

    // Selepas bayaran / batal invois (dalam transaksi pemanggil): pulihkan vendor jika tiada lagi tunggakan
    public static function restore($vendorId, $actorType = 'system', $actorId = null)
    {
        $vendor = Database::fetchOne("SELECT id, status FROM vendors WHERE id = ?", [(int) $vendorId]);
        if ($vendor === null || !in_array($vendor['status'], ['past_due', 'suspended'], true) || self::oldestOverdue($vendorId) !== null) {
            return false;
        }
        if ($vendor['status'] === 'suspended' && !self::autoSuspended($vendorId)) {
            return false;
        }
        $subscription = Subscriptions::current($vendorId);
        Database::execute("UPDATE vendors SET status = 'active' WHERE id = ?", [(int) $vendorId]);
        if ($subscription !== null && in_array($subscription['status'], ['past_due', 'suspended'], true)) {
            Database::execute("UPDATE subscriptions SET status = 'active' WHERE id = ?", [(int) $subscription['id']]);
        }
        (new WriteAuditLogJob($actorType, $actorId, (int) $vendorId, 'vendor.auto_activate', 'vendor', (int) $vendorId, ''))->run();
        return true;
    }

    // Penggantungan terakhir vendor dibuat oleh sistem (invois tertunggak), bukan owner
    public static function autoSuspended($vendorId)
    {
        $action = Database::fetchValue(
            "SELECT action FROM audit_logs WHERE vendor_id = ? AND action IN ('vendor.suspend', 'vendor.auto_suspend')
              ORDER BY id DESC LIMIT 1",
            [(int) $vendorId]
        );
        return $action === 'vendor.auto_suspend';
    }
}
