<?php
// Pembantu had pelan vendor (PELAN 14): pelan langganan semasa + penggunaan koperasi / produk.
// Diletak dalam jobs/ (bukan core/) kerana ia membaca DB — core tidak menulis SQL.
//
//   $plan = PlanLimit::plan($vendorId);          // [plan_id, plan_name, billing_cycle, max_cooperatives, ...] atau null
//   PlanLimit::cooperativeCount($vendorId);      // koperasi yang belum ditutup
class PlanLimit
{
    // Langganan terkini yang belum dibatalkan + had pelannya
    public static function plan($vendorId)
    {
        $row = Database::fetchOne(
            "SELECT s.id AS subscription_id, s.plan_id, s.billing_cycle, s.current_period_end,
                    p.name AS plan_name, p.max_cooperatives, p.max_products, p.max_staff
               FROM subscriptions s JOIN plans p ON p.id = s.plan_id
              WHERE s.vendor_id = ? AND s.status <> 'cancelled'
              ORDER BY s.id DESC LIMIT 1",
            [(int) $vendorId]
        );
        if ($row === null) {
            return null;
        }
        foreach (['max_cooperatives', 'max_products', 'max_staff'] as $key) {
            $row[$key] = $row[$key] === null ? null : (int) $row[$key];
        }
        $row['plan_id'] = (int) $row['plan_id'];
        $row['subscription_id'] = (int) $row['subscription_id'];
        return $row;
    }

    // Koperasi yang dikira dalam had & harga (status closed tidak dikira)
    public static function cooperativeCount($vendorId)
    {
        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM cooperatives WHERE vendor_id = ? AND status <> 'closed'",
            [(int) $vendorId]
        );
    }

    // Produk semua koperasi vendor (had max_products ialah peringkat vendor)
    public static function productCount($vendorId)
    {
        return (int) Database::fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = ?", [(int) $vendorId]);
    }

    // max NULL = tiada had. Tiada langganan langsung = tidak boleh tambah
    public static function canAddCooperative($vendorId)
    {
        $plan = self::plan($vendorId);
        return $plan !== null && ($plan['max_cooperatives'] === null || self::cooperativeCount($vendorId) < $plan['max_cooperatives']);
    }

    public static function canAddProduct($vendorId)
    {
        $plan = self::plan($vendorId);
        return $plan !== null && ($plan['max_products'] === null || self::productCount($vendorId) < $plan['max_products']);
    }
}
