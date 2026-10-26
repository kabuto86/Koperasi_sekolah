<?php
// Papan pemuka vendor untuk satu koperasi (PELAN 5.2 #1, 18.8). Jualan = pesanan yang sudah dibayar (paid_at) ikut tarikh bayar,
// tidak termasuk dibatalkan/luput. Pengumuman platform yang sudah terbit untuk semua vendor atau vendor ini.
class GetVendorDashboardJob
{
    const UNSOLD = "('cancelled', 'expired')";

    private $vendorId;
    private $cooperativeId;

    public function __construct($vendorId, $cooperativeId)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
    }

    public function run()
    {
        $args = [$this->vendorId, $this->cooperativeId];
        $today = Database::fetchOne(
            "SELECT COALESCE(SUM(total), 0) AS amount, COUNT(*) AS orders FROM orders
              WHERE vendor_id = ? AND cooperative_id = ? AND paid_at >= CURDATE() AND paid_at < CURDATE() + INTERVAL 1 DAY
                AND status NOT IN " . self::UNSOLD,
            $args
        );
        $counts = Database::fetchOne(
            "SELECT COALESCE(SUM(status IN ('paid', 'unpaid_cash', 'preparing')), 0) AS to_prepare,
                    COALESCE(SUM(status IN ('ready', 'partially_collected')), 0) AS ready
               FROM orders WHERE vendor_id = ? AND cooperative_id = ?",
            $args
        );
        $lowWhere = ' AND p.is_active = 1' . ListInventoryJob::LOW_SQL;
        $low = InventoryRows::fetch($this->vendorId, $this->cooperativeId, $lowWhere);
        usort($low, function ($a, $b) {
            return $a['qty_available'] - $b['qty_available'];
        });

        return [
            'sales_today'       => (int) $today['amount'],
            'sales_today_count' => (int) $today['orders'],
            'orders_to_prepare' => (int) $counts['to_prepare'],
            'orders_ready'      => (int) $counts['ready'],
            'low_stock_count'   => count($low),
            'low_stock'         => array_slice($low, 0, 6),
            'sales_7days'       => $this->salesLastDays(7),
            'announcements'     => $this->announcements(),
        ];
    }

    // [{ date: 'YYYY-MM-DD', amount }] — hari tanpa jualan = 0
    private function salesLastDays($days)
    {
        $rows = Database::fetchAll(
            "SELECT DATE(paid_at) AS day, SUM(total) AS amount FROM orders
              WHERE vendor_id = ? AND cooperative_id = ? AND paid_at >= CURDATE() - INTERVAL ? DAY AND status NOT IN " . self::UNSOLD . "
              GROUP BY DATE(paid_at)",
            [$this->vendorId, $this->cooperativeId, $days - 1]
        );
        $byDay = array_column($rows, 'amount', 'day');
        $result = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = date('Y-m-d', strtotime('-' . $offset . ' day'));
            $result[] = ['date' => $day, 'amount' => (int) ($byDay[$day] ?? 0)];
        }
        return $result;
    }

    private function announcements()
    {
        $rows = Database::fetchAll(
            "SELECT a.id, a.title, a.body, a.published_at FROM vendor_announcements a
              WHERE a.published_at IS NOT NULL AND a.published_at <= NOW()
                AND (a.target = 'all' OR EXISTS (SELECT 1 FROM vendor_announcement_targets t WHERE t.announcement_id = a.id AND t.vendor_id = ?))
              ORDER BY a.published_at DESC LIMIT 5",
            [$this->vendorId]
        );
        return array_map(function ($row) {
            return ['id' => (int) $row['id'], 'title' => $row['title'], 'body' => $row['body'], 'published_at' => IsoDate::format($row['published_at'])];
        }, $rows);
    }
}
