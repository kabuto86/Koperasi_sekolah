<?php
// Laporan tunai vs online (type payment): pesanan dibayar ikut kaedah, refund selesai ikut tarikh refund,
// saluran = gateway yang menerima bayaran online dalam julat. Bahagian = amaun ÷ jumlah semua kaedah.
class PaymentMethodReportJob
{
    private $vendorId;
    private $cooperativeId;
    private $from;
    private $to;

    public function __construct($vendorId, $cooperativeId, $from, $to)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->from = $from;
        $this->to = $to;
    }

    public function run()
    {
        $args = [$this->vendorId, $this->cooperativeId];
        list($paidSql, $paidArgs) = ReportRange::between('o.paid_at', $this->from, $this->to);
        $sales = Database::fetchAll(
            "SELECT o.payment_method AS method, COUNT(*) AS orders, SUM(o.total) AS amount
               FROM orders o WHERE o.vendor_id = ? AND o.cooperative_id = ? AND o.paid_at IS NOT NULL$paidSql
              GROUP BY o.payment_method",
            array_merge($args, $paidArgs)
        );
        list($refundSql, $refundArgs) = ReportRange::between('r.created_at', $this->from, $this->to);
        $refunds = Database::fetchAll(
            "SELECT o.payment_method AS method, SUM(r.amount) AS amount
               FROM refunds r JOIN orders o ON o.id = r.order_id AND o.vendor_id = r.vendor_id AND o.cooperative_id = r.cooperative_id
              WHERE r.vendor_id = ? AND r.cooperative_id = ? AND r.status = 'completed' AND o.paid_at IS NOT NULL$refundSql
              GROUP BY o.payment_method",
            array_merge($args, $refundArgs)
        );
        list($gatewaySql, $gatewayArgs) = ReportRange::between('p.verified_at', $this->from, $this->to);
        $channel = (string) Database::fetchValue(
            "SELECT GROUP_CONCAT(DISTINCT pg.name ORDER BY pg.name SEPARATOR ', ')
               FROM payments p JOIN payment_gateways pg ON pg.code = p.gateway_code
              WHERE p.vendor_id = ? AND p.cooperative_id = ? AND p.method = 'online' AND p.status IN ('paid', 'refunded')$gatewaySql",
            array_merge($args, $gatewayArgs)
        );

        $rows = [];
        foreach (['cash', 'online'] as $method) {
            $rows[$method] = ['method' => $method, 'channel' => $method === 'online' ? $channel : '', 'orders' => 0, 'amount' => 0, 'refunds' => 0];
        }
        foreach ($sales as $sale) {
            $rows[$sale['method']]['orders'] = (int) $sale['orders'];
            $rows[$sale['method']]['amount'] = (int) $sale['amount'];
        }
        foreach ($refunds as $refund) {
            $rows[$refund['method']]['refunds'] = (int) $refund['amount'];
        }
        $rows = array_values(array_filter($rows, function ($row) {
            return $row['orders'] > 0 || $row['refunds'] > 0;
        }));
        $totals = ReportRange::sum($rows, ['orders', 'amount', 'refunds']);
        foreach ($rows as &$row) {
            $row['share'] = ReportRange::percent($row['amount'], $totals['amount']);
        }
        unset($row);
        $totals['share'] = $rows ? 100 : 0;
        return [
            'columns' => [['method', 'method'], ['channel', 'text'], ['orders', 'int'], ['amount', 'money'], ['refunds', 'money'], ['share', 'percent']],
            'rows'    => $rows,
            'totals'  => $totals,
            'chart'   => array_map(function ($row) {
                return ['label' => t('payment.method.' . $row['method']), 'value' => $row['amount']];
            }, $rows),
        ];
    }
}
