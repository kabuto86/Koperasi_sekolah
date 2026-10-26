<?php
// Papan pemuka owner (PELAN 5.1): MRR, vendor ikut status, koperasi, pesanan bulan ini, langganan hampir tamat.
// MRR = jumlah bulanan setara langganan aktif / past_due: harga pelan + item langganan (koperasi, modul, template)
// yang masih berjalan; kitaran tahunan dibahagi 12. Sejarah 6 bulan dikira dengan harga snapshot yang sama.
class GetOwnerDashboardJob
{
    const EXPIRING_DAYS = 30;
    const HISTORY_MONTHS = 6;

    public function run()
    {
        $today = date('Y-m-d');
        $history = $this->mrrHistory();
        $current = $this->mrrAt($today, true);
        $previous = $history[count($history) - 2]['amount'];

        return [
            'mrr'                     => $current,
            'mrr_change_percent'      => $previous > 0 ? round(($current - $previous) * 100 / $previous, 1) : 0,
            'vendors'                 => $this->vendorCounts(),
            'cooperatives_total'      => (int) Database::fetchValue("SELECT COUNT(*) FROM cooperatives"),
            'cooperatives_active'     => (int) Database::fetchValue("SELECT COUNT(*) FROM cooperatives WHERE status = 'active'"),
            'orders_this_month'       => (int) Database::fetchValue(
                "SELECT COUNT(*) FROM orders WHERE created_at >= ? AND status NOT IN ('cancelled','expired')", [date('Y-m-01')]
            ),
            'orders_value_this_month' => (int) Database::fetchValue(
                "SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at >= ? AND paid_at IS NOT NULL AND status <> 'refunded'", [date('Y-m-01')]
            ),
            'invoices_unpaid'         => (int) Database::fetchValue("SELECT COUNT(*) FROM invoices WHERE status = 'issued'"),
            'mrr_history'             => $history,
            'expiring'                => $this->expiring($today),
        ];
    }

    private function vendorCounts()
    {
        $counts = ['trial' => 0, 'active' => 0, 'past_due' => 0, 'suspended' => 0, 'cancelled' => 0];
        foreach (Database::fetchAll("SELECT status, COUNT(*) AS total FROM vendors GROUP BY status") as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    // [{ month: 'YYYY-MM', amount }] — 6 bulan terakhir termasuk bulan ini (bulan ini = MRR semasa)
    private function mrrHistory()
    {
        $history = [];
        for ($i = self::HISTORY_MONTHS - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));
            $date = $i === 0 ? date('Y-m-d') : date('Y-m-t', strtotime($month . '-01'));
            $history[] = ['month' => $month, 'amount' => $this->mrrAt($date, $i === 0)];
        }
        return $history;
    }

    // $isNow: guna status semasa; bulan lalu: langganan yang sudah bermula dan belum dibatalkan pada tarikh itu
    private function mrrAt($date, $isNow)
    {
        $statusSql = $isNow ? " AND s.status IN ('active','past_due')" : " AND s.status <> 'trial'";
        $subscriptions = Database::fetchAll(
            "SELECT s.id, s.billing_cycle, p.price_monthly, p.price_yearly FROM subscriptions s JOIN plans p ON p.id = s.plan_id
              WHERE s.started_at <= ? AND (s.cancelled_at IS NULL OR s.cancelled_at > ?)" . $statusSql,
            [$date, $date . ' 23:59:59']
        );
        $total = 0;
        foreach ($subscriptions as $sub) {
            $yearly = $sub['billing_cycle'] === 'yearly';
            $items = (int) Database::fetchValue(
                "SELECT COALESCE(SUM(unit_price), 0) FROM subscription_items
                  WHERE subscription_id = ? AND started_at <= ? AND (ended_at IS NULL OR ended_at >= ?)",
                [$sub['id'], $date, $date]
            );
            $amount = ($yearly ? (int) $sub['price_yearly'] : (int) $sub['price_monthly']) + $items;
            $total += $yearly ? intdiv($amount, 12) : $amount;
        }
        return $total;
    }

    private function expiring($today)
    {
        $rows = Database::fetchAll(
            "SELECT s.id, s.vendor_id, v.code AS vendor_code, v.company_name AS vendor_name, s.plan_id, p.name AS plan_name,
                    s.billing_cycle, s.status, s.started_at, s.current_period_start, s.current_period_end,
                    (SELECT COUNT(*) FROM cooperatives c WHERE c.vendor_id = s.vendor_id AND c.status <> 'closed') AS cooperative_count
               FROM subscriptions s JOIN vendors v ON v.id = s.vendor_id JOIN plans p ON p.id = s.plan_id
              WHERE s.status IN ('active','trial','past_due') AND s.current_period_end <= ?
              ORDER BY s.current_period_end LIMIT 20",
            [date('Y-m-d', strtotime($today . ' +' . self::EXPIRING_DAYS . ' days'))]
        );
        return array_map(function ($row) {
            foreach (['id', 'vendor_id', 'plan_id', 'cooperative_count'] as $field) {
                $row[$field] = (int) $row[$field];
            }
            return $row;
        }, $rows);
    }
}
