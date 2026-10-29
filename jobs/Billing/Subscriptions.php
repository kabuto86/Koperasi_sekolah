<?php
// Pembantu langganan vendor (PELAN 14): langganan semasa + pelan, harga ikut kitaran, kadar SST.
// vendor_id SENTIASA dari sesi / baris DB — tidak pernah dari input.
class Subscriptions
{
    // Langganan yang masih "hidup" (dibilkan / boleh dibayar)
    const LIVE_STATUSES = ['trial', 'active', 'past_due', 'suspended'];

    // Langganan yang diperbaharui oleh cron (digantung → tunggu bayaran dahulu)
    const RENEWING_STATUSES = ['trial', 'active', 'past_due'];

    const SELECT = "SELECT s.*, p.code AS plan_code, p.name AS plan_name, p.price_monthly, p.price_yearly,
                           p.max_cooperatives, p.max_products, p.max_staff, p.features_json
                      FROM subscriptions s JOIN plans p ON p.id = s.plan_id";

    // Langganan terkini vendor yang belum dibatalkan, atau null. $lock = FOR UPDATE (dalam transaksi)
    public static function current($vendorId, $lock = false)
    {
        $marks = implode(',', array_fill(0, count(self::LIVE_STATUSES), '?'));
        return Database::fetchOne(
            self::SELECT . " WHERE s.vendor_id = ? AND s.status IN ($marks) ORDER BY s.id DESC LIMIT 1" . ($lock ? ' FOR UPDATE' : ''),
            array_merge([(int) $vendorId], self::LIVE_STATUSES)
        );
    }

    public static function find($subscriptionId, $lock = false)
    {
        return Database::fetchOne(self::SELECT . " WHERE s.id = ?" . ($lock ? ' FOR UPDATE' : ''), [(int) $subscriptionId]);
    }

    // Kunci baris vendor — dua permintaan bil serentak untuk vendor yang sama berjalan satu demi satu
    public static function lockVendor($vendorId)
    {
        return Database::fetchOne("SELECT * FROM vendors WHERE id = ? FOR UPDATE", [(int) $vendorId]);
    }

    public static function cyclePrice(array $row, $cycle)
    {
        return (int) ($cycle === 'yearly' ? $row['price_yearly'] : $row['price_monthly']);
    }

    public static function plan($planId)
    {
        return Database::fetchOne("SELECT * FROM plans WHERE id = ?", [(int) $planId]);
    }

    // Kadar SST platform (%) — disalin ke setiap invois (snapshot)
    public static function sstRate()
    {
        return max(0, min(100, (float) PlatformSettings::get('sst_rate', '0')));
    }

    public static function tax($subtotal, $rate)
    {
        return (int) round($subtotal * $rate / 100);
    }

    // Penggunaan berbanding had pelan (PlanLimit)
    public static function usage($vendorId)
    {
        $staff = ListVendorStaffJob::limit($vendorId);
        return [
            'cooperatives' => PlanLimit::cooperativeCount($vendorId),
            'products'     => PlanLimit::productCount($vendorId),
            'staff'        => (int) $staff['used'],
        ];
    }

    // Pelan baharu muat penggunaan semasa? (turun taraf ditolak jika tidak)
    public static function fits(array $plan, array $usage)
    {
        foreach (['cooperatives' => 'max_cooperatives', 'products' => 'max_products', 'staff' => 'max_staff'] as $key => $column) {
            if ($plan[$column] !== null && $usage[$key] > (int) $plan[$column]) {
                return false;
            }
        }
        return true;
    }

    public static function planShape(array $row)
    {
        $features = json_decode((string) $row['features_json'], true);
        return [
            'id'               => (int) $row['id'],
            'code'             => $row['code'],
            'name'             => $row['name'],
            'price_monthly'    => (int) $row['price_monthly'],
            'price_yearly'     => (int) $row['price_yearly'],
            'max_cooperatives' => $row['max_cooperatives'] === null ? null : (int) $row['max_cooperatives'],
            'max_products'     => $row['max_products'] === null ? null : (int) $row['max_products'],
            'max_staff'        => $row['max_staff'] === null ? null : (int) $row['max_staff'],
            'features'         => is_array($features) ? $features : [],
        ];
    }
}
