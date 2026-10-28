<?php
// Jualan ikut tempoh (harian / bulanan): pesanan dibayar ikut tarikh bayar + refund selesai ikut tarikh refund.
// Bersih = jumlah (selepas diskaun + cukai) − refund; tunai / online ikut kaedah bayaran pesanan.
class PeriodSales
{
    // $format = format DATE_FORMAT MySQL ('%Y-%m-%d' atau '%Y-%m'); $key = nama lajur tempoh dalam baris
    public static function rows($vendorId, $cooperativeId, $from, $to, $format, $key)
    {
        $args = [(int) $vendorId, (int) $cooperativeId];
        list($paidSql, $paidArgs) = ReportRange::between('o.paid_at', $from, $to);
        $sales = Database::fetchAll(
            "SELECT DATE_FORMAT(o.paid_at, '$format') AS period, COUNT(*) AS orders, SUM(o.subtotal) AS gross, SUM(o.discount) AS discount,
                    SUM(o.total) AS total, SUM(CASE WHEN o.payment_method = 'cash' THEN o.total ELSE 0 END) AS cash
               FROM orders o WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL$paidSql
              GROUP BY period",
            array_merge($args, $paidArgs)
        );
        list($refundSql, $refundArgs) = ReportRange::between('r.created_at', $from, $to);
        $refunds = Database::fetchAll(
            "SELECT DATE_FORMAT(r.created_at, '$format') AS period, SUM(r.amount) AS amount,
                    SUM(CASE WHEN o.payment_method = 'cash' THEN r.amount ELSE 0 END) AS cash
               FROM refunds r JOIN orders o ON o.id = r.order_id AND o.vendor_id = r.vendor_id AND o.cooperative_id = r.cooperative_id
              WHERE r.vendor_id = ? AND r.cooperative_id = ? AND r.status = 'completed' AND o.paid_at IS NOT NULL$refundSql
              GROUP BY period",
            array_merge($args, $refundArgs)
        );

        $rows = [];
        foreach ($sales as $sale) {
            $rows[$sale['period']] = self::row($key, $sale['period']);
            $rows[$sale['period']]['orders'] = (int) $sale['orders'];
            $rows[$sale['period']]['gross'] = (int) $sale['gross'];
            $rows[$sale['period']]['discount'] = (int) $sale['discount'];
            $rows[$sale['period']]['net'] = (int) $sale['total'];
            $rows[$sale['period']]['cash'] = (int) $sale['cash'];
            $rows[$sale['period']]['online'] = (int) $sale['total'] - (int) $sale['cash'];
        }
        foreach ($refunds as $refund) {
            $row = $rows[$refund['period']] ?? self::row($key, $refund['period']);
            $row['refunds'] += (int) $refund['amount'];
            $row['net'] -= (int) $refund['amount'];
            $row['cash'] -= (int) $refund['cash'];
            $row['online'] -= (int) $refund['amount'] - (int) $refund['cash'];
            $rows[$refund['period']] = $row;
        }
        ksort($rows);
        return array_values($rows);
    }

    private static function row($key, $period)
    {
        return [$key => $period, 'orders' => 0, 'gross' => 0, 'discount' => 0, 'refunds' => 0, 'net' => 0, 'cash' => 0, 'online' => 0];
    }

    const COLUMNS = [['orders', 'int'], ['gross', 'money'], ['discount', 'money'], ['refunds', 'money'], ['net', 'money'], ['cash', 'money'], ['online', 'money']];

    const TOTAL_KEYS = ['orders', 'gross', 'discount', 'refunds', 'net', 'cash', 'online'];
}
